<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-4 sm:p-6 lg:p-8">
    <div class="w-full max-w-md">
      <!-- Logo / Brand Header (Clean Text Branding) -->
      <div class="text-center mb-8">
        <router-link to="/" class="inline-flex items-center gap-1 group">
          <span class="text-3xl font-black tracking-tight text-slate-900">Co<span class="text-orange-600">Fund</span></span>
        </router-link>
      </div>

      <!-- Auth Card -->
      <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl shadow-slate-200/60 border border-slate-100">
        <router-view />
      </div>

      <!-- Back to home -->
      <div class="text-center mt-6">
        <router-link to="/" class="text-xs font-semibold text-slate-400 hover:text-orange-600 transition-colors inline-flex items-center gap-1">
          <i class="pi pi-arrow-left text-[10px]"></i>
          Kembali ke Beranda
        </router-link>
      </div>
    </div>
  </div>
</template>
