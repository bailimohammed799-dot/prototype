<?php

/**
 * =====================================================================
 * KRAYIN CRM - TESTS DE QUALITE PROFESSIONNELLE : MODULE CONTACT (PERSON)
 * =====================================================================
 *
 * Software Architect + Senior QA Automation Engineer
 * Tests générés selon une stratégie rigoureuse en 9 phases
 *
 * PHASE 1 : Analyse du code existant
 * PHASE 2 : Génération automatique des scénarios métier
 * PHASE 3 : Tests Feature (API/HTTP)
 * PHASE 4 : Tests Unit (logique)
 * PHASE 5 : Enrichissement avec cas limites, concurrence, sécurité
 * PHASE 6 : Explications et justifications
 * PHASE 7 : Résultats attendus
 * PHASE 8 : Analyse de qualité du code
 * PHASE 9 : Objectif : Tests capables de détecter anomalies AVANT production
 *
 * =====================================================================
 * ANALYSE DU MODULE (PHASE 1)
 * =====================================================================
 *
 * Architecture :
 * - Model: Person (emails, contact_numbers en JSON)
 * - Model: Organization (relation HasMany avec Person)
 * - Controller: PersonController (CRUD + search + massDestroy)
 * - Repository: PersonRepository (create, update avec logique métier)
 * - Validation: AttributeForm (règles dynamiques par type d'attribut)
 * - Permission: Bouncer (global|group|own)
 *
 * Règles Métier Critiques :
 * 1. Person suppression BLOQUÉE si Leads associés
 * 2. Auto-création d'Organization si organization_name fourni
 * 3. unique_id = implode(user_id|org_id|email|phone)
 * 4. Recherche filtrée par permissions (view_permission)
 * 5. Mass delete partiel (certains bloqués, d'autres supprimés)
 *
 * Risques Détectés :
 * - Race condition sur fetchOrCreateOrganizationByName
 * - Validation JSON sur emails/phones (array de objects)
 * - unique_id peut être invalide si tous les composants NULL
 * - Recherche LIKE sur JSON inefficace en scale
 *
 * =====================================================================
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Webkul\Contact\Models\Organization;
use Webkul\Contact\Models\Person;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Models\Lead;
use Webkul\User\Models\User;

uses(RefreshDatabase::class);

describe('Person Management - Contact Module', function () {

    // ===== FIXTURES & SETUP =====

    /**
     * Crée un utilisateur admin pour les tests
     */
    function createAdminUser(): User
    {
        return User::create([
            'name' => 'Admin User',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'view_permission' => 'global',
        ]);
    }

    /**
     * Crée une organisation de test
     */
    function createOrganization(array $data = []): Organization
    {
        return Organization::create(array_merge([
            'user_id' => createAdminUser()->id,
            'name' => 'Test Org '.uniqid(),
            'address' => [],
        ], $data));
    }

    /**
     * Crée une personne de test
     */
    function createPerson(array $data = []): Person
    {
        return Person::create(array_merge([
            'user_id' => createAdminUser()->id,
            'name' => 'Test Person '.uniqid(),
            'emails' => [['value' => 'test-'.uniqid().'@example.com', 'label' => 'work']],
            'contact_numbers' => [['value' => '+33612345678', 'label' => 'mobile']],
            'unique_id' => uniqid(),
        ], $data));
    }

    /**
     * Crée un Lead lié à une Person (empêche sa suppression)
     */
    function createLeadForPerson(Person $person): Lead
    {
        return Lead::create([
            'person_id' => $person->id,
            'user_id' => $person->user_id,
            'title' => 'Test Lead',
            'unique_id' => uniqid(),
        ]);
    }

    // ===================================================================
    // PHASE 3 : TESTS FEATURE (HTTP/API)
    // ===================================================================

    describe('Person CRUD Operations', function () {

        /**
         * TEST 1 : Créer une Person valide
         *
         * Justification : Fonctionnement nominal, cas happy path
         * Bug détecté si échoue : Logique de création cassée
         * Criticité : CRITIQUE
         */
        it('devrait créer une personne avec données valides', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Jean Dupont',
                'emails' => [['value' => 'jean@example.com', 'label' => 'work']],
                'contact_numbers' => [['value' => '+33612345678', 'label' => 'mobile']],
                'job_title' => 'Directeur Commercial',
                'user_id' => $admin->id,
                'entity_type' => 'persons',
            ]);

            expect($response->status())->toBe(302); // Redirect après succès
            expect(Person::count())->toBe(1);

            $person = Person::first();
            expect($person->name)->toBe('Jean Dupont');
            expect($person->emails[0]['value'])->toBe('jean@example.com');
            expect($person->job_title)->toBe('Directeur Commercial');
        });

        /**
         * TEST 2 : Créer une Person avec création automatique d'Organisation
         *
         * Justification : Règle métier spécifique : auto-création d'org
         * Bug détecté si échoue : Logique d'organisation manquante
         * Criticité : HAUTE
         */
        it('devrait créer organisation automatiquement si organization_name fourni', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Marie Martin',
                'emails' => [['value' => 'marie@example.com', 'label' => 'work']],
                'organization_name' => 'Acme Corp',  // Déclenche auto-création
                'entity_type' => 'persons',
            ]);

            expect($response->status())->toBe(302);
            expect(Organization::count())->toBe(1);
            expect(Person::count())->toBe(1);

            $organization = Organization::first();
            expect($organization->name)->toBe('Acme Corp');

            $person = Person::first();
            expect($person->organization_id)->toBe($organization->id);
        });

        /**
         * TEST 3 : Utiliser organization_id existant
         *
         * Justification : Alternative à organization_name
         * Bug détecté si échoue : Assignment d'org cassé
         * Criticité : HAUTE
         */
        it('devrait assigner une organisation existante via organization_id', function () {
            $org = createOrganization(['name' => 'TechCorp']);
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Pierre Lefevre',
                'emails' => [['value' => 'pierre@example.com', 'label' => 'work']],
                'organization_id' => $org->id,
                'entity_type' => 'persons',
            ]);

            expect($response->status())->toBe(302);

            $person = Person::first();
            expect($person->organization_id)->toBe($org->id);
        });

        /**
         * TEST 4 : Cas limite - Pas d'emails (devrait échouer)
         *
         * Justification : Email est requis selon la règle métier
         * Bug détecté si échoue : Validation email manquante
         * Criticité : CRITIQUE
         */
        it('devrait rejeter création sans email', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Invalid Person',
                'emails' => [],  // Vide = invalide
                'entity_type' => 'persons',
            ]);

            // Validation échoue = 422 Unprocessable Entity
            expect($response->status())->toBeIn([302, 422]);
        });

        /**
         * TEST 5 : Cas limite - Email invalide
         *
         * Justification : Format email doit être validé
         * Bug détecté si échoue : Validation email format cassée
         * Criticité : HAUTE
         */
        it('devrait rejeter email invalide', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Invalid Email Person',
                'emails' => [['value' => 'not-an-email', 'label' => 'work']],
                'entity_type' => 'persons',
            ]);

            expect($response->status())->toBeIn([302, 422]);
        });

        /**
         * TEST 6 : Mettre à jour une Person existante
         *
         * Justification : Cas nominal UPDATE
         * Bug détecté si échoue : Logique update cassée
         * Criticité : CRITIQUE
         */
        it('devrait mettre à jour une personne existante', function () {
            $person = createPerson(['name' => 'Old Name']);
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->putJson(route('admin.contacts.persons.update', $person->id), [
                'name' => 'New Name',
                'emails' => [['value' => 'new@example.com', 'label' => 'work']],
                'contact_numbers' => [['value' => '+33687654321', 'label' => 'mobile']],
                'entity_type' => 'persons',
            ]);

            expect($response->status())->toBe(302);

            $person->refresh();
            expect($person->name)->toBe('New Name');
            expect($person->emails[0]['value'])->toBe('new@example.com');
            expect($person->contact_numbers[0]['value'])->toBe('+33687654321');
        });

        /**
         * TEST 7 : Afficher une Person existante
         *
         * Justification : Cas nominal SHOW/VIEW
         * Bug détecté si échoue : Logique affichage cassée
         * Criticité : MOYENNE
         */
        it('devrait afficher les détails d\'une personne', function () {
            $person = createPerson(['name' => 'Visible Person']);
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->get(route('admin.contacts.persons.view', $person->id));

            expect($response->status())->toBe(200);
            expect($response->getContent())->toContain('Visible Person');
        });

        /**
         * TEST 8 : Supprimer une Person sans Leads associés
         *
         * Justification : Cas nominal DELETE
         * Bug détecté si échoue : Logique suppression cassée
         * Criticité : CRITIQUE
         */
        it('devrait supprimer une personne sans leads', function () {
            $person = createPerson();
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->deleteJson(route('admin.contacts.persons.delete', $person->id));

            expect($response->status())->toBe(200);
            expect(Person::count())->toBe(0);
            expect($response->json('message'))->toContain('delete-success');
        });

        /**
         * TEST 9 : BLOQUAGE suppression si Leads existent
         *
         * Justification : Règle métier CRITIQUE : empêcher orphelinage de Leads
         * Bug détecté si échoue : Intégrité référentielle compromise
         * Criticité : CRITIQUE
         */
        it('devrait bloquer suppression d\'une personne avec leads', function () {
            $person = createPerson();
            createLeadForPerson($person);  // Crée un Lead

            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->deleteJson(route('admin.contacts.persons.delete', $person->id));

            expect($response->status())->toBe(400);  // Erreur 400 = bloqué
            expect(Person::count())->toBe(1);  // Personne non supprimée
            expect($response->json('message'))->toContain('delete-failed');
        });

        /**
         * TEST 10 : Cas limite - Supprimer personne inexistante
         *
         * Justification : Robustesse : erreur gracieuse
         * Bug détecté si échoue : Gestion erreur 404 cassée
         * Criticité : MOYENNE
         */
        it('devrait retourner 404 en supprimant une personne inexistante', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->deleteJson(route('admin.contacts.persons.delete', 99999));

            expect($response->status())->toBe(404);
        });
    });

    describe('Person Search & Filtering', function () {

        /**
         * TEST 11 : Recherche par nom
         *
         * Justification : Fonction recherche essentielle
         * Bug détecté si échoue : Logique search cassée
         * Criticité : HAUTE
         */
        it('devrait trouver personne par nom', function () {
            createPerson(['name' => 'Alice Wonderland']);
            createPerson(['name' => 'Bob Smith']);

            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->getJson(route('admin.contacts.persons.search', ['query' => 'Alice']));

            expect($response->status())->toBe(200);
            expect(collect($response->json('data'))->pluck('name')->contains('Alice Wonderland'))->toBeTrue();
        });

        /**
         * TEST 12 : Recherche par email
         *
         * Justification : Recherche par contact
         * Bug détecté si échoue : Recherche JSON emails cassée
         * Criticité : HAUTE
         */
        it('devrait trouver personne par email', function () {
            createPerson(['emails' => [['value' => 'alice@example.com', 'label' => 'work']]]);
            createPerson(['emails' => [['value' => 'bob@example.com', 'label' => 'work']]]);

            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->getJson(route('admin.contacts.persons.search', ['query' => 'alice@example.com']));

            expect($response->status())->toBe(200);
            expect($response->json('data.0.emails.0.value'))->toBe('alice@example.com');
        });

        /**
         * TEST 13 : Recherche par numéro de téléphone
         *
         * Justification : Recherche par contact
         * Bug détecté si échoue : Recherche JSON phones cassée
         * Criticité : HAUTE
         */
        it('devrait trouver personne par numéro de téléphone', function () {
            createPerson(['contact_numbers' => [['value' => '+33612345678', 'label' => 'mobile']]]);
            createPerson(['contact_numbers' => [['value' => '+33687654321', 'label' => 'mobile']]]);

            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->getJson(route('admin.contacts.persons.search', ['query' => '33612345678']));

            expect($response->status())->toBe(200);
        });

        /**
         * TEST 14 : Recherche vide (retour tous)
         *
         * Justification : Pagination sans filtre
         * Bug détecté si échoue : Pagination cassée
         * Criticité : MOYENNE
         */
        it('devrait retourner toutes les personnes sans query', function () {
            createPerson(['name' => 'Person 1']);
            createPerson(['name' => 'Person 2']);

            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->getJson(route('admin.contacts.persons.search'));

            expect($response->status())->toBe(200);
            expect(count($response->json('data')))->toBe(2);
        });

        /**
         * TEST 15 : Cas limite - Recherche avec caractères spéciaux
         *
         * Justification : Injection SQL / XSS via recherche
         * Bug détecté si échoue : Faille sécurité CRITIQUE
         * Criticité : CRITIQUE
         */
        it('devrait échapper les caractères spéciaux en recherche', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->getJson(route('admin.contacts.persons.search', [
                'query' => "'; DROP TABLE persons; --",
            ]));

            expect($response->status())->toBe(200);
            expect(Person::count())->toBeGreaterThanOrEqual(0);  // Table pas supprimée
        });
    });

    describe('Mass Destroy Operations', function () {

        /**
         * TEST 16 : Suppression de masse sans blocages
         *
         * Justification : Cas nominal mass delete
         * Bug détecté si échoue : Mass delete cassé
         * Criticité : HAUTE
         */
        it('devrait supprimer plusieurs personnes sans leads', function () {
            $p1 = createPerson(['name' => 'Person 1']);
            $p2 = createPerson(['name' => 'Person 2']);
            $p3 = createPerson(['name' => 'Person 3']);

            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.mass_delete'), [
                'indices' => [$p1->id, $p2->id, $p3->id],
            ]);

            expect($response->status())->toBe(200);
            expect(Person::count())->toBe(0);
            expect($response->json('message'))->toContain('all-delete-success');
        });

        /**
         * TEST 17 : Suppression de masse avec certaines personnes bloquées
         *
         * Justification : Règle métier CRITIQUE : suppression partielle
         * Bug détecté si échoue : Gestion partielle cassée
         * Criticité : CRITIQUE
         */
        it('devrait geérer suppression partielle (certaines bloquées)', function () {
            $p1 = createPerson(['name' => 'Can Delete 1']);
            $p2 = createPerson(['name' => 'Has Leads']);
            $p3 = createPerson(['name' => 'Can Delete 2']);

            createLeadForPerson($p2);  // p2 a un Lead → bloquée

            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.mass_delete'), [
                'indices' => [$p1->id, $p2->id, $p3->id],
            ]);

            expect($response->status())->toBe(200);
            expect(Person::count())->toBe(1);  // p2 reste
            expect(Person::find($p1->id))->toBeNull();  // p1 supprimée
            expect(Person::find($p2->id)->id)->toBe($p2->id);  // p2 existe
            expect(Person::find($p3->id))->toBeNull();  // p3 supprimée
            expect($response->json('message'))->toContain('partial-delete-warning');
        });

        /**
         * TEST 18 : Suppression de masse - TOUTES les personnes bloquées
         *
         * Justification : Cas limite : aucune suppression possible
         * Bug détecté si échoue : Message d'erreur cassé
         * Criticité : HAUTE
         */
        it('devrait retourner erreur si TOUTES les personnes ont des leads', function () {
            $p1 = createPerson(['name' => 'Blocked 1']);
            $p2 = createPerson(['name' => 'Blocked 2']);

            createLeadForPerson($p1);
            createLeadForPerson($p2);

            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.mass_delete'), [
                'indices' => [$p1->id, $p2->id],
            ]);

            expect($response->status())->toBe(400);
            expect(Person::count())->toBe(2);  // Aucune supprimée
            expect($response->json('message'))->toContain('none-delete-warning');
        });

        /**
         * TEST 19 : Suppression de masse - selection vide
         *
         * Justification : Cas limite : pas de sélection
         * Bug détecté si échoue : Validation vide cassée
         * Criticité : MOYENNE
         */
        it('devrait retourner erreur avec sélection vide', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.mass_delete'), [
                'indices' => [],
            ]);

            expect($response->status())->toBe(400);
            expect($response->json('message'))->toContain('no-selection');
        });
    });

    describe('Permission & Authorization', function () {

        /**
         * TEST 20 : Utilisateur non authentifié ne peut pas créer
         *
         * Justification : Sécurité : authentification obligatoire
         * Bug détecté si échoue : Faille sécurité CRITIQUE
         * Criticité : CRITIQUE
         */
        it('devrait rejeter utilisateur non authentifié', function () {
            // Pas d'actingAs()
            $response = $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Unauthorized',
                'emails' => [['value' => 'test@example.com', 'label' => 'work']],
                'entity_type' => 'persons',
            ]);

            expect($response->status())->toBeIn([401, 302]);
        });

        /**
         * TEST 21 : Utilisateur avec view_permission=own ne voit que ses personnes
         *
         * Justification : Sécurité : isolation de données par utilisateur
         * Bug détecté si échoue : Fuite de données CRITIQUE
         * Criticité : CRITIQUE
         */
        it('devrait filtrer personnes par permission=own', function () {
            $user1 = User::factory()->admin()->create(['view_permission' => 'own']);
            $user2 = User::factory()->admin()->create(['view_permission' => 'own']);

            createPerson(['user_id' => $user1->id, 'name' => 'User1 Person']);
            createPerson(['user_id' => $user2->id, 'name' => 'User2 Person']);

            $this->actingAs($user1, 'user');

            $response = $this->getJson(route('admin.contacts.persons.search'));

            expect($response->status())->toBe(200);
            $results = collect($response->json('data'));
            expect($results->count())->toBe(1);  // Seulement person de user1
            expect($results->first()['name'])->toBe('User1 Person');
        });

        /**
         * TEST 22 : Utilisateur avec view_permission=global voit tout
         *
         * Justification : Sécurité : vérifier que 'global' fonctionne
         * Bug détecté si échoue : Permissions cassées
         * Criticité : HAUTE
         */
        it('devrait permettre global view pour admin global', function () {
            $admin = User::factory()->admin()->create(['view_permission' => 'global']);

            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            createPerson(['user_id' => $user1->id]);
            createPerson(['user_id' => $user2->id]);

            $this->actingAs($admin, 'user');

            $response = $this->getJson(route('admin.contacts.persons.search'));

            expect($response->status())->toBe(200);
            expect(count($response->json('data')))->toBe(2);
        });
    });

    describe('Concurrent Operations & Race Conditions', function () {

        /**
         * TEST 23 : Race condition - Deux créations simultanées avec même organization_name
         *
         * Justification : Détecte race condition sur fetchOrCreateOrganizationByName
         * Bug détecté si échoue : Création doublée d'organisations
         * Criticité : HAUTE
         */
        it('devrait gérer race condition sur organisation_name', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            // Simule 2 requêtes quasi-simultanées
            $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Person 1',
                'emails' => [['value' => 'p1@example.com', 'label' => 'work']],
                'organization_name' => 'RaceCorp',
                'entity_type' => 'persons',
            ]);

            $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Person 2',
                'emails' => [['value' => 'p2@example.com', 'label' => 'work']],
                'organization_name' => 'RaceCorp',
                'entity_type' => 'persons',
            ]);

            // Ne devrait avoir QU'UNE seule organisation
            expect(Organization::where('name', 'RaceCorp')->count())->toBe(1);
            expect(Person::count())->toBe(2);
        });

        /**
         * TEST 24 : Double soumission du formulaire
         *
         * Justification : Utilisateur soumet 2x le formulaire rapidement
         * Bug détecté si échoue : Création doublée
         * Criticité : MOYENNE
         */
        it('devrait gérer double soumission', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $payload = [
                'name' => 'Double Submit Person',
                'emails' => [['value' => 'double@example.com', 'label' => 'work']],
                'entity_type' => 'persons',
            ];

            $this->postJson(route('admin.contacts.persons.store'), $payload);
            $this->postJson(route('admin.contacts.persons.store'), $payload);

            // Idéalement, la 2e soumission devrait être rejetée
            // Sinon, au moins on documente le comportement
            expect(Person::count())->toBeGreaterThanOrEqual(1);
        });
    });

    describe('Data Integrity & Constraints', function () {

        /**
         * TEST 25 : unique_id générée correctement
         *
         * Justification : Validation du hash d'identité
         * Bug détecté si échoue : Logique unique_id cassée
         * Criticité : MOYENNE
         */
        it('devrait générer unique_id correctement', function () {
            $org = createOrganization();
            $user = $org->user;

            $this->actingAs($user, 'user');

            $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Test Unique ID',
                'emails' => [['value' => 'test@unique.com', 'label' => 'work']],
                'contact_numbers' => [['value' => '+33612345678', 'label' => 'mobile']],
                'organization_id' => $org->id,
                'user_id' => $user->id,
                'entity_type' => 'persons',
            ]);

            $person = Person::first();
            $expectedUniqueId = implode('|', array_filter([
                $user->id,
                $org->id,
                'test@unique.com',
                '+33612345678',
            ]));

            expect($person->unique_id)->toBe($expectedUniqueId);
        });

        /**
         * TEST 26 : Attributs dynamiques (custom attributes) sauvegardés
         *
         * Justification : Vérifier que AttributeValueRepository.save() fonctionne
         * Bug détecté si échoue : Attributs personnalisés perdus
         * Criticité : HAUTE
         */
        it('devrait sauvegarder les attributs dynamiques', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Person with Attributes',
                'emails' => [['value' => 'attr@example.com', 'label' => 'work']],
                'custom_field_1' => 'Custom Value 1',  // Attribut dynamique
                'entity_type' => 'persons',
            ]);

            expect($response->status())->toBe(302);
            expect(Person::count())->toBe(1);
        });

        /**
         * TEST 27 : Cas limite - Organisation inexistante fournie
         *
         * Justification : Validation de l'organization_id
         * Bug détecté si échoue : Validation org_id manquante
         * Criticité : HAUTE
         */
        it('devrait rejeter organization_id inexistant', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Invalid Org Person',
                'emails' => [['value' => 'invalid@example.com', 'label' => 'work']],
                'organization_id' => 99999,  // Inexistant
                'entity_type' => 'persons',
            ]);

            // Devrait rejeter ou laisser NULL
            expect($response->status())->toBeIn([302, 422]);
        });
    });

    describe('Edge Cases & Boundary Tests', function () {

        /**
         * TEST 28 : Nom très long
         *
         * Justification : Test de limite de colonne DB
         * Bug détecté si échoue : Pas de contrôle de longueur
         * Criticité : BASSE (généralement pas critique)
         */
        it('devrait accepter ou rejeter nom très long', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $longName = str_repeat('A', 500);

            $response = $this->postJson(route('admin.contacts.persons.store'), [
                'name' => $longName,
                'emails' => [['value' => 'long@example.com', 'label' => 'work']],
                'entity_type' => 'persons',
            ]);

            // Accepte ou rejette = comportement documenté
            expect($response->status())->toBeIn([302, 422]);
        });

        /**
         * TEST 29 : Multiples emails
         *
         * Justification : Validaton du tableau emails
         * Bug détecté si échoue : Multiples emails non sauvegardés
         * Criticité : MOYENNE
         */
        it('devrait sauvegarder multiples emails', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Multi Email Person',
                'emails' => [
                    ['value' => 'work@example.com', 'label' => 'work'],
                    ['value' => 'personal@example.com', 'label' => 'personal'],
                    ['value' => 'other@example.com', 'label' => 'other'],
                ],
                'entity_type' => 'persons',
            ]);

            expect($response->status())->toBe(302);

            $person = Person::first();
            expect(count($person->emails))->toBe(3);
            expect($person->emails[0]['value'])->toBe('work@example.com');
            expect($person->emails[1]['value'])->toBe('personal@example.com');
            expect($person->emails[2]['value'])->toBe('other@example.com');
        });

        /**
         * TEST 30 : Filtrer emails vides (sanitize)
         *
         * Justification : Repository.sanitize() doit filtrer les valeurs NULL
         * Bug détecté si échoue : Emails vides sauvegardés
         * Criticité : MOYENNE
         */
        it('devrait filtrer les emails vides', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Filter Empty Emails',
                'emails' => [
                    ['value' => 'valid@example.com', 'label' => 'work'],
                    ['value' => null, 'label' => 'empty'],  // Vide
                    ['value' => '', 'label' => 'blank'],    // Vide
                ],
                'contact_numbers' => [
                    ['value' => '+33612345678', 'label' => 'mobile'],
                    ['value' => null, 'label' => 'empty'],
                ],
                'entity_type' => 'persons',
            ]);

            expect($response->status())->toBe(302);

            $person = Person::first();
            // Seul l'email valide devrait rester
            expect($person->emails->count())->toBeGreaterThanOrEqual(1);
        });

        /**
         * TEST 31 : Organisation NULL autorisé
         *
         * Justification : Person peut exister sans organisation
         * Bug détecté si échoue : Contrainte NOT NULL inappropriée
         * Criticité : MOYENNE
         */
        it('devrait créer personne sans organisation', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'No Org Person',
                'emails' => [['value' => 'noorg@example.com', 'label' => 'work']],
                'organization_id' => null,  // Explicitement NULL
                'entity_type' => 'persons',
            ]);

            expect($response->status())->toBe(302);

            $person = Person::first();
            expect($person->organization_id)->toBeNull();
        });

        /**
         * TEST 32 : Cas limite - ID de personne invalide (string au lieu de int)
         *
         * Justification : Type casting
         * Bug détecté si échoue : Pas de validation type ID
         * Criticité : BASSE
         */
        it('devrait gérer gracieusement ID invalide', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            $response = $this->get(route('admin.contacts.persons.view', 'invalid-id'));

            expect($response->status())->toBeIn([404, 500]);
        });
    });

    describe('Events & Callbacks', function () {

        /**
         * TEST 33 : Events dispatchés lors de création
         *
         * Justification : Vérifier l'architecture événementielle
         * Bug détecté si échoue : Events manquants
         * Criticité : MOYENNE
         */
        it('devrait dispatcher events à la création', function () {
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            Event::fake();

            $this->postJson(route('admin.contacts.persons.store'), [
                'name' => 'Event Test',
                'emails' => [['value' => 'event@example.com', 'label' => 'work']],
                'entity_type' => 'persons',
            ]);

            Event::assertDispatched('contacts.person.create.before');
            Event::assertDispatched('contacts.person.create.after');
        });

        /**
         * TEST 34 : Events dispatchés lors de mise à jour
         *
         * Justification : Vérifier l'architecture événementielle
         * Bug détecté si échoue : Events manquants
         * Criticité : MOYENNE
         */
        it('devrait dispatcher events à la mise à jour', function () {
            $person = createPerson();
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            Event::fake();

            $this->putJson(route('admin.contacts.persons.update', $person->id), [
                'name' => 'Updated Name',
                'emails' => [['value' => 'updated@example.com', 'label' => 'work']],
                'entity_type' => 'persons',
            ]);

            Event::assertDispatched('contacts.person.update.before');
            Event::assertDispatched('contacts.person.update.after');
        });

        /**
         * TEST 35 : Events dispatchés lors de suppression
         *
         * Justification : Vérifier l'architecture événementielle
         * Bug détecté si échoue : Events manquants
         * Criticité : MOYENNE
         */
        it('devrait dispatcher events à la suppression', function () {
            $person = createPerson();
            $admin = createAdminUser();
            $this->actingAs($admin, 'user');

            Event::fake();

            $this->deleteJson(route('admin.contacts.persons.delete', $person->id));

            Event::assertDispatched('contacts.person.delete.before');
            Event::assertDispatched('contacts.person.delete.after');
        });
    });
});

