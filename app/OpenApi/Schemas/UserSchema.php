<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="Modèle d'utilisateur du système"
 * )
 */
class UserSchema
{
    /**
     * @OA\Property(property="id", type="integer", example=1)
     */
    public $id;

    /**
     * @OA\Property(property="username", type="string", example="johndoe")
     */
    public $username;

    /**
     * @OA\Property(property="email", type="string", format="email", example="john@example.com")
     */
    public $email;

    /**
     * @OA\Property(property="first_name", type="string", example="John")
     */
    public $first_name;

    /**
     * @OA\Property(property="last_name", type="string", example="Doe")
     */
    public $last_name;

    /**
     * @OA\Property(property="phone_number", type="string", example="+33123456789")
     */
    public $phone_number;

    /**
     * @OA\Property(property="role", type="string", enum={"ADMIN", "CLIENT"}, example="CLIENT")
     */
    public $role;

    /**
     * @OA\Property(property="enabled", type="boolean", example=true)
     */
    public $enabled;

    /**
     * @OA\Property(property="created_at", type="string", format="date-time")
     */
    public $created_at;

    /**
     * @OA\Property(property="updated_at", type="string", format="date-time")
     */
    public $updated_at;
}
