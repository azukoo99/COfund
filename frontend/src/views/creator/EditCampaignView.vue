<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { campaignService } from '@/services/campaignService'
import { useCampaignStore } from '@/stores/useCampaignStore'
import { formatImageUrl } from '@/utils/formatImage'
import { useToast } from 'vue-toastification'

const route = useRoute()
const router = useRouter()
const campaignStore = useCampaignStore()
const toast = useToast()

const campaign = ref(null)
const isLoading = ref(false)
const isSubmittingReview = ref(false)
const activeTab = ref('details') // 'details', 'tiers', 'gallery'

// Main form
const form = ref({
  title: '',
  category_id: '',
  target_amount: 0,
  deadline: '',
  description: '',
  video_url: '',
})

// Tier management state
const tiers = ref([])
const isTierModalOpen = ref(false)
const editingTierId = ref(null)
const tierForm = ref({
  name: '',
  min_amount: 50000,
  quota: 50,
  reward_description: '',
})

// Image upload state
const newImageUrls = ref([''])
const isUploadingImage = ref(false)

// Minimum deadline date (H+7)
const minDeadline = computed(() => {
  const d = new Date()
  d.setDate(d.getDate() + 7)
  return d.toISOString().split('T')[0]
})

async function loadCampaign() {
  isLoading.value = true
  try {
    const res = await campaignService.getOne(route.params.id)
    campaign.value = res.data
    form.value = {
      title: campaign.value.title,
      category_id: campaign.value.category_id,
      target_amount: Number(campaign.value.target_amount),
      deadline: campaign.value.deadline ? campaign.value.deadline.split('T')[0] : '',
      description: campaign.value.description,
      video_url: campaign.value.video_url || '',
    }
    await loadTiers()
  } catch (error) {
    toast.error('Gagal memuat data kampanye.')
    router.push({ name: 'creator.dashboard' })
  } finally {
    isLoading.value = false
  }
}

async function loadTiers() {
  try {
    const res = await campaignService.getTiers(route.params.id)
    tiers.value = res.data
  } catch (error) {
    console.error('Gagal memuat tiers:', error)
  }
}

async function handleUpdateDetails() {
  isLoading.value = true
  try {
    await campaignService.update(campaign.value.id, form.value)
    toast.success('Informasi kampanye berhasil diperbarui.')
    await loadCampaign()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal memperbarui kampanye.')
  } finally {
    isLoading.value = false
  }
}

// Tier actions
function openAddTierModal() {
  editingTierId.value = null
  tierForm.value = {
    name: '',
    min_amount: 50000,
    quota: 50,
    reward_description: '',
  }
  isTierModalOpen.value = true
}

function openEditTierModal(tier) {
  editingTierId.value = tier.id
  tierForm.value = {
    name: tier.name,
    min_amount: Number(tier.min_amount),
    quota: Number(tier.quota),
    reward_description: tier.reward_description,
  }
  isTierModalOpen.value = true
}

async function handleSaveTier() {
  if (!tierForm.value.name || !tierForm.value.reward_description) {
    toast.error('Mohon lengkapi seluruh kolom tier reward.')
    return
  }

  try {
    if (editingTierId.value) {
      await campaignService.updateTier(campaign.value.id, editingTierId.value, tierForm.value)
      toast.success('Tier reward berhasil diperbarui.')
    } else {
      await campaignService.addTier(campaign.value.id, tierForm.value)
      toast.success('Tier reward baru berhasil ditambahkan.')
    }
    isTierModalOpen.value = false
    await loadTiers()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan tier reward.')
  }
}

async function handleDeleteTier(tierId) {
  if (!confirm('Apakah Anda yakin ingin menghapus tier reward ini?')) return
  try {
    await campaignService.deleteTier(campaign.value.id, tierId)
    toast.success('Tier reward berhasil dihapus.')
    await loadTiers()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus tier.')
  }
}

// Image actions
async function handleUploadImages() {
  const validUrls = newImageUrls.value.filter(u => u.trim() !== '')
  if (validUrls.length === 0) {
    toast.error('Masukkan setidaknya satu URL gambar.')
    return
  }

  isUploadingImage.value = true
  try {
    await campaignService.uploadImages(campaign.value.id, { images: validUrls })
    toast.success('Foto berhasil ditambahkan ke galeri.')
    newImageUrls.value = ['']
    await loadCampaign()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal mengunggah foto.')
  } finally {
    isUploadingImage.value = false
  }
}

function addImageUrlField() {
  if (newImageUrls.value.length < 4) {
    newImageUrls.value.push('')
  }
}

function removeImageUrlField(index) {
  newImageUrls.value.splice(index, 1)
}

