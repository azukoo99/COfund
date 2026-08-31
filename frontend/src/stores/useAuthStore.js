import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null)
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const isLoading = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isCreator = computed(() => user.value?.role === 'creator' || user.value?.role === 'admin')
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isVerified = computed(() => !!user.value?.email_verified_at)

  async function login(credentials) {
    isLoading.value = true
    try {
      const response = await authService.login(credentials)
      token.value = response.data.token
      user.value = response.data.user
      localStorage.setItem('token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
      return response.data
    } finally {
      isLoading.value = false
    }
  }

  async function register(data) {
    isLoading.value = true
    try {
      const response = await authService.register(data)
      token.value = response.data.token
      user.value = response.data.user
      localStorage.setItem('token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
      return response.data
    } finally {
      isLoading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) return null
    try {
      const response = await authService.getMe()
      user.value = response.data
      localStorage.setItem('user', JSON.stringify(response.data))
      return response.data
    } catch (error) {
      logout()
      return null
    }
  }

  async function upgradeToCreator() {
    isLoading.value = true
    try {
      const response = await authService.upgradeToCreator()
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(response.data.user))
      return response.data
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await authService.logout().catch(() => {})
      }
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  return {
    token,
    user,
    isLoading,
    isAuthenticated,
    isCreator,
    isAdmin,
    isVerified,
    login,
    register,
    fetchUser,
    upgradeToCreator,
    logout,
  }
})
