<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useToast } from 'vue-toastification'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

async function handleLogout() {
  await authStore.logout()
  toast.success('Keluar dari Admin Panel')
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 flex">
    
    <!-- Admin Sidebar -->
    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col justify-between p-4 sticky top-0 h-screen shadow-sm">
      <div class="space-y-6">
        
        <!-- Logo -->
        <div class="flex items-center gap-2.5 px-3 py-2">
          <div class="w-9 h-9 rounded-xl bg-orange-600 flex items-center justify-center text-white shadow-md shadow-orange-600/30">
            <i class="pi pi-shield text-base"></i>
          </div>
          <div>
            <h2 class="font-black text-base text-slate-900 tracking-tight leading-none">Co<span class="text-orange-600">Admin</span></h2>
            <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Panel Kontrol</span>
          </div>
        </div>

        <!-- Admin Nav Links -->
        <nav class="space-y-1.5">
          <router-link
            to="/admin/overview"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
            :class="route.name === 'admin.overview' ? 'bg-orange-600 text-white font-bold shadow-md shadow-orange-600/20' : 'text-slate-600 hover:bg-orange-50 hover:text-orange-600'"
          >
            <i class="pi pi-th-large"></i>
            Overview Platform
          </router-link>

          <router-link
            to="/admin/campaigns"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
            :class="route.name === 'admin.campaigns' ? 'bg-orange-600 text-white font-bold shadow-md shadow-orange-600/20' : 'text-slate-600 hover:bg-orange-50 hover:text-orange-600'"
          >
            <i class="pi pi-check-square"></i>
            Antrian Approval
          </router-link>

          <router-link
            to="/admin/users"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
            :class="route.name === 'admin.users' ? 'bg-orange-600 text-white font-bold shadow-md shadow-orange-600/20' : 'text-slate-600 hover:bg-orange-50 hover:text-orange-600'"
          >
            <i class="pi pi-users"></i>
            Manajemen User
          </router-link>
        </nav>
      </div>

      <!-- Admin Profile & Logout -->
      <div class="pt-4 border-t border-slate-100 space-y-2">
        <div class="flex items-center gap-3 px-3 py-2 bg-slate-50 rounded-xl border border-slate-100">
          <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-xs">
            AD
          </div>
          <div class="truncate">
            <p class="text-xs font-semibold text-slate-800 truncate">{{ authStore.user?.name }}</p>
            <p class="text-[10px] text-slate-400 truncate">{{ authStore.user?.email }}</p>
          </div>
        </div>

        <router-link
          to="/"
          class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors"
        >
          <i class="pi pi-globe text-slate-400"></i>
          Lihat Website Publik
        </router-link>

        <button
          @click="handleLogout"
          class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors"
        >
          <i class="pi pi-sign-out"></i>
          Keluar Admin
        </button>
      </div>

    </aside>

    <!-- Main Content Body -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-16 border-b border-slate-200 bg-white px-8 flex items-center justify-between shadow-xs">
        <h1 class="text-sm font-bold text-slate-800">CoFund Platform Administration</h1>
      </header>

      <main class="p-8 flex-1 overflow-auto bg-slate-50">
        <router-view />
      </main>
    </div>

  </div>
</template>
