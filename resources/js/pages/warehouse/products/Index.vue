<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';
import { usePermission } from '@/composables/usePermission';
import { index, create } from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
interface Props {
    products: {
        data: Array<Record<string, unknown>>;
        links: Array<{ url: string | null; label: string }>;
    };
    categories: Array<Record<string, unknown>>;
}

const props = defineProps<Props>();
const { t } = useI18n();
const { can } = usePermission();
const search = ref('');
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.products'), href: index.url() },
];

function doSearch() {
    router.get(index.url(), { search: search.value }, { preserveState: true });
}

function destroy(id: number): void {
    if (confirm(t('common.delete') + '?')) {
        router.delete(`/warehouse/products/${id}`);
    }
}
</script>

<template>
    <Head :title="t('products.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <div>
                        <CardTitle>{{ t('products.title') }}</CardTitle>
                        <CardDescription>{{ t('common.view') }}</CardDescription>
                    </div>
                    <Link v-if="can('products.create')" :href="create.url()">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            {{ t('products.createProduct') }}
                        </Button>
                    </Link>
                </CardHeader>
                <CardContent>
                    <div class="mb-4 flex gap-2">
                        <Input
                            v-model="search"
                            :placeholder="t('common.search')"
                            class="max-w-sm"
                            @keyup.enter="doSearch"
                        />
                        <Button variant="secondary" @click="doSearch">
                            {{ t('common.search') }}
                        </Button>
                    </div>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ t('common.name') }}</TableHead>
                                <TableHead>{{ t('products.sku') }}</TableHead>
                                <TableHead>{{ t('products.category') }}</TableHead>
                                <TableHead>{{ t('products.unit') }}</TableHead>
                                <TableHead>{{ t('common.status') }}</TableHead>
                                <TableHead class="w-[80px]">{{ t('common.actions') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="p in products.data" :key="p.id">
                                <TableCell class="font-medium">
                                    {{ (p as any)[locale] || p.name_tr }}
                                </TableCell>
                                <TableCell>{{ p.sku || '-' }}</TableCell>
                                <TableCell>
                                    {{ p.category ? (p.category as any)[locale] : '-' }}
                                </TableCell>
                                <TableCell>{{ p.unit?.symbol ?? '-' }}</TableCell>
                                <TableCell>
                                    <Badge :variant="p.is_active ? 'default' : 'secondary'">
                                        {{ p.is_active ? t('common.active') : t('common.inactive') }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon">
                                                ...
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem v-if="can('products.edit')" as-child>
                                                <Link :href="`/warehouse/products/${p.id}/edit`">
                                                    <Pencil class="mr-2 h-4 w-4" />
                                                    {{ t('common.edit') }}
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="can('products.delete')"
                                                class="text-destructive"
                                                @click="destroy(p.id)"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                {{ t('common.delete') }}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-if="products.links?.length" class="mt-4 flex gap-2">
                        <Link
                            v-for="(link, i) in products.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="rounded border px-3 py-1 text-sm"
                            :class="{ 'opacity-50 pointer-events-none': !link.url }"
                        >
                            {{ link.label }}
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
