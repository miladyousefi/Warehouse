<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Plus,
    MoreHorizontal,
    Calendar,
    Trash2,
    Pencil,
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';

const { t } = useI18n();

const props = defineProps<{ tasks: any; users?: Array<Record<string, any>> }>();

// Local state for optimistic updates
const localTasks = ref([...(props.tasks.data || props.tasks)]);

// Sync local state when props change (e.g. after navigation or refresh)
watch(
    () => props.tasks,
    (newTasks) => {
        localTasks.value = [...(newTasks.data || newTasks)];
    },
    { deep: true },
);

// Drag and Drop state
const draggedTask = ref<any>(null);
const createTaskOpen = ref(false);

const statusOptions = [
    { id: 'pending', label: t('tasks.pending') || 'pending' },
    { id: 'in_progress', label: t('tasks.in_progress') || 'in_progress' },
    { id: 'completed', label: t('tasks.completed') || 'completed' },
    { id: 'cancelled', label: t('tasks.cancelled') || 'cancelled' },
];

const priorityOptions = [
    { id: 'low', label: 'low' },
    { id: 'medium', label: 'medium' },
    { id: 'high', label: 'high' },
    { id: 'critical', label: 'critical' },
];

const userOptions = computed(() =>
    (props.users || []).map((u: any) => ({
        id: u.id,
        label: u.name,
    })),
);

const createForm = useForm({
    title: '',
    description: '',
    status: 'pending',
    priority: 'medium',
    due_date: '',
    assigned_to: '',
    color: '#ffffff',
});

const columns = [
    {
        id: 'pending',
        title: 'tasks.pending',
        color: 'bg-slate-100 dark:bg-slate-800',
    },
    {
        id: 'in_progress',
        title: 'tasks.in_progress',
        color: 'bg-blue-50 dark:bg-blue-900/20',
    },
    {
        id: 'completed',
        title: 'tasks.completed',
        color: 'bg-emerald-50 dark:bg-emerald-900/20',
    },
    {
        id: 'cancelled',
        title: 'tasks.cancelled',
        color: 'bg-rose-50 dark:bg-rose-900/20',
    },
];

const getTasksByStatus = (status: string) => {
    return localTasks.value.filter((task) => task.status === status);
};

// Drag handlers
const onDragStart = (event: DragEvent, task: any) => {
    draggedTask.value = task;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.dropEffect = 'move';
    }
};

const onDrop = (event: DragEvent, status: string) => {
    event.preventDefault();
    if (!draggedTask.value) return;

    if (draggedTask.value.status !== status) {
        const taskId = draggedTask.value.id;

        // Optimistic update
        const taskIndex = localTasks.value.findIndex((t) => t.id === taskId);
        if (taskIndex !== -1) {
            localTasks.value[taskIndex].status = status;
        }

        // Backend update
        axios
            .patch(`/warehouse/tasks/${taskId}/status`, {
                status: status,
            })
            .catch(() => {
                // Revert on error
                if (taskIndex !== -1) {
                    localTasks.value[taskIndex].status =
                        draggedTask.value.status;
                }
            });
    }
    draggedTask.value = null;
};

// Actions
const updateColor = (task: any, color: string) => {
    // Keep old color for revert
    const oldColor = task.color;

    // Optimistic update
    const taskIndex = localTasks.value.findIndex((t) => t.id === task.id);
    if (taskIndex !== -1) {
        localTasks.value[taskIndex].color = color;
    }

    axios
        .patch(`/warehouse/tasks/${task.id}/color`, { color })
        .catch((error) => {
            // Revert on error
            if (taskIndex !== -1) {
                localTasks.value[taskIndex].color = oldColor;
            }
            console.error('Failed to update color:', error);
            // Optional: alert user
            alert(t('common.error') || 'Failed to update color');
        });
};

const deleteTask = (task: any) => {
    if (confirm(t('common.confirmDelete'))) {
        router.delete(`/warehouse/tasks/${task.id}`, {
            onSuccess: () => {
                localTasks.value = localTasks.value.filter(
                    (t) => t.id !== task.id,
                );
            },
        });
    }
};

