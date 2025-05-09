<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Réinitialisation de mot de passe",
 *     description="API pour la réinitialisation de mot de passe"
 * )
 */
class PasswordResetController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/forgot-password",
     *     summary="Demander un lien de réinitialisation de mot de passe",
     *     description="Envoie un email contenant un lien de réinitialisation de mot de passe",
     *     operationId="forgotPassword",
     *     tags={"Réinitialisation de mot de passe"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Adresse email de l'utilisateur",
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email envoyé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email de réinitialisation de mot de passe envoyé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Adresse email invalide ou utilisateur non trouvé"
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email de réinitialisation de mot de passe envoyé'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/reset-password",
     *     summary="Réinitialiser le mot de passe",
     *     description="Réinitialise le mot de passe de l'utilisateur avec le token fourni par email",
     *     operationId="resetPassword",
     *     tags={"Réinitialisation de mot de passe"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données pour la réinitialisation du mot de passe",
     *         @OA\JsonContent(
     *             required={"token", "email", "password", "password_confirmation"},
     *             @OA\Property(property="token", type="string", example="8d8e8fbd3e90954057e991235eff7975e4d26922243e5a52b4333650c"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mot de passe réinitialisé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mot de passe réinitialisé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides ou token expiré"
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }
}
