<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ChefHat,
    ChevronDown,
    CircleDollarSign,
    Search,
    UtensilsCrossed,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const locale = computed(() =>
    useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en',
);

const props = defineProps<{
    setting: {
        layout_type: string;
    };
    categories: Array<Record<string, any>>;
}>();

const template = computed(() => props.setting.layout_type || 'template_1');
const searchQuery = ref('');
const openCategoryIds = ref<number[]>([]);

const theme = computed(() => {
    if (template.value === 'template_2') {
        return {
            page: 'bg-slate-950 text-slate-100',
            hero: 'border-slate-700 bg-slate-900/90',
            search: 'border-slate-700 bg-slate-900 text-slate-100 placeholder:text-slate-400',
            surface: 'border-slate-700 bg-slate-900/70',
            card: 'border-slate-700 bg-slate-900',
            muted: 'text-slate-300',
            price: 'bg-emerald-500/15 text-emerald-300 border border-emerald-400/30',
            chip: 'border-slate-600 bg-slate-800 text-slate-200',
            chipActive: 'border-slate-300 bg-slate-700 text-white',
        };
    }

    if (template.value === 'template_3') {
        return {
            page: 'bg-gradient-to-br from-orange-50 via-rose-50 to-cyan-50 text-slate-900',
            hero: 'border-rose-200 bg-white/85 backdrop-blur',
            search: 'border-rose-200 bg-white text-slate-900 placeholder:text-slate-500',
            surface: 'border-rose-200 bg-white/85 backdrop-blur',
            card: 'border-rose-100 bg-white',
            muted: 'text-slate-600',
            price: 'bg-rose-100 text-rose-700 border border-rose-200',
            chip: 'border-rose-200 bg-white text-rose-700',
            chipActive: 'border-rose-300 bg-rose-600 text-white',
        };
    }

    if (template.value === 'template_4') {
        return {
            page: 'bg-zinc-100 text-zinc-900',
            hero: 'border-zinc-300 bg-white',
            search: 'border-zinc-300 bg-white text-zinc-900 placeholder:text-zinc-500',
            surface: 'border-zinc-300 bg-white',
            card: 'border-zinc-200 bg-white',
            muted: 'text-zinc-600',
            price: 'bg-zinc-900 text-white border border-zinc-900',
            chip: 'border-zinc-300 bg-white text-zinc-700',
            chipActive: 'border-zinc-900 bg-zinc-900 text-white',
        };
    }

    if (template.value === 'template_5') {
        return {
            page: 'bg-cyan-50 text-slate-900',
            hero: 'border-cyan-200 bg-white',
            search: 'border-cyan-200 bg-white text-slate-900 placeholder:text-slate-500',
            surface: 'border-cyan-200 bg-white',
            card: 'border-cyan-100 bg-white',
            muted: 'text-slate-600',
            price: 'bg-cyan-100 text-cyan-700 border border-cyan-200',
            chip: 'border-cyan-200 bg-white text-cyan-700',
            chipActive: 'border-cyan-400 bg-cyan-600 text-white',
        };
    }

    return {
        page: 'bg-amber-50 text-slate-900',
        hero: 'border-amber-200 bg-white',
        search: 'border-amber-200 bg-white text-slate-900 placeholder:text-slate-500',
        surface: 'border-amber-200 bg-white',
        card: 'border-amber-100 bg-white',
        muted: 'text-slate-600',
        price: 'bg-amber-100 text-amber-700 border border-amber-200',
        chip: 'border-amber-200 bg-white text-amber-700',
        chipActive: 'border-amber-400 bg-amber-600 text-white',
    };
});

watch(
    () => props.categories,
    (categories) => {
        const ids = categories
            .map((cat) => Number(cat.id))
            .filter((id) => Number.isFinite(id));
        openCategoryIds.value = openCategoryIds.value.filter((id) =>
            ids.includes(id),
        );
        if (openCategoryIds.value.length === 0 && ids.length > 0) {
            openCategoryIds.value = [ids[0]];
        }
    },
    { immediate: true },
);

