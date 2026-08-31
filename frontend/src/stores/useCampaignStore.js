import { defineStore } from 'pinia'
import { ref } from 'vue'
import { campaignService } from '@/services/campaignService'

export const useCampaignStore = defineStore('campaign', () => {
  const campaigns = ref([])
  const currentCampaign = ref(null)
  const categories = ref([])
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0,
  })
  const isLoading = ref(false)

  async function fetchCategories() {
    try {
      const response = await campaignService.getCategories()
      categories.value = response.data
      return response.data
    } catch (error) {
      console.error('Failed to load categories', error)
      return []
    }
  }

  async function fetchCampaigns(params = {}) {
    isLoading.value = true
    try {
      const response = await campaignService.getAll(params)
      campaigns.value = response.data.data
      pagination.value = {
        currentPage: response.data.current_page,
        lastPage: response.data.last_page,
        total: response.data.total,
      }
      return response.data
    } finally {
      isLoading.value = false
    }
  }

  async function fetchCampaign(idOrSlug) {
    isLoading.value = true
    try {
      const response = await campaignService.getOne(idOrSlug)
      currentCampaign.value = response.data
      return response.data
    } finally {
      isLoading.value = false
    }
  }

  return {
    campaigns,
    currentCampaign,
    categories,
    pagination,
    isLoading,
    fetchCategories,
    fetchCampaigns,
    fetchCampaign,
  }
})
