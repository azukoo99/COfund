<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/useAuthStore'
import { useNotifStore } from '@/stores/useNotifStore'

const authStore = useAuthStore()
const notifStore = useNotifStore()

onMounted(async () => {
  if (authStore.isAuthenticated) {
    await authStore.fetchUser()
    await notifStore.fetchUnreadCount()
  }
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 flex flex-col font-sans">
    <router-view />
  </div>
</template>
