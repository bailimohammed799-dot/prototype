<?php

/**
 * =====================================================================
 * KRAYIN CRM - TESTS UNITAIRES : PERSON REPOSITORY (+ RAPPORT AUTO)
 * =====================================================================
 *
 * Stratégie de tests unitaires isolés pour valider la logique métier
 * du PersonRepository (sanitization, unique_id, organisation, filtres).
 *
 * NOUVEAUTÉ : chaque test enregistre automatiquement son résultat
 * (statut, durée, règle métier protégée, bug potentiel, criticité)
 * dans un tableau statique. À la toute fin de la suite (afterAll),
 * un rapport Markdown est généré automatiquement dans :
 *   storage/app/reports/person_repository_report_<timestamp>.md
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Webkul\Contact\Models\Organization;
use Webkul\Contact\Models\Person;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Installer\Database\Seeders\User\DatabaseSeeder as UserDatabaseSeeder;
use Webkul\User\Models\User;

uses(RefreshDatabase::class);

// =====================================================================
// COLLECTEUR DE RÉSULTATS POUR LE RAPPORT (classe interne au fichier)
// =====================================================================

class PersonRepositoryTestReport
{
    /** @var array<int, array<string, mixed>> */
    public static array $results = [];

    public static function add(
        string $suite,
        string $test,
        string $rule,
        string $bug,
        string $criticality,
        string $status,
        float $durationMs,
        ?string $error = null
    ): void {
        self::$results[] = [
            'suite' => $suite,
            'test' => $test,
            'rule' => $rule,
            'bug' => $bug,
            'criticality' => $criticality,
            'status' => $status,
            'duration_ms' => round($durationMs, 2),
            'error' => $error,
        ];
    }

    public static function generate(): string
    {
        $total = count(self::$results);
        $passed = count(array_filter(self::$results, fn ($r) => $r['status'] === 'PASS'));
        $failed = $total - $passed;
        $rate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        $now = date('Y-m-d H:i:s');

        $md = "# Rapport de Tests Unitaires — PersonRepository\n\n";
        $md .= "**Généré le :** {$now}\n\n";
        $md .= "## Résumé\n\n";
        $md .= "| Total | Réussis | Échoués | Taux de réussite |\n";
        $md .= "|-------|---------|---------|-------------------|\n";
        $md .= "| {$total} | {$passed} | {$failed} | {$rate}% |\n\n";

        $md .= "## Détail par suite\n\n";

        $grouped = [];
        foreach (self::$results as $r) {
            $grouped[$r['suite']][] = $r;
        }

        foreach ($grouped as $suite => $tests) {
            $md .= "### {$suite}\n\n";
            $md .= "| Statut | Test | Règle métier protégée | Bug détecté si échec | Criticité | Durée (ms) |\n";
            $md .= "|--------|------|------------------------|------------------------|-----------|------------|\n";

            foreach ($tests as $t) {
                $icon = $t['status'] === 'PASS' ? '✅' : '❌';
                $md .= "| {$icon} {$t['status']} | {$t['test']} | {$t['rule']} | {$t['bug']} | {$t['criticality']} | {$t['duration_ms']} |\n";
            }
            $md .= "\n";
        }

        $failedTests = array_filter(self::$results, fn ($r) => $r['status'] === 'FAIL');
        if (! empty($failedTests)) {
            $md .= "## Détails des échecs\n\n";
            foreach ($failedTests as $t) {
                $md .= "### ❌ {$t['test']}\n";
                $md .= "- **Suite :** {$t['suite']}\n";
                $md .= "- **Criticité :** {$t['criticality']}\n";
                $md .= "- **Erreur :** `{$t['error']}`\n\n";
            }
        }

        return $md;
    }

    public static function save(): string
    {
        $dir = storage_path('app/reports');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = 'person_repository_report_'.date('Y-m-d_His').'.md';
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        file_put_contents($path, self::generate());

        return $path;
    }
}

// =====================================================================
// HELPER : exécute un test, mesure sa durée, capture le résultat
// =====================================================================

function runAndRecord(
    string $suite,
    string $test,
    string $rule,
    string $bug,
    string $criticality,
    callable $assertions
): void {
    $start = microtime(true);
    $status = 'PASS';
    $error = null;

    try {
        $assertions();
    } catch (Throwable $e) {
        $status = 'FAIL';
        $error = $e->getMessage();
        throw $e; // on relance pour que Pest/PHPUnit marque bien le test comme échoué
    } finally {
        $durationMs = (microtime(true) - $start) * 1000;
        PersonRepositoryTestReport::add($suite, $test, $rule, $bug, $criticality, $status, $durationMs, $error);
    }
}

