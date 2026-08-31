<script setup>
import { ref, onMounted, watch } from 'vue'
import { useCampaignStore } from '@/stores/useCampaignStore'
import CampaignCard from '@/components/campaign/CampaignCard.vue'

const campaignStore = useCampaignStore()

const selectedCategory = ref(null)
const selectedSort = ref('latest')
const searchQuery = ref('')

async function loadData() {
  await campaignStore.fetchCategories()
  await fetchCampaigns()
}

async function fetchCampaigns() {
  const params = {
    status: 'active',
    sort: selectedSort.value,
  }
  if (selectedCategory.value) {
    params.category_id = selectedCategory.value
  }
  if (searchQuery.value) {
    params.search = searchQuery.value
  }
  await campaignStore.fetchCampaigns(params)
}

let searchTimeout = null

watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchCampaigns()
  }, 350)
})

watch([selectedCategory, selectedSort], () => {
  fetchCampaigns()
})

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="space-y-12 pb-16">
    
    <!-- Hero Banner Section -->
    <section class="relative bg-gradient-to-b from-orange-600 via-orange-500 to-amber-600 text-white py-16 sm:py-24 px-4 overflow-hidden shadow-sm">
      <!-- Glow Background Elements -->
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-amber-300/20 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-4xl mx-auto text-center relative z-10 space-y-6">

        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight text-white leading-tight">
          Wujudkan Ide Inovatif Bersama <span class="underline decoration-white/40">CoFund</span>
        </h1>

        <p class="text-sm sm:text-base text-orange-50 max-w-2xl mx-auto leading-relaxed">
          Dukung pendanaan proyek teknologi, pendidikan, karya kreatif, dan aksi sosial lokal dengan jaminan sistem <strong>Virtual Escrow 100% Aman</strong>.
        </p>

        <!-- Search Bar Input in Hero -->
        <div class="max-w-xl mx-auto pt-4">
          <form @submit.prevent="fetchCampaigns" class="flex items-center bg-white p-2 rounded-2xl shadow-2xl">
            <i class="pi pi-search text-slate-400 ml-3 text-base"></i>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari inovasi, robot, perpustakaan..."
              class="w-full px-3 py-2 text-slate-800 text-sm focus:outline-none"
            />
            <button
              v-if="searchQuery"
              type="button"
              @click="searchQuery = ''; fetchCampaigns()"
              class="text-slate-400 hover:text-slate-600 p-2 text-xs"
            >
              <i class="pi pi-times"></i>
            </button>
            <button
              type="submit"
              class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-orange-600/30 flex-shrink-0"
            >
              Cari
            </button>
          </form>
        </div>
      </div>
    </section>

    <!-- Main Content Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
      
      <!-- Category Tabs & Sort Filter Controls -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        
        <!-- Category Filter Pills -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-none">
          <button
            @click="selectedCategory = null"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap"
            :class="selectedCategory === null ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
          >
            Semua Kategori
          </button>
          <button
            v-for="cat in campaignStore.categories"
            :key="cat.id"
            @click="selectedCategory = cat.id"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap"
            :class="selectedCategory === cat.id ? 'bg-orange-600 text-white shadow-md shadow-orange-600/20' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
          >
            {{ cat.name }}
          </button>
        </div>

        <!-- Sort Filter Dropdown -->
        <div class="flex items-center gap-2 self-end md:self-auto">
          <span class="text-xs font-bold text-slate-400">Urutkan:</span>
          <select
            v-model="selectedSort"
            class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500"
          >
            <option value="latest">Terbaru</option>
            <option value="popular">Terpopuler (Backer Terbanyak)</option>
          </select>
        </div>

      </div>

      <!-- Campaign Grid -->
      <div v-if="campaignStore.isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="n in 6" :key="n" class="bg-white rounded-3xl h-80 animate-pulse border border-slate-100 p-5 space-y-4">
          <div class="bg-slate-200 h-44 rounded-2xl"></div>
          <div class="bg-slate-200 h-4 w-3/4 rounded"></div>
          <div class="bg-slate-200 h-3 w-1/2 rounded"></div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="!campaignStore.campaigns.length"
        class="text-center py-20 bg-white rounded-3xl border border-slate-200/80 p-8 space-y-3"
      >
        <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto text-2xl">
          <i class="pi pi-folder-open"></i>
        </div>
        <h3 class="font-black text-slate-800 text-lg">Belum Ada Kampanye</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
          Tidak ada kampanye aktif yang cocok dengan filter atau kata kunci pencarian Anda.
        </p>
      </div>

      <!-- Grid Cards -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <CampaignCard
          v-for="item in campaignStore.campaigns"
          :key="item.id"
          :campaign="item"
        />
      </div>

    </section>

  </div>
</template>
