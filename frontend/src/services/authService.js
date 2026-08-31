import api from './api'

export const authService = {
  login: (credentials) => api.post('/login', credentials),
  register: (data) => api.post('/register', data),
  logout: () => api.post('/logout'),
  getMe: () => api.get('/me'),
  upgradeToCreator: () => api.post('/me/upgrade-creator'),
  getBalance: (params) => api.get('/me/balance', { params }),
  withdraw: (data) => api.post('/me/withdraw', data),
  forgotPassword: (data) => api.post('/forgot-password', data),
  resetPassword: (data) => api.post('/reset-password', data),
}
