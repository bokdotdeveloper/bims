import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

// Shared singleton ref - all components use the same reactive state
const appearance = ref<Appearance>('system');
let isInitialized = false;

export function updateTheme(value: Appearance) {
    // Guard against SSR
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }

    if (value === 'system') {
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.classList.toggle('dark', systemTheme === 'dark');
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }
}

const handleSystemThemeChange = () => {
    // Guard against SSR
    if (typeof window === 'undefined' || typeof localStorage === 'undefined') {
        return;
    }

    const currentAppearance = localStorage.getItem('appearance') as Appearance | null;
    updateTheme(currentAppearance || 'system');
};

export function initializeTheme() {
    // Guard against SSR - exit early
    if (typeof window === 'undefined' || typeof localStorage === 'undefined') {
        return;
    }

    if (isInitialized) {
        return;
    }

    isInitialized = true;

    // Initialize theme from saved preference or default to system
    const savedAppearance = localStorage.getItem('appearance') as Appearance | null;

    if (savedAppearance) {
        appearance.value = savedAppearance;
    }

    updateTheme(appearance.value);

    // Set up system theme change listener
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    onMounted(() => {
        // This only runs in the browser, but add guard for safety
        if (typeof window === 'undefined') {
            return;
        }

        initializeTheme();
    });

    function updateAppearance(value: Appearance) {
        // Guard against SSR
        if (typeof window === 'undefined' || typeof localStorage === 'undefined') {
            return;
        }

        appearance.value = value;
        localStorage.setItem('appearance', value);
        updateTheme(value);
    }

    return {
        appearance,
        updateAppearance,
    };
}
