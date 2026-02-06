<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { index, update } from '@/actions/App/Http/Controllers/Warehouse/UserController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent } from '@/components/ui/card';

const props = defineProps<{
    user: { id: number; name: string; email: string; roles: Array<{ id: number; name: string }> };
    role_ids: number[];
    roles: Array<{ id: number; name: string }>;
}>();

const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.admins'), href: index.url() }, { title: t('users.editAdmin') }];

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    role_ids: (props.role_ids || []).map(id => Number(id)),
});


function submit() {
    form.transform((data) => ({
        ...data,
        password: data.password || undefined,
        password_confirmation: data.password ? data.password_confirmation : undefined,
    })).put(update.url({ user: props.user.id }));
}
</script>

<template>
    <Head :title="t('users.editAdmin')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardContent class="pt-6">
                    <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">{{ t('common.name') }}</Label>
                            <Input id="name" v-model="form.name" required />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" type="email" required />
                            <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="password">{{ t('auth.password') }}</Label>
                            <Input id="password" v-model="form.password" type="password" :placeholder="t('users.leaveBlank')" />
                            <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="password_confirmation">{{ t('users.confirmPassword') }}</Label>
                            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
                            <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">{{ form.errors.password_confirmation }}</p>
                        </div>
                        <div class="md:col-span-2 space-y-2">
                            <Label>{{ t('users.roles') }}</Label>
                            <div class="flex flex-wrap gap-4 mt-2">
                                <label v-for="r in roles" :key="r.id" class="flex items-center gap-2 cursor-pointer">
                                    <Checkbox 
                                        :checked="form.role_ids.includes(Number(r.id))" 
                                        @update:model-value="(checked: boolean) => {
                                            const rid = Number(r.id);
                                            if (checked) {
                                                if (!form.role_ids.includes(rid)) form.role_ids = [...form.role_ids, rid];
                                            } else {
                                                form.role_ids = form.role_ids.filter(id => Number(id) !== rid);
                                            }
                                        }" 
                                    />
                                    <span>{{ formatRoleName(r.name) }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex gap-2 md:col-span-2">
                            <Button type="submit" :disabled="form.processing">{{ t('common.save') }}</Button>
                            <Link :href="index.url()"><Button type="button" variant="outline">{{ t('common.cancel') }}</Button></Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
