import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermission() {
    const page = usePage();
    const permissions = computed(() => (page.props.auth?.user as { permissions?: string[] } | undefined)?.permissions ?? []);

    function can(permission: string): boolean {
        return permissions.value.includes(permission);
    }

    function canAny(perms: string[]): boolean {
        return perms.some((p) => permissions.value.includes(p));
    }

    function canAll(perms: string[]): boolean {
        return perms.every((p) => permissions.value.includes(p));
    }

    return { can, canAny, canAll, permissions };
}
