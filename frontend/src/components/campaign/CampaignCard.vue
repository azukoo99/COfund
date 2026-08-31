<script setup>
import { computed } from 'vue'
import { formatCurrency } from '@/utils/formatCurrency'
import { formatImageUrl } from '@/utils/formatImage'

const props = defineProps({
  campaign: {
    type: Object,
    required: true,
  },
})

const primaryImage = computed(() => {
  const img = props.campaign.images?.find(i => i.is_primary) || props.campaign.images?.[0]
  return formatImageUrl(img?.url)
})

const percentage = computed(() => {
  if (!props.campaign.target_amount || props.campaign.target_amount <= 0) return 0
  return Math.min(100, Math.round((props.campaign.collected_amount / props.campaign.target_amount) * 100))
})
</script>

<template>
  <div class="bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col group">
    
    <!-- Thumbnail Image -->
    <div class="relative h-48 sm:h-52 w-full overflow-hidden bg-slate-100">
      <img
        :src="primaryImage"
        :alt="campaign.title"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
        loading="lazy"
      />
      <!-- Category Badge -->
      <span class="absolute top-3 left-3 px-3 py-1 bg-white/90 backdrop-blur-md rounded-full text-[11px] font-bold text-orange-700 shadow-sm">
        {{ campaign.category?.name || 'Inovasi' }}
      </span>

      <!-- Status Badge if not active -->
      <span
        v-if="campaign.status !== 'active'"
        class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase shadow-sm"
        :class="{
          'bg-amber-100 text-amber-800': campaign.status === 'review',
          'bg-slate-100 text-slate-800': campaign.status === 'draft',
          'bg-emerald-100 text-emerald-800': campaign.status === 'success',
          'bg-rose-100 text-rose-800': campaign.status === 'failed',
        }"
      >
        {{ campaign.status }}
      </span>
    </div>

    <!-- Card Content -->
    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base leading-snug line-clamp-2 group-hover:text-orange-600 transition-colors">
          <router-link :to="{ name: 'campaign.detail', params: { slug: campaign.slug } }">
            {{ campaign.title }}
          </router-link>
        </h3>
        <p class="text-xs text-slate-500 mt-1 line-clamp-2">
          {{ campaign.description }}
        </p>
      </div>

      <!-- Funding Stats & Progress -->
      <div class="space-y-2.5 pt-2 border-t border-slate-100">
        <!-- Progress Bar -->
        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
          <div
            class="bg-gradient-to-r from-orange-500 to-amber-400 h-full rounded-full transition-all duration-500"
            :style="{ width: `${percentage}%` }"
          ></div>
        </div>

        <div class="flex items-center justify-between text-xs">
          <div>
            <span class="font-black text-slate-900 text-sm block">
              {{ formatCurrency(campaign.collected_amount) }}
            </span>
            <span class="text-slate-400 text-[11px]">
              dari {{ formatCurrency(campaign.target_amount) }}
            </span>
          </div>

          <div class="text-right">
            <span class="font-extrabold text-orange-600 text-sm block">
              {{ percentage }}%
            </span>
            <span class="text-slate-400 text-[11px]">
              {{ campaign.backings_count || 0 }} Donatur
            </span>
          </div>
        </div>
      </div>

      <!-- Action Button -->
      <router-link
        :to="{ name: 'campaign.detail', params: { slug: campaign.slug } }"
        class="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-orange-600 text-slate-700 hover:text-white text-xs font-bold text-center transition-all flex items-center justify-center gap-1.5"
      >
        <span>Lihat Detail Proyek</span>
        <i class="pi pi-arrow-right text-[10px]"></i>
      </router-link>

    </div>

  </div>
</template>