beforeEach(function () {
    // Initialise les rôles et permissions de base
    $this->seed(UserDatabaseSeeder::class);
    $this->admin = User::find(1);
    $this->repository = app(PersonRepository::class);
});

// Génération du rapport une fois TOUS les tests du fichier terminés
afterAll(function () {
    $path = PersonRepositoryTestReport::save();
    fwrite(STDOUT, "\n📄 Rapport généré : {$path}\n");
});

// =====================================================================
// SCÉNARIOS : SANITIZATION & UNIQUE ID GENERATION (MÉTHODES INTERNES)
// =====================================================================

describe('Sanitization & Unique ID Generation', function () {

    /**
     * Règle métier protégée : Conversion d'organization_id vide en null
     * Bug détecté si échec : Une chaîne vide insérée dans la clé étrangère organization_id lève une erreur d'intégrité SQL.
     * Criticité : CRITIQUE
     */
    it('devrait convertir organization_id vide en null lors de la création', function () {
        runAndRecord(
            'Sanitization & Unique ID Generation',
            'devrait convertir organization_id vide en null lors de la création',
            "Conversion d'organization_id vide en null",
            "Erreur d'intégrité SQL sur la clé étrangère organization_id",
            'CRITIQUE',
            function () {
                $data = [
                    'name' => 'Alice Martin',
                    'emails' => [['value' => 'alice@example.com', 'label' => 'work']],
                    'organization_id' => '', // Chaîne vide provenant du formulaire
                    'entity_type' => 'persons',
                    'user_id' => $this->admin->id,
                ];

                $person = $this->repository->create($data);

                expect($person->organization_id)->toBeNull();
            }
        );
    });

    /**
     * Règle métier protégée : Génération de l'unique_id composite (user_id|org_id|email)
     * Bug détecté si échec : Duplication physique de contacts due à un ID unique mal calculé.
     * Criticité : HAUTE
     */
    it('devrait générer unique_id correct à partir de user_id, organization_id et du premier email', function () {
        runAndRecord(
            'Sanitization & Unique ID Generation',
            'devrait générer unique_id correct à partir de user_id, organization_id et du premier email',
            "Génération de l'unique_id composite (user_id|org_id|email)",
            'Duplication physique de contacts due à un ID unique mal calculé',
            'HAUTE',
            function () {
                $org = Organization::create([
                    'name' => 'Test Org unique_id',
                    'user_id' => $this->admin->id,
                ]);

                $data = [
                    'name' => 'Bob Lapointe',
                    'emails' => [
                        ['value' => 'bob@example.com', 'label' => 'work'],
                        ['value' => 'bob.personal@example.com', 'label' => 'personal'],
                    ],
                    'organization_id' => $org->id,
                    'user_id' => $this->admin->id,
                    'entity_type' => 'persons',
                ];

                $person = $this->repository->create($data);

                $expectedUniqueId = "{$this->admin->id}|{$org->id}|bob@example.com";
                expect($person->unique_id)->toBe($expectedUniqueId);
            }
        );
    });

    /**
     * Règle métier protégée : Intégration du numéro de téléphone dans l'unique_id
     * Bug détecté si échec : Conflits de déduplication si deux contacts partagent le même email mais ont des téléphones différents.
     * Criticité : HAUTE
     */
    it('devrait ajouter le premier contact_number valide à l unique_id', function () {
        runAndRecord(
            'Sanitization & Unique ID Generation',
            'devrait ajouter le premier contact_number valide à l unique_id',
            "Intégration du numéro de téléphone dans l'unique_id",
            'Conflits de déduplication si deux contacts partagent le même email mais ont des téléphones différents',
            'HAUTE',
            function () {
                $data = [
                    'name' => 'Charlie Chaplin',
                    'emails' => [['value' => 'charlie@example.com', 'label' => 'work']],
                    'contact_numbers' => [
                        ['value' => '+33699999999', 'label' => 'mobile'],
                        ['value' => null, 'label' => 'empty'],
                    ],
                    'user_id' => $this->admin->id,
                    'entity_type' => 'persons',
                ];

                $person = $this->repository->create($data);

                expect($person->unique_id)->toContain('+33699999999');
            }
        );
    });

    /**
     * Règle métier protégée : Nettoyage des numéros de téléphone nuls (sanitize)
     * Bug détecté si échec : Stockage de valeurs nulles ou vides dans la structure JSON de contact_numbers.
     * Criticité : MOYENNE
     */
    it('devrait filtrer les contact_numbers dont la valeur est nulle', function () {
        runAndRecord(
            'Sanitization & Unique ID Generation',
            'devrait filtrer les contact_numbers dont la valeur est nulle',
            'Nettoyage des numéros de téléphone nuls (sanitize)',
            'Stockage de valeurs nulles ou vides dans la structure JSON de contact_numbers',
            'MOYENNE',
            function () {
                $data = [
                    'name' => 'David Bowie',
                    'emails' => [['value' => 'david@example.com', 'label' => 'work']],
                    'contact_numbers' => [
                        ['value' => '+33611111111', 'label' => 'mobile'],
                        ['value' => null, 'label' => 'empty'],
                    ],
                    'user_id' => $this->admin->id,
                    'entity_type' => 'persons',
                ];

                $person = $this->repository->create($data);

                expect(count($person->contact_numbers))->toBe(1);
                expect($person->contact_numbers[0]['value'])->toBe('+33611111111');
            }
        );
    });
});

