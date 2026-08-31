<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { useToast } from 'vue-toastification'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const name = ref('')
const email = ref('')
const password = ref('')
const password_confirmation = ref('')
const errorMessage = ref('')

async function handleSubmit() {
  errorMessage.value = ''
  if (password.value !== password_confirmation.value) {
    errorMessage.value = 'Konfirmasi password tidak cocok.'
    return
  }

  try {
    await authStore.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
    })
    toast.success('Pendaftaran berhasil! Selamat datang di CoFund.')
    router.push({ name: 'home' })
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat pendaftaran.'
    toast.error(errorMessage.value)
  }
}
</script>

<template>
  <div>
    <div class="mb-6 text-center">
      <h2 class="text-2xl font-black text-slate-900 tracking-tight">Buat Akun Baru</h2>
      <p class="text-sm text-slate-500 mt-1">Bergabung bersama ribuan donatur inovasi lokal</p>
    </div>

    <!-- Error Alert -->
    <div v-if="errorMessage" class="mb-4 p-3.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-center gap-2">
      <i class="pi pi-exclamation-circle text-base"></i>
      <span>{{ errorMessage }}</span>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-3.5">
      <!-- Full Name -->
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Nama Lengkap</label>
        <div class="relative">
          <i class="pi pi-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
          <input
            v-model="name"
            type="text"
            required
            placeholder="Budi Santoso"
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:bg-white transition-all"
          />
        </div>
      </div>

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
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:bg-white transition-all"
          />
        </div>
      </div>

      <!-- Password Field -->
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Kata Sandi (Min. 8 Karakter)</label>
        <div class="relative">
          <i class="pi pi-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
          <input
            v-model="password"
            type="password"
            required
            minlength="8"
            placeholder="••••••••"
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:bg-white transition-all"
          />
        </div>
      </div>

      <!-- Password Confirmation Field -->
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Ulangi Kata Sandi</label>
        <div class="relative">
          <i class="pi pi-check-circle absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
          <input
            v-model="password_confirmation"
            type="password"
            required
            minlength="8"
            placeholder="••••••••"
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:bg-white transition-all"
          />
        </div>
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="authStore.isLoading"
        class="w-full py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl shadow-lg shadow-orange-600/30 transition-all flex items-center justify-center gap-2 disabled:opacity-50 mt-4"
      >
        <i v-if="authStore.isLoading" class="pi pi-spin pi-spinner"></i>
        <span>{{ authStore.isLoading ? 'Mendaftarkan...' : 'Daftar Akun Baru' }}</span>
      </button>
    </form>

    <!-- Footer Switch -->
    <div class="mt-6 text-center text-xs text-slate-500">
      Sudah punya akun?
      <router-link to="/login" class="font-bold text-orange-600 hover:text-orange-700">
        Masuk di Sini
      </router-link>
    </div>
  </div>
</template>
