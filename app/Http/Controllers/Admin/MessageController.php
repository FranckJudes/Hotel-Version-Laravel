<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Messages (Admin)",
 *     description="API de gestion des messages internes - Partie admin"
 * )
 */
class MessageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/messages/inbox",
     *     summary="Récupérer les messages reçus",
     *     description="Récupère la liste des messages reçus avec possibilité de filtrage",
     *     operationId="getInboxMessages",
     *     tags={"Messages (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="read",
     *         in="query",
     *         description="Filtrer par statut de lecture",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="sender_id",
     *         in="query",
     *         description="Filtrer par expéditeur",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche dans le sujet ou le contenu",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des messages reçus récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Message")
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=20)
     *             ),
     *             @OA\Property(property="unread_count", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès interdit - Droits administrateur requis"
     *     )
     * )
     *
     * Afficher tous les messages (boîte de réception)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function inbox(Request $request): JsonResponse
    {
        $query = Message::with('sender')
            ->where('recipient_id', Auth::id());

        // Filtrer par lu/non lu
        if ($request->has('read')) {
            $query->where('read', $request->boolean('read'));
        }

        // Filtrer par expéditeur
        if ($request->has('sender_id')) {
            $query->where('sender_id', $request->sender_id);
        }

        // Recherche dans le sujet ou le contenu
        if ($request->has('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', $search)
                  ->orWhere('content', 'like', $search);
            });
        }

        // Tri par date de réception
        $query->orderBy('created_at', 'desc');

        $messages = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $messages,
            'unread_count' => Message::where('recipient_id', Auth::id())->where('read', false)->count()
        ]);
    }

    /**
     * Afficher tous les messages envoyés
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sent(Request $request): JsonResponse
    {
        $query = Message::with('recipient')
            ->where('sender_id', Auth::id());

        // Filtrer par destinataire
        if ($request->has('recipient_id')) {
            $query->where('recipient_id', $request->recipient_id);
        }

        // Recherche dans le sujet ou le contenu
        if ($request->has('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', $search)
                  ->orWhere('content', 'like', $search);
            });
        }

        // Tri par date d'envoi
        $query->orderBy('created_at', 'desc');

        $messages = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Envoyer un nouveau message
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'parent_id' => 'sometimes|exists:messages,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si le destinataire existe
        $recipient = User::find($request->recipient_id);
        if (!$recipient) {
            return response()->json([
                'success' => false,
                'message' => 'Destinataire non trouvé'
            ], 404);
        }

        $message = Message::create([
            'sender_id' => Auth::id(),
            'recipient_id' => $request->recipient_id,
            'subject' => $request->subject,
            'content' => $request->content,
            'parent_id' => $request->parent_id,
            'read' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message envoyé avec succès',
            'data' => $message->load(['sender', 'recipient'])
        ], 201);
    }

    /**
     * Afficher un message spécifique
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $message = Message::with(['sender', 'recipient', 'parent', 'replies'])->find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouvé'
            ], 404);
        }

        // Vérifier si l'utilisateur est autorisé à voir le message
        if ($message->sender_id !== Auth::id() && $message->recipient_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à voir ce message'
            ], 403);
        }

        // Marquer le message comme lu si l'utilisateur est le destinataire
        if ($message->recipient_id === Auth::id() && !$message->read) {
            $message->read = true;
            $message->save();
        }

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    /**
     * Marquer un message comme lu
     *
     * @param int $id
     * @return JsonResponse
     */
    public function markAsRead(int $id): JsonResponse
    {
        $message = Message::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouvé'
            ], 404);
        }

        // Vérifier si l'utilisateur est le destinataire
        if ($message->recipient_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier ce message'
            ], 403);
        }

        $message->read = true;
        $message->save();

        return response()->json([
            'success' => true,
            'message' => 'Message marqué comme lu',
            'data' => $message->fresh(['sender', 'recipient'])
        ]);
    }

    /**
     * Marquer plusieurs messages comme lus
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message_ids' => 'required|array',
            'message_ids.*' => 'integer|exists:messages,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mettre à jour uniquement les messages dont l'utilisateur est le destinataire
        $updatedCount = Message::whereIn('id', $request->message_ids)
            ->where('recipient_id', Auth::id())
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json([
            'success' => true,
            'message' => $updatedCount . ' message(s) marqué(s) comme lu(s)',
            'data' => [
                'updated_count' => $updatedCount,
                'unread_count' => Message::where('recipient_id', Auth::id())->where('read', false)->count()
            ]
        ]);
    }

    /**
     * Supprimer un message
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $message = Message::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouvé'
            ], 404);
        }

        // Vérifier si l'utilisateur est autorisé à supprimer le message
        if ($message->sender_id !== Auth::id() && $message->recipient_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à supprimer ce message'
            ], 403);
        }

        // Si l'utilisateur est l'expéditeur, marquer comme supprimé par l'expéditeur
        if ($message->sender_id === Auth::id()) {
            $message->deleted_by_sender = true;
        }

        // Si l'utilisateur est le destinataire, marquer comme supprimé par le destinataire
        if ($message->recipient_id === Auth::id()) {
            $message->deleted_by_recipient = true;
        }

        // Si le message est supprimé par les deux parties, le supprimer réellement
        if ($message->deleted_by_sender && $message->deleted_by_recipient) {
            $message->delete();
        } else {
            $message->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Message supprimé avec succès'
        ]);
    }

    /**
     * Obtenir la liste des utilisateurs pour l'autocomplétion
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsersForAutocomplete(Request $request): JsonResponse
    {
        $query = $request->query('q', '');

        $users = User::where('id', '!=', Auth::id())
            ->where(function($q) use ($query) {
                $q->where('username', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->select('id', 'username', 'email', 'first_name', 'last_name')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}
