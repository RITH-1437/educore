<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A host company — a lean record, not a CRM (`skills/internship` §12).
 * Inactive companies stay on past internships but cannot be chosen again.
 *
 * @property int $id
 * @property string $name
 * @property string|null $industry
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $address
 * @property string|null $website
 * @property bool $is_active
 */
class InternshipCompany extends Model
{
    protected $fillable = ['name', 'industry', 'contact_name', 'contact_email', 'contact_phone', 'address', 'website', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class, 'company_id');
    }
}
