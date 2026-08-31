<script setup>
import { ref, onMounted } from 'vue'
import { adminService } from '@/services/adminService'
import { formatCurrency } from '@/utils/formatCurrency'
import { formatDate } from '@/utils/formatDate'
import { useToast } from 'vue-toastification'

const toast = useToast()

const campaigns = ref([])
const isLoading = ref(true)
const selectedStatus = ref('review')

// Action Modals State
const isRejectModalOpen = ref(false)
const isForceFailModalOpen = ref(false)
const selectedCampaign = ref(null)
const rejectionNote = ref('')
const forceFailReason = ref('')
const isActionLoading = ref(false)

async function loadCampaigns() {
  isLoading.value = true
  try {
    const params = {}
    if (selectedStatus.value) params.status = selectedStatus.value
    const res = await adminService.getCampaigns(params)
    campaigns.value = res.data.data
  } catch (error) {
    toast.error('Gagal memuat antrian kampanye.')
  } finally {
    isLoading.value = false
  }
}

async function handleApprove(c) {
  if (!confirm(`Setujui kampanye "${c.title}" untuk mulai aktif?`)) return
  try {
    await adminService.approveCampaign(c.id)
    toast.success('Kampanye berhasil disetujui & sekarang berstatus ACTIVE!')
    await loadCampaigns()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyetujui kampanye.')
  }
}

function openRejectModal(c) {
  selectedCampaign.value = c
  rejectionNote.value = ''
  isRejectModalOpen.value = true
}

async function handleReject() {
  if (!rejectionNote.value.trim()) {
    toast.error('Catatan penolakan wajib diisi.')
    return
  }
  isActionLoading.value = true
  try {
    await adminService.rejectCampaign(selectedCampaign.value.id, {
      rejection_note: rejectionNote.value,
    })
    toast.success('Kampanye berhasil ditolak & dikembalikan ke status DRAFT bersama catatan.')
    isRejectModalOpen.value = false
    await loadCampaigns()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menolak kampanye.')
  } finally {
    isActionLoading.value = false
  }
}

function openForceFailModal(c) {
  selectedCampaign.value = c
  forceFailReason.value = ''
  isForceFailModalOpen.value = true
}

async function handleForceFail() {
  isActionLoading.value = true
  try {
    await adminService.forceFailCampaign(selectedCampaign.value.id, {
      reason: forceFailReason.value || 'Digagalkan paksa oleh Admin karena pelanggaran ketentuan.',
    })
    toast.success('Kampanye berhasil digagalkan paksa & RefundBackersJob otomatis dipicu!')
    isForceFailModalOpen.value = false
    await loadCampaigns()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menggagalkan kampanye.')
  } finally {
    isActionLoading.value = false
  }
}

onMounted(() => {
  loadCampaigns()
})
</script>

