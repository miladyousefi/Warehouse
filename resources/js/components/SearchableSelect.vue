<script setup lang="ts">
import { Search, X } from 'lucide-vue-next';
import { computed, ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import type { HTMLAttributes } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface Props {
    modelValue: string | number | null;
    options: Array<{ id: string | number; label: string; [key: string]: any }>;
    placeholder?: string;
    searchable?: boolean;
    clearable?: boolean;
    disabled?: boolean;
    triggerClass?: HTMLAttributes['class'];
    searchInputClass?: HTMLAttributes['class'];
    panelClass?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Select...',
    searchable: true,
    clearable: true,
    disabled: false,
    triggerClass: undefined,
    searchInputClass: undefined,
    panelClass: undefined,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number | null];
}>();

const searchInput = ref('');
const isOpen = ref(false);
const dropdownRef = ref<HTMLDivElement | null>(null);
const panelRef = ref<HTMLDivElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);
const panelStyle = ref<Record<string, string>>({});

const filteredOptions = computed(() => {
    if (!props.searchable || !searchInput.value) return props.options;
    const q = searchInput.value.toLowerCase();
    return props.options.filter((opt) => opt.label.toLowerCase().includes(q));
});

const selectedOption = computed(() => {
    return props.options.find((opt) => opt.id === props.modelValue);
});

function select(option: any) {
    emit('update:modelValue', option.id);
    isOpen.value = false;
    searchInput.value = '';
}

function clear() {
    emit('update:modelValue', null);
}

function handleClickOutside(event: MouseEvent) {
    const t = event.target as Node;
    if (dropdownRef.value && dropdownRef.value.contains(t)) return;
    if (panelRef.value && panelRef.value.contains(t)) return;
    if (isOpen.value) {
        isOpen.value = false;
    }
}

function updatePanelPosition() {
    const root = dropdownRef.value;
    if (!root) return;
    const rect = root.getBoundingClientRect();

    panelStyle.value = {
        position: 'fixed',
        left: `${rect.left}px`,
        top: `${rect.bottom + 4}px`,
        width: `${rect.width}px`,
        zIndex: '1000',
    };
}

function open() {
    if (props.disabled) return;
    isOpen.value = true;
}

function close() {
    isOpen.value = false;
    searchInput.value = '';
}

function toggleOpen() {
    if (props.disabled) return;
    if (isOpen.value) {
        close();
    } else {
        open();
    }
}

function onWindowChange() {
    if (!isOpen.value) return;
    updatePanelPosition();
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('resize', onWindowChange, { passive: true });
    // Capture is important: we want to re-position even if a parent scroll container stops propagation.
    window.addEventListener('scroll', onWindowChange, {
        passive: true,
        capture: true,
    } as any);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('resize', onWindowChange as any);
    window.removeEventListener('scroll', onWindowChange as any, true as any);
});

watch(isOpen, async (v) => {
    if (!v) return;
    await nextTick();
    updatePanelPosition();
    if (props.searchable) {
        await nextTick();
        searchInputRef.value?.focus?.();
    }
});
</script>

<template>
    <div ref="dropdownRef" class="relative w-full">
        <div
            :class="
                cn(
                    'flex h-11 w-full cursor-pointer rounded-md border border-input bg-transparent px-4 py-2 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring/50 focus-within:ring-offset-2',
                    props.triggerClass,
                    disabled && 'cursor-not-allowed opacity-50',
                )
            "
            @click="toggleOpen"
        >
            <div class="flex flex-1 items-center gap-2">
                <Search class="h-4 w-4 flex-shrink-0 text-muted-foreground" />
                <span v-if="selectedOption" class="text-sm">{{
                    selectedOption.label
                }}</span>
                <span v-else class="text-muted-foreground">{{
                    placeholder
                }}</span>
            </div>
            <Button
                v-if="clearable && selectedOption && !disabled"
                type="button"
                variant="ghost"
                size="sm"
                class="h-7 w-7 p-0 hover:bg-transparent"
                @click.stop="clear"
            >
                <X class="h-3 w-3" />
            </Button>
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="isOpen && !disabled"
            ref="panelRef"
            :class="
                cn(
                    'rounded-md border border-input bg-popover/95 shadow-md backdrop-blur supports-[backdrop-filter]:bg-popover/80',
                    props.panelClass,
                )
            "
            :style="panelStyle"
        >
            <div v-if="searchable" class="border-b border-border p-2">
                <Input
                    ref="searchInputRef"
                    v-model="searchInput"
                    :placeholder="placeholder"
                    type="text"
                    :class="cn('h-9', props.searchInputClass)"
                    @keydown.esc.prevent="close"
                />
            </div>
            <div class="max-h-48 overflow-y-auto">
                <template v-if="filteredOptions.length > 0">
                    <div
                        v-for="option in filteredOptions"
                        :key="option.id"
                        class="cursor-pointer px-3 py-2 text-sm transition-colors hover:bg-accent"
                        :class="{
                            'bg-accent font-medium': modelValue === option.id,
                        }"
                        @click="select(option)"
                    >
                        {{ option.label }}
                    </div>
                </template>
                <div
                    v-else
                    class="px-3 py-4 text-center text-sm text-muted-foreground"
                >
                    No options found
                </div>
            </div>
        </div>
    </Teleport>
</template>
