<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Afficher tous les paiements
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['reservation', 'reservation.user', 'reservation.room']);

        // Filtrer par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrer par méthode de paiement
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filtrer par réservation
        if ($request->has('reservation_id')) {
            $query->where('reservation_id', $request->reservation_id);
        }

        // Filtrer par utilisateur (via la réservation)
        if ($request->has('user_id')) {
            $query->whereHas('reservation', function($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        // Filtrer par date
        if ($request->has('from_date')) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        // Tri
        $sortBy = $request->sort_by ?? 'payment_date';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $payments = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Enregistrer un nouveau paiement
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|exists:reservations,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'transaction_id' => 'sometimes|string',
            'status' => 'required|string|in:pending,completed,failed,refunded',
            'notes' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si la réservation existe
        $reservation = Reservation::find($request->reservation_id);
        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Réservation non trouvée'
            ], 404);
        }

        // Vérifier si le montant du paiement n'excède pas le montant restant à payer
        $totalPaid = $reservation->payments()->where('status', 'completed')->sum('amount');
        $remainingAmount = $reservation->total_amount - $totalPaid;

        if ($request->amount > $remainingAmount && $request->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Le montant du paiement excède le montant restant à payer',
                'data' => [
                    'total_amount' => $reservation->total_amount,
                    'total_paid' => $totalPaid,
                    'remaining_amount' => $remainingAmount,
                    'payment_amount' => $request->amount
                ]
            ], 400);
        }

        $payment = Payment::create([
            'reservation_id' => $request->reservation_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_date' => $request->payment_date,
            'transaction_id' => $request->transaction_id,
            'status' => $request->status,
            'notes' => $request->notes
        ]);

        // Mettre à jour le statut de la réservation si le paiement est complété
        if ($request->status === 'completed') {
            $newTotalPaid = $totalPaid + $request->amount;

            // Si le paiement complet est effectué, mettre à jour le statut de la réservation
            if ($newTotalPaid >= $reservation->total_amount && $reservation->status === 'pending') {
                $reservation->status = 'confirmed';
                $reservation->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Paiement enregistré avec succès',
            'data' => $payment->load(['reservation', 'reservation.user'])
        ], 201);
    }

    /**
     * Afficher un paiement spécifique
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $payment = Payment::with(['reservation', 'reservation.user', 'reservation.room'])->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Mettre à jour un paiement
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|numeric|min:0',
            'payment_method' => 'sometimes|string',
            'payment_date' => 'sometimes|date',
            'transaction_id' => 'sometimes|string',
            'status' => 'sometimes|string|in:pending,completed,failed,refunded',
            'notes' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Si le statut du paiement passe à "completed"
        if ($request->has('status') && $request->status === 'completed' && $payment->status !== 'completed') {
            $reservation = $payment->reservation;

            // Vérifier si le nouveau montant n'excède pas le montant restant à payer
            $totalPaid = $reservation->payments()
                ->where('status', 'completed')
                ->where('id', '!=', $payment->id)
                ->sum('amount');

            $newAmount = $request->has('amount') ? $request->amount : $payment->amount;
            $remainingAmount = $reservation->total_amount - $totalPaid;

            if ($newAmount > $remainingAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant du paiement excède le montant restant à payer',
                    'data' => [
                        'total_amount' => $reservation->total_amount,
                        'total_paid' => $totalPaid,
                        'remaining_amount' => $remainingAmount,
                        'payment_amount' => $newAmount
                    ]
                ], 400);
            }

            // Si le paiement complet est effectué, mettre à jour le statut de la réservation
            $newTotalPaid = $totalPaid + $newAmount;
            if ($newTotalPaid >= $reservation->total_amount && $reservation->status === 'pending') {
                $reservation->status = 'confirmed';
                $reservation->save();
            }
        }

        // Si le statut passe de "completed" à autre chose, vérifier l'impact sur la réservation
        if ($payment->status === 'completed' && $request->has('status') && $request->status !== 'completed') {
            $reservation = $payment->reservation;

            // Recalculer le total payé sans ce paiement
            $totalPaid = $reservation->payments()
                ->where('status', 'completed')
                ->where('id', '!=', $payment->id)
                ->sum('amount');

            // Si le total payé devient insuffisant et que la réservation est confirmée, la repasser en attente
            if ($totalPaid < $reservation->total_amount && $reservation->status === 'confirmed') {
                $reservation->status = 'pending';
                $reservation->save();
            }
        }

        $payment->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Paiement mis à jour avec succès',
            'data' => $payment->fresh(['reservation', 'reservation.user'])
        ]);
    }

    /**
     * Supprimer un paiement
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }

        // Si le paiement était complété, vérifier l'impact sur la réservation
        if ($payment->status === 'completed') {
            $reservation = $payment->reservation;

            // Recalculer le total payé sans ce paiement
            $totalPaid = $reservation->payments()
                ->where('status', 'completed')
                ->where('id', '!=', $id)
                ->sum('amount');

            // Si le total payé devient insuffisant et que la réservation est confirmée, la repasser en attente
            if ($totalPaid < $reservation->total_amount && $reservation->status === 'confirmed') {
                $reservation->status = 'pending';
                $reservation->save();
            }
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paiement supprimé avec succès'
        ]);
    }
}