// =====================================================================
// SCÉNARIOS : GESTION DES ORGANISATIONS (RÉUTILISATION VS CRÉATION)
// =====================================================================

describe('Organization Fetching & Creation', function () {

    /**
     * Règle métier protégée : Réutilisation d'une organisation existante par nom
     * Bug détecté si échec : Doublons d'organisations en base lors de l'enregistrement de contacts de la même entreprise.
     * Criticité : HAUTE
     */
    it('devrait réutiliser une organisation existante si elle est trouvée par son nom', function () {
        runAndRecord(
            'Organization Fetching & Creation',
            'devrait réutiliser une organisation existante si elle est trouvée par son nom',
            "Réutilisation d'une organisation existante par nom",
            "Doublons d'organisations en base lors de l'enregistrement de contacts de la même entreprise",
            'HAUTE',
            function () {
                $existingOrg = Organization::create([
                    'name' => 'Acme Corporation',
                    'user_id' => $this->admin->id,
                ]);

                $resolvedOrg = $this->repository->fetchOrCreateOrganizationByName('Acme Corporation');

                expect($resolvedOrg->id)->toBe($existingOrg->id);
                expect(Organization::count())->toBe(1);
            }
        );
    });

    /**
     * Règle métier protégée : Création d'une nouvelle organisation à la volée
     * Bug détecté si échec : Échec de la liaison avec l'organisation lors de l'ajout d'un contact d'une nouvelle entreprise.
     * Criticité : HAUTE
     */
    it('devrait créer une nouvelle organisation si elle n existe pas encore', function () {
        runAndRecord(
            'Organization Fetching & Creation',
            'devrait créer une nouvelle organisation si elle n existe pas encore',
            "Création d'une nouvelle organisation à la volée",
            "Échec de la liaison avec l'organisation lors de l'ajout d'un contact d'une nouvelle entreprise",
            'HAUTE',
            function () {
                $resolvedOrg = $this->repository->fetchOrCreateOrganizationByName('New Startup SAS');

                expect($resolvedOrg->name)->toBe('New Startup SAS');
                expect(Organization::count())->toBe(1);
            }
        );
    });
});

// =====================================================================
// SCÉNARIOS : STATISTIQUES & RAPPORTS
// =====================================================================

describe('Statistics and Reporting', function () {

    /**
     * Règle métier protégée : Comptabilisation correcte des nouveaux contacts dans un intervalle de temps
     * Bug détecté si échec : Indicateurs du tableau de bord erronés (statistiques clients fausses).
     * Criticité : MOYENNE
     */
    it('devrait renvoyer le nombre correct de personnes créées dans un intervalle de dates', function () {
        runAndRecord(
            'Statistics and Reporting',
            'devrait renvoyer le nombre correct de personnes créées dans un intervalle de dates',
            'Comptabilisation correcte des nouveaux contacts dans un intervalle de temps',
            'Indicateurs du tableau de bord erronés (statistiques clients fausses)',
            'MOYENNE',
            function () {
                $p1 = Person::create([
                    'name' => 'P1',
                    'emails' => [['value' => 'p1@test.com']],
                    'user_id' => $this->admin->id,
                    'unique_id' => '1',
                ]);
                DB::table('persons')->where('id', $p1->id)
                    ->update(['created_at' => '2026-06-01 10:00:00']);

                $p2 = Person::create([
                    'name' => 'P2',
                    'emails' => [['value' => 'p2@test.com']],
                    'user_id' => $this->admin->id,
                    'unique_id' => '2',
                ]);
                DB::table('persons')->where('id', $p2->id)
                    ->update(['created_at' => '2026-06-15 10:00:00']);

                $p3 = Person::create([
                    'name' => 'P3',
                    'emails' => [['value' => 'p3@test.com']],
                    'user_id' => $this->admin->id,
                    'unique_id' => '3',
                ]);
                DB::table('persons')->where('id', $p3->id)
                    ->update(['created_at' => '2026-07-05 10:00:00']);

                $count = $this->repository->getCustomerCount('2026-06-01', '2026-06-30');

                expect($count)->toBe(2);
            }
        );
    });
});
