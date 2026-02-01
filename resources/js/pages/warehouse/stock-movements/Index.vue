<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
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

const props = defineProps<{ movements: { data: Array<Record<string, unknown>>; links: Array<{ url: string | null; label: string }> }; warehouses: Array<Record<string, unknown>> }>();
const { t } = useI18n();
const { can } = usePermission();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.stockMovements'), href: index.url() }];

import { formatTurkeyDate } from '@/composables/useTurkeyDate';
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
                            </TableRow>
                        </TableBody>
                    </Table>
                <Pagination v-if="movements.links?.length" :links="movements.links" class="mt-4" />
            </div>
        </AppPageContent>
    </AppLayout>
</template>
