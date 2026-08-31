<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { campaignService } from '@/services/campaignService'
import { useCampaignStore } from '@/stores/useCampaignStore'
import { formatCurrency } from '@/utils/formatCurrency'
import { useToast } from 'vue-toastification'

const router = useRouter()
const campaignStore = useCampaignStore()
const toast = useToast()

const step = ref(1)
const isLoading = ref(false)
const createdCampaign = ref(null)

// Step 1 Form Data
const form = ref({
  title: '',
  category_id: '',
  target_amount: 1000000,
  deadline: '',
  description: '',
  video_url: '',
})

// Step 2 Photos Data
const imageFiles = ref([])
const imageUrlInputs = ref([''])

// Step 3 Tiers Data
const tiers = ref([
  { name: '', min_amount: 50000, quota: 0, reward_description: '' }
])

function addTier() {
  tiers.value.push({ name: '', min_amount: 50000, quota: 0, reward_description: '' })
}

function removeTier(index) {
  if (tiers.value.length > 1) {
    tiers.value.splice(index, 1)
  }
}

function addImageUrl() {
  if (imageUrlInputs.value.length < 5) {
    imageUrlInputs.value.push('')
  }
}

function removeImageUrl(index) {
  imageUrlInputs.value.splice(index, 1)
}

function handleFileChange(event) {
  imageFiles.value = Array.from(event.target.files).slice(0, 5)
}

// Action: Submit Step 1 (Create Base Campaign)
async function submitStep1() {
  isLoading.value = true
  try {
    const res = await campaignService.create(form.value)
    createdCampaign.value = res.data.campaign
    toast.success('Informasi dasar kampanye tersimpan sebagai Draft.')
    step.value = 2
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan kampanye.')
  } finally {
    isLoading.value = false
  }
}

// Action: Submit Step 2 (Upload Photos)
async function submitStep2() {
  isLoading.value = true
  try {
    const campaignId = createdCampaign.value.id

    if (imageFiles.value.length > 0) {
      const formData = new FormData()
      imageFiles.value.forEach(f => formData.append('images[]', f))
      await campaignService.uploadImages(campaignId, formData)
    } else {
      const validUrls = imageUrlInputs.value.filter(u => u.trim() !== '')
      if (validUrls.length > 0) {
        await campaignService.uploadImages(campaignId, { images: validUrls })
      } else {
        toast.error('Wajib mengunggah minimal 1 foto kampanye.')
        isLoading.value = false
        return
      }
    }

    toast.success('Foto kampanye berhasil diunggah.')
    step.value = 3
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal mengunggah foto.')
  } finally {
    isLoading.value = false
  }
}

// Action: Submit Step 3 (Save Tiers & Submit Review)
async function submitStep3() {
  isLoading.value = true
  try {
    const campaignId = createdCampaign.value.id

    // Save all tiers
    for (const t of tiers.value) {
      if (t.name.trim()) {
        await campaignService.addTier(campaignId, t)
      }
    }

    // Submit for admin review
    await campaignService.submitReview(campaignId)
    toast.success('Kampanye Anda berhasil diajukan untuk review Admin!')
    router.push({ name: 'creator.dashboard' })
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyelesaikan pengajuan.')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  campaignStore.fetchCategories()
  // Set default deadline to today + 14 days
  const d = new Date()
  d.setDate(d.getDate() + 14)
  form.value.deadline = d.toISOString().split('T')[0]
})
</script>

