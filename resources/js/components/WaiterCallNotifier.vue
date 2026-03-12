<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { BellRing } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Button } from '@/components/ui/button';

const show = ref(false);
const tableLabel = ref<string | null>(null);
const note = ref<string | null>(null);

const page = usePage();
const permissions = computed(
    () =>
        (page.props.auth?.user as { permissions?: string[] } | undefined)
            ?.permissions ?? [],
);
const canSeeCalls = computed(
    () =>
        permissions.value.includes('restaurant_orders.view') ||
        permissions.value.includes('restaurant_orders.edit') ||
        permissions.value.includes('restaurant_orders.calls.handle'),
);

function notifyWithSoundAndVoice(message: string): void {
    try {
        const audioContext = new (
            window.AudioContext || (window as any).webkitAudioContext
        )();
        const oscillator = audioContext.createOscillator();
        const gain = audioContext.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = 988;
        gain.gain.value = 0.0001;
        oscillator.connect(gain);
        gain.connect(audioContext.destination);
        oscillator.start();
        gain.gain.exponentialRampToValueAtTime(
            0.2,
            audioContext.currentTime + 0.02,
        );
        gain.gain.exponentialRampToValueAtTime(
            0.0001,
            audioContext.currentTime + 0.45,
        );
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

function openCall(payload: any): void {
    tableLabel.value =
        payload?.table?.name || payload?.table?.table_number || null;
    note.value = payload?.note || null;
    show.value = true;

    const text = tableLabel.value
        ? `Waiter call from table ${tableLabel.value}`
        : 'New waiter call';
    notifyWithSoundAndVoice(text);
}

function closeModal(): void {
    show.value = false;
}

function setupListener(): void {
    const w = window as any;
    if (!canSeeCalls.value || !w.Echo) return;

    w.Echo.private('restaurant-calls').listen(
        '.waiter.called',
        (payload: any) => {
            openCall(payload ?? {});
        },
    );
}

function cleanupListener(): void {
    const w = window as any;
    if (!w.Echo) return;
    w.Echo.leave('private-restaurant-calls');
}

onMounted(() => {
    setupListener();
});

onUnmounted(() => {
    cleanupListener();
});
</script>

<template>
    <div
        v-if="show && canSeeCalls"
        class="fixed inset-0 z-[120] flex items-center justify-center bg-black/35 p-4"
    >
        <div
            class="w-full max-w-sm rounded-lg border bg-background p-4 shadow-xl"
        >
            <div class="flex items-start gap-3">
                <div class="rounded-full bg-amber-100 p-2 text-amber-700">
                    <BellRing class="h-4 w-4" />
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold">Waiter Call</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            tableLabel
                                ? `Table: ${tableLabel}`
                                : 'A table called waiter.'
                        }}
                    </p>
                    <p v-if="note" class="mt-1 text-xs text-muted-foreground">
                        Note: {{ note }}
                    </p>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2">
                <Button size="sm" variant="outline" @click="closeModal"
                    >Close</Button
                >
            </div>
        </div>
    </div>
</template>
