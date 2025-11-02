#!/bin/bash

# Script de démarrage optimisé pour Render
set -e

echo "🚀 Démarrage de BankManager API sur Render..."

# Générer la clé d'application si elle n'existe pas
if [ -z "$APP_KEY" ]; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate --force
fi

# Attendre que la base de données soit prête
echo "⏳ Attente de la base de données..."
until php artisan migrate:status > /dev/null 2>&1; do
    echo "Base de données non prête, nouvelle tentative dans 5 secondes..."
    sleep 5
done

echo "📦 Exécution des migrations..."
php artisan migrate --force

echo "🌱 Exécution des seeders..."
php artisan db:seed --force

echo "🔐 Installation de Laravel Passport..."
php artisan passport:install --force

echo "📚 Génération de la documentation Swagger..."
php artisan l5-swagger:generate

echo "🔧 Optimisation pour la production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Application prête ! Démarrage de Supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