function descriptionFor(item: Record<string, any>): string {
    return (
        (locale.value === 'name_tr'
            ? item.description_tr
            : item.description_en) || ''
    );
}

function itemMatches(item: Record<string, any>, query: string): boolean {
    const q = query.toLowerCase().trim();
    if (!q) return true;

    const fields = [
        String(item.name_tr || ''),
        String(item.name_en || ''),
        String(item.description_tr || ''),
        String(item.description_en || ''),
    ];

    return fields.some((field) => field.toLowerCase().includes(q));
}

const filteredCategories = computed(() => {
    const q = searchQuery.value.trim();
    if (!q) return props.categories;

    return props.categories
        .map((cat) => ({
            ...cat,
            items: (cat.items || []).filter((item: Record<string, any>) =>
                itemMatches(item, q),
            ),
        }))
        .filter((cat) => (cat.items || []).length > 0);
});

function isCategoryOpen(categoryId: number): boolean {
    return openCategoryIds.value.includes(Number(categoryId));
}

function toggleCategory(categoryId: number): void {
    const id = Number(categoryId);
    if (isCategoryOpen(id)) {
        openCategoryIds.value = openCategoryIds.value.filter(
            (value) => value !== id,
        );
        return;
    }

    openCategoryIds.value.push(id);
}

