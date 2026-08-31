<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useCampaignStore } from '@/stores/useCampaignStore'
import { formatCurrency } from '@/utils/formatCurrency'
import { formatDate } from '@/utils/formatDate'
import { formatImageUrl } from '@/utils/formatImage'
import TierCard from '@/components/campaign/TierCard.vue'
import BackingModal from '@/components/backing/BackingModal.vue'

const route = useRoute()
const campaignStore = useCampaignStore()

const campaign = computed(() => campaignStore.currentCampaign)
const activeImageIndex = ref(0)
const activeTab = ref('description')

const isBackingModalOpen = ref(false)
const selectedTierForBacking = ref(null)

const activeImage = computed(() => {
  if (!campaign.value?.images?.length) {
    return formatImageUrl(null)
  }
  const img = campaign.value.images[activeImageIndex.value] || campaign.value.images[0]
  return formatImageUrl(img?.url)
})

const percentage = computed(() => {
  if (!campaign.value?.target_amount || campaign.value.target_amount <= 0) return 0
  return Math.min(100, Math.round((campaign.value.collected_amount / campaign.value.target_amount) * 100))
})

function openBacking(tier = null) {
  selectedTierForBacking.value = tier
  isBackingModalOpen.value = true
}

async function loadCampaign() {
  await campaignStore.fetchCampaign(route.params.slug)
}

onMounted(() => {
  loadCampaign()
})
</script>

