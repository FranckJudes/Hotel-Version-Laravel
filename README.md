# Système de Gestion Hôtelière

Ce projet est une API RESTful pour un système de gestion hôtelière développé avec Laravel 11 et JWT pour l'authentification.

## Fonctionnalités

- Authentification avec JWT (connexion, inscription, déconnexion)
- Gestion des utilisateurs avec différents rôles (ADMIN, MANAGER, RECEPTIONIST, CLIENT)
- Gestion des chambres d'hôtel
- Gestion des réservations
- Gestion des paiements
- Système de blog
- Messagerie interne
- Gestion des témoignages clients
- Statistiques pour les administrateurs et gestionnaires

## Prérequis

- PHP 8.2+
- Composer
- MySQL
- Node.js et NPM (pour le frontend si vous souhaitez en développer un)

## Installation

1. Cloner le dépôt :
```bash
git clone <url-du-depot>
cd hotel-project
```

2. Installer les dépendances :
```bash
composer install
```

3. Copier le fichier .env.example et configurer votre base de données :
```bash
cp .env.example .env
```

4. Générer la clé d'application et la clé JWT :
```bash
php artisan key:generate
php artisan jwt:secret
```

5. Exécuter les migrations pour créer les tables :
```bash
php artisan migrate
```

6. Exécuter les seeders (optionnel) pour créer des données de test :
```bash
php artisan db:seed
```

7. Lancer le serveur de développement :
```bash
php artisan serve
```

## Documentation API

La documentation API est générée automatiquement grâce à L5-Swagger. Pour y accéder, visitez :
```
http://localhost:8000/api/documentation
```

Pour générer ou mettre à jour la documentation :
```bash
php artisan l5-swagger:generate
```

## Routes API

### Authentification
- POST /api/v1/auth/register - Inscription d'un nouvel utilisateur
- POST /api/v1/auth/login - Connexion d'un utilisateur
- POST /api/v1/auth/logout - Déconnexion (nécessite authentification)

### Chambres
- GET /api/v1/public/rooms - Récupérer toutes les chambres disponibles
- GET /api/v1/public/rooms/{id} - Récupérer les détails d'une chambre

### Réservations (nécessite authentification)
- GET /api/v1/reservations - Récupérer les réservations de l'utilisateur
- POST /api/v1/reservations - Créer une nouvelle réservation
- GET /api/v1/reservations/{id} - Récupérer les détails d'une réservation
- PUT /api/v1/reservations/{id} - Mettre à jour une réservation
- DELETE /api/v1/reservations/{id} - Annuler une réservation

### Autres routes
- Voir la documentation générée par Swagger pour plus de détails

## Tests

Pour exécuter les tests :
```bash
php artisan test
```

## Contribution

Les contributions sont les bienvenues. Veuillez suivre ces étapes :
1. Fork du dépôt
2. Créer une branche pour votre fonctionnalité (`git checkout -b feature/amazing-feature`)
3. Commit de vos changements (`git commit -m 'Add some amazing feature'`)
4. Push vers la branche (`git push origin feature/amazing-feature`)
5. Ouvrir une Pull Request

## Licence

Ce projet est sous licence MIT.
# Hotel-Version-Laravel
