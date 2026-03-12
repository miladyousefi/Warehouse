<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Package,
    BarChart3,
    ArrowRightLeft,
    Truck,
    Boxes,
    ClipboardList,
    Sparkles,
    ChevronRight,
    Zap,
} from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import MarketingNavbar from '@/components/MarketingNavbar.vue';
import { dashboard, login } from '@/routes';

defineProps<{
    demoLogin?: { email: string; password: string };
}>();

const { t } = useI18n();
const mounted = ref(false);

onMounted(() => {
    setTimeout(() => {
        mounted.value = true;
    }, 50);
});

const features = [
    { icon: Package, key: 'products', color: 'amber' },
    { icon: Boxes, key: 'rawMaterials', color: 'orange' },
    { icon: ArrowRightLeft, key: 'movements', color: 'yellow' },
    { icon: Truck, key: 'suppliers', color: 'amber' },
    { icon: ClipboardList, key: 'purchaseOrders', color: 'orange' },
    { icon: BarChart3, key: 'reports', color: 'yellow' },
];
</script>

<template>
    <Head :title="'The Hunger — ' + t('home.title')">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600;9..144,700&family=DM+Sans:wght@300;400;500;600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="welcome-root" :class="{ 'is-mounted': mounted }">
        <!-- Animated background blobs -->
        <div class="blob blob-1" />
        <div class="blob blob-2" />
        <div class="blob blob-3" />
        <!-- Grain overlay -->
        <div class="grain" />

        <!-- ─── Header ─── -->
        <MarketingNavbar :subtitle="t('home.title')" />

        <!-- ─── Hero ─── -->
        <main class="site-main">
            <section class="hero">
                <div class="hero-badge">
                    <Sparkles class="badge-icon" />
                    <span>Inventory Management Platform</span>
                </div>

                <h1 class="hero-title">
                    <span class="title-serif">{{ t('home.subtitle') }}</span>
                </h1>

                <p class="hero-desc">{{ t('home.features.productsDesc') }}</p>

                <div class="hero-actions">
                    <template v-if="$page.props.auth?.user">
                        <Link
                            :href="dashboard().url"
                            class="btn btn-hero-primary"
                        >
                            <Zap class="btn-icon" />
                            {{ t('nav.dashboard') }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login().url" class="btn btn-hero-primary">
                            {{ t('auth.login') }}
                            <ChevronRight class="btn-icon-right" />
                        </Link>
                        <a href="#features" class="btn btn-hero-ghost">
                            Explore features
                        </a>
                    </template>
                </div>

                <!-- Floating stat pills -->
                <div class="stat-pills">
                    <div class="stat-pill">
                        <span class="stat-num">6</span>
                        <span class="stat-label">Modules</span>
                    </div>
                    <div class="stat-pill">
                        <span class="stat-num">∞</span>
                        <span class="stat-label">Products</span>
                    </div>
                    <div class="stat-pill">
                        <span class="stat-num">Live</span>
                        <span class="stat-label">Tracking</span>
                    </div>
                </div>
            </section>

            <!-- ─── Feature cards ─── -->
            <section id="features" class="features-section">
                <div class="features-header">
                    <h2 class="features-title">Everything you need</h2>
                    <p class="features-sub">
                        One platform for your entire supply chain
                    </p>
                </div>

                <div class="feature-grid">
                    <div
                        v-for="(feat, i) in features"
                        :key="feat.key"
                        class="feature-card"
                        :style="{ '--delay': `${i * 80}ms` }"
                    >
                        <div class="card-glow" />
                        <div class="card-icon-wrap">
                            <component :is="feat.icon" class="card-icon" />
                        </div>
                        <h3 class="card-title">
                            {{ t(`home.features.${feat.key}`) }}
                        </h3>
                        <p class="card-desc">
                            {{ t(`home.features.${feat.key}Desc`) }}
                        </p>
                        <div class="card-arrow">
                            <ChevronRight class="arrow-icon" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- ─── Demo credentials ─── -->
            <div v-if="demoLogin && !$page.props.auth?.user" class="demo-panel">
                <div class="demo-inner">
                    <div class="demo-label">
                        <Sparkles class="demo-label-icon" />
                        <span>{{ t('auth.demoCredentials') }}</span>
                    </div>
                    <p class="demo-hint">{{ t('auth.demoHint') }}</p>
                    <div class="demo-creds">
                        <div class="cred-row">
                            <span class="cred-key">Email</span>
                            <span class="cred-val">{{ demoLogin.email }}</span>
                        </div>
                        <div class="cred-row">
                            <span class="cred-key">Password</span>
                            <span class="cred-val">{{
                                demoLogin.password
                            }}</span>
                        </div>
                    </div>
                    <Link :href="login().url" class="btn btn-demo">
                        Try the demo
                        <ChevronRight class="btn-icon-right" />
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
/* ── Fonts & tokens ── */
*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

.welcome-root {
    --font-display: 'Fraunces', Georgia, serif;
    --font-body: 'DM Sans', sans-serif;

    --amber-50: #fffbeb;
    --amber-100: #fef3c7;
    --amber-200: #fde68a;
    --amber-400: #fbbf24;
    --amber-500: #f59e0b;
    --amber-600: #d97706;
    --amber-700: #b45309;
    --amber-800: #92400e;
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

/* "Dark" mode → warm light slate, keeps orange accents */
@media (prefers-color-scheme: dark) {
    .welcome-root {
        --bg: #f5f3f0;
        --surface: #faf8f5;
        --border: rgba(180, 130, 50, 0.16);
        --text: #1a1410;
        --muted: #6b5c48;
        --card-bg: rgba(255, 253, 248, 0.92);
    }
}

/* ── Background blobs ── */
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

/* Grain */
.grain {
    position: fixed;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    background-size: 180px;
    opacity: 0.4;
}

/* ── Buttons ── */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0 1rem;
    height: 38px;
    border-radius: 9px;
    font-family: var(--font-body);
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
.btn:active {
    transform: scale(0.97);
}

.btn-icon {
    width: 14px;
    height: 14px;
}
.btn-icon-right {
    width: 14px;
    height: 14px;
    transition: transform 0.2s;
}
.btn:hover .btn-icon-right {
    transform: translateX(2px);
}

/* ── Main ── */
.site-main {
    position: relative;
    z-index: 2;
    max-width: 1200px;
    margin: 0 auto;
    padding: 64px 1.5rem 5rem;
}

/* ── Hero ── */
.hero {
    padding: 5rem 0 4rem;
    max-width: 680px;
    opacity: 0;
    transform: translateY(24px);
    transition:
        opacity 0.8s ease,
        transform 0.8s ease;
}
.is-mounted .hero {
    opacity: 1;
    transform: translateY(0);
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.875rem;
    border-radius: 999px;
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.1),
        rgba(251, 191, 36, 0.07)
    );
    border: 1px solid rgba(245, 158, 11, 0.22);
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--amber-700);
    letter-spacing: 0.04em;
    margin-bottom: 1.75rem;
    animation: badgePop 0.6s 0.2s both;
}
@media (prefers-color-scheme: dark) {
    .hero-badge {
        color: var(--amber-600);
    }
}

