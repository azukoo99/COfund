<script setup>
import { computed } from 'vue'
import { formatCurrency } from '@/utils/formatCurrency'

const props = defineProps({
  tier: {
    type: Object,
    required: true,
  },
  isSelectable: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['select'])

const isSoldOut = computed(() => {
  return props.tier.quota > 0 && props.tier.remaining_quota <= 0
})
</script>

<template>
  <div
    class="bg-white rounded-2xl border p-5 transition-all relative overflow-hidden flex flex-col justify-between"
    :class="[
      isSoldOut ? 'border-slate-200 opacity-60 bg-slate-50' : 'border-orange-200/80 hover:border-orange-500 hover:shadow-lg',
    ]"
  >
    <!-- Sold Out Banner -->
    <div
      v-if="isSoldOut"
      class="absolute top-3 right-3 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-700"
    >
      Habis Terjual
    </div>

    <div>
      <div class="flex items-baseline justify-between mb-2">
        <h4 class="font-black text-base text-slate-900">{{ tier.name }}</h4>
      </div>

      <div class="mb-3">
        <span class="text-xs text-slate-400 font-semibold block">Mulai dari</span>
        <span class="text-xl font-black text-orange-600">
          {{ formatCurrency(tier.min_amount) }}
        </span>
      </div>

      <p class="text-xs text-slate-600 leading-relaxed mb-4">
        {{ tier.reward_description || 'Dukungan untuk kesuksesan proyek ini.' }}
      </p>
    </div>

    <div class="pt-4 border-t border-slate-100 space-y-3">
      <!-- Quota Info -->
      <div class="flex items-center justify-between text-[11px] text-slate-500">
        <span>Sisa Kuota:</span>
        <span class="font-bold text-slate-800">
          {{ tier.quota === 0 ? 'Tak Terbatas' : `${tier.remaining_quota} dari ${tier.quota} slot` }}
        </span>
      </div>

      <!-- Select Button -->
      <button
        v-if="isSelectable"
        @click="emit('select', tier)"
        :disabled="isSoldOut"
        class="w-full py-2.5 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5"
        :class="[
          isSoldOut
            ? 'bg-slate-200 text-slate-400 cursor-not-allowed'
            : 'bg-orange-600 hover:bg-orange-700 text-white shadow-md shadow-orange-600/20'
        ]"
      >
        <span>{{ isSoldOut ? 'Slot Habis' : 'Pilih Reward Ini' }}</span>
        <i v-if="!isSoldOut" class="pi pi-chevron-right text-[10px]"></i>
      </button>
    </div>

  </div>
</template>
