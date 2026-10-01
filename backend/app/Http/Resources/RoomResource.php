<?php

namespace App\Http\Resources;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Room
 */
class RoomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'building' => $this->building,
            'floor' => $this->floor,
            'capacity' => $this->capacity,
            'room_type' => $this->room_type,
            'is_active' => $this->is_active,
            'schedule_entries_count' => $this->whenCounted('scheduleEntries'),
        ];
    }
}
