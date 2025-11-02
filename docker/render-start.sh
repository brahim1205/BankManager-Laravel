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

# Générer la clé d'application si elle n'existe pas
if [ -z "${APP_KEY:-}" ]; then
    log "🔑 Génération de la clé d'application..."
    if ! php artisan key:generate --force; then
        error_exit "Échec de la génération de la clé d'application"
    fi
else
    log "🔑 Clé d'application déjà définie"
fi

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

# Vérification des clés Passport
log "🔍 Vérification des clés Passport..."
PASSPORT_CHECK=$(php artisan tinker --execute="echo 'OAuth clients: ' . \Laravel\Passport\Client::count() . ', OAuth access tokens: ' . \Laravel\Passport\Token::count();" 2>/dev/null || echo "Erreur vérification Passport")
log "$PASSPORT_CHECK"

# Génération de la documentation Swagger
log "📚 Génération de la documentation Swagger..."
if ! php artisan l5-swagger:generate; then
    log "⚠️ Avertissement: Échec de la génération Swagger, continuation..."
fi

# Optimisation pour la production
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

# Nettoyage étendu du cache
log "🧹 Nettoyage étendu du cache..."
php artisan cache:clear || log "⚠️ Avertissement: Échec du nettoyage du cache"
php artisan config:clear || log "⚠️ Avertissement: Échec du nettoyage de la config"
php artisan route:clear || log "⚠️ Avertissement: Échec du nettoyage des routes"
php artisan view:clear || log "⚠️ Avertissement: Échec du nettoyage des vues"
php artisan passport:keys --force || log "⚠️ Avertissement: Échec du rafraîchissement des clés Passport"

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