<template>
  <div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Manajemen & Antrian Kampanye</h2>
        <p class="text-xs text-slate-500 mt-1">Tinjau pengajuan baru, setujui, tolak dengan catatan, atau batalkan paksa kampanye bermasalah</p>
      </div>

      <!-- Filter Tabs -->
      <div class="flex gap-2">
        <button
          @click="selectedStatus = 'review'; loadCampaigns()"
          class="px-4 py-2 rounded-xl text-xs font-bold transition-all"
          :class="selectedStatus === 'review' ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
        >
          Menunggu Review
        </button>
        <button
          @click="selectedStatus = 'active'; loadCampaigns()"
          class="px-4 py-2 rounded-xl text-xs font-bold transition-all"
          :class="selectedStatus === 'active' ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
        >
          Aktif Live
        </button>
        <button
          @click="selectedStatus = ''; loadCampaigns()"
          class="px-4 py-2 rounded-xl text-xs font-bold transition-all"
          :class="selectedStatus === '' ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
        >
          Semua Status
        </button>
      </div>
    </div>

    <!-- Campaigns Table -->
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
            <tr>
              <th class="px-6 py-4">Kampanye & Creator</th>
              <th class="px-6 py-4">Kategori</th>
              <th class="px-6 py-4">Target & Terkumpul</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4 text-right">Aksi Admin</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-if="!campaigns.length">
              <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                Tidak ada kampanye pada kategori filter ini.
              </td>
            </tr>
            <tr v-for="c in campaigns" :key="c.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="px-6 py-4">
                <p class="font-bold text-slate-900 text-sm">{{ c.title }}</p>
                <p class="text-[11px] text-slate-400">Oleh: {{ c.creator?.name || 'Creator' }} ({{ c.creator?.email }})</p>
              </td>
              <td class="px-6 py-4">
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold border border-slate-200">
                  {{ c.category?.name || 'Inovasi' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span class="font-bold text-slate-900 block">{{ formatCurrency(c.collected_amount) }}</span>
                <span class="text-[11px] text-slate-400">Target: {{ formatCurrency(c.target_amount) }}</span>
              </td>
              <td class="px-6 py-4">
                <span
                  class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase"
                  :class="{
                    'bg-amber-100 text-amber-800 border border-amber-200': c.status === 'review',
                    'bg-orange-100 text-orange-800 border border-orange-200': c.status === 'active',
                    'bg-emerald-100 text-emerald-800 border border-emerald-200': c.status === 'success',
                    'bg-slate-100 text-slate-700 border border-slate-200': c.status === 'draft',
                    'bg-rose-100 text-rose-800 border border-rose-200': c.status === 'failed',
                  }"
                >
                  {{ c.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <!-- Action: Approve / Reject (review status) -->
                <template v-if="c.status === 'review'">
                  <button
                    @click="handleApprove(c)"
                    class="px-3.5 py-1.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl transition-colors shadow-sm"
                  >
                    Setujui
                  </button>
                  <button
                    @click="openRejectModal(c)"
                    class="px-3.5 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold rounded-xl border border-rose-200 transition-colors"
                  >
                    Tolak
                  </button>
                </template>

                <!-- Action: Force Fail (active status) -->
                <template v-if="c.status === 'active'">
                  <button
                    @click="openForceFailModal(c)"
                    class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl transition-colors shadow-sm"
                  >
                    Batalkan Paksa
                  </button>
                </template>

                <router-link
                  :to="`/campaigns/${c.slug || c.id}`"
                  target="_blank"
                  class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl inline-block transition-colors"
                >
                  Preview
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Reject Campaign -->
    <div
      v-if="isRejectModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
    >
      <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full border border-slate-200 shadow-2xl space-y-6 animate-in fade-in zoom-in-95">
        <div>
          <h3 class="text-lg font-black text-slate-900">Tolak Pengajuan Kampanye</h3>
          <p class="text-xs text-slate-500 mt-1">Status akan dikembalikan ke Draft beserta catatan revisi untuk creator.</p>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-700">Alasan Penolakan / Catatan Perbaikan</label>
          <textarea
            v-model="rejectionNote"
            rows="4"
            placeholder="Contoh: Rincian anggaran belum jelas, target dana terlalu tinggi tanpa rincian RAB..."
            class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-slate-900 text-xs focus:ring-2 focus:ring-orange-500 focus:outline-none"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-3">
          <button
            type="button"
            @click="isRejectModalOpen = false"
            class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50"
          >
            Batal
          </button>
          <button
            type="button"
            @click="handleReject"
            :disabled="isActionLoading"
            class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md disabled:opacity-50"
          >
            {{ isActionLoading ? 'Memproses...' : 'Konfirmasi Tolak' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Force Fail Campaign -->
    <div
      v-if="isForceFailModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
    >
      <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full border border-slate-200 shadow-2xl space-y-6 animate-in fade-in zoom-in-95">
        <div>
          <h3 class="text-lg font-black text-rose-600">Batalkan Paksa & Refund Donatur</h3>
          <p class="text-xs text-slate-500 mt-1">
            Tindakan ini akan langsung mengubah status kampanye menjadi FAILED dan secara otomatis mengembalikan 100% dana ke seluruh donatur!
          </p>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-700">Alasan Pembatalan</label>
          <textarea
            v-model="forceFailReason"
            rows="3"
            placeholder="Pelanggaran ketentuan / indikasi ketidaksesuaian proyek..."
            class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-slate-900 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-3">
          <button
            type="button"
            @click="isForceFailModalOpen = false"
            class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50"
          >
            Batal
          </button>
          <button
            type="button"
            @click="handleForceFail"
            :disabled="isActionLoading"
            class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md disabled:opacity-50"
          >
            {{ isActionLoading ? 'Memproses...' : 'Eksekusi Pembatalan' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
