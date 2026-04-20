<template>
	<div class="w-full max-w-md mx-auto">
		<!-- Tarjeta mejorada con fondo sutil y borde con gradiente -->
		<div
			class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-2xl overflow-hidden border border-white/20 relative">
			<!-- Efecto de gradiente decorativo en la esquina -->
			<div
				class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-100 rounded-full opacity-30 blur-3xl pointer-events-none">
			</div>
			<div
				class="absolute -bottom-24 -left-24 w-48 h-48 bg-purple-100 rounded-full opacity-30 blur-3xl pointer-events-none">
			</div>

			<div class="px-6 sm:px-8 py-8 sm:py-10 relative z-10">
				<div class="flex justify-center mb-4 sm:mb-6">
					<img src="@/assets/logo-arsite.png" alt="Ar-Site Integradores"
						class="h-18 sm:h-20 w-auto max-w-full mix-blend-multiply logo-animation" />
				</div>

				<div class="text-center mb-6">
					<h2
						class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent mb-2">
						Bienvenido</h2>
					<p class="text-sm text-gray-500 mt-12 pb-1">Inicia sesión para continuar</p>
				</div>

				<Transition name="slide-down">
					<div v-if="errorMessage"
						class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 flex justify-between items-start shadow-sm">
						<span class="text-red-800 ml-1 flex-1 break-words pr-2">{{
							errorMessage
						}}</span>

						<button type="button" @click="clearError"
							class="text-red-900/40 hover:text-red-900 transition-all p-1 hover:bg-red-100 hover:shadow-sm rounded-md flex-shrink-0 ml-2">
							<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
									d="M6 18L18 6M6 6l12 12" />
							</svg>
						</button>
					</div>
				</Transition>

				<Transition name="slide-down">
					<div v-if="logoutReason === 'inactivity'"
						class="mb-6 rounded-lg bg-amber-50 border border-amber-200 p-3 flex items-center shadow-sm">
						<span class="text-sm text-amber-800 ml-1 flex-1 break-words pr-2">Tu sesión se cerró por
							inactividad.</span>

						<button type="button" @click="clearLogoutReason"
							class="text-amber-900/40 hover:text-amber-900 transition-all p-1 hover:bg-amber-100 hover:shadow-sm rounded-md flex-shrink-0 ml-2">
							<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
									d="M6 18L18 6M6 6l12 12" />
							</svg>
						</button>
					</div>
				</Transition>

				<form @submit.prevent="handleLogin" class="space-y-6">
					<div>
						<label for="email" class="block text-sm font-medium text-gray-700 mb-2">Correo
							electrónico</label>
						<input id="email" ref="emailInput" v-model="form.email" type="email" autocomplete="email"
							required placeholder="nombre@email.com" @keydown.enter.prevent="focusNext('password')"
							class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition-all duration-200 text-gray-900 text-base bg-gray-50/50 hover:bg-white" />
					</div>

					<div>
						<label for="password"
							class="block text-sm font-medium text-gray-700 mb-2 pt-2">Contraseña</label>
						<div class="relative">
							<input id="password" ref="passwordInput" v-model="form.password"
								:type="showPassword ? 'text' : 'password'" autocomplete="current-password" required
								placeholder="••••••••" @keydown.enter.prevent="handleLogin"
								class="block w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition-all duration-200 text-gray-900 text-base bg-gray-50/50 hover:bg-white" />
							<button type="button" @click="showPassword = !showPassword"
								class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-indigo-500 transition-colors">
								<svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor"
									viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
										d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
										d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
								</svg>
								<svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
										d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
								</svg>
							</button>
						</div>
					</div>

					<div class="flex items-center justify-between mt-12 pt-1">
						<div class="flex items-center">
							<input id="remember" type="checkbox" v-model="form.remember"
								class="h-4 w-4 text-indigo-600 border-gray-300 rounded cursor-pointer flex-shrink-0 focus:ring-indigo-400" />
							<label for="remember"
								class="ml-2 block text-sm text-gray-600 cursor-pointer">Recordarme</label>
						</div>
						<a href="#"
							class="text-sm font-medium text-indigo-500 hover:text-indigo-600 transition-colors">¿Olvidaste
							tu contraseña?</a>
					</div>

					<div class="mt-12 pt-2">
						<button type="submit" :disabled="!isFormValid || authStore.loading"
							class="btn-login w-full py-3 px-4 text-white font-bold rounded-xl shadow-lg transition-all duration-300 flex items-center justify-center gap-2"
							:class="[
								isFormValid && !authStore.loading
									? 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 hover:shadow-xl active:scale-98'
									: 'bg-gray-300 cursor-not-allowed opacity-70',
								{ 'shake-animation': isShaking },
							]">
							<svg v-if="authStore.loading" class="animate-spin h-5 w-5 text-white flex-shrink-0"
								viewBox="0 0 24 24">
								<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
									fill="none"></circle>
								<path class="opacity-75" fill="currentColor"
									d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
								</path>
							</svg>
							{{ authStore.loading ? 'Iniciando...' : 'Iniciar Sesión' }}
						</button>
					</div>

					<!-- SECCIÓN DE REGISTRO COMENTADA (solo admin crea cuentas) -->
					<!--
					<div class="text-center mt-12 pt-1">
						<span class="text-sm text-gray-600">¿No tienes cuenta? </span>
						<router-link
							to="/register"
							class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
						>
							Regístrate aquí
						</router-link>
					</div>
					-->
				</form>
			</div>
		</div>
	</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

