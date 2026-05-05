import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

// Layouts
import SoporteLayout from '@/layouts/SoporteLayout.vue'

// Páginas normales
import Home from '../pages/Home.vue'
import About from '../pages/About.vue'

import Services from '../pages/Services.vue'
import Partners from '../pages/Partners.vue'
import Clients from '../pages/Clients.vue'

import Contact from '../pages/Contact.vue'
import NotFound from '../pages/NotFound.vue'
import Terminos from '../pages/Terminos.vue'

// paginas productos
import Products from '../pages/Products.vue'
import CategoriaProductos from '../pages/CategoriaProductos.vue'

// Páginas de soporte
import LoginPage from '../pages/soporte/LoginPage.vue'
import HelpDeskPage from '../pages/soporte/HelpDeskPage.vue'
import RecuperarPage from '../pages/soporte/RecuperarPage.vue'
import RegistroPage from '../pages/soporte/RegistroPage.vue'
import CuentaPage from '../pages/soporte/CuentaPage.vue'

// //import EnConstruccion from '../pages/EnConstruccion.vue'

const routes = [
  { path: '/', component: Home },
  { path: '/nosotros', component: About },

  { path: '/productos', component: Products },
  { path: '/productos/:categoria', component: CategoriaProductos },

  { path: '/servicios', component: Services },
  { path: '/partners', component: Partners },
  { path: '/clientes', component: Clients },
  
  { path: '/contacto', component: Contact },
  { path: '/terminos', component: Terminos },

  //soporte
  { path: '/soporte',
    component: SoporteLayout,   // Layout con <router-view />
    // Redirección por defecto: si estás logueado vas a helpdesk, si no a login
    redirect: (to) => {
      const { isLoggedIn } = useAuth()
      return isLoggedIn.value ? '/soporte/helpdesk' : '/soporte/login'
    },
    children: [
      { path: 'login', name: 'soporte.login', component: LoginPage, meta: { guest: true } },
      { path: 'helpdesk', name: 'soporte.helpdesk', component: HelpDeskPage, meta: { requiresAuth: true } },
      { path: 'recuperar', component: RecuperarPage, meta: { guest: true } },
      { path: 'registro', component: RegistroPage, meta: { guest: true } },
      { path: 'cuenta', component: CuentaPage, meta: { requiresAuth: true } },
    ]
  },



  // 404 al final 
  { path: '/:pathMatch(.*)*', component: NotFound },
  
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    // Si hay una posición guardada (por ejemplo, al usar "atrás" del navegador)
    if (savedPosition) {
      return savedPosition
    } else {
      // Siempre ir al inicio de la página
      return { top: 0, left: 0, behavior: 'smooth' } // behavior smooth opcional
    }
  }
})

// GUARDIA DE NAVEGACIÓN GLOBAL
router.beforeEach((to, from, next) => {
  // Obtén el estado actual de autenticación
  const { isLoggedIn } = useAuth()
  const authenticated = isLoggedIn.value

  if (to.meta.requiresAuth && !authenticated) {
    next('/soporte/login')
  } else if (to.meta.guest && authenticated) {
    next('/soporte/helpdesk')
  } else {
    next()
  }
})

export default router