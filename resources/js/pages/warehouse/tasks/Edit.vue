<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();
const props = defineProps<{ task: Record<string, any>; users?: Array<Record<string, any>> }>();

const statusOptions = [
    { id: 'pending', label: 'pending' },
    { id: 'in_progress', label: 'in_progress' },
    { id: 'completed', label: 'completed' },
    { id: 'cancelled', label: 'cancelled' },
];

const priorityOptions = [
    { id: 'low', label: 'low' },
    { id: 'medium', label: 'medium' },
    { id: 'high', label: 'high' },
    { id: 'critical', label: 'critical' },
];

const userOptions = (props.users || []).map((u: any) => ({
    id: u.id,
    label: u.name,
}));

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.tasks'), href: '/warehouse/tasks' },
    { title: t('common.edit') },
];

const form = useForm({
    title: String(props.task.title ?? ''),
    description: String(props.task.description ?? ''),
    status: String(props.task.status ?? 'pending'),
    priority: String(props.task.priority ?? 'medium'),
    due_date: props.task.due_date ? String(props.task.due_date).split('T')[0] : '',
    assigned_to: props.task.assigned_to ?? '',
    color: props.task.color ?? '#ffffff',
});

function submit() {
    form.patch(`/warehouse/tasks/${props.task.id}`);
}
</script>

<template>
    <Head :title="t('nav.tasks')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-6">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('common.edit') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <Label for="title">{{ t('common.title') }}</Label>
                            <Input id="title" v-model="form.title" required />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label for="description">{{ t('common.description') }}</Label>
                            <Input id="description" v-model="form.description" />
                        </div>
                        <div class="space-y-2">
                            <Label for="status">{{ t('common.status') }}</Label>
                            <SearchableSelect :model-value="form.status" :options="statusOptions" @update:model-value="(v) => form.status = v" />
                        </div>
                        <div class="space-y-2">
                            <Label for="priority">{{ t('common.priority') }}</Label>
                            <SearchableSelect :model-value="form.priority" :options="priorityOptions" @update:model-value="(v) => form.priority = v" />
                        </div>
                        <div class="space-y-2">
                            <Label for="due_date">{{ t('common.due_date') }}</Label>
                            <Input id="due_date" v-model="form.due_date" type="date" />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label for="assigned_to">{{ t('tasks.assignee') || 'Assignee' }}</Label>
                            <SearchableSelect :model-value="form.assigned_to" :options="userOptions" :placeholder="t('common.select')" @update:model-value="(v) => form.assigned_to = v" />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label>{{ t('common.color') || 'Card Color' }}</Label>
                            <div class="flex flex-wrap gap-3 p-2 bg-slate-50 dark:bg-slate-900 rounded-lg border">
                                <button 
                                    v-for="color in ['#ffffff', '#fef3c7', '#dcfce7', '#dbeafe', '#f3e8ff', '#fee2e2', '#ffedd5']" 
                                    :key="color"
                                    type="button"
                                    class="w-8 h-8 rounded-full border-2 transition-all hover:scale-110"
                                    :style="{ backgroundColor: color }"
                                    :class="form.color === color ? 'border-primary ring-2 ring-primary/20 scale-110' : 'border-transparent shadow-sm'"
                                    @click="form.color = color"
                                ></button>
                            </div>
                        </div>
                        <div class="flex gap-2 md:col-span-2">
                            <Button type="submit" :disabled="form.processing">{{ t('common.save') }}</Button>
                            <Link :href="`/warehouse/tasks/${props.task.id}`"><Button type="button" variant="outline">{{ t('common.cancel') }}</Button></Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
