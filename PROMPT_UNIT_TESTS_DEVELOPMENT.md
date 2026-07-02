# 📝 PROMPT: DÉVELOPPEMENT DES TESTS UNITAIRES
## Module Contact - Krayin CRM

**Contexte:** Suite de tests feature réussie (30 tests ✅). Étape suivante: Tests unitaires.  
**Framework:** Pest v3, Laravel 12, PHP 8.3  
**Methodologie:** TDD - Tests d'abord, code après  

---

## 🎯 OBJECTIF GLOBAL

Développer une suite complète de tests unitaires pour le module Contact (Person) suivant les meilleures pratiques:
- ✅ Tests isolés et rapides (< 100ms par test)
- ✅ Mocking des dépendances externes
- ✅ Couverture 100% des méthodes publiques
- ✅ Tests de business logic
- ✅ Tests des validations et erreurs

---

## 📋 STRUCTURE DES TESTS UNITAIRES À CRÉER

### 1. Tests du Modèle Person
**Fichier:** `tests/Unit/Contact/PersonModelTest.php`

#### Objectifs:
- Valider les relationships (organization, user, leads, activities, tags)
- Tester les casts JSON (emails, contact_numbers)
- Valider les scopes (si présents)
- Tester les accessors/mutators
- Tester les événements (boot())

#### Tests à implémenter:
```php
describe('Person Model', function () {
    // RELATIONSHIPS
    it('belongs to user')
    it('belongs to organization')
    it('has many leads')
    it('has many through activities')
    it('belongs to many tags')
    
    // JSON CASTING
    it('casts emails to array')
    it('casts contact_numbers to array')
    it('preserves empty array in emails')
    
    // ATTRIBUTES
    it('generates unique_id on creation')
    it('stores fillable attributes correctly')
    
    // SCOPES (si présents)
    it('filters by organization scope')
    it('filters by user scope')
    
    // MUTATIONS
    it('normalizes emails format')
    it('normalizes phone numbers')
    
    // EVENTS
    it('fires events on create')
    it('fires events on update')
    it('fires events on delete')
});
```

---

### 2. Tests du Modèle Organization
**Fichier:** `tests/Unit/Contact/OrganizationModelTest.php`

#### Objectifs:
- Valider les relationships
- Tester les casts JSON (address)
- Tester la création et la mise à jour

#### Tests à implémenter:
```php
describe('Organization Model', function () {
    // RELATIONSHIPS
    it('has many persons')
    it('belongs to user')
    
    // JSON CASTING
    it('casts address to array')
    it('stores address components correctly')
    
    // ATTRIBUTES
    it('stores name correctly')
    it('stores user_id correctly')
    
    // PERSISTENCE
    it('creates new organization successfully')
    it('updates organization successfully')
});
```

---

### 3. Tests du Repository PersonRepository
**Fichier:** `tests/Unit/Contact/PersonRepositoryTest.php`

#### Objectifs:
- Tester chaque méthode publique en isolation
- Tester avec mocks des dépendances
- Valider la logique métier
- Tester les cas d'erreur

#### Tests à implémenter:
```php
describe('PersonRepository', function () {
    // CREATE
    it('creates person with valid data')
    it('sanitizes data before creation')
    it('generates unique_id correctly')
    it('auto-creates organization if name provided')
    it('reuses existing organization')
    it('throws exception on invalid data')
    
    // READ
    it('finds person by id')
    it('returns null for nonexistent person')
    it('loads relationships with person')
    
    // UPDATE
    it('updates person attributes')
    it('updates emails correctly')
    it('updates contact numbers')
    it('preserves unique_id on update')
    
    // DELETE
    it('deletes person successfully')
    it('fires delete events')
    
    // SEARCH
    it('searches by name')
    it('searches by email')
    it('searches by phone')
    it('filters by organization')
    it('applies permission filters')
    
    // DATA SANITIZATION
    it('sanitizes organization_name')
    it('removes empty emails')
    it('removes empty contact numbers')
    it('handles null values correctly')
    
    // ORGANIZATION MANAGEMENT
    it('creates organization if not exists')
    it('reuses organization with same name')
    it('handles race condition in org creation')
});
```

---

### 4. Tests du Controller PersonController
**Fichier:** `tests/Unit/Contact/PersonControllerTest.php`

#### Objectifs:
- Tester les méthodes du controller en isolation
- Mocker le repository et les dépendances
- Valider les réponses HTTP
- Tester la validation des données

