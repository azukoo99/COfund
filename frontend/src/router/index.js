import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'

// Layouts
import MainLayout from '@/layouts/MainLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

// Public & Auth Views
import HomeView from '@/views/public/HomeView.vue'
import CampaignDetailView from '@/views/public/CampaignDetailView.vue'
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'
import ForgotPasswordView from '@/views/auth/ForgotPasswordView.vue'
import ResetPasswordView from '@/views/auth/ResetPasswordView.vue'

// Creator & Backer Views
import CreatorDashboardView from '@/views/creator/CreatorDashboardView.vue'
import CreateCampaignView from '@/views/creator/CreateCampaignView.vue'
import EditCampaignView from '@/views/creator/EditCampaignView.vue'
import BackerDashboardView from '@/views/backer/BackerDashboardView.vue'
import WalletView from '@/views/backer/WalletView.vue'
import NotificationsView from '@/views/common/NotificationsView.vue'

// Admin Views
import AdminOverviewView from '@/views/admin/AdminOverviewView.vue'
import AdminApprovalQueueView from '@/views/admin/AdminApprovalQueueView.vue'
import AdminUserManagementView from '@/views/admin/AdminUserManagementView.vue'

const routes = [
  // Public Routes (MainLayout)
  {
    path: '/',
    component: MainLayout,
    children: [
      {
        path: '',
        name: 'home',
        component: HomeView,
      },
      {
        path: 'campaigns/:slug',
        name: 'campaign.detail',
        component: CampaignDetailView,
      },
    ],
  },

  // Auth Routes (AuthLayout - Guest Only)
  {
    path: '/',
    component: AuthLayout,
    meta: { guestOnly: true },
    children: [
      {
        path: 'login',
        name: 'login',
        component: LoginView,
      },
      {
        path: 'register',
        name: 'register',
        component: RegisterView,
      },
      {
        path: 'forgot-password',
        name: 'forgot-password',
        component: ForgotPasswordView,
      },
      {
        path: 'reset-password',
        name: 'reset-password',
        component: ResetPasswordView,
      },
    ],
  },

  // User & Creator Dashboard Routes (DashboardLayout)
  {
    path: '/dashboard',
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: 'creator',
        name: 'creator.dashboard',
        component: CreatorDashboardView,
        meta: { requiresCreator: true },
      },
      {
        path: 'backer',
        name: 'backer.dashboard',
        component: BackerDashboardView,
      },
      {
        path: 'campaigns/create',
        name: 'campaign.create',
        component: CreateCampaignView,
        meta: { requiresCreator: true },
      },
      {
        path: 'campaigns/:id/edit',
        name: 'campaign.edit',
        component: EditCampaignView,
        meta: { requiresCreator: true },
      },
      {
        path: 'wallet',
        name: 'wallet',
        component: WalletView,
      },
      {
        path: 'notifications',
        name: 'notifications',
        component: NotificationsView,
      },
    ],
  },

  // Admin Routes (AdminLayout)
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: '',
        redirect: { name: 'admin.overview' },
      },
      {
        path: 'overview',
        name: 'admin.overview',
        component: AdminOverviewView,
      },
      {
        path: 'campaigns',
        name: 'admin.campaigns',
        component: AdminApprovalQueueView,
      },
      {
        path: 'users',
        name: 'admin.users',
        component: AdminUserManagementView,
      },
    ],
  },

  // Fallback Catch All
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior() {
    return { top: 0, behavior: 'smooth' }
  },
})

// Navigation Guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return next({ name: 'home' })
  }

  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    return next({ name: 'home' })
  }

  if (to.meta.requiresCreator && !authStore.isCreator) {
    return next({ name: 'backer.dashboard' })
  }

  next()
})

export default router
