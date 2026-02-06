<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, ShieldCheck } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/composables/usePermission';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{
    roles: Array<{ id: number; name: string; permissions_count: number }>;
}>();

const { t } = useI18n();
const { can } = usePermission();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('permissions.title'), href: '#' }];

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

function deleteRole(id: number, name: string) {
    if (confirm(t('common.confirmDelete'))) {
        router.delete(`/warehouse/roles/${id}`);
    }
}
</script>

<template>
    <Head :title="t('permissions.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-row items-center justify-between gap-4 p-4 md:p-6 pb-0">
                    <div>
                        <h1 class="text-xl font-semibold">{{ t('permissions.title') }}</h1>
                        <p class="text-sm text-muted-foreground">{{ t('users.roles') }}</p>
                    </div>
                    <Link v-if="can('roles.create')" href="/warehouse/roles/create">
                        <Button><Plus class="mr-2 h-4 w-4" />{{ t('common.add') }}</Button>
                    </Link>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{{ t('common.name') }}</TableHead>
                            <TableHead>{{ t('permissions.title') }}</TableHead>
                            <TableHead class="w-24 text-right">{{ t('common.actions') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="r in roles" :key="r.id">
                            <TableCell class="font-medium">
                                <span>{{ formatRoleName(r.name) }}</span>
                            </TableCell>
                            <TableCell>
                                <Badge variant="secondary">{{ r.permissions_count }}</Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <Link v-if="can('roles.edit')" :href="`/warehouse/roles/${r.id}/edit`">
                                        <Button variant="ghost" size="sm"><Pencil class="h-4 w-4" /></Button>
                                    </Link>
                                    <Button
                                        v-if="can('roles.delete') && r.name !== 'admin'"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="deleteRole(r.id, r.name)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
