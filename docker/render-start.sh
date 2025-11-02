#!/bin/bash

# Script de démarrage optimisé pour Render avec gestion d'erreurs et logs détaillés
set -euo pipefail

# Fonction de logging
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# Fonction d'erreur
error_exit() {
    log "❌ ERREUR: $1"
    exit 1
}

log "🚀 Démarrage de BankManager API sur Render..."

# Préparation des permissions nécessaires
log "🔒 Préparation des permissions (storage, cache)..."
mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/api-docs bootstrap/cache || true
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Générer la clé d'application si elle n'existe pas
if [ -z "${APP_KEY:-}" ]; then
    log "🔑 Génération de la clé d'application..."
    if ! php artisan key:generate --force; then
        error_exit "Échec de la génération de la clé d'application"
    fi
else
    log "🔑 Clé d'application déjà définie"
fi

# Nettoyage préalable des caches (avant optimisation)
log "🧹 Nettoyage initial des caches..."
php artisan cache:clear || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Attendre que la base de données soit prête avec timeout et retries
log "⏳ Attente de la base de données..."
DB_TIMEOUT=300  # 5 minutes
DB_RETRY_INTERVAL=5
elapsed=0

while [ $elapsed -lt $DB_TIMEOUT ]; do
    if php artisan migrate:status > /dev/null 2>&1; then
        log "✅ Base de données prête"
        break
    fi
    log "Base de données non prête, nouvelle tentative dans $DB_RETRY_INTERVAL secondes... ($elapsed/$DB_TIMEOUT s)"
    sleep $DB_RETRY_INTERVAL
    elapsed=$((elapsed + DB_RETRY_INTERVAL))
done

if [ $elapsed -ge $DB_TIMEOUT ]; then
    error_exit "Timeout d'attente de la base de données dépassé"
fi

# Exécution des migrations
log "📦 Exécution des migrations..."
if ! php artisan migrate --force; then
    error_exit "Échec des migrations"
fi

# Exécution des seeders
log "🌱 Exécution des seeders..."
if ! php artisan db:seed --force; then
    error_exit "Échec des seeders"
fi

# Installation de Laravel Passport avec vérification
log "🔐 Installation de Laravel Passport..."
if ! php artisan passport:install --force; then
    error_exit "Échec de l'installation de Passport"
fi

# Génération des clés Passport si nécessaire
log "🔐 Vérification/rafraîchissement des clés Passport..."
php artisan passport:keys --force || log "⚠️ Avertissement: Échec du rafraîchissement des clés Passport"

# Lien de stockage public
log "🔗 Création du lien de stockage (storage:link)..."
php artisan storage:link || log "ℹ️ storage:link déjà en place"

# Génération de la documentation Swagger
log "📚 Génération de la documentation Swagger..."
mkdir -p storage/api-docs || true
if ! php artisan l5-swagger:generate; then
    log "⚠️ Avertissement: Échec de la génération Swagger, tentative forcée..."
    php artisan l5-swagger:generate --force || log "❌ Échec forcé de la génération Swagger"
fi

# Vérification de la génération Swagger
if [ -f "storage/api-docs/api-docs.json" ]; then
    log "✅ Documentation Swagger générée avec succès"
else
    error_exit "Fichier api-docs.json non trouvé après génération"
fi

# Optimisation pour la production (ne plus vider après)
log "🔧 Optimisation pour la production..."
if ! php artisan config:cache; then
    error_exit "Échec du cache de configuration"
fi
if ! php artisan route:cache; then
    error_exit "Échec du cache des routes"
fi
if ! php artisan view:cache; then
    error_exit "Échec du cache des vues"
fi

# Contrôle de santé avant démarrage
log "🏥 Contrôle de santé de l'application..."
if ! php artisan --version > /dev/null 2>&1; then
    error_exit "Application Laravel non fonctionnelle"
fi

# Test de connexion à la DB
if ! php artisan migrate:status > /dev/null 2>&1; then
    error_exit "Connexion à la base de données perdue"
fi

log "✅ Application prête ! Démarrage de Supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