<template>
  <div v-if="campaignStore.isLoading" class="max-w-7xl mx-auto px-4 py-16 text-center">
    <i class="pi pi-spin pi-spinner text-3xl text-orange-600"></i>
    <p class="text-xs text-slate-500 mt-2">Memuat detail kampanye...</p>
  </div>

  <div v-else-if="campaign" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
    
    <!-- Breadcrumb & Header Title -->
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-2">
        <router-link to="/" class="hover:text-orange-600">Beranda</router-link>
        <i class="pi pi-chevron-right text-[10px]"></i>
        <span>{{ campaign.category?.name || 'Inovasi' }}</span>
      </div>
      <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
        {{ campaign.title }}
      </h1>
      <p class="text-xs text-slate-500 mt-1">
        Digagas oleh <strong class="text-slate-800">{{ campaign.creator?.name || 'Creator' }}</strong> · Berakhir pada {{ formatDate(campaign.deadline) }}
      </p>
    </div>

    <!-- Main Grid: Images + Funding Card -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
      
      <!-- Left 2 Cols: Image Gallery -->
      <div class="lg:col-span-2 space-y-4">
        <!-- Main Large Photo -->
        <div class="h-80 sm:h-[420px] w-full rounded-3xl overflow-hidden bg-slate-100 border border-slate-200 shadow-sm relative">
          <img
            :src="activeImage"
            :alt="campaign.title"
            class="w-full h-full object-cover"
          />
        </div>

        <!-- Thumbnails Gallery -->
        <div v-if="campaign.images?.length > 1" class="flex gap-3 overflow-x-auto pb-2">
          <button
            v-for="(img, idx) in campaign.images"
            :key="img.id"
            @click="activeImageIndex = idx"
            class="w-20 h-16 rounded-xl overflow-hidden border-2 flex-shrink-0 transition-all"
            :class="activeImageIndex === idx ? 'border-orange-600 ring-2 ring-orange-500/30' : 'border-slate-200 opacity-60 hover:opacity-100'"
          >
            <img
              :src="formatImageUrl(img.url)"
              class="w-full h-full object-cover"
            />
          </button>
        </div>
      </div>

      <!-- Right 1 Col: Sticky Funding Action Card -->
      <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-7 shadow-lg shadow-slate-200/50 space-y-6 lg:sticky lg:top-24">
        
        <!-- Progress Bar -->
        <div class="space-y-2">
          <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
            <div
              class="bg-gradient-to-r from-orange-500 to-amber-400 h-full rounded-full transition-all duration-500"
              :style="{ width: `${percentage}%` }"
            ></div>
          </div>
          <div class="flex items-center justify-between text-xs font-bold text-orange-600">
            <span>{{ percentage }}% Tercapai</span>
            <span class="text-slate-400 font-normal">Target {{ formatCurrency(campaign.target_amount) }}</span>
          </div>
        </div>

        <!-- Fin Metrics -->
        <div class="space-y-3">
          <div>
            <span class="text-xs text-slate-400 block font-semibold">Dana Terkumpul</span>
            <span class="text-3xl font-black text-slate-900">
              {{ formatCurrency(campaign.collected_amount) }}
            </span>
          </div>

          <div class="grid grid-cols-2 gap-4 pt-3 border-t border-slate-100">
            <div>
              <span class="text-xs text-slate-400 block font-semibold">Total Donatur</span>
              <span class="text-xl font-extrabold text-slate-800">{{ campaign.backings_count || 0 }}</span>
            </div>
            <div>
              <span class="text-xs text-slate-400 block font-semibold">Sisa Waktu</span>
              <span class="text-xl font-extrabold text-slate-800">{{ campaign.days_left || 0 }} Hari</span>
            </div>
          </div>
        </div>

        <!-- Big Back Button -->
        <div class="space-y-2.5 pt-2">
          <button
            v-if="campaign.status === 'active'"
            @click="openBacking(null)"
            class="w-full py-4 bg-orange-600 hover:bg-orange-700 text-white font-extrabold text-sm rounded-2xl shadow-xl shadow-orange-600/30 transition-all flex items-center justify-center gap-2 hover:scale-[1.02]"
          >
            <span>Dukung Proyek Ini Sekarang</span>
          </button>

          <div v-else class="p-3.5 bg-slate-100 rounded-2xl text-center text-xs font-bold text-slate-500 uppercase">
            Kampanye Tidak Menerima Donasi ({{ campaign.status }})
          </div>

          <p class="text-[11px] text-center text-slate-400 flex items-center justify-center gap-1">
            <i class="pi pi-shield text-orange-600 text-xs"></i>
            Jaminan Virtual Escrow & Refund Otomatis
          </p>
        </div>

      </div>

    </div>

    <!-- Details Section with Tabs -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 space-y-6">
      
      <!-- Tab Buttons -->
      <div class="flex items-center gap-4 border-b border-slate-100 pb-3">
        <button
          @click="activeTab = 'description'"
          class="text-sm font-bold pb-2 transition-all"
          :class="activeTab === 'description' ? 'text-orange-600 border-b-2 border-orange-600' : 'text-slate-500 hover:text-slate-800'"
        >
          Cerita & Deskripsi
        </button>
        <button
          @click="activeTab = 'tiers'"
          class="text-sm font-bold pb-2 transition-all flex items-center gap-1.5"
          :class="activeTab === 'tiers' ? 'text-orange-600 border-b-2 border-orange-600' : 'text-slate-500 hover:text-slate-800'"
        >
          <span>Pilihan Reward Tier</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600">
            {{ campaign.tiers?.length || 0 }}
          </span>
        </button>
        <button
          @click="activeTab = 'updates'"
          class="text-sm font-bold pb-2 transition-all flex items-center gap-1.5"
          :class="activeTab === 'updates' ? 'text-orange-600 border-b-2 border-orange-600' : 'text-slate-500 hover:text-slate-800'"
        >
          <span>Kabar Terbaru</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600">
            {{ campaign.updates?.length || 0 }}
          </span>
        </button>
        <button
          @click="activeTab = 'backers'"
          class="text-sm font-bold pb-2 transition-all flex items-center gap-1.5"
          :class="activeTab === 'backers' ? 'text-orange-600 border-b-2 border-orange-600' : 'text-slate-500 hover:text-slate-800'"
        >
          <span>Donatur</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600">
            {{ campaign.backings_count || campaign.backings?.length || 0 }}
          </span>
        </button>
      </div>

      <!-- Tab Content: Description -->
      <div v-if="activeTab === 'description'" class="prose prose-slate max-w-none text-slate-700 text-sm leading-relaxed whitespace-pre-line">
        {{ campaign.description }}
      </div>

      <!-- Tab Content: Tiers List -->
      <div v-else-if="activeTab === 'tiers'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <TierCard
          v-for="tier in campaign.tiers"
          :key="tier.id"
          :tier="tier"
          @select="openBacking(tier)"
        />
      </div>

      <!-- Tab Content: Updates -->
      <div v-else-if="activeTab === 'updates'" class="space-y-4">
        <div
          v-if="!campaign.updates?.length"
          class="text-center py-10 text-slate-400 text-xs"
        >
          Belum ada pembaruan kabar dari creator.
        </div>
        <div
          v-for="item in campaign.updates"
          :key="item.id"
          class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2"
        >
          <div class="flex items-center justify-between text-xs text-slate-400">
            <span class="font-bold text-slate-700">Pembaruan Kampanye</span>
            <span>{{ formatDate(item.created_at) }}</span>
          </div>
          <p class="text-sm text-slate-800 whitespace-pre-line">{{ item.content }}</p>
        </div>
      </div>

      <!-- Tab Content: Backers List -->
      <div v-else-if="activeTab === 'backers'" class="space-y-3">
        <div
          v-if="!campaign.backings?.length"
          class="text-center py-10 text-slate-400 text-xs"
        >
          Belum ada donatur pada kampanye ini. Jadilah donatur pertama!
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div
            v-for="b in campaign.backings"
            :key="b.id"
            class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-sm">
                {{ b.backer?.name ? b.backer.name.charAt(0).toUpperCase() : 'D' }}
              </div>
              <div>
                <h4 class="font-bold text-sm text-slate-900">{{ b.backer?.name || 'Donatur Anonim' }}</h4>
                <span class="text-[11px] text-slate-400">{{ formatDate(b.created_at) }}</span>
              </div>
            </div>
            <div class="text-right">
              <span class="font-black text-sm text-orange-600 block">{{ formatCurrency(b.amount) }}</span>
              <span class="text-[10px] text-slate-400 uppercase font-semibold">{{ b.status }}</span>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Backing Modal Component -->
    <BackingModal
      :is-open="isBackingModalOpen"
      :campaign="campaign"
      :selected-tier="selectedTierForBacking"
      @close="isBackingModalOpen = false"
      @success="loadCampaign"
    />

  </div>
</template>
