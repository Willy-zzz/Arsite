// resources/js/app.js
import '../css/app.css'
import { createApp } from 'vue'
import AppLayout from './layouts/PublicLayout.vue'
import router from './router'
import Carousel from './components/Carousel.vue'
import { silenceBrowserConsole } from './utils/logger'


// Crea la aplicación Vue
silenceBrowserConsole()
const app = createApp(AppLayout)
app.component('Carousel', Carousel)               
app.use(router)
app.mount('#app')
