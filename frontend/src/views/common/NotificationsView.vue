<script setup>
import { ref, onMounted } from 'vue'
import { useNotifStore } from '@/stores/useNotifStore'
import { formatRelative } from '@/utils/formatDate'
import { useToast } from 'vue-toastification'

const notifStore = useNotifStore()
const toast = useToast()

const unreadOnly = ref(false)

async function loadNotifications() {
  await notifStore.fetchNotifications({
    unread_only: unreadOnly.value ? 1 : 0,
  })
}

async function markAllRead() {
  await notifStore.markAllAsRead()
  toast.success('Semua notifikasi ditandai telah dibaca.')
}

async function handleItemClick(notif) {
  if (!notif.read_at) {
    await notifStore.markAsRead(notif.id)
  }
}

onMounted(() => {
  loadNotifications()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Notifikasi</h1>
        <p class="text-xs text-slate-500 mt-1">Pemberitahuan persetujuan kampanye, donasi masuk, deadline, dan kabar terbaru</p>
      </div>

      <button
        v-if="notifStore.unreadCount > 0"
        @click="markAllRead"
        class="text-xs font-bold text-orange-600 hover:text-orange-700 bg-orange-50 px-3.5 py-2 rounded-xl transition-colors"
      >
        Tandai Semua Dibaca
      </button>
    </div>

    <!-- Filter Toggle -->
    <div class="flex gap-2">
      <button
        @click="unreadOnly = false; loadNotifications()"
        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
        :class="!unreadOnly ? 'bg-orange-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200'"
      >
        Semua Notifikasi
      </button>
      <button
        @click="unreadOnly = true; loadNotifications()"
        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
        :class="unreadOnly ? 'bg-orange-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200'"
      >
        Hanya Belum Dibaca ({{ notifStore.unreadCount }})
      </button>
    </div>

    <!-- Notifications List -->
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden divide-y divide-slate-100 shadow-sm">
      <div v-if="notifStore.isLoading" class="p-8 text-center text-xs text-slate-400">
        <i class="pi pi-spin pi-spinner text-lg text-orange-600 mr-2"></i>
        Memuat notifikasi...
      </div>

      <div v-else-if="!notifStore.notifications.length" class="p-12 text-center text-xs text-slate-400">
        <i class="pi pi-bell-slash text-2xl text-slate-300 block mb-2"></i>
        Tidak ada notifikasi untuk ditampilkan.
      </div>

      <div
        v-for="n in notifStore.notifications"
        :key="n.id"
        @click="handleItemClick(n)"
        class="p-4 sm:p-5 flex items-start gap-4 transition-colors cursor-pointer"
        :class="!n.read_at ? 'bg-orange-50/40 hover:bg-orange-50/80' : 'hover:bg-slate-50'"
      >
        <!-- Icon Marker -->
        <div
          class="w-10 h-10 rounded-2xl flex items-center justify-center text-base flex-shrink-0"
          :class="!n.read_at ? 'bg-orange-600 text-white shadow-md shadow-orange-600/30' : 'bg-slate-100 text-slate-500'"
        >
          <i class="pi pi-bell"></i>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <h4 class="font-bold text-sm text-slate-900 truncate" :class="{ 'text-orange-950 font-black': !n.read_at }">
              {{ n.title }}
            </h4>
            <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ formatRelative(n.created_at) }}</span>
          </div>
          <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ n.body }}</p>
        </div>

        <!-- Unread Dot Indicator -->
        <div v-if="!n.read_at" class="w-2.5 h-2.5 rounded-full bg-orange-600 flex-shrink-0 self-center"></div>
      </div>
    </div>
  </div>
</template>
