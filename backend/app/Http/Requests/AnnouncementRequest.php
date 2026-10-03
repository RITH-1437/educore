<?php

namespace App\Http\Requests;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Announcement content and audience. Whether the author may reach the
 * audience (and that the target exists) is checked by `AnnouncementService`.
 */
class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'announcement_type' => ['nullable', Rule::in(Announcement::TYPES)],
            'audience_type' => ['required', Rule::in([...Announcement::GROUP_AUDIENCES, ...array_keys(Announcement::UNIT_AUDIENCES)])],
            'audience_id' => ['nullable', 'integer'],
            'publish' => ['sometimes', 'boolean'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,docx,xlsx,txt,zip'],
            'remove_attachment_ids' => ['sometimes', 'array'],
            'remove_attachment_ids.*' => ['integer'],
        ];
    }
}
