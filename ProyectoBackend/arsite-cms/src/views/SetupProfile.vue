<template>
	<div
		class="min-h-screen bg-gradient-vibrant flex items-center justify-center p-6 font-inter overflow-hidden relative"
	>
		<div
			class="absolute top-[-15%] left-[-10%] w-[600px] h-[600px] bg-indigo-600 rounded-full mix-blend-multiply filter blur-[110px] opacity-50 animate-blob"
		></div>
		<div
			class="absolute bottom-[-15%] right-[-10%] w-[600px] h-[600px] bg-orange-400 rounded-full mix-blend-multiply filter blur-[90px] opacity-30 animate-blob animation-delay-2000"
		></div>
		<div
			class="absolute top-[25%] right-[5%] w-[350px] h-[350px] bg-purple-500 rounded-full mix-blend-multiply filter blur-[90px] opacity-30 animate-blob animation-delay-4000"
		></div>
		<div class="absolute inset-0 opacity-50 pointer-events-none z-0">
			<div
				class="absolute top-[10%] left-[5%] h-5 w-5 bg-amber-500 animate-shine-rotate shadow-lg"
			></div>
			<div class="absolute top-[25%] left-[12%] h-2 w-2 bg-white animate-float"></div>
			<div
				class="absolute top-[40%] left-[8%] h-4 w-4 border-2 border-indigo-400 animate-shine-rotate"
			></div>
			<div
				class="absolute top-[55%] left-[15%] h-3 w-3 bg-amber-600 animate-float-reverse"
			></div>
			<div
				class="absolute top-[70%] left-[5%] h-6 w-6 border-2 border-white/40 animate-shine-rotate"
			></div>
			<div class="absolute top-[82%] left-[10%] h-4 w-4 bg-indigo-500 shadow-md"></div>
			<div
				class="absolute top-[35%] left-[2%] h-2 w-2 bg-white rounded-full animate-pulse"
			></div>
			<div
				class="absolute top-[92%] left-[18%] h-5 w-5 bg-amber-500/50 rotate-45 animate-float-slow"
			></div>

			<div
				class="absolute top-[8%] right-[10%] h-6 w-6 bg-indigo-600 animate-shine-rotate shadow-xl"
			></div>
			<div
				class="absolute top-[22%] right-[5%] h-3 w-3 bg-amber-400 animate-float-slow"
			></div>
			<div
				class="absolute top-[38%] right-[15%] h-5 w-5 border-2 border-white rotate-12"
			></div>
			<div
				class="absolute top-[52%] right-[8%] h-2 w-2 bg-indigo-400 animate-shine-rotate"
			></div>
			<div
				class="absolute top-[68%] right-[12%] h-4 w-4 bg-amber-600 animate-float shadow-md"
			></div>
			<div
				class="absolute top-[82%] right-[4%] h-5 w-5 border-2 border-indigo-400 animate-shine-rotate"
			></div>
			<div
				class="absolute top-[45%] right-[2%] h-3 w-3 bg-white opacity-80 animate-float-reverse"
			></div>
			<div
				class="absolute top-[90%] right-[15%] h-2 w-2 bg-amber-500 rounded-full animate-pulse"
			></div>
		</div>

		<transition name="page-zoom" appear>
			<div
				class="max-w-sm w-full bg-white/85 backdrop-blur-2xl rounded-[2.5rem] shadow-[0_25px_60px_rgba(0,0,0,0.18)] p-8 text-center border border-white/50 relative z-10"
			>
				<header class="mb-10">
					<h2 class="text-3xl font-black text-slate-800 tracking-tight leading-tight">
						¡Hola, <span class="text-indigo-600">{{ authStore.user?.nombre }}</span
						>!
					</h2>
					<p class="text-slate-500 font-medium mt-3 leading-relaxed pt-1 pb-3">
						Personaliza tu perfil para comenzar
					</p>
				</header>

				<div class="mb-8 flex justify-center">
					<div @click="triggerFileInput" class="relative group cursor-pointer">
						<div
							:class="[
								'w-32 h-32 rounded-full border-4 border-white shadow-xl overflow-hidden bg-slate-50 ring-2 ring-slate-100 transition-all duration-500 ease-out',
								'group-hover:scale-110 group-hover:rotate-6 group-hover:shadow-indigo-300/50',
								isSaved ? 'ring-green-400 scale-105' : '',
							]"
						>
							<transition name="fade" mode="out-in">
								<img
									v-if="previewUrl || selectedPreset"
									:key="previewUrl || selectedPreset"
									:src="previewUrl || selectedPreset"
									class="w-full h-full object-cover"
								/>
								<div
									v-else
									class="w-full h-full flex items-center justify-center text-4xl font-black"
									:class="
										authStore.user?.rol === 'Administrador'
											? 'bg-amber-500 text-white'
											: 'bg-indigo-600 text-white'
									"
								>
									{{ authStore.user?.iniciales || '??' }}
								</div>
							</transition>
						</div>

						<transition name="pop">
							<div
								v-if="isSaved"
								class="absolute bottom-0 right-0 bg-green-500 text-white w-10 h-10 rounded-full border-4 border-white flex items-center justify-center shadow-lg z-10"
							>
								<svg
									xmlns="http://www.w3.org/2000/svg"
									class="h-5 w-5"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
									stroke-width="4"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										d="M5 13l4 4L19 7"
									/>
								</svg>
							</div>
						</transition>

						<div
							v-if="isLoading"
							class="absolute inset-0 bg-white/60 backdrop-blur-[1px] rounded-full flex items-center justify-center z-20"
						>
							<div
								class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"
							></div>
						</div>
					</div>
				</div>

				<input
					ref="fileInput"
					type="file"
					class="hidden"
					@change="handleFileUpload"
					accept="image/*"
				/>

				<transition name="shake">
					<div
						v-if="validationErrors"
						class="mb-8 py-3 px-5 bg-red-50 text-red-500 text-xs font-bold rounded-2xl border border-red-100 flex items-center justify-center gap-2"
					>
						<span>{{ validationErrors[0] }}</span>
					</div>
				</transition>

				<div class="space-y-6">
					<template v-if="!isSaved">
						<label
							class="block w-full py-4.5 bg-slate-900 text-white rounded-2xl font-bold cursor-pointer hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all duration-300 active:scale-95"
						>
							<span class="flex items-center justify-center gap-2 tracking-wide">
								<svg
									xmlns="http://www.w3.org/2000/svg"
									class="h-5 w-5"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
									stroke-width="2"
								>
									<path
										d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
									/>
								</svg>
								Subir foto
							</span>
							<input
								type="file"
								class="hidden"
								@change="handleFileUpload"
								accept="image/*"
							/>
						</label>

						<div class="flex items-center gap-4 py-4">
							<div class="flex-1 h-px bg-slate-200/50"></div>
							<span
								class="text-[10px] font-black text-slate-600 uppercase tracking-widest"
								>O elige un avatar</span
							>
							<div class="flex-1 h-px bg-slate-200/50"></div>
						</div>

						<div class="grid grid-cols-5 gap-5 px-1">
							<button
								v-for="p in presets"
								:key="p.name"
								@click="selectPresetAction(p)"
								class="p-0.5 rounded-xl transition-all duration-300"
								:class="
									selectedPreset === p.url
										? 'bg-indigo-50 ring-2 ring-indigo-500 scale-110 shadow-md'
										: 'hover:scale-115 hover:rotate-3'
								"
							>
								<img
									:src="p.url"
									class="w-full h-auto drop-shadow-md"
									:alt="p.name"
								/>
							</button>
						</div>

						<button
							@click="handleSkip"
							class="block w-full mt-10 text-slate-700 text-[10px] font-black hover:text-indigo-600 transition-colors uppercase tracking-[0.2em] py-2"
						>
							Omitir por ahora →
						</button>
					</template>

					<transition name="slide-up">
						<div v-if="isSaved" class="py-4 space-y-10">
							<div class="space-y-3 text-center">
								<p class="text-3xl font-black text-slate-800 tracking-tight">
									¡Se ve genial!
								</p>
								<p class="text-slate-600 text-sm font-medium pt-2 pb-2">
									Tu nueva foto ha sido guardada.
								</p>
							</div>

							<button
								@click="handleContinue"
								class="w-full py-5 bg-indigo-600 text-white rounded-2xl font-black shadow-2xl shadow-indigo-200 hover:bg-indigo-700 transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-3 group"
							>
								Continuar
								<svg
									xmlns="http://www.w3.org/2000/svg"
									class="h-6 w-6 group-hover:translate-x-1 transition-transform duration-300"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
									stroke-width="3"
								>
									<path d="M14 5l7 7m0 0l-7 7m7-7H3" />
								</svg>
							</button>

							<button
								@click="changeOpinion"
								class="text-slate-600 text-[10px] font-black hover:text-red-500 transition-colors uppercase tracking-widest py-2 pt-4"
							>
								Cambiar foto
							</button>
						</div>
					</transition>
				</div>
			</div>
		</transition>

		<!-- Modal de Cuenta Pendiente con Contador -->
		<transition name="modal-fade">
			<div
				v-if="showPendingModal"
				class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
				@click.self="goToLogin"
			>
				<transition name="modal-scale">
					<div
						v-if="showPendingModal"
						class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden"
					>
						<!-- Header con ícono -->
						<div class="bg-gradient-to-br from-amber-500 to-orange-500 p-8 text-center">
							<div
								class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full mb-4 shadow-lg"
							>
								<svg
									class="w-10 h-10 text-amber-500"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
									/>
								</svg>
							</div>
							<h2 class="text-2xl font-bold text-white mb-2">¡Registro Exitoso!</h2>
							<p class="text-amber-50 text-sm">
								Tu cuenta ha sido creada correctamente
							</p>
						</div>

						<!-- Contenido -->
						<div class="p-8">
							<div class="space-y-4 mb-8">
								<div class="flex items-start gap-3">
									<div
										class="flex-shrink-0 w-6 h-6 bg-amber-100 rounded-full flex items-center justify-center mt-0.5"
									>
										<svg
											class="w-4 h-4 text-amber-600"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M5 13l4 4L19 7"
											/>
										</svg>
									</div>
									<div>
										<h3 class="font-semibold text-gray-900 text-sm">
											Cuenta Creada
										</h3>
										<p class="text-sm text-gray-600 mt-1">
											Tu información ha sido registrada exitosamente
										</p>
									</div>
								</div>

								<div class="flex items-start gap-3">
									<div
										class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mt-0.5"
									>
										<svg
											class="w-4 h-4 text-blue-600"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
											/>
										</svg>
									</div>
									<div>
										<h3 class="font-semibold text-gray-900 text-sm">
											Pendiente de Autorización
										</h3>
										<p class="text-sm text-gray-600 mt-1">
											Un administrador debe aprobar tu cuenta antes de que
											puedas acceder al sistema
										</p>
									</div>
								</div>

								<div class="flex items-start gap-3">
									<div
										class="flex-shrink-0 w-6 h-6 bg-green-100 rounded-full flex items-center justify-center mt-0.5"
									>
										<svg
											class="w-4 h-4 text-green-600"
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
									<div>
										<h3 class="font-semibold text-gray-900 text-sm">
											Te Notificaremos
										</h3>
										<p class="text-sm text-gray-600 mt-1">
											Recibirás una notificación cuando tu cuenta sea activada
										</p>
									</div>
								</div>
							</div>

							<!-- Nota informativa -->
							<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
								<div class="flex gap-3">
									<svg
										class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5"
										fill="currentColor"
										viewBox="0 0 20 20"
									>
										<path
											fill-rule="evenodd"
											d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
											clip-rule="evenodd"
										/>
									</svg>
									<p class="text-xs text-amber-800 leading-relaxed">
										<strong>Importante:</strong> No podrás iniciar sesión hasta
										que un administrador apruebe tu cuenta. Este proceso suele
										tardar entre 24-48 horas.
									</p>
								</div>
							</div>

							<!-- Contador de redirección -->
							<div
								class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 mb-6 text-center"
							>
								<p class="text-sm text-indigo-800">
									Serás redirigido al login en
									<span
										class="inline-flex items-center justify-center w-10 h-10 mx-1 font-black text-xl text-white bg-indigo-600 rounded-full animate-pulse"
									>
										{{ countdown }}
									</span>
									segundo{{ countdown !== 1 ? 's' : '' }}
								</p>
							</div>

							<!-- Botón de acción -->
							<button
								@click="goToLogin"
								class="w-full py-3 px-4 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:from-amber-600 hover:to-orange-600 transition-all duration-200 active:scale-95 flex items-center justify-center gap-2"
							>
								<span>Ir al Inicio de Sesión Ahora</span>
								<svg
									class="w-5 h-5"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M13 7l5 5m0 0l-5 5m5-5H6"
									/>
								</svg>
							</button>
						</div>
					</div>
				</transition>
			</div>
		</transition>
	</div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const authStore = useAuthStore()
