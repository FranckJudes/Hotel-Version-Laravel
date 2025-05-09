# Guide de Documentation Swagger pour les Contrôleurs Admin

Voici un guide pour documenter correctement tous les contrôleurs administrateurs de l'application hôtelière avec Swagger.

## Structure de base des annotations

Pour chaque contrôleur, ajoutez d'abord l'annotation `@OA\Tag` au niveau de la classe pour définir sa catégorie :

```php
/**
 * @OA\Tag(
 *     name="Nom du contrôleur (Admin)",
 *     description="Description de l'API"
 * )
 */
class MonController extends Controller
{
```

## Documentation des méthodes

Pour chaque méthode, utilisez l'annotation appropriée selon le type d'opération HTTP :

### Pour les requêtes GET (récupération de données)

```php
/**
 * @OA\Get(
 *     path="/api/v1/admin/votre-route",
 *     summary="Titre court de l'action",
 *     description="Description détaillée de ce que fait la méthode",
 *     operationId="nomUniqueOperation",
 *     tags={"Nom du contrôleur (Admin)"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="param1",
 *         in="query",
 *         description="Description du paramètre",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Description du succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 // Propriétés spécifiques ici
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
 *         response=404,
 *         description="Ressource non trouvée"
 *     )
 * )
 */
```

### Pour les requêtes POST (création)

```php
/**
 * @OA\Post(
 *     path="/api/v1/admin/votre-route",
 *     summary="Créer une nouvelle ressource",
 *     description="Description détaillée",
 *     operationId="nomUniqueOperation",
 *     tags={"Nom du contrôleur (Admin)"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Données nécessaires",
 *         @OA\JsonContent(
 *             required={"champ1", "champ2"},
 *             @OA\Property(property="champ1", type="string", example="exemple"),
 *             // Autres propriétés
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Ressource créée avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Ressource créée avec succès"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 // Propriétés spécifiques
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Données invalides"
 *     )
 * )
 */
```

### Pour les requêtes PUT/PATCH (mise à jour)

```php
/**
 * @OA\Put(
 *     path="/api/v1/admin/votre-route/{id}",
 *     summary="Mettre à jour une ressource",
 *     description="Description détaillée",
 *     operationId="nomUniqueOperation",
 *     tags={"Nom du contrôleur (Admin)"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID de la ressource",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         description="Données à mettre à jour",
 *         @OA\JsonContent(
 *             // Propriétés
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ressource mise à jour avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Mise à jour réussie"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Ressource non trouvée"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Données invalides"
 *     )
 * )
 */
```

### Pour les requêtes DELETE (suppression)

```php
/**
 * @OA\Delete(
 *     path="/api/v1/admin/votre-route/{id}",
 *     summary="Supprimer une ressource",
 *     description="Description détaillée",
 *     operationId="nomUniqueOperation",
 *     tags={"Nom du contrôleur (Admin)"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID de la ressource à supprimer",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ressource supprimée avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Suppression réussie")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Ressource non trouvée"
 *     )
 * )
 */
```

## Schémas de modèles

Pour éviter de répéter les structures de données, définissez des schémas de modèles dans une classe séparée (par exemple `app/OpenApi/Schemas/RoomSchema.php`) :

```php
<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="Room",
 *     title="Room",
 *     description="Modèle de chambre d'hôtel"
 * )
 */
class RoomSchema
{
    /**
     * @OA\Property(property="id", type="integer", example=1)
     */
    public $id;

    /**
     * @OA\Property(property="number", type="string", example="101")
     */
    public $number;

    /**
     * @OA\Property(property="type", type="string", example="standard")
     */
    public $type;

    // Ajoutez toutes les propriétés du modèle
}
```

Ensuite, référencez ce schéma dans vos annotations:

```php
/**
 * @OA\Response(
 *     response=200,
 *     description="Succès",
 *     @OA\JsonContent(ref="#/components/schemas/Room")
 * )
 */
```

## Exemple complet pour le contrôleur RoomController

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Chambres (Admin)",
 *     description="API de gestion des chambres - Partie admin"
 * )
 */
class RoomController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/rooms",
     *     summary="Récupérer toutes les chambres",
     *     description="Récupère la liste de toutes les chambres disponibles dans l'hôtel",
     *     operationId="getRooms",
     *     tags={"Chambres (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des chambres récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Room")
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
    public function index(): JsonResponse
    {
        // Méthode existante
    }

    // Autres méthodes documentées...
}
```

## Personnalisation pour chaque contrôleur

Adaptez les annotations en fonction des spécificités de chaque contrôleur :

1. **RoomController** : Gestion des chambres avec images
2. **ReservationController** : Gestion des réservations avec statuts multiples
3. **PaymentController** : Suivi des paiements avec méthodes et statuts
4. **TestimonialController** : Gestion des témoignages avec modération
5. **BlogController** : Gestion des articles avec tags et médias
6. **StatisticsController** : Exposition des données statistiques
7. **MessageController** : Gestion de la messagerie interne

## Génération de la documentation

Après avoir documenté tous les contrôleurs, générez la documentation Swagger avec la commande :

```bash
php artisan l5-swagger:generate
```

Accédez à la documentation via l'URL `/api/documentation`. 
