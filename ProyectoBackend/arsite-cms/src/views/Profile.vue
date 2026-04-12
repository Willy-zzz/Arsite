<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const authStore = useAuthStore()

// Estados
const activeTab = ref('personal')
const isLoading = ref(false)
const presets = ref([])
const tokens = ref([])

// Formularios
const personalForm = ref({
	usu_nombre: '',
	email: '',
})

const passwordForm = ref({
	current_password: '',
	new_password: '',
	new_password_confirmation: '',
})

const avatarForm = ref({
	selectedPreset: null,
	previewUrl: null,
})

// Mensajes
const successMessage = ref('')
const errorMessage = ref('')

// Validación de contraseña
const isPasswordFocused = ref(false)

const passwordStrength = computed(() => {
	let score = 0
	if (passwordForm.value.new_password.length >= 8) score++
	if (/[A-Z]/.test(passwordForm.value.new_password)) score++
	if (/[0-9]/.test(passwordForm.value.new_password)) score++
	if (/[^A-Za-z0-9]/.test(passwordForm.value.new_password)) score++
	return score
})

const passwordStrengthWidth = computed(() =>
	!passwordForm.value.new_password ? '0%' : (passwordStrength.value / 4) * 100 + '%',
)

const passwordStrengthClass = computed(() => {
	if (passwordStrength.value <= 1) return 'bg-red-500'
	if (passwordStrength.value <= 2) return 'bg-amber-500'
	if (passwordStrength.value <= 3) return 'bg-blue-500'
	return 'bg-green-500'
})

// Computed para avatar
const userAvatar = computed(() => authStore.user?.avatar)
const userInitials = computed(() => authStore.userInitials || '??')
const avatarBgColor = computed(() => (authStore.isAdmin ? 'bg-amber-500' : 'bg-indigo-600'))

// Cargar datos iniciales
onMounted(async () => {
	personalForm.value = {
		usu_nombre: authStore.user?.nombre || '',
		email: authStore.user?.email || '',
	}

	await loadPresets()
	await loadTokens()
})

const loadPresets = async () => {
	try {
		const response = await api.get('/profile/avatar/presets')
		if (response.data.success) {
			presets.value = response.data.data
		}
	} catch (err) {
		console.error('Error al cargar presets:', err)
	}
}

const loadTokens = async () => {
	try {
		const response = await api.get('/tokens')
		if (response.data.success) {
			tokens.value = response.data.data
		}
	} catch (err) {
		console.error('Error al cargar tokens:', err)
	}
}

// Actualizar información personal
const updatePersonalInfo = async () => {
	isLoading.value = true
	errorMessage.value = ''
	successMessage.value = ''

	try {
		const response = await api.put('/profile', personalForm.value)

		if (response.data.success) {
			authStore.user.nombre = personalForm.value.usu_nombre
			authStore.user.email = personalForm.value.email

			const storage = localStorage.getItem('auth-token') ? localStorage : sessionStorage
			storage.setItem('auth-user', JSON.stringify(authStore.user))

			successMessage.value = 'Información actualizada correctamente'
			setTimeout(() => {
				successMessage.value = ''
			}, 3000)
		}
	} catch (err) {
		errorMessage.value = err.response?.data?.message || 'Error al actualizar información'
	} finally {
		isLoading.value = false
	}
}

// Cambiar contraseña
const changePassword = async () => {
	if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
		errorMessage.value = 'Las contraseñas no coinciden'
		return
	}

	isLoading.value = true
	errorMessage.value = ''
	successMessage.value = ''

	try {
		const response = await api.put('/change-password', passwordForm.value)

		if (response.data.success) {
			successMessage.value = 'Contraseña actualizada correctamente'
			passwordForm.value = {
				current_password: '',
				new_password: '',
				new_password_confirmation: '',
			}
			setTimeout(() => {
				successMessage.value = ''
			}, 3000)
		}
	} catch (err) {
		errorMessage.value = err.response?.data?.message || 'Error al cambiar contraseña'
	} finally {
		isLoading.value = false
	}
}

// Manejar archivo de avatar
const handleFileUpload = (e) => {
	const file = e.target.files[0]
	if (!file) return

	avatarForm.value.selectedPreset = null
	avatarForm.value.previewUrl = URL.createObjectURL(file)
	saveAvatar(file, 'upload')
}

