<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const authStore = useAuthStore()
const router = useRouter()

const isMobileMenuOpen = ref(false)
const showUserMenu = ref(false)

const showNotifications = ref(false)
const notificationCount = ref(0)
const recentContacts = ref([])
const loadingNotifications = ref(false)
let notificationInterval = null
let authCheckInterval = null

const fetchNotifications = async () => {
	try {
		loadingNotifications.value = true
		const response = await api.get('/contactos', {
			params: { estado: 'Nuevo', per_page: 5, sort_by: 'created_at', sort_direction: 'desc' },
		})
		if (response.data.success) {
			recentContacts.value = response.data.data.data
			notificationCount.value = response.data.data.total
		}
	} catch (err) {
		console.error('Error al cargar notificaciones:', err)
	} finally {
		loadingNotifications.value = false
	}
}

const toggleNotifications = () => {
	showNotifications.value = !showNotifications.value
	if (showNotifications.value) {
		showUserMenu.value = false
		fetchNotifications()
	}
}

const goToContacto = () => {
	showNotifications.value = false
	router.push('/contact')
}

const formatTimeAgo = (dateString) => {
	if (!dateString) return ''
	const diff = Math.floor((Date.now() - new Date(dateString)) / 1000)
	if (diff < 60) return 'Hace un momento'
	if (diff < 3600) return `Hace ${Math.floor(diff / 60)} min`
	if (diff < 86400) return `Hace ${Math.floor(diff / 3600)} h`
	return `Hace ${Math.floor(diff / 86400)} d`
}

const toggleMobileMenu = () => {
	isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
	isMobileMenuOpen.value = false
}

const toggleUserMenu = () => {
	showUserMenu.value = !showUserMenu.value
	if (showUserMenu.value) showNotifications.value = false
}

const closeUserMenu = () => {
	showUserMenu.value = false
}

// Computed para el avatar del usuario
const userAvatar = computed(() => {
	if (!authStore.user?.avatar) return null
	return authStore.user.avatar
})

// Computed para las iniciales del usuario
const userInitials = computed(() => {
	return authStore.userInitials || authStore.user?.iniciales || '??'
})

// Computed para el color de fondo según el rol
const avatarBgColor = computed(() => {
	return authStore.isAdmin ? 'bg-amber-500' : 'bg-indigo-600'
})

// Cerrar menú al hacer clic fuera
const handleClickOutside = (event) => {
	if (showUserMenu.value && !event.target.closest('.user-menu-container')) {
		closeUserMenu()
	}
	if (showNotifications.value && !event.target.closest('.notifications-container')) {
		showNotifications.value = false
	}
}

onMounted(() => {
	document.addEventListener('click', handleClickOutside)
	fetchNotifications()
	notificationInterval = setInterval(fetchNotifications, 150000)
	authCheckInterval = setInterval(checkAuthValidity, 15000)
})

onUnmounted(() => {
	document.removeEventListener('click', handleClickOutside)
	if (notificationInterval) clearInterval(notificationInterval)
	if (authCheckInterval) clearInterval(authCheckInterval)
})

const checkAuthValidity = async () => {
	try {
		await api.get('/check')
	} catch (err) {
		if (err.response?.status === 401) {
			clearInterval(authCheckInterval)
			clearInterval(notificationInterval)
			authStore.clearAuth()
			router.push('/login')
		}
	}
}
// Navegación
const goToProfile = () => {
	closeUserMenu()
	router.push('/perfil')
}

const handleLogout = async () => {
	closeUserMenu()
	await authStore.logout()
}
</script>

