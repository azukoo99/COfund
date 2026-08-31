<script setup>
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import Navbar from '@/components/common/Navbar.vue'
import Footer from '@/components/common/Footer.vue'

const route = useRoute()
const authStore = useAuthStore()
</script>

<template>
  <div class="min-h-screen flex flex-col bg-slate-50">
    <Navbar />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Sidebar Navigation -->
        <aside class="lg:col-span-1">
          <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-6 sticky top-24">
            
            <!-- User Summary -->
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
              <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-700 font-black text-lg flex items-center justify-center">
                {{ authStore.user?.name?.charAt(0)?.toUpperCase() }}
              </div>
              <div class="truncate">
                <h3 class="font-bold text-slate-800 text-sm truncate">{{ authStore.user?.name }}</h3>
                <span class="inline-block text-[11px] font-semibold text-orange-600 uppercase">
                  Role: {{ authStore.user?.role }}
                </span>
              </div>
            </div>

            <!-- Links Group -->
            <div class="space-y-1">
              <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-2">Menu Pengguna</p>
              
              <!-- Backer Dashboard -->
              <router-link
                to="/dashboard/backer"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
                :class="route.name === 'backer.dashboard' ? 'bg-orange-50 text-orange-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
              >
                <i class="pi pi-history text-base"></i>
                Riwayat Donasi
              </router-link>

              <!-- Wallet / Balance -->
              <router-link
                to="/dashboard/wallet"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
                :class="route.name === 'wallet' ? 'bg-orange-50 text-orange-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
              >
                <i class="pi pi-wallet text-base"></i>
                Saldo & Mutasi
              </router-link>

              <!-- Notifications -->
              <router-link
                to="/dashboard/notifications"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
                :class="route.name === 'notifications' ? 'bg-orange-50 text-orange-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
              >
                <i class="pi pi-bell text-base"></i>
                Notifikasi
              </router-link>
            </div>

            <!-- Creator Links -->
            <div v-if="authStore.isCreator" class="space-y-1 pt-4 border-t border-slate-100">
              <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-2">Area Creator</p>
              
              <router-link
                to="/dashboard/creator"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
                :class="route.name === 'creator.dashboard' ? 'bg-orange-50 text-orange-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
              >
                <i class="pi pi-chart-line text-base"></i>
                Dashboard Creator
              </router-link>

              <router-link
                to="/dashboard/campaigns/create"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
                :class="route.name === 'campaign.create' ? 'bg-orange-50 text-orange-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
              >
                <i class="pi pi-plus-circle text-base"></i>
                Buat Kampanye Baru
              </router-link>
            </div>

          </div>
        </aside>

        <!-- Main Content Area -->
        <main class="lg:col-span-3">
          <router-view />
        </main>

      </div>
    </div>

    <Footer />
  </div>
</template>
