<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Edit, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/composables/usePermission';
import { index, create } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { type BreadcrumbItem } from '@/types';
import AppPageContent from '@/components/AppPageContent.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatTurkeyDate } from '@/composables/useTurkeyDate';

const props = defineProps<{ movements: { data: Array<Record<string, unknown>>; links: Array<{ url: string | null; label: string }> }; warehouses: Array<Record<string, unknown>> }>();
const { t } = useI18n();
const { can } = usePermission();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.stockMovements'), href: index.url() }];

const deleteMovementId = ref<number | null>(null);
const form = useForm({});

const confirmDelete = (id: number) => {
    deleteMovementId.value = id;
};

const cancelDelete = () => {
    deleteMovementId.value = null;
};

const deleteMovement = async (id: number) => {
    form.delete(`/warehouse/stock-movements/${id}`, {
        onSuccess: () => {
            deleteMovementId.value = null;
        },
        onError: (errors) => {
            console.error('Delete error:', errors);
        },
    });
};
</script>

<template>
    <Head :title="t('stockMovements.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-row items-center justify-between gap-4 p-4 md:p-6 pb-0">
                    <div>
                        <h1 class="text-xl font-semibold">{{ t('nav.movements') }}</h1>
                        <p class="text-sm text-muted-foreground">{{ t('stockMovements.title') }}</p>
                    </div>
                    <Link v-if="can('stock.in')" :href="create.url()"><Button><Plus class="mr-2 h-4 w-4" />{{ t('nav.input') }}</Button></Link>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4 overflow-y-auto">
                <Table class="bg-transparent">
                        <TableHeader>
                            <TableRow class="border-b border-border">
                                <TableHead class="text-muted-foreground">{{ t('common.date') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('stock.product') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('stockMovements.type') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('common.quantity') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('stock.warehouse') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('activityLogs.user') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('common.actions') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="m in movements.data" :key="(m as any).id" class="border-b border-border hover:bg-muted/30">
                                <TableCell class="whitespace-nowrap">{{ formatTurkeyDate((m as any).movement_date) }}</TableCell>
                                <TableCell>{{ (m as any).product?.[locale] ?? '-' }}</TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="(m as any).type === 'in' ? 'success' : (m as any).type === 'out' ? 'destructive' : 'secondary'"
                                    >
                                        {{ (m as any).type === 'in' ? t('nav.input') : (m as any).type === 'out' ? t('nav.output') : (m as any).type }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ (m as any).quantity }}</TableCell>
                                <TableCell>{{ (m as any).warehouse?.[locale] ?? '-' }}</TableCell>
                                <TableCell>{{ (m as any).user?.name ?? '-' }}</TableCell>
                                <TableCell class="flex gap-2">
                                    <Link v-if="can('stock.movements.edit')" :href="`/warehouse/stock-movements/${(m as any).id}/edit`">
                                        <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button v-if="can('stock.movements.delete')" variant="ghost" size="sm" class="h-8 w-8 p-0 text-destructive hover:text-destructive" @click="confirmDelete((m as any).id)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                <!-- Delete Confirmation Dialog -->
                <div v-if="deleteMovementId !== null" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div class="bg-background rounded-lg shadow-lg p-6 max-w-sm mx-auto">
                        <h2 class="text-lg font-semibold mb-2">{{ t('common.confirmDelete') }}</h2>
                        <p class="text-sm text-muted-foreground mb-6">{{ t('common.deleteWarning') }}</p>
                        <div class="flex gap-3 justify-end">
                            <Button variant="outline" @click="cancelDelete">{{ t('common.cancel') }}</Button>
                            <Button variant="destructive" @click="deleteMovement(deleteMovementId)" :disabled="form.processing">
                                {{ t('common.delete') }}
                            </Button>
                        </div>
                    </div>
                </div>
                <Pagination v-if="movements.links?.length" :links="movements.links" class="mt-4" />
            </div>
        </AppPageContent>
    </AppLayout>
</template>
