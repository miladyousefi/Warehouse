<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import {
    BellRing,
    Bot,
    Check,
    ChevronRight,
    CornerDownLeft,
    Loader,
    SendHorizontal,
    SmilePlus,
    Sparkles,
} from 'lucide-vue-next'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'

type Message = {
    id?: string
    role: 'user' | 'assistant'
    content: string
    timestamp?: Date
}

type Conversation = {
    id: string
    title: string
    created_at?: string
}

type AuthUser = {
    id: number
    name: string
    avatar?: string | null
    avatar_url?: string | null
}

const QUICK_EMOJIS = ['🙂', '🔥', '✅', '📦', '📈', '💡', '🚚', '🧾', '⚡', '🎯']
const SUGGESTIONS = [
    'Count my products',
    'How many categories do I have?',
    'Show super admin name and permissions',
    'What is low in stock today?',
]

const page = usePage()
const messagesContainer = ref<HTMLElement>()
const textareaRef = ref<HTMLTextAreaElement>()

const conversationId = ref('')
const messages = ref<Message[]>([])
const inputMessage = ref('')
const isLoading = ref(false)
const isNotificationLoading = ref(false)
const error = ref('')
const emojiTrayOpen = ref(false)
const notificationPermission = ref<NotificationPermission>('default')
const notificationSupported = ref(false)

const authUser = computed(() => (page.props.auth as { user?: AuthUser } | undefined)?.user ?? null)

const csrfToken =
    (
        document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null
    )?.content || ''

const conversations = computed(() => {
    return ((page.props.conversations as Conversation[] | undefined) || []).slice(0, 8)
})

const canSend = computed(() => inputMessage.value.trim().length > 0 && !isLoading.value)
const messageCountLabel = computed(() => `${messages.value.length} message${messages.value.length === 1 ? '' : 's'}`)
const notificationBadgeLabel = computed(() => {
    if (!notificationSupported.value) return 'Notifications unavailable'
    if (notificationPermission.value === 'granted') return 'Notifications on'
    if (notificationPermission.value === 'denied') return 'Notifications blocked'
    return 'Notifications off'
})
const userInitial = computed(() => authUser.value?.name?.trim().charAt(0).toUpperCase() || 'U')

const syncTextareaHeight = () => {
    const textarea = textareaRef.value
    if (!textarea) return

    textarea.style.height = '0px'
    textarea.style.height = `${Math.min(textarea.scrollHeight, 180)}px`
}

const scrollToBottom = async () => {
    await nextTick()
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
}

const parseJson = async (response: Response) => {
    const text = await response.text()

    if (!text) {
        return {}
    }

    try {
        return JSON.parse(text)
    } catch {
        return { message: text }
    }
}

const formatTime = (date?: Date) => {
    if (!date) return ''

    return new Intl.DateTimeFormat(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    }).format(date)
}

const insertSuggestion = (value: string) => {
    inputMessage.value = value
    syncTextareaHeight()
    textareaRef.value?.focus()
}

const insertEmoji = (emoji: string) => {
    inputMessage.value = `${inputMessage.value}${emoji}`
    emojiTrayOpen.value = false
    syncTextareaHeight()
    textareaRef.value?.focus()
}

const setNotificationPermissionState = () => {
    notificationSupported.value =
        typeof window !== 'undefined'
        && 'Notification' in window
        && 'serviceWorker' in navigator
        && 'PushManager' in window

    if (notificationSupported.value) {
        notificationPermission.value = Notification.permission
    }
}

const urlBase64ToUint8Array = (base64String: string) => {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
    const rawData = window.atob(base64)
    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)))
}

const enableNotifications = async () => {
    if (!notificationSupported.value || isNotificationLoading.value) {
        return
    }

    isNotificationLoading.value = true
    error.value = ''

    try {
        const permission = await Notification.requestPermission()
        notificationPermission.value = permission

        if (permission !== 'granted') {
            throw new Error('Notification permission was not granted.')
        }

        const configResponse = await fetch('/api/push/config', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })

        const configData = await parseJson(configResponse)
        if (!configResponse.ok || !configData.public_key) {
            throw new Error(configData.error || 'Push configuration is not ready yet.')
        }

        const registration = await navigator.serviceWorker.ready
        let subscription = await registration.pushManager.getSubscription()

        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(configData.public_key),
            })
        }

        const subscriptionPayload = subscription.toJSON()

        const subscribeResponse = await fetch('/api/push/subscriptions', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(subscriptionPayload),
        })

        const subscribeData = await parseJson(subscribeResponse)
        if (!subscribeResponse.ok) {
            throw new Error(subscribeData.error || 'Failed to save push subscription.')
        }

        await fetch('/api/push/test', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Failed to enable notifications.'
    } finally {
        isNotificationLoading.value = false
    }
}