// Available colors for cards
const cardColors = [
    '#ffffff', // White
    '#fef3c7', // Yellow
    '#dcfce7', // Green
    '#dbeafe', // Blue
    '#f3e8ff', // Purple
    '#fee2e2', // Red
    '#ffedd5', // Orange
];

function submitCreateTask() {
    createForm.post('/warehouse/tasks', {
        preserveScroll: true,
        onSuccess: () => {
            createTaskOpen.value = false;
            createForm.reset();
            router.reload({ only: ['tasks'] });
        },
    });
}
</script>

<template>
    <Head :title="t('nav.tasks')" />
    <AppLayout>
        <div
            class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6"
        >
            <!-- Header -->
            <div
                class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
            >
                <div>
                    <h2
                        class="text-3xl font-bold tracking-tight text-slate-800 dark:text-slate-100"
                    >
                        {{ t('nav.tasks') }}
                    </h2>
                    <p class="mt-1 text-muted-foreground">
                        {{
                            t(
                                'common.dragDropHint',
                                'Drag and drop cards to update status',
                            )
                        }}
                    </p>
                </div>
                <Button
                    class="shadow-lg shadow-primary/20 transition-all hover:shadow-primary/30"
                    @click="createTaskOpen = true"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    {{ t('common.create') }}
                </Button>
            </div>

            <Dialog
                :open="createTaskOpen"
                @update:open="(v) => (createTaskOpen = v)"
            >
                <DialogContent class="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle
                            >{{ t('common.create') }}
                            {{ t('nav.tasks') }}</DialogTitle
                        >
                        <DialogDescription>
                            {{ t('common.fillRequired') }}
                        </DialogDescription>
                    </DialogHeader>

                    <form
                        @submit.prevent="submitCreateTask"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="space-y-2 md:col-span-2">
                            <Label for="task-title">{{
                                t('common.title')
                            }}</Label>
                            <Input
                                id="task-title"
                                v-model="createForm.title"
                                required
                            />
                            <p
                                v-if="createForm.errors.title"
                                class="text-xs text-destructive"
                            >
                                {{ createForm.errors.title }}
                            </p>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="task-description">{{
                                t('common.description')
                            }}</Label>
                            <textarea
                                id="task-description"
                                v-model="createForm.description"
                                class="min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                            <p
                                v-if="createForm.errors.description"
                                class="text-xs text-destructive"
                            >
                                {{ createForm.errors.description }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>{{ t('common.status') }}</Label>
                            <SearchableSelect
                                :model-value="createForm.status"
                                :options="statusOptions"
                                @update:model-value="
                                    (v) => (createForm.status = v)
                                "
                            />
                            <p
                                v-if="createForm.errors.status"
                                class="text-xs text-destructive"
                            >
                                {{ createForm.errors.status }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>{{ t('common.priority') }}</Label>
                            <SearchableSelect
                                :model-value="createForm.priority"
                                :options="priorityOptions"
                                @update:model-value="
                                    (v) => (createForm.priority = v)
                                "
                            />
                            <p
                                v-if="createForm.errors.priority"
                                class="text-xs text-destructive"
                            >
                                {{ createForm.errors.priority }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="task-due-date">{{
                                t('common.due_date')
                            }}</Label>
                            <Input
                                id="task-due-date"
                                v-model="createForm.due_date"
                                type="date"
                            />
                            <p
                                v-if="createForm.errors.due_date"
                                class="text-xs text-destructive"
                            >
                                {{ createForm.errors.due_date }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>{{ t('common.assignee') }}</Label>
                            <SearchableSelect
                                :model-value="createForm.assigned_to"
                                :options="userOptions"
                                :placeholder="t('common.select')"
                                @update:model-value="
                                    (v) => (createForm.assigned_to = v)
                                "
                            />
                            <p
                                v-if="createForm.errors.assigned_to"
                                class="text-xs text-destructive"
                            >
                                {{ createForm.errors.assigned_to }}
                            </p>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label>{{ t('common.color') }}</Label>
                            <div
                                class="flex flex-wrap gap-2 rounded-lg border border-amber-200/40 bg-transparent p-2 backdrop-blur dark:border-white/10"
                            >
                                <button
                                    v-for="color in cardColors"
                                    :key="`create-${color}`"
                                    type="button"
                                    class="h-7 w-7 rounded-full border-2 transition-all hover:scale-110"
                                    :style="{ backgroundColor: color }"
                                    :class="
                                        createForm.color === color
                                            ? 'scale-110 border-primary ring-2 ring-primary/20'
                                            : 'border-transparent shadow-sm'
                                    "
                                    @click="createForm.color = color"
                                />
                            </div>
                            <p
                                v-if="createForm.errors.color"
                                class="text-xs text-destructive"
                            >
                                {{ createForm.errors.color }}
                            </p>
                        </div>

                        <DialogFooter class="md:col-span-2">
                            <Button
                                type="button"
                                variant="outline"
                                @click="createTaskOpen = false"
                                >{{ t('common.cancel') }}</Button
                            >
                            <Button
                                type="submit"
                                :disabled="createForm.processing"
                                >{{ t('common.save') }}</Button
                            >
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Board -->
            <div class="-mx-4 flex-1 overflow-x-auto px-4 pb-4 md:mx-0 md:px-0">
                <div class="flex h-full min-w-[1200px] gap-6 md:min-w-0">
                    <div
                        v-for="column in columns"
                        :key="column.id"
                        class="flex flex-1 flex-col rounded-xl border border-amber-200/40 bg-white/60 backdrop-blur shadow-[0_14px_45px_rgba(28,21,16,0.06)] dark:border-white/10 dark:bg-white/5"
                        @dragover.prevent
                        @drop="onDrop($event, column.id)"
                    >
                        <!-- Column Header -->
                        <div
                            class="sticky top-0 z-10 flex items-center justify-between rounded-t-xl bg-inherit p-4 md:static"
                        >
                            <h3
                                class="flex items-center gap-2 font-bold text-slate-700 dark:text-slate-200"
                            >
                                <span
                                    :class="`h-2.5 w-2.5 rounded-full ring-2 ring-offset-2 ring-offset-slate-100 dark:ring-offset-slate-900 ${
                                        column.id === 'completed'
                                            ? 'bg-emerald-500 ring-emerald-200'
                                            : column.id === 'in_progress'
                                              ? 'bg-blue-500 ring-blue-200'
                                              : column.id === 'cancelled'
                                                ? 'bg-rose-500 ring-rose-200'
                                                : 'bg-slate-400 ring-slate-200'
                                    }`"
                                ></span>
                                {{ t(column.title) }}
                            </h3>
                            <Badge
                                variant="secondary"
                                class="bg-white/70 font-mono text-xs shadow-sm backdrop-blur dark:bg-white/10"
                            >
                                {{ getTasksByStatus(column.id).length }}
                            </Badge>
                        </div>

                        <!-- Column Content -->
                        <div
                            class="scrollbar-thin flex min-h-[150px] flex-1 flex-col gap-3 overflow-y-auto p-3"
                        >
                            <div
                                v-for="task in getTasksByStatus(column.id)"
                                :key="task.id"
                                draggable="true"
                                @dragstart="onDragStart($event, task)"
                                class="group relative flex cursor-grab flex-col gap-3 rounded-xl border p-4 shadow-sm backdrop-blur transition-all hover:-translate-y-1 hover:shadow-md active:cursor-grabbing"
                                :style="{
                                    backgroundColor:
                                        task.color === '#ffffff'
                                            ? 'rgba(255,255,255,0.65)'
                                            : task.color || 'rgba(255,255,255,0.65)',
                                    borderColor:
                                        task.color && task.color !== '#ffffff'
                                            ? 'rgba(0,0,0,0.05)'
                                            : '',
                                }"
                            >
                                <!-- Card Header -->
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <h4
                                        class="line-clamp-3 text-sm leading-snug font-bold text-slate-800/90"
                                    >
                                        {{ task.title }}
                                    </h4>

                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="-mt-2 -mr-2 h-6 w-6 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-black/5"
                                                :class="{
                                                    'opacity-100': false,
                                                }"
                                            >
                                                <!-- Always keep space but hide -->
                                                <MoreHorizontal
                                                    class="h-4 w-4"
                                                />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent
                                            align="end"
                                            class="w-40"
                                        >
                                            <DropdownMenuItem as-child>
                                                <Link
                                                    :href="`/warehouse/tasks/${task.id}/edit`"
                                                >
                                                    <Pencil
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    {{ t('common.edit') }}
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                @click="deleteTask(task)"
                                                class="text-destructive"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                {{ t('common.delete') }}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>

                                <!-- Description -->
                                <p
                                    v-if="task.description"
                                    class="line-clamp-3 text-xs leading-relaxed text-muted-foreground/80"
                                >
                                    {{ task.description }}
                                </p>

                                <!-- Footer Info -->
                                <div
                                    class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-black/5 pt-3 text-xs text-muted-foreground/70 dark:border-white/5"
                                >
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <!-- Priority -->
                                        <Badge
                                            variant="outline"
                                            class="h-5 px-2 py-0 text-[10px] font-bold capitalize"
                                            :class="{
                                                'border-rose-200 bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400':
                                                    task.priority ===
                                                    'critical',
                                                'border-orange-200 bg-orange-50 text-orange-700 dark:bg-orange-950/30 dark:text-orange-400':
                                                    task.priority === 'high',
                                                'border-blue-200 bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400':
                                                    task.priority === 'medium',
                                                'border-slate-200 bg-slate-50 text-slate-600 dark:bg-slate-900/30 dark:text-slate-400':
                                                    task.priority === 'low',
                                            }"
                                        >
                                            {{ task.priority }}
                                        </Badge>

                                        <!-- Assignee -->
                                        <div
                                            v-if="task.assignee"
                                            class="flex items-center gap-1.5"
                                            title="Assignee"
                                        >
                                            <div
                                                class="flex h-5 w-5 items-center justify-center overflow-hidden rounded-full bg-black/10 text-[9px] font-bold uppercase ring-1 ring-black/5"
                                            >
                                                <img
                                                    v-if="
                                                        task.assignee.avatar_url
                                                    "
                                                    :src="
                                                        task.assignee.avatar_url
                                                    "
                                                    class="h-full w-full object-cover"
                                                />
                                                <span v-else>{{
                                                    task.assignee.name.charAt(0)
                                                }}</span>
                                            </div>
                                            <span
                                                class="max-w-[60px] truncate"
                                                >{{ task.assignee.name }}</span
                                            >
                                        </div>

                                        <!-- Due Date -->
                                        <div
                                            v-if="task.due_date"
                                            class="flex items-center gap-1.5 rounded-md px-1.5 py-0.5"
                                            :class="{
                                                'bg-rose-100 font-medium text-rose-700':
                                                    new Date(task.due_date) <
                                                        new Date() &&
                                                    task.status !== 'completed',
                                                'bg-black/5': !(
                                                    new Date(task.due_date) <
                                                        new Date() &&
                                                    task.status !== 'completed'
                                                ),
                                            }"
                                        >
                                            <Calendar class="h-3 w-3" />
                                            <span>{{
                                                new Date(
                                                    task.due_date,
                                                ).toLocaleDateString(
                                                    undefined,
                                                    {
                                                        month: 'short',
                                                        day: 'numeric',
                                                    },
                                                )
                                            }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Always Visible Color Picker on bottom right -->
                                <div
                                    class="absolute right-3 bottom-3 flex opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <div
                                        class="flex -space-x-1.5 transition-all hover:space-x-1"
                                    >
                                        <button
                                            v-for="color in cardColors"
                                            :key="color"
                                            class="h-4 w-4 rounded-full border border-black/10 shadow-sm transition-all hover:z-10 hover:scale-125 focus:ring-2 focus:ring-primary focus:ring-offset-1 focus:outline-none"
                                            :style="{ backgroundColor: color }"
                                            @click.stop="
                                                updateColor(task, color)
                                            "
                                            :title="color"
                                        ></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background-color: rgba(0, 0, 0, 0.2);
}
</style>
