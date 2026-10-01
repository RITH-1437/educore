<?php

namespace App\Http\Resources;

use App\Models\ScheduleEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ScheduleEntry
 */
class ScheduleEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'day_of_week' => $this->day_of_week,
            'day' => ScheduleEntry::DAYS[$this->day_of_week] ?? null,
            'start_time' => $this->startsAt(),
            'end_time' => $this->endsAt(),
            'room' => $this->whenLoaded('room', fn () => ['id' => $this->room->id, 'code' => $this->room->code, 'name' => $this->room->name, 'capacity' => $this->room->capacity]),
        ];
    }
}