<template>
	<div class="min-h-screen bg-[#f4f6f8] font-['Inter'] antialiased">
		<!-- Overlay para mobile -->
		<div
			v-if="isMobileMenuOpen"
			@click="closeMobileMenu"
			class="fixed inset-0 z-40 bg-black/50 md:hidden transition-opacity"
		></div>

		<!-- Sidebar Navigation -->
		<nav
			:class="[
				'fixed top-0 left-0 z-50 flex h-screen w-[280px] flex-col bg-[#312AFF] pt-[25px] transition-transform duration-300',
				{ '-translate-x-full': !isMobileMenuOpen, 'translate-x-0': isMobileMenuOpen },
				'md:!translate-x-0',
			]"
		>
			<!-- Logo y título -->
			<div class="flex items-center gap-[12px] px-[25px] pb-[25px]">
				<div
					class="h-[42px] w-[42px] rounded-lg bg-white/10 flex items-center justify-center border border-white/10"
				>
					<img
						src="@/assets/vector_ArSite.svg"
						alt="Logo de Ar-Site Integradores"
						class="h-[30px] w-[30px] object-contain brightness-0 invert"
					/>
				</div>
				<h1 class="text-[17px] font-bold leading-[1.4] text-white tracking-tight">
					Ar-Site Integradores
				</h1>
				<!-- Botón cerrar en mobile -->
				<button
					@click="closeMobileMenu"
					class="ml-auto md:hidden p-1 hover:bg-white/10 rounded-lg transition-colors"
				>
					<svg
						class="h-6 w-6 text-white"
						fill="none"
						viewBox="0 0 24 24"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M6 18L18 6M6 6l12 12"
						/>
					</svg>
				</button>
			</div>

			<!-- Menú -->
			<div class="mt-[30px] px-5">
				<div class="mx-auto h-[1px] w-[calc(100%-10px)] bg-white/20 mb-[10px]"></div>
				<router-link
					to="/dashboard"
					class="flex items-center gap-[15px] rounded-lg p-[12px_15px] text-white transition-all hover:bg-white/10"
					active-class="bg-white/20"
					@click="closeMobileMenu"
				>
					<svg
						class="h-6 w-6 text-white opacity-90 group-hover:opacity-100"
						fill="none"
						viewBox="0 0 24 24"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
						/>
					</svg>
					<span class="text-base font-medium text-white">Dashboard</span>
				</router-link>
				<div class="mx-auto h-[1px] w-[calc(100%-10px)] bg-white/20 mt-[10px]"></div>

				<!-- Usuarios (solo para administradores) -->
				<router-link
					v-if="authStore.isAdmin"
					to="/users"
					class="flex items-center gap-[15px] rounded-lg p-[12px_15px] text-white transition-all hover:bg-white/10 mt-[10px]"
					active-class="bg-white/20"
					@click="closeMobileMenu"
				>
					<svg
						class="h-6 w-6 text-white opacity-90 group-hover:opacity-100"
						fill="none"
						viewBox="0 0 24 24"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
						/>
					</svg>
					<span class="text-base font-medium text-white">Usuarios</span>
				</router-link>

				<div class="mx-auto h-[1px] w-[calc(100%-10px)] bg-white/20 mt-[10px]"></div>

				<!-- Contacto (bandeja de mensajes) -->
				<router-link
					to="/contact"
					class="flex items-center gap-[15px] rounded-lg p-[12px_15px] text-white transition-all hover:bg-white/10 mt-[10px]"
					active-class="bg-white/20"
					@click="closeMobileMenu"
				>
					<svg
						class="h-6 w-6 text-white opacity-90"
						fill="none"
						viewBox="0 0 24 24"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
						/>
					</svg>
					<span class="text-base font-medium text-white">Contacto</span>
					<span
						v-if="notificationCount > 0"
						class="flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white"
					>
						{{ notificationCount > 99 ? '99+' : notificationCount }}
					</span>
				</router-link>
				<div class="mx-auto h-[1px] w-[calc(100%-10px)] bg-white/20 mt-[10px]"></div>
			</div>
		</nav>

		<!-- Main Content -->
		<main class="min-h-screen main-content overflow-y-auto">
			<!-- Header -->
			<header
				class="sticky top-0 z-30 flex h-[60px] md:h-[75px] items-center bg-white px-4 md:px-[30px] shadow-[0px_2px_10px_rgba(0,0,0,0.05)]"
			>
				<!-- Botón hamburguesa -->
				<button
					@click="toggleMobileMenu"
					class="md:hidden mr-3 p-2 hover:bg-gray-100 rounded-lg transition-colors"
				>
					<svg
						class="h-6 w-6 text-gray-600"
						fill="none"
						viewBox="0 0 24 24"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M4 6h16M4 12h16M4 18h16"
						/>
					</svg>
				</button>

				<h2
					class="flex-1 truncate pr-3 md:pr-5 text-[15px] md:text-[17px] font-semibold text-[#1c2321]"
				>
					Sistema de administración de contenido de Ar-Site Integradores
				</h2>

				<div class="flex items-center gap-2 md:gap-4">
					<!-- Campana de notificaciones -->
					<div class="relative notifications-container">
						<button
							@click="toggleNotifications"
							class="relative flex h-[40px] w-[40px] md:h-[50px] md:w-[50px] items-center justify-center rounded-lg bg-indigo-100 hover:bg-indigo-300 transition-colors"
							title="Notificaciones"
						>
							<svg
								class="h-5 w-5 text-gray-600"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
								/>
							</svg>
							<span
								v-if="notificationCount > 0"
								class="absolute -top-0.5 -right-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
							>
								{{ notificationCount > 99 ? '99+' : notificationCount }}
							</span>
						</button>

						<!-- Panel desplegable -->
						<transition name="dropdown">
							<div
								v-if="showNotifications"
								class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden z-50"
							>
								<div
									class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200"
								>
									<div class="flex items-center gap-2">
										<span class="text-sm font-semibold text-gray-800"
											>Mensajes nuevos</span
										>
										<span
											v-if="notificationCount > 0"
											class="flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-100 px-1.5 text-[11px] font-bold text-red-600"
										>
											{{ notificationCount }}
										</span>
									</div>
									<button
										@click="goToContacto"
										class="text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors"
									>
										Ver todos
									</button>
								</div>

								<div class="max-h-[320px] overflow-y-auto">
									<div
										v-if="loadingNotifications"
										class="flex justify-center items-center py-8"
									>
										<div
											class="animate-spin rounded-full h-6 w-6 border-2 border-indigo-600 border-t-transparent"
										></div>
									</div>

									<div
										v-else-if="recentContacts.length === 0"
										class="flex flex-col items-center justify-center py-10 text-gray-400"
									>
										<svg
											class="h-10 w-10 mb-2 opacity-40"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="1.5"
												d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
											/>
										</svg>
										<p class="text-sm">Sin mensajes nuevos</p>
									</div>

									<div
										v-else
										v-for="contacto in recentContacts"
										:key="contacto.con_id"
										@click="goToContacto"
										class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors"
									>
										<div
											class="flex-shrink-0 h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mt-0.5"
										>
											<svg
												class="h-4 w-4 text-indigo-600"
												fill="none"
												viewBox="0 0 24 24"
												stroke="currentColor"
											>
												<path
													stroke-linecap="round"
													stroke-linejoin="round"
													stroke-width="2"
													d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
												/>
											</svg>
										</div>
										<div class="flex-1 min-w-0">
											<p class="text-sm font-semibold text-gray-900 truncate">
												{{ contacto.con_nombre }}
											</p>
											<p class="text-xs text-gray-500 truncate">
												{{ contacto.con_asunto }}
											</p>
											<p class="text-[11px] text-gray-400 mt-0.5">
												{{ formatTimeAgo(contacto.created_at) }}
											</p>
										</div>
										<div
											class="flex-shrink-0 h-2 w-2 rounded-full bg-red-500 mt-1.5"
										></div>
									</div>
								</div>

								<div
									v-if="recentContacts.length > 0"
									class="px-4 py-2.5 bg-gray-50 border-t border-gray-200"
								>
									<button
										@click="goToContacto"
										class="w-full text-center text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors py-0.5"
									>
										Ir a la bandeja de contacto →
									</button>
								</div>
							</div>
						</transition>
					</div>

					<div class="hidden md:block h-7 w-[1.5px] bg-[#cccccc] mx-[18px]"></div>
					<span class="hidden md:inline text-[15px] font-medium text-[#5f5f5f] mr-[12px]">
						{{ authStore.userRole }}
					</span>

					<!-- Avatar con menú -->
					<div class="relative user-menu-container">
						<button
							@click="toggleUserMenu"
							class="h-[40px] w-[40px] md:h-[50px] md:w-[50px] overflow-hidden rounded-full bg-[#E0E0E0] flex items-center justify-center hover:ring-2 hover:ring-indigo-300 transition-all cursor-pointer"
						>
							<!-- Avatar con imagen -->
							<img
								v-if="
									userAvatar?.tipo === 'upload' || userAvatar?.tipo === 'preset'
								"
								:src="userAvatar.url"
								:alt="authStore.userName"
								class="h-full w-full object-cover"
							/>
							<!-- Avatar con iniciales -->
							<div
								v-else
								class="h-full w-full flex items-center justify-center text-white font-bold"
								:class="avatarBgColor"
							>
								<span class="text-sm md:text-base">{{ userInitials }}</span>
							</div>
						</button>

						<!-- Menú desplegable -->
						<transition name="dropdown">
							<div
								v-if="showUserMenu"
								class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50"
							>
								<!-- Info del usuario -->
								<div class="px-4 py-3 border-b border-gray-100">
									<p class="text-sm font-semibold text-gray-900">
										{{ authStore.userName }}
									</p>
									<p class="text-xs text-gray-500 truncate">
										{{ authStore.userEmail }}
									</p>
									<span
										class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full"
										:class="
											authStore.isAdmin
												? 'bg-amber-100 text-amber-700'
												: 'bg-indigo-100 text-indigo-700'
										"
									>
										{{ authStore.userRole }}
									</span>
								</div>

								<!-- Opciones del menú -->
								<button
									@click="goToProfile"
									class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
								>
									<svg
										class="h-4 w-4"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
										/>
									</svg>
									Mi Perfil
								</button>

								<button
									v-if="authStore.isAdmin"
									@click="
										() => {
											closeUserMenu()
											router.push('/users')
										}
									"
									class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
								>
									<svg
										class="h-4 w-4"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
										/>
									</svg>
									Usuarios
								</button>

								<div class="border-t border-gray-100 my-1"></div>

								<button
									@click="handleLogout"
									class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
								>
									<svg
										class="h-4 w-4"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
										/>
									</svg>
									Cerrar Sesión
								</button>
							</div>
						</transition>
					</div>
				</div>
			</header>

			<!-- Contenido dinámico de las vistas -->
			<div class="p-4 md:p-[30px]">
				<router-view />
			</div>
		</main>
	</div>
</template>

<style scoped>
.main-content {
	margin-left: 0;
	max-height: 100vh;
}

@media (min-width: 768px) {
	.main-content {
		margin-left: 280px;
	}
}

/* Transición del menú desplegable */
.dropdown-enter-active,
.dropdown-leave-active {
	transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
	opacity: 0;
	transform: translateY(-10px);
}
</style>
