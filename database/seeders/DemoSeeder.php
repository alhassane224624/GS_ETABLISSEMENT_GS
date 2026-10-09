<?php

namespace Database\Seeders;

use App\Http\Controllers\BulletinController;
use App\Models\Absence;
use App\Models\AnneeScolaire;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\ConfigurationPaiement;
use App\Models\Creneau;
use App\Models\Depense;
use App\Models\Echeancier;
use App\Models\Examen;
use App\Models\Filiere;
use App\Models\Matiere;
use App\Models\Message;
use App\Models\Niveau;
use App\Models\Note;
use App\Models\Periode;
use App\Models\Planning;
use App\Models\Remise;
use App\Models\Salaire;
use App\Models\Salle;
use App\Models\Stagiaire;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\EmploiDuTempsService;
use App\Services\ExamenService;
use App\Services\FinanceService;
use App\Services\PaiementService;
use App\Services\RemiseService;
use App\Services\StagiaireService;
use App\Support\Etablissement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Jeu de données de démonstration couvrant TOUTES les fonctionnalités.
 * Les dates sont calculées à partir d'aujourd'hui : l'année scolaire en cours est toujours « active ».
 *
 *   php artisan migrate:fresh --seed      (⚠️ efface la base : à faire sur une base de test)
 *
 * Mot de passe de tous les comptes : password
 */
class DemoSeeder extends Seeder
{
    private const MDP = 'password';
    private const MOIS = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    private User $admin;
    private array $profs = [];
    private array $matieres = [];
    private array $classes = [];
    private array $salles = [];
    private AnneeScolaire $annee;
    private Periode $s1;
    private Periode $s2;
    private Carbon $debutAnnee;

    public function run(): void
    {
        mt_srand(2026); // données identiques à chaque exécution

        $this->etape('Paramètres de l\'établissement', fn () => $this->parametres());
        $this->etape('Comptes administrateur, comptable, professeurs', fn () => $this->utilisateurs());
        $this->etape('Années scolaires et périodes', fn () => $this->annees());
        $this->etape('Filières, niveaux, matières, salles, classes', fn () => $this->structure());
        $this->etape('Affectation des professeurs', fn () => $this->affectations());
        $this->etape('Stagiaires (comptes, inscriptions, frais d\'inscription)', fn () => $this->stagiaires());
        $this->etape('Mensualités, remise, paiements', fn () => $this->finances());
        $this->etape('Emploi du temps et séances', fn () => $this->emploiDuTemps());
        $this->etape('Appels, absences, cahier de textes, justificatifs', fn () => $this->vieScolaire());
        $this->etape('Notes S1 et S2', fn () => $this->notes());
        $this->etape('Session d\'examen S1 et résultats', fn () => $this->examens());
        $this->etape('Bulletins S1 validés (classe DI-1A)', fn () => $this->bulletins());
        $this->etape('Parents, document, messages', fn () => $this->divers());
        $this->etape('Dépenses et salaires', fn () => $this->depensesSalaires());

        $this->identifiants();
    }

    // =====================================================================

    private function parametres(): void
    {
        $this->call(PaymentConfigSeeder::class);

        Etablissement::enregistrer([
            'etablissement_nom' => 'Institut Al Amal de Formation',
            'etablissement_slogan' => 'Centre de formation professionnelle privé',
            'etablissement_adresse' => '12, boulevard Mohammed V',
            'etablissement_ville' => 'Nador',
            'etablissement_telephone' => '05 36 00 00 00',
            'etablissement_email' => 'contact@alamal-formation.ma',
            'etablissement_site' => 'www.alamal-formation.ma',
            'etablissement_ice' => '001234567000089',
            'etablissement_rc' => '12345',
            'etablissement_if' => '45678901',
            'etablissement_autorisation' => 'AUT-2024-017',
            'etablissement_directeur' => 'M. Ahmed Benali',
            'etablissement_directeur_titre' => 'Directeur pédagogique',
            'banque_nom' => 'CIH Bank',
            'rib_etablissement' => '230 810 0000000000000000 12',
            'recu_footer_text' => 'Merci pour votre confiance.',
            'recu_conditions' => 'Les frais versés ne sont ni remboursables ni transférables.',
            'seuil_admission' => '10',
            'seuil_rattrapage' => '8',
        ]);

        // Démo : pas de suspension automatique pour retard de paiement
        ConfigurationPaiement::set('max_retard_avant_suspension', '0');
    }

