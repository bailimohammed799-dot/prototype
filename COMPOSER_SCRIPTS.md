# Krayin CRM - Test Scripts Configuration

Ajoutez ces scripts à votre `composer.json` pour faciliter l'exécution des tests :

## Configuration

```json
{
  "scripts": {
    "test": "php artisan test --compact",
    "test:all": "php artisan test --compact",
    
    "test:security": "php artisan test tests/Feature/SecurityDynamicTest.php --compact",
    "test:security:watch": "php artisan test tests/Feature/SecurityDynamicTest.php --watch --compact",
    "test:security:verbose": "php artisan test tests/Feature/SecurityDynamicTest.php --verbose",
    "test:security:parallel": "php artisan test tests/Feature/SecurityDynamicTest.php --parallel --compact",
    
    "test:unit": "php artisan test --testsuite=Unit --compact",
    "test:feature": "php artisan test --testsuite=Feature --compact",
    
    "test:sql": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='SQL Injection' --compact",
    "test:auth": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Authentication' --compact",
    "test:session": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Session' --compact",
    "test:privilege": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Privilege' --compact",
    "test:replay": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Replay' --compact",
    "test:api": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='API' --compact",
    "test:dos": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Denial' --compact",
    "test:credential": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Credential' --compact",
    "test:config": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Misconfiguration' --compact",
    "test:data": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Sensitive Data' --compact",
    "test:email": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Sender Spoofing' --compact",
    "test:mitm": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Man-in-the-Middle' --compact",
    
    "format": "./vendor/bin/pint",
    "format:fix": "./vendor/bin/pint --test",
    "lint": "./vendor/bin/phpstan analyse",
    "lint:security": "./vendor/bin/phpstan analyse tests/Feature/SecurityDynamicTest.php --level=8",
    
    "clear": [
      "@php artisan config:clear",
      "@php artisan cache:clear",
      "@php artisan view:clear",
      "@php artisan route:clear"
    ],
    
    "install-all": [
      "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
      "composer install",
      "@php artisan key:generate",
      "@php artisan migrate",
      "@php artisan test --compact"
    ]
  }
}
```

## Utilisation

### Tests de Sécurité
```bash
# Tous les tests de sécurité
composer test:security

# Mode watch (auto-relance)
composer test:security:watch

# En parallèle (plus rapide)
composer test:security:parallel

# Rapport détaillé
composer test:security:verbose

# Tests spécifiques
composer test:sql
composer test:auth
composer test:session
composer test:privilege
composer test:replay
composer test:api
composer test:dos
composer test:credential
composer test:config
composer test:data
composer test:email
composer test:mitm
```

### Tests Généraux
```bash
# Tous les tests
composer test

# Seulement unitaires
composer test:unit

# Seulement fonctionnalité
composer test:feature
```

### Qualité du Code
```bash
# Formater le code
composer format

# Vérifier la mise en forme
composer format:fix

# Analyser le code
composer lint

# Analyser les tests de sécurité
composer lint:security
```

### Utilitaires
```bash
# Nettoyer le cache
composer clear

# Installation complète
composer install-all
```

## Configuration Complète

Remplacez le contenu de `composer.json` (section "scripts") par :

```json
{
  "name": "krayin/laravel-crm",
  "description": "Free & Opensource Laravel CRM solution for SMEs and Enterprises",
  "type": "project",
  "license": "MIT",
  "require": {
    "php": "^8.3",
    "laravel/framework": "^12.0"
  },
  "require-dev": {
    "pestphp/pest": "^3.0",
    "phpstan/phpstan": "^1.10"
  },
  "scripts": {
    "test": "php artisan test --compact",
    "test:all": "php artisan test --compact",
    "test:security": "php artisan test tests/Feature/SecurityDynamicTest.php --compact",
    "test:security:watch": "php artisan test tests/Feature/SecurityDynamicTest.php --watch --compact",
    "test:security:verbose": "php artisan test tests/Feature/SecurityDynamicTest.php --verbose",
    "test:security:parallel": "php artisan test tests/Feature/SecurityDynamicTest.php --parallel --compact",
    "test:unit": "php artisan test --testsuite=Unit --compact",
    "test:feature": "php artisan test --testsuite=Feature --compact",
    "test:sql": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='SQL Injection' --compact",
    "test:auth": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Authentication' --compact",
    "test:session": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Session' --compact",
    "test:privilege": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Privilege' --compact",
    "test:replay": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Replay' --compact",
    "test:api": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='API' --compact",
    "test:dos": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Denial' --compact",
    "test:credential": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Credential' --compact",
    "test:config": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Misconfiguration' --compact",
    "test:data": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Sensitive Data' --compact",
    "test:email": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Sender Spoofing' --compact",
    "test:mitm": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Man-in-the-Middle' --compact",
    "format": "./vendor/bin/pint",
    "format:fix": "./vendor/bin/pint --test",
    "lint": "./vendor/bin/phpstan analyse",
    "lint:security": "./vendor/bin/phpstan analyse tests/Feature/SecurityDynamicTest.php --level=8",
    "clear": [
      "@php artisan config:clear",
      "@php artisan cache:clear",
      "@php artisan view:clear",
      "@php artisan route:clear"
    ],
    "install-all": [
      "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
      "composer install",
      "@php artisan key:generate",
      "@php artisan migrate",
      "@php artisan test --compact"
    ]
  }
}
```

## Installation

1. Mettez à jour `composer.json` avec les scripts ci-dessus
2. Rechargez l'autoloader :
   ```bash
   composer dump-autoload
   ```
3. Utilisez les commandes :
   ```bash
   composer test:security
   ```

## Avantages

✅ **Facilité d'utilisation** - Commandes courtes et mémorables  
✅ **Documentation** - Les scripts sont auto-documentés  
✅ **Standardisation** - Suit les conventions Krayin CRM  
✅ **Automatisation** - Intégration facile dans CI/CD  
✅ **Performance** - Options parallèles et watch disponibles

---

**Note:** Ces scripts supposent que Laravel Artisan et Pest sont correctement configurés dans le projet.