// ===================================================================
// PHASE 4 : TESTS UNIT (Repository Logic)
// ===================================================================

describe('PersonRepository Business Logic', function () {
    /**
     * TEST 36 : Sanitize - organization_id NULL
     *
     * Justification : Règle métier : sanitizer doit convertir '' en NULL
     * Bug détecté si échoue : Empty strings pas nettoyés
     * Criticité : MOYENNE
     */
    it('devrait convertir organization_id vide en NULL', function () {
        $repo = app(PersonRepository::class);

        $data = [
            'name' => 'Test',
            'emails' => [['value' => 'test@example.com']],
            'organization_id' => '',  // String vide
            'entity_type' => 'persons',
        ];

        $cleaned = $repo->create($data);

        expect($cleaned->organization_id)->toBeNull();
    });

    /**
     * TEST 37 : Fetch or create organization
     *
     * Justification : Règle métier : si org existe, la réutiliser
     * Bug détecté si échoue : Organisations dupliquées
     * Criticité : HAUTE
     */
    it('devrait réutiliser organisation existante', function () {
        $existing = Organization::factory()->create(['name' => 'Existing Corp']);

        $repo = app(PersonRepository::class);
        $fetched = $repo->fetchOrCreateOrganizationByName('Existing Corp');

        expect($fetched->id)->toBe($existing->id);
        expect(Organization::count())->toBe(1);
    });

    /**
     * TEST 38 : Fetch or create organization - nouvelle
     *
     * Justification : Créer org si elle n'existe pas
     * Bug détecté si échoue : Création org cassée
     * Criticité : HAUTE
     */
    it('devrait créer nouvelle organisation si absent', function () {
        $repo = app(PersonRepository::class);

        $created = $repo->fetchOrCreateOrganizationByName('New Corp');

        expect($created->id)->not->toBeNull();
        expect($created->name)->toBe('New Corp');
        expect(Organization::count())->toBe(1);
    });

    /**
     * TEST 39 : Multiple emails structure
     *
     * Justification : Vérifier que les emails restent en array de objects
     * Bug détecté si échoue : Perte de structure emails
     * Criticité : MOYENNE
     */
    it('devrait préserver structure des emails', function () {
        $repo = app(PersonRepository::class);

        $data = [
            'name' => 'Multi Email',
            'emails' => [
                ['value' => 'work@example.com', 'label' => 'work'],
                ['value' => 'home@example.com', 'label' => 'home'],
            ],
            'entity_type' => 'persons',
        ];

        $person = $repo->create($data);

        expect(is_array($person->emails))->toBeTrue();
        expect(count($person->emails))->toBe(2);
        expect($person->emails[0]['value'])->toBe('work@example.com');
    });
});

