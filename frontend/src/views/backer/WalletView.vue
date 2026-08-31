<script setup>
import { ref, onMounted } from 'vue'
import { authService } from '@/services/authService'
import { formatCurrency } from '@/utils/formatCurrency'
import { formatDate } from '@/utils/formatDate'
import { useToast } from 'vue-toastification'

const toast = useToast()

const balance = ref(0)
const transactions = ref([])
const isLoading = ref(true)

// Filter State
const selectedType = ref('')
const fromDate = ref('')
const toDate = ref('')

// Withdraw Modal State
const isWithdrawModalOpen = ref(false)
const withdrawAmount = ref(50000)
const bankName = ref('BCA')
const accountNumber = ref('')
const isWithdrawing = ref(false)

async function loadBalance() {
  isLoading.value = true
  try {
    const params = {}
    if (selectedType.value) params.type = selectedType.value
    if (fromDate.value) params.from = fromDate.value
    if (toDate.value) params.to = toDate.value

    const res = await authService.getBalance(params)
    balance.value = res.data.balance
    transactions.value = res.data.transactions.data
  } catch (error) {
    toast.error('Gagal memuat data saldo dan transaksi.')
  } finally {
    isLoading.value = false
  }
}

async function handleWithdraw() {
  if (withdrawAmount.value < 10000) {
    toast.error('Nominal penarikan minimal Rp 10.000')
    return
  }
  if (withdrawAmount.value > balance.value) {
    toast.error('Saldo tidak mencukupi untuk nominal penarikan ini.')
    return
  }

  isWithdrawing.value = true
  try {
    await authService.withdraw({
      amount: withdrawAmount.value,
      bank_name: bankName.value,
      account_number: accountNumber.value,
    })
    toast.success('Permintaan penarikan dana berhasil diproses!')
    isWithdrawModalOpen.value = false
    await loadBalance()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal melakukan penarikan.')
  } finally {
    isWithdrawing.value = false
  }
}

onMounted(() => {
  loadBalance()
})
</script>

<template>
  <div class="space-y-8">
    <div>
      <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Saldo & Mutasi Dompet</h1>
      <p class="text-xs text-slate-500 mt-1">Kelola saldo virtual hasil pencairan dana kampanye atau pengembalian refund</p>
    </div>

    <!-- Balance Hero Card -->
    <div class="bg-gradient-to-tr from-slate-900 to-slate-800 rounded-3xl p-6 sm:p-8 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-6 shadow-xl">
      <div class="space-y-1">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Saldo Virtual Tersedia</span>
        <h2 class="text-3xl sm:text-4xl font-black tracking-tight text-orange-400">
          {{ formatCurrency(balance) }}
        </h2>
        <p class="text-[11px] text-slate-400">Dapat ditarik ke rekening bank lokal kapan saja</p>
      </div>

      <button
        @click="isWithdrawModalOpen = true"
        :disabled="balance <= 0"
        class="px-6 py-3 bg-orange-600 hover:bg-orange-700 disabled:opacity-50 text-white font-extrabold text-xs rounded-2xl shadow-lg shadow-orange-600/20 transition-all flex items-center justify-center gap-2 self-start sm:self-auto"
      >
        <i class="pi pi-arrow-up-right"></i>
        <span>Tarik Saldo (Withdraw)</span>
      </button>
    </div>

    <!-- Transaction History Card -->
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm space-y-4">
      <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h3 class="text-base font-bold text-slate-900">Riwayat Mutasi Transaksi</h3>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-2 text-xs">
          <select
            v-model="selectedType"
            @change="loadBalance"
            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 font-semibold text-slate-700 focus:outline-none"
          >
            <option value="">Semua Tipe</option>
            <option value="payment">Payment (Backing)</option>
            <option value="disbursement">Disbursement (Pencairan)</option>
            <option value="refund">Refund (Pengembalian)</option>
            <option value="platform_fee">Platform Fee (5%)</option>
          </select>

          <input
            v-model="fromDate"
            type="date"
            @change="loadBalance"
            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-700 focus:outline-none"
          />
          <span class="text-slate-400">-</span>
          <input
            v-model="toDate"
            type="date"
            @change="loadBalance"
            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-700 focus:outline-none"
          />
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
            <tr>
              <th class="px-6 py-3.5">Referensi</th>
              <th class="px-6 py-3.5">Tipe Mutasi</th>
              <th class="px-6 py-3.5">Nominal</th>
              <th class="px-6 py-3.5">Tanggal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-if="!transactions.length">
              <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                Belum ada catatan mutasi transaksi.
              </td>
            </tr>
            <tr v-for="t in transactions" :key="t.id" class="hover:bg-slate-50 transition-colors">
              <td class="px-6 py-4 font-bold text-slate-800">
                {{ t.reference_number || `TRX-${t.id}` }}
              </td>
              <td class="px-6 py-4">
                <span
                  class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase"
                  :class="{
                    'bg-emerald-100 text-emerald-800': t.type === 'disbursement',
                    'bg-sky-100 text-sky-800': t.type === 'refund',
                    'bg-slate-100 text-slate-800': t.type === 'payment',
                    'bg-amber-100 text-amber-800': t.type === 'platform_fee',
                  }"
                >
                  {{ t.type }}
                </span>
              </td>
              <td class="px-6 py-4 font-black text-sm" :class="t.type === 'disbursement' || t.type === 'refund' ? 'text-orange-600' : 'text-slate-900'">
                {{ t.type === 'disbursement' || t.type === 'refund' ? '+' : '-' }} {{ formatCurrency(t.amount) }}
              </td>
              <td class="px-6 py-4 text-slate-400">
                {{ formatDate(t.created_at, 'DD/MM/YYYY HH:mm') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Withdraw Modal -->
    <div v-if="isWithdrawModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 space-y-5 shadow-2xl">
        <h3 class="text-xl font-black text-slate-900">Tarik Saldo ke Rekening</h3>
        
        <div class="p-3 bg-orange-50 border border-orange-200 rounded-2xl text-xs text-orange-900 font-semibold">
          Saldo Tersedia: <strong>{{ formatCurrency(balance) }}</strong>
        </div>

        <form @submit.prevent="handleWithdraw" class="space-y-3.5">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Nominal Tarik</label>
            <input
              v-model.number="withdrawAmount"
              type="number"
              min="10000"
              :max="balance"
              required
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Bank Tujuan</label>
            <select
              v-model="bankName"
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
            >
              <option value="BCA">Bank Central Asia (BCA)</option>
              <option value="Mandiri">Bank Mandiri</option>
              <option value="BRI">Bank Rakyat Indonesia (BRI)</option>
              <option value="BNI">Bank Negara Indonesia (BNI)</option>
              <option value="BSI">Bank Syariah Indonesia (BSI)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Nomor Rekening</label>
            <input
              v-model="accountNumber"
              type="text"
              required
              placeholder="Contoh: 1234567890"
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>

          <div class="flex gap-2 pt-2">
            <button
              type="button"
              @click="isWithdrawModalOpen = false"
              class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="isWithdrawing"
              class="flex-1 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-2xl text-xs shadow-md shadow-orange-600/30 flex items-center justify-center gap-1.5 disabled:opacity-50"
            >
              <i v-if="isWithdrawing" class="pi pi-spin pi-spinner"></i>
              <span>Konfirmasi Tarik</span>
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>