#### Tests à implémenter:
```php
describe('PersonController', function () {
    // STORE
    it('calls repository create on store')
    it('validates incoming data')
    it('dispatches create events')
    it('returns correct response status')
    
    // SHOW
    it('retrieves person by id')
    it('applies permission checks')
    it('returns 404 for unauthorized access')
    
    // UPDATE
    it('calls repository update')
    it('validates update data')
    it('applies authorization checks')
    
    // DELETE
    it('checks if person can be deleted')
    it('blocks deletion if leads exist')
    it('dispatches delete events')
    it('returns correct status codes')
    
    // SEARCH
    it('calls repository search')
    it('applies filters')
    it('paginates results')
    it('applies permission filters')
    
    // MASS DELETE
    it('handles multiple deletions')
    it('blocks deletion for persons with leads')
    it('returns correct message for partial deletion')
    it('handles empty selection')
});
```

---

### 5. Tests de Validation (AttributeForm)
**Fichier:** `tests/Unit/Contact/PersonValidationTest.php`

#### Objectifs:
- Tester chaque règle de validation
- Tester les messages d'erreur
- Tester les validations conditionnelles

#### Tests à implémenter:
```php
describe('Person Validation', function () {
    // EMAIL VALIDATION
    it('validates email format')
    it('rejects invalid email')
    it('requires at least one email')
    it('allows multiple emails')
    
    // PHONE VALIDATION
    it('validates phone format')
    it('accepts various phone formats')
    
    // NAME VALIDATION
    it('requires person name')
    it('validates name length')
    
    // ORGANIZATION VALIDATION
    it('validates organization_id exists')
    it('allows null organization_id')
    it('rejects invalid organization_id')
    
    // CONDITIONAL VALIDATION
    it('validates custom attributes by type')
    it('validates attributes for entity_type')
    
    // ERROR MESSAGES
    it('returns correct validation messages')
    it('returns all validation errors together')
});
```

---

### 6. Tests des Services (À créer)
**Fichier:** `tests/Unit/Contact/PersonDeletionServiceTest.php`

#### Objectifs:
- Tester la logique de suppression
- Tester les conditions de blocage
- Tester les événements

#### Tests à implémenter:
```php
describe('PersonDeletionService', function () {
    // DELETION LOGIC
    it('can delete person without leads')
    it('cannot delete person with leads')
    it('provides block reason')
    it('fires delete events')
    
    // VALIDATION
    it('validates person exists before deletion')
    it('validates authorization')
});
```

---

## 📊 TABLEAU RÉCAPITULATIF

| Classe | Fichier de Test | Tests | Status |
|--------|-----------------|-------|--------|
| Person | PersonModelTest.php | 8-10 | À créer |
| Organization | OrganizationModelTest.php | 6-8 | À créer |
| PersonRepository | PersonRepositoryTest.php | 20-25 | À créer |
| PersonController | PersonControllerTest.php | 15-20 | À créer |
| Validation | PersonValidationTest.php | 10-12 | À créer |
| PersonDeletionService | PersonDeletionServiceTest.php | 5-7 | À créer |
| **TOTAL** | | **64-82 tests** | **~10 heures** |

---

## 🔧 TEMPLATE DE BASE POUR CHAQUE FICHIER

```php
<?php

namespace Tests\Unit\Contact;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Webkul\Contact\Models\Person;
use Webkul\Contact\Models\Organization;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\User\Models\User;
use Webkul\Lead\Models\Lead;
use Mockery;

uses(RefreshDatabase::class);

describe('Person Model', function () {
    
    beforeEach(function () {
        // Setup minimal data needed
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
        ]);
        
        $this->organization = Organization::create([
            'name' => 'Test Org',
            'user_id' => $this->admin->id,
        ]);
    });
    
    it('belongs to user', function () {
        $person = Person::create([
            'name' => 'John Doe',
            'emails' => [['value' => 'john@example.com', 'label' => 'work']],
            'user_id' => $this->admin->id,
            'unique_id' => uniqid(),
        ]);
        
        expect($person->user)->toBeInstanceOf(User::class);
        expect($person->user_id)->toBe($this->admin->id);
    });
    
    // ... more tests
});
```

---

## 🎯 CRITÈRES D'ACCEPTATION

### Pour chaque test unitaire:
- [ ] Teste une seule responsabilité
- [ ] Utilise mocks pour les dépendances externes
- [ ] Exécution < 100ms
- [ ] Nom de test descriptif
- [ ] Pas de dépendances avec d'autres tests
- [ ] Setup minimal et isolé
- [ ] Assertions claires et spécifiques
- [ ] Gère les cas d'erreur

### Pour la suite complète:
- [ ] Tous les tests passent
- [ ] Couverture 100% des méthodes publiques
- [ ] Pas de tests flaky
- [ ] Exécution totale < 60 secondes
- [ ] Documentation dans les commentaires
- [ ] Pas de code en dur (magic numbers)

---

## 📈 MÉTRIQUES À ATTEINDRE

