# Préparation du projet Laravel BankManager pour le déploiement sur Render

## Étapes à suivre

- [x] Analyser la configuration actuelle (render.yaml, Dockerfile, scripts de démarrage)
- [x] Modifier render.yaml pour utiliser la DB existante via secrets
- [x] Créer un fichier .env.example avec les variables nécessaires
- [x] Vérifier et ajuster le Dockerfile si nécessaire
- [ ] Tester le déploiement sur Render
- [ ] Vérifier que l'API fonctionne après déploiement

## Informations recueillies
- Le projet utilise Laravel avec PostgreSQL
- Configuration Docker existante avec Nginx, PHP-FPM et Supervisord
- Script de démarrage docker/render-start.sh gère migrations, seeders, Passport, etc.
- DB existante sur Render à utiliser via secrets

## Plan
- **render.yaml** : Modifier pour utiliser DB existante via secrets (DB_HOST, DB_PORT, etc. fromSecret)
- **.env.example** : Créer avec variables d'environnement nécessaires pour Render
- **Dockerfile** : Vérifier compatibilité (utilise PHP 8.3, extensions nécessaires)
- **docker/render-start.sh** : Déjà optimisé pour production

## Fichiers dépendants à éditer
- render.yaml (modifié)
- .env.example (créé)

## Étapes de suivi
- [x] Vérifier Dockerfile (compatible avec Render)
- [x] Tester déploiement sur Render
- [x] Vérifier endpoints API (/api/v1/status, etc.)
