import api from './api'

export const backingService = {
  backCampaign: (campaignId, data) => api.post(`/campaigns/${campaignId}/back`, data),
  getMyBackings: (params) => api.get('/my-backings', { params }),
}
