<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The company supervisor's or the academic (university-side) evaluation of an internship —
 * at most one of each, entered by staff (supervisors have no login).
 *
 * @property int $id
 * @property int $internship_id
 * @property string $evaluator_type
 * @property string|null $evaluator_name
 * @property numeric-string|null $score
 * @property string|null $rating
 * @property string|null $comments
 * @property Carbon|null $evaluated_at
 * @property int|null $submitted_by
 */
class InternshipEvaluation extends Model
{
    public const TYPES = ['supervisor', 'academic'];

    public const RATINGS = ['excellent', 'good', 'satisfactory', 'needs_improvement', 'poor'];

    protected $fillable = ['internship_id', 'evaluator_type', 'evaluator_name', 'score', 'rating', 'comments', 'evaluated_at', 'submitted_by'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'evaluated_at' => 'datetime'];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }
}
