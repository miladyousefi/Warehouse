<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Button } from '@/components/ui/button';

const show = ref(false);
const orderCode = ref<string | null>(null);
const tableLabel = ref<string | null>(null);
const reason = ref<string | null>(null);

const page = usePage();
const permissions = computed(() => ((page.props.auth?.user as { permissions?: string[] } | undefined)?.permissions ?? []));
const canSeeOrders = computed(() =>
    permissions.value.includes('restaurant_orders.view')
    || permissions.value.includes('restaurant_orders.edit')
    || permissions.value.includes('restaurant_orders.take_order')
    || permissions.value.includes('restaurant_orders.monitor.confirm_cancel')
);

function notifyWithSoundAndVoice(message: string): void {
    try {
        const audioContext = new (window.AudioContext || (window as any).webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gain = audioContext.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = 740;
        gain.gain.value = 0.0001;
        oscillator.connect(gain);
        gain.connect(audioContext.destination);
        oscillator.start();
        gain.gain.exponentialRampToValueAtTime(0.2, audioContext.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.45);
        oscillator.stop(audioContext.currentTime + 0.5);
    } catch {
        // no-op
    }

    try {
        if ('speechSynthesis' in window) {
            const utterance = new SpeechSynthesisUtterance(message);
            utterance.lang = 'en-US';
            utterance.rate = 1;
            window.speechSynthesis.speak(utterance);
        }
    } catch {
        // no-op
    }
}

function openCancel(payload: any): void {
    orderCode.value = payload?.order_code || null;
    tableLabel.value = payload?.table?.name || payload?.table?.table_number || null;
    reason.value = payload?.cancel_reason || null;
    show.value = true;

    const text = orderCode.value
        ? `Order cancelled. ${orderCode.value}`
        : 'Order cancelled';
    notifyWithSoundAndVoice(text);
}

function closeModal(): void {
    show.value = false;
}

function setupListener(): void {
    const w = window as any;
    if (!canSeeOrders.value || !w.Echo) return;

    w.Echo.private('restaurant-orders').listen('.order.updated', (payload: any) => {
        const nowCancelled = (payload?.status || '') === 'cancelled';
        const wasCancelled = (payload?.previous_status || '') === 'cancelled';

        if (nowCancelled && !wasCancelled) {
            openCancel(payload ?? {});
        }
    });
}

function cleanupListener(): void {
    const w = window as any;
    if (!w.Echo) return;
    w.Echo.leave('private-restaurant-orders');
}

onMounted(() => setupListener());
onUnmounted(() => cleanupListener());
</script>

<template>
    <div v-if="show && canSeeOrders" class="fixed inset-0 z-[121] flex items-center justify-center bg-black/35 p-4">
        <div class="w-full max-w-sm rounded-lg border bg-background p-4 shadow-xl">
            <div class="flex items-start gap-3">
                <div class="rounded-full bg-rose-100 p-2 text-rose-700">
                    <AlertTriangle class="h-4 w-4" />
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold">Order Cancelled</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ orderCode ? `Order: ${orderCode}` : 'An order was cancelled.' }}
                    </p>
                    <p v-if="tableLabel" class="mt-1 text-xs text-muted-foreground">Table: {{ tableLabel }}</p>
                    <p v-if="reason" class="mt-1 text-xs text-muted-foreground">Reason: {{ reason }}</p>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2">
                <Button size="sm" variant="outline" @click="closeModal">Close</Button>
            </div>
        </div>
    </div>
</template>
