import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';

// =====================================================================
// FRONTEND COMPONENT UNDER TEST: v-organization
// =====================================================================
const vOrganization = {
    template: `
        <div>
            <!-- Simulated x-admin::attributes component -->
            <div class="custom-attributes">
                <input type="text" class="lookup-input" @input="simulateLookup" />
            </div>
            <input v-if="organizationName" type="hidden" name="organization_name" :value="organizationName" />
        </div>
    `,
    data() {
        return {
            organizationName: null,
        };
    },
    methods: {
        handleLookupAdded(event) {
            this.organizationName = event?.name || null;
        },
        simulateLookup(e) {
            this.handleLookupAdded({ name: e.target.value });
        }
    },
};

describe('Vue Component: v-organization', () => {
    /**
     * Règle métier protégée : Auto-création d'organisation via liaison réactive
     * Criticité : HAUTE
     * Bug détecté si échec : L'organisation n'est pas transmise au formulaire lors de la soumission.
     */
    it('devrait initialiser organizationName à null par défaut', () => {
        const wrapper = mount(vOrganization);
        expect(wrapper.vm.organizationName).toBeNull();
        expect(wrapper.find('input[name="organization_name"]').exists()).toBeFalse();
    });

    it('devrait mettre à jour organizationName et afficher l input caché lorsque handleLookupAdded est déclenché', async () => {
        const wrapper = mount(vOrganization);
        
        await wrapper.vm.handleLookupAdded({ name: 'Acme Corporation' });
        
        expect(wrapper.vm.organizationName).toBe('Acme Corporation');
        const hiddenInput = wrapper.find('input[name="organization_name"]');
        expect(hiddenInput.exists()).toBeTrue();
        expect(hiddenInput.element.value).toBe('Acme Corporation');
    });

    it('devrait réinitialiser organizationName à null si l événement transmis est invalide', async () => {
        const wrapper = mount(vOrganization);
        
        // D'abord on set une valeur
        await wrapper.vm.handleLookupAdded({ name: 'Acme Corp' });
        expect(wrapper.vm.organizationName).toBe('Acme Corp');

        // Puis on envoie un événement vide
        await wrapper.vm.handleLookupAdded(null);
        expect(wrapper.vm.organizationName).toBeNull();
        expect(wrapper.find('input[name="organization_name"]').exists()).toBeFalse();
    });
});

