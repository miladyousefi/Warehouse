<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/composables/usePermission';
import { index, create } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{ movements: { data: Array<Record<string, unknown>>; links: Array<{ url: string | null; label: string }> }; warehouses: Array<Record<string, unknown>> }>();
const { t } = useI18n();
const { can } = usePermission();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.stockMovements'), href: index.url() }];
</script>

<template>
    <Head :title="t('stockMovements.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div><CardTitle>{{ t('stockMovements.title') }}</CardTitle><CardDescription>{{ t('common.view') }}</CardDescription></div>
                    <Link v-if="can('stock.in')" :href="create.url()"><Button><Plus class="mr-2 h-4 w-4" />{{ t('stockMovements.addMovement') }}</Button></Link>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ t('common.date') }}</TableHead>
                                <TableHead>{{ t('stock.product') }}</TableHead>
                                <TableHead>{{ t('stockMovements.type') }}</TableHead>
                                <TableHead>{{ t('common.quantity') }}</TableHead>
                                <TableHead>{{ t('stock.warehouse') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="m in movements.data" :key="(m as any).id">
                                <TableCell>{{ (m as any).movement_date }}</TableCell>
                                <TableCell>{{ (m as any).product?.[locale] ?? '-' }}</TableCell>
                                <TableCell><Badge>{{ (m as any).type }}</Badge></TableCell>
                                <TableCell>{{ (m as any).quantity }}</TableCell>
                                <TableCell>{{ (m as any).warehouse?.[locale] ?? '-' }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-if="movements.links?.length" class="mt-4 flex gap-2">
                        <Link v-for="(link, i) in movements.links" :key="i" :href="link.url || '#'" class="rounded border px-3 py-1 text-sm" :class="{ 'opacity-50 pointer-events-none': !link.url }">{{ link.label }}</Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
