# Déploiement en production

Guide pas à pas pour un hébergement mutualisé avec accès SSH (exemple : **Hostinger, offre Business**).
Les commandes sont les mêmes sur un autre hébergeur avec SSH, seuls les menus du panneau changent.

## Prérequis côté hébergeur

- PHP **8.2** (extensions : `pdo_mysql`, `mbstring`, `zip`, `gd`, `xml`, `fileinfo`)
- MySQL / MariaDB
- Accès **SSH**, **Composer 2**, **tâches cron**
- Certificat **SSL** (gratuit chez la plupart des hébergeurs)

Node.js n'est **pas** nécessaire : les fichiers compilés (`public/build`) sont dans le dépôt.

---

## 1. Préparer l'hébergement (panneau hPanel)

1. **Domaine** : ajoutez votre domaine (ou utilisez le sous-domaine temporaire fourni).
2. **PHP** : *Avancé → Configuration PHP* → choisissez **8.2**.
3. **Base de données** : *Bases de données → MySQL* → créez une base et un utilisateur. Notez le nom de la base, l'utilisateur et le mot de passe.
4. **SSH** : *Avancé → Accès SSH* → activez-le et notez la commande de connexion (`ssh -p 65002 u123456789@...`).
5. **SSL** : *Sécurité → SSL* → installez le certificat gratuit pour le domaine.

## 2. Installer l'application (première fois)

Connectez-vous en SSH, puis :

```bash
cd ~/domains/<votre-domaine>

# Récupérer le code (dossier « app » à côté de public_html)
git clone https://github.com/<compte>/<depot>.git app
cd app

# Dépendances PHP
composer install --no-dev --optimize-autoloader

# Configuration
cp .env.production.example .env
nano .env            # remplir les valeurs <...> (base de données, domaine, e-mail)
php artisan key:generate

# Base de données (structure seulement, PAS de données de démo)
php artisan migrate --force

# Premier compte administrateur (les questions s'affichent)
php artisan app:creer-admin

# Fichiers publics (photos des stagiaires, logo) et caches
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

> Si le dépôt GitHub est **privé**, `git clone` demandera un identifiant : utilisez un *Personal access token* GitHub (Settings → Developer settings → Tokens) à la place du mot de passe.

## 3. Faire pointer le domaine vers le dossier `public`

Seul le dossier `public/` doit être accessible depuis le web (le reste contient le code et le fichier `.env`).

```bash
cd ~/domains/<votre-domaine>
mv public_html public_html.ancien      # garder l'ancien contenu au cas où
ln -s app/public public_html
```

Ouvrez `https://<votre-domaine>` : la page de connexion doit s'afficher.

## 4. Tâches automatiques (cron)

*Avancé → Tâches Cron* → ajoutez une tâche **toutes les minutes** :

```
cd /home/<utilisateur>/domains/<votre-domaine>/app && php artisan schedule:run >> /dev/null 2>&1
```

Elle déclenche chaque jour :

| Heure | Tâche |
|---|---|
| 01h00 | Sauvegarde complète (base + fichiers) |
| 08h00 | Passage des échéances impayées « en retard » |
| 09h00 | Rappels avant échéance |
| 18h00 | Signalement des absences non justifiées |
| Dimanche 02h00 | Nettoyage des notifications de plus de 30 jours |

## 5. Vérifications après installation

- [ ] Connexion avec le compte administrateur
- [ ] *Paramètres → Établissement* : nom, logo et coordonnées (ils apparaissent sur les bulletins, reçus et attestations)
- [ ] Création d'une année scolaire, des filières, niveaux, classes, matières et salles
- [ ] *Sauvegardes* : créer une sauvegarde de la base et vérifier que le fichier n'est pas vide
- [ ] *Mot de passe oublié* : vérifier que l'e-mail arrive (sinon revoir `MAIL_*` dans `.env`)
- [ ] Une fois les e-mails fonctionnels, mettre `PARENTS_NOTIFICATIONS_MAIL=true` puis `php artisan config:cache`

## 6. Mettre à jour l'application

Sur votre ordinateur : commitez et poussez sur GitHub (si vous avez modifié `resources/css` ou `resources/js`, lancez d'abord `npm run build`).

Sur le serveur :

```bash
cd ~/domains/<votre-domaine>/app
bash deploy.sh
```

Le script met le site en maintenance, récupère le code, applique les migrations et reconstruit les caches.

## Sécurité : à ne jamais faire en production

- `APP_DEBUG=true` (affiche le code et les mots de passe en cas d'erreur)
- `php artisan db:seed` (refusé en production : il créerait des comptes de démo au mot de passe « password »)
- `php artisan migrate:fresh` (efface **toutes** les données)
- Commiter le fichier `.env`

## En cas de problème

- **Erreur 500** : lire `storage/logs/laravel-<date>.log`
- **Page blanche ou « Permission denied »** : `chmod -R 775 storage bootstrap/cache`
- **Modification de `.env` sans effet** : `php artisan config:cache`
- **Photos ou logo introuvables** : `php artisan storage:link`
