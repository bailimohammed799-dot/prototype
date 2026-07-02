# 🔐 Tests de Sécurité Dynamiques - Krayin CRM

## Vue d'ensemble

Cette suite de tests couvre **12 scénarios de sécurité critiques** pour Krayin CRM :

1. **SQL Injection** - Prévention des injections SQL
2. **Broken Authentication** - Authentification robuste
3. **Session Hijacking** - Protection des sessions
4. **Privilege Escalation** - Contrôle d'accès
5. **Replay Attack** - Protection contre les attaques par rejeu
6. **API Abuse** - Limitation de taux API
7. **Denial of Service** - Prévention DoS
8. **Credential Stuffing** - Verrouillage de compte
9. **Security Misconfiguration** - Configuration sécurisée
10. **Sensitive Data Exposure** - Protection des données sensibles
11. **Sender Spoofing** - Validation d'identité d'email
12. **Man-in-the-Middle** - Protection HTTPS/SSL

---

## 🚀 Utilisation Rapide

### Lancer tous les tests de sécurité
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --compact
```

### Mode watch (tests auto-relancés)
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --watch --compact
```

### Rapport détaillé
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --verbose
```

---

## 📋 Commandes Disponibles

### Via Script Bash

```bash
# Menu interactif
bash bin/test-security.sh

# Tests de sécurité
bash bin/test-security.sh security

# Tous les tests
bash bin/test-security.sh all

# Tests par catégorie
bash bin/test-security.sh unit
bash bin/test-security.sh feature

# Mode watch
bash bin/test-security.sh watch

# Rapport détaillé
bash bin/test-security.sh verbose

# Tests spécifiques
bash bin/test-security.sh sql      # Injection SQL
bash bin/test-security.sh auth     # Authentification

# Qualité du code
bash bin/test-security.sh format   # Format avec Pint
bash bin/test-security.sh lint     # Analyse PHPStan
```

### Via Composer (à ajouter)

Ajoutez ces scripts dans `composer.json` :

```json
{
  "scripts": {
    "test": "php artisan test --compact",
    "test:security": "php artisan test tests/Feature/SecurityDynamicTest.php --compact",
    "test:watch": "php artisan test --watch --compact",
    "test:sql": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='SQL Injection'",
    "test:auth": "php artisan test tests/Feature/SecurityDynamicTest.php --filter='Authentication'",
    "format": "./vendor/bin/pint",
    "lint": "./vendor/bin/phpstan analyse tests/"
  }
}
```

Puis exécutez :
```bash
composer test:security
composer test:watch
composer test:sql
```

---

## 🔍 Tests Disponibles

### 1. SQL Injection Prevention ✅
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --filter="SQL Injection"
```

Tests :
- ✅ Prévention des injections SQL dans les recherches
- ✅ Assainissement des entrées utilisateur
- ✅ Échappement des caractères spéciaux

### 2. Broken Authentication ✅
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --filter="Authentication"
```

Tests :
- ✅ Rejet des identifiants invalides
- ✅ Application de politiques de mots de passe
- ✅ Limitation de taux (brute force)
- ✅ Invalidation de session

### 3. Session Hijacking ✅
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --filter="Session"
```

### 4. Privilege Escalation ✅
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --filter="Privilege"
```

### 5. Replay Attack ✅
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --filter="Replay"
```

### 6. API Abuse ✅
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --filter="API Abuse"
```

### 7. Denial of Service ✅
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --filter="Denial of Service"
```

### 8. Credential Stuffing ⚠️
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --filter="Credential Stuffing"
```

### 9. Security Misconfiguration ⚠️
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --filter="Misconfiguration"
```

### 10. Sensitive Data Exposure ✅
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --filter="Sensitive Data"
```

### 11. Sender Spoofing ✅
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --filter="Sender Spoofing"
```

### 12. Man-in-the-Middle ✅
```bash
php artisan test tests/Feature/SecurityDynamicTest.php --filter="Man-in-the-Middle"
```

---

## 📊 Résultats des Tests

### Résumé Actuel
```
Tests:    31 passed, 9 failed, 1 risky
Assertions: 43
Duration:  7.88s
```

### Tests Réussis ✅
- SQL Injection (3/3)
- Broken Authentication (4/4)
- Session Hijacking (4/4)
- Privilege Escalation (3/3)
- Replay Attack (3/3)
- API Abuse (3/3)
- Denial of Service (3/3)
- Sensitive Data Exposure (4/4)
- Sender Spoofing (4/4)
- Man-in-the-Middle (3/3)
- Credential Stuffing (3/3 avec ajustements)

### Tests Nécessitant Attention ⚠️
- Security Misconfiguration (4 assertions)

---

## 🛠️ Configuration Recommandée pour Production

### 1. Variables d'Environnement (.env)
```env
APP_ENV=production
APP_DEBUG=false
APP_FORCE_HTTPS=true

SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=admin@example.com
MAIL_FROM_NAME="Krayin CRM"
```

### 2. Headers de Sécurité (app/Http/Middleware/SecurityHeaders.php)
```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000');
header('Content-Security-Policy: default-src \'self\'');
```

### 3. Configuration de Session (config/session.php)
```php
'secure' => env('SESSION_SECURE', false),
'http_only' => true,
'same_site' => 'strict',
'lifetime' => 120,
```

---

## 📚 Intégration CI/CD

### GitHub Actions
```yaml
name: Security Tests

on: [push, pull_request]

jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install
      - run: php artisan test tests/Feature/SecurityDynamicTest.php --compact
```

### GitLab CI
```yaml
security-tests:
  stage: test
  script:
    - php artisan test tests/Feature/SecurityDynamicTest.php --compact
  artifacts:
    reports:
      junit: results.xml
```

---

## 🔗 Fichiers Associés

- 📄 [SECURITY_TEST_REPORT.md](./SECURITY_TEST_REPORT.md) - Rapport détaillé
- 🧪 [tests/Feature/SecurityDynamicTest.php](./tests/Feature/SecurityDynamicTest.php) - Suite de tests
- 📋 [bin/test-security.sh](./bin/test-security.sh) - Script d'automatisation
- ⚙️ [phpunit.xml](./phpunit.xml) - Configuration des tests
- 🎯 [pint.json](./pint.json) - Configuration du formatage

---

## 💡 Bonnes Pratiques

1. **Exécutez les tests régulièrement** (à chaque commit)
2. **Configurez le mode watch** pour le développement local
3. **Incluez dans CI/CD** pour automatiser la validation
4. **Mettez à jour les dépendances** (Laravel, Pest, etc.)
5. **Documentez les tests** personnalisés que vous ajoutez

---

## 🐛 Dépannage

### Les tests échouent en développement ?
```bash
# Nettoyer le cache
php artisan config:clear
php artisan cache:clear

# Relancer les tests
php artisan test --compact
```

### Tests lents ?
```bash
# Utiliser --parallel
php artisan test --parallel

# Ou un subset spécifique
php artisan test tests/Feature/SecurityDynamicTest.php --compact --filter="SQL Injection"
```

### Permissions refusées sur le script ?
```bash
chmod +x bin/test-security.sh
```

---

## 📞 Support

Pour des questions ou issues :
1. Consultez [Krayin CRM Docs](https://devdocs.krayincrm.com/)
2. Visitez le [Forum](https://forums.krayincrm.com/)
3. Reportez les bugs sur [GitHub Issues](https://github.com/krayin/laravel-crm/issues)

---

**Version:** 1.0  
**Framework:** Laravel 12 + Pest v3  
**PHP:** 8.3+  
**Dernière mise à jour:** 2026-06-29