// Seleccionar preset
const selectPreset = (preset) => {
	avatarForm.value.selectedPreset = preset.url
	avatarForm.value.previewUrl = null
	saveAvatar(preset.name, 'preset')
}

// Volver a iniciales
const resetToInitials = () => {
	avatarForm.value.selectedPreset = null
	avatarForm.value.previewUrl = null
	saveAvatar(null, 'initials')
}

// Guardar avatar
const saveAvatar = async (data, tipo) => {
	isLoading.value = true
	errorMessage.value = ''
	successMessage.value = ''

	try {
		const formData = new FormData()
		formData.append('avatar_tipo', tipo)

		if (tipo === 'upload') {
			formData.append('avatar', data)
		} else if (tipo === 'preset') {
			formData.append('avatar_preset', data)
		}

		const response = await api.post('/profile/avatar', formData, {
			headers: { 'Content-Type': 'multipart/form-data' },
		})

		if (response.data.success) {
			authStore.user.avatar = response.data.data.avatar
			const storage = localStorage.getItem('auth-token') ? localStorage : sessionStorage
			storage.setItem('auth-user', JSON.stringify(authStore.user))

			successMessage.value = 'Avatar actualizado correctamente'
			setTimeout(() => {
				successMessage.value = ''
			}, 3000)
		}
	} catch (err) {
		errorMessage.value = err.response?.data?.message || 'Error al actualizar avatar'
		avatarForm.value.previewUrl = null
		avatarForm.value.selectedPreset = null
	} finally {
		isLoading.value = false
	}
}

// Revocar token
const revokeToken = async (tokenId) => {
	if (!confirm('¿Estás seguro de cerrar esta sesión?')) return

	try {
		const response = await api.delete(`/tokens/${tokenId}`)
		if (response.data.success) {
			await loadTokens()
			successMessage.value = 'Sesión cerrada correctamente'
			setTimeout(() => {
				successMessage.value = ''
			}, 3000)
		}
	} catch (err) {
		errorMessage.value = err.response?.data?.message || 'Error al cerrar sesión'
	}
}

const clearMessage = () => {
	successMessage.value = ''
	errorMessage.value = ''
}
</script>

