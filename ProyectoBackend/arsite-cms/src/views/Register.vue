<template>
	<div class="w-full max-w-md mx-auto my-auto px-2">
		<div
			class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 flex flex-col"
		>
			<div class="px-6 sm:px-8 py-8 sm:py-10 flex-1">
				<div class="flex justify-center mb-6 sm:mb-8">
					<img
						src="@/assets/logo-arsite.png"
						alt="Ar-Site Integradores"
						class="h-20 sm:h-24 w-auto mix-blend-multiply logo-animation"
					/>
				</div>

				<div class="text-center mb-8">
					<h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Crear Cuenta</h2>
					<p class="text-sm sm:text-base text-gray-600 pt-2">
						Regístrate para acceder al sistema
					</p>
				</div>

				<Transition name="slide-down">
					<div
						v-if="authStore.error"
						class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 flex items-start gap-3 shadow-sm"
					>
						<svg
							class="w-5 h-5 text-red-500 mt-0.5"
							fill="currentColor"
							viewBox="0 0 20 20"
						>
							<path
								fill-rule="evenodd"
								d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
								clip-rule="evenodd"
							/>
						</svg>
						<p class="text-sm text-red-800 font-medium">{{ authStore.error }}</p>
					</div>
				</Transition>

				<form @submit.prevent="handleRegister" class="flex flex-col">
					<div class="space-y-7">
						<div>
							<label
								for="usu_nombre"
								class="block text-sm font-semibold text-gray-700 mb-2 pt-4"
								>Nombre Completo</label
							>
							<input
								id="usu_nombre"
								v-model="formData.usu_nombre"
								type="text"
								required
								placeholder="Tu nombre"
								autocomplete="name"
								@keydown.enter.prevent="focusNext('email')"
								class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-gray-900"
							/>
						</div>

						<div>
							<label
								for="email"
								class="block text-sm font-semibold text-gray-700 mb-1.5 pt-2"
								>Correo Electrónico</label
							>
							<input
								id="email"
								ref="emailInput"
								v-model="formData.email"
								type="email"
								required
								placeholder="ejemplo@ar-site.com"
								autocomplete="email"
								@keydown.enter.prevent="focusNext('password')"
								class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-gray-900"
							/>
						</div>

						<div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
							<div>
								<label
									for="password"
									class="block text-sm font-semibold text-gray-700 mb-1.5 pt-2"
									>Contraseña</label
								>
								<input
									id="password"
									ref="passwordInput"
									v-model="formData.password"
									type="password"
									required
									placeholder="••••••••"
									autocomplete="new-password"
									@focus="isPasswordFocused = true"
									@blur="isPasswordFocused = false"
									@keydown.enter.prevent="focusNext('password_confirmation')"
									class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-gray-900"
								/>
							</div>
							<div>
								<label
									for="password_confirmation"
									class="block text-sm font-semibold text-gray-700 mb-1.5"
									>Confirmar</label
								>
								<input
									id="password_confirmation"
									ref="confirmInput"
									v-model="formData.password_confirmation"
									type="password"
									required
									placeholder="••••••••"
									autocomplete="new-password"
									@keydown.enter.prevent="handleRegister"
									class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-gray-900"
								/>
							</div>
						</div>

						<Transition name="slide-down">
							<div
								v-if="isPasswordFocused || formData.password"
								class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-4"
							>
								<div class="h-1.5 w-full bg-gray-200 rounded-full overflow-hidden">
									<div
										class="h-full transition-all duration-500"
										:class="passwordStrengthClass"
										:style="{ width: passwordStrengthWidth }"
									></div>
								</div>
								<div class="grid grid-cols-2 gap-4 text-[11px] font-medium">
									<p
										:class="
											formData.password.length >= 8
												? 'text-green-600'
												: 'text-gray-400'
										"
										class="flex items-center"
									>
										<span class="mr-2 text-sm">{{
											formData.password.length >= 8 ? '✓' : '○'
										}}</span>
										+8 caracteres
									</p>
									<p
										:class="
											/[A-Z]/.test(formData.password)
												? 'text-green-600'
												: 'text-gray-400'
										"
										class="flex items-center"
									>
										<span class="mr-2 text-sm">{{
											/[A-Z]/.test(formData.password) ? '✓' : '○'
										}}</span>
										Mayúscula
									</p>
									<p
										:class="
											/[0-9]/.test(formData.password)
												? 'text-green-600'
												: 'text-gray-400'
										"
										class="flex items-center"
									>
										<span class="mr-2 text-sm">{{
											/[0-9]/.test(formData.password) ? '✓' : '○'
										}}</span>
										Un número
									</p>
									<p
										:class="
											formData.password === formData.password_confirmation &&
											formData.password !== ''
												? 'text-green-600'
												: 'text-gray-400'
										"
										class="flex items-center"
									>
										<span class="mr-2 text-sm">{{
											formData.password === formData.password_confirmation &&
											formData.password !== ''
												? '✓'
												: '○'
										}}</span>
										Coinciden
									</p>
								</div>
							</div>
						</Transition>
					</div>

					<div class="mt-12 pt-5">
						<button
							type="submit"
							:disabled="!isFormValid || authStore.loading"
							class="btn-register w-full py-3 px-2 text-white font-bold rounded-xl shadow-lg transition-all flex items-center justify-center gap-2"
							:class="[
								isFormValid && !authStore.loading
									? 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-200 active:scale-[0.98]'
									: 'bg-gray-300 cursor-not-allowed opacity-70',
								{ 'shake-error': isShaking },
							]"
						>
							<svg
								v-if="authStore.loading"
								class="animate-spin h-5 w-5 text-white"
								viewBox="0 0 24 24"
							>
								<circle
									class="opacity-25"
									cx="12"
									cy="12"
									r="10"
									stroke="currentColor"
									stroke-width="4"
									fill="none"
								></circle>
								<path
									class="opacity-75"
									fill="currentColor"
									d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
								></path>
							</svg>
							{{ authStore.loading ? 'Procesando...' : 'Registrarme ahora' }}
						</button>
					</div>
				</form>
			</div>

			<div class="px-8 py-6 bg-gray-50 border-t border-gray-100 text-center">
				<p class="text-sm text-gray-600">
					¿Ya tienes una cuenta?
					<router-link to="/login" class="text-indigo-600 font-bold ml-1"
						>Inicia sesión</router-link
					>
				</p>
			</div>
		</div>
	</div>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

