<?php

namespace App\Http\Resources;

use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Announcement
 */
class AnnouncementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'announcement_type' => $this->announcement_type,
            'audience_type' => $this->audience_type,
            'audience_id' => $this->audience_id,
            'audience' => app(AnnouncementService::class)->audienceLabel($this->resource),
            'publish_state' => $this->publish_state,
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'author' => $this->whenLoaded('author', fn () => ['id' => $this->author->id, 'name' => $this->author->name]),
            'attachments' => $this->relationLoaded('attachments')
                ? $this->attachments->map(fn ($f) => [
                    'id' => $f->id,
                    'original_name' => $f->original_name,
                    'mime_type' => $f->mime_type,
                    'size' => $f->size,
                    'download_url' => url("/api/announcements/{$this->id}/attachments/{$f->id}/download"),
                ])->values()
                : [],
        ];
    }
}