const router = useRouter()

// Estados
const presets = ref([])
const previewUrl = ref(null)
const selectedPreset = ref(null)
const isLoading = ref(false)
const isSaved = ref(false)
const validationErrors = ref(null)
const fileInput = ref(null)
const showPendingModal = ref(false)
const countdown = ref(10)
let countdownInterval = null

const triggerFileInput = () => {
	fileInput.value.click()
}

onMounted(async () => {
	const token = localStorage.getItem('auth-token') || sessionStorage.getItem('auth-token')
	if (token) {
		api.defaults.headers.common['Authorization'] = `Bearer ${token}`
	}

	try {
		const response = await api.get('/profile/avatar/presets')
		if (response.data.success) {
			presets.value = response.data.data
		}
	} catch (e) {
		console.error('Error al cargar presets:', e)
	}
})

const handleFileUpload = (e) => {
	const file = e.target.files[0]
	if (!file) return

	validationErrors.value = null
	selectedPreset.value = null
	previewUrl.value = URL.createObjectURL(file)
	saveAvatar(file, 'upload')
}

const saveAvatar = async (data, tipo) => {
	isLoading.value = true
	isSaved.value = false
	validationErrors.value = null

	try {
		const formData = new FormData()
		formData.append('avatar_tipo', tipo)

		if (tipo === 'upload') {
			formData.append('avatar', data)
		} else {
			formData.append('avatar_preset', data)
		}

		const response = await api.post('/profile/avatar', formData, {
			headers: { 'Content-Type': 'multipart/form-data' },
		})

		if (response.data.success) {
			authStore.user.avatar = response.data.data.avatar
			const storage = localStorage.getItem('auth-token') ? localStorage : sessionStorage
			storage.setItem('auth-user', JSON.stringify(authStore.user))

			setTimeout(() => {
				isSaved.value = true
			}, 400)
		}
	} catch (e) {
		if (e.status === 422) {
			validationErrors.value = e.errors.avatar || e.errors.avatar_preset || [e.message]
			previewUrl.value = null
		}
	} finally {
		isLoading.value = false
	}
}