const sendMessage = async () => {
    if (!canSend.value) return

    error.value = ''

    const userInput = inputMessage.value.trim()
    messages.value.push({
        role: 'user',
        content: userInput,
        timestamp: new Date(),
    })

    inputMessage.value = ''
    emojiTrayOpen.value = false
    isLoading.value = true
    syncTextareaHeight()

    await scrollToBottom()

    try {
        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page.')
        }

        let convId = conversationId.value
        if (!convId) {
            const convResponse = await fetch('/api/ai/conversation', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    title: userInput.substring(0, 50) + (userInput.length > 50 ? '...' : ''),
                }),
            })

            const convData = await parseJson(convResponse)
            if (!convResponse.ok) {
                throw new Error(convData.error || convData.message || 'Failed to create conversation')
            }

            conversationId.value = convData.data?.id || convData.data?.conversation?.id || ''
            convId = conversationId.value
        }

        if (!convId) {
            throw new Error('Conversation could not be created.')
        }

        const response = await fetch(`/api/ai/conversation/${convId}/message`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                message: userInput,
                include_data: 'auto',
            }),
        })

        const data = await parseJson(response)
        if (!response.ok) {
            throw new Error(data.error || data.message || `API error: ${response.status}`)
        }

        messages.value.push({
            role: 'assistant',
            content: data.data?.response || data.response || 'No response from AI',
            timestamp: new Date(),
        })

        await scrollToBottom()
    } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to send message'
        error.value = errorMessage
        messages.value.push({
            role: 'assistant',
            content: `Error: ${errorMessage}`,
            timestamp: new Date(),
        })
        await scrollToBottom()
    } finally {
        isLoading.value = false
        textareaRef.value?.focus()
    }
}

const handleKeypress = (event: KeyboardEvent) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault()
        sendMessage()
    }
}

watch(inputMessage, () => {
    syncTextareaHeight()
})

onMounted(async () => {
    setNotificationPermissionState()

    if (page.props.conversation) {
        const conv = page.props.conversation as { id: string }
        conversationId.value = conv.id
    }

    if (page.props.messages) {
        messages.value = (
            page.props.messages as Array<Omit<Message, 'timestamp'> & { created_at?: string }>
        ).map((msg) => ({
            ...msg,
            timestamp: msg.created_at ? new Date(msg.created_at) : new Date(),
        }))
    }

    syncTextareaHeight()
    await scrollToBottom()
})
</script>

