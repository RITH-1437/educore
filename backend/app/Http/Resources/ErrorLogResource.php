<?php

namespace App\Http\Resources;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ErrorLog
 */
class ErrorLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status_code' => $this->status_code,
            'is_server_error' => $this->isServerError(),
            'is_not_found' => $this->isNotFound(),
            'method' => $this->method,
            'url' => $this->url,
            'route_name' => $this->route_name,
            'exception_class' => $this->exception_class,
            'message' => $this->message,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'user' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'context' => $this->context,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
