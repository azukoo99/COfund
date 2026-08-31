<script setup>
import { ref, onMounted } from 'vue'
import { campaignService } from '@/services/campaignService'
import { formatCurrency } from '@/utils/formatCurrency'
import { formatDate } from '@/utils/formatDate'
import { useToast } from 'vue-toastification'

const toast = useToast()
const data = ref(null)
const isLoading = ref(true)

async function loadDashboard() {
  isLoading.value = true
  try {
    const res = await campaignService.getBackerDashboard()
    data.value = res.data
  } catch (error) {
    toast.error('Gagal memuat riwayat donasi.')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadDashboard()
})
</script>

<template>
  <div class="space-y-8">
    <div>
      <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Riwayat Donasi Saya</h1>
      <p class="text-xs text-slate-500 mt-1">Daftar kontribusi dan paket reward yang Anda dukung</p>
    </div>

    <!-- Summary Cards -->
    <div v-if="data?.summary" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1 shadow-sm">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Didonasikan</span>
        <p class="text-xl font-black text-orange-600">{{ formatCurrency(data.summary.total_donated) }}</p>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1 shadow-sm">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Proyek Didukung</span>
        <p class="text-xl font-black text-slate-900">{{ data.summary.total_campaigns_backed }} Proyek</p>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1 shadow-sm">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Refund Diterima</span>
        <p class="text-xl font-black text-rose-600">{{ formatCurrency(data.summary.total_refunded) }}</p>
      </div>
    </div>

    <!-- Backing History Table -->
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
      <div class="p-6 border-b border-slate-100">
        <h3 class="text-base font-bold text-slate-900">Riwayat Transaksi Backing</h3>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
            <tr>
              <th class="px-6 py-3.5">Proyek Kampanye</th>
              <th class="px-6 py-3.5">Tier Reward</th>
              <th class="px-6 py-3.5">Nominal</th>
              <th class="px-6 py-3.5">Status</th>
              <th class="px-6 py-3.5">Tanggal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-if="!data?.backings?.data?.length">
              <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                Anda belum pernah melakukan backing kampanye.
              </td>
            </tr>
            <tr v-for="b in data?.backings?.data" :key="b.id" class="hover:bg-slate-50 transition-colors">
              <td class="px-6 py-4">
                <router-link
                  :to="{ name: 'campaign.detail', params: { slug: b.campaign?.slug } }"
                  class="font-bold text-slate-900 text-sm hover:text-orange-600 transition-colors"
                >
                  {{ b.campaign?.title }}
                </router-link>
              </td>
              <td class="px-6 py-4">
                <span v-if="b.tier" class="font-bold text-orange-800 bg-orange-50 border border-orange-200 px-2.5 py-1 rounded-lg">
                  {{ b.tier.name }}
                </span>
                <span v-else class="text-slate-400">Donasi Bebas</span>
              </td>
              <td class="px-6 py-4 font-black text-slate-900 text-sm">
                {{ formatCurrency(b.amount) }}
              </td>
              <td class="px-6 py-4">
                <span
                  class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase"
                  :class="{
                    'bg-emerald-100 text-emerald-800': b.status === 'completed',
                    'bg-amber-100 text-amber-800': b.status === 'pending',
                    'bg-rose-100 text-rose-800': b.status === 'refunded',
                  }"
                >
                  {{ b.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-slate-400">
                {{ formatDate(b.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
