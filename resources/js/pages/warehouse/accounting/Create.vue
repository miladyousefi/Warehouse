<template>
    <Head :title="t('accounting.addEntry')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900">
                            <Plus class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <CardTitle>{{ t('accounting.addEntry') }}</CardTitle>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="date" class="flex items-center gap-2">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                {{ t('common.date') }} *
                            </Label>
                            <Input id="date" v-model="form.date" type="date" required />
                            <p v-if="form.errors.date" class="text-sm text-destructive">
                                {{ form.errors.date }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="type" class="flex items-center gap-2">
                                <DollarSign class="h-4 w-4 text-muted-foreground" />
                                {{ t('common.type') }} *
                            </Label>
                            <select id="type" v-model="form.type" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="">-</option>
                                <option value="income">{{ t('accounting.income') }}</option>
                                <option value="expense">{{ t('accounting.expenses') }}</option>
                            </select>
                            <p v-if="form.errors.type" class="text-sm text-destructive">
                                {{ form.errors.type }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="category" class="flex items-center gap-2">
                                <FolderTree class="h-4 w-4 text-muted-foreground" />
                                {{ t('common.category') }} *
                            </Label>
                            <select id="category" v-model="form.category" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="">-</option>
                                <option v-for="(cat, key) in currentCategories" :key="key" :value="key">
                                    {{ cat }}
                                </option>
                            </select>
                            <p v-if="form.errors.category" class="text-sm text-destructive">
                                {{ form.errors.category }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="amount" class="flex items-center gap-2">
                                <Banknote class="h-4 w-4 text-muted-foreground" />
                                {{ t('common.amount') }} *
                            </Label>
                            <Input id="amount" v-model="form.amount" type="number" step="0.01" required />
                            <p v-if="form.errors.amount" class="text-sm text-destructive">
                                {{ form.errors.amount }}
                            </p>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="description" class="flex items-center gap-2">
                                <FileText class="h-4 w-4 text-muted-foreground" />
                                {{ t('common.description') }} *
                            </Label>
                            <Input id="description" v-model="form.description" required />
                            <p v-if="form.errors.description" class="text-sm text-destructive">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="notes" class="flex items-center gap-2">
                                <FileText class="h-4 w-4 text-muted-foreground" />
                                {{ t('common.notes') }}
                            </Label>
                            <textarea id="notes" v-model="form.notes" class="flex h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
                        </div>

                        <div class="flex gap-2 md:col-span-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ t('common.save') }}
                            </Button>
                            <Link href="/warehouse/accounting">
                                <Button type="button" variant="outline">{{ t('common.cancel') }}</Button>
                            </Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Calendar, DollarSign, FolderTree, Banknote, FileText } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';
import { nowTurkeyDateLocal } from '@/composables/useTurkeyDate';

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
// verified this is already correct in the viewed file.

const form = useForm({
    date: nowTurkeyDateLocal(),
    type: 'income',
    category: '',
    amount: '',
    description: '',
    notes: '',
});

const currentCategories = computed(() => {
    return form.type === 'income' ? props.categories.income : props.categories.expense;
});

function submit() {
    form.post('/warehouse/accounting');
}
</script>