// Refs para inputs y animaciones
const emailInput = ref(null)
const passwordInput = ref(null)
const confirmInput = ref(null)
const isPasswordFocused = ref(false)
const isShaking = ref(false)

const formData = reactive({
	usu_nombre: '',
	email: '',
	password: '',
	password_confirmation: '',
	usu_rol: 'Editor',
})

// Salto de campos
const focusNext = (fieldName) => {
	if (fieldName === 'email') emailInput.value?.focus()
	if (fieldName === 'password') passwordInput.value?.focus()
	if (fieldName === 'confirm') confirmInput.value?.focus()
}

// Lógica de fuerza de contraseña
const passwordStrength = computed(() => {
	let score = 0
	if (formData.password.length >= 8) score++
	if (/[A-Z]/.test(formData.password)) score++
	if (/[0-9]/.test(formData.password)) score++
	if (/[^A-Za-z0-9]/.test(formData.password)) score++
	return score
})

const passwordStrengthWidth = computed(() =>
	!formData.password ? '0%' : (passwordStrength.value / 4) * 100 + '%',
)

const passwordStrengthClass = computed(() => {
	if (passwordStrength.value <= 1) return 'bg-red-500'
	if (passwordStrength.value <= 2) return 'bg-amber-500'
	if (passwordStrength.value <= 3) return 'bg-blue-500'
	return 'bg-green-500'
})

// Validación reactiva del formulario
const isFormValid = computed(() => {
	return (
		formData.usu_nombre.trim().length >= 3 &&
		formData.email.includes('@') &&
		formData.password.length >= 8 &&
		formData.password === formData.password_confirmation &&
		formData.password !== ''
	)
})

// Handler de Registro
const handleRegister = async () => {
	if (!isFormValid.value || authStore.loading) return
	authStore.error = null

	const result = await authStore.register(formData)

	if (result.success) {
		router.push('/setup-profile')
	} else {
		// Efecto Shake
		isShaking.value = true
		setTimeout(() => {
			isShaking.value = false
		}, 500)

		if (result.errors && result.errors.email) {
			authStore.error = result.errors.email[0]
		} else {
			authStore.error = result.message || 'Ocurrió un error inesperado.'
		}
	}
}
</script>

<style scoped>
.logo-animation {
	filter: drop-shadow(0 0 8px rgba(79, 70, 229, 0.1));
	transition: transform 0.3s ease;
}
.logo-animation:hover {
	transform: scale(1.05);
}

.slide-down-enter-active,
.slide-down-leave-active {
	transition: all 0.3s ease-out;
}
.slide-down-enter-from,
.slide-down-leave-to {
	opacity: 0;
	transform: translateY(-10px);
}

/* --- Animaciones del Botón --- */
.btn-register {
	position: relative;
	overflow: hidden;
}

/* Efecto Brillo (Shine) */
.btn-register:not(:disabled)::after {
	content: '';
	position: absolute;
	top: -50%;
	left: -60%;
	width: 20%;
	height: 200%;
	background: rgba(255, 255, 255, 0.2);
	transform: rotate(30deg);
	animation: shine 3s infinite;
}

/* Efecto Pulso sutil */
.btn-register:not(:disabled) {
	animation: pulse-subtle 2s infinite;
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

@keyframes pulse-subtle {
	0% {
		box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4);
	}
	70% {
		box-shadow: 0 0 0 6px rgba(79, 70, 229, 0);
	}
	100% {
		box-shadow: 0 0 0 0 rgba(79, 70, 229, 0);
	}
}

/* --- Efecto SHAKE de Error --- */
.shake-error {
	animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both !important;
	background-color: #ef4444 !important;
	box-shadow: 0 0 15px rgba(239, 68, 68, 0.5) !important;
}

@keyframes shake {
	10%,
	90% {
		transform: translateX(-1px);
	}
	20%,
	80% {
		transform: translateX(2px);
	}
	30%,
	50%,
	70% {
		transform: translateX(-4px);
	}
	40%,
	60% {
		transform: translateX(4px);
	}
}
</style>