async function handleDeleteImage(imageId) {
  if (!confirm('Hapus foto ini dari kampanye?')) return
  try {
    await campaignService.deleteImage(campaign.value.id, imageId)
    toast.success('Foto berhasil dihapus.')
    await loadCampaign()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus foto.')
  }
}

async function handleSetPrimary(imageId) {
  try {
    await campaignService.setPrimaryImage(campaign.value.id, imageId)
    toast.success('Foto cover utama berhasil diubah.')
    await loadCampaign()
  } catch (error) {
    toast.error('Gagal mengatur foto utama.')
  }
}

// Submit review
async function handleSubmitReview() {
  if (tiers.value.length === 0) {
    if (!confirm('Kampanye belum memiliki tier reward. Apakah Anda tetap ingin mengajukan tanpa tier reward?')) {
      return
    }
  }

  if (!confirm('Ajukan kampanye ini ke Admin untuk ditinjau? Status akan berubah menjadi Review.')) return

  isSubmittingReview.value = true
  try {
    await campaignService.submitReview(campaign.value.id)
    toast.success('Kampanye berhasil diajukan untuk ditinjau oleh Admin.')
    router.push({ name: 'creator.dashboard' })
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal mengajukan kampanye.')
  } finally {
    isSubmittingReview.value = false
  }
}

// Delete draft
async function handleDeleteCampaign() {
  if (!confirm('Apakah Anda yakin ingin menghapus draft kampanye ini secara permanen?')) return
  try {
    await campaignService.delete(campaign.value.id)
    toast.success('Draft kampanye berhasil dihapus.')
    router.push({ name: 'creator.dashboard' })
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus kampanye.')
  }
}

function formatRupiah(num) {
  return 'Rp ' + Number(num || 0).toLocaleString('id-ID')
}

onMounted(() => {
  campaignStore.fetchCategories()
  loadCampaign()
})
</script>

