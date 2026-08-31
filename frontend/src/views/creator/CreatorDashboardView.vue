<script setup>
import { ref, onMounted } from 'vue'
import { campaignService } from '@/services/campaignService'
import { formatCurrency } from '@/utils/formatCurrency'
import { formatDate } from '@/utils/formatDate'
import { useToast } from 'vue-toastification'

const toast = useToast()

const dashboardData = ref(null)
const isLoading = ref(true)

// Post Update Modal State
const isUpdateModalOpen = ref(false)
const selectedCampaignForUpdate = ref(null)
const updateContent = ref('')
const isPostingUpdate = ref(false)

async function loadDashboard() {
  isLoading.value = true
  try {
    const res = await campaignService.getCreatorDashboard()
    dashboardData.value = res.data
  } catch (error) {
    toast.error('Gagal memuat dashboard creator.')
  } finally {
    isLoading.value = false
  }
}

function openUpdateModal(campaign) {
  selectedCampaignForUpdate.value = campaign
  updateContent.value = ''
  isUpdateModalOpen.value = true
}

async function handlePostUpdate() {
  if (!updateContent.value.trim()) return
  isPostingUpdate.value = true
  try {
    await campaignService.postUpdate(selectedCampaignForUpdate.value.id, {
      content: updateContent.value,
    })
    toast.success('Kabar terbaru berhasil diposting dan dibroadcast ke semua donatur!')
    isUpdateModalOpen.value = false
    await loadDashboard()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal memposting kabar.')
  } finally {
    isPostingUpdate.value = false
  }
}

async function handleSubmitReview(campaign) {
  if (!confirm(`Ajukan kampanye "${campaign.title}" ke Admin untuk ditinjau? Status akan berubah menjadi Review.`)) return
  try {
    await campaignService.submitReview(campaign.id)
    toast.success('Kampanye berhasil diajukan untuk ditinjau oleh Admin!')
    await loadDashboard()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal mengajukan kampanye.')
  }
}

onMounted(() => {
  loadDashboard()
})
</script>

