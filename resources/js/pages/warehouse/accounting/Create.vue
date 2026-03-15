<template>
    <Head :title="t('accounting.addEntry')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="mx-auto w-full max-w-7xl p-4 pb-0 md:p-6 md:pb-0">
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div class="space-y-1">
                            <h1 class="text-xl font-semibold">
                                {{ t('accounting.addEntry') }}
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                {{ t('nav.accounting') }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Link href="/warehouse/accounting">
                                <Button variant="outline" size="sm">
                                    {{ t('common.back') || t('common.cancel') }}
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </template>

            <div class="mx-auto w-full max-w-7xl space-y-4 p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-md border border-border/70 bg-white/10 p-2 backdrop-blur-md dark:bg-white/5"
                            >
                                <Plus class="h-5 w-5 text-primary/80" />
                            </div>
                            <CardTitle>{{
                                t('accounting.addEntry')
                            }}</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form
                            @submit.prevent="submit"
                            class="grid gap-4 sm:grid-cols-2"
                        >
                            <div class="space-y-2">
                                <Label
                                    for="date"
                                    class="flex items-center gap-2"
                                >
                                    <Calendar
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    {{ t('common.date') }} *
                                </Label>
                                <Input
                                    id="date"
                                    v-model="form.date"
                                    type="date"
                                    required
                                    class="h-11"
                                />
                                <p
                                    v-if="form.errors.date"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.date }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label
                                    for="type"
                                    class="flex items-center gap-2"
                                >
                                    <DollarSign
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    {{ t('common.type') }} *
                                </Label>
                                <SearchableSelect
                                    :model-value="form.type"
                                    :options="typeOptions"
                                    :trigger-class="selectTriggerClass"
                                    :panel-class="selectPanelClass"
                                    @update:model-value="(v) => (form.type = v)"
                                />
                                <p
                                    v-if="form.errors.type"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.type }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label
                                    for="category"
                                    class="flex items-center gap-2"
                                >
                                    <FolderTree
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    {{ t('common.category') }} *
                                </Label>
                                <SearchableSelect
                                    :model-value="form.category"
                                    :options="categoryOptions"
                                    :placeholder="t('common.select')"
                                    :trigger-class="selectTriggerClass"
                                    :panel-class="selectPanelClass"
                                    @update:model-value="
                                        (v) => (form.category = v)
                                    "
                                />
                                <p
                                    v-if="form.errors.category"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.category }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label
                                    for="amount"
                                    class="flex items-center gap-2"
                                >
                                    <Banknote
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    {{ t('common.amount') }} *
                                </Label>
                                <Input
                                    id="amount"
                                    v-model="form.amount"
                                    type="number"
                                    step="0.01"
                                    required
                                    class="h-11"
                                />
                                <p
                                    v-if="form.errors.amount"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.amount }}
                                </p>
                            </div>

                            <div class="space-y-2 sm:col-span-2">
                                <Label
                                    for="description"
                                    class="flex items-center gap-2"
                                >
                                    <FileText
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    {{ t('common.description') }} *
                                </Label>
                                <Input
                                    id="description"
                                    v-model="form.description"
                                    required
                                    class="h-11"
                                />
                                <p
                                    v-if="form.errors.description"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.description }}
                                </p>
                            </div>

                            <div class="space-y-2 sm:col-span-2">
                                <Label
                                    for="notes"
                                    class="flex items-center gap-2"
                                >
                                    <FileText
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    {{ t('common.notes') }}
                                </Label>
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    :class="notesClass"
                                />
                            </div>

                            <div
                                class="flex flex-col gap-2 sm:col-span-2 sm:flex-row sm:justify-end sm:gap-3"
                            >
                                <Button
                                    type="submit"
                                    class="w-full sm:w-auto"
                                    :disabled="form.processing"
                                >
                                    {{ t('common.save') }}
                                </Button>
                                <Link
                                    href="/warehouse/accounting"
                                    class="w-full sm:w-auto"
                                >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="w-full sm:w-auto"
                                    >
                                        {{ t('common.cancel') }}
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppPageContent>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Plus,
    Calendar,
    DollarSign,
    FolderTree,
    Banknote,
    FileText,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppPageContent from '@/components/AppPageContent.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { nowTurkeyDateLocal } from '@/composables/useTurkeyDate';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();

const props = defineProps<{
    categories: {
        income: Record<string, string>;
        expense: Record<string, string>;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.accounting'), href: '/warehouse/accounting' },
    { title: t('accounting.addEntry') },
];

const form = useForm({
    date: nowTurkeyDateLocal(),
    type: 'income',
    category: '',
    amount: '',
    description: '',
    notes: '',
});

const currentCategories = computed(() => {
    return form.type === 'income'
        ? props.categories.income
        : props.categories.expense;
});

const typeOptions = computed(() => [
    { id: 'income', label: t('accounting.income') },
    { id: 'expense', label: t('accounting.expenses') },
]);

const categoryOptions = computed(() =>
    Object.entries(currentCategories.value).map(([key, label]) => ({
        id: key,
        label: t(label as string),
    })),
);

const selectTriggerClass =
    'border border-border/70 bg-white/10 px-3 text-sm backdrop-blur-md shadow-none focus-within:ring-2 focus-within:ring-ring/50 dark:bg-white/5';

const selectPanelClass =
    'border border-border/70 bg-white/70 shadow-md backdrop-blur-xl dark:bg-black/35';

const notesClass =
    'min-h-28 w-full resize-y rounded-md border border-border/70 bg-white/10 px-3 py-2 text-sm backdrop-blur-md shadow-none outline-none focus:ring-2 focus:ring-ring/50 dark:bg-white/5';

function submit() {
    form.post('/warehouse/accounting');
}
</script>
