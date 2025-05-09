<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="API de Gestion Hôtelière",
 *      description="Documentation de l'API du système de gestion hôtelière",
 *      @OA\Contact(
 *          email="contact@hotel.com",
 *          name="Support API"
 *      ),
 *      @OA\License(
 *          name="Licence propriétaire",
 *          url="https://www.hotel.com/licence"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Environnement de l'API"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