function focusCategory(categoryId: number): void {
    const id = Number(categoryId);
    if (!isCategoryOpen(id)) {
        openCategoryIds.value.push(id);
    }

    const el = document.getElementById(`cat-${id}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
</script>

<template>
    <Head :title="t('restaurantMenu.title')" />

    <div
        class="min-h-screen transition-colors duration-300"
        :class="theme.page"
    >
        <div class="mx-auto max-w-7xl px-4 py-8 md:px-6">
            <section
                class="menu-fade mb-5 rounded-3xl border p-6 shadow-sm md:p-8"
                :class="theme.hero"
            >
                <div
                    class="mb-2 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold"
                    :class="theme.chip"
                >
                    <UtensilsCrossed class="h-3.5 w-3.5" />
                    {{ t('restaurantMenu.publicMenu') }}
                </div>

                <div
                    class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between"
                >
                    <div>
                        <h1
                            class="text-4xl font-black tracking-tight"
                            :class="
                                template === 'template_4'
                                    ? 'font-serif'
                                    : 'font-sans'
                            "
                        >
                            {{ t('restaurantMenu.title') }}
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm" :class="theme.muted">
                            {{ t('restaurantMenu.menuSubtitle') }}
                        </p>
                    </div>
                    <div
                        class="flex items-center gap-2 rounded-2xl border px-3 py-2"
                        :class="theme.chip"
                    >
                        <ChefHat class="h-4 w-4" />
                        <span class="text-sm font-semibold"
                            >{{ filteredCategories.length }}
                            {{ t('restaurantMenu.category') }}</span
                        >
                    </div>
                </div>
            </section>

            <section
                class="menu-fade sticky top-2 z-20 mb-5 rounded-2xl border p-3 shadow-sm backdrop-blur"
                :class="theme.hero"
            >
                <div
                    class="grid gap-3 lg:grid-cols-[minmax(0,360px)_1fr] lg:items-center"
                >
                    <label class="relative block">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 opacity-70"
                        />
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="t('common.search')"
                            class="w-full rounded-xl border py-2.5 pr-10 pl-10 text-sm transition outline-none focus:ring-2 focus:ring-offset-0"
                            :class="theme.search"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            class="absolute top-1/2 right-2 -translate-y-1/2 rounded-md p-1.5 hover:bg-black/10"
                            @click="searchQuery = ''"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </label>

                    <div class="flex gap-2 overflow-x-auto pb-1">
                        <button
                            v-for="cat in filteredCategories"
                            :key="`chip-${cat.id}`"
                            type="button"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                            :class="
                                isCategoryOpen(cat.id)
                                    ? theme.chipActive
                                    : theme.chip
                            "
                            @click="focusCategory(cat.id)"
                        >
                            {{ cat[locale] || cat.name_tr }}
                        </button>
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <article
                    v-for="(cat, catIndex) in filteredCategories"
                    :id="`cat-${cat.id}`"
                    :key="cat.id"
                    class="menu-fade overflow-hidden rounded-2xl border"
                    :class="theme.surface"
                    :style="{ animationDelay: `${catIndex * 40}ms` }"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-3 px-4 py-4 text-left"
                        @click="toggleCategory(cat.id)"
                    >
                        <span class="flex min-w-0 items-center gap-3">
                            <img
                                v-if="cat.image_url"
                                :src="cat.image_url"
                                class="h-11 w-11 rounded-xl border object-cover"
                                :class="theme.card"
                                alt="category"
                            />
                            <span
                                v-else
                                class="flex h-11 w-11 items-center justify-center rounded-xl border text-xl"
                                :class="theme.card"
                                >{{ cat.icon || '🍽️' }}</span
                            >
                            <span>
                                <span
                                    class="block text-lg font-bold tracking-tight"
                                    :class="
                                        template === 'template_4'
                                            ? 'font-serif'
                                            : 'font-sans'
                                    "
                                    >{{ cat[locale] || cat.name_tr }}</span
                                >
                                <span class="text-xs" :class="theme.muted"
                                    >{{ (cat.items || []).length }} items</span
                                >
                            </span>
                        </span>

                        <ChevronDown
                            class="h-5 w-5 transition-transform duration-300"
                            :class="
                                isCategoryOpen(cat.id)
                                    ? 'rotate-180'
                                    : 'rotate-0'
                            "
                        />
                    </button>

                    <transition name="accordion">
                        <div v-show="isCategoryOpen(cat.id)" class="px-4 pb-4">
                            <div
                                class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
                            >
                                <article
                                    v-for="item in cat.items"
                                    :key="item.id"
                                    class="menu-card overflow-hidden rounded-2xl border shadow-sm"
                                    :class="theme.card"
                                >
                                    <div
                                        class="relative aspect-[4/3] overflow-hidden"
                                    >
                                        <img
                                            v-if="item.image_url"
                                            :src="item.image_url"
                                            alt="food"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                        />
                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center bg-slate-100 text-4xl"
                                        >
                                            🍽️
                                        </div>
                                        <div
                                            class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/55 to-transparent p-2"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold"
                                                :class="theme.price"
                                            >
                                                <CircleDollarSign
                                                    class="h-3.5 w-3.5"
                                                />
                                                {{
                                                    Number(
                                                        item.sale_price,
                                                    ).toFixed(2)
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <h3 class="leading-5 font-semibold">
                                            {{ item[locale] || item.name_tr }}
                                        </h3>
                                        <p
                                            v-if="descriptionFor(item)"
                                            class="mt-1 line-clamp-2 text-sm"
                                            :class="theme.muted"
                                        >
                                            {{ descriptionFor(item) }}
                                        </p>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </transition>
                </article>
            </section>

            <section
                v-if="filteredCategories.length === 0"
                class="menu-fade rounded-2xl border border-dashed p-8 text-center text-sm"
                :class="theme.surface"
            >
                No items found for this search.
            </section>
        </div>
    </div>
</template>

<style scoped>
.menu-fade {
    animation: menu-fade-in 360ms ease both;
}

.menu-card {
    transition:
        transform 180ms ease,
        box-shadow 180ms ease;
}

.menu-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 26px -20px rgba(15, 23, 42, 0.6);
}

.accordion-enter-active,
.accordion-leave-active {
    transition:
        opacity 220ms ease,
        transform 220ms ease;
}

.accordion-enter-from,
.accordion-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

@keyframes menu-fade-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
