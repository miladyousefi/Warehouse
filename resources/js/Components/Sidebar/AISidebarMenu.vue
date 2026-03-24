<template>
  <!-- AI Assistant Menu Item for Sidebar -->
  <!-- Place this in your existing sidebar/navigation component -->
  
  <div class="px-6 py-4">
    <!-- Section Title -->
    <h3 class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-500">
      AI & Analytics
    </h3>

    <!-- AI Chat Link -->
    <Link
      href="/ai/chat"
      :class="[
        'group relative mb-2 flex items-center space-x-3 rounded-lg px-4 py-2.5 transition-all duration-200',
        isActive('/ai/chat')
          ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg'
          : 'text-gray-700 hover:bg-blue-50',
      ]"
    >
      <!-- Icon -->
      <div
        :class="[
          'flex h-8 w-8 items-center justify-center rounded-lg transition-all duration-200',
          isActive('/ai/chat')
            ? 'bg-white bg-opacity-20'
            : 'bg-blue-100 group-hover:bg-blue-200',
        ]"
      >
        <svg
          class="h-5 w-5"
          :class="isActive('/ai/chat') ? 'text-white' : 'text-blue-600'"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
          />
        </svg>
      </div>

      <!-- Label -->
      <div class="flex-1">
        <div class="font-medium">AI Assistant</div>
        <div
          :class="[
            'text-xs',
            isActive('/ai/chat')
              ? 'text-blue-100'
              : 'text-gray-500 group-hover:text-gray-700',
          ]"
        >
          Chat with AI
        </div>
      </div>

      <!-- New Badge (if there are unread messages) -->
      <div
        v-if="hasNewMessages"
        class="flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white"
      >
        !
      </div>

      <!-- Arrow Icon -->
      <svg
        :class="[
          'h-4 w-4 transition-transform duration-200',
          isActive('/ai/chat') ? 'text-white' : 'text-gray-400',
        ]"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </Link>

    <!-- Recent Conversations (Optional) -->
    <Transition
      enter-active-class="transition-all duration-200"
      enter-from-class="max-h-0 opacity-0"
      enter-to-class="max-h-96 opacity-100"
      leave-active-class="transition-all duration-200"
      leave-from-class="max-h-96 opacity-100"
      leave-to-class="max-h-0 opacity-0"
    >
      <div
        v-if="showRecentConversations && conversations.length > 0"
        class="mt-2 space-y-1 border-t border-gray-200 pt-2"
      >
        <Link
          v-for="conversation in recentConversations"
          :key="conversation.id"
          :href="`/ai/chat/${conversation.id}`"
          class="block truncate rounded px-4 py-1.5 text-xs text-gray-600 hover:bg-blue-50 hover:text-blue-700"
          :title="conversation.title"
        >
          {{ conversation.title }}
        </Link>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const conversations = ref([])
const hasNewMessages = ref(false)
const showRecentConversations = ref(false)
const token = ref('')

onMounted(() => {
  token.value = page.props.auth?.sanctum_token || localStorage.getItem('sanctum_token') || ''
  if (token.value) {
    loadConversations()
  }
})

const loadConversations = async () => {
  if (!token.value) return
  
  try {
    const response = await fetch('/api/ai/conversations?per_page=5', {
      headers: {
        'Authorization': `Bearer ${token.value}`,
        'Content-Type': 'application/json',
      },
    })
    
    if (response.ok) {
      const data = await response.json()
      conversations.value = data.conversations.data || []
    }
  } catch (error) {
    console.error('Failed to load conversations:', error)
  }
}

const recentConversations = computed(() => {
  return conversations.value.slice(0, 3)
})

const isActive = (route) => {
  return page.url.includes(route)
}

// Toggle recent conversations on click
const toggleRecentConversations = () => {
  showRecentConversations.value = !showRecentConversations.value
}
</script>