<template>
    <Head title="AI Chat" />
    <AppLayout :breadcrumbs="[{ title: 'AI Chat', href: '/ai/chat' }]">
        <div class="chat-page min-h-0 p-3 md:p-6">
            <Card class="chat-shell mx-auto flex h-[calc(100vh-7.75rem)] min-h-[38rem] w-full max-w-7xl flex-col overflow-hidden border-white/40 md:h-[calc(100vh-9rem)]">
                <CardHeader class="relative shrink-0 border-b border-white/45 bg-white/65 px-4 py-4 backdrop-blur-2xl md:px-6">
                    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,173,92,0.25),_transparent_45%),radial-gradient(circle_at_top_right,_rgba(255,236,199,0.45),_transparent_35%)]"></div>

                    <div class="relative flex flex-col gap-4">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                            <div class="space-y-2">
                                <div class="inline-flex items-center gap-2 rounded-full border border-primary/15 bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                    <Sparkles class="h-3.5 w-3.5" />
                                    Smart warehouse assistant
                                </div>
                                <div>
                                    <CardTitle class="chat-display text-2xl tracking-tight md:text-3xl">AI Command Center</CardTitle>
                                    <CardDescription class="mt-1 max-w-2xl text-sm md:text-[15px]">
                                        Fast answers, smoother mobile chat, quick emoji reactions, and push-ready notifications for installed devices.
                                    </CardDescription>
                                </div>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-2 xl:min-w-[22rem]">
                                <div class="rounded-2xl border border-white/50 bg-white/70 px-4 py-3 shadow-[0_10px_40px_-28px_rgba(31,20,8,0.85)] backdrop-blur-xl">
                                    <p class="text-[11px] uppercase tracking-[0.25em] text-muted-foreground">Conversation</p>
                                    <p class="mt-2 text-sm font-semibold">{{ messageCountLabel }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-2xl border border-white/50 bg-white/70 px-4 py-3 text-left shadow-[0_10px_40px_-28px_rgba(31,20,8,0.85)] backdrop-blur-xl transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-70"
                                    :disabled="isNotificationLoading || notificationPermission === 'granted'"
                                    @click="enableNotifications"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-[11px] uppercase tracking-[0.25em] text-muted-foreground">Alerts</p>
                                            <p class="mt-2 text-sm font-semibold">{{ notificationBadgeLabel }}</p>
                                        </div>
                                        <BellRing class="mt-0.5 h-4 w-4 text-primary" />
                                    </div>
                                    <p class="mt-2 text-xs text-muted-foreground">
                                        {{ notificationPermission === 'granted' ? 'Installed devices can receive push alerts.' : 'Enable browser permission for mobile notifications.' }}
                                    </p>
                                </button>
                            </div>
                        </div>

                        <div class="flex gap-2 overflow-x-auto pb-1 lg:hidden">
                            <a
                                v-for="conversation in conversations"
                                :key="conversation.id"
                                :href="`/ai/chat/${conversation.id}`"
                                class="min-w-[14rem] rounded-2xl border border-white/50 bg-white/60 px-4 py-3 text-sm shadow-[0_10px_40px_-28px_rgba(31,20,8,0.85)] backdrop-blur-xl transition hover:-translate-y-0.5"
                            >
                                <p class="truncate font-medium">{{ conversation.title }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ conversation.created_at ? new Date(conversation.created_at).toLocaleDateString() : 'Draft' }}
                                </p>
                            </a>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="grid min-h-0 flex-1 gap-0 bg-[linear-gradient(180deg,rgba(255,252,247,0.92),rgba(255,244,228,0.82))] px-0 dark:bg-[linear-gradient(180deg,rgba(31,21,17,0.96),rgba(25,18,15,0.98))] lg:grid-cols-[290px_minmax(0,1fr)]">
                    <aside class="hidden min-h-0 border-r border-white/30 bg-white/30 lg:flex lg:flex-col">
                        <div class="border-b border-white/35 px-5 py-5">
                            <p class="text-xs uppercase tracking-[0.22em] text-muted-foreground">Recent chats</p>
                            <p class="mt-2 text-base font-semibold">Jump back into a conversation</p>
                        </div>

                        <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-4">
                            <a
                                v-for="conversation in conversations"
                                :key="conversation.id"
                                :href="`/ai/chat/${conversation.id}`"
                                class="group block rounded-2xl border border-white/45 bg-white/65 px-4 py-4 shadow-[0_16px_50px_-34px_rgba(31,20,8,0.85)] backdrop-blur-xl transition duration-200 hover:-translate-y-0.5 hover:border-primary/25 hover:bg-white"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">{{ conversation.title }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">
                                            {{ conversation.created_at ? new Date(conversation.created_at).toLocaleDateString() : 'Draft' }}
                                        </p>
                                    </div>
                                    <ChevronRight class="h-4 w-4 text-muted-foreground transition group-hover:text-primary" />
                                </div>
                            </a>
                        </div>

                        <div class="border-t border-white/35 px-4 py-4">
                            <div class="rounded-2xl border border-primary/15 bg-primary/10 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.22em] text-primary/80">Quick asks</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button
                                        v-for="suggestion in SUGGESTIONS"
                                        :key="suggestion"
                                        type="button"
                                        class="rounded-full border border-primary/15 bg-white/80 px-3 py-1.5 text-xs font-medium text-foreground transition hover:border-primary/30 hover:text-primary"
                                        @click="insertSuggestion(suggestion)"
                                    >
                                        {{ suggestion }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <div class="flex min-h-0 flex-1 flex-col">
                        <div
                            ref="messagesContainer"
                            class="chat-scroll min-h-0 flex-1 overflow-y-auto px-3 py-4 md:px-6 md:py-6"
                        >
                            <div v-if="messages.length === 0" class="flex h-full min-h-[20rem] items-center justify-center">
                                <div class="max-w-2xl text-center">
                                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-[1.35rem] border border-primary/20 bg-gradient-to-br from-primary/15 to-white text-primary shadow-[0_18px_55px_-30px_rgba(193,90,23,0.9)]">
                                        <Bot class="h-8 w-8" />
                                    </div>
                                    <p class="chat-display text-2xl font-semibold md:text-3xl">Ask anything about your warehouse</p>
                                    <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-muted-foreground md:text-base">
                                        Inventory counts, categories, permissions, stock questions, and daily operations all in one polished chat workspace.
                                    </p>
                                    <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                                        <button
                                            v-for="suggestion in SUGGESTIONS"
                                            :key="suggestion"
                                            type="button"
                                            class="rounded-full border border-white/55 bg-white/80 px-4 py-2 text-sm font-medium shadow-[0_12px_45px_-32px_rgba(31,20,8,0.85)] transition hover:-translate-y-0.5 hover:border-primary/25 hover:text-primary"
                                            @click="insertSuggestion(suggestion)"
                                        >
                                            {{ suggestion }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="space-y-4 md:space-y-5">
                                <div
                                    v-for="(msg, idx) in messages"
                                    :key="msg.id || idx"
                                    class="chat-in flex items-end gap-3"
                                    :class="{ 'justify-end': msg.role === 'user', 'justify-start': msg.role === 'assistant' }"
                                >
                                    <div
                                        v-if="msg.role === 'assistant'"
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 text-primary shadow-[0_14px_40px_-28px_rgba(193,90,23,0.85)]"
                                    >
                                        <Bot class="h-4 w-4" />
                                    </div>

                                    <div
                                        class="max-w-[92%] rounded-[1.5rem] px-4 py-3 md:max-w-[76%] md:px-5"
                                        :class="
                                            msg.role === 'user'
                                                ? 'bg-gradient-to-br from-primary to-[hsl(24_86%_42%)] text-primary-foreground shadow-[0_20px_45px_-30px_rgba(193,90,23,0.95)]'
                                                : 'border border-white/50 bg-white/80 text-foreground shadow-[0_18px_50px_-34px_rgba(31,20,8,0.85)] backdrop-blur-xl dark:border-white/10 dark:bg-white/7'
                                        "
                                    >
                                        <p class="whitespace-pre-wrap break-words text-sm leading-6 md:text-[15px]">{{ msg.content }}</p>
                                        <div class="mt-3 flex items-center justify-between gap-3">
                                            <p
                                                v-if="msg.timestamp"
                                                class="text-[11px]"
                                                :class="msg.role === 'user' ? 'text-primary-foreground/75' : 'text-muted-foreground'"
                                            >
                                                {{ formatTime(msg.timestamp) }}
                                            </p>
                                            <span
                                                class="rounded-full px-2 py-1 text-[10px] uppercase tracking-[0.22em]"
                                                :class="msg.role === 'user' ? 'bg-white/14 text-primary-foreground/80' : 'bg-primary/8 text-primary/85'"
                                            >
                                                {{ msg.role === 'user' ? 'You' : 'AI' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        v-if="msg.role === 'user'"
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/35 bg-white/70 text-foreground shadow-[0_16px_40px_-30px_rgba(31,20,8,0.85)] backdrop-blur-xl"
                                    >
                                        <img
                                            v-if="authUser?.avatar_url"
                                            :src="authUser.avatar_url"
                                            alt="User avatar"
                                            class="h-full w-full rounded-2xl object-cover"
                                        />
                                        <span v-else class="text-sm font-semibold">{{ userInitial }}</span>
                                    </div>
                                </div>

                                <div v-if="isLoading" class="flex items-end gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 text-primary">
                                        <Loader class="h-4 w-4 animate-spin" />
                                    </div>
                                    <div class="rounded-[1.5rem] border border-white/45 bg-white/80 px-4 py-3 shadow-[0_18px_50px_-34px_rgba(31,20,8,0.85)] backdrop-blur-xl dark:border-white/10 dark:bg-white/7">
                                        <div class="flex gap-1.5">
                                            <div class="h-2 w-2 animate-bounce rounded-full bg-muted-foreground/60"></div>
                                            <div class="h-2 w-2 animate-bounce rounded-full bg-muted-foreground/60" style="animation-delay: 0.1s"></div>
                                            <div class="h-2 w-2 animate-bounce rounded-full bg-muted-foreground/60" style="animation-delay: 0.2s"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="shrink-0 border-t border-white/35 bg-white/55 px-3 py-3 backdrop-blur-2xl md:px-5 md:py-4">
                            <div v-if="error" class="mb-3 rounded-2xl border border-red-500/25 bg-red-500/10 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                                {{ error }}
                            </div>

                            <div class="rounded-[1.75rem] border border-white/55 bg-[linear-gradient(180deg,rgba(255,255,255,0.95),rgba(255,247,235,0.92))] p-3 shadow-[0_28px_75px_-45px_rgba(31,20,8,0.95)] backdrop-blur-2xl dark:border-white/10 dark:bg-[linear-gradient(180deg,rgba(33,25,21,0.96),rgba(29,22,18,0.96))]">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-primary/15 bg-primary/8 text-primary transition hover:scale-105 hover:border-primary/25"
                                        @click="emojiTrayOpen = !emojiTrayOpen"
                                    >
                                        <SmilePlus class="h-4 w-4" />
                                    </button>

                                    <button
                                        v-for="emoji in QUICK_EMOJIS"
                                        :key="emoji"
                                        type="button"
                                        class="rounded-full border border-white/60 bg-white/85 px-2.5 py-1 text-sm shadow-sm transition hover:-translate-y-0.5 hover:border-primary/25"
                                        @click="insertEmoji(emoji)"
                                    >
                                        {{ emoji }}
                                    </button>
                                </div>

                                <transition name="fade-slide">
                                    <div
                                        v-if="emojiTrayOpen"
                                        class="mt-3 flex flex-wrap gap-2 rounded-2xl border border-white/55 bg-white/75 p-3 dark:border-white/10 dark:bg-white/6"
                                    >
                                        <button
                                            v-for="emoji in QUICK_EMOJIS"
                                            :key="`tray-${emoji}`"
                                            type="button"
                                            class="rounded-full border border-white/60 bg-white/90 px-3 py-1.5 text-base transition hover:-translate-y-0.5 hover:border-primary/25"
                                            @click="insertEmoji(emoji)"
                                        >
                                            {{ emoji }}
                                        </button>
                                    </div>
                                </transition>

                                <div class="mt-3 rounded-[1.4rem] border border-white/55 bg-white/85 px-3 py-2 dark:border-white/10 dark:bg-black/10">
                                    <textarea
                                        ref="textareaRef"
                                        v-model="inputMessage"
                                        @keydown="handleKeypress"
                                        placeholder="Message AI... Enter sends, Shift+Enter adds a new line."
                                        rows="1"
                                        class="min-h-[60px] w-full resize-none border-0 bg-transparent px-1 py-2 text-sm leading-6 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-0 md:text-[15px]"
                                    ></textarea>
                                </div>

                                <div class="mt-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3 py-1.5 dark:bg-white/5">
                                            <CornerDownLeft class="h-3.5 w-3.5" />
                                            Enter sends
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3 py-1.5 dark:bg-white/5">
                                            <BellRing class="h-3.5 w-3.5" />
                                            Install PWA on iPhone, then enable notifications
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="rounded-full border-white/60 bg-white/80 backdrop-blur-xl"
                                            :disabled="isNotificationLoading"
                                            @click="enableNotifications"
                                        >
                                            <BellRing class="mr-2 h-4 w-4" />
                                            {{ notificationPermission === 'granted' ? 'Notifications enabled' : 'Enable alerts' }}
                                        </Button>

                                        <Button
                                            @click="sendMessage"
                                            :disabled="!canSend"
                                            class="h-12 rounded-full px-5 shadow-[0_18px_45px_-26px_rgba(193,90,23,0.95)]"
                                        >
                                            <Check v-if="!isLoading && canSend" class="mr-2 h-4 w-4" />
                                            <SendHorizontal v-else class="mr-2 h-4 w-4" />
                                            Send
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
.chat-page {
    background:
        radial-gradient(circle at top left, rgba(255, 202, 145, 0.24), transparent 24rem),
        radial-gradient(circle at bottom right, rgba(255, 238, 205, 0.3), transparent 20rem);
}

.chat-shell {
    background: rgba(255, 250, 244, 0.7);
    box-shadow: 0 30px 90px -52px rgba(50, 28, 10, 0.75);
}

.chat-display {
    font-family:
        Peyda, 'Peyda Web', 'PeydaWeb', 'Instrument Sans', ui-sans-serif,
        system-ui, sans-serif;
}

.chat-scroll {
    scroll-behavior: smooth;
}

.chat-in {
    animation: rise-in 0.35s ease-out;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition:
        opacity 0.18s ease,
        transform 0.18s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(6px);
}

@keyframes rise-in {
    from {
        opacity: 0;
        transform: translateY(12px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