    private function utilisateurs(): void
    {
        $this->admin = $this->compte('Administrateur Démo', 'admin@demo.ma', 'administrateur');
        Auth::setUser($this->admin); // les services enregistrent « qui a fait quoi »

        $this->compte('Comptable Démo', 'comptable@demo.ma', 'comptable');

        $profs = [
            'alaoui'   => ['Karim Alaoui', 'prof.alaoui@demo.ma', 'Algorithmique', 'horaire', 150, null],
            'idrissi'  => ['Salma Idrissi', 'prof.idrissi@demo.ma', 'Développement web', 'horaire', 140, null],
            'benali'   => ['Youssef Benali', 'prof.benali@demo.ma', 'Comptabilité et droit', 'fixe', null, 6000],
            'chraibi'  => ['Nadia Chraibi', 'prof.chraibi@demo.ma', 'Marketing et anglais', 'horaire', 120, null],
        ];
        foreach ($profs as $cle => [$nom, $email, $specialite, $mode, $taux, $fixe]) {
            $u = $this->compte($nom, $email, 'professeur');
            $u->update(['specialite' => $specialite, 'mode_remuneration' => $mode, 'taux_horaire' => $taux, 'salaire_fixe' => $fixe]);
            $this->profs[$cle] = $u;
        }
    }

    private function annees(): void
    {
        $now = now();
        $an = $now->month >= 9 ? $now->year : $now->year - 1;
        $this->debutAnnee = Carbon::create($an, 9, 1);

        $this->annee = AnneeScolaire::create([
            'nom' => $an . '/' . ($an + 1),
            'debut' => $this->debutAnnee->toDateString(),
            'fin' => Carbon::create($an + 1, 6, 30)->toDateString(),
            'is_active' => true,
        ]);

        // Année suivante (inactive, sans classe) : pour tester le passage d'année
        AnneeScolaire::create([
            'nom' => ($an + 1) . '/' . ($an + 2),
            'debut' => Carbon::create($an + 1, 9, 1)->toDateString(),
            'fin' => Carbon::create($an + 2, 6, 30)->toDateString(),
            'is_active' => false,
        ]);

        $finS1 = Carbon::create($an + 1, 1, 31);
        $this->s1 = Periode::create(['nom' => 'Semestre 1', 'type' => 'semestre', 'debut' => $this->debutAnnee->toDateString(),
            'fin' => $finS1->toDateString(), 'annee_scolaire_id' => $this->annee->id, 'is_active' => $now->lte($finS1)]);
        $this->s2 = Periode::create(['nom' => 'Semestre 2', 'type' => 'semestre', 'debut' => Carbon::create($an + 1, 2, 1)->toDateString(),
            'fin' => Carbon::create($an + 1, 6, 30)->toDateString(), 'annee_scolaire_id' => $this->annee->id, 'is_active' => $now->gt($finS1)]);
    }

