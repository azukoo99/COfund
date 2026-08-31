<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { authService } from '@/services/authService'
import { useToast } from 'vue-toastification'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const token = ref('')
const email = ref('')
const password = ref('')
const password_confirmation = ref('')
const isLoading = ref(false)
const errorMessage = ref('')

onMounted(() => {
  token.value = route.query.token || ''
  email.value = route.query.email || ''
})

async function handleSubmit() {
  if (password.value !== password_confirmation.value) {
    errorMessage.value = 'Konfirmasi password tidak cocok.'
    return
  }

  errorMessage.value = ''
  isLoading.value = true
  try {
    await authService.resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
    })
    toast.success('Password berhasil direset! Silakan login dengan password baru.')
    router.push({ name: 'login' })
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Token reset tidak valid atau telah expired.'
    toast.error(errorMessage.value)
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div>
    <div class="mb-6 text-center">
      <h2 class="text-2xl font-black text-slate-900 tracking-tight">Atur Kata Sandi Baru</h2>
      <p class="text-sm text-slate-500 mt-1">Masukkan kata sandi baru untuk akun Anda</p>
    </div>

    <div v-if="errorMessage" class="mb-4 p-3.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-center gap-2">
      <i class="pi pi-exclamation-circle text-base"></i>
      <span>{{ errorMessage }}</span>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Email</label>
        <input
          v-model="email"
          type="email"
          required
          class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all"
        />
      </div>

      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Kata Sandi Baru</label>
        <input
          v-model="password"
          type="password"
          required
          minlength="8"
          placeholder="••••••••"
          class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all"
        />
      </div>

      <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Ulangi Kata Sandi Baru</label>
        <input
          v-model="password_confirmation"
          type="password"
          required
          minlength="8"
          placeholder="••••••••"
          class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all"
        />
      </div>

      <button
        type="submit"
        :disabled="isLoading"
        class="w-full py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl shadow-lg shadow-orange-600/30 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
      >
        <i v-if="isLoading" class="pi pi-spin pi-spinner"></i>
        <span>{{ isLoading ? 'Menyimpan...' : 'Simpan Sandi Baru' }}</span>
      </button>
    </form>
  </div>
</template>
