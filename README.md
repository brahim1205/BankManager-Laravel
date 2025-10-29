# BankManager API

API REST Laravel pour la gestion bancaire avec comptes et transactions.

## 🚀 Déploiement sur Render

### Prérequis
- Compte Render (https://render.com)
- GitHub repository

### Étapes de déploiement

1. **Connecter votre repository GitHub à Render**
   - Allez sur https://dashboard.render.com
   - Cliquez sur "New +" > "Web Service"
   - Connectez votre repository GitHub

2. **Configuration du déploiement**
   - **Runtime**: PHP
   - **Build Command**:
     ```bash
     composer install --no-dev --optimize-autoloader
     php artisan key:generate
     php artisan config:cache
     php artisan route:cache
     php artisan view:cache
     ```
   - **Start Command**:
     ```bash
     php artisan migrate --force
     php artisan db:seed --force
     php artisan l5-swagger:generate
     php artisan serve --host 0.0.0.0 --port $PORT
     ```

3. **Variables d'environnement**
   ```
   APP_NAME=BankManager
   APP_ENV=production
   APP_DEBUG=false
   DB_CONNECTION=pgsql
   L5_SWAGGER_USE_ABSOLUTE_PATH=true
   MAIL_MAILER=log
   QUEUE_CONNECTION=sync
   ```

4. **Base de données**
   - Ajoutez une base PostgreSQL depuis Render
   - Les variables DB_* seront automatiquement configurées

## 📚 Documentation API

Une fois déployé, accédez à la documentation Swagger :
```
https://votre-app.render.com/api/documentation
```

## 🔧 Développement local

```bash
# Installation
composer install
cp .env.example .env
php artisan key:generate

# Base de données
php artisan migrate
php artisan db:seed

# Démarrage
php artisan serve
```

## 📋 Endpoints principaux

### Authentification
- `POST /api/v1/login` - Connexion
- `POST /api/v1/logout` - Déconnexion
- `POST /api/v1/refresh` - Rafraîchir token

### Clients
- `GET /api/v1/clients` - Lister les clients
- `POST /api/v1/clients` - Créer un client
- `GET /api/v1/clients/{id}` - Détails client
- `PUT /api/v1/clients/{id}` - Modifier client
- `DELETE /api/v1/clients/{id}` - Supprimer client

### Comptes
- `GET /api/v1/comptes` - Lister les comptes
- `POST /api/v1/comptes` - Créer un compte
- `GET /api/v1/comptes/{id}` - Détails compte
- `PATCH /api/v1/comptes/{id}` - Modifier compte
- `DELETE /api/v1/comptes/{id}` - Supprimer compte
- `POST /api/v1/comptes/{id}/bloquer` - Bloquer compte

### Transactions
- `GET /api/v1/transactions` - Lister les transactions
- `POST /api/v1/transactions` - Créer une transaction
- `GET /api/v1/transactions/{id}` - Détails transaction

## 🏗️ Architecture

- **Laravel 10** avec PHP 8.3
- **PostgreSQL** pour la base de données
- **Laravel Passport** pour l'authentification OAuth2
- **Swagger/OpenAPI** pour la documentation
- **Architecture en couches** avec Services métier

## 📦 Technologies

- Laravel Framework
- PostgreSQL
- Laravel Passport
- L5 Swagger
- Docker (développement)

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT.