<template>
	<div class="max-w-4xl mx-auto">
		<h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6 pb-4">Mi Perfil</h1>

		<!-- Mensajes -->
		<transition name="slide-down">
			<div
				v-if="successMessage"
				class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between"
			>
				<div class="flex items-center gap-3">
					<svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
						<path
							fill-rule="evenodd"
							d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
							clip-rule="evenodd"
						/>
					</svg>
					<p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
				</div>
				<button @click="clearMessage" class="text-green-600 hover:text-green-800">
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M6 18L18 6M6 6l12 12"
						/>
					</svg>
				</button>
			</div>
		</transition>

		<transition name="slide-down">
			<div
				v-if="errorMessage"
				class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center justify-between"
			>
				<div class="flex items-center gap-3">
					<svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
						<path
							fill-rule="evenodd"
							d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
							clip-rule="evenodd"
						/>
					</svg>
					<p class="text-sm font-medium text-red-800">{{ errorMessage }}</p>
				</div>
				<button @click="clearMessage" class="text-red-600 hover:text-red-800">
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M6 18L18 6M6 6l12 12"
						/>
					</svg>
				</button>
			</div>
		</transition>

		<!-- Tabs -->
		<div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-md border border-gray-100 mb-6">
			<div class="flex border-b border-gray-200 overflow-x-auto">
				<button
					@click="activeTab = 'personal'"
					class="px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap"
					:class="
						activeTab === 'personal'
							? 'border-indigo-600 text-indigo-600'
							: 'border-transparent text-gray-500 hover:text-gray-700'
					"
				>
					Información Personal
				</button>
				<button
					@click="activeTab = 'avatar'"
					class="px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap"
					:class="
						activeTab === 'avatar'
							? 'border-indigo-600 text-indigo-600'
							: 'border-transparent text-gray-500 hover:text-gray-700'
					"
				>
					Avatar
				</button>
				<button
					@click="activeTab = 'password'"
					class="px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap"
					:class="
						activeTab === 'password'
							? 'border-indigo-600 text-indigo-600'
							: 'border-transparent text-gray-500 hover:text-gray-700'
					"
				>
					Contraseña
				</button>
				<button
					@click="activeTab = 'sessions'"
					class="px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap"
					:class="
						activeTab === 'sessions'
							? 'border-indigo-600 text-indigo-600'
							: 'border-transparent text-gray-500 hover:text-gray-700'
					"
				>
					Sesiones
				</button>
			</div>

			<!-- Contenido de tabs -->
			<div class="p-6">
				<!-- Tab: Información Personal -->
				<div v-show="activeTab === 'personal'" class="space-y-10">
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2"
							>Nombre Completo</label
						>
						<input
							v-model="personalForm.usu_nombre"
							type="text"
							class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900"
						/>
					</div>

					<div>
						<label class="block text-sm font-medium text-gray-700 mb-2 pt-2"
							>Correo Electrónico</label
						>
						<input
							v-model="personalForm.email"
							type="email"
							class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900"
						/>
					</div>

					<div class="flex items-center gap-3 pt-3 pb-3">
						<span class="text-sm font-medium text-gray-700">Rol:</span>
						<span
							class="px-3 py-1 text-sm font-medium rounded-full"
							:class="
								authStore.isAdmin
									? 'bg-amber-100 text-amber-700'
									: 'bg-indigo-100 text-indigo-700'
							"
						>
							{{ authStore.userRole }}
						</span>
					</div>

					<button
						@click="updatePersonalInfo"
						:disabled="isLoading"
						class="w-full md:w-auto px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors mt-6"
					>
						{{ isLoading ? 'Guardando...' : 'Guardar Cambios' }}
					</button>
				</div>

				<!-- Tab: Avatar -->
				<div v-show="activeTab === 'avatar'" class="space-y-10">
					<div class="flex justify-center mb-6">
						<div class="relative">
							<div
								class="w-32 h-32 rounded-full border-4 border-white shadow-xl overflow-hidden bg-slate-50 ring-2 ring-slate-200"
							>
								<img
									v-if="avatarForm.previewUrl"
									:src="avatarForm.previewUrl"
									class="w-full h-full object-cover"
								/>
								<img
									v-else-if="avatarForm.selectedPreset"
									:src="avatarForm.selectedPreset"
									class="w-full h-full object-cover"
								/>
								<img
									v-else-if="
										userAvatar?.tipo === 'upload' ||
										userAvatar?.tipo === 'preset'
									"
									:src="userAvatar.url"
									class="w-full h-full object-cover"
								/>
								<div
									v-else
									class="w-full h-full flex items-center justify-center text-4xl font-black text-white"
									:class="avatarBgColor"
								>
									{{ userInitials }}
								</div>
							</div>
						</div>
					</div>

					<div class="flex flex-wrap pt-4 gap-4 mt-6">
						<label
							class="w-full sm:flex-1 py-3 bg-indigo-600 text-white text-center rounded-lg font-medium cursor-pointer hover:bg-indigo-700 transition-colors"
						>
							<span class="flex items-center justify-center gap-2">
								<svg
									class="h-5 w-5"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
									/>
								</svg>
								Subir Foto
							</span>
							<input
								type="file"
								class="hidden"
								@change="handleFileUpload"
								accept="image/*"
							/>
						</label>

						<button
							@click="resetToInitials"
							class="w-full sm:flex-1 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
						>
							Volver a Iniciales
						</button>
					</div>

					<div class="pt-4">
						<p class="text-sm font-medium text-gray-700 mb-3">
							O elige un avatar predeterminado:
						</p>
						<div class="grid grid-cols-5 gap-3">
							<button
								v-for="preset in presets"
								:key="preset.name"
								@click="selectPreset(preset)"
								class="p-1 rounded-xl transition-all"
								:class="
									avatarForm.selectedPreset === preset.url
										? 'ring-2 ring-indigo-500 scale-110'
										: 'hover:scale-105'
								"
							>
								<img
									:src="preset.url"
									class="w-full h-auto rounded-lg"
									:alt="preset.name"
								/>
							</button>
						</div>
					</div>
				</div>

				<!-- Tab: Contraseña -->
				<div v-show="activeTab === 'password'" class="space-y-10">
					<div class="mb-6 pb-2">
						<label class="block text-sm font-medium text-gray-700 mb-2"
							>Contraseña Actual</label
						>
						<input
							v-model="passwordForm.current_password"
							type="password"
							class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900"
						/>
					</div>

					<div class="mb-6 pb-2">
						<label class="block text-sm font-medium text-gray-700 mb-2"
							>Nueva Contraseña</label
						>
						<input
							v-model="passwordForm.new_password"
							type="password"
							@focus="isPasswordFocused = true"
							@blur="isPasswordFocused = false"
							class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900"
						/>
					</div>

					<div class="mb-6 pb-4">
						<label class="block text-sm font-medium text-gray-700 mb-2"
							>Confirmar Nueva Contraseña</label
						>
						<input
							v-model="passwordForm.new_password_confirmation"
							type="password"
							class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900"
						/>
					</div>

					<transition name="slide-down">
						<div
							v-if="isPasswordFocused || passwordForm.new_password"
							class="bg-gray-50 p-3 rounded-lg border border-gray-200"
						>
							<div class="h-1.5 w-full bg-gray-200 rounded-full overflow-hidden mb-3">
								<div
									class="h-full transition-all duration-500"
									:class="passwordStrengthClass"
									:style="{ width: passwordStrengthWidth }"
								></div>
							</div>
							<div class="grid grid-cols-2 gap-3 text-xs">
								<p
									:class="
										passwordForm.new_password.length >= 8
											? 'text-green-600'
											: 'text-gray-500'
									"
								>
									{{ passwordForm.new_password.length >= 8 ? '✓' : '○' }} Mínimo 8
									caracteres
								</p>
								<p
									:class="
										/[A-Z]/.test(passwordForm.new_password)
											? 'text-green-600'
											: 'text-gray-500'
									"
								>
									{{ /[A-Z]/.test(passwordForm.new_password) ? '✓' : '○' }} Una
									mayúscula
								</p>
								<p
									:class="
										/[0-9]/.test(passwordForm.new_password)
											? 'text-green-600'
											: 'text-gray-500'
									"
								>
									{{ /[0-9]/.test(passwordForm.new_password) ? '✓' : '○' }} Un
									número
								</p>
								<p
									:class="
										passwordForm.new_password ===
											passwordForm.new_password_confirmation &&
										passwordForm.new_password
											? 'text-green-600'
											: 'text-gray-500'
									"
								>
									{{
										passwordForm.new_password ===
											passwordForm.new_password_confirmation &&
										passwordForm.new_password
											? '✓'
											: '○'
									}}
									Coinciden
								</p>
							</div>
						</div>
					</transition>

					<button
						@click="changePassword"
						:disabled="isLoading"
						class="w-full md:w-auto px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors mt-6"
					>
						{{ isLoading ? 'Actualizando...' : 'Cambiar Contraseña' }}
					</button>
				</div>

				<!-- Tab: Sesiones -->
				<div v-show="activeTab === 'sessions'" class="space-y-8">
					<p class="text-sm text-gray-600 mb-4 pb-4">
						Estas son las sesiones activas de tu cuenta. Puedes cerrar cualquier sesión
						que no reconozcas.
					</p>

					<div v-if="tokens.length === 0" class="text-center py-8 text-gray-500">
						No hay sesiones activas
					</div>

					<div
						v-for="token in tokens"
						:key="token.id"
						class="p-4 border border-gray-200 rounded-lg hover:border-gray-300 transition-colors"
					>
						<div class="flex items-start justify-between">
							<div class="flex-1">
								<div class="flex items-center gap-2 mb-2">
									<svg
										class="h-5 w-5 text-gray-400"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
										/>
									</svg>
									<h3 class="font-medium text-gray-900">{{ token.name }}</h3>
									<span
										v-if="token.is_current"
										class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full"
									>
										Sesión Actual
									</span>
								</div>
								<p class="text-sm text-gray-500">
									Último uso: {{ token.last_used_at }}
								</p>
								<p class="text-sm text-gray-500">Creada: {{ token.created_at }}</p>
							</div>
							<button
								v-if="!token.is_current"
								@click="revokeToken(token.id)"
								class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors"
							>
								Cerrar Sesión
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
	transition: all 0.3s ease-out;
}

.slide-down-enter-from,
.slide-down-leave-to {
	opacity: 0;
	transform: translateY(-10px);
}
</style>
