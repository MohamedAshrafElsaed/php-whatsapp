import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

// Translation store
const translations = ref<Record<string, any>>({});
const currentLocale = ref<string>('ar');

export function useTranslation() {
    const page = usePage();

    // Get current locale from props or default to 'ar'
    const locale = computed(() => {
        return (page.props.locale as string) || currentLocale.value;
    });

    // Get translations from props
    const trans = computed(() => {
        return (page.props.translations as Record<string, any>) || translations.value;
    });

    // Check if current locale is RTL
    const isRTL = computed(() => locale.value === 'ar');

    // Translation function
    const t = (key: string, replacements?: Record<string, any>): string => {
        const keys = key.split('.');
        let value: any = trans.value;

        // Navigate through nested keys
        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                return key; // Return key if translation not found
            }
        }

        // Handle replacements
        if (replacements && typeof value === 'string') {
            Object.keys(replacements).forEach((replaceKey) => {
                value = value.replace(
                    new RegExp(`{${replaceKey}}`, 'g'),
                    replacements[replaceKey]
                );
            });
        }

        return value || key;
    };

    // Switch language
    const switchLocale = (newLocale: 'en' | 'ar') => {
        router.get(
            window.location.pathname,
            { lang: newLocale },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['locale', 'translations'],
                onSuccess: () => {
                    currentLocale.value = newLocale;
                    // Update HTML attributes
                    document.documentElement.lang = newLocale;
                    document.documentElement.dir = newLocale === 'ar' ? 'rtl' : 'ltr';
                },
            }
        );
    };

    return {
        t,
        locale,
        isRTL,
        switchLocale,
    };
}
