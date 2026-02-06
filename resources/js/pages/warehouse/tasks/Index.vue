<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { 
    Plus, 
    MoreHorizontal, 
    Calendar, 
    User, 
    Trash2, 
    Pencil,
    Palette
} from 'lucide-vue-next';

const { t } = useI18n();

const props = defineProps<{ tasks: any }>();

// Local state for optimistic updates
const localTasks = ref([...(props.tasks.data || props.tasks)]);

// Sync local state when props change (e.g. after navigation or refresh)
watch(() => props.tasks, (newTasks) => {
    localTasks.value = [...(newTasks.data || newTasks)];
}, { deep: true });

// Drag and Drop state
const draggedTask = ref<any>(null);

const columns = [
    { id: 'pending', title: 'tasks.pending', color: 'bg-slate-100 dark:bg-slate-800' },
    { id: 'in_progress', title: 'tasks.in_progress', color: 'bg-blue-50 dark:bg-blue-900/20' },
    { id: 'completed', title: 'tasks.completed', color: 'bg-emerald-50 dark:bg-emerald-900/20' },
    { id: 'cancelled', title: 'tasks.cancelled', color: 'bg-rose-50 dark:bg-rose-900/20' },
];

const getTasksByStatus = (status: string) => {
    return localTasks.value.filter(task => task.status === status);
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
        const taskIndex = localTasks.value.findIndex(t => t.id === taskId);
        if (taskIndex !== -1) {
            localTasks.value[taskIndex].status = status;
        }

        // Backend update
        axios.patch(`/warehouse/tasks/${taskId}/status`, {
            status: status
        }).catch(() => {
            // Revert on error
            if (taskIndex !== -1) {
                localTasks.value[taskIndex].status = draggedTask.value.status;
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
    const taskIndex = localTasks.value.findIndex(t => t.id === task.id);
    if (taskIndex !== -1) {
        localTasks.value[taskIndex].color = color;
    }

    axios.patch(`/warehouse/tasks/${task.id}/color`, { color })
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
                localTasks.value = localTasks.value.filter(t => t.id !== task.id);
            }
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

const getContrastColor = (hexcolor: string) => {
    // If no color or invalid, default to dark
    if (!hexcolor || hexcolor === '#ffffff') return 'text-slate-900';
    return 'text-slate-900'; // Most pastel colors work well with dark text
};

</script>

<template>
    <Head :title="t('nav.tasks')" />
    <AppLayout>
        <div class="h-full flex flex-col p-4 md:p-6 gap-6 bg-slate-50 dark:bg-slate-950">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-800 dark:text-slate-100">{{ t('nav.tasks') }}</h2>
                    <p class="text-muted-foreground mt-1">
                        {{ t('common.dragDropHint', 'Drag and drop cards to update status') }}
                    </p>
                </div>
                <Link href="/warehouse/tasks/create">
                    <Button class="shadow-lg shadow-primary/20 hover:shadow-primary/30 transition-all">
                        <Plus class="mr-2 h-4 w-4" />
                        {{ t('common.create') }}
                    </Button>
                </Link>
            </div>

            <!-- Board -->
            <div class="flex-1 overflow-x-auto pb-4 -mx-4 px-4 md:mx-0 md:px-0">
                <div class="flex h-full gap-6 min-w-[1200px] md:min-w-0">
                    <div 
                        v-for="column in columns" 
                        :key="column.id"
                        class="flex-1 flex flex-col rounded-xl bg-slate-100/50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-800/60 backdrop-blur-sm"
                        @dragover.prevent
                        @drop="onDrop($event, column.id)"
                    >
                        <!-- Column Header -->
                        <div class="p-4 flex items-center justify-between sticky top-0 md:static z-10 bg-inherit rounded-t-xl">
                            <h3 class="font-bold flex items-center gap-2 text-slate-700 dark:text-slate-200">
                                <span :class="`w-2.5 h-2.5 rounded-full ring-2 ring-offset-2 ring-offset-slate-100 dark:ring-offset-slate-900 ${
                                    column.id === 'completed' ? 'bg-emerald-500 ring-emerald-200' : 
                                    column.id === 'in_progress' ? 'bg-blue-500 ring-blue-200' :
                                    column.id === 'cancelled' ? 'bg-rose-500 ring-rose-200' :
                                    'bg-slate-400 ring-slate-200'
                                }`"></span>
                                {{ t(column.title) }}
                            </h3>
                            <Badge variant="secondary" class="font-mono text-xs bg-white dark:bg-slate-800 shadow-sm">
                                {{ getTasksByStatus(column.id).length }}
                            </Badge>
                        </div>

                        <!-- Column Content -->
                        <div class="flex-1 p-3 flex flex-col gap-3 overflow-y-auto min-h-[150px] scrollbar-thin">
                            <div
                                v-for="task in getTasksByStatus(column.id)"
                                :key="task.id"
                                draggable="true"
                                @dragstart="onDragStart($event, task)"
                                class="group relative rounded-xl border shadow-sm transition-all hover:shadow-md hover:-translate-y-1 cursor-grab active:cursor-grabbing p-4 flex flex-col gap-3"
                                :style="{ 
                                    backgroundColor: task.color || '#ffffff',
                                    borderColor: task.color && task.color !== '#ffffff' ? 'rgba(0,0,0,0.05)' : '' 
                                }"
                            >
                                <!-- Card Header -->
                                <div class="flex items-start justify-between gap-3">
                                    <h4 class="font-bold text-sm leading-snug line-clamp-3 text-slate-800/90">{{ task.title }}</h4>
                                    
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon" class="h-6 w-6 -mr-2 -mt-2 opacity-0 group-hover:opacity-100 transition-opacity hover:bg-black/5" :class="{'opacity-100': false}"> <!-- Always keep space but hide -->
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="w-40">
                                            <DropdownMenuItem as-child>
                                                <Link :href="`/warehouse/tasks/${task.id}/edit`">
                                                    <Pencil class="mr-2 h-4 w-4" />
                                                    {{ t('common.edit') }}
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="deleteTask(task)" class="text-destructive">
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                {{ t('common.delete') }}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>

                                <!-- Description -->
                                <p v-if="task.description" class="text-xs text-muted-foreground/80 line-clamp-3 leading-relaxed">
                                    {{ task.description }}
                                </p>

                                <!-- Footer Info -->
                                <div class="mt-auto pt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground/70 border-t border-black/5 dark:border-white/5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <!-- Priority -->
                                        <Badge 
                                            variant="outline" 
                                            class="capitalize py-0 h-5 px-2 text-[10px] font-bold"
                                            :class="{
                                                'border-rose-200 bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400': task.priority === 'critical',
                                                'border-orange-200 bg-orange-50 text-orange-700 dark:bg-orange-950/30 dark:text-orange-400': task.priority === 'high',
                                                'border-blue-200 bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400': task.priority === 'medium',
                                                'border-slate-200 bg-slate-50 text-slate-600 dark:bg-slate-900/30 dark:text-slate-400': task.priority === 'low'
                                            }"
                                        >
                                            {{ task.priority }}
                                        </Badge>

                                        <!-- Assignee -->
                                        <div v-if="task.assignee" class="flex items-center gap-1.5" title="Assignee">
                                            <div class="w-5 h-5 rounded-full bg-black/10 flex items-center justify-center text-[9px] font-bold uppercase overflow-hidden ring-1 ring-black/5">
                                                <img v-if="task.assignee.avatar_url" :src="task.assignee.avatar_url" class="w-full h-full object-cover">
                                                <span v-else>{{ task.assignee.name.charAt(0) }}</span>
                                            </div>
                                            <span class="max-w-[60px] truncate">{{ task.assignee.name }}</span>
                                        </div>
                                        
                                        <!-- Due Date -->
                                        <div 
                                            v-if="task.due_date" 
                                            class="flex items-center gap-1.5 px-1.5 py-0.5 rounded-md"
                                            :class="{
                                                'bg-rose-100 text-rose-700 font-medium': new Date(task.due_date) < new Date() && task.status !== 'completed',
                                                'bg-black/5': !((new Date(task.due_date) < new Date()) && task.status !== 'completed')
                                            }"
                                        >
                                            <Calendar class="h-3 w-3" />
                                            <span>{{ new Date(task.due_date).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Always Visible Color Picker on bottom right -->
                                <div class="absolute bottom-3 right-3 flex group-hover:opacity-100 opacity-0 transition-opacity">
                                    <div class="flex -space-x-1.5 hover:space-x-1 transition-all">
                                        <button 
                                            v-for="color in cardColors" 
                                            :key="color"
                                            class="w-4 h-4 rounded-full border border-black/10 shadow-sm hover:scale-125 hover:z-10 transition-all focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary"
                                            :style="{ backgroundColor: color }"
                                            @click.stop="updateColor(task, color)"
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
