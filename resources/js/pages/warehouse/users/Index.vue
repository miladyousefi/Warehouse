<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Users } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import Pagination from '@/components/Pagination.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/composables/usePermission';
import { index, create, edit, destroy } from '@/actions/App/Http/Controllers/Warehouse/UserController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatTurkeyDate } from '@/composables/useTurkeyDate';

const props = defineProps<{
    users: { data: Array<Record<string, unknown>>; links: Array<{ url: string | null; label: string; active?: boolean }> };
    roles: Array<{ id: number; name: string }>;
}>();

const { t } = useI18n();
const { can } = usePermission();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.admins'), href: index.url() }];

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

function deleteUser(id: number, name: string) {
    if (confirm(t('users.confirmDelete', { name }))) {
        router.delete(destroy.url({ user: id }));
    }
}
</script>

<template>
    <Head :title="t('nav.admins')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-row items-center justify-between gap-4 p-4 md:p-6 pb-0">
                    <div>
                        <h1 class="text-xl font-semibold">{{ t('nav.admins') }}</h1>
                        <p class="text-sm text-muted-foreground">{{ t('users.description') }}</p>
                    </div>
                    <Link v-if="can('users.create')" :href="create.url()">
                        <Button><Plus class="mr-2 h-4 w-4" />{{ t('users.addAdmin') }}</Button>
                    </Link>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4 overflow-y-auto">
                <Table class="bg-transparent">
                    <TableHeader>
                        <TableRow class="border-b border-border">
                            <TableHead class="text-muted-foreground">{{ t('common.name') }}</TableHead>
                            <TableHead class="text-muted-foreground">Email</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('users.roles') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.date') }}</TableHead>
                            <TableHead class="text-muted-foreground w-24">{{ t('common.actions') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="u in users.data" :key="(u as any).id" class="border-b border-border hover:bg-muted/30">
                            <TableCell class="font-medium">{{ (u as any).name }}</TableCell>
                            <TableCell>{{ (u as any).email }}</TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <Badge v-for="r in ((u as any).roles || [])" :key="(r as any).id" variant="outline" class="text-xs border-primary/20 bg-primary/5">
                                        {{ formatRoleName((r as any).name) }}
                                    </Badge>
                                    <span v-if="!((u as any).roles || []).length" class="text-muted-foreground">-</span>
                                </div>
                            </TableCell>
                            <TableCell class="whitespace-nowrap">{{ formatTurkeyDate((u as any).created_at) }}</TableCell>
                            <TableCell>
                                <div class="flex gap-1">
                                    <Link v-if="can('users.edit')" :href="edit.url({ user: (u as any).id })">
                                        <Button variant="ghost" size="sm"><Pencil class="h-4 w-4" /></Button>
                                    </Link>
                                    <Button
                                        v-if="can('users.delete') && (u as any).id !== $page.props.auth?.user?.id"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="deleteUser((u as any).id, (u as any).name)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <Pagination v-if="users.links?.length" :links="users.links" class="mt-4" />
            </div>
        </AppPageContent>
    </AppLayout>
</template>
