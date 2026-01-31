<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/composables/usePermission';
import { index, create } from '@/actions/App/Http/Controllers/Warehouse/SupplierController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

const props = defineProps<{ suppliers: { data: Array<Record<string, unknown>>; links: Array<{ url: string | null; label: string }> } }>();
const { t } = useI18n();
const { can } = usePermission();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.suppliers'), href: index.url() }];
function destroy(id: number) {
    if (confirm(t('common.delete') + '?')) router.delete(`/warehouse/suppliers/${id}`);
}
</script>

<template>
    <Head :title="t('suppliers.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div><CardTitle>{{ t('suppliers.title') }}</CardTitle><CardDescription>{{ t('common.view') }}</CardDescription></div>
                    <Link v-if="can('suppliers.create')" :href="create.url()"><Button><Plus class="mr-2 h-4 w-4" />{{ t('common.add') }}</Button></Link>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ t('common.name') }}</TableHead>
                                <TableHead>{{ t('suppliers.contactPerson') }}</TableHead>
                                <TableHead>{{ t('suppliers.phone') }}</TableHead>
                                <TableHead>{{ t('common.status') }}</TableHead>
                                <TableHead class="w-[80px]">{{ t('common.actions') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="s in suppliers.data" :key="(s as any).id">
                                <TableCell class="font-medium">{{ (s as any).name }}</TableCell>
                                <TableCell>{{ (s as any).contact_person ?? '-' }}</TableCell>
                                <TableCell>{{ (s as any).phone ?? '-' }}</TableCell>
                                <TableCell><Badge :variant="(s as any).is_active ? 'default' : 'secondary'">{{ (s as any).is_active ? t('common.active') : t('common.inactive') }}</Badge></TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child><Button variant="ghost" size="icon">...</Button></DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem v-if="can('suppliers.edit')" as-child><Link :href="`/warehouse/suppliers/${(s as any).id}/edit`"><Pencil class="mr-2 h-4 w-4" />{{ t('common.edit') }}</Link></DropdownMenuItem>
                                            <DropdownMenuItem v-if="can('suppliers.delete')" class="text-destructive" @click="destroy((s as any).id)"><Trash2 class="mr-2 h-4 w-4" />{{ t('common.delete') }}</DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-if="suppliers.links?.length" class="mt-4 flex gap-2">
                        <Link v-for="(link, i) in suppliers.links" :key="i" :href="link.url || '#'" class="rounded border px-3 py-1 text-sm" :class="{ 'opacity-50 pointer-events-none': !link.url }">{{ link.label }}</Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
