<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import MarketingNavbar from '@/components/MarketingNavbar.vue';

defineProps<{
    title?: string;
    description?: string;
}>();

const mounted = ref(false);

onMounted(() => {
    setTimeout(() => {
        mounted.value = true;
    }, 50);
});
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600;9..144,700&family=DM+Sans:wght@300;400;500;600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="auth-root" :class="{ 'is-mounted': mounted }">
        <div class="blob blob-1" />
        <div class="blob blob-2" />
        <div class="blob blob-3" />
        <div class="grain" />

        <MarketingNavbar variant="auth" subtitle="Warehouse" />

        <main class="auth-main">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="card-logo" aria-hidden="true">
                        <div class="logo-wrap">
                            <AppLogoIcon class="logo-icon" />
                            <div class="logo-ring" />
                        </div>
                    </div>
                    <h1 class="auth-title">{{ title }}</h1>
                    <p v-if="description" class="auth-desc">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </main>
    </div>
</template>

<style scoped>
*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

.auth-root {
    --font-display: 'Fraunces', Georgia, serif;
    --font-body: 'DM Sans', sans-serif;

    --amber-50: #fffbeb;
    --amber-100: #fef3c7;
    --amber-200: #fde68a;
    --amber-400: #fbbf24;
    --amber-500: #f59e0b;
    --amber-600: #d97706;
    --amber-700: #b45309;
    --amber-900: #78350f;

    --bg: #fdfaf4;
    --surface: #fffdf8;
    --border: rgba(180, 130, 50, 0.15);
    --text: #1c1510;
    --muted: #7a6a55;
    --card-bg: rgba(255, 253, 248, 0.9);

    font-family: var(--font-body);
    background: var(--bg);
    color: var(--text);
    min-height: 100svh;
    position: relative;
    overflow-x: hidden;
}

@media (prefers-color-scheme: dark) {
    .auth-root {
        --bg: #f5f3f0;
        --surface: #faf8f5;
        --border: rgba(180, 130, 50, 0.16);
        --text: #1a1410;
        --muted: #6b5c48;
        --card-bg: rgba(255, 253, 248, 0.92);
    }
}

.blob {
    position: fixed;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
    z-index: 0;
    opacity: 0;
    transition: opacity 1.2s ease;
}
.is-mounted .blob {
    opacity: 1;
}

.blob-1 {
    width: 600px;
    height: 600px;
    background: radial-gradient(
        circle,
        rgba(99, 179, 237, 0.28) 0%,
        transparent 70%
    );
    top: -150px;
    left: -150px;
    animation: float1 18s ease-in-out infinite;
}
.blob-2 {
    width: 500px;
    height: 500px;
    background: radial-gradient(
        circle,
        rgba(245, 158, 11, 0.12) 0%,
        transparent 70%
    );
    top: 40%;
    right: -120px;
    animation: float2 22s ease-in-out infinite;
}
.blob-3 {
    width: 400px;
    height: 400px;
    background: radial-gradient(
        circle,
        rgba(251, 191, 36, 0.1) 0%,
        transparent 70%
    );
    bottom: 0;
    left: 30%;
    animation: float3 16s ease-in-out infinite;
}

@keyframes float1 {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(40px, 60px) scale(1.1);
    }
    66% {
        transform: translate(-30px, 30px) scale(0.95);
    }
}
@keyframes float2 {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    50% {
        transform: translate(-50px, -40px) scale(1.08);
    }
}
@keyframes float3 {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    40% {
        transform: translate(30px, -50px) scale(1.05);
    }
    70% {
        transform: translate(-20px, 20px) scale(0.97);
    }
}

.grain {
    position: fixed;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    background-size: 180px;
    opacity: 0.4;
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
    color: var(--amber-600);
    z-index: 1;
    position: relative;
}
.logo-ring {
    position: absolute;
    inset: 0;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--amber-100), var(--amber-50));
    border: 1px solid var(--amber-200);
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

.auth-main {
    position: relative;
    z-index: 2;
    min-height: 100svh;
    display: grid;
    place-items: center;
    padding: 88px 1.5rem 3rem;
}

.auth-card {
    width: min(520px, 100%);
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 18px;
    backdrop-filter: blur(10px);
    box-shadow: 0 18px 60px rgba(28, 21, 16, 0.08);
    padding: 1.5rem;
}

.card-logo {
    display: flex;
    justify-content: center;
    margin-bottom: 0.75rem;
}
.card-logo .logo-wrap {
    width: 56px;
    height: 56px;
}
.card-logo .logo-icon {
    width: 28px;
    height: 28px;
}
.card-logo .logo-ring {
    border-radius: 16px;
}

.auth-header {
    text-align: center;
    margin-bottom: 1.25rem;
}
.auth-title {
    font-family: var(--font-display);
    font-size: 1.4rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    margin-bottom: 0.35rem;
}
.auth-desc {
    font-size: 0.9rem;
    color: var(--muted);
    line-height: 1.5;
}
</style>