.badge-icon {
    width: 13px;
    height: 13px;
    animation: spin 4s linear infinite;
}
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
@keyframes badgePop {
    from {
        opacity: 0;
        transform: scale(0.85) translateY(8px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.hero-title {
    font-family: var(--font-display);
    font-size: clamp(2.6rem, 6vw, 4.2rem);
    font-weight: 400;
    line-height: 1.1;
    letter-spacing: -0.02em;
    margin-bottom: 1.25rem;
    color: var(--text);
}
.title-serif {
    background: linear-gradient(135deg, var(--text) 0%, var(--amber-700) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
@media (prefers-color-scheme: dark) {
    .title-serif {
        background: linear-gradient(
            135deg,
            var(--text) 0%,
            var(--amber-600) 100%
        );
        -webkit-background-clip: text;
        background-clip: text;
    }
}

.hero-desc {
    font-size: 1.05rem;
    line-height: 1.7;
    color: var(--muted);
    max-width: 520px;
    margin-bottom: 2.25rem;
}

.hero-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 3rem;
}

.btn-hero-primary {
    height: 48px;
    padding: 0 1.5rem;
    font-size: 0.95rem;
    background: linear-gradient(135deg, var(--amber-500), var(--amber-600));
    color: #fff;
    border-radius: 12px;
    box-shadow:
        0 4px 20px rgba(245, 158, 11, 0.38),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    font-weight: 500;
    transition:
        transform 0.2s,
        box-shadow 0.2s;
}
.btn-hero-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(245, 158, 11, 0.5);
}

.btn-hero-ghost {
    height: 48px;
    padding: 0 1.5rem;
    font-size: 0.95rem;
    font-weight: 500;
    border-radius: 12px;
    border: 1px solid var(--border);
    color: var(--muted);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    background: var(--card-bg);
    backdrop-filter: blur(8px);
    transition:
        transform 0.2s,
        border-color 0.2s,
        color 0.2s;
}
.btn-hero-ghost:hover {
    transform: translateY(-2px);
    border-color: rgba(245, 158, 11, 0.4);
    color: var(--text);
}

/* Stat pills */
.stat-pills {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.stat-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.75rem 1.25rem;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    backdrop-filter: blur(10px);
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.04);
    min-width: 80px;
    transition:
        transform 0.2s,
        box-shadow 0.2s;
}
.stat-pill:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.14);
}
.stat-num {
    font-family: var(--font-display);
    font-size: 1.5rem;
    font-weight: 500;
    color: var(--amber-600);
    line-height: 1;
}
@media (prefers-color-scheme: dark) {
    .stat-num {
        color: var(--amber-600);
    }
}
.stat-label {
    font-size: 0.68rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-top: 0.25rem;
}

