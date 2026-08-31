import { createApp } from 'vue'
import { createPinia } from 'pinia'

// PrimeVue UI & Themes
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import ToastService from 'primevue/toastservice'
import DialogService from 'primevue/dialogservice'
import ConfirmationService from 'primevue/confirmationservice'

// Toastification
import Toast, { POSITION } from 'vue-toastification'

// App & Styling
import App from './App.vue'
import router from './router'
import './assets/main.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Setup PrimeVue dengan preset Aura
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: false,
      cssLayer: false,
    },
  },
})
app.use(ToastService)
app.use(DialogService)
app.use(ConfirmationService)

// Setup Toastification
app.use(Toast, {
  position: POSITION.TOP_RIGHT,
  timeout: 3500,
  closeOnClick: true,
  pauseOnHover: true,
  draggable: true,
})

app.mount('#app')
