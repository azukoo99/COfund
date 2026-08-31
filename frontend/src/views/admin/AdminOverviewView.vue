<script setup>
import { ref, onMounted } from 'vue'
import { adminService } from '@/services/adminService'
import { formatCurrency } from '@/utils/formatCurrency'
import { useToast } from 'vue-toastification'

const toast = useToast()
const overview = ref(null)
const isLoading = ref(true)

async function loadOverview() {
  isLoading.value = true
  try {
    const res = await adminService.getOverview()
    overview.value = res.data
  } catch (error) {
    toast.error('Gagal memuat statistik admin.')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadOverview()
})
</script>

<template>
  <div class="space-y-8">
    <div>
      <h2 class="text-2xl font-black text-slate-900 tracking-tight">Overview Platform CoFund</h2>
      <p class="text-xs text-slate-500 mt-1">Ringkasan finansial, pertumbuhan pengguna, dan distribusi status kampanye</p>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-pulse">
      <div v-for="i in 4" :key="i" class="h-28 bg-slate-200 rounded-3xl"></div>
    </div>

    <!-- Metrics Cards Grid -->
    <div v-else-if="overview" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 space-y-2 shadow-sm">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Dana Terkumpul</span>
        <p class="text-2xl font-black text-slate-900">{{ formatCurrency(overview.total_collected_platform) }}</p>
        <span class="text-[10px] text-slate-400 block font-semibold">Semua Kampanye</span>
      </div>

      <div class="bg-white border border-slate-200 rounded-3xl p-6 space-y-2 shadow-sm">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pendapatan Fee (5%)</span>
        <p class="text-2xl font-black text-orange-600">{{ formatCurrency(overview.total_platform_fee_collected) }}</p>
        <span class="text-[10px] text-orange-500 block font-semibold">Dari Kampanye Sukses</span>
      </div>

      <div class="bg-white border border-slate-200 rounded-3xl p-6 space-y-2 shadow-sm">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Kampanye</span>
        <p class="text-2xl font-black text-slate-900">{{ overview.total_campaigns }} Proyek</p>
        <span class="text-[10px] text-amber-600 block font-semibold">{{ overview.campaigns_by_status?.review || 0 }} Menunggu Review</span>
      </div>

      <div class="bg-white border border-slate-200 rounded-3xl p-6 space-y-2 shadow-sm">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pengguna</span>
        <p class="text-2xl font-black text-orange-600">{{ overview.total_users }} Akun</p>
        <span class="text-[10px] text-slate-400 block font-semibold">Backer & Creator</span>
      </div>
    </div>

    <!-- Status Distribution Grid -->
    <div v-if="overview?.campaigns_by_status" class="bg-white border border-slate-200 rounded-3xl p-6 space-y-4 shadow-sm">
      <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Distribusi Status Kampanye</h3>
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center">
        <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl">
          <span class="text-[11px] font-bold text-slate-500 block">DRAFT</span>
          <span class="text-xl font-black text-slate-700">{{ overview.campaigns_by_status.draft || 0 }}</span>
        </div>
        <div class="bg-amber-50 border border-amber-200 p-4 rounded-2xl">
          <span class="text-[11px] font-bold text-amber-700 block">REVIEW</span>
          <span class="text-xl font-black text-amber-700">{{ overview.campaigns_by_status.review || 0 }}</span>
        </div>
        <div class="bg-orange-50 border border-orange-200 p-4 rounded-2xl">
          <span class="text-[11px] font-bold text-orange-700 block">ACTIVE</span>
          <span class="text-xl font-black text-orange-700">{{ overview.campaigns_by_status.active || 0 }}</span>
        </div>
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl">
          <span class="text-[11px] font-bold text-emerald-700 block">SUCCESS</span>
          <span class="text-xl font-black text-emerald-700">{{ overview.campaigns_by_status.success || 0 }}</span>
        </div>
        <div class="bg-rose-50 border border-rose-200 p-4 rounded-2xl">
          <span class="text-[11px] font-bold text-rose-700 block">FAILED</span>
          <span class="text-xl font-black text-rose-700">{{ overview.campaigns_by_status.failed || 0 }}</span>
        </div>
      </div>
    </div>

    <!-- Monthly Trend Chart -->
    <div v-if="overview?.monthly_campaigns?.length" class="bg-white border border-slate-200 rounded-3xl p-6 space-y-4 shadow-sm">
      <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Aktivitas Pembuatan Kampanye Bulanan</h3>
      <div class="flex items-end gap-4 h-40 pt-4 border-b border-slate-200">
        <div
          v-for="(item, idx) in overview.monthly_campaigns"
          :key="idx"
          class="flex-1 flex flex-col items-center gap-1 group relative"
        >
          <div class="absolute -top-7 bg-slate-800 text-white text-[10px] py-0.5 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity">
            {{ item.total }} Kampanye
          </div>
          <div
            class="w-full bg-orange-500 rounded-t-xl transition-all group-hover:bg-orange-600"
            :style="{ height: `${Math.max(item.total * 15, 12)}px` }"
          ></div>
          <span class="text-[10px] font-semibold text-slate-500 mt-2">{{ item.month }}</span>
        </div>
      </div>
    </div>

  </div>
</template>
