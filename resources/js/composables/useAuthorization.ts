import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useAuthorization() {
    const page = usePage();

    const permissionSet = computed(() => new Set<string>((page.props.permissions as string[]) ?? []));
    const roleSet = computed(() => new Set<string>((page.props.roleNames as string[]) ?? []));

    const can = (permission: string) => permissionSet.value.has(permission);

    const canAny = (permissions: string[]) => permissions.some((p) => permissionSet.value.has(p));

    const hasRole = (role: string) => roleSet.value.has(role);

    return { can, canAny, hasRole };
}
