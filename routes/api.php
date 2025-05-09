<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes publiques
Route::prefix('v1')->group(function () {
    // Routes d'authentification
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        // Routes de réinitialisation de mot de passe
        Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
        Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

        // Route protégée par auth:api
        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Autres routes publiques
    Route::prefix('public')->group(function () {
        // Chambres publiques
        Route::get('/rooms', [RoomController::class, 'publicIndex']);
        Route::get('/rooms/{id}', [RoomController::class, 'publicShow']);

        // Témoignages publics
        Route::get('/testimonials', [TestimonialController::class, 'publicIndex']);

        // Articles de blog publics
        Route::get('/blog', [BlogController::class, 'publicIndex']);
        Route::get('/blog/{slug}', [BlogController::class, 'publicShow']);
    });
});

// Routes protégées
Route::prefix('v1')->middleware('auth:api')->group(function () {
    // Routes pour les utilisateurs authentifiés

    // Gestion des réservations (côté client)
    Route::prefix('reservations')->group(function () {
        Route::get('/', [ReservationController::class, 'clientIndex']);
        Route::post('/', [ReservationController::class, 'clientStore']);
        Route::get('/{id}', [ReservationController::class, 'clientShow']);
        Route::post('/{id}/cancel', [ReservationController::class, 'clientCancel']);
    });

    // Gestion des témoignages (côté client)
    Route::prefix('testimonials')->group(function () {
        Route::get('/mine', [TestimonialController::class, 'clientIndex']);
        Route::post('/', [TestimonialController::class, 'clientStore']);
        Route::put('/{id}', [TestimonialController::class, 'clientUpdate']);
        Route::delete('/{id}', [TestimonialController::class, 'clientDestroy']);
    });

    // Messagerie (côté client)
    Route::prefix('messages')->group(function () {
        Route::get('/inbox', [MessageController::class, 'inbox']);
        Route::get('/sent', [MessageController::class, 'sent']);
        Route::post('/', [MessageController::class, 'send']);
        Route::get('/{id}', [MessageController::class, 'show']);
        Route::post('/{id}/read', [MessageController::class, 'markAsRead']);
        Route::post('/read-multiple', [MessageController::class, 'markMultipleAsRead']);
        Route::delete('/{id}', [MessageController::class, 'destroy']);
        Route::get('/users-autocomplete', [MessageController::class, 'getUsersForAutocomplete']);
    });

    // Routes réservées aux administrateurs et gestionnaires
    Route::middleware('role:ADMIN,MANAGER')->prefix('admin')->group(function () {
        // Gestion des chambres
        Route::prefix('rooms')->group(function () {
            Route::get('/', [RoomController::class, 'index']);
            Route::post('/', [RoomController::class, 'store']);
            Route::get('/{id}', [RoomController::class, 'show']);
            Route::put('/{id}', [RoomController::class, 'update']);
            Route::delete('/{id}', [RoomController::class, 'destroy']);
            Route::post('/{id}/images', [RoomController::class, 'addImages']);
            Route::delete('/{roomId}/images/{imageId}', [RoomController::class, 'deleteImage']);
        });

        // Gestion des réservations
        Route::prefix('reservations')->group(function () {
            Route::get('/', [ReservationController::class, 'index']);
            Route::post('/', [ReservationController::class, 'store']);
            Route::get('/{id}', [ReservationController::class, 'show']);
            Route::put('/{id}', [ReservationController::class, 'update']);
            Route::delete('/{id}', [ReservationController::class, 'destroy']);
            Route::post('/{id}/status', [ReservationController::class, 'changeStatus']);
        });

        // Gestion des paiements
        Route::prefix('payments')->group(function () {
            Route::get('/', [PaymentController::class, 'index']);
            Route::post('/', [PaymentController::class, 'store']);
            Route::get('/{id}', [PaymentController::class, 'show']);
            Route::put('/{id}', [PaymentController::class, 'update']);
            Route::delete('/{id}', [PaymentController::class, 'destroy']);
        });

        // Gestion des témoignages
        Route::prefix('testimonials')->group(function () {
            Route::get('/', [TestimonialController::class, 'index']);
            Route::get('/{id}', [TestimonialController::class, 'show']);
            Route::put('/{id}', [TestimonialController::class, 'update']);
            Route::post('/{id}/status', [TestimonialController::class, 'changeStatus']);
            Route::post('/{id}/highlight', [TestimonialController::class, 'highlight']);
            Route::post('/{id}/unhighlight', [TestimonialController::class, 'unhighlight']);
            Route::delete('/{id}', [TestimonialController::class, 'destroy']);
        });

        // Gestion du blog
        Route::prefix('blog')->group(function () {
            Route::get('/', [BlogController::class, 'index']);
            Route::post('/', [BlogController::class, 'store']);
            Route::get('/{id}', [BlogController::class, 'show']);
            Route::put('/{id}', [BlogController::class, 'update']);
            Route::delete('/{id}', [BlogController::class, 'destroy']);
            Route::post('/{id}/status', [BlogController::class, 'changeStatus']);
        });

        // Statistiques
        Route::prefix('statistics')->group(function () {
            Route::get('/dashboard', [StatisticsController::class, 'dashboard']);
            Route::get('/revenue', [StatisticsController::class, 'revenue']);
            Route::get('/occupancy', [StatisticsController::class, 'occupancy']);
            Route::get('/room-types', [StatisticsController::class, 'roomTypePopularity']);
            Route::post('/save-current', [StatisticsController::class, 'saveCurrentStats']);
            Route::get('/history', [StatisticsController::class, 'getStatsHistory']);
        });
    });

    // Gestion du profil utilisateur
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'getProfile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/password', [UserController::class, 'updatePassword']);
    });
});

// Route fallback pour les routes API non trouvées
Route::fallback(function() {
    return response()->json([
        'message' => 'Endpoint introuvable. Si vous pensez que c\'est une erreur, veuillez contacter l\'administrateur.',
        'success' => false
    ], 404);
});
