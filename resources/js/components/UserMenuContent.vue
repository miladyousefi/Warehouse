<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Check, Globe, LogOut, Settings } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();

const page = usePage();
const currentLocale = computed(() => (page.props.locale as string) ?? 'tr');
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link
                class="block w-full cursor-pointer"
                :href="edit().url"
                prefetch
            >
                <Settings class="mr-2 h-4 w-4" />
                {{ $t('nav.settings') }}
            </Link>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem :as-child="true">
            <a class="flex w-full items-center" href="/locale/tr">
                <Globe class="mr-2 h-4 w-4" />
                Türkçe (TR)
                <Check
                    v-if="currentLocale === 'tr'"
                    class="ml-auto h-4 w-4 text-primary"
                />
            </a>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <a class="flex w-full items-center" href="/locale/en">
                <Globe class="mr-2 h-4 w-4" />
                English (EN)
                <Check
                    v-if="currentLocale === 'en'"
                    class="ml-auto h-4 w-4 text-primary"
                />
            </a>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout().url"
            method="post"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            {{ $t('auth.logout') || 'Log out' }}
        </Link>
    </DropdownMenuItem>
</template>