    private function structure(): void
    {
        $di = Filiere::create(['nom' => 'Développement Informatique', 'niveau' => 'Technicien spécialisé']);
        $ge = Filiere::create(['nom' => 'Gestion des Entreprises', 'niveau' => 'Technicien spécialisé']);

        $niveaux = [];
        foreach (['DI' => $di, 'GE' => $ge] as $code => $f) {
            $niveaux[$code][1] = Niveau::create(['nom' => '1re année', 'ordre' => 1, 'filiere_id' => $f->id, 'duree_semestres' => 2]);
            $niveaux[$code][2] = Niveau::create(['nom' => '2e année', 'ordre' => 2, 'filiere_id' => $f->id, 'duree_semestres' => 2]);
        }

        $defs = [
            'ALGO'   => ['Algorithmique', 4, '#4f46e5', ['DI']],
            'WEB'    => ['Développement web', 3, '#0ea5e9', ['DI']],
            'BDD'    => ['Bases de données', 3, '#10b981', ['DI']],
            'COMPTA' => ['Comptabilité générale', 4, '#f59e0b', ['GE']],
            'MKT'    => ['Marketing', 3, '#ec4899', ['GE']],
            'DROIT'  => ['Droit des affaires', 2, '#64748b', ['GE']],
            'ANG'    => ['Anglais professionnel', 1, '#8b5cf6', ['DI', 'GE']],
        ];
        $filieres = ['DI' => $di, 'GE' => $ge];
        foreach ($defs as $code => [$nom, $coef, $couleur, $fs]) {
            $m = Matiere::create(['nom' => $nom, 'code' => $code, 'coefficient' => $coef, 'couleur' => $couleur]);
            $this->matieres[$code] = $m;
            foreach ($fs as $f) {
                DB::table('matiere_filiere')->insert(['filiere_id' => $filieres[$f]->id, 'matiere_id' => $m->id, 'created_at' => now(), 'updated_at' => now()]);
                foreach ($niveaux[$f] as $n) {
                    DB::table('matiere_niveau')->insert(['matiere_id' => $m->id, 'niveau_id' => $n->id, 'heures_cours' => 60, 'is_obligatoire' => true, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }

        foreach ([['Salle 1', 30, 'salle_cours'], ['Salle 2', 30, 'salle_cours'], ['Salle 3', 30, 'salle_cours'],
                  ['Labo informatique', 24, 'salle_informatique'], ['Amphithéâtre', 120, 'amphitheatre']] as [$nom, $cap, $type]) {
            $this->salles[$nom] = Salle::create(['nom' => $nom, 'capacite' => $cap, 'type' => $type, 'disponible' => true, 'batiment' => 'A']);
        }

        foreach (['DI-1A' => ['DI', 1], 'DI-2A' => ['DI', 2], 'GE-1A' => ['GE', 1], 'GE-2A' => ['GE', 2]] as $nom => [$f, $o]) {
            $this->classes[$nom] = Classe::create(['nom' => $nom, 'niveau_id' => $niveaux[$f][$o]->id, 'filiere_id' => $filieres[$f]->id,
                'annee_scolaire_id' => $this->annee->id, 'effectif_max' => 25, 'effectif_actuel' => 0]);
        }
    }

    private function affectations(): void
    {
        $di = $this->classes['DI-1A']->filiere_id;
        $ge = $this->classes['GE-1A']->filiere_id;
        $liens = [
            'alaoui'  => [[$di, 'ALGO'], [$di, 'BDD']],
            'idrissi' => [[$di, 'WEB']],
            'benali'  => [[$ge, 'COMPTA'], [$ge, 'DROIT']],
            'chraibi' => [[$ge, 'MKT'], [$di, 'ANG'], [$ge, 'ANG']],
        ];
        foreach ($liens as $prof => $paires) {
            $p = $this->profs[$prof];
            foreach (collect($paires)->pluck(0)->unique() as $filiereId) {
                DB::table('professeur_filiere')->insert(['professeur_id' => $p->id, 'filiere_id' => $filiereId, 'created_by' => $this->admin->id,
                    'is_active' => true, 'date_assignation' => $this->debutAnnee->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
            }
            foreach ($paires as [$filiereId, $code]) {
                DB::table('professeur_matiere')->insert(['professeur_id' => $p->id, 'matiere_id' => $this->matieres[$code]->id, 'filiere_id' => $filiereId,
                    'assigned_by' => $this->admin->id, 'is_active' => true, 'date_assignation' => $this->debutAnnee->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    private function stagiaires(): void
    {
        $noms = [
            'DI-1A' => [['Traoré', 'Fatoumata', 'F'], ['El Amrani', 'Yassine', 'M'], ['Bennani', 'Salma', 'F'], ['Diallo', 'Mamadou', 'M'], ['Ouazzani', 'Imane', 'F'], ['Tazi', 'Omar', 'M']],
            'DI-2A' => [['Berrada', 'Hamza', 'M'], ['Kettani', 'Lina', 'F'], ['Camara', 'Ibrahima', 'M'], ['Fassi', 'Nour', 'F'], ['Lahlou', 'Adam', 'M'], ['Sow', 'Aïssatou', 'F']],
            'GE-1A' => [['Chami', 'Rania', 'F'], ['Mansouri', 'Ilyas', 'M'], ['Bah', 'Kadiatou', 'F'], ['Rachidi', 'Mehdi', 'M'], ['Zerouali', 'Sara', 'F'], ['Kone', 'Moussa', 'M']],
            'GE-2A' => [['Alami', 'Hiba', 'F'], ['Naciri', 'Anas', 'M'], ['Sylla', 'Mariama', 'F'], ['Bouzidi', 'Ayoub', 'M'], ['Hajji', 'Meryem', 'F'], ['Barry', 'Alpha', 'M']],
        ];

        $service = app(StagiaireService::class);
        $i = 0;
        foreach ($noms as $classeNom => $liste) {
            $classe = $this->classes[$classeNom];
            foreach ($liste as [$nom, $prenom, $sexe]) {
                $i++;
                $email = $i === 1 ? 'stagiaire@demo.ma' : $this->slug($prenom) . '.' . $this->slug($nom) . '@demo.ma';
                [$s] = $service->creer([
                    'nom' => mb_strtoupper($nom), 'prenom' => $prenom, 'sexe' => $sexe, 'email' => $email,
                    'date_naissance' => Carbon::create(2004 + mt_rand(0, 3), mt_rand(1, 12), mt_rand(1, 28))->toDateString(),
                    'lieu_naissance' => ['Nador', 'Oujda', 'Conakry', 'Dakar', 'Fès', 'Tanger'][mt_rand(0, 5)],
                    'telephone' => '06' . mt_rand(10000000, 99999999),
                    'nom_tuteur' => 'Famille ' . $nom, 'telephone_tuteur' => '06' . mt_rand(10000000, 99999999),
                    'email_tuteur' => $i === 1 ? 'parent@demo.ma' : null,
                    'filiere_id' => $classe->filiere_id, 'niveau_id' => $classe->niveau_id, 'classe_id' => $classe->id,
                    'date_inscription' => $this->debutAnnee->toDateString(),
                    'frais_inscription' => 1500,
                    'frais_payes' => $i % 4 !== 0, // 3 sur 4 ont réglé l'inscription en espèces (reçu disponible)
                ], $this->admin->id);
                $s->user->update(['password' => Hash::make(self::MDP)]);
            }
        }

        // Demande d'inscription en ligne en attente de validation (dossier suspendu, compte désactivé)
        [$demande] = $service->creer([
            'nom' => 'NOUVEAU', 'prenom' => 'Candidat', 'sexe' => 'M', 'email' => 'candidat@demo.ma', 'telephone' => '0611223344',
            'filiere_id' => $this->classes['DI-1A']->filiere_id,
            'statut' => 'suspendu', 'motif_statut' => 'Demande d\'inscription en ligne — à valider',
        ], null, false);
        $demande->user->update(['password' => Hash::make(self::MDP)]);
    }

    private function finances(): void
    {
        $paiements = app(PaiementService::class);
        $stagiaires = Stagiaire::where('statut', 'actif')->orderBy('id')->get();

        foreach ($stagiaires as $n => $s) {
            $mensualite = str_starts_with($s->classe->nom ?? '', 'DI') ? 800 : 700;

            // 10 mensualités, de septembre à juin, le 5 du mois
            for ($k = 0; $k < 10; $k++) {
                $date = $this->debutAnnee->copy()->addMonthsNoOverflow($k)->day(5);
                Echeancier::create([
                    'stagiaire_id' => $s->id, 'annee_scolaire_id' => $this->annee->id, 'type' => 'mensualite',
                    'titre' => 'Mensualité ' . self::MOIS[$date->month] . ' ' . $date->year, 'montant' => $mensualite,
                    'montant_remise' => 0, 'montant_paye' => 0, 'montant_restant' => $mensualite,
                    'date_echeance' => $date->toDateString(), 'statut' => 'impaye',
                ])->recalculer();
            }

            // Bourse : 10 % sur les mensualités du 2e stagiaire
            if ($n === 1) {
                Remise::create(['stagiaire_id' => $s->id, 'created_by' => $this->admin->id, 'titre' => 'Bourse d\'excellence', 'type' => 'pourcentage',
                    'valeur' => 10, 'porte' => 'mensualite', 'motif' => 'Excellents résultats à l\'admission',
                    'date_debut' => $this->debutAnnee->toDateString(), 'date_fin' => $this->annee->fin->toDateString(), 'is_active' => true]);
                app(RemiseService::class)->appliquer($s);
            }

            // Mensualités échues : payées en espèces, sauf pour 1 stagiaire sur 5 (retards à tester)
            if ($n % 5 === 4) {
                continue;
            }
            $echues = Echeancier::where('stagiaire_id', $s->id)->where('type', 'mensualite')
                ->whereDate('date_echeance', '<=', now()->toDateString())->orderBy('date_echeance')->get();
            foreach ($echues as $e) {
                $paiements->enregistrer([
                    'stagiaire_id' => $s->id, 'montant' => $e->montant_restant, 'methode_paiement' => 'especes',
                    'date_paiement' => $e->date_echeance->copy()->addDays(mt_rand(0, 4))->min(now())->toDateString(),
                    'echeanciers' => [$e->id],
                ]);
            }
        }

        // Chèque en attente d'encaissement (stagiaire de démo) : à valider ou refuser
        $demo = Stagiaire::where('email', 'stagiaire@demo.ma')->first();
        $prochaine = Echeancier::where('stagiaire_id', $demo->id)->where('montant_restant', '>', 0)->orderBy('date_echeance')->first();
        if ($prochaine) {
            $paiements->enregistrer([
                'stagiaire_id' => $demo->id, 'montant' => $prochaine->montant_restant, 'methode_paiement' => 'cheque',
                'reference_externe' => 'CHQ 4587120', 'banque' => 'Attijariwafa bank',
                'date_paiement' => now()->toDateString(), 'echeanciers' => [$prochaine->id],
            ]);
        }
    }

    private function emploiDuTemps(): void
    {
        $P = $this->profs;
        $M = $this->matieres;
        $S = $this->salles;
        // [jour, début, fin, matière, prof, type] — aucun conflit de prof ni de salle
        $grilles = [
            'DI-1A' => ['Labo informatique', [[1, '08:30', '10:30', 'ALGO', 'alaoui', 'cours'], [1, '10:45', '12:45', 'WEB', 'idrissi', 'tp'], [3, '08:30', '10:30', 'BDD', 'alaoui', 'cours'], [4, '14:00', '16:00', 'ANG', 'chraibi', 'cours']]],
            'DI-2A' => ['Salle 1', [[1, '14:00', '16:00', 'ALGO', 'alaoui', 'td'], [2, '08:30', '10:30', 'WEB', 'idrissi', 'cours'], [3, '10:45', '12:45', 'BDD', 'alaoui', 'tp'], [5, '08:30', '10:30', 'ANG', 'chraibi', 'cours']]],
            'GE-1A' => ['Salle 2', [[1, '08:30', '10:30', 'COMPTA', 'benali', 'cours'], [2, '10:45', '12:45', 'MKT', 'chraibi', 'cours'], [3, '14:00', '16:00', 'DROIT', 'benali', 'cours'], [4, '08:30', '10:30', 'ANG', 'chraibi', 'cours']]],
            'GE-2A' => ['Salle 3', [[2, '08:30', '10:30', 'COMPTA', 'benali', 'td'], [2, '14:00', '16:00', 'MKT', 'chraibi', 'cours'], [4, '10:45', '12:45', 'DROIT', 'benali', 'cours'], [5, '10:45', '12:45', 'ANG', 'chraibi', 'cours']]],
        ];

        foreach ($grilles as $classeNom => [$salle, $creneaux]) {
            foreach ($creneaux as [$jour, $debut, $fin, $code, $prof, $type]) {
                Creneau::create([
                    'annee_scolaire_id' => $this->annee->id, 'classe_id' => $this->classes[$classeNom]->id,
                    'matiere_id' => $M[$code]->id, 'professeur_id' => $P[$prof]->id, 'salle_id' => $S[$salle]->id,
                    'jour' => $jour, 'heure_debut' => $debut, 'heure_fin' => $fin, 'type_cours' => $type,
                    'is_active' => true, 'created_by' => $this->admin->id,
                ]);
            }
        }

        // Séances : 45 jours en arrière (salaires du mois précédent) → 3 semaines en avant
        $debut = now()->subDays(45)->max($this->debutAnnee)->toDateString();
        $fin = now()->addWeeks(3)->min($this->annee->fin)->toDateString();
        app(EmploiDuTempsService::class)->generer($this->annee->id, $debut, $fin);
    }

    private function vieScolaire(): void
    {
        $contenus = ['Introduction et rappels', 'Exercices d\'application', 'Étude de cas', 'Travaux pratiques guidés', 'Correction du devoir', 'Nouveau chapitre'];
        $passees = Planning::where('date', '<', now()->toDateString())->where('statut', 'valide')->orderBy('date')->get();

        foreach ($passees as $k => $p) {
            $eleves = Stagiaire::where('classe_id', $p->classe_id)->where('statut', 'actif')->pluck('id');

            // Environ une séance sur trois : un absent, parfois un retard
            if ($k % 3 === 0 && $eleves->isNotEmpty()) {
                $this->absence($eleves[$k % $eleves->count()], $p, null);
                if ($k % 6 === 0 && $eleves->count() > 1) {
                    $this->absence($eleves[($k + 1) % $eleves->count()], $p, 10 + ($k % 3) * 5);
                }
            }

            DB::table('plannings')->where('id', $p->id)->update([
                'appel_fait_at' => Carbon::parse($p->date->toDateString() . ' ' . $p->heure_debut)->addMinutes(10),
                'contenu_seance' => $contenus[$k % count($contenus)] . ' — ' . ($p->matiere->nom ?? ''),
                'statut' => 'termine',
                'updated_at' => now(),
            ]);
        }

        // Travail à faire pour les prochains jours (cahier de textes, stagiaire et parent)
        $recentes = Planning::whereNotNull('appel_fait_at')->orderByDesc('date')->take(6)->get();
        foreach ($recentes as $k => $p) {
            DB::table('plannings')->where('id', $p->id)->update([
                'devoirs' => ['Exercices 1 à 4 du support', 'Préparer l\'exposé de groupe', 'Relire le chapitre et faire le QCM'][$k % 3],
                'devoirs_pour' => now()->addDays(2 + $k)->toDateString(),
            ]);
        }

        // Stagiaire de démo : une absence avec justificatif en attente, une justifiée
        $demo = Stagiaire::where('email', 'stagiaire@demo.ma')->first();
        $seances = Planning::where('classe_id', $demo->classe_id)->whereNotNull('appel_fait_at')->orderByDesc('date')->take(2)->get();
        foreach ($seances as $k => $p) {
            $a = Absence::where('planning_id', $p->id)->where('stagiaire_id', $demo->id)->first() ?? $this->absence($demo->id, $p, null);
            $a->update($k === 0
                ? ['justification_statut' => 'en_attente', 'justification_motif' => 'Rendez-vous médical (certificat joint)', 'justification_soumise_at' => now()]
                : ['justification_statut' => 'acceptee', 'justifiee' => true, 'motif' => 'Convocation administrative', 'justification_motif' => 'Convocation administrative',
                   'justification_soumise_at' => now()->subDays(3), 'justification_traitee_at' => now()->subDays(2), 'justification_traitee_by' => $this->admin->id]);
        }
    }

    private function notes(): void
    {
        $parFiliere = DB::table('matiere_filiere')->get()->groupBy('filiere_id');
        $prof = fn ($matiereId, $filiereId) => DB::table('professeur_matiere')->where('matiere_id', $matiereId)->where('filiere_id', $filiereId)->value('professeur_id') ?? $this->admin->id;

        foreach (Stagiaire::where('statut', 'actif')->get() as $s) {
            $niveau = 8 + mt_rand(0, 90) / 10; // chaque stagiaire a son « niveau » (8 à 17)
            foreach ($parFiliere[$s->filiere_id] ?? [] as $lien) {
                foreach ([$this->s1, $this->s2] as $periode) {
                    foreach (['ds', 'cc'] as $type) {
                        Note::create([
                            'stagiaire_id' => $s->id, 'matiere_id' => $lien->matiere_id, 'classe_id' => $s->classe_id,
                            'periode_id' => $periode->id, 'type_note' => $type, 'note_sur' => 20,
                            'note' => max(0, min(20, round($niveau + mt_rand(-30, 30) / 10, 2))),
                            'created_by' => $prof($lien->matiere_id, $s->filiere_id),
                        ]);
                    }
                }
            }
        }
    }

    private function examens(): void
    {
        $finS1 = $this->s1->fin->copy();
        $samedi = $finS1->copy()->subDays(10)->next(Carbon::SATURDAY); // samedi : aucun cours au planning

        $examen = Examen::create([
            'annee_scolaire_id' => $this->annee->id, 'periode_id' => $this->s1->id, 'nom' => 'Examens du 1er semestre',
            'type' => 'normale', 'date_debut' => $samedi->copy()->subDays(5)->toDateString(), 'date_fin' => $samedi->toDateString(),
            'statut' => 'planifiee', 'created_by' => $this->admin->id,
        ]);
        Examen::create([
            'annee_scolaire_id' => $this->annee->id, 'periode_id' => null, 'nom' => 'Session de rattrapage',
            'type' => 'rattrapage', 'date_debut' => $this->annee->fin->copy()->subDays(10)->toDateString(),
            'date_fin' => $this->annee->fin->copy()->subDays(5)->toDateString(), 'statut' => 'planifiee', 'created_by' => $this->admin->id,
        ]);

        $service = app(ExamenService::class);
        $classe = $this->classes['DI-1A'];
        foreach ([['ALGO', '09:00', '11:00', 'alaoui'], ['WEB', '11:30', '13:30', 'idrissi']] as [$code, $debut, $fin, $prof]) {
            $epreuve = $service->creerEpreuve($examen, [
                'classe_id' => $classe->id, 'matiere_id' => $this->matieres[$code]->id, 'date' => $samedi->toDateString(),
                'heure_debut' => $debut, 'heure_fin' => $fin, 'salle_id' => $this->salles['Amphithéâtre']->id,
                'surveillant_id' => $this->profs[$prof]->id, 'note_sur' => 20,
            ]);

            $notes = [];
            $absents = [];
            foreach ($service->convoques($epreuve) as $k => $s) {
                if ($k === 3 && $code === 'WEB') {
                    $absents[$s->id] = 1; // un absent à l'épreuve
                } else {
                    $notes[$s->id] = round(6 + mt_rand(0, 130) / 10, 2);
                }
            }
            $service->enregistrerResultats($epreuve, $notes, $absents);
        }
    }

    private function bulletins(): void
    {
        // Mêmes calculs que le bouton « Générer » (méthodes du contrôleur), pour une classe seulement :
        // les autres classes / le S2 restent à générer depuis l'interface.
        $controleur = app(BulletinController::class);
        $calcul = new \ReflectionMethod($controleur, 'calculerMoyennesClasse');
        $rangs = new \ReflectionMethod($controleur, 'calculerRangs');
        $appreciation = new \ReflectionMethod($controleur, 'genererAppreciation');
        foreach ([$calcul, $rangs, $appreciation] as $m) {
            $m->setAccessible(true);
        }

        $classe = $this->classes['DI-1A']->load('stagiaires');
        $resultats = $calcul->invoke($controleur, $classe, $this->s1);
        $classement = $rangs->invoke($controleur, $resultats);

        foreach ($resultats as $stagiaireId => $r) {
            Bulletin::create([
                'stagiaire_id' => $stagiaireId, 'classe_id' => $classe->id, 'periode_id' => $this->s1->id,
                'moyenne_generale' => $r['moyenne'], 'rang' => $classement[$stagiaireId], 'total_classe' => $resultats->count(),
                'appreciation_generale' => $appreciation->invoke($controleur, $r['moyenne'], $classement[$stagiaireId], $resultats->count()),
                'moyennes_matieres' => $r['matieres'], 'created_by' => $this->admin->id,
                'validated_at' => now(), 'validated_by' => $this->admin->id,
            ]);
        }
    }

    private function divers(): void
    {
        $demo = Stagiaire::where('email', 'stagiaire@demo.ma')->first();
        $soeur = Stagiaire::where('classe_id', $this->classes['GE-1A']->id)->orderBy('id')->first();

        // Parent de la stagiaire de démo, avec un 2e enfant (fratrie)
        $parent = $this->compte('Famille Traoré', 'parent@demo.ma', 'parent');
        $parent->enfants()->attach([$demo->id => ['lien' => 'mere'], $soeur->id => ['lien' => 'mere']]);

        // Une attestation déjà délivrée (registre + vérification publique)
        app(DocumentService::class)->delivrer('attestation', $demo);

        Message::create(['sender_id' => $this->admin->id, 'receiver_id' => $demo->user_id, 'message' => 'Bienvenue sur votre espace stagiaire !', 'is_read' => false]);
        Message::create(['sender_id' => $this->profs['alaoui']->id, 'receiver_id' => $demo->user_id, 'message' => 'Pensez à rendre l\'exercice 4 pour la prochaine séance.', 'is_read' => false]);
    }

    private function depensesSalaires(): void
    {
        $mois = $this->debutAnnee->copy();
        while ($mois->lte(now())) {
            $jour = fn ($j) => $mois->copy()->day($j)->min(now())->toDateString();
            Depense::create(['categorie' => 'loyer', 'libelle' => 'Loyer ' . self::MOIS[$mois->month] . ' ' . $mois->year, 'montant' => 8000, 'date_depense' => $jour(1), 'mode_paiement' => 'virement', 'reference' => 'VIR-' . $mois->format('Ym'), 'fournisseur' => 'SCI Al Manar', 'created_by' => $this->admin->id]);
            Depense::create(['categorie' => 'energie', 'libelle' => 'Électricité et internet', 'montant' => 1200 + mt_rand(0, 400), 'date_depense' => $jour(10), 'mode_paiement' => 'especes', 'fournisseur' => 'ONEE / IAM', 'created_by' => $this->admin->id]);
            Depense::create(['categorie' => 'fournitures', 'libelle' => 'Fournitures de bureau', 'montant' => 300 + mt_rand(0, 500), 'date_depense' => $jour(15), 'mode_paiement' => 'especes', 'created_by' => $this->admin->id]);
            $mois->addMonth();
        }

        // Salaires du mois précédent (calculés sur les séances réalisées) : un payé, les autres à payer
        $finance = app(FinanceService::class);
        $precedent = now()->subMonthNoOverflow()->startOfMonth();
        if ($precedent->gte($this->debutAnnee)) {
            $finance->calculerMois($precedent);
            $salaire = Salaire::whereDate('mois', $precedent->toDateString())->where('professeur_id', $this->profs['benali']->id)->first();
            if ($salaire && $salaire->montant_net > 0) {
                $finance->payer($salaire, 'virement', $precedent->copy()->endOfMonth()->min(now())->toDateString(), 'VIR-SAL-' . $precedent->format('Ym'));
            }
        }
    }

    // =====================================================================

    private function compte(string $nom, string $email, string $role): User
    {
        $u = User::create(['name' => $nom, 'email' => $email, 'password' => Hash::make(self::MDP), 'role' => $role, 'is_active' => true]);
        $u->forceFill(['email_verified_at' => now()])->save();
        return $u;
    }

    private function absence(int $stagiaireId, Planning $p, ?int $retard): Absence
    {
        return Absence::create([
            'stagiaire_id' => $stagiaireId, 'planning_id' => $p->id,
            'periode_id' => Absence::periodePourDate($p->date->toDateString()),
            'date' => $p->date->toDateString(), 'type' => 'heure',
            'heure_debut' => substr($p->heure_debut, 0, 5), 'heure_fin' => substr($p->heure_fin, 0, 5),
            'retard_minutes' => $retard, 'justifiee' => false, 'created_by' => $p->professeur_id,
        ]);
    }

    private function slug(string $s): string
    {
        return str($s)->ascii()->lower()->replace([' ', "'"], '')->toString();
    }

    private function etape(string $libelle, callable $fn): void
    {
        $this->command?->line("  • {$libelle}…");
        $fn();
    }

    private function identifiants(): void
    {
        $this->command?->newLine();
        $this->command?->info('Données de démonstration créées. Mot de passe de tous les comptes : ' . self::MDP);
        $this->command?->table(['Rôle', 'E-mail', 'À tester'], [
            ['Administrateur', 'admin@demo.ma', 'tout : inscriptions, examens, bulletins S2, délibération, passage, documents…'],
            ['Comptable', 'comptable@demo.ma', 'paiements (chèque en attente), retards, remises, dépenses, salaires, bilan'],
            ['Professeur', 'prof.alaoui@demo.ma', 'planning, appel + cahier de textes, notes, épreuves'],
            ['Professeur', 'prof.chraibi@demo.ma', 'enseigne en DI et en GE (messages de groupe, épreuves)'],
            ['Stagiaire', 'stagiaire@demo.ma', 'notes, bulletin S1, absences (1 justificatif en attente), paiements, attestation'],
            ['Parent', 'parent@demo.ma', 'portail parents : 2 enfants'],
            ['Candidat', 'candidat@demo.ma', 'inscription en ligne à valider (compte désactivé)'],
        ]);
    }
}
