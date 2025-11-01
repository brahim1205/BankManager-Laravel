# TODO: Préparer le projet BankManager pour le déploiement sur Render

## Analyse du projet
- **Framework**: Laravel 10 avec PHP 8.3
- **Base de données**: PostgreSQL (déjà configurée dans render.yaml)
- **Déploiement**: Docker avec Nginx + PHP-FPM + Supervisord
- **API**: RESTful avec authentification Passport
- **Documentation**: Swagger (l5-swagger)

## État actuel
- ✅ Dockerfile optimisé pour production
- ✅ render.yaml configuré avec health check (/api/v1/status)
- ✅ Script de démarrage docker/render-start.sh avec migrations et seeders
- ✅ Nginx configuré sur port 8000 (cohérent avec EXPOSE)
- ✅ Health check endpoint fonctionnel

## Tâches à effectuer

### 1. Variables d'environnement
- [ ] Créer/mettre à jour .env.production avec les bonnes valeurs pour Render
- [ ] S'assurer que DB_CONNECTION=pgsql
- [ ] Configurer APP_URL dynamique via Render
- [ ] Définir MAIL_MAILER=log pour production

### 2. Optimisations production
- [ ] Vérifier que les commandes d'optimisation sont dans le script de démarrage
- [ ] S'assurer que les dépendances dev sont exclues en production

### 3. Base de données
- [ ] Les migrations sont présentes (16 fichiers)
- [ ] Seeders configurés pour créer des données de test
- [ ] Configuration PostgreSQL correcte

### 4. Sécurité
- [ ] Vérifier que .env n'est pas commité
- [ ] S'assurer que les clés sensibles sont dans les variables Render

### 5. Tests et validation
- [ ] Tester le health check endpoint
- [ ] Vérifier que les migrations s'exécutent correctement

## Commandes de déploiement
```bash
# Build et push vers Render
render deploy

# Ou via CLI si installé
gh repo create bankmanager-api --public --source=. --remote=origin --push
```

## Variables d'environnement Render requises
- APP_NAME=BankManager
- APP_ENV=production
- APP_DEBUG=false
- DB_CONNECTION=pgsql
- DB_HOST= (fourni par Render DB)
- DB_PORT=5432
- DB_DATABASE=bankmanager
- DB_USERNAME=bankmanager_user
- DB_PASSWORD= (fourni par Render DB)
- APP_KEY= (généré automatiquement)
- MAIL_MAILER=log
- QUEUE_CONNECTION=database
- L5_SWAGGER_USE_ABSOLUTE_PATH=true
- APP_URL= (fourni par Render)
- L5_SWAGGER_BASE_PATH= (fourni par Render)
