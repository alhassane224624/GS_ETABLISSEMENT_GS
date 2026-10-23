#!/usr/bin/env bash
# Mise à jour de l'application sur le serveur, après un git push.
# Usage (sur le serveur, dans le dossier du projet) : bash deploy.sh
set -euo pipefail

cd "$(dirname "$0")"

echo "→ Mode maintenance"
php artisan down --retry=30 || true
# En cas d'erreur, l'application est remise en ligne quand même
trap 'php artisan up' EXIT

echo "→ Récupération du code"
git pull --ff-only

echo "→ Dépendances PHP (sans les outils de développement)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Base de données"
php artisan migrate --force

echo "→ Lien vers les fichiers publics (photos, logo)"
php artisan storage:link 2>/dev/null || true

echo "→ Caches de production"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✓ Déploiement terminé"
