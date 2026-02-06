<template>
    <Head :title="t('accounting.editEntry')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900">
                            <Pencil class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <CardTitle>{{ t('accounting.editEntry') }}</CardTitle>
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
                            <Label class="flex items-center gap-2">
                                <DollarSign class="h-4 w-4 text-muted-foreground" />
                                {{ t('common.type') }}
                            </Label>
                            <Input :value="entry.type" disabled />
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
import { Pencil, Calendar, DollarSign, FolderTree, Banknote, FileText } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();

const props = defineProps<{
    entry: {
        id: number;
        date: string;
        type: 'income' | 'expense';
        category: string;
        description: string;
        amount: number;
        notes: string | null;
    };
    categories: {
        income: Record<string, string>;
        expense: Record<string, string>;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.accounting'), href: '/warehouse/accounting' },
    { title: t('accounting.editEntry') },
];
// verified this is already correct in the viewed file.

const form = useForm({
    date: props.entry.date,
    category: props.entry.category,
    amount: props.entry.amount.toString(),
    description: props.entry.description,
    notes: props.entry.notes || '',
});

const currentCategories = computed(() => {
    return props.categories[props.entry.type];
});

function submit() {
    form.patch(`/warehouse/accounting/${props.entry.id}`);
}
</script>
