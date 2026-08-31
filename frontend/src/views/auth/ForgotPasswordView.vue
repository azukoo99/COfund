<script setup>
import { ref } from 'vue'
import { authService } from '@/services/authService'
import { useToast } from 'vue-toastification'

const toast = useToast()
const email = ref('')
const isLoading = ref(false)
const isSubmitted = ref(false)
const errorMessage = ref('')

async function handleSubmit() {
  errorMessage.value = ''
  isLoading.value = true
  try {
    await authService.forgotPassword({ email: email.value })
    isSubmitted.value = true
    toast.success('Link reset password telah dikirim ke email Anda.')
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal mengirim link reset password.'
    toast.error(errorMessage.value)
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div>
    <div class="mb-6 text-center">
      <h2 class="text-2xl font-black text-slate-900 tracking-tight">Lupa Kata Sandi</h2>
      <p class="text-sm text-slate-500 mt-1">Kami akan mengirimkan link untuk mengatur ulang kata sandi Anda</p>
    </div>

    <!-- Success Feedback -->
    <div v-if="isSubmitted" class="p-4 bg-orange-50 border border-orange-200 text-orange-900 text-sm rounded-2xl text-center space-y-2">
      <i class="pi pi-check-circle text-2xl text-orange-600"></i>
      <p class="font-bold">Email Terkirim!</p>
      <p class="text-xs text-orange-800">Silakan periksa kotak masuk email <strong>{{ email }}</strong> untuk melanjutkan proses reset password.</p>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="space-y-4">
      <div v-if="errorMessage" class="p-3.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-center gap-2">
        <i class="pi pi-exclamation-circle text-base"></i>
        <span>{{ errorMessage }}</span>
      </div>

      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Email Terdaftar</label>
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

      <button
        type="submit"
        :disabled="isLoading"
        class="w-full py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl shadow-lg shadow-orange-600/30 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
      >
        <i v-if="isLoading" class="pi pi-spin pi-spinner"></i>
        <span>{{ isLoading ? 'Mengirim...' : 'Kirim Link Reset' }}</span>
      </button>
    </form>

    <div class="mt-6 text-center text-xs text-slate-500">
      Ingat kata sandi Anda?
      <router-link to="/login" class="font-bold text-orange-600 hover:text-orange-700">
        Kembali ke Login
      </router-link>
    </div>
  </div>
</template>
