<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     type="object",
 *     required={"username", "email", "password", "password_confirmation"},
 *     @OA\Property(property="username", type="string", example="jdupont", description="Nom d'utilisateur"),
 *     @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com", description="Adresse email"),
 *     @OA\Property(property="password", type="string", format="password", example="password123", description="Mot de passe"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="password123", description="Confirmation du mot de passe"),
 *     @OA\Property(property="first_name", type="string", example="Jean", description="Prénom"),
 *     @OA\Property(property="last_name", type="string", example="Dupont", description="Nom de famille"),
 *     @OA\Property(property="phone_number", type="string", example="0612345678", description="Numéro de téléphone")
 * )
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Le nom d\'utilisateur est obligatoire',
            'username.unique' => 'Ce nom d\'utilisateur est déjà utilisé',
            'email.required' => 'L\'adresse email est obligatoire',
            'email.email' => 'L\'adresse email n\'est pas valide',
            'email.unique' => 'Cette adresse email est déjà utilisée',
            'password.required' => 'Le mot de passe est obligatoire',
            'password.min' => 'Le mot de passe doit comporter au moins 8 caractères',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas',
        ];
    }
}
