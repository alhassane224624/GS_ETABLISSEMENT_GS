# 🎓 GS_ETABLISSEMENT - Système de Gestion Scolaire

Application web complète de gestion d'établissement scolaire / centre de formation, développée avec **Laravel**. La plateforme centralise la scolarité, la vie scolaire, l'évaluation, les documents officiels et les finances, autour d'un espace dédié à chaque acteur.

🔗 **Démo en ligne :** [gestionetablissement.42web.io](https://gestionetablissement.42web.io/)

---

## 📸 Aperçu

<!-- Ajoute tes captures d'écran ici, par exemple : -->
<!-- ![Dashboard Admin](docs/screenshots/dashboard-admin.png) -->
<!-- ![Espace Comptable](docs/screenshots/dashboard-comptable.png) -->
<!-- ![Espace Professeur](docs/screenshots/dashboard-professeur.png) -->
<!-- ![Espace Stagiaire](docs/screenshots/dashboard-stagiaire.png) -->
<!-- ![Espace Parents](docs/screenshots/espace-parents.png) -->

> Ajoute tes images dans un dossier `docs/screenshots/` à la racine du projet, puis référence-les ci-dessus avec `![Titre](docs/screenshots/nom-fichier.png)`.

---

## 👥 Acteurs & Rôles

L'application propose des espaces distincts selon le rôle de l'utilisateur connecté.

### 🛠️ Administrateur
Gère l'ensemble de l'établissement :
- Stagiaires : inscription, inscription en ligne à valider, import Excel, export PDF/Excel, statuts
- **Inscriptions annuelles** : parcours par année, délibération de fin d'année, **procès-verbal PDF**, passage vers l'année suivante
- Filières, niveaux, classes, matières (coefficients), salles, années scolaires et périodes
- **Emploi du temps hebdomadaire** et génération automatique des séances
- Notes, **bulletins** (génération, validation, réouverture) et **examens** (sessions normale et de rattrapage)
- Absences, rapports et **justificatifs** à valider
- **Documents officiels** : attestation de scolarité, certificat d'inscription, carte de stagiaire
- Utilisateurs (professeurs, comptables) et **accès parents**
- Paramètres de l'établissement (logo, coordonnées, mentions légales, signataire, seuils)
- Statistiques, messagerie, notifications et sauvegardes

### 💼 Comptable
Espace dédié à la gestion financière :
- Tableau de bord financier (encaissements, paiements en attente, échéances en retard)
- **Paiements** : espèces validées immédiatement, chèques et virements en attente jusqu'à l'encaissement
- **Reçus PDF** officiels (montant en lettres, situation financière)
- Échéanciers, **remises** (en % ou en DH), rappels et retards automatiques
- **Dépenses**, **salaires des professeurs** (calculés sur les séances réalisées), **bilan et caisse**
- Rapports financiers PDF et Excel

### 👨‍🏫 Professeur
Espace dédié à la gestion pédagogique :
- Planning du jour et de la semaine
- **Appel** depuis la séance (présent, absent, retard) et **cahier de textes**
- Saisie des notes de ses matières et des notes d'examen
- Export des notes et listes de stagiaires (PDF/Excel)
- Messagerie avec ses classes

### 🎓 Stagiaire / Élève
Espace personnel de suivi :
- Notes, relevé et **bulletins validés**
- Emploi du temps et **cahier de textes** (travail à faire)
- Absences et **dépôt de justificatifs**
- Paiements, échéances et **reçus**
- **Attestation de scolarité** en libre-service
- Profil

### 👨‍👩‍👧 Parent
Suivi en lecture seule de ses enfants (plusieurs enfants possibles) :
- Bulletins, absences, travail à faire
- Échéances, paiements et reçus

---

## ✨ Fonctionnalités principales

- 🔐 Authentification multi-rôles avec redirection automatique vers le bon tableau de bord
- 📊 Tableaux de bord dédiés par rôle
- 🗓️ Inscriptions annuelles, délibérations, rattrapage et passage d'année
- 📅 Emploi du temps récurrent, appel et cahier de textes
- 📝 Moyennes pondérées, rangs, bulletins verrouillés après validation
- 💰 Paiements, échéanciers, remises, reçus, dépenses, salaires et bilan
- 📄 Documents officiels avec **code de vérification public** anti-fraude
- 📥 Import de stagiaires via Excel, avec modèle téléchargeable
- 💬 Messagerie interne (individuelle et groupée) et notifications
- 📤 Exports PDF & Excel (notes, stagiaires, absences, rapports financiers)
- ⏰ Tâches automatiques : rappels, retards, sauvegardes

---

## 🛠️ Stack Technique

- **Backend :** Laravel (PHP 8.1+)
- **Frontend :** Blade, Bootstrap 5, Tailwind CSS
- **Build :** Vite
- **Base de données :** MySQL / MariaDB
- **Paquets :** DomPDF (PDF), Laravel Excel, Spatie Activity Log, Laravel Breeze

---

## 🚀 Installation locale

```bash
git clone https://github.com/alhassane224624/GS_ETABLISSEMENT_GS.git
cd GS_ETABLISSEMENT_GS
composer install
npm install
cp .env.example .env          # Windows : copy .env.example .env
php artisan key:generate
```

Configure la base de données dans `.env`, puis :

```bash
php artisan migrate --seed    # --seed : charge les données de démonstration
php artisan storage:link
npm run build
php artisan serve
```

Tâches automatiques (rappels, retards, sauvegardes) :

```bash
php artisan schedule:work     # en local ; en production : cron « php artisan schedule:run » chaque minute
```

---

## 🔑 Comptes de démonstration

Créés par `php artisan migrate --seed`. Le mot de passe est **`password`** pour tous les comptes.

| Rôle | E-mail |
|---|---|
| Administrateur | admin@demo.ma |
| Comptable | comptable@demo.ma |
| Professeur | prof.alaoui@demo.ma |
| Stagiaire | stagiaire@demo.ma |
| Parent | parent@demo.ma |

> ⚠️ Ces comptes servent uniquement aux tests. En production, lance `php artisan migrate` **sans** `--seed`.

---

## 📄 Licence

Projet développé à des fins pédagogiques et professionnelles par Alhassane Diané.