<template>
  <div v-if="campaign" class="space-y-6 max-w-6xl mx-auto pb-12">
    
    <!-- Top Header Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
      <div>
        <div class="flex items-center gap-3">
          <router-link :to="{ name: 'creator.dashboard' }" class="text-xs font-semibold text-slate-500 hover:text-orange-600 transition-colors flex items-center gap-1">
            <i class="pi pi-arrow-left text-[10px]"></i>
            Kembali ke Dashboard
          </router-link>
          <span class="text-slate-300">|</span>
          <span
            class="text-[11px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"
            :class="{
              'bg-amber-100 text-amber-800': campaign.status === 'draft',
              'bg-blue-100 text-blue-800': campaign.status === 'review',
              'bg-emerald-100 text-emerald-800': campaign.status === 'active',
              'bg-rose-100 text-rose-800': campaign.status === 'rejected' || campaign.status === 'failed',
            }"
          >
            Status: {{ campaign.status }}
          </span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 mt-2 tracking-tight">{{ form.title || 'Edit Kampanye' }}</h1>
        <p class="text-xs text-slate-500 mt-0.5">Kelola informasi proyek, paket reward, foto galeri, dan pengajuan kurasi.</p>
      </div>

      <div class="flex items-center gap-2 flex-wrap">
        <button
          v-if="campaign.status === 'draft'"
          @click="handleSubmitReview"
          :disabled="isSubmittingReview"
          class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl shadow-md shadow-orange-600/20 transition-all flex items-center gap-2"
        >
          <span>Ajukan Review ke Admin</span>
        </button>

        <button
          v-if="campaign.status === 'draft'"
          @click="handleDeleteCampaign"
          class="px-4 py-2.5 bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200 rounded-xl text-xs font-bold transition-colors"
        >
          Hapus Draft
        </button>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
      <button
        @click="activeTab = 'details'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition-colors"
        :class="activeTab === 'details' ? 'bg-orange-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
      >
        1. Informasi & Detail Proyek
      </button>
      <button
        @click="activeTab = 'tiers'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5"
        :class="activeTab === 'tiers' ? 'bg-orange-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
      >
        <span>2. Paket Reward (Tier)</span>
        <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'tiers' ? 'bg-orange-800 text-white' : 'bg-slate-200 text-slate-700'">
          {{ tiers.length }}
        </span>
      </button>
      <button
        @click="activeTab = 'gallery'"
        class="px-4 py-2 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5"
        :class="activeTab === 'gallery' ? 'bg-orange-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
      >
        <span>3. Galeri Foto</span>
        <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'gallery' ? 'bg-orange-800 text-white' : 'bg-slate-200 text-slate-700'">
          {{ campaign.images?.length || 0 }}
        </span>
      </button>
    </div>

    <!-- TAB 1: DETAILS FORM -->
    <div v-show="activeTab === 'details'" class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 space-y-6 shadow-sm">
      <div class="flex items-center justify-between border-b border-slate-100 pb-4">
        <div>
          <h2 class="text-base font-bold text-slate-900">Informasi Utama Kampanye</h2>
          <p class="text-xs text-slate-500">Lengkapi data tujuan penggalangan dana dan target proyek Anda.</p>
        </div>
      </div>

      <form @submit.prevent="handleUpdateDetails" class="space-y-5">
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Judul Kampanye <span class="text-orange-600">*</span></label>
          <input
            v-model="form.title"
            type="text"
            required
            maxlength="100"
            :disabled="campaign.status !== 'draft'"
            placeholder="Contoh: Robot Pembersih Sampah Sungai Otomatis"
            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 disabled:opacity-60"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Kategori <span class="text-orange-600">*</span></label>
            <select
              v-model="form.category_id"
              required
              :disabled="campaign.status !== 'draft'"
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 disabled:opacity-60"
            >
              <option value="" disabled>Pilih Kategori</option>
              <option v-for="cat in campaignStore.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Target Dana (Rp) <span class="text-orange-600">*</span></label>
            <input
              v-model.number="form.target_amount"
              type="number"
              min="100000"
              step="10000"
              required
              :disabled="campaign.status !== 'draft'"
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 disabled:opacity-60"
            />
            <p class="text-[11px] text-slate-500 mt-1">Minimal Rp 100.000</p>
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Batas Waktu (Deadline) <span class="text-orange-600">*</span></label>
            <input
              v-model="form.deadline"
              type="date"
              :min="minDeadline"
              required
              :disabled="campaign.status !== 'draft'"
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 disabled:opacity-60"
            />
            <p class="text-[11px] text-slate-500 mt-1">Minimal 7 hari dari sekarang</p>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Video Pitching (URL YouTube / Vimeo)</label>
          <input
            v-model="form.video_url"
            type="url"
            :disabled="campaign.status !== 'draft'"
            placeholder="https://www.youtube.com/watch?v=..."
            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 disabled:opacity-60"
          />
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Cerita & Deskripsi Lengkap <span class="text-orange-600">*</span></label>
          <textarea
            v-model="form.description"
            rows="7"
            required
            :disabled="campaign.status !== 'draft'"
            placeholder="Jelaskan latar belakang inovasi, tujuan penggunaan dana, dan dampak positif proyek Anda..."
            class="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 disabled:opacity-60"
          ></textarea>
        </div>

        <div v-if="campaign.status === 'draft'" class="flex justify-end pt-2">
          <button
            type="submit"
            :disabled="isLoading"
            class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl shadow-md shadow-orange-600/20 transition-all flex items-center gap-2"
          >
            <span>Simpan Informasi Kampanye</span>
          </button>
        </div>
      </form>
    </div>

    <!-- TAB 2: TIERS REWARD -->
    <div v-show="activeTab === 'tiers'" class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 space-y-6 shadow-sm">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
        <div>
          <h2 class="text-base font-bold text-slate-900">Paket Donasi & Reward Donatur (Tiers)</h2>
          <p class="text-xs text-slate-500">Berikan apresiasi menarik (produk awal, merchandise, akses eksklusif) untuk donatur Anda.</p>
        </div>
        <button
          v-if="campaign.status === 'draft'"
          @click="openAddTierModal"
          class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all self-start flex items-center gap-1.5"
        >
          <i class="pi pi-plus text-xs"></i>
          <span>Tambah Paket Reward</span>
        </button>
      </div>

      <!-- Tiers List -->
      <div v-if="tiers.length === 0" class="text-center py-12 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
        <p class="text-sm font-semibold text-slate-700">Belum ada paket reward yang ditambahkan.</p>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Menambahkan paket reward dapat meningkatkan peluang kampanye Anda didukung oleh donatur hingga 3x lipat.</p>
        <button
          v-if="campaign.status === 'draft'"
          @click="openAddTierModal"
          class="mt-4 px-4 py-2 bg-white border border-slate-300 hover:border-orange-500 text-orange-600 text-xs font-bold rounded-xl transition-all"
        >
          Buat Paket Pertama
        </button>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="tier in tiers"
          :key="tier.id"
          class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between hover:border-orange-300 transition-colors shadow-sm"
        >
          <div>
            <div class="flex items-start justify-between gap-2">
              <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ tier.name }}</h3>
              <span class="text-xs font-black text-orange-600 shrink-0">{{ formatRupiah(tier.min_amount) }}</span>
            </div>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed whitespace-pre-line">{{ tier.reward_description }}</p>
          </div>

          <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[11px] font-semibold text-slate-500">
              Kuota: <strong class="text-slate-800">{{ tier.remaining_quota }}</strong> / {{ tier.quota }}
            </span>

            <div v-if="campaign.status === 'draft'" class="flex items-center gap-1.5">
              <button
                @click="openEditTierModal(tier)"
                class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs transition-colors"
                title="Edit Tier"
              >
                <i class="pi pi-pencil"></i>
              </button>
              <button
                @click="handleDeleteTier(tier.id)"
                class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs transition-colors"
                title="Hapus Tier"
              >
                <i class="pi pi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: GALLERY & IMAGES -->
    <div v-show="activeTab === 'gallery'" class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 space-y-6 shadow-sm">
      <div class="border-b border-slate-100 pb-4">
        <h2 class="text-base font-bold text-slate-900">Galeri Foto Kampanye</h2>
        <p class="text-xs text-slate-500">Upload hingga 5 foto beresolusi tinggi. Pilih salah satu sebagai foto cover utama.</p>
      </div>

      <!-- Current Photos -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div
          v-for="img in campaign.images"
          :key="img.id"
          class="relative h-36 rounded-2xl overflow-hidden border bg-slate-100 group"
          :class="img.is_primary ? 'border-orange-600 ring-2 ring-orange-500/30' : 'border-slate-200'"
        >
          <img
            :src="formatImageUrl(img.url)"
            class="w-full h-full object-cover"
          />
          <span v-if="img.is_primary" class="absolute top-2 left-2 bg-orange-600 text-white text-[9px] font-bold px-2 py-0.5 rounded-full shadow-sm">
            Cover Utama
          </span>
          <div
            v-if="campaign.status === 'draft'"
            class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 flex items-center justify-center gap-2 transition-opacity"
          >
            <button
              v-if="!img.is_primary"
              type="button"
              @click="handleSetPrimary(img.id)"
              class="px-2.5 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-semibold"
            >
              Jadikan Cover
            </button>
            <button
              type="button"
              @click="handleDeleteImage(img.id)"
              class="p-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs"
              title="Hapus Foto"
            >
              <i class="pi pi-trash"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Add New Photos Form (URL) -->
      <div v-if="campaign.status === 'draft' && (campaign.images?.length || 0) < 5" class="pt-4 border-t border-slate-100 space-y-4">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Tambah Foto Baru (URL Gambar)</h3>
        
        <div v-for="(url, index) in newImageUrls" :key="index" class="flex items-center gap-2">
          <input
            v-model="newImageUrls[index]"
            type="url"
            placeholder="https://images.unsplash.com/photo-..."
            class="flex-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-orange-500"
          />
          <button
            v-if="newImageUrls.length > 1"
            type="button"
            @click="removeImageUrlField(index)"
            class="p-2 text-slate-400 hover:text-rose-600 transition-colors"
          >
            <i class="pi pi-times text-xs"></i>
          </button>
        </div>

        <div class="flex items-center gap-3">
          <button
            v-if="newImageUrls.length < 4"
            type="button"
            @click="addImageUrlField"
            class="text-xs font-bold text-orange-600 hover:text-orange-700 transition-colors flex items-center gap-1"
          >
            <i class="pi pi-plus text-[10px]"></i>
            Tambah Baris URL
          </button>
          <button
            type="button"
            @click="handleUploadImages"
            :disabled="isUploadingImage"
            class="ml-auto px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl transition-all"
          >
            Upload ke Galeri
          </button>
        </div>
      </div>
    </div>

    <!-- TIER MODAL -->
    <div v-if="isTierModalOpen" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-bold text-slate-900">{{ editingTierId ? 'Edit Paket Reward' : 'Tambah Paket Reward Baru' }}</h3>
          <button @click="isTierModalOpen = false" class="text-slate-400 hover:text-slate-600">
            <i class="pi pi-times"></i>
          </button>
        </div>

        <form @submit.prevent="handleSaveTier" class="space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Paket <span class="text-orange-600">*</span></label>
            <input
              v-model="tierForm.name"
              type="text"
              required
              placeholder="Contoh: Paket Early Supporter"
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Minimal Donasi (Rp) <span class="text-orange-600">*</span></label>
              <input
                v-model.number="tierForm.min_amount"
                type="number"
                min="10000"
                step="5000"
                required
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
              />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Kuota Tersedia <span class="text-orange-600">*</span></label>
              <input
                v-model.number="tierForm.quota"
                type="number"
                min="1"
                required
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Deskripsi Reward <span class="text-orange-600">*</span></label>
            <textarea
              v-model="tierForm.reward_description"
              rows="3"
              required
              placeholder="Jelaskan apa yang akan didapatkan donatur..."
              class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="isTierModalOpen = false"
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl transition-colors"
            >
              Simpan Paket
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>
