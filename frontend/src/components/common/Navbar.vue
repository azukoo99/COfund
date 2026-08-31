<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useNotifStore } from '@/stores/useNotifStore'
import { useToast } from 'vue-toastification'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const notifStore = useNotifStore()
const toast = useToast()

const isUserMenuOpen = ref(false)
const isMobileMenuOpen = ref(false)

async function handleLogout() {
  await authStore.logout()
  isUserMenuOpen.value = false
  toast.success('Berhasil keluar akun')
  router.push({ name: 'home' })
}

async function handleUpgrade() {
  try {
    await authStore.upgradeToCreator()
    toast.success('Selamat! Anda sekarang memiliki akses Creator')
    router.push({ name: 'creator.dashboard' })
  } catch (error) {
    toast.error('Gagal upgrade akun: ' + (error.response?.data?.message || error.message))
  }
}
</script>

<template>
  <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      
      <!-- Brand Name (Clean Typography, No Graphic Logo) -->
      <div class="flex items-center gap-3">
        <router-link to="/" class="flex items-center gap-1 group">
          <span class="text-2xl font-black tracking-tight text-slate-900">Co<span class="text-orange-600">Fund</span></span>
        </router-link>
      </div>

      <!-- Desktop Navigation Links (Only shown when relevant) -->
      <nav v-if="authStore.isAuthenticated && authStore.isCreator" class="hidden md:flex items-center gap-6">
        <router-link
          to="/dashboard/campaigns/create"
          class="text-sm font-semibold text-slate-600 hover:text-orange-600 transition-colors flex items-center gap-1.5"
        >
          <span>Mulai Galang Dana</span>
        </router-link>
      </nav>

      <!-- Right Action Items -->
      <div class="flex items-center gap-3">
        
        <!-- Guest Actions (Not Logged In) -->
        <template v-if="!authStore.isAuthenticated">
          <router-link
            to="/login"
            class="text-sm font-semibold text-slate-700 hover:text-orange-600 px-3 py-2 rounded-lg transition-colors"
          >
            Masuk
          </router-link>
          <router-link
            to="/register"
            class="text-sm font-semibold text-white bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded-xl shadow-sm shadow-orange-600/30 transition-all hover:shadow-md"
          >
            Daftar
          </router-link>
        </template>

        <!-- Logged In User Actions -->
        <template v-else>
          <!-- Jadi Creator Button (Positioned next to notifications) -->
          <button
            v-if="!authStore.isCreator"
            @click="handleUpgrade"
            class="px-3.5 py-1.5 rounded-xl bg-orange-50 hover:bg-orange-100 text-orange-700 font-bold text-xs transition-all border border-orange-200 shadow-sm"
          >
            Jadi Creator
          </button>

          <!-- Notification Bell Icon -->
          <router-link
            to="/dashboard/notifications"
            class="relative p-2 text-slate-600 hover:text-orange-600 hover:bg-slate-100 rounded-xl transition-colors"
            title="Notifikasi"
          >
            <i class="pi pi-bell text-lg"></i>
            <span
              v-if="notifStore.unreadCount > 0"
              class="absolute top-1 right-1 w-5 h-5 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-sm"
            >
              {{ notifStore.unreadCount > 9 ? '9+' : notifStore.unreadCount }}
            </span>
          </router-link>

          <!-- User Menu Dropdown -->
          <div class="relative">
            <button
              @click="isUserMenuOpen = !isUserMenuOpen"
              class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 border border-slate-200 transition-colors"
            >
              <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-sm">
                {{ authStore.user?.name?.charAt(0)?.toUpperCase() || 'U' }}
              </div>
              <div class="hidden sm:block text-left text-xs">
                <p class="font-semibold text-slate-800 truncate max-w-[100px]">{{ authStore.user?.name }}</p>
                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 uppercase">
                  {{ authStore.user?.role }}
                </span>
              </div>
              <i class="pi pi-chevron-down text-xs text-slate-400"></i>
            </button>

            <!-- Dropdown Card -->
            <div
              v-if="isUserMenuOpen"
              @click="isUserMenuOpen = false"
              class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50 animate-in fade-in slide-in-from-top-2"
            >
              <div class="px-4 py-2 border-b border-slate-100">
                <p class="text-xs text-slate-400">Masuk sebagai</p>
                <p class="text-sm font-semibold text-slate-800 truncate">{{ authStore.user?.email }}</p>
              </div>

              <!-- Admin Link -->
              <router-link
                v-if="authStore.isAdmin"
                to="/admin/overview"
                class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-indigo-600 hover:bg-indigo-50 font-medium"
              >
                <i class="pi pi-shield"></i>
                Admin Panel
              </router-link>

              <!-- Creator Dashboard -->
              <router-link
                v-if="authStore.isCreator"
                to="/dashboard/creator"
                class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50"
              >
                <i class="pi pi-chart-line text-orange-600"></i>
                Dashboard Creator
              </router-link>

              <!-- Backer Dashboard -->
              <router-link
                to="/dashboard/backer"
                class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50"
              >
                <i class="pi pi-history text-orange-600"></i>
                Riwayat Donasi Saya
              </router-link>

              <!-- Wallet & Balance -->
              <router-link
                to="/dashboard/wallet"
                class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50"
              >
                <i class="pi pi-wallet text-orange-600"></i>
                Saldo & Dompet
              </router-link>

              <div class="border-t border-slate-100 mt-1 pt-1">
                <button
                  @click="handleLogout"
                  class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 font-medium"
                >
                  <i class="pi pi-sign-out"></i>
                  Keluar
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>

    </div>
  </header>
</template>
