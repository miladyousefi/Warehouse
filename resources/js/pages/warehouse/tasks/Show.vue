<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

const { t } = useI18n();
const props = defineProps<{ task: Record<string, any> }>();
</script>

<template>
    <Head :title="props.task.title" />
    <AppLayout :breadcrumbs="[{ title: t('nav.tasks'), href: '/warehouse/tasks' }, { title: props.task.title }]">
        <div class="p-4 md:p-6">
            <Card>
                <CardHeader>
                    <CardTitle>{{ props.task.title }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div><strong>{{ t('common.description') }}:</strong> {{ props.task.description }}</div>
                        <div><strong>{{ t('common.status') }}:</strong> {{ props.task.status }}</div>
                        <div><strong>{{ t('common.priority') }}:</strong> {{ props.task.priority }}</div>
                        <div><strong>{{ t('common.due_date') }}:</strong> {{ props.task.due_date || '-' }}</div>
                        <div><strong>{{ t('tasks.assigned_to') || 'Assigned To' }}:</strong> {{ props.task.assignee?.name ?? '-' }}</div>
                    </div>
                    <div class="mt-4">
                        <Link :href="`/warehouse/tasks/${props.task.id}/edit`"><Button>{{ t('common.edit') }}</Button></Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
