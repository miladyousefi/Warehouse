<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    links: Array<{ url: string | null; label: string; active?: boolean }>;
    class?: string;
}>();

const { t } = useI18n();

function isPrevious(l: { label: string }) {
    const s = l.label.toLowerCase();
    return s.includes('previous') || s.includes('önceki') || s.includes('&laquo;');
}
function isNext(l: { label: string }) {
    const s = l.label.toLowerCase();
    return s.includes('next') || s.includes('sonraki') || s.includes('&raquo;');
}
</script>

<template>
    <nav :class="['flex items-center justify-center gap-2 flex-wrap', props.class]" role="navigation" aria-label="Pagination">
        <template v-for="(link, i) in props.links" :key="i">
            <!-- Previous Button -->
            <Link
                v-if="isPrevious(link) && link.url"
                :href="link.url"
                preserve-scroll
                as-child
            >
                <Button variant="outline" size="sm">
                    <ChevronLeft class="mr-1 h-4 w-4" />
                    {{ t('pagination.previous') }}
                </Button>
            </Link>
            <Button
                v-else-if="isPrevious(link) && !link.url"
                variant="outline"
                size="sm"
                disabled
            >
                <ChevronLeft class="mr-1 h-4 w-4" />
                {{ t('pagination.previous') }}
            </Button>

            <!-- Next Button -->
            <Link
                v-else-if="isNext(link) && link.url"
                :href="link.url"
                preserve-scroll
                as-child
            >
                <Button variant="outline" size="sm">
                    {{ t('pagination.next') }}
                    <ChevronRight class="ml-1 h-4 w-4" />
                </Button>
            </Link>
            <Button
                v-else-if="isNext(link) && !link.url"
                variant="outline"
                size="sm"
                disabled
            >
                {{ t('pagination.next') }}
                <ChevronRight class="ml-1 h-4 w-4" />
            </Button>

            <!-- Dots -->
            <span v-else-if="link.label === '...'" class="px-2 py-1 text-muted-foreground">...</span>

            <!-- Page Number Links -->
            <Link
                v-else-if="link.url"
                :href="link.url"
                preserve-scroll
                as-child
            >
                <Button
                    variant="outline"
                    size="sm"
                    :class="{ 'bg-primary text-primary-foreground': link.active }"
                >
                    {{ link.label }}
                </Button>
            </Link>

            <!-- Disabled Page Number (current) -->
            <Button
                v-else
                variant="outline"
                size="sm"
                disabled
                :class="{ 'bg-primary text-primary-foreground': link.active }"
            >
                {{ link.label }}
            </Button>
        </template>
    </nav>
</template>
