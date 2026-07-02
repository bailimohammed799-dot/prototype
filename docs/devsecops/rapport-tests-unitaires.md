# Rapport des tests unitaires et fonctionnels

## Identification

- Stagiaire: Mohamed Habib Baili
- Encadrant: Baylassen
- Entreprise: L2T
- Projet: Krayin Laravel CRM
- Date: 2026-06-28

## Objectif

Ce rapport présente l'état des tests automatisés du projet. Les tests sont écrits avec Pest PHP, framework de test utilisé par Laravel.

## Configuration

- Framework: Pest PHP 3
- Base: Laravel TestCase
- Fichier de configuration: `phpunit.xml`
- Dossier des tests unitaires: `tests/Unit`
- Dossier des tests fonctionnels: `tests/Feature`

## Tests existants

| Fichier | Type | Objectif |
|---|---|---|
| `tests/Unit/BasicTest.php` | Unit | Vérifie que l'environnement de test fonctionne |
| `tests/Feature/AuthenticationTest.php` | Feature | Vérifie login page, dashboard après connexion, logout |
| `tests/Feature/HelpTest.php` | Feature | Vérifie l'accès à la page d'aide et son contenu |

## Résultat local

Commande exécutée:

```bash
php artisan test --compact
```

Résultat:

```text
Tests: 5 passed (15 assertions)
Duration: 1.88s
```

## Interprétation

Les tests actuellement disponibles valident les comportements essentiels suivants:

- L'application expose bien la page de connexion admin.
- Un administrateur existant peut accéder au dashboard.
- La déconnexion admin fonctionne.
- La page d'aide admin est accessible et contient les sections attendues.

## Couverture fonctionnelle

La couverture actuelle reste limitée. Les modules CRM principaux ne sont pas encore suffisamment couverts:

- Leads
- Contacts
- Activités
- Produits
- Devis
- Utilisateurs et rôles
- Workflows
- Import/export

## Recommandations

1. Ajouter des tests Feature pour la création, modification et suppression d'un lead.
2. Ajouter des tests pour les permissions et rôles utilisateurs.
3. Ajouter des tests pour les imports afin d'éviter les erreurs sur fichiers malformés.
4. Ajouter des tests API pour les routes protégées par Sanctum.
5. Générer la couverture avec `--coverage-clover` dans la pipeline.

