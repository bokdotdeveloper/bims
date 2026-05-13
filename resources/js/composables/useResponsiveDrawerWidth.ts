import { computed, type ComputedRef } from 'vue';
import { useMediaQuery } from '@vueuse/core';

/**
 * Ant Design Drawer width: full viewport on narrow screens, fixed px on md+.
 */
export function useResponsiveDrawerWidth(desktopPx: number): ComputedRef<string | number> {
    const isNarrow = useMediaQuery('(max-width: 767px)');

    return computed(() => (isNarrow.value ? '100%' : desktopPx));
}
