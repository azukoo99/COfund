<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'
import { backingService } from '@/services/backingService'
import { formatCurrency } from '@/utils/formatCurrency'
import { useToast } from 'vue-toastification'

const props = defineProps({
  campaign: {
    type: Object,
    required: true,
  },
  selectedTier: {
    type: Object,
    default: null,
  },
  isOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'success'])

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const amount = ref(props.selectedTier ? props.selectedTier.min_amount : 50000)
const paymentMethod = ref('bank_transfer')
const isProcessing = ref(false)
const showConfirmation = ref(false)

const minAmount = computed(() => {
  if (props.selectedTier) return Number(props.selectedTier.min_amount)
  return 10000
})

function handleProceed() {
  if (!authStore.isAuthenticated) {
    toast.info('Silakan login terlebih dahulu untuk melakukan backing.')
    emit('close')
    router.push({ name: 'login' })
    return
  }

  if (amount.value < minAmount.value) {
    toast.error(`Nominal minimal untuk pilihan ini adalah ${formatCurrency(minAmount.value)}`)
    return
  }

  showConfirmation.value = true
}

async function handleConfirmPayment() {
  isProcessing.value = true
  try {
    const payload = {
      amount: amount.value,
      tier_id: props.selectedTier ? props.selectedTier.id : null,
    }
    const res = await backingService.backCampaign(props.campaign.id, payload)
    toast.success('Backing berhasil! Terima kasih atas dukungan Anda.')
    showConfirmation.value = false
    emit('success', res.data)
    emit('close')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal memproses backing.')
  } finally {
    isProcessing.value = false
  }
}
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-100 relative">
      
      <!-- Close Button -->
      <button
        @click="emit('close')"
        class="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center text-xs transition-colors"
      >
        <i class="pi pi-times"></i>
      </button>

      <!-- Step 1: Input Amount & Method -->
      <div v-if="!showConfirmation" class="space-y-5">
        <div>
          <span class="text-[11px] font-bold uppercase tracking-wider text-orange-600">Dukungan Pendanaan</span>
          <h3 class="text-xl font-black text-slate-900 tracking-tight mt-0.5">{{ campaign.title }}</h3>
        </div>

        <!-- Selected Tier Summary -->
        <div v-if="selectedTier" class="p-3.5 bg-orange-50 border border-orange-200 rounded-2xl">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-orange-950">{{ selectedTier.name }}</span>
            <span class="text-xs font-extrabold text-orange-700">Min. {{ formatCurrency(selectedTier.min_amount) }}</span>
          </div>
          <p class="text-[11px] text-orange-900 mt-1 line-clamp-2">{{ selectedTier.reward_description }}</p>
        </div>

        <div v-else class="p-3.5 bg-slate-100 border border-slate-200 rounded-2xl text-xs text-slate-600">
          Anda memilih <strong>Donasi Bebas</strong> (Tanpa klaim paket reward).
        </div>

        <!-- Nominal Input -->
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
            Nominal Backing (Min. {{ formatCurrency(minAmount) }})
          </label>
          <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-sm">Rp</span>
            <input
              v-model.number="amount"
              type="number"
              :min="minAmount"
              step="10000"
              class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-900 text-base focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all"
            />
          </div>
        </div>

        <!-- Payment Method Mock Selector -->
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Metode Pembayaran (Simulasi)</label>
          <div class="grid grid-cols-2 gap-2 text-xs font-semibold">
            <button
              type="button"
              @click="paymentMethod = 'bank_transfer'"
              class="p-3 rounded-xl border flex items-center gap-2 transition-all"
              :class="paymentMethod === 'bank_transfer' ? 'border-orange-600 bg-orange-50 text-orange-800 font-bold' : 'border-slate-200 text-slate-600'"
            >
              <i class="pi pi-building"></i>
              Virtual Account
            </button>
            <button
              type="button"
              @click="paymentMethod = 'qris'"
              class="p-3 rounded-xl border flex items-center gap-2 transition-all"
              :class="paymentMethod === 'qris' ? 'border-orange-600 bg-orange-50 text-orange-800 font-bold' : 'border-slate-200 text-slate-600'"
            >
              <i class="pi pi-qrcode"></i>
              QRIS Instant
            </button>
          </div>
        </div>

        <!-- Continue Action -->
        <button
          @click="handleProceed"
          class="w-full py-3.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-2xl shadow-lg shadow-orange-600/30 transition-all flex items-center justify-center gap-2"
        >
          <span>Lanjut ke Pembayaran</span>
          <i class="pi pi-arrow-right text-xs"></i>
        </button>
      </div>

      <!-- Step 2: Mock Payment Gateway Dialog -->
      <div v-else class="space-y-5 text-center">
        <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center mx-auto text-2xl shadow-inner">
          <i class="pi pi-credit-card"></i>
        </div>

        <div>
          <h3 class="text-xl font-black text-slate-900">Simulasi Payment Gateway</h3>
          <p class="text-xs text-slate-500 mt-1">Konfirmasi pembayaran untuk menyelesaikan backing</p>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-left space-y-2 text-xs">
          <div class="flex justify-between">
            <span class="text-slate-500">Proyek:</span>
            <span class="font-bold text-slate-800 truncate max-w-[200px]">{{ campaign.title }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Tier:</span>
            <span class="font-bold text-slate-800">{{ selectedTier ? selectedTier.name : 'Donasi Bebas' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Metode:</span>
            <span class="font-bold text-slate-800 uppercase">{{ paymentMethod }} (Mock)</span>
          </div>
          <div class="border-t border-slate-200 pt-2 flex justify-between text-sm font-black text-orange-600">
            <span>Total Bayar:</span>
            <span>{{ formatCurrency(amount) }}</span>
          </div>
        </div>

        <div class="flex gap-2">
          <button
            @click="showConfirmation = false"
            class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs transition-colors"
          >
            Kembali
          </button>
          <button
            @click="handleConfirmPayment"
            :disabled="isProcessing"
            class="flex-1 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-2xl text-xs shadow-lg shadow-orange-600/30 transition-all flex items-center justify-center gap-1.5 disabled:opacity-50"
          >
            <i v-if="isProcessing" class="pi pi-spin pi-spinner"></i>
            <span>{{ isProcessing ? 'Memproses...' : 'Konfirmasi Bayar' }}</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>