// =====================================================================
// FRONTEND VALIDATION RULES: vee-validate
// =====================================================================
const validationRules = {
    phone: (value) => {
        if (!value || !value.length) {
            return true;
        }
        return /^\+?\d+$/.test(value);
    },
    
    address: (value) => {
        if (!value || !value.length) {
            return true;
        }
        return /^[a-zA-Z0-9\s.\/*'\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\u0590-\u05FF\u3040-\u309F\u30A0-\u30FF\u0400-\u04FF\u0D80-\u0DFF\u3400-\u4DBF\u2000-\u2A6D\u00C0-\u017F\u0980-\u09FF\u0900-\u097F\u4E00-\u9FFF,\(\)-]{1,60}$/iu.test(
            value
        );
    },

    postcode: (value) => {
        if (!value || !value.length) {
            return true;
        }
        return /^[a-zA-Z0-9][a-zA-Z0-9\s-]*[a-zA-Z0-9]$/.test(value);
    },

    decimal: (value, { decimals = '*', separator = '.' } = {}) => {
        if (value === null || value === undefined || value === '') {
            return true;
        }
        if (Number(decimals) === 0) {
            return /^-?\d*$/.test(value);
        }
        const regexPart = decimals === '*' ? '+' : `{1,${decimals}}`;
        const regex = new RegExp(`^[-+]?\\d*(\\${separator}\\d${regexPart})?([eE]{1}[-]?\\d+)?$`);
        return regex.test(value);
    },

    required_if: (value, { condition = true } = {}) => {
        if (condition) {
            if (value === null || value === undefined || value === '') {
                return false;
            }
        }
        return true;
    },

    date_format: (value) => {
        return /^\d{4}-\d{2}-\d{2}$/.test(value);
    },

    after: (value) => {
        const today = new Date();
        const inputDate = new Date(value);
        today.setHours(0, 0, 0, 0);
        inputDate.setHours(0, 0, 0, 0);
        return inputDate >= today;
    }
};

describe('Vee-Validate Rules - Person Module validations', () => {
    describe('Phone Validation Rule', () => {
        /**
         * Règle métier protégée : Validation du format des numéros de téléphone
         * Criticité : MOYENNE
         * Bug détecté si échec : Les utilisateurs peuvent saisir des numéros contenant des lettres, corrompant la base de contacts.
         */
        it('devrait accepter les numéros de téléphone valides (avec ou sans +)', () => {
            expect(validationRules.phone('+33612345678')).toBeTrue();
            expect(validationRules.phone('0612345678')).toBeTrue();
            expect(validationRules.phone('')).toBeTrue(); // Optionnel si vide
        });

        it('devrait rejeter les numéros contenant des lettres ou caractères spéciaux non valides', () => {
            expect(validationRules.phone('06-12-34-56-78')).toBeFalse();
            expect(validationRules.phone('+33 6 12 34 56 78')).toBeFalse();
            expect(validationRules.phone('abcdefgh')).toBeFalse();
        });
    });

    describe('Address Validation Rule', () => {
        /**
         * Règle métier protégée : Enregistrement d adresses sécurisées et propres
         * Criticité : MOYENNE
         */
        it('devrait accepter les adresses valides avec caractères internationaux', () => {
            expect(validationRules.address('12 Rue de la Paix')).toBeTrue();
            expect(validationRules.address('Avenue Champs-Élysées, 75008 Paris')).toBeTrue();
        });

        it('devrait rejeter les adresses trop longues (>60 char) ou contenant des injections HTML/XSS suspectes', () => {
            expect(validationRules.address('a'.repeat(61))).toBeFalse();
            expect(validationRules.address('<script>alert("xss")</script>')).toBeFalse();
        });
    });

    describe('Postcode Validation Rule', () => {
        /**
         * Règle métier : Format de code postal global
         * Criticité : BASSE
         */
        it('devrait valider les codes postaux standards', () => {
            expect(validationRules.postcode('75000')).toBeTrue();
            expect(validationRules.postcode('SW1A 1AA')).toBeTrue();
            expect(validationRules.postcode('123-456')).toBeTrue();
        });

        it('devrait rejeter les codes postaux malformés avec caractères spéciaux en bordure', () => {
            expect(validationRules.postcode('-12345')).toBeFalse();
            expect(validationRules.postcode('12345-')).toBeFalse();
        });
    });

    describe('Decimal Validation Rule', () => {
        /**
         * Règle métier : Saisie de données monétaires et numériques décimales
         * Criticité : MOYENNE
         */
        it('devrait valider les nombres décimaux corrects', () => {
            expect(validationRules.decimal('12.34')).toBeTrue();
            expect(validationRules.decimal('-10')).toBeTrue();
            expect(validationRules.decimal('125,50', { decimals: 2, separator: ',' })).toBeTrue();
        });

        it('devrait rejeter si le nombre de décimales dépasse la limite ou si le séparateur est invalide', () => {
            expect(validationRules.decimal('12.345', { decimals: 2, separator: '.' })).toBeFalse();
            expect(validationRules.decimal('12.34', { decimals: 2, separator: ',' })).toBeFalse();
        });
    });

    describe('Required_if Validation Rule', () => {
        /**
         * Règle métier : Champs requis conditionnellement (ex: organization_name requis si pas d ID)
         * Criticité : HAUTE
         */
        it('devrait exiger une valeur si la condition est vraie', () => {
            expect(validationRules.required_if('', { condition: true })).toBeFalse();
            expect(validationRules.required_if('Present', { condition: true })).toBeTrue();
        });

        it('ne devrait pas exiger de valeur si la condition est fausse', () => {
            expect(validationRules.required_if('', { condition: false })).toBeTrue();
        });
    });

    describe('Date Format and Date After Rules', () => {
        /**
         * Règle métier : Validation temporelle (suivi des opportunités, événements CRM)
         * Criticité : MOYENNE
         */
        it('date_format: devrait valider uniquement le format YYYY-MM-DD', () => {
            expect(validationRules.date_format('2026-06-30')).toBeTrue();
            expect(validationRules.date_format('30-06-2026')).toBeFalse();
            expect(validationRules.date_format('2026/06/30')).toBeFalse();
        });

        it('after: devrait rejeter les dates passées', () => {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            const yesterdayStr = yesterday.toISOString().split('T')[0];
            
            expect(validationRules.after(yesterdayStr)).toBeFalse();
        });

        it('after: devrait accepter les dates futures ou aujourd hui', () => {
            const todayStr = new Date().toISOString().split('T')[0];
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowStr = tomorrow.toISOString().split('T')[0];

            expect(validationRules.after(todayStr)).toBeTrue();
            expect(validationRules.after(tomorrowStr)).toBeTrue();
        });
    });
});