```
Nombre de tests:        64-82 tests unitaires
Couverture totale:      > 95% des méthodes publiques
Temps d'exécution:      < 60 secondes
Tests passant:          100%
Flaky tests:            0%
Couverture de branche:  > 80%
Densité d'assertions:   3-5 par test
```

---

## 🏃 ORDRE DE DÉVELOPPEMENT (RECOMMANDÉ)

### Jour 1: Modèles (2 heures)
1. PersonModelTest.php (10 tests)
2. OrganizationModelTest.php (8 tests)

### Jour 2: Repository (3 heures)
3. PersonRepositoryTest.php (25 tests)

### Jour 3: Controller + Validation (3 heures)
4. PersonControllerTest.php (20 tests)
5. PersonValidationTest.php (12 tests)

### Jour 4: Services + Refactoring (2 heures)
6. PersonDeletionServiceTest.php (7 tests)
7. Refactoring du code testé si nécessaire

---

## 🛠️ COMMANDES UTILES

```bash
# Exécuter TOUS les tests
php artisan test

# Exécuter tests unitaires uniquement
php artisan test tests/Unit/

# Exécuter avec couverture
php artisan test --coverage tests/Unit/

# Exécuter tests spécifiques
php artisan test tests/Unit/Contact/PersonModelTest.php

# Watch mode (auto-reload)
php artisan test --watch tests/Unit/

# Tests spécifiques avec pattern
php artisan test --filter="PersonModel" tests/Unit/
```

---

## 📚 RESSOURCES & PATTERNS

### Mocking avec Mockery
```php
// Mock du Repository
$repositoryMock = Mockery::mock(PersonRepository::class);
$repositoryMock->shouldReceive('create')
    ->with(['name' => 'John'])
    ->once()
    ->andReturn($person);

$controller = new PersonController($repositoryMock);
```

### Testing Relationships
```php
it('has many leads', function () {
    $person = Person::create([...]);
    Lead::create(['person_id' => $person->id, ...]);
    Lead::create(['person_id' => $person->id, ...]);
    
    expect($person->leads)->toHaveCount(2);
    expect($person->leads->first())->toBeInstanceOf(Lead::class);
});
```

### Testing Scopes
```php
it('filters by organization scope', function () {
    $org1 = Organization::create(['name' => 'Org 1', ...]);
    $org2 = Organization::create(['name' => 'Org 2', ...]);
    
    Person::create(['name' => 'P1', 'organization_id' => $org1->id, ...]);
    Person::create(['name' => 'P2', 'organization_id' => $org2->id, ...]);
    
    $results = Person::whereOrganization($org1->id)->get();
    expect($results)->toHaveCount(1);
});
```

### Testing Events
```php
it('fires events on create', function () {
    Event::fake();
    
    Person::create([...]);
    
    Event::assertDispatched('contact.person.create.before');
    Event::assertDispatched('contact.person.create.after');
});
```

---

## ⚠️ PIÈGES À ÉVITER

### ❌ À NE PAS FAIRE:

1. **Tests trop accouplés:**
```php
// ❌ MAUVAIS
it('creates and searches person', function () {
    $person = $repo->create([...]);
    $results = $repo->search('John');
    expect($results)->toContain($person);
});

// ✅ BON
it('creates person', function () {
    $person = $repo->create([...]);
    expect($person)->toBeInstanceOf(Person::class);
});

it('searches person by name', function () {
    Person::create(['name' => 'John', ...]);
    $results = $repo->search('John');
    expect($results)->toHaveCount(1);
});
```

2. **Pas de mocking des dépendances:**
```php
// ❌ MAUVAIS - Accès réel à la base de données
$repo = new PersonRepository();

// ✅ BON - Mock des dépendances
$mockAttributeRepo = Mockery::mock(AttributeRepository::class);
$repo = new PersonRepository($mockAttributeRepo);
```

3. **Tests lents:**
```php
// ❌ MAUVAIS - Utilise RefreshDatabase pour chaque test unitaire
uses(RefreshDatabase::class);

// ✅ BON - Utilise setup/teardown minimal
beforeEach(function () {
    // Réinitialiser les mocks seulement
});
```

4. **Assertions vagues:**
```php
// ❌ MAUVAIS
expect($response)->toBeTruthy();

// ✅ BON
expect($response->status())->toBe(200);
expect($response->json('data'))->toHaveCount(5);
```

---

## 📝 CHECKLIST AVANT LIVRAISON

### Pour chaque fichier de test:
- [ ] Tous les tests ont noms descriptifs
- [ ] Chaque test teste UNE chose
- [ ] Tous les tests passent
- [ ] Aucun test n'a d'effet de bord
- [ ] Les mocks sont correctement configurés
- [ ] Les données de test sont minimalistes
- [ ] Les commentaires expliquent le contexte
- [ ] Pas de code en dur (magic numbers)