const selectPresetAction = (p) => {
	validationErrors.value = null
	selectedPreset.value = p.url
	previewUrl.value = null
	saveAvatar(p.name, 'preset')
}

const changeOpinion = () => {
	isSaved.value = false
	validationErrors.value = null
}

// Iniciar contador cuando se muestra el modal
const startCountdown = () => {
	countdown.value = 10
	if (countdownInterval) {
		clearInterval(countdownInterval)
	}
	countdownInterval = setInterval(() => {
		countdown.value--
		if (countdown.value <= 0) {
			goToLogin()
		}
	}, 1000)
}

const stopCountdown = () => {
	if (countdownInterval) {
		clearInterval(countdownInterval)
		countdownInterval = null
	}
}

const handleContinue = () => {
	showPendingModal.value = true
	startCountdown()
}

const handleSkip = () => {
	showPendingModal.value = true
	startCountdown()
}

const goToLogin = () => {
	stopCountdown()
	showPendingModal.value = false
	setTimeout(() => {
		authStore.logout()
	}, 300)
}

onUnmounted(() => {
	stopCountdown()
})
</script>

<style scoped>
/* Fondo con Gradiente Animado */
.bg-gradient-vibrant {
	background: linear-gradient(-45deg, #020617, #0f172a, #1e1b4b, #020617);
	background-size: 400% 400%;
	animation: gradient-anim 12s ease infinite;
}

@keyframes gradient-anim {
	0% {
		background-position: 0% 50%;
	}
	50% {
		background-position: 100% 50%;
	}
	100% {
		background-position: 0% 50%;
	}
}

/* Animaciones de Blobs y Geometría */
@keyframes blob {
	0% {
		transform: translate(0px, 0px) scale(1);
	}
	33% {
		transform: translate(40px, -50px) scale(1.1);
	}
	66% {
		transform: translate(-30px, 30px) scale(0.9);
	}
	100% {
		transform: translate(0px, 0px) scale(1);
	}
}

@keyframes float {
	0%,
	100% {
		transform: translateY(0);
	}
	50% {
		transform: translateY(-15px);
	}
}

.animate-blob {
	animation: blob 8s infinite alternate ease-in-out;
}
.animate-float {
	animation: float 6s infinite ease-in-out;
}
.animate-float-reverse {
	animation: float 6s infinite reverse ease-in-out;
}
.animate-float-slow {
	animation: float 10s infinite ease-in-out;
}
.animation-delay-2000 {
	animation-delay: 2s;
}
.animation-delay-4000 {
	animation-delay: 4s;
}

.py-4\.5 {
	padding-top: 1.125rem;
	padding-bottom: 1.125rem;
}

/* Transiciones de Vue */
.page-zoom-enter-active {
	transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.page-zoom-enter-from {
	opacity: 0;
	transform: scale(0.95);
}

.fade-enter-active,
.fade-leave-active {
	transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
	opacity: 0;
}

.pop-enter-active {
	animation: pop-in 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes pop-in {
	0% {
		transform: scale(0);
		opacity: 0;
	}
	100% {
		transform: scale(1);
		opacity: 1;
	}
}

.slide-up-enter-active {
	transition: all 0.5s ease-out;
}
.slide-up-enter-from {
	opacity: 0;
	transform: translateY(20px);
}

.shake-enter-active {
	animation: shake 0.4s;
}
@keyframes shake {
	0%,
	100% {
		transform: translateX(0);
	}
	25% {
		transform: translateX(-5px);
	}
	75% {
		transform: translateX(5px);
	}
}

@keyframes spin-slow {
	from {
		transform: rotate(0deg);
	}
	to {
		transform: rotate(360deg);
	}
}

@keyframes glow-pulse {
	0%,
	100% {
		filter: brightness(1) drop-shadow(0 0 0px rgba(255, 255, 255, 0));
		opacity: 0.4;
	}
	50% {
		filter: brightness(1.5) drop-shadow(0 0 15px rgba(255, 255, 255, 0.7));
		opacity: 1;
	}
}

.animate-shine-rotate {
	animation:
		spin-slow 8s linear infinite,
		glow-pulse 4s ease-in-out infinite;
}

/* Animaciones del Modal */
.modal-fade-enter-active,
.modal-fade-leave-active {
	transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
	opacity: 0;
}

.modal-scale-enter-active {
	transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-scale-leave-active {
	transition: all 0.2s ease;
}

.modal-scale-enter-from {
	opacity: 0;
	transform: scale(0.9) translateY(-20px);
}

.modal-scale-leave-to {
	opacity: 0;
	transform: scale(0.95);
}
</style>
