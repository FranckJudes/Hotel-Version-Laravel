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
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

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

        Route::get('/rooms', [RoomController::class, 'publicIndex']);
        Route::get('/rooms/{id}', [RoomController::class, 'publicShow']);

        Route::get('/testimonials/approuved', [TestimonialController::class, 'publicIndex']);

        Route::get('/blog', [BlogController::class, 'publicIndex']);
        Route::get('/blog/{slug}', [BlogController::class, 'publicShow']);
    });

// Routes protégées
Route::prefix('v1')->middleware('auth:api')->group(function () {

    Route::prefix('reservations')->group(function () {
        Route::get('/', [ReservationController::class, 'clientIndex']);
        Route::post('/', [ReservationController::class, 'clientStore']);
        Route::get('/{id}', [ReservationController::class, 'clientShow']);
        Route::post('/{id}/cancel', [ReservationController::class, 'clientCancel']);
    });

    Route::prefix('testimonials')->group(function () {
        Route::get('/mine', [TestimonialController::class, 'clientIndex']);
        Route::post('/', [TestimonialController::class, 'clientStore']);
        Route::put('/{id}', [TestimonialController::class, 'clientUpdate']);
        Route::delete('/{id}', [TestimonialController::class, 'clientDestroy']);
    });

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
    Route::prefix('admin')->group(function () {
        // Déclarer un middleware pour chaque route individuellement
        // Routes de gestion des chambres
        Route::prefix('rooms')->group(function () {
            Route::get('/', [RoomController::class, 'index'])->middleware('checkAdmin');
            Route::post('/', [RoomController::class, 'store'])->middleware('checkAdmin');
            Route::get('/{id}', [RoomController::class, 'show'])->middleware('checkAdmin');
            Route::put('/{id}', [RoomController::class, 'update'])->middleware('checkAdmin');
            Route::delete('/{id}', [RoomController::class, 'destroy'])->middleware('checkAdmin');
            Route::post('/{id}/images', [RoomController::class, 'addImages'])->middleware('checkAdmin');
            Route::delete('/{roomId}/images/{imageId}', [RoomController::class, 'deleteImage'])->middleware('checkAdmin');
        });

        // Routes de gestion des réservations
        Route::prefix('reservations')->group(function () {
            Route::get('/', [ReservationController::class, 'index'])->middleware('checkAdmin');
            Route::post('/', [ReservationController::class, 'store'])->middleware('checkAdmin');
            Route::get('/{id}', [ReservationController::class, 'show'])->middleware('checkAdmin');
            Route::put('/{id}', [ReservationController::class, 'update'])->middleware('checkAdmin');
            Route::delete('/{id}', [ReservationController::class, 'destroy'])->middleware('checkAdmin');
            Route::post('/{id}/status', [ReservationController::class, 'changeStatus'])->middleware('checkAdmin');
        });

        // Routes de gestion des paiements
        Route::prefix('payments')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->middleware('checkAdmin');
            Route::post('/', [PaymentController::class, 'store'])->middleware('checkAdmin');
            Route::get('/{id}', [PaymentController::class, 'show'])->middleware('checkAdmin');
            Route::put('/{id}', [PaymentController::class, 'update'])->middleware('checkAdmin');
            Route::delete('/{id}', [PaymentController::class, 'destroy'])->middleware('checkAdmin');
        });

        // Routes de gestion des témoignages
        Route::prefix('testimonials')->group(function () {
            Route::get('/', [TestimonialController::class, 'index'])->middleware('checkAdmin');
            Route::get('/{id}', [TestimonialController::class, 'show'])->middleware('checkAdmin');
            Route::put('/{id}', [TestimonialController::class, 'update'])->middleware('checkAdmin');
            Route::post('/{id}/status', [TestimonialController::class, 'changeStatus'])->middleware('checkAdmin');
            Route::post('/{id}/highlight', [TestimonialController::class, 'highlight'])->middleware('checkAdmin');
            Route::post('/{id}/unhighlight', [TestimonialController::class, 'unhighlight'])->middleware('checkAdmin');
            Route::delete('/{id}', [TestimonialController::class, 'destroy'])->middleware('checkAdmin');
        });

        // Routes de gestion du blog
        Route::prefix('blog')->group(function () {
            Route::get('/', [BlogController::class, 'index'])->middleware('checkAdmin');
            Route::post('/', [BlogController::class, 'store'])->middleware('checkAdmin');
            Route::get('/{id}', [BlogController::class, 'show'])->middleware('checkAdmin');
            Route::put('/{id}', [BlogController::class, 'update'])->middleware('checkAdmin');
            Route::delete('/{id}', [BlogController::class, 'destroy'])->middleware('checkAdmin');
            Route::post('/{id}/status', [BlogController::class, 'changeStatus'])->middleware('checkAdmin');
        });

        // Routes des statistiques
        Route::prefix('statistics')->group(function () {
            Route::get('/dashboard', [StatisticsController::class, 'dashboard'])->middleware('checkAdmin');
            Route::get('/revenue', [StatisticsController::class, 'revenue'])->middleware('checkAdmin');
            Route::get('/occupancy', [StatisticsController::class, 'occupancy'])->middleware('checkAdmin');
            Route::get('/room-types', [StatisticsController::class, 'roomTypePopularity'])->middleware('checkAdmin');
            Route::post('/save-current', [StatisticsController::class, 'saveCurrentStats'])->middleware('checkAdmin');
            Route::get('/history', [StatisticsController::class, 'getStatsHistory'])->middleware('checkAdmin');
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