<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="API pour l'authentification des utilisateurs"
 * )
 */
class AuthController extends Controller
{
    public function __construct()
    {
        // Appliquer middleware seulement pour logout
        $this->middleware('auth:api', ['only' => ['logout']]);
    }
    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Inscription d'un nouvel utilisateur",
     *     description="Permet à un utilisateur de créer un compte",
     *     operationId="register",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données d'inscription",
     *         @OA\JsonContent(ref="#/components/schemas/RegisterRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Inscription réussie",
     *         @OA\JsonContent(ref="#/components/schemas/AuthResource")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données d'inscription invalides"
     *     )
     * )
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'role' => UserRole::CLIENT,
            'enabled' => true,
        ]);

        $token = JWTAuth::fromUser($user);

        return $this->respondWithToken($token, $user);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Connexion d'un utilisateur",
     *     description="Authentifie un utilisateur et retourne un token JWT",
     *     operationId="login",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données de connexion",
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(ref="#/components/schemas/AuthResource")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides"
     *     )
     * )
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('username', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Identifiants invalides',
                'success' => false
            ], 401);
        }

        $user = Auth::user();

        return $this->respondWithToken($token, $user);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Déconnexion de l'utilisateur",
     *     description="Déconnecte l'utilisateur actuellement authentifié et invalide son token JWT",
     *     operationId="logout",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie"),
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        return response()->json([
            'message' => 'Déconnexion réussie',
            'success' => true
        ]);
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     * @param User $user
     * @return JsonResponse
     */
    protected function respondWithToken(string $token, User $user): JsonResponse
    {
        return response()->json([
            'message' => 'Authentification réussie',
            'success' => true,
            'data' => new AuthResource([
                'token' => $token,
                'user_id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
            ])
        ]);
    }
}
