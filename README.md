# 🎓 GS_ETABLISSEMENT - Système de Gestion Scolaire

Application web complète de gestion d'établissement scolaire / centre de formation, développée avec **Laravel**. La plateforme centralise l'administration pédagogique, financière et académique autour de plusieurs espaces utilisateurs dédiés.

🔗 **Démo en ligne :** [gestionetablissement.42web.io](https://gestionetablissement.42web.io/)

---

## 📸 Aperçu

<!-- Ajoute tes captures d'écran ici, par exemple : -->
<!-- ![Dashboard Admin](docs/screenshots/dashboard-admin.png) -->
<!-- ![Espace Comptable](docs/screenshots/dashboard-comptable.png) -->
<!-- ![Espace Stagiaire](docs/screenshots/dashboard-stagiaire.png) -->
<!-- ![Espace Professeur](docs/screenshots/dashboard-professeur.png) -->

> Ajoute tes images dans un dossier `docs/screenshots/` à la racine du projet, puis référence-les ci-dessus avec `![Titre](docs/screenshots/nom-fichier.png)`.

---

## 👥 Acteurs & Rôles

L'application propose des espaces distincts selon le rôle de l'utilisateur connecté :

### 🛠️ Administrateur
Gère l'ensemble de l'établissement :
- Gestion des stagiaires (inscription, import Excel, export PDF/Excel)
- Gestion des filières, classes, niveaux et matières
- Gestion des salles (disponibilité, planning)
- Gestion des années scolaires et périodes
- Gestion des notes et génération des bulletins
- Gestion des absences et rapports
- Gestion des utilisateurs (professeurs, comptables, comptes)
- Supervision globale de l'établissement (le module financier est géré en détail par le rôle Comptable)
- Statistiques globales de l'établissement
- Messagerie et notifications internes

### 👨‍🏫 Professeur
Espace dédié à la gestion pédagogique :
- Consultation de la liste des stagiaires
- Saisie et modification des notes par matière
- Export des notes et listes de stagiaires (PDF/Excel)
- Gestion des présences/absences
- Consultation et création de son planning de cours

### 🎓 Stagiaire / Élève
Espace personnel de suivi :
- Consultation des notes et téléchargement du relevé
- Consultation et téléchargement du bulletin
- Consultation de l'emploi du temps
- Suivi de ses absences
- Suivi de ses paiements et échéanciers
- Téléchargement des reçus de paiement
- Gestion de son profil

### 💼 Comptable
Espace dédié entièrement à la gestion financière :
- Tableau de bord financier (paiements du jour/mois, en attente, échéances en retard)
- Gestion complète des paiements (validation, historique, reçus)
- Gestion des échéanciers (création, suivi, alertes de retard)
- Suivi des stagiaires liés à la facturation
- Gestion des remises actives
- Rapports financiers détaillés avec graphiques (évolution des paiements, méthodes de paiement)

---

## ✨ Fonctionnalités principales

- 🔐 Authentification multi-rôles avec redirection automatique vers le bon tableau de bord
- 📊 Tableaux de bord dédiés par rôle avec statistiques en temps réel
- 📝 Gestion des notes et génération automatique des bulletins
- 📅 Planning des cours et gestion des salles
- 🗂️ Gestion des années scolaires et périodes
- 💰 Système de paiement complet (échéanciers, remises, reçus, rapports financiers)
- 📥 Import de stagiaires via fichier Excel + modèle téléchargeable
- 💬 Messagerie interne (individuelle et groupée)
- 🔔 Système de notifications en temps réel
- 📤 Exports PDF & Excel (notes, stagiaires, absences, statistiques)

---

## 🛠️ Stack Technique

- **Backend :** Laravel (PHP)
- **Frontend :** Blade, Tailwind CSS
- **Build :** Vite
- **Base de données :** MySQL

---

## 🚀 Installation locale

```bash
git clone https://github.com/alhassane224624/GS_ETABLISSEMENT-.git
cd GS_ETABLISSEMENT-
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

---

## 📄 Licence

Projet développé à des fins pédagogiques et professionnelles.