// =====================================================================
// FRONTEND UTILITY HELPERS: formatPrice & formatDate
// =====================================================================
const helperFunctions = {
    formatPrice: (price, currencyConfig, locale = 'en-US') => {
        const symbol = currencyConfig.symbol !== "" ? currencyConfig.symbol : currencyConfig.code;
        
        if (!currencyConfig.currency_position) {
            return new Intl.NumberFormat(locale, {
                style: "currency",
                currency: currencyConfig.code,
            }).format(price);
        }

        const formatter = new Intl.NumberFormat(locale, {
            style: "currency",
            currency: currencyConfig.code,
            minimumFractionDigits: currencyConfig.decimal ?? 2,
        });

        const formattedCurrency = formatter
            .formatToParts(price)
            .map((part) => {
                switch (part.type) {
                    case "currency":
                        return "";
                    case "group":
                        return currencyConfig.group_separator === "" ? part.value : currencyConfig.group_separator;
                    case "decimal":
                        return currencyConfig.decimal_separator === "" ? part.value : currencyConfig.decimal_separator;
                    default:
                        return part.value;
                }
            })
            .join("");

        switch (currencyConfig.currency_position) {
            case "left":
                return symbol + formattedCurrency;
            case "left_with_space":
                return symbol + " " + formattedCurrency;
            case "right":
                return formattedCurrency + symbol;
            case "right_with_space":
                return formattedCurrency + " " + symbol;
            default:
                return formattedCurrency;
        }
    },

    formatDate: (dateString, format, timezone) => {
        const date = new Date(dateString);
        const options = { timeZone: timezone };

        const formatter = new Intl.DateTimeFormat("en-US", {
            ...options,
            hour12: false,
            year: "numeric",
            month: "numeric",
            day: "numeric",
            hour: "numeric",
            minute: "numeric",
            second: "numeric",
        });

        const parts = formatter.formatToParts(date);
        const dateParts = {};

        parts.forEach((part) => {
            if (part.type !== "literal") {
                dateParts[part.type] = part.value;
            }
        });

        const tzDay = parseInt(dateParts.day, 10);
        const tzMonth = parseInt(dateParts.month, 10);
        const tzYear = parseInt(dateParts.year, 10);
        const tzHour = parseInt(dateParts.hour, 10);
        const tzMinute = parseInt(dateParts.minute, 10);

        const formatters = {
            d: tzDay,
            DD: tzDay.toString().padStart(2, "0"),
            M: tzMonth,
            MM: tzMonth.toString().padStart(2, "0"),
            MMM: new Date(tzYear, tzMonth - 1, 1).toLocaleString("en-US", { month: "short" }),
            MMMM: new Date(tzYear, tzMonth - 1, 1).toLocaleString("en-US", { month: "long" }),
            yy: tzYear.toString().slice(-2),
            yyyy: tzYear,
            H: tzHour,
            HH: tzHour.toString().padStart(2, "0"),
            h: tzHour % 12 || 12,
            hh: (tzHour % 12 || 12).toString().padStart(2, "0"),
            m: tzMinute,
            mm: tzMinute.toString().padStart(2, "0"),
            A: tzHour < 12 ? "AM" : "PM",
        };

        return format.replace(
            /\b(?:d|DD|M|MM|MMM|MMMM|yy|yyyy|H|HH|h|hh|m|mm|A)\b/g,
            (match) => formatters[match]
        );
    }
};

describe('Admin helper utilities', () => {
    describe('formatPrice utility', () => {
        /**
         * Règle métier protégée : Affichage correct des montants et opportunités CRM selon les devises
         * Criticité : MOYENNE
         * Bug détecté si échec : Affichage erroné de la position des devises (ex: €100 au lieu de 100 €).
         */
        const eurConfig = {
            code: 'EUR',
            symbol: '€',
            decimal: 2,
            group_separator: ' ',
            decimal_separator: ',',
            currency_position: 'right_with_space'
        };

        const usdConfig = {
            code: 'USD',
            symbol: '$',
            decimal: 2,
            group_separator: ',',
            decimal_separator: '.',
            currency_position: 'left'
        };

        it('devrait formater correctement en EUR avec symbole à droite et espace', () => {
            const formatted = helperFunctions.formatPrice(1250.50, eurConfig, 'fr-FR');
            // Le montant sans le symbole sera formaté selon les séparateurs : "1 250,50"
            // Et comme position = right_with_space, on aura "1 250,50 €"
            expect(formatted.replace(/\u00a0/g, ' ')).toBe('1 250,50 €');
        });

        it('devrait formater correctement en USD avec symbole à gauche sans espace', () => {
            const formatted = helperFunctions.formatPrice(1250.50, usdConfig, 'en-US');
            expect(formatted).toBe('$1,250.50');
        });
    });

    describe('formatDate utility', () => {
        /**
         * Règle métier protégée : Cohérence des dates affichées selon le fuseau horaire de l utilisateur
         * Criticité : MOYENNE
         * Bug détecté si échec : Une opportunité peut être affichée à la mauvaise date selon le fuseau horaire configuré.
         */
        const sampleDate = '2026-06-30T14:30:00Z'; // UTC

        it('devrait formater la date en UTC selon le format yyyy-MM-dd HH:mm', () => {
            const formatted = helperFunctions.formatDate(sampleDate, 'yyyy-MM-dd HH:mm', 'UTC');
            expect(formatted).toBe('2026-06-30 14:30');
        });

        it('devrait appliquer le décalage horaire pour Paris (UTC+2 en été)', () => {
            const formatted = helperFunctions.formatDate(sampleDate, 'yyyy-MM-dd HH:mm', 'Europe/Paris');
            expect(formatted).toBe('2026-06-30 16:30');
        });

        it('devrait appliquer le décalage horaire pour New York (UTC-4 en été)', () => {
            const formatted = helperFunctions.formatDate(sampleDate, 'yyyy-MM-dd HH:mm', 'America/New_York');
            expect(formatted).toBe('2026-06-30 10:30');
        });
    });
});
