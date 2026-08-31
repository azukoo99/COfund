<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useToast } from 'vue-toastification'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toast = useToast()

const email = ref('')
const password = ref('')
const errorMessage = ref('')

async function handleSubmit() {
  errorMessage.value = ''
  try {
    const data = await authStore.login({
      email: email.value,
      password: password.value,
    })
    toast.success('Login berhasil! Selamat datang kembali.')

    // Redirect berdasarkan role
    if (route.query.redirect) {
      router.push(route.query.redirect)
    } else if (data.user.role === 'admin') {
      router.push({ name: 'admin.overview' })
    } else if (data.user.role === 'creator') {
      router.push({ name: 'creator.dashboard' })
    } else {
      router.push({ name: 'home' })
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Email atau password salah.'
    toast.error(errorMessage.value)
  }
}
</script>

<template>
  <div>
    <div class="mb-6 text-center">
      <h2 class="text-2xl font-black text-slate-900 tracking-tight">Masuk ke Akun</h2>
      <p class="text-sm text-slate-500 mt-1">Lanjutkan untuk mendanai atau mengelola kampanye</p>
    </div>

    <!-- Error Alert -->
    <div v-if="errorMessage" class="mb-4 p-3.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-center gap-2">
      <i class="pi pi-exclamation-circle text-base"></i>
      <span>{{ errorMessage }}</span>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Email Field -->
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Email</label>
        <div class="relative">
          <i class="pi pi-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
          <input
            v-model="email"
            type="email"
            required
            placeholder="nama@email.com"
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all"
          />
        </div>
      </div>

      <!-- Password Field -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Kata Sandi</label>
          <router-link to="/forgot-password" class="text-xs font-semibold text-orange-600 hover:text-orange-700">
            Lupa sandi?
          </router-link>
        </div>
        <div class="relative">
          <i class="pi pi-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
          <input
            v-model="password"
            type="password"
            required
            placeholder="••••••••"
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:bg-white transition-all"
          />
        </div>
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="authStore.isLoading"
        class="w-full py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl shadow-lg shadow-orange-600/30 transition-all flex items-center justify-center gap-2 disabled:opacity-50 mt-2"
      >
        <i v-if="authStore.isLoading" class="pi pi-spin pi-spinner"></i>
        <span>{{ authStore.isLoading ? 'Memproses...' : 'Masuk Sekarang' }}</span>
      </button>
    </form>

    <!-- Footer Switch -->
    <div class="mt-6 text-center text-xs text-slate-500">
      Belum punya akun?
      <router-link to="/register" class="font-bold text-orange-600 hover:text-orange-700">
        Daftar Sekarang
      </router-link>
    </div>
  </div>
</template>
