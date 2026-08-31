import { defineStore } from 'pinia'
import { ref } from 'vue'
import { notifService } from '@/services/notifService'

export const useNotifStore = defineStore('notification', () => {
  const notifications = ref([])
  const unreadCount = ref(0)
  const isLoading = ref(false)

  async function fetchUnreadCount() {
    try {
      const response = await notifService.getUnreadCount()
      unreadCount.value = response.data.unread_count
      return response.data.unread_count
    } catch (error) {
      return 0
    }
  }

  async function fetchNotifications(params = {}) {
    isLoading.value = true
    try {
      const response = await notifService.getAll(params)
      notifications.value = response.data.data
      await fetchUnreadCount()
      return response.data
    } finally {
      isLoading.value = false
    }
  }

  async function markAsRead(id) {
    try {
      await notifService.markAsRead(id)
      const notif = notifications.value.find(n => n.id === id)
      if (notif && !notif.read_at) {
        notif.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (error) {
      console.error(error)
    }
  }

  async function markAllAsRead() {
    try {
      await notifService.markAllAsRead()
      notifications.value.forEach(n => { n.read_at = new Date().toISOString() })
      unreadCount.value = 0
    } catch (error) {
      console.error(error)
    }
  }

  return {
    notifications,
    unreadCount,
    isLoading,
    fetchUnreadCount,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
  }
})
