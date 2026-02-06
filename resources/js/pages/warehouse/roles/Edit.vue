<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';

const props = defineProps<{
    role: { id: number; name: string; permissions: Array<{ id: number; name: string }> };
    permission_ids: number[];
    permissions: Array<{ id: number; name: string }>;
}>();

const { t, tm, rt } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('permissions.title'), href: '/warehouse/roles' },
    { title: t('common.edit') }
];

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const initialPermissionIds = (props.permission_ids && props.permission_ids.length)
    ? (props.permission_ids || []).map(id => Number(id))
    : ((props.role.permissions || []).map(p => Number((p as any).id)));

const form = useForm({
    name: props.role.name,
    permission_ids: initialPermissionIds,
});

// Debugging: log incoming props and initial form state to console
console.log('Role edit props:', {
    permission_ids: props.permission_ids,
    role_permissions: props.role?.permissions,
    initialPermissionIds,
    form_preview: { name: props.role.name, permission_ids: initialPermissionIds }
});

// Group permissions by prefix (e.g. products.view -> products)
const groupedPermissions = computed(() => {
    const groups: Record<string, typeof props.permissions> = {};
    props.permissions.forEach(p => {
        const group = p.name.split('.')[0];
        if (!groups[group]) groups[group] = [];
        groups[group].push(p);
    });
    return groups;
});

function getPermissionTitle(name: string) {
    // Handle specific groupings or patterns
    const key = `permissions.${name}`;
    const translated = t(key);
    
    // If translation exists and is not just the key returned
    if (translated && !translated.includes('permissions.')) {
        return translated;
    }
    
    // Check for nested keys like stock.movements.view -> permissions.stock_movements.view
    const flatName = name.replace(/\./g, '_');
    const flatKey = `permissions.${flatName}`;
    const flatTranslated = t(flatKey);
    if (flatTranslated && !flatTranslated.includes('permissions.')) {
        return flatTranslated;
    }

    // Fallback: format parts
    return name.split('.').map(part => part.charAt(0).toUpperCase() + part.slice(1).replace('_', ' ')).join(' > ');
}

function submit() {
    form.put(`/warehouse/roles/${props.role.id}`);
}
</script>

<template>
    <Head :title="t('common.edit') + ' ' + t('users.roles')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-6">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('common.edit') }} - {{ formatRoleName(role.name) }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="name">{{ t('common.name') }}</Label>
                            <Input id="name" v-model="form.name" required :disabled="role.name === 'admin'" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-4">
                            <Label class="text-lg font-bold">{{ t('permissions.title') }}</Label>
                            
                            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                                <div v-for="(perms, group) in groupedPermissions" :key="group" class="p-4 rounded-lg border bg-slate-50/50 dark:bg-slate-900/50">
                                    <h3 class="font-bold capitalize mb-3 text-primary border-b pb-2">{{ group.replace('_', ' ') }}</h3>
                                    <div class="space-y-3">
                                        <div v-for="p in perms" :key="p.id" class="flex items-center space-x-2">
                                            <Checkbox
                                                :id="'p-' + p.id"
                                                :checked="form.permission_ids.includes(Number(p.id))"
                                                :model-value="form.permission_ids.includes(Number(p.id))"
                                                @update:model-value="(checked: boolean) => {
                                                    const pid = Number(p.id);
                                                    if (checked) form.permission_ids = [...form.permission_ids, pid];
                                                    else form.permission_ids = form.permission_ids.filter(id => Number(id) !== pid);
                                                }"
                                                @update:checked="(checked: boolean) => {
                                                    const pid = Number(p.id);
                                                    if (checked) form.permission_ids = [...form.permission_ids, pid];
                                                    else form.permission_ids = form.permission_ids.filter(id => Number(id) !== pid);
                                                }"
                                            />
                                            <Label :for="'p-' + p.id" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer">
                                                {{ getPermissionTitle(p.name) }}
                                            </Label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-4">
                            <Button type="submit" :disabled="form.processing">{{ t('common.save') }}</Button>
                            <Link href="/warehouse/roles"><Button type="button" variant="outline">{{ t('common.cancel') }}</Button></Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
