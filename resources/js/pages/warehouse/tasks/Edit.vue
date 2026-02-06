<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();
const props = defineProps<{ task: Record<string, any>; users?: Array<Record<string, any>> }>();

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
                            <select id="status" v-model="form.status" class="w-full rounded-md border px-3 py-2">
                                <option value="pending">pending</option>
                                <option value="in_progress">in_progress</option>
                                <option value="completed">completed</option>
                                <option value="cancelled">cancelled</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="priority">{{ t('common.priority') }}</Label>
                            <select id="priority" v-model="form.priority" class="w-full rounded-md border px-3 py-2">
                                <option value="low">low</option>
                                <option value="medium">medium</option>
                                <option value="high">high</option>
                                <option value="critical">critical</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="due_date">{{ t('common.due_date') }}</Label>
                            <Input id="due_date" v-model="form.due_date" type="date" />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label for="assigned_to">{{ t('tasks.assignee') || 'Assignee' }}</Label>
                            <select id="assigned_to" v-model="form.assigned_to" class="w-full rounded-md border px-3 py-2">
                                <option value="">-</option>
                                <option v-for="u in props.users || []" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
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
