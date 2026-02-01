<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import Pagination from '@/components/Pagination.vue';
import { useI18n } from 'vue-i18n';
import { index as activityLogsIndex } from '@/actions/App/Http/Controllers/Warehouse/ActivityLogController';
import { type BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{
    logs: {
        data: Array<{
            id: number;
            action: string;
            description: string | null;
            subject_type: string | null;
            subject_id: number | null;
            created_at: string;
            user?: { id: number; name: string; email: string } | null;
        }>;
        links: Array<{ url: string | null; label: string }>;
    };
}>();

const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('activityLogs.title'), href: activityLogsIndex.url() }];

import { formatTurkeyDate } from '@/composables/useTurkeyDate';
</script>

<template>
    <Head :title="t('activityLogs.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-row items-center justify-between gap-4 p-4 md:p-6 pb-0">
                    <div>
                        <h1 class="text-xl font-semibold">{{ t('activityLogs.title') }}</h1>
                        <p class="text-sm text-muted-foreground">{{ t('activityLogs.description') }}</p>
                    </div>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4 overflow-y-auto">
                <Table class="bg-transparent">
                    <TableHeader>
                        <TableRow class="border-b border-border">
                            <TableHead class="text-muted-foreground">{{ t('common.date') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('activityLogs.action') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('activityLogs.description') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('activityLogs.user') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="log in logs.data" :key="log.id" class="border-b border-border hover:bg-muted/30">
                            <TableCell class="whitespace-nowrap">{{ formatTurkeyDate(log.created_at) }}</TableCell>
                            <TableCell><Badge variant="secondary">{{ log.action }}</Badge></TableCell>
                            <TableCell class="max-w-xs truncate">{{ log.description || '-' }}</TableCell>
                            <TableCell>{{ log.user?.name ?? '-' }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <Pagination v-if="logs.links?.length" :links="logs.links" class="mt-4" />
            </div>
        </AppPageContent>
    </AppLayout>
</template>
