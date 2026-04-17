import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import logger from '@/utils/logger'
//import { compile } from 'vue'
//import { meta } from 'eslint-plugin-vue'
//import HomeView from '../views/HomeView.vue'

//Definir rutas
const routes = [
	//Rutas públicas
	{
		path: '/',
		component: () => import('@/layouts/AuthLayout.vue'),
		children: [
			{
				path: '',
				redirect: '/login',
			},
			{
				path: '/login',
				name: 'Login',
				component: () => import('@/views/Login.vue'),
				meta: { requiresAuth: false },
			},
			{
				path: '/register',
				name: 'Register',
				component: () => import('@/views/Register.vue'),
				meta: { requiresAuth: false, layout: 'auth' },
			},
		],
	},

	{
		path: '/setup-profile',
		name: 'SetupProfile',
		component: () => import('@/views/SetupProfile.vue'),
		meta: { requiresAuth: true },
	},

	//Rutas protegidas
	{
		path: '/',
		component: () => import('@/layouts/MainLayout.vue'),
		meta: { requiresAuth: true },
		children: [
			{
				path: '/dashboard',
				name: 'Dashboard',
				component: () => import('@/views/Dashboard.vue'),
				//meta: { requiresAuth: true },
			},
			{
				path: '/users',
				name: 'Users',
				component: () => import('@/views/UsersView.vue'),
				meta: { requiresAdmin: true },
			},
			{
				path: '/perfil',
				name: 'Perfil',
				component: () => import('@/views/Profile.vue'),
			},
			{
				path: '/banners',
				name: 'Banners',
				component: () => import('@/views/BannersView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/sliders',
				name: 'Sliders',
				component: () => import('@/views/SlidersView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/products',
				name: 'Products',
				component: () => import('@/views/ProductsView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/clients',
				name: 'Clients',
				component: () => import('@/views/ClientsView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/services',
				name: 'Services',
				component: () => import('@/views/ServicesView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/partners',
				name: 'Partners',
				component: () => import('@/views/PartnersView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/contact',
				name: 'Contact',
				component: () => import('@/views/ContactView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/news',
				name: 'News',
				component: () => import('@/views/NewsView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/news/create',
				name: 'NewsCreate',
				component: () => import('@/views/NewsCreateView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/news/:id/edit',
				name: 'NewsEdit',
				component: () => import('@/views/NewsEditView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/milestones',
				name: 'Milestones',
				component: () => import('@/views/MilestonesView.vue'),
				meta: { requiresAuth: true },
			},

			//Módulos de create
			{
				path: '/banners/create',
				name: 'BannersCreate',
				component: () => import('@/views/BannerCreateView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/sliders/create',
				name: 'SlidersCreate',
				component: () => import('@/views/SliderCreateView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/products/create',
				name: 'ProductsCreate',
				component: () => import('@/views/ProductCreateView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/services/create',
				name: 'ServicesCreate',
				component: () => import('@/views/ServiceCreateView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/partners/create',
				name: 'PartnersCreate',
				component: () => import('@/views/PartnerCreateView.vue'),
				meta: { requiresAuth: true },
			},
			{
				path: '/clients/create',
				name: 'ClientCreate',
				component: () => import('@/views/ClientCreateView.vue'),
				meta: { requiresAuth: true },
			},
		],
	},

	//404 - Página no encontrada
	{
		path: '/:pathMatch(.*)*',
		name: 'NotFound',
		component: () => import('@/views/NotFound.vue'),
	},
]

//Crear router
const router = createRouter({
	history: createWebHistory(import.meta.env.BASE_URL),
	routes,
})

//Router guard
router.beforeEach(async (to, from, next) => {
	const authStore = useAuthStore()

	logger.debug('Router guard activado')
	logger.debug('Ir desde:', from.path)
	logger.debug('Ir a:', to.path)

	//Inicializar autenticación si hay token guardado
	if (
		!authStore.user &&
		(localStorage.getItem('auth-token') || sessionStorage.getItem('auth-token'))
	) {
		//console.log(' Restaurando sesión desde localStorage...')
		try {
			await authStore.initAuth()
		} catch (err) {
			//console.error('Error al restaurar sesión:', err)
			authStore.clearAuth()
		}
	}

	//Redirigir usuarios autenticados que intenten ir al login
	if ((to.path === '/login' || to.path === '/register') && authStore.isAuthenticated) {
		//console.log('Ya autenticado, redirigir a dashboard')

		if (authStore.userStatus === 'Pendiente') {
			// Cerrar sesión y mostrar mensaje
			authStore.clearAuth()
			return next()
		}
		return next('/dashboard')
	}

	//Verificar autenticación para rutas protegidas
	if (to.meta.requiresAuth) {
		//console.log('Esta ruta requiere autenticación')

		if (!authStore.isAuthenticated) {
			//console.log('Usuario no autenticado, redirigir a login')
			return next({
				path: '/login',
				query: { redirect: to.fullPath },
			})
		}

		if (to.name === 'SetupProfile') {
			return next()
		}

		//Verificar si el usuario está pendiente y la ruta no permite pendientes
		if (authStore.userStatus === 'Pendiente') {
			logger.info('Usuario con estado Pendiente, redirigir a setup-profile')
			return next('/setup-profile')
		}

		//Verificar si requiere ser admin
		if (to.meta.requiresAdmin && !authStore.isAdmin) {
			// console.log('Usuario no es Admin, redirigir a dashboard')
			return next('/dashboard')
		}
	}
	next()
})

export default router