/* ── Features ── */
.features-section {
    padding-bottom: 3rem;
}

.features-header {
    text-align: center;
    margin-bottom: 3rem;
    opacity: 0;
    transform: translateY(16px);
    transition:
        opacity 0.7s 0.3s ease,
        transform 0.7s 0.3s ease;
}
.is-mounted .features-header {
    opacity: 1;
    transform: translateY(0);
}

.features-title {
    font-family: var(--font-display);
    font-size: clamp(1.8rem, 3.5vw, 2.4rem);
    font-weight: 400;
    letter-spacing: -0.015em;
    margin-bottom: 0.5rem;
}
.features-sub {
    color: var(--muted);
    font-size: 1rem;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.25rem;
}

.feature-card {
    position: relative;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 1.75rem;
    backdrop-filter: blur(16px);
    overflow: hidden;
    cursor: default;

    opacity: 0;
    transform: translateY(20px);
    transition:
        opacity 0.6s var(--delay, 0ms) ease,
        transform 0.6s var(--delay, 0ms) ease,
        box-shadow 0.25s,
        border-color 0.25s;
}
.is-mounted .feature-card {
    opacity: 1;
    transform: translateY(0);
}
.feature-card:hover {
    box-shadow:
        0 12px 40px rgba(245, 158, 11, 0.13),
        0 2px 8px rgba(0, 0, 0, 0.05);
    border-color: rgba(245, 158, 11, 0.28);
    transform: translateY(-4px);
}

.card-glow {
    position: absolute;
    width: 140px;
    height: 140px;
    top: -40px;
    right: -40px;
    background: radial-gradient(
        circle,
        rgba(245, 158, 11, 0.13) 0%,
        transparent 70%
    );
    border-radius: 50%;
    transition:
        transform 0.4s,
        opacity 0.4s;
    opacity: 0;
}
.feature-card:hover .card-glow {
    opacity: 1;
    transform: scale(1.3);
}