// Refs para manejo de inputs
const emailInput = ref(null)
const passwordInput = ref(null)

const form = ref({
	email: '',
	password: '',
	remember: false,
})

const errorMessage = ref('')
const logoutReason = ref(null)
const showPassword = ref(false)
const isShaking = ref(false)

// Año actual para el footer
const currentYear = computed(() => new Date().getFullYear())

// Validación reactiva
const isFormValid = computed(() => {
	return form.value.email.includes('@') && form.value.password.length >= 4
})

const focusNext = (target) => {
	if (target === 'password') passwordInput.value?.focus()
}

/**
 * Verificar si el logout fue por inactividad
 */
onMounted(() => {
	const reason = sessionStorage.getItem('logout-reason')
	if (reason === 'inactivity') {
		logoutReason.value = 'inactivity'
		sessionStorage.removeItem('logout-reason')
	}
})

/**
 * Limpiar mensaje de inactividad
 */
const clearLogoutReason = () => {
	logoutReason.value = null
}

/**
 * Handler de login
 */
const handleLogin = async () => {
	if (!isFormValid.value || authStore.loading) return

	errorMessage.value = ''

	const result = await authStore.login({
		email: form.value.email,
		password: form.value.password,
		remember: form.value.remember,
	})

	if (result.success) {
		const redirect = router.currentRoute.value.query.redirect || '/dashboard'
		router.push(redirect)
	} else {
		isShaking.value = true
		setTimeout(() => {
			isShaking.value = false
		}, 500)

		// Manejar diferentes tipos de errores
		if (result.message.includes('pendiente') || result.message.includes('Pendiente')) {
			errorMessage.value =
				result.message || 'Tu cuenta está pendiente de autorización por un administrador.'
		} else if (result.message.includes('desactivada') || result.message.includes('Inactivo')) {
			errorMessage.value =
				result.message || 'Tu cuenta ha sido desactivada. Contacta al administrador.'
		} else if (result.message.includes('401')) {
			errorMessage.value =
				'El correo o la contraseña son incorrectos. Por favor, verifica tus datos.'
		} else if (result.message.includes('network')) {
			errorMessage.value = 'No hay conexión con el servidor. Revisa tu internet.'
		} else {
			errorMessage.value =
				result.message || 'Ocurrió un error inesperado. Inténtalo de nuevo más tarde.'
		}
	}
}
// Función para cerrar el error manualmente
const clearError = () => {
	errorMessage.value = ''
}
</script>

<style scoped>
/* Animaciones de entrada */
.logo-animation {
	animation: logoFadeIn 0.8s ease-out forwards;
}

@keyframes logoFadeIn {
	from {
		opacity: 0;
		transform: translateY(-20px);
	}

	to {
		opacity: 1;
		transform: translateY(0);
	}
}

/* Transiciones de alertas */
.slide-down-enter-active,
.slide-down-leave-active {
	transition: all 0.3s ease-out;
}

.slide-down-enter-from,
.slide-down-leave-to {
	opacity: 0;
	transform: translateY(-10px);
}

/* Botón mejorado con gradiente y brillo */
.btn-login {
	position: relative;
	overflow: hidden;
}

.btn-login:not(:disabled)::after {
	content: '';
	position: absolute;
	top: -50%;
	left: -60%;
	width: 20%;
	height: 200%;
	background: rgba(255, 255, 255, 0.3);
	transform: rotate(30deg);
	animation: shine 3s infinite;
}

@keyframes shine {
	0% {
		left: -60%;
	}

	20% {
		left: 120%;
	}

	100% {
		left: 120%;
	}
}

/* Pulso sutil en botón válido */
@keyframes pulse-subtle {
	0% {
		box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
	}

	70% {
		box-shadow: 0 0 0 6px rgba(99, 102, 241, 0);
	}

	100% {
		box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
	}
}

.btn-login:not(:disabled) {
	animation: pulse-subtle 2s infinite;
}

/* Shake mejorado (error) */
.shake-animation {
	background: linear-gradient(to right, #ef4444, #dc2626) !important;
	box-shadow: 0 0 20px rgba(239, 68, 68, 0.6) !important;
	animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both !important;
}

@keyframes shake {

	10%,
	90% {
		transform: translate3d(-2px, 0, 0);
	}

	20%,
	80% {
		transform: translate3d(3px, 0, 0);
	}

	30%,
	50%,
	70% {
		transform: translate3d(-6px, 0, 0);
	}

	40%,
	60% {
		transform: translate3d(6px, 0, 0);
	}
}

/* Escala activa personalizada */
.active\:scale-98:active {
	transform: scale(0.98);
}
</style>