<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Statistic;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Statistiques (Admin)",
 *     description="API de statistiques de l'hôtel - Partie admin"
 * )
 */
class StatisticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/statistics/dashboard",
     *     summary="Récupérer les statistiques du tableau de bord",
     *     description="Récupère les principales statistiques pour le tableau de bord administrateur",
     *     operationId="getDashboardStatistics",
     *     tags={"Statistiques (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques récupérées avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_rooms", type="integer", example=60),
     *                 @OA\Property(property="total_reservations", type="integer", example=1250),
     *                 @OA\Property(property="total_users", type="integer", example=450),
     *                 @OA\Property(property="total_revenue", type="number", format="float", example=285000.50),
     *                 @OA\Property(property="reservations_this_week", type="integer", example=45),
     *                 @OA\Property(property="revenue_this_month", type="number", format="float", example=42500.75),
     *                 @OA\Property(property="occupancy_rate", type="number", format="float", example=78.5),
     *                 @OA\Property(property="upcoming_reservations", type="integer", example=85)
     *             )
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
     */
    public function dashboard(): JsonResponse
    {
        // Nombre total de réservations
        $totalBookings = Reservation::count();

        // Réservations à venir
        $upcomingBookings = Reservation::where('check_in', '>=', now())->count();

        // Chiffre d'affaires
        $revenueToday = Reservation::whereDate('created_at', today())
            ->sum('total_price');

        $revenueThisWeek = Reservation::whereBetween('created_at', [now()->startOfWeek(), now()])
            ->sum('total_price');

        $revenueThisMonth = Reservation::whereBetween('created_at', [now()->startOfMonth(), now()])
            ->sum('total_price');

        $revenueThisYear = Reservation::whereBetween('created_at', [now()->startOfYear(), now()])
            ->sum('total_price');

        // Taux d'occupation
        $totalRooms = Room::count();
        $occupiedRooms = Reservation::where('status', 'confirmed')
            ->orWhere('status', 'checked_in')
            ->where('check_in', '<=', now())
            ->where('check_out', '>=', now())
            ->distinct('room_id')
            ->count('room_id');

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        // Nombre total de clients
        $totalCustomers = Reservation::distinct('customer_email')->count('customer_email');

        // Chambres populaires
        $popularRooms = Reservation::select('room_id')
            ->selectRaw('COUNT(*) as booking_count')
            ->groupBy('room_id')
            ->orderByDesc('booking_count')
            ->limit(4)
            ->with('room:id,name')
            ->get()
            ->map(function($reservation) {
                return [
                    'roomId' => $reservation->room_id,
                    'bookingCount' => $reservation->booking_count,
                    'roomName' => $reservation->room->name
                ];
            });

        $dashboardStats = [
            'totalBookings' => $totalBookings,
            'upcomingBookings' => $upcomingBookings,
            'revenue' => [
                'today' => $revenueToday,
                'thisWeek' => $revenueThisWeek,
                'thisMonth' => $revenueThisMonth,
                'thisYear' => $revenueThisYear,
            ],
            'occupancyRate' => $occupancyRate,
            'totalCustomers' => $totalCustomers,
            'popularRooms' => $popularRooms,
        ];

        return response()->json([
            'success' => true,
            'data' => $dashboardStats
        ]);
    }
     /**
     * @OA\Get(
     *     path="/api/v1/admin/statistics/revenue",
     *     summary="Récupérer les revenus par période",
     *     description="Récupère les revenus par période (jour, semaine, mois, année)",
     *     operationId="getRevenueStatistics",
     *     tags={"Statistiques (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Période pour le calcul des revenus",
     *         required=false,
     *         @OA\Schema(type="string", enum={"day", "week", "month", "year"}, example="month")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Date de début pour le filtrage",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Date de fin pour le filtrage",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Revenus récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="date", type="string", format="date", example="2023-10-01"),
     *                     @OA\Property(property="total", type="number", format="float", example=12500.75)
     *                 )
     *             )
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
     */
    public function revenue(Request $request): JsonResponse
    {
        $period = $request->period ?? 'month';
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $query = Payment::where('status', 'completed')
            ->whereBetween('payment_date', [$startDate, $endDate]);

        if ($period === 'day') {
            $revenues = $query->select(
                DB::raw('DATE(payment_date) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $revenues
            ]);
        } elseif ($period === 'week') {
            $revenues = $query->select(
                DB::raw('YEAR(payment_date) as year'),
                DB::raw('WEEK(payment_date) as week'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('year', 'week')
            ->orderBy('year')
            ->orderBy('week')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $revenues
            ]);
        } elseif ($period === 'month') {
            $revenues = $query->select(
                DB::raw('YEAR(payment_date) as year'),
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $revenues
            ]);
        } else { // year
            $revenues = $query->select(
                DB::raw('YEAR(payment_date) as year'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $revenues
            ]);
        }
    }
      /**
     * @OA\Get(
     *     path="/api/v1/admin/statistics/occupancy",
     *     summary="Récupérer le taux d'occupation par période",
     *     description="Récupère le taux d'occupation par période (jour, mois, année)",
     *     operationId="getOccupancyStatistics",
     *     tags={"Statistiques (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Période pour le calcul du taux d'occupation",
     *         required=false,
     *         @OA\Schema(type="string", enum={"day", "month", "year"}, example="month")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Date de début pour le filtrage",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Date de fin pour le filtrage",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Taux d'occupation récupérés avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="date", type="string", format="date", example="2023-10-01"),
     *                     @OA\Property(property="occupancy_rate", type="number", format="float", example=78.5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès interdit - Droits administrateur requis"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Aucune chambre disponible pour calculer le taux d'occupation"
     *     )
     * )
     */
    public function occupancy(Request $request): JsonResponse
    {
        $period = $request->period ?? 'month';
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $totalRooms = Room::count();

        if ($totalRooms === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune chambre disponible pour calculer le taux d\'occupation'
            ], 400);
        }

        $query = Reservation::where('status', '!=', 'cancelled')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('check_in_date', [$startDate, $endDate])
                  ->orWhereBetween('check_out_date', [$startDate, $endDate])
                  ->orWhere(function($q2) use ($startDate, $endDate) {
                      $q2->where('check_in_date', '<', $startDate)
                         ->where('check_out_date', '>', $endDate);
                  });
            });

        if ($period === 'day') {
            $dateRange = new \DatePeriod(
                $startDate,
                new \DateInterval('P1D'),
                $endDate->modify('+1 day')
            );

            $occupancyData = [];

            foreach ($dateRange as $date) {
                $dateString = $date->format('Y-m-d');

                $occupiedRoomsCount = $query->whereDate('check_in_date', '<=', $dateString)
                    ->whereDate('check_out_date', '>', $dateString)
                    ->count();

                $occupancyRate = ($occupiedRoomsCount / $totalRooms) * 100;

                $occupancyData[] = [
                    'date' => $dateString,
                    'occupancy_rate' => round($occupancyRate, 2)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $occupancyData
            ]);
        } elseif ($period === 'month') {
            $startMonth = $startDate->copy()->startOfMonth();
            $endMonth = $endDate->copy()->endOfMonth();

            $monthRange = new \DatePeriod(
                $startMonth,
                new \DateInterval('P1M'),
                $endMonth->modify('+1 month')
            );

            $occupancyData = [];

            foreach ($monthRange as $month) {
                $monthStart = $month->format('Y-m-01');
                $monthEnd = $month->format('Y-m-t');

                $reservationsInMonth = Reservation::where('status', '!=', 'cancelled')
                    ->where(function($q) use ($monthStart, $monthEnd) {
                        $q->whereBetween('check_in_date', [$monthStart, $monthEnd])
                          ->orWhereBetween('check_out_date', [$monthStart, $monthEnd])
                          ->orWhere(function($q2) use ($monthStart, $monthEnd) {
                              $q2->where('check_in_date', '<', $monthStart)
                                 ->where('check_out_date', '>', $monthEnd);
                          });
                    })
                    ->get();

                $daysInMonth = Carbon::parse($monthEnd)->day;
                $totalRoomDays = $totalRooms * $daysInMonth;
                $occupiedRoomDays = 0;

                foreach ($reservationsInMonth as $reservation) {
                    $checkIn = max(Carbon::parse($reservation->check_in_date), Carbon::parse($monthStart));
                    $checkOut = min(Carbon::parse($reservation->check_out_date), Carbon::parse($monthEnd)->addDay());
                    $days = $checkIn->diffInDays($checkOut);
                    $occupiedRoomDays += $days;
                }

                $occupancyRate = ($occupiedRoomDays / $totalRoomDays) * 100;

                $occupancyData[] = [
                    'year' => $month->format('Y'),
                    'month' => $month->format('m'),
                    'occupancy_rate' => round($occupancyRate, 2)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $occupancyData
            ]);
        } else { // year
            $startYear = $startDate->copy()->startOfYear();
            $endYear = $endDate->copy()->endOfYear();

            $yearRange = new \DatePeriod(
                $startYear,
                new \DateInterval('P1Y'),
                $endYear->modify('+1 year')
            );

            $occupancyData = [];

            foreach ($yearRange as $year) {
                $yearStart = $year->format('Y-01-01');
                $yearEnd = $year->format('Y-12-31');

                $reservationsInYear = Reservation::where('status', '!=', 'cancelled')
                    ->where(function($q) use ($yearStart, $yearEnd) {
                        $q->whereBetween('check_in_date', [$yearStart, $yearEnd])
                          ->orWhereBetween('check_out_date', [$yearStart, $yearEnd])
                          ->orWhere(function($q2) use ($yearStart, $yearEnd) {
                              $q2->where('check_in_date', '<', $yearStart)
                                 ->where('check_out_date', '>', $yearEnd);
                          });
                    })
                    ->get();

                $daysInYear = Carbon::parse($yearEnd)->diff(Carbon::parse($yearStart))->days + 1;
                $totalRoomDays = $totalRooms * $daysInYear;
                $occupiedRoomDays = 0;

                foreach ($reservationsInYear as $reservation) {
                    $checkIn = max(Carbon::parse($reservation->check_in_date), Carbon::parse($yearStart));
                    $checkOut = min(Carbon::parse($reservation->check_out_date), Carbon::parse($yearEnd)->addDay());
                    $days = $checkIn->diffInDays($checkOut);
                    $occupiedRoomDays += $days;
                }

                $occupancyRate = ($occupiedRoomDays / $totalRoomDays) * 100;

                $occupancyData[] = [
                    'year' => $year->format('Y'),
                    'occupancy_rate' => round($occupancyRate, 2)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $occupancyData
            ]);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/v1/admin/statistics/room-type-popularity",
     *     summary="Récupérer la popularité des types de chambres",
     *     description="Récupère la popularité des types de chambres sur une période donnée",
     *     operationId="getRoomTypePopularity",
     *     tags={"Statistiques (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Date de début pour le filtrage",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Date de fin pour le filtrage",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Popularité des types de chambres récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="type", type="string", example="standard"),
     *                     @OA\Property(property="reservation_count", type="integer", example=120)
     *                 )
     *             )
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
     */
    public function roomTypePopularity(Request $request): JsonResponse
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->subYear();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now();

        $roomTypeStats = DB::table('reservations')
            ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
            ->where('reservations.status', '!=', 'cancelled')
            ->whereBetween('reservations.created_at', [$startDate, $endDate])
            ->select('rooms.type', DB::raw('COUNT(*) as reservation_count'))
            ->groupBy('rooms.type')
            ->orderBy('reservation_count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roomTypeStats
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/statistics/save",
     *     summary="Sauvegarder les statistiques actuelles",
     *     description="Sauvegarde les statistiques actuelles pour l'historique",
     *     operationId="saveCurrentStatistics",
     *     tags={"Statistiques (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques enregistrées avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistiques enregistrées avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Statistic"
     *             )
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
     */
    public function saveCurrentStats(): JsonResponse
    {
        $today = Carbon::now();

        // Statistiques générales
        $totalRooms = Room::count();

        // Calculer le taux d'occupation
        $roomsBooked = Reservation::where('status', '!=', 'cancelled')
            ->whereDate('check_in_date', '<=', $today)
            ->whereDate('check_out_date', '>', $today)
            ->count();

        $occupancyRate = $totalRooms > 0 ? ($roomsBooked / $totalRooms) * 100 : 0;

        // Calculer les revenus du jour
        $dailyRevenue = Payment::where('status', 'completed')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // Calculer les revenus du mois
        $monthlyRevenue = Payment::where('status', 'completed')
            ->whereYear('payment_date', $today->year)
            ->whereMonth('payment_date', $today->month)
            ->sum('amount');

        // Sauvegarder les statistiques
        $stat = Statistic::create([
            'date' => $today->toDateString(),
            'occupancy_rate' => round($occupancyRate, 2),
            'daily_revenue' => $dailyRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'rooms_available' => $totalRooms - $roomsBooked,
            'rooms_booked' => $roomsBooked,
            'total_rooms' => $totalRooms,
            'reservations_created' => Reservation::whereDate('created_at', $today)->count(),
            'users_registered' => User::whereDate('created_at', $today)->count()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statistiques enregistrées avec succès',
            'data' => $stat
        ]);
    }

   /**
     * @OA\Get(
     *     path="/api/v1/admin/statistics/history",
     *     summary="Récupérer l'historique des statistiques",
     *     description="Récupère l'historique des statistiques sur une période donnée",
     *     operationId="getStatisticsHistory",
     *     tags={"Statistiques (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Date de début pour le filtrage",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Date de fin pour le filtrage",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique des statistiques récupéré avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Statistic")
     *             )
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
     */
    public function getStatsHistory(Request $request): JsonResponse
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->subMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now();

        $stats = Statistic::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
