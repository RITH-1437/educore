<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A kind of official document a student may request (`skills/documents` §4).
 *
 * Only codes listed in `GENERATABLE` have a template, because every document
 * is rendered from authoritative data — never from a hand-filled form.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $requires_fee
 * @property bool $is_active
 * @property int $sort_order
 */
class DocumentType extends Model
{
    public const ENROLLMENT_CERTIFICATE = 'enrollment_certificate';

    public const TRANSCRIPT = 'transcript';

    public const ACADEMIC_RESULT = 'academic_result';

    public const STUDENT_CERTIFICATE = 'student_certificate';

    public const INTERNSHIP_LETTER = 'internship_letter';

    /** Codes with a template; `academic_result` is per semester. */
    public const GENERATABLE = [self::ENROLLMENT_CERTIFICATE, self::STUDENT_CERTIFICATE, self::TRANSCRIPT, self::ACADEMIC_RESULT, self::INTERNSHIP_LETTER];

    protected $fillable = ['code', 'name', 'description', 'requires_fee', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['requires_fee' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function needsSemester(): bool
    {
        return $this->code === self::ACADEMIC_RESULT;
    }
}
