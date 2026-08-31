<script setup>
import { ref, onMounted } from 'vue'
import { adminService } from '@/services/adminService'
import { formatDate } from '@/utils/formatDate'
import { formatCurrency } from '@/utils/formatCurrency'
import { useToast } from 'vue-toastification'

const toast = useToast()

const users = ref([])
const isLoading = ref(true)
const searchQuery = ref('')
const selectedRole = ref('')
const selectedSuspended = ref('')

// User Detail Modal State
const isDetailModalOpen = ref(false)
const selectedUser = ref(null)

async function loadUsers() {
  isLoading.value = true
  try {
    const params = {}
    if (searchQuery.value) params.search = searchQuery.value
    if (selectedRole.value) params.role = selectedRole.value
    if (selectedSuspended.value !== '') params.is_suspended = selectedSuspended.value

    const res = await adminService.getUsers(params)
    users.value = res.data.data
  } catch (error) {
    toast.error('Gagal memuat data pengguna.')
  } finally {
    isLoading.value = false
  }
}

async function handleToggleSuspend(u) {
  const actionText = u.is_suspended ? 'mengaktifkan kembali' : 'menangguhkan (suspend)'
  if (!confirm(`Apakah Anda yakin ingin ${actionText} akun ${u.name}?`)) return

  try {
    const res = await adminService.toggleSuspendUser(u.id)
    toast.success(res.data.message)
    await loadUsers()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal mengubah status penangguhan akun.')
  }
}

async function openUserDetail(u) {
  try {
    const res = await adminService.getUserDetail(u.id)
    selectedUser.value = res.data
    isDetailModalOpen.value = true
  } catch (error) {
    toast.error('Gagal memuat detail user.')
  }
}

onMounted(() => {
  loadUsers()
})
</script>

<template>
  <div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Manajemen Pengguna</h2>
        <p class="text-xs text-slate-500 mt-1">Daftar seluruh akun terdaftar, filter peranan, dan kontrol penangguhan akses (suspend)</p>
      </div>

      <!-- Filters & Search Bar -->
      <div class="flex flex-wrap items-center gap-2 text-xs">
        <input
          v-model="searchQuery"
          @input="loadUsers"
          type="text"
          placeholder="Cari nama atau email..."
          class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500 shadow-sm"
        />

        <select
          v-model="selectedRole"
          @change="loadUsers"
          class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-700 focus:outline-none shadow-sm"
        >
          <option value="">Semua Peran</option>
          <option value="backer">Backer</option>
          <option value="creator">Creator</option>
          <option value="admin">Admin</option>
        </select>

        <select
          v-model="selectedSuspended"
          @change="loadUsers"
          class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-700 focus:outline-none shadow-sm"
        >
          <option value="">Semua Status Akun</option>
          <option value="false">Aktif Normal</option>
          <option value="true">Ditangguhkan (Suspended)</option>
        </select>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
            <tr>
              <th class="px-6 py-4">Pengguna</th>
              <th class="px-6 py-4">Peran (Role)</th>
              <th class="px-6 py-4">Saldo Virtual</th>
              <th class="px-6 py-4">Status Akun</th>
              <th class="px-6 py-4">Terdaftar Sejak</th>
              <th class="px-6 py-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-if="!users.length">
              <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                Tidak ada pengguna yang sesuai dengan filter pencarian.
              </td>
            </tr>
            <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-xl bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-xs">
                    {{ u.name?.charAt(0)?.toUpperCase() || 'U' }}
                  </div>
                  <div>
                    <p class="font-bold text-slate-900 text-sm">{{ u.name }}</p>
                    <p class="text-[11px] text-slate-400">{{ u.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span
                  class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase"
                  :class="{
                    'bg-orange-100 text-orange-800 border border-orange-200': u.role === 'creator',
                    'bg-slate-100 text-slate-700 border border-slate-200': u.role === 'backer',
                    'bg-amber-100 text-amber-800 border border-amber-200': u.role === 'admin',
                  }"
                >
                  {{ u.role }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span class="font-bold text-slate-900">{{ formatCurrency(u.wallet_balance || 0) }}</span>
              </td>
              <td class="px-6 py-4">
                <span
                  v-if="u.is_suspended"
                  class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200 uppercase"
                >
                  Suspended
                </span>
                <span
                  v-else
                  class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase"
                >
                  Aktif
                </span>
              </td>
              <td class="px-6 py-4 text-slate-500">
                {{ formatDate(u.created_at) }}
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button
                  @click="openUserDetail(u)"
                  class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-colors"
                >
                  Detail
                </button>
                <button
                  v-if="u.role !== 'admin'"
                  @click="handleToggleSuspend(u)"
                  class="px-3.5 py-1.5 font-bold rounded-xl transition-colors"
                  :class="u.is_suspended ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200'"
                >
                  {{ u.is_suspended ? 'Buka Suspend' : 'Suspend' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: User Detail -->
    <div
      v-if="isDetailModalOpen && selectedUser"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
    >
      <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-2xl w-full border border-slate-200 shadow-2xl space-y-6 animate-in fade-in zoom-in-95 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-black text-slate-900">Detail Pengguna: {{ selectedUser.name }}</h3>
            <p class="text-xs text-slate-500">{{ selectedUser.email }} • Role: {{ selectedUser.role }}</p>
          </div>
          <button
            @click="isDetailModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center text-xs"
          >
            <i class="pi pi-times"></i>
          </button>
        </div>

        <!-- Kampanye Dibuat -->
        <div class="space-y-3">
          <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Kampanye Dibuat ({{ selectedUser.campaigns?.length || 0 }})</h4>
          <div v-if="!selectedUser.campaigns?.length" class="text-xs text-slate-400 p-3 bg-slate-50 rounded-xl">
            Belum pernah membuat kampanye.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="c in selectedUser.campaigns"
              :key="c.id"
              class="p-3 bg-slate-50 rounded-xl flex items-center justify-between text-xs border border-slate-100"
            >
              <div>
                <p class="font-bold text-slate-800">{{ c.title }}</p>
                <p class="text-[10px] text-slate-400">Target: {{ formatCurrency(c.target_amount) }}</p>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-200 text-slate-700">{{ c.status }}</span>
            </div>
          </div>
        </div>

        <!-- Riwayat Donasi -->
        <div class="space-y-3">
          <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Riwayat Donasi / Backing ({{ selectedUser.backings?.length || 0 }})</h4>
          <div v-if="!selectedUser.backings?.length" class="text-xs text-slate-400 p-3 bg-slate-50 rounded-xl">
            Belum pernah mendanai kampanye.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="b in selectedUser.backings"
              :key="b.id"
              class="p-3 bg-slate-50 rounded-xl flex items-center justify-between text-xs border border-slate-100"
            >
              <div>
                <p class="font-bold text-slate-800">{{ b.campaign?.title || 'Proyek' }}</p>
                <p class="text-[10px] text-slate-400">{{ formatDate(b.created_at) }}</p>
              </div>
              <span class="font-black text-orange-600">{{ formatCurrency(b.amount) }}</span>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-100">
          <button
            @click="isDetailModalOpen = false"
            class="px-5 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs shadow-md"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
