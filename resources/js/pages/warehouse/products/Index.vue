<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Eye } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import Pagination from '@/components/Pagination.vue';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';
import { usePermission } from '@/composables/usePermission';
import { index, create } from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

interface Props {
    products: { data: Array<Record<string, unknown>>; links: Array<{ url: string | null; label: string }> };
    categories: Array<Record<string, unknown>>;
}

const props = defineProps<Props>();
const { t } = useI18n();
const { can } = usePermission();
const search = ref('');
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.products'), href: index.url() }];

function doSearch() {
    router.get(index.url(), { search: search.value }, { preserveState: true });
}

function destroy(id: number): void {
    if (confirm(t('common.delete') + '?')) router.delete(`/warehouse/products/${id}`);
}
</script>

<template>
    <Head :title="t('products.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-row items-center justify-between gap-4 p-4 md:p-6 pb-0">
                    <div>
                        <h1 class="text-xl font-semibold">{{ t('products.title') }}</h1>
                        <p class="text-sm text-muted-foreground">{{ t('common.view') }}</p>
                    </div>
                    <div class="flex gap-2">
                        <div class="flex gap-2">
                            <Input v-model="search" :placeholder="t('common.search')" class="max-w-sm" @keyup.enter="doSearch" />
                            <Button variant="secondary" @click="doSearch">{{ t('common.search') }}</Button>
                        </div>
                        <Link v-if="can('products.create')" :href="create.url()">
                            <Button><Plus class="mr-2 h-4 w-4" />{{ t('products.createProduct') }}</Button>
                        </Link>
                    </div>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4 overflow-y-auto">
                <Table class="bg-transparent">
                    <TableHeader>
                        <TableRow class="border-b border-border hover:bg-muted/30">
                            <TableHead class="text-muted-foreground">{{ t('common.name') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('products.sku') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('products.category') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('products.unit') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.status') }}</TableHead>
                            <TableHead class="w-[80px] text-muted-foreground">{{ t('common.actions') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="p in products.data" :key="p.id" class="border-b border-border hover:bg-muted/30">
                            <TableCell class="font-medium">
                                <Link :href="`/warehouse/products/${p.id}`" class="hover:underline">{{ (p as any)[locale] || p.name_tr }}</Link>
                            </TableCell>
                            <TableCell>{{ p.sku || '-' }}</TableCell>
                            <TableCell>{{ p.category ? (p.category as any)[locale] : '-' }}</TableCell>
                            <TableCell>{{ p.unit?.symbol ?? '-' }}</TableCell>
                            <TableCell>
                                <Badge :variant="p.is_active ? 'default' : 'secondary'">{{ p.is_active ? t('common.active') : t('common.inactive') }}</Badge>
                            </TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon">...</Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem as-child>
                                            <Link :href="`/warehouse/products/${p.id}`"><Eye class="mr-2 h-4 w-4" />{{ t('common.view') }}</Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-if="can('products.edit')" as-child>
                                            <Link :href="`/warehouse/products/${p.id}/edit`"><Pencil class="mr-2 h-4 w-4" />{{ t('common.edit') }}</Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-if="can('products.delete')" class="text-destructive" @click="destroy(p.id)">
                                            <Trash2 class="mr-2 h-4 w-4" />{{ t('common.delete') }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <Pagination v-if="products.links?.length" :links="products.links" class="mt-4" />
            </div>
        </AppPageContent>
    </AppLayout>
</template>
