import api from './api'

export const campaignService = {
  // Public
  getCategories: () => api.get('/categories'),
  getAll: (params) => api.get('/campaigns', { params }),
  getOne: (idOrSlug) => api.get(`/campaigns/${idOrSlug}`),
  getTiers: (idOrSlug) => api.get(`/campaigns/${idOrSlug}/tiers`),
  getUpdates: (idOrSlug) => api.get(`/campaigns/${idOrSlug}/updates`),

  // Creator Actions
  create: (formDataOrJson) => api.post('/campaigns', formDataOrJson, {
    headers: formDataOrJson instanceof FormData ? { 'Content-Type': 'multipart/form-data' } : {},
  }),
  update: (id, data) => api.put(`/campaigns/${id}`, data),
  delete: (id) => api.delete(`/campaigns/${id}`),
  uploadImages: (id, formData) => api.post(`/campaigns/${id}/images`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  deleteImage: (campaignId, imageId) => api.delete(`/campaigns/${campaignId}/images/${imageId}`),
  setPrimaryImage: (campaignId, imageId) => api.patch(`/campaigns/${campaignId}/images/${imageId}/primary`),
  submitReview: (id) => api.post(`/campaigns/${id}/submit-review`),
  postUpdate: (id, data) => api.post(`/campaigns/${id}/updates`, data),

  // Tier Management
  addTier: (campaignId, data) => api.post(`/campaigns/${campaignId}/tiers`, data),
  updateTier: (campaignId, tierId, data) => api.put(`/campaigns/${campaignId}/tiers/${tierId}`, data),
  deleteTier: (campaignId, tierId) => api.delete(`/campaigns/${campaignId}/tiers/${tierId}`),

  // Dashboards
  getCreatorDashboard: () => api.get('/dashboard/creator'),
  getBackerDashboard: () => api.get('/dashboard/backer'),
}