<template>
  <div class="space-y-8">
    
    <!-- Header Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Dashboard Creator</h1>
        <p class="text-xs text-slate-500 mt-1">Pantau performa kampanye, statistik donatur, dan posting kabar terbaru</p>
      </div>

      <router-link
        to="/dashboard/campaigns/create"
        class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-md shadow-orange-600/30 transition-all flex items-center justify-center gap-1.5 self-start sm:self-auto"
      >
        <i class="pi pi-plus"></i>
        <span>Buat Kampanye Baru</span>
      </router-link>
    </div>

    <!-- Stats Cards Grid -->
    <div v-if="dashboardData?.summary" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1 shadow-sm">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Terkumpul</span>
        <p class="text-xl font-black text-slate-900">{{ formatCurrency(dashboardData.summary.total_collected) }}</p>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1 shadow-sm">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Donatur</span>
        <p class="text-xl font-black text-orange-600">{{ dashboardData.summary.total_backers }} Orang</p>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1 shadow-sm">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Kampanye Aktif</span>
        <p class="text-xl font-black text-slate-900">{{ dashboardData.summary.active_campaigns_count }} Proyek</p>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1 shadow-sm">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Saldo Virtual</span>
        <p class="text-xl font-black text-orange-600">{{ formatCurrency(dashboardData.summary.wallet_balance) }}</p>
      </div>
    </div>

    <!-- Funding History Daily Chart Data -->
    <div v-if="dashboardData?.daily_funding?.length" class="bg-white rounded-3xl border border-slate-200 p-6 space-y-4">
      <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Tren Pendanaan Harian (30 Hari Terakhir)</h3>
      <div class="flex items-end gap-2 h-36 pt-4 border-b border-slate-100 overflow-x-auto">
        <div
          v-for="(day, idx) in dashboardData.daily_funding"
          :key="idx"
          class="flex-1 min-w-[28px] flex flex-col items-center gap-1 group relative"
        >
          <!-- Tooltip -->
          <div class="absolute -top-8 bg-slate-900 text-white text-[10px] py-0.5 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
            {{ day.date }}: {{ formatCurrency(day.total_amount) }}
          </div>
          <!-- Bar -->
          <div
            class="w-full bg-orange-500 group-hover:bg-orange-600 rounded-t transition-all"
            :style="{ height: `${Math.max(10, Math.min(100, (day.total_amount / (dashboardData.summary.total_collected || 1)) * 100))}%` }"
          ></div>
          <span class="text-[9px] text-slate-400 truncate w-full text-center">{{ day.date.slice(5) }}</span>
        </div>
      </div>
    </div>

    <!-- Campaigns List Table -->
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
      <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-base font-bold text-slate-900">Daftar Kampanye Saya</h3>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
            <tr>
              <th class="px-6 py-3.5">Judul Kampanye</th>
              <th class="px-6 py-3.5 whitespace-nowrap">Status</th>
              <th class="px-6 py-3.5 whitespace-nowrap">Progress Dana</th>
              <th class="px-6 py-3.5 whitespace-nowrap">Sisa Waktu</th>
              <th class="px-6 py-3.5 text-right whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-if="!dashboardData?.campaigns?.length">
              <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                Anda belum memiliki kampanye.
              </td>
            </tr>
            <tr v-for="c in dashboardData?.campaigns" :key="c.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="px-6 py-4">
                <router-link
                  :to="{ name: 'campaign.detail', params: { slug: c.slug || c.id } }"
                  class="font-bold text-slate-900 text-sm hover:text-orange-600 transition-colors block"
                >
                  {{ c.title }}
                </router-link>
                <span class="text-[11px] text-slate-400 font-medium">{{ c.category?.name }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase"
                  :class="{
                    'bg-emerald-100 text-emerald-800': c.status === 'active' || c.status === 'success',
                    'bg-amber-100 text-amber-800': c.status === 'review',
                    'bg-slate-100 text-slate-700': c.status === 'draft',
                    'bg-rose-100 text-rose-800': c.status === 'failed',
                  }"
                >
                  {{ c.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="space-y-1.5 min-w-[130px]">
                  <div class="flex items-center justify-between text-[11px]">
                    <span class="font-bold text-slate-900">{{ c.collected_percentage }}%</span>
                    <span class="text-slate-400">{{ formatCurrency(c.collected_amount) }}</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-orange-500 h-1.5 rounded-full transition-all" :style="{ width: `${Math.min(c.collected_percentage, 100)}%` }"></div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap font-bold text-slate-700">
                <span v-if="c.status === 'active'">{{ c.days_left }} Hari</span>
                <span v-else class="text-slate-400 font-normal">-</span>
              </td>
              <td class="px-6 py-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  <!-- Submit Review button (draft only) -->
                  <button
                    v-if="c.status === 'draft'"
                    @click="handleSubmitReview(c)"
                    class="px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl transition-all shadow-sm flex items-center gap-1.5"
                    title="Ajukan ke Admin agar diperiksa & diaktifkan"
                  >
                    <i class="pi pi-send text-[10px]"></i>
                    <span>Ajukan Review</span>
                  </button>

                  <!-- Edit button (draft only) -->
                  <router-link
                    v-if="c.status === 'draft'"
                    :to="{ name: 'campaign.edit', params: { id: c.id } }"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors flex items-center gap-1.5"
                  >
                    <i class="pi pi-pencil text-[10px]"></i>
                    <span>Edit</span>
                  </router-link>

                  <!-- Post Update button (active only) -->
                  <button
                    v-if="c.status === 'active'"
                    @click="openUpdateModal(c)"
                    class="px-3 py-1.5 bg-orange-50 hover:bg-orange-100 text-orange-700 font-bold rounded-xl transition-colors flex items-center gap-1.5"
                  >
                    <i class="pi pi-plus text-[10px]"></i>
                    <span>Kabar</span>
                  </button>

                  <!-- View Detail -->
                  <router-link
                    :to="{ name: 'campaign.detail', params: { slug: c.slug || c.id } }"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors flex items-center gap-1.5"
                  >
                    <i class="pi pi-eye text-[10px]"></i>
                    <span>Lihat</span>
                  </router-link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Post Update Modal -->
    <div v-if="isUpdateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-4 shadow-2xl">
        <h3 class="text-lg font-black text-slate-900">Posting Kabar Terbaru</h3>
        <p class="text-xs text-slate-500">Kabar ini akan langsung dikirimkan sebagai notifikasi ke seluruh donatur proyek <strong>{{ selectedCampaignForUpdate?.title }}</strong>.</p>

        <textarea
          v-model="updateContent"
          rows="5"
          placeholder="Tulis perkembangan pengerjaan proyek, dokumentasi riset, atau progres pencapaian target..."
          class="w-full p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
        ></textarea>

        <div class="flex gap-2 justify-end">
          <button
            @click="isUpdateModalOpen = false"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl"
          >
            Batal
          </button>
          <button
            @click="handlePostUpdate"
            :disabled="isPostingUpdate"
            class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl shadow-md shadow-orange-600/30 flex items-center gap-1.5 disabled:opacity-50"
          >
            <i v-if="isPostingUpdate" class="pi pi-spin pi-spinner"></i>
            <span>Broadcast Kabar</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
