<template>
  <footer class="bg-white border-t border-slate-200 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        
        <!-- Brand Info -->
        <div class="space-y-3 md:col-span-2">
          <div class="flex items-center gap-1">
            <span class="text-xl font-black tracking-tight text-slate-900">Co<span class="text-orange-600">Fund</span></span>
          </div>
          <p class="text-sm text-slate-500 max-w-sm">
            Platform crowdfunding karya lokal Indonesia. Wujudkan inovasi teknologi, karya seni, pendidikan, dan aksi sosial bersama ribuan donatur terpercaya.
          </p>
        </div>

        <!-- Quick Links -->
        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Platform</h4>
          <ul class="space-y-2 text-sm text-slate-600">
            <li><router-link to="/" class="hover:text-orange-600 transition-colors">Jelajah Kampanye</router-link></li>
            <li><router-link to="/dashboard/campaigns/create" class="hover:text-orange-600 transition-colors">Galang Dana</router-link></li>
            <li><router-link to="/dashboard/backer" class="hover:text-orange-600 transition-colors">Dashboard Backer</router-link></li>
          </ul>
        </div>

        <!-- Contact / Info -->
        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Keamanan</h4>
          <ul class="space-y-2 text-sm text-slate-600">
            <li class="flex items-center gap-1.5"><i class="pi pi-shield text-orange-600 text-xs"></i> Virtual Escrow 100%</li>
            <li class="flex items-center gap-1.5"><i class="pi pi-check-circle text-orange-600 text-xs"></i> Refund Otomatis</li>
            <li class="flex items-center gap-1.5"><i class="pi pi-lock text-orange-600 text-xs"></i> Transaksi Aman</li>
          </ul>
        </div>

      </div>

      <div class="border-t border-slate-100 mt-8 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400">
        <p>© 2026 CoFund Crowdfunding. Dilindungi hak cipta.</p>
      </div>
    </div>
  </footer>
</template>
