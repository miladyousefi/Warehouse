<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { index, store } from '@/actions/UserController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent } from '@/components/ui/card';

const props = defineProps<{ roles: Array<{ id: number; name: string }> }>();
const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.admins'), href: index.url() }, { title: t('users.addAdmin') }];
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_ids: [] as number[],
});

function toggleRole(id: number) {
    const idx = form.role_ids.indexOf(id);
    if (idx >= 0) {
        form.role_ids = form.role_ids.filter((r) => r !== id);
    } else {
        form.role_ids = [...form.role_ids, id];
    }
}

function submit() {
    form.post(store.url());
}
</script>

<template>
    <Head :title="t('users.addAdmin')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardContent class="pt-6">
                    <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2"><Label for="name">{{ t('common.name') }}</Label><Input id="name" v-model="form.name" required /></div>
                        <div class="space-y-2"><Label for="email">Email</Label><Input id="email" v-model="form.email" type="email" required /></div>
                        <div class="space-y-2"><Label for="password">{{ t('auth.password') }}</Label><Input id="password" v-model="form.password" type="password" required /></div>
                        <div class="space-y-2"><Label for="password_confirmation">{{ t('users.confirmPassword') }}</Label><Input id="password_confirmation" v-model="form.password_confirmation" type="password" required /></div>
                        <div class="md:col-span-2 space-y-2">
                            <Label>{{ t('users.roles') }}</Label>
                            <div class="flex flex-wrap gap-4 mt-2">
                                <label v-for="r in roles" :key="r.id" class="flex items-center gap-2 cursor-pointer">
                                    <Checkbox :checked="form.role_ids.includes(r.id)" @update:checked="() => toggleRole(r.id)" />
                                    <span>{{ r.name }}</span>
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
