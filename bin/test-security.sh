#!/usr/bin/env bash

# Krayin CRM - Security Testing Automation Script
# Ce script automatise le lancement des tests de sécurité

set -e

echo "🔐 Krayin CRM - Security Test Suite"
echo "===================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
run_security_tests() {
    echo -e "${BLUE}▶ Exécution des tests de sécurité...${NC}"
    php artisan test tests/Feature/SecurityDynamicTest.php --compact
}

run_all_tests() {
    echo -e "${BLUE}▶ Exécution de tous les tests...${NC}"
    php artisan test --compact
}

run_unit_tests() {
    echo -e "${BLUE}▶ Exécution des tests unitaires...${NC}"
    php artisan test --testsuite="Unit" --compact
}

run_feature_tests() {
    echo -e "${BLUE}▶ Exécution des tests de fonctionnalité...${NC}"
    php artisan test --testsuite="Feature" --compact
}

run_security_watch() {
    echo -e "${BLUE}▶ Lancement du mode watch pour les tests de sécurité...${NC}"
    php artisan test --watch tests/Feature/SecurityDynamicTest.php --compact
}

run_security_verbose() {
    echo -e "${BLUE}▶ Exécution avec rapport détaillé...${NC}"
    php artisan test tests/Feature/SecurityDynamicTest.php --verbose
}

run_sql_injection_tests() {
    echo -e "${BLUE}▶ Tests d'injection SQL uniquement...${NC}"
    php artisan test tests/Feature/SecurityDynamicTest.php --filter="SQL Injection"
}

run_authentication_tests() {
    echo -e "${BLUE}▶ Tests d'authentification...${NC}"
    php artisan test tests/Feature/SecurityDynamicTest.php --filter="Authentication"
}

format_code() {
    echo -e "${BLUE}▶ Formatage du code avec Pint...${NC}"
    ./vendor/bin/pint
}

lint_security_tests() {
    echo -e "${BLUE}▶ Vérification du code des tests de sécurité...${NC}"
    ./vendor/bin/phpstan analyse tests/Feature/SecurityDynamicTest.php --level=8
}

show_menu() {
    echo -e "${YELLOW}Sélectionnez une option:${NC}"
    echo "1) Tests de sécurité uniquement"
    echo "2) Tous les tests"
    echo "3) Tests unitaires"
    echo "4) Tests de fonctionnalité"
    echo "5) Mode watch (sécurité)"
    echo "6) Rapport détaillé (sécurité)"
    echo "7) Tests d'injection SQL"
    echo "8) Tests d'authentification"
    echo "9) Formater le code"
    echo "10) Linter les tests de sécurité"
    echo "0) Quitter"
    echo ""
    read -p "Choix: " choice
}

# Main script
if [ $# -eq 0 ]; then
    # Interactive mode
    while true; do
        show_menu
        case $choice in
            1)
                run_security_tests
                ;;
            2)
                run_all_tests
                ;;
            3)
                run_unit_tests
                ;;
            4)
                run_feature_tests
                ;;
            5)
                run_security_watch
                ;;
            6)
                run_security_verbose
                ;;
            7)
                run_sql_injection_tests
                ;;
            8)
                run_authentication_tests
                ;;
            9)
                format_code
                ;;
            10)
                lint_security_tests
                ;;
            0)
                echo -e "${GREEN}Au revoir!${NC}"
                exit 0
                ;;
            *)
                echo -e "${RED}Option invalide${NC}"
                ;;
        esac
        echo ""
    done
else
    # Command line argument mode
    case $1 in
        "security")
            run_security_tests
            ;;
        "all")
            run_all_tests
            ;;
        "unit")
            run_unit_tests
            ;;
        "feature")
            run_feature_tests
            ;;
        "watch")
            run_security_watch
            ;;
        "verbose")
            run_security_verbose
            ;;
        "sql")
            run_sql_injection_tests
            ;;
        "auth")
            run_authentication_tests
            ;;
        "format")
            format_code
            ;;
        "lint")
            lint_security_tests
            ;;
        *)
            echo -e "${RED}Commande inconnue: $1${NC}"
            echo "Usage: $0 {security|all|unit|feature|watch|verbose|sql|auth|format|lint}"
            exit 1
            ;;
    esac
fi
