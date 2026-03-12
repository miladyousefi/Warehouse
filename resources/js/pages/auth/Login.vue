<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/login';

defineProps<{
    status?: string;
}>();

const { t } = useI18n();
</script>

<template>
    <AuthBase
        :title="t('auth.loginTitle')"
        :description="t('auth.loginDescription')"
    >
        <Head :title="t('auth.login')" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600 dark:text-green-400"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">{{ t('auth.email') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        :placeholder="'email@example.com'"
                        class="h-11 rounded-xl border-amber-200/70 bg-white/70 shadow-sm backdrop-blur focus-visible:border-amber-400 focus-visible:ring-amber-400/25 dark:border-amber-200/30 dark:bg-white/10"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{ t('auth.password') }}</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        :placeholder="'Password'"
                        class="h-11 rounded-xl border-amber-200/70 bg-white/70 shadow-sm backdrop-blur focus-visible:border-amber-400 focus-visible:ring-amber-400/25 dark:border-amber-200/30 dark:bg-white/10"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>{{ t('auth.remember') }}</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 h-11 w-full rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-[0_10px_30px_rgba(245,158,11,0.35)] hover:from-amber-400 hover:to-amber-500"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.login') }}
                </Button>
            </div>

            <!-- Demo credentials removed -->
        </Form>
    </AuthBase>
</template>