.card-icon-wrap {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: linear-gradient(
        135deg,
        var(--amber-100),
        rgba(255, 253, 248, 0.5)
    );
    border: 1px solid rgba(245, 158, 11, 0.18);
    margin-bottom: 1rem;
    transition:
        transform 0.3s,
        box-shadow 0.3s;
}
@media (prefers-color-scheme: dark) {
    .card-icon-wrap {
        background: linear-gradient(
            135deg,
            rgba(245, 158, 11, 0.14),
            rgba(245, 158, 11, 0.04)
        );
        border-color: rgba(245, 158, 11, 0.18);
    }
}
.feature-card:hover .card-icon-wrap {
    transform: rotate(-6deg) scale(1.1);
    box-shadow: 0 4px 16px rgba(245, 158, 11, 0.22);
}

.card-icon {
    width: 20px;
    height: 20px;
    color: var(--amber-600);
}
@media (prefers-color-scheme: dark) {
    .card-icon {
        color: var(--amber-600);
    }
}

.card-title {
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text);
}
.card-desc {
    font-size: 0.83rem;
    line-height: 1.65;
    color: var(--muted);
}

.card-arrow {
    position: absolute;
    bottom: 1.25rem;
    right: 1.25rem;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: translateX(-4px);
    transition:
        opacity 0.25s,
        transform 0.25s;
}
.feature-card:hover .card-arrow {
    opacity: 1;
    transform: translateX(0);
}
.arrow-icon {
    width: 14px;
    height: 14px;
    color: var(--amber-500);
}

/* ── Demo panel ── */
.demo-panel {
    margin-top: 2rem;
    opacity: 0;
    transform: translateY(16px);
    transition:
        opacity 0.6s 0.5s ease,
        transform 0.6s 0.5s ease;
}
.is-mounted .demo-panel {
    opacity: 1;
    transform: translateY(0);
}

.demo-inner {
    display: inline-block;
    max-width: 420px;
    background: linear-gradient(
        135deg,
        rgba(240, 249, 255, 0.95),
        rgba(255, 255, 255, 0.92)
    );
    border: 1.5px dashed rgba(245, 158, 11, 0.35);
    border-radius: 20px;
    padding: 1.75rem 2rem;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 24px rgba(245, 158, 11, 0.08);
}
@media (prefers-color-scheme: dark) {
    .demo-inner {
        background: linear-gradient(
            135deg,
            rgba(224, 242, 254, 0.9),
            rgba(255, 255, 255, 0.88)
        );
    }
}

.demo-label {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--amber-700);
    margin-bottom: 0.5rem;
}
@media (prefers-color-scheme: dark) {
    .demo-label {
        color: var(--amber-700);
    }
}
.demo-label-icon {
    width: 12px;
    height: 12px;
}

.demo-hint {
    font-size: 0.83rem;
    color: var(--muted);
    margin-bottom: 1.25rem;
    line-height: 1.5;
}

.demo-creds {
    font-family: 'Courier New', monospace;
    font-size: 0.82rem;
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    margin-bottom: 1.5rem;
    background: rgba(245, 158, 11, 0.05);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 0.875rem 1rem;
}
.cred-row {
    display: flex;
    gap: 0.75rem;
}
.cred-key {
    color: var(--muted);
    min-width: 72px;
}
.cred-val {
    color: var(--amber-700);
    font-weight: 500;
}
@media (prefers-color-scheme: dark) {
    .cred-val {
        color: var(--amber-700);
    }
}

.btn-demo {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 40px;
    padding: 0 1.25rem;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--amber-500), var(--amber-600));
    color: #fff;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    box-shadow: 0 3px 14px rgba(245, 158, 11, 0.32);
    transition:
        transform 0.2s,
        box-shadow 0.2s;
}
.btn-demo:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 22px rgba(245, 158, 11, 0.48);
}

/* ── Responsive ── */
@media (max-width: 640px) {
    .site-main {
        padding: 0 1rem 3rem;
    }
    .hero {
        padding: 3rem 0 2.5rem;
    }
    .feature-grid {
        grid-template-columns: 1fr;
    }
}
</style>