### Pour la suite complète:
- [ ] Exécution rapide (< 60s)
- [ ] Pas de tests flaky
- [ ] Coverage > 95%
- [ ] Documentation mise à jour
- [ ] CI/CD intégration testée
- [ ] Code review effectuée

---

## 🎓 EXEMPLE COMPLET - COMMENCER PAR CELUI-CI

```php
<?php

namespace Tests\Unit\Contact;

use Webkul\Contact\Models\Person;
use Webkul\Contact\Models\Organization;
use Webkul\User\Models\User;

describe('Person Model - Unit Tests', function () {
    
    beforeEach(function () {
        // Créer un utilisateur pour les tests
        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-unit-test@example.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
        ]);
        
        // Créer une organisation pour les tests
        $this->org = Organization::create([
            'name' => 'Test Organization',
            'user_id' => $this->admin->id,
        ]);
    });
    
    describe('Relationships', function () {
        
        it('belongs to user', function () {
            $person = Person::create([
                'name' => 'John Doe',
                'emails' => [['value' => 'john@example.com', 'label' => 'work']],
                'user_id' => $this->admin->id,
                'organization_id' => $this->org->id,
                'unique_id' => uniqid(),
            ]);
            
            expect($person->user_id)->toBe($this->admin->id);
            expect($person->user)->toBeInstanceOf(User::class);
            expect($person->user->name)->toBe('Admin Test');
        });
        
        it('belongs to organization', function () {
            $person = Person::create([
                'name' => 'Jane Doe',
                'emails' => [['value' => 'jane@example.com', 'label' => 'work']],
                'user_id' => $this->admin->id,
                'organization_id' => $this->org->id,
                'unique_id' => uniqid(),
            ]);
            
            expect($person->organization_id)->toBe($this->org->id);
            expect($person->organization)->toBeInstanceOf(Organization::class);
            expect($person->organization->name)->toBe('Test Organization');
        });
    });
    
    describe('Attributes & Casting', function () {
        
        it('casts emails to array', function () {
            $emails = [
                ['value' => 'personal@example.com', 'label' => 'personal'],
                ['value' => 'work@example.com', 'label' => 'work'],
            ];
            
            $person = Person::create([
                'name' => 'Test Person',
                'emails' => $emails,
                'user_id' => $this->admin->id,
                'unique_id' => uniqid(),
            ]);
            
            // Recharger depuis la DB pour tester le cast
            $person = $person->fresh();
            
            expect($person->emails)->toBeArray();
            expect($person->emails)->toHaveCount(2);
            expect($person->emails[0]['value'])->toBe('personal@example.com');
        });
        
        it('casts contact_numbers to array', function () {
            $phones = [
                ['value' => '+33612345678', 'label' => 'mobile'],
                ['value' => '+33123456789', 'label' => 'work'],
            ];
            
            $person = Person::create([
                'name' => 'Phone Person',
                'emails' => [['value' => 'phone@example.com', 'label' => 'work']],
                'contact_numbers' => $phones,
                'user_id' => $this->admin->id,
                'unique_id' => uniqid(),
            ]);
            
            $person = $person->fresh();
            
            expect($person->contact_numbers)->toBeArray();
            expect($person->contact_numbers)->toHaveCount(2);
            expect($person->contact_numbers[0]['value'])->toBe('+33612345678');
        });
    });
});
```

---

## 📞 SUPPORT & QUESTIONS

### Avant de commencer:
1. Lisez TEST_RESULTS_PERSON_MODULE.md pour comprendre les tests feature
2. Comprenez les 6 règles métier critiques
3. Revoyez la structure du code dans PersonRepository.php

### Pendant le développement:
1. Exécutez régulièrement les tests (commande ci-dessus)
2. Utilisez watch mode pour feedback immédiat
3. Consultez les patterns Pest v3

### Questions fréquentes:
- **Q:** Tests trop lents? **R:** Réduisez RefreshDatabase usage, utilisez des mocks
- **Q:** Tests flaky? **R:** Isolez mieux les dépendances, évitez l'état partagé
- **Q:** Comment tester les événements? **R:** Utilisez Event::fake() et Event::assertDispatched()

---

## ✅ RÉSUMÉ

**Objectif:** 64-82 tests unitaires de haute qualité  
**Effort estimé:** 10 heures  
**Coverage cible:** > 95%  
**Performance:** < 60 secondes  
**Status:** À commencer immédiatement  

**Prochaine étape:** Créer tests/Unit/Contact/PersonModelTest.php

🚀 **Bon développement des tests unitaires!**
