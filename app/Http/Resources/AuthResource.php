<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="AuthResource",
 *     type="object",
 *     @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="username", type="string", example="jdupont"),
 *     @OA\Property(property="role", type="string", example="CLIENT")
 * )
 */
class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this['token'],
            'user_id' => $this['user_id'],
            'username' => $this['username'],
            'role' => $this['role'],
        ];
    }
}
