<script setup lang="ts">
import { Search, X } from 'lucide-vue-next';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface Props {
    modelValue: string | number | null;
    options: Array<{ id: string | number; label: string; [key: string]: any }>;
    placeholder?: string;
    searchable?: boolean;
    clearable?: boolean;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Select...',
    searchable: true,
    clearable: true,
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number | null];
}>();

const searchInput = ref('');
const isOpen = ref(false);
const dropdownRef = ref<HTMLDivElement | null>(null);

const filteredOptions = computed(() => {
    if (!props.searchable || !searchInput.value) return props.options;
    const q = searchInput.value.toLowerCase();
    return props.options.filter(opt => 
        opt.label.toLowerCase().includes(q)
    );
});

const selectedOption = computed(() => {
    return props.options.find(opt => opt.id === props.modelValue);
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
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        isOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="dropdownRef" class="relative w-full">
        <div class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2 cursor-pointer" @click="isOpen = !isOpen" :class="{ 'opacity-50 cursor-not-allowed': disabled }">
            <div class="flex-1 flex items-center gap-2">
                <Search class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                <span v-if="selectedOption" class="text-sm">{{ selectedOption.label }}</span>
                <span v-else class="text-muted-foreground">{{ placeholder }}</span>
            </div>
            <Button v-if="clearable && selectedOption && !disabled" type="button" variant="ghost" size="sm" class="h-4 w-4 p-0 hover:bg-transparent" @click.stop="clear">
                <X class="h-3 w-3" />
            </Button>
        </div>

        <div v-if="isOpen && !disabled" class="absolute z-50 w-full mt-1 rounded-md border border-input bg-background shadow-md">
            <div v-if="searchable" class="p-2 border-b border-border">
                <Input v-model="searchInput" :placeholder="placeholder" type="text" class="h-8" />
            </div>
            <div class="max-h-48 overflow-y-auto">
                <template v-if="filteredOptions.length > 0">
                    <div
                        v-for="option in filteredOptions"
                        :key="option.id"
                        class="px-3 py-2 text-sm cursor-pointer hover:bg-accent transition-colors"
                        :class="{ 'bg-accent font-medium': modelValue === option.id }"
                        @click="select(option)"
                    >
                        {{ option.label }}
                    </div>
                </template>
                <div v-else class="px-3 py-4 text-sm text-muted-foreground text-center">
                    No options found
                </div>
            </div>
        </div>
    </div>
</template>
