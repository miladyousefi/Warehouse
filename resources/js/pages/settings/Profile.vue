<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed, ref, watch } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { type BreadcrumbItem } from '@/types';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();
const { t } = useI18n();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('settings.profileSettings'),
        href: edit().url,
    },
];

const page = usePage();
const user = computed(() => (page.props.auth as any).user);

const avatarPreviewUrl = ref<string | null>(null);
const avatarUrl = computed(() =>
    avatarPreviewUrl.value ? avatarPreviewUrl.value : (user.value as any)?.avatar || ''
);

watch(
    () => (user.value as any)?.avatar,
    () => {
        // Once server avatar changes, drop local preview.
        avatarPreviewUrl.value = null;
    }
);

function onAvatarChange(e: Event) {
    const input = e.target as HTMLInputElement | null;
    const file = input?.files?.[0];
    if (!file) {
        avatarPreviewUrl.value = null;
        return;
    }
    avatarPreviewUrl.value = URL.createObjectURL(file);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings.profileSettings')" />

        <h1 class="sr-only">{{ t('settings.profileSettings') }}</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    :title="t('settings.profileInfo')"
                    :description="t('settings.profileInfoDescription')"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    enctype="multipart/form-data"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="flex items-center gap-4">
                        <Avatar class="h-14 w-14 overflow-hidden rounded-lg">
                            <AvatarImage v-if="avatarUrl" :src="avatarUrl" :alt="(user as any).name" />
                            <AvatarFallback class="rounded-lg text-black dark:text-white">
                                {{ String((user as any).name || '').split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase() }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="grid gap-2">
                            <Label for="avatar">{{ t('common.avatar') || 'Avatar' }}</Label>
                            <Input id="avatar" name="avatar" type="file" accept="image/png,image/jpeg,image/webp" @change="onAvatarChange" />
                            <InputError class="mt-1" :message="(errors as any).avatar" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">{{ t('common.name') }}</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="(user as any).name"
                            required
                            autocomplete="name"
                            :placeholder="t('settings.fullName')"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">{{ t('auth.email') }}</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="(user as any).email"
                            required
                            autocomplete="username"
                            :placeholder="t('auth.email')"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !(user as any).email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            {{ t('settings.unverifiedEmail') }}
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                {{ t('settings.resendVerification') }}
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            {{ t('settings.verificationSent') }}
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >{{ t('common.save') }}</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                {{ t('common.saved') || 'Saved.' }}
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
