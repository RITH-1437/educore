<?php

namespace Tests\Feature\Documents;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\GpaService;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentFeeAndTypeTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $univAdmin;

    private User $deptAdmin;

    private Student $student;

    private Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $this->seed(DocumentTypeSeeder::class);

        $department = Department::factory()->create();
        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->univAdmin = $this->userWithRole('university-admin');
        $this->deptAdmin = $this->departmentAdminFor($department);
        $this->student = Student::factory()->create();
        $this->placeInDepartment($this->student, $department);
        $this->semester = Semester::factory()->create(['status' => 'completed']);

        // Seed an approved grade so academic results/transcripts can be generated if needed
        $offering = CourseOffering::factory()->create([
            'course_id' => Course::factory()->create(['code' => 'CS102', 'credits' => 3])->id,
            'semester_id' => $this->semester->id,
        ]);
        $enrollment = Enrollment::query()->create([
            'student_id' => $this->student->id,
            'section_id' => Section::factory()->create(['course_offering_id' => $offering->id])->id,
            'academic_year_id' => $this->semester->academic_year_id,
            'semester_id' => $this->semester->id,
            'status' => 'completed',
            'enrolled_at' => now(),
        ]);
        Grade::query()->create([
            'enrollment_id' => $enrollment->id,
            'letter_grade' => 'A',
            'grade_point' => 4,
            'total_score' => 95,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        app(GpaService::class)->recalculate($this->student);
    }

    public function test_document_types_can_be_viewed_by_authorized_users(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/document-types')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DocumentTypes/Index')
                ->has('types.data')
                ->where('canManage', true)
            );

        $this->actingAs($this->univAdmin)
            ->get('/document-types')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DocumentTypes/Index')
                ->where('canManage', true)
            );

        $this->actingAs($this->deptAdmin)
            ->get('/document-types')
            ->assertForbidden();

        $this->actingAs($this->student->user)
            ->get('/document-types')
            ->assertForbidden();
    }

    public function test_api_document_types_crud_and_authorization(): void
    {
        // Dept admin and student cannot create
        $payload = [
            'name' => 'Graduation Letter',
            'code' => 'graduation_letter',
            'description' => 'Official proof of graduation',
            'requires_fee' => true,
            'fee_amount' => 15.50,
            'is_active' => true,
            'sort_order' => 10,
        ];

        $this->actingAs($this->deptAdmin)
            ->postJson('/api/document-types', $payload)
            ->assertForbidden();

        $this->actingAs($this->student->user)
            ->postJson('/api/document-types', $payload)
            ->assertForbidden();

        // Super Admin can create
        $createRes = $this->actingAs($this->superAdmin)
            ->postJson('/api/document-types', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Graduation Letter')
            ->assertJsonPath('data.code', 'graduation_letter')
            ->assertJsonPath('data.fee_amount', 15.5)
            ->assertJsonPath('data.requires_fee', true);

        $typeId = $createRes->json('data.id');

        // Univ Admin can update
        $this->actingAs($this->univAdmin)
            ->putJson("/api/document-types/{$typeId}", [
                'name' => 'Graduation Certificate',
                'code' => 'graduation_letter',
                'description' => 'Updated description',
                'requires_fee' => true,
                'fee_amount' => 20.00,
                'is_active' => true,
                'sort_order' => 12,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Graduation Certificate')
            ->assertJsonPath('data.fee_amount', 20);

        // Delete unreferenced document type
        $this->actingAs($this->univAdmin)
            ->deleteJson("/api/document-types/{$typeId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('document_types', ['id' => $typeId]);
    }

    public function test_document_type_delete_is_guarded_when_requests_exist(): void
    {
        $type = DocumentType::query()->where('code', DocumentType::TRANSCRIPT)->firstOrFail();

        // Student creates a request referencing this type
        $this->actingAs($this->student->user)
            ->postJson('/api/document-requests', [
                'document_type_id' => $type->id,
                'reason' => 'Job application',
            ])
            ->assertCreated();

        // Deleting should throw 409 Conflict
        $this->actingAs($this->superAdmin)
            ->deleteJson("/api/document-types/{$type->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('document_types', ['id' => $type->id]);
    }

    public function test_approving_fee_required_document_request_creates_invoice(): void
    {
        $transcriptType = DocumentType::query()->where('code', DocumentType::TRANSCRIPT)->firstOrFail();
        $this->assertTrue($transcriptType->requires_fee);
        $this->assertEquals(10.00, $transcriptType->fee_amount);

        // 1. Student requests transcript
        $requestId = $this->actingAs($this->student->user)
            ->postJson('/api/document-requests', [
                'document_type_id' => $transcriptType->id,
                'reason' => 'Scholarship',
            ])
            ->assertCreated()
            ->json('data.id');

        $req = DocumentRequest::query()->find($requestId);
        $this->assertNull($req->invoice_id);

        // 2. Department Admin approves request
        $this->actingAs($this->deptAdmin)
            ->postJson("/api/document-requests/{$requestId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $req->refresh();
        $this->assertNotNull($req->invoice_id);

        $invoice = Invoice::query()->find($req->invoice_id);
        $this->assertNotNull($invoice);
        $this->assertSame($this->student->id, $invoice->student_id);
        $this->assertSame(10.00, (float) $invoice->total);
        $this->assertSame('10.00', $invoice->balance());
        $this->assertSame(Invoice::STATUS_PENDING, $invoice->status);
        $this->assertCount(1, $invoice->items);
        $this->assertSame('Fee for Academic transcript', $invoice->items->first()->description);

        // 3. Attempting to generate PDF before invoice is paid is blocked (409)
        $this->actingAs($this->deptAdmin)
            ->postJson("/api/document-requests/{$requestId}/generate")
            ->assertStatus(409);

        // 4. Mark invoice as paid
        $invoice->update([
            'status' => Invoice::STATUS_PAID,
            'amount_paid' => 10.00,
        ]);

        // 5. PDF generation now succeeds
        $this->actingAs($this->deptAdmin)
            ->postJson("/api/document-requests/{$requestId}/generate")
            ->assertCreated()
            ->assertJsonPath('data.status', 'generated')
            ->assertJsonPath('data.document.status', 'valid');

        $doc = Document::query()->first();
        $this->assertNotNull($doc);
        Storage::disk('s3')->assertExists($doc->file_key);
    }

    public function test_approving_free_document_request_does_not_create_invoice(): void
    {
        $certType = DocumentType::query()->where('code', DocumentType::STUDENT_CERTIFICATE)->firstOrFail();
        $this->assertFalse($certType->requires_fee);

        $requestId = $this->actingAs($this->student->user)
            ->postJson('/api/document-requests', [
                'document_type_id' => $certType->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->univAdmin)
            ->postJson("/api/document-requests/{$requestId}/approve")
            ->assertOk();

        $req = DocumentRequest::query()->find($requestId);
        $this->assertNull($req->invoice_id);

        // Free document can be generated immediately
        $this->actingAs($this->univAdmin)
            ->postJson("/api/document-requests/{$requestId}/generate")
            ->assertCreated()
            ->assertJsonPath('data.status', 'generated');
    }

    public function test_student_and_type_options_include_fee_metadata(): void
    {
        // Type options endpoint
        $typesRes = $this->actingAs($this->student->user)
            ->getJson('/api/document-types')
            ->assertOk()
            ->json('data');

        $transcriptMeta = collect($typesRes)->firstWhere('code', DocumentType::TRANSCRIPT);
        $this->assertNotNull($transcriptMeta);
        $this->assertTrue($transcriptMeta['requires_fee']);
        $this->assertEquals(10.0, $transcriptMeta['fee_amount']);

        // Student's request listing shows fee and invoice details
        $type = DocumentType::query()->where('code', DocumentType::TRANSCRIPT)->firstOrFail();
        $req = DocumentRequest::query()->create([
            'student_id' => $this->student->id,
            'document_type_id' => $type->id,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $listRes = $this->actingAs($this->student->user)
            ->getJson('/api/document-requests')
            ->assertOk()
            ->json('data');

        $item = collect($listRes)->firstWhere('id', $req->id);
        $this->assertNotNull($item);
        $this->assertTrue($item['type']['requires_fee']);
        $this->assertEquals(10.0, $item['type']['fee_amount']);
        $this->assertNull($item['invoice']);
    }

    public function test_fee_waiver_cancels_pending_invoice_and_unlocks_generation(): void
    {
        $transcriptType = DocumentType::query()->where('code', DocumentType::TRANSCRIPT)->firstOrFail();
        $this->assertTrue($transcriptType->requires_fee);

        $requestId = $this->actingAs($this->student->user)
            ->postJson('/api/document-requests', [
                'document_type_id' => $transcriptType->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->deptAdmin)
            ->postJson("/api/document-requests/{$requestId}/approve")
            ->assertOk();

        $req = DocumentRequest::query()->find($requestId);
        $this->assertNotNull($req->invoice_id);
        $invoice = Invoice::query()->find($req->invoice_id);
        $this->assertSame(Invoice::STATUS_PENDING, $invoice->status);

        // Generation is blocked before waiver or payment
        $this->actingAs($this->deptAdmin)
            ->postJson("/api/document-requests/{$requestId}/generate")
            ->assertStatus(409);

        // Dept admin and student cannot waive fees (403)
        $this->actingAs($this->deptAdmin)
            ->postJson("/api/document-requests/{$requestId}/waive-fee", ['reason' => 'Dean waiver'])
            ->assertForbidden();

        $this->actingAs($this->student->user)
            ->postJson("/api/document-requests/{$requestId}/waive-fee", ['reason' => 'Self waiver'])
            ->assertForbidden();

        // Univ admin can waive fee
        $this->actingAs($this->univAdmin)
            ->postJson("/api/document-requests/{$requestId}/waive-fee", ['reason' => 'Scholarship recipient waiver'])
            ->assertOk()
            ->assertJsonPath('data.is_fee_waived', true)
            ->assertJsonPath('data.waiver_reason', 'Scholarship recipient waiver');

        $req->refresh();
        $invoice->refresh();
        $this->assertTrue($req->is_fee_waived);
        $this->assertSame('Scholarship recipient waiver', $req->waiver_reason);
        $this->assertSame($this->univAdmin->id, $req->waived_by);
        $this->assertNotNull($req->waived_at);
        $this->assertSame(Invoice::STATUS_CANCELLED, $invoice->status);

        // Dept admin can now generate PDF since fee is waived
        $this->actingAs($this->deptAdmin)
            ->postJson("/api/document-requests/{$requestId}/generate")
            ->assertCreated()
            ->assertJsonPath('data.status', 'generated')
            ->assertJsonPath('data.document.status', 'valid');
    }

    public function test_fee_waiver_fails_if_invoice_already_paid(): void
    {
        $transcriptType = DocumentType::query()->where('code', DocumentType::TRANSCRIPT)->firstOrFail();

        $requestId = $this->actingAs($this->student->user)
            ->postJson('/api/document-requests', [
                'document_type_id' => $transcriptType->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->deptAdmin)
            ->postJson("/api/document-requests/{$requestId}/approve")
            ->assertOk();

        $req = DocumentRequest::query()->find($requestId);
        $invoice = Invoice::query()->find($req->invoice_id);
        $invoice->update([
            'status' => Invoice::STATUS_PAID,
            'amount_paid' => 10.00,
        ]);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/document-requests/{$requestId}/waive-fee", ['reason' => 'Too late'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Cannot waive fee: the invoice is already paid.');
    }

    public function test_web_routes_for_document_fee_waiver(): void
    {
        $transcriptType = DocumentType::query()->where('code', DocumentType::TRANSCRIPT)->firstOrFail();

        $requestId = $this->actingAs($this->student->user)
            ->postJson('/api/document-requests', [
                'document_type_id' => $transcriptType->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->deptAdmin)
            ->postJson("/api/document-requests/{$requestId}/approve")
            ->assertOk();

        $this->actingAs($this->superAdmin)
            ->from('/documents')
            ->post("/document-requests/{$requestId}/waive-fee", [
                'reason' => 'Admin discretionary waiver',
            ])
            ->assertRedirect('/documents')
            ->assertSessionHas('success');

        $req = DocumentRequest::query()->find($requestId);
        $this->assertTrue($req->is_fee_waived);
        $this->assertSame('Admin discretionary waiver', $req->waiver_reason);
    }

    public function test_web_routes_for_document_types(): void
    {
        // Web create
        $this->actingAs($this->superAdmin)
            ->from('/document-types')
            ->post('/document-types', [
                'name' => 'Web Certificate',
                'code' => 'web_certificate',
                'description' => 'Test web certificate',
                'requires_fee' => true,
                'fee_amount' => 12.00,
                'is_active' => true,
                'sort_order' => 5,
            ])
            ->assertRedirect('/document-types')
            ->assertSessionHas('success');

        $type = DocumentType::query()->where('code', 'web_certificate')->firstOrFail();

        // Web update
        $this->actingAs($this->superAdmin)
            ->from('/document-types')
            ->put("/document-types/{$type->id}", [
                'name' => 'Web Certificate Updated',
                'code' => 'web_certificate',
                'description' => 'Test web certificate updated',
                'requires_fee' => false,
                'fee_amount' => 0.00,
                'is_active' => true,
                'sort_order' => 6,
            ])
            ->assertRedirect('/document-types')
            ->assertSessionHas('success');

        // Web delete
        $this->actingAs($this->superAdmin)
            ->from('/document-types')
            ->delete("/document-types/{$type->id}")
            ->assertRedirect('/document-types')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('document_types', ['id' => $type->id]);
    }

    private function userWithRole(string $slug): User
    {
        $role = Role::query()->firstWhere('slug', $slug) ?? Role::factory()->withSlug($slug)->create();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