<template>
  <div class="space-y-6">
    
    <!-- Title & Wizard Stepper Header -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 space-y-6">
      <div>
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Galang Dana Baru</h2>
        <p class="text-xs text-slate-500 mt-1">Lengkapi data proyek inovasi Anda melalui 3 langkah mudah</p>
      </div>

      <!-- Step Progress Indicator -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <div class="grid grid-cols-3 gap-2 text-center text-xs font-bold">
          <div
            class="py-2.5 rounded-xl transition-all"
            :class="step >= 1 ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'bg-slate-100 text-slate-400'"
          >
            1. Data Dasar
          </div>
          <div
            class="py-2.5 rounded-xl transition-all"
            :class="step >= 2 ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'bg-slate-100 text-slate-400'"
          >
            2. Foto (1-5)
          </div>
          <div
            class="py-2.5 rounded-xl transition-all"
            :class="step >= 3 ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'bg-slate-100 text-slate-400'"
          >
            3. Reward Tier & Review
          </div>
        </div>
      </div>
    </div>

    <!-- Step 1: Base Details Form -->
    <div v-if="step === 1" class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8">
      <form @submit.prevent="submitStep1" class="space-y-4">
        
        <!-- Judul -->
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Judul Kampanye (Maks. 100 Karakter)</label>
          <input
            v-model="form.title"
            type="text"
            required
            maxlength="100"
            placeholder="Contoh: Robot Pembersih Sampah Sungai Otomatis"
            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
          />
        </div>

        <!-- Kategori & Target Dana -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Kategori</label>
            <select
              v-model="form.category_id"
              required
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
            >
              <option value="" disabled>Pilih Kategori...</option>
              <option v-for="cat in campaignStore.categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Target Dana (Min. Rp 100.000)</label>
            <input
              v-model.number="form.target_amount"
              type="number"
              min="100000"
              required
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>
        </div>

        <!-- Deadline & Video URL -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Batas Waktu / Deadline (Min. H+7)</label>
            <input
              v-model="form.deadline"
              type="date"
              required
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Video YouTube/Vimeo (Opsional)</label>
            <input
              v-model="form.video_url"
              type="url"
              placeholder="https://youtube.com/watch?v=..."
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>
        </div>

        <!-- Deskripsi -->
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Deskripsi Lengkap Proyek</label>
          <textarea
            v-model="form.description"
            required
            rows="6"
            placeholder="Jelaskan latar belakang inovasi, anggaran belanja, rencana kerja, dan manfaat bagi masyarakat..."
            class="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
          ></textarea>
        </div>

        <button
          type="submit"
          :disabled="isLoading"
          class="w-full py-3.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-2xl shadow-lg shadow-orange-600/30 transition-all flex items-center justify-center gap-2"
        >
          <span>Lanjut ke Unggah Foto</span>
          <i class="pi pi-arrow-right text-xs"></i>
        </button>
      </form>
    </div>

    <!-- Step 2: Upload Images -->
    <div v-else-if="step === 2" class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 space-y-6">
      <div>
        <h3 class="text-lg font-black text-slate-900">Unggah Foto Kampanye (1 - 5 Foto)</h3>
        <p class="text-xs text-slate-500 mt-1">Pilih file gambar lokal atau masukkan tautan URL gambar</p>
      </div>

      <div class="space-y-4">
        <!-- File Upload Option -->
        <div class="p-6 border-2 border-dashed border-slate-200 hover:border-orange-500 rounded-2xl text-center bg-slate-50 cursor-pointer">
          <input
            type="file"
            multiple
            accept="image/*"
            @change="handleFileChange"
            class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-orange-600 file:text-white hover:file:bg-orange-700"
          />
          <p class="text-[11px] text-slate-400 mt-2">Maksimal 5 gambar (JPG, PNG, WEBP)</p>
        </div>

        <!-- OR URL Input Option -->
        <div class="space-y-2">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Atau Gunakan URL Gambar:</label>
          <div v-for="(url, idx) in imageUrlInputs" :key="idx" class="flex gap-2">
            <input
              v-model="imageUrlInputs[idx]"
              type="url"
              placeholder="https://images.unsplash.com/photo-..."
              class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
            <button
              type="button"
              @click="removeImageUrl(idx)"
              class="px-3 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-xl text-xs"
            >
              <i class="pi pi-trash"></i>
            </button>
          </div>
          <button
            v-if="imageUrlInputs.length < 5"
            type="button"
            @click="addImageUrl"
            class="text-xs font-bold text-orange-600 hover:text-orange-700"
          >
            + Tambah Baris URL Gambar
          </button>
        </div>
      </div>

      <button
        @click="submitStep2"
        :disabled="isLoading"
        class="w-full py-3.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-2xl shadow-lg shadow-orange-600/30 transition-all flex items-center justify-center gap-2"
      >
        <span>Lanjut ke Pengaturan Tier</span>
        <i class="pi pi-arrow-right text-xs"></i>
      </button>
    </div>

    <!-- Step 3: Tiers & Final Submit Review -->
    <div v-else-if="step === 3" class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-black text-slate-900">Kelola Paket Reward (Tier)</h3>
          <p class="text-xs text-slate-500 mt-1">Minimal buat 1 paket apresiasi untuk donatur Anda</p>
        </div>
        <button
          type="button"
          @click="addTier"
          class="px-3.5 py-2 bg-orange-50 text-orange-700 hover:bg-orange-100 rounded-xl text-xs font-bold transition-colors"
        >
          + Tambah Tier
        </button>
      </div>

      <!-- Tiers Repeater -->
      <div class="space-y-4">
        <div
          v-for="(t, idx) in tiers"
          :key="idx"
          class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3 relative"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-extrabold text-slate-700">Paket Tier #{{ idx + 1 }}</span>
            <button
              v-if="tiers.length > 1"
              type="button"
              @click="removeTier(idx)"
              class="text-rose-500 text-xs hover:text-rose-700 font-bold"
            >
              Hapus
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Paket</label>
              <input
                v-model="t.name"
                type="text"
                required
                placeholder="Contoh: Early Supporter"
                class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-600 mb-1">Nominal Donasi Minimal</label>
              <input
                v-model.number="t.min_amount"
                type="number"
                min="10000"
                required
                class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-600 mb-1">Kuota Slot (0 = Tak Terbatas)</label>
              <input
                v-model.number="t.quota"
                type="number"
                min="0"
                required
                class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Deskripsi Reward / Hadiah</label>
            <input
              v-model="t.reward_description"
              type="text"
              placeholder="Contoh: Merchandise eksklusif + Sertifikat digital"
              class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none"
            />
          </div>
        </div>
      </div>

      <!-- Final Submit to Admin Action -->
      <button
        @click="submitStep3"
        :disabled="isLoading"
        class="w-full py-4 bg-orange-600 hover:bg-orange-700 text-white font-extrabold rounded-2xl shadow-xl shadow-orange-600/30 transition-all flex items-center justify-center gap-2"
      >
        <i v-if="isLoading" class="pi pi-spin pi-spinner"></i>
        <span>{{ isLoading ? 'Mengajukan...' : 'Ajukan Kampanye ke Admin (Submit Review)' }}</span>
      </button>
    </div>

  </div>
</template>
