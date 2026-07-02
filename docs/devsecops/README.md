# DevSecOps - Krayin Laravel CRM

## Identification

- Stagiaire: Mohamed Habib Baili
- Encadrant: Baylassen
- Entreprise: L2T
- Projet: Krayin CRM, application Laravel open source
- Date de préparation: 2026-06-28

## Objectif

Ce dossier contient les livrables demandés dans le cahier des charges "Mise en place d'une pipeline DevSecOps avec intégration de tests de sécurité".

La pipeline met en place une approche Shift Left Security: les erreurs de build, les régressions, les défauts de qualité, les vulnérabilités de dépendances, les secrets exposés, les failles SAST et les problèmes DAST sont détectés automatiquement avant une livraison.

## Fichiers ajoutés

- `.github/workflows/devsecops-pipeline.yml`: pipeline CI/CD DevSecOps.
- `sonar-project.properties`: configuration SonarQube.
- `docs/devsecops/documentation-technique.md`: documentation technique de la pipeline.
- `docs/devsecops/rapport-tests-unitaires.md`: rapport de tests unitaires.
- `docs/devsecops/rapport-analyses-securite.md`: rapport SAST, dépendances et DAST.
- `docs/devsecops/rapport-final-vulnerabilites.md`: synthèse finale, impacts et recommandations.

## Commandes locales utiles

```bash
composer install
npm install
php artisan krayin-crm:install --skip-env-check
npm run build
php artisan test --compact
./vendor/bin/pint --test
composer audit
npm audit
```

## Variables à configurer dans GitHub

Pour activer SonarQube dans la pipeline, ajouter:

- Variable GitHub Actions: `SONAR_HOST_URL`
- Secret GitHub Actions: `SONAR_TOKEN`

Pour Semgrep AppSec Platform, optionnel:

- Secret GitHub Actions: `SEMGREP_APP_TOKEN`

