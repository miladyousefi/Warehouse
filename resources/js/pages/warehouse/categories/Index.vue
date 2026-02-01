<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import Pagination from '@/components/Pagination.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/composables/usePermission';
import { index, create } from '@/actions/App/Http/Controllers/Warehouse/ProductCategoryController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

const props = defineProps<{ categories: { data: Array<Record<string, unknown>>; links: Array<{ url: string | null; label: string }> } }>();
const { t } = useI18n();
const { can } = usePermission();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.categories'), href: index.url() }];

function destroy(id: number) {
    if (confirm(t('common.delete') + '?')) router.delete(`/warehouse/categories/${id}`);
}
</script>

<template>
    <Head :title="t('categories.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-row items-center justify-between gap-4 p-4 md:p-6 pb-0">
                    <div>
                        <h1 class="text-xl font-semibold">{{ t('categories.title') }}</h1>
                        <p class="text-sm text-muted-foreground">{{ t('common.view') }}</p>
                    </div>
                    <Link v-if="can('categories.create')" :href="create.url()"><Button><Plus class="mr-2 h-4 w-4" />{{ t('common.add') }}</Button></Link>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4 overflow-y-auto">
                <Table class="bg-transparent">
                    <TableHeader>
                        <TableRow class="border-b border-border hover:bg-muted/30">
                            <TableHead class="text-muted-foreground">{{ t('common.name') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('categories.productCount') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.status') }}</TableHead>
                            <TableHead class="w-[80px] text-muted-foreground">{{ t('common.actions') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="c in categories.data" :key="(c as any).id" class="border-b border-border hover:bg-muted/30">
                            <TableCell class="font-medium">{{ (c as any)[locale] }}</TableCell>
                            <TableCell>{{ (c as any).products_count ?? 0 }}</TableCell>
                            <TableCell><Badge :variant="(c as any).is_active ? 'default' : 'secondary'">{{ (c as any).is_active ? t('common.active') : t('common.inactive') }}</Badge></TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child><Button variant="ghost" size="icon">...</Button></DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem v-if="can('categories.edit')" as-child><Link :href="`/warehouse/categories/${(c as any).id}/edit`"><Pencil class="mr-2 h-4 w-4" />{{ t('common.edit') }}</Link></DropdownMenuItem>
                                        <DropdownMenuItem v-if="can('categories.delete')" class="text-destructive" @click="destroy((c as any).id)"><Trash2 class="mr-2 h-4 w-4" />{{ t('common.delete') }}</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <Pagination v-if="categories.links?.length" :links="categories.links" class="mt-4" />
            </div>
        </AppPageContent>
    </AppLayout>
</template>
