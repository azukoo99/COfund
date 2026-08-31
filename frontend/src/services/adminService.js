import api from './api'

export const adminService = {
  getOverview: () => api.get('/admin/overview'),
  getCampaigns: (params) => api.get('/admin/campaigns', { params }),
  getCampaignDetail: (id) => api.get(`/admin/campaigns/${id}`),
  approveCampaign: (id) => api.post(`/admin/campaigns/${id}/approve`),
  rejectCampaign: (id, data) => api.post(`/admin/campaigns/${id}/reject`, data),
  forceFailCampaign: (id, data) => api.post(`/admin/campaigns/${id}/force-fail`, data),
  getUsers: (params) => api.get('/admin/users', { params }),
  getUserDetail: (id) => api.get(`/admin/users/${id}`),
  toggleSuspendUser: (id) => api.patch(`/admin/users/${id}/suspend`),
}
