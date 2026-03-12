<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRight, Zap } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard, home, login, logout } from '@/routes';

type Variant = 'landing' | 'auth';

withDefaults(
    defineProps<{
        subtitle?: string;
        variant?: Variant;
        brandName?: string;
        backLabel?: string;
    }>(),
    {
        variant: 'landing',
        brandName: 'The Hunger',
        backLabel: 'Back to home',
    },
);

const { t } = useI18n();
const page = usePage();

const isAuthed = computed(() => Boolean((page.props as any)?.auth?.user));

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <header class="site-header">
        <div class="header-inner">
            <Link :href="home().url" class="brand">
                <div class="logo-wrap">
                    <AppLogoIcon class="logo-icon" />
                    <div class="logo-ring" />
                </div>
                <div class="brand-text">
                    <span class="brand-name">{{ brandName }}</span>
                    <span v-if="subtitle" class="brand-sub">{{
                        subtitle
                    }}</span>
                </div>
            </Link>

            <nav class="site-nav">
                <template v-if="variant === 'auth'">
                    <Link :href="home().url" class="nav-btn nav-btn-primary">{{
                        backLabel
                    }}</Link>
                </template>

                <template v-else>
                    <template v-if="isAuthed">
                        <Link
                            :href="dashboard().url"
                            class="nav-btn nav-btn-primary"
                        >
                            <Zap class="nav-btn-icon" />
                            {{ t('nav.dashboard') }}
                        </Link>
                        <Link
                            class="nav-btn nav-btn-ghost"
                            :href="logout().url"
                            method="post"
                            as="button"
                            @click="handleLogout"
                        >
                            {{ t('auth.logout') || 'Log out' }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login().url"
                            class="nav-btn nav-btn-primary"
                        >
                            {{ t('auth.login') }}
                            <ChevronRight class="nav-btn-icon-right" />
                        </Link>
                    </template>
                </template>
            </nav>
        </div>
    </header>
</template>

<style scoped>
.site-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 50;
    backdrop-filter: blur(20px) saturate(180%);
    background: rgba(253, 250, 244, 0.75);
    border-bottom: 1px solid rgba(180, 130, 50, 0.15);
    transition: background 0.3s;
}

@media (prefers-color-scheme: dark) {
    .site-header {
        background: rgba(245, 243, 240, 0.88);
    }
}

.header-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

@media (max-width: 640px) {
    .header-inner {
        padding: 0 1rem;
    }

    .brand-sub {
        display: none;
    }
}

.brand {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    text-decoration: none;
    color: inherit;
}

.logo-wrap {
    position: relative;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-icon {
    width: 22px;
    height: 22px;
    color: #d97706;
    z-index: 1;
    position: relative;
}

.logo-ring {
    position: absolute;
    inset: 0;
    border-radius: 10px;
    background: linear-gradient(135deg, #fef3c7, #fffbeb);
    border: 1px solid #fde68a;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.18);
}

@media (prefers-color-scheme: dark) {
    .logo-ring {
        background: linear-gradient(
            135deg,
            rgba(245, 158, 11, 0.18),
            rgba(245, 158, 11, 0.05)
        );
        border-color: rgba(245, 158, 11, 0.22);
    }
}

.brand-name {
    display: block;
    font-family: 'Fraunces', Georgia, serif;
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    line-height: 1.2;
}

.brand-sub {
    display: block;
    font-size: 0.7rem;
    color: #7a6a55;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.site-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0 1rem;
    height: 38px;
    border-radius: 9px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition:
        transform 0.15s,
        box-shadow 0.15s,
        background 0.15s,
        opacity 0.15s;
    white-space: nowrap;
}

.nav-btn:active {
    transform: scale(0.97);
}

.nav-btn-primary {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    box-shadow:
        0 2px 12px rgba(245, 158, 11, 0.32),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.nav-btn-primary:hover {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    box-shadow: 0 4px 20px rgba(245, 158, 11, 0.48);
    transform: translateY(-1px);
}

.nav-btn-ghost {
    background: transparent;
    color: #7a6a55;
    border: 1px solid rgba(180, 130, 50, 0.15);
}

.nav-btn-ghost:hover {
    background: rgba(245, 158, 11, 0.06);
    color: #1c1510;
    border-color: rgba(245, 158, 11, 0.3);
}

.nav-btn-icon {
    width: 14px;
    height: 14px;
}

.nav-btn-icon-right {
    width: 14px;
    height: 14px;
    transition: transform 0.2s;
}

.nav-btn:hover .nav-btn-icon-right {
    transform: translateX(2px);
}
</style>