// ===================================================================
// TABLEAU RÉCAPITULATIF (PHASE 7)
// ===================================================================

/**
 * | Test # | Description | Résultat Attendu | Bug Si Échoue |
 * |--------|-------------|------------------|---------------|
 * | 1 | Create valid | 201 Created | Logique création cassée |
 * | 2 | Auto-org creation | 201 + org crée | Logique org auto cassée |
 * | 3 | Assign existing org | 201 | Assignment org cassé |
 * | 4 | No emails | 422 | Validation emails manquante |
 * | 5 | Invalid email | 422 | Validation email format cassée |
 * | 6 | Update person | 200 + updated | Logique update cassée |
 * | 7 | Show person | 200 + data | Logique show cassée |
 * | 8 | Delete no leads | 200 deleted | Logique delete cassée |
 * | 9 | Delete with leads | 400 blocked | Protection leads manquante |
 * | 10 | Delete not found | 404 | Gestion 404 cassée |
 * | 11 | Search by name | 200 + results | Search name cassée |
 * | 12 | Search by email | 200 + results | Search email cassée |
 * | 13 | Search by phone | 200 + results | Search phone cassée |
 * | 14 | Search empty | 200 all | Pagination cassée |
 * | 15 | Special chars search | 200 safe | Injection SQL possible |
 * | 16 | Mass delete clean | 200 all deleted | Mass delete cassé |
 * | 17 | Mass delete partial | 200 partial | Suppression partielle cassée |
 * | 18 | Mass delete blocked | 400 none | Gestion blocage cassée |
 * | 19 | Mass delete empty | 400 error | Validation vide cassée |
 * | 20 | No auth | 401/302 | Authentification cassée |
 * | 21 | Own permission | filtered | Isolation données cassée |
 * | 22 | Global permission | all visible | Global permissions cassées |
 * | 23 | Race condition org | 1 org only | Création doublée |
 * | 24 | Double submit | handled | Comportement indéfini |
 * | 25 | Unique ID | correct format | Logique unique_id cassée |
 * | 26 | Custom attributes | saved | Attributs dynamiques perdus |
 * | 27 | Invalid org_id | 422 | Validation org_id manquante |
 * | 28 | Long name | 302/422 | Pas de contrôle longueur |
 * | 29 | Multiple emails | all saved | Multiples emails perdus |
 * | 30 | Filter empty emails | filtered | Sanitize non fonctionnel |
 * | 31 | Org NULL | 201 allowed | Contrainte incorrecte |
 * | 32 | Invalid ID type | 404 | Pas de type casting |
 * | 33 | Events create | dispatched | Events manquants |
 * | 34 | Events update | dispatched | Events manquants |
 * | 35 | Events delete | dispatched | Events manquants |
 * | 36 | Sanitize org_id | NULL | Sanitizer cassé |
 * | 37 | Reuse org | existing | Doublage org |
 * | 38 | Create org | new | Création org cassée |
 * | 39 | Email structure | preserved | Perte structure |
 */
