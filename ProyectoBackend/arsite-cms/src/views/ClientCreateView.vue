<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const loading = ref(false)
const logoPreview = ref(null)
const formErrors = ref({})
const toast = ref({ show: false, message: '', type: 'success' })

const form = ref({
	cli_nombre: '',
	cli_logo: null,
	cli_orden: '',
	cli_fecha_publicacion: '',
	cli_fecha_terminacion: '',
	cli_estatus: 'Guardado',
})

const showToast = (message, type = 'success') => {
	toast.value = { show: true, message, type }
	setTimeout(() => (toast.value.show = false), 4500)
}

const handleLogoChange = (event) => {
	const file = event.target.files[0]
	if (file) {
		form.value.cli_logo = file
		const reader = new FileReader()
		reader.onload = (e) => (logoPreview.value = e.target.result)
		reader.readAsDataURL(file)
	}
}

const removeLogo = () => {
	form.value.cli_logo = null
	logoPreview.value = null
}

const saveCliente = async (estatus = null) => {
	if (estatus) form.value.cli_estatus = estatus
	loading.value = true
	formErrors.value = {}

	if (!form.value.cli_nombre || form.value.cli_nombre.trim() === '') {
		formErrors.value.cli_nombre = ['El nombre es obligatorio']
	}
	if (!form.value.cli_logo) {
		formErrors.value.cli_logo = ['El logotipo es obligatorio']
	}
	if (!form.value.cli_fecha_publicacion) {
		formErrors.value.cli_fecha_publicacion = ['La fecha de publicación es obligatoria']
	}

	if (Object.keys(formErrors.value).length > 0) {
		showToast('Por favor completa todos los campos obligatorios', 'error')
		loading.value = false
		return
	}

	try {
		const formData = new FormData()
		Object.keys(form.value).forEach((key) => {
			if (key === 'cli_logo') return
			if (form.value[key] !== null && form.value[key] !== '') {
				formData.append(key, form.value[key])
			}
		})
		if (form.value.cli_logo instanceof File) {
			formData.append('cli_logo', form.value.cli_logo)
		}

		const response = await api.post('/clientes', formData, {
			headers: { 'Content-Type': 'multipart/form-data' },
		})

		if (response.data.success) {
			showToast(response.data.message, 'success')
			setTimeout(() => router.push('/clients'), 1400)
		}
	} catch (err) {
		if (err.response?.status === 422) formErrors.value = err.response.data.errors || {}
		showToast(err.response?.data?.message || 'Error al crear el cliente', 'error')
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<div class="min-h-screen bg-[#f4f5f7] px-4 py-10">
		<transition name="toast-slide">
			<div
				v-if="toast.show"
				class="fixed top-5 right-5 z-50 w-full max-w-sm pointer-events-none"
			>
				<div
					class="flex items-start gap-3.5 px-5 py-4 rounded-2xl shadow-2xl text-white"
					:class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-rose-600'"
				>
					<div
						class="mt-0.5 shrink-0 w-5 h-5 rounded-full bg-white/20 flex items-center justify-center"
					>
						<svg
							v-if="toast.type === 'success'"
							class="w-3 h-3"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
							stroke-width="3"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								d="M5 13l4 4L19 7"
							/>
						</svg>
						<svg
							v-else
							class="w-3 h-3"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
							stroke-width="3"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								d="M6 18L18 6M6 6l12 12"
							/>
						</svg>
					</div>
					<p class="text-sm font-medium leading-relaxed">{{ toast.message }}</p>
				</div>
			</div>
		</transition>

		<div class="max-w-4xl mx-auto">
			<div class="mb-10">
				<button
					@click="router.push('/clients')"
					class="group inline-flex items-center gap-2.5 mb-7 text-sm font-semibold text-gray-500 hover:text-[#312AFF] transition-colors duration-200"
				>
					<span
						class="w-9 h-9 rounded-xl border border-gray-200 bg-white flex items-center justify-center shadow-sm group-hover:border-[#312AFF] group-hover:bg-[#312AFF]/5 group-hover:shadow-md transition-all duration-200"
					>
						<svg
							class="w-4 h-4"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
							stroke-width="2.5"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								d="M15 19l-7-7 7-7"
							/>
						</svg>
					</span>
					Volver a clientes
				</button>
				<div class="flex items-start justify-between gap-4">
					<div>
						<h1 class="text-3xl font-bold text-gray-900 tracking-tight">
							Crear cliente
						</h1>
						<p class="mt-1.5 text-gray-500 text-sm max-w-md">
							Agrega una nueva empresa a la sección de clientes
						</p>
					</div>
					<div
						class="hidden sm:flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-gray-200 shadow-sm shrink-0 mt-1"
					>
						<span
							class="w-2 h-2 rounded-full transition-colors"
							:class="
								form.cli_estatus === 'Publicado' ? 'bg-emerald-500' : 'bg-amber-400'
							"
						></span>
						<span class="text-xs font-bold text-gray-700">{{ form.cli_estatus }}</span>
					</div>
				</div>
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-6 items-start">
				<div class="space-y-5">
					<!-- Nombre -->
					<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
						<div class="px-7 py-5 border-b border-gray-100 flex items-center gap-3">
							<div
								class="w-8 h-8 rounded-xl bg-[#312AFF]/10 flex items-center justify-center shrink-0"
							>
								<svg
									class="w-4 h-4 text-[#312AFF]"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
									stroke-width="2"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
									/>
								</svg>
							</div>
							<div>
								<h2 class="text-sm font-bold text-gray-800">
									Información del cliente
								</h2>
								<p class="text-xs text-gray-400 mt-0.5">
									El nombre debe ser único en el directorio
								</p>
							</div>
						</div>
						<div class="px-7 py-7">
							<div class="space-y-2">
								<label class="block text-sm font-semibold text-gray-700"
									>Nombre de la empresa
									<span class="text-rose-500">*</span></label
								>
								<input
									v-model="form.cli_nombre"
									type="text"
									maxlength="50"
									placeholder="Ej: Corporativo XYZ S.A. de C.V."
									class="w-full px-4 py-3 bg-gray-50 border rounded-xl text-sm text-gray-900 placeholder-gray-400 outline-none transition-all duration-200"
									:class="
										formErrors.cli_nombre
											? 'border-rose-400 bg-rose-50/40 focus:ring-2 focus:ring-rose-200'
											: 'border-gray-200 focus:bg-white focus:ring-2 focus:ring-[#312AFF]/15 focus:border-[#312AFF]'
									"
								/>
								<div class="flex items-center justify-between">
									<p
										v-if="formErrors.cli_nombre"
										class="text-xs text-rose-500 flex items-center gap-1"
									>
										<svg
											class="w-3.5 h-3.5 shrink-0"
											fill="currentColor"
											viewBox="0 0 20 20"
										>
											<path
												fill-rule="evenodd"
												d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
												clip-rule="evenodd"
											/>
										</svg>
										{{ formErrors.cli_nombre[0] }}
									</p>
									<p v-else class="text-xs text-gray-400">Máximo 50 caracteres</p>
									<span class="text-xs text-gray-400 tabular-nums"
										>{{ form.cli_nombre.length }}/50</span
									>
								</div>
							</div>
						</div>
					</div>

					<!-- Logo -->
					<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
						<div class="px-7 py-5 border-b border-gray-100 flex items-center gap-3">
							<div
								class="w-8 h-8 rounded-xl bg-violet-50 flex items-center justify-center shrink-0"
							>
								<svg
									class="w-4 h-4 text-violet-500"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
									stroke-width="2"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
									/>
								</svg>
							</div>
							<div>
								<h2 class="text-sm font-bold text-gray-800">Logotipo</h2>
								<p class="text-xs text-gray-400 mt-0.5">
									JPG, PNG, GIF, SVG · Máx. 3 MB
								</p>
							</div>
						</div>
						<div class="px-7 py-7">
							<div v-if="logoPreview" class="flex flex-col items-center gap-3">
								<div
									class="relative group w-52 h-36 rounded-2xl overflow-hidden border-2 border-gray-100 bg-gray-50 flex items-center justify-center p-5"
								>
									<img
										:src="logoPreview"
										alt="Logo"
										class="max-w-full max-h-full object-contain"
									/>
									<div
										class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-all duration-300 flex flex-col items-center justify-center gap-2 p-4"
									>
										<button
											type="button"
											@click="$refs.logoInput.click()"
											class="w-full py-1.5 bg-white/90 text-gray-800 text-xs font-semibold rounded-xl hover:bg-white transition-all shadow-lg"
										>
											Reemplazar
										</button>
										<button
											type="button"
											@click="removeLogo"
											class="w-full py-1.5 bg-rose-500/90 text-white text-xs font-semibold rounded-xl hover:bg-rose-500 transition-all shadow-lg"
										>
											Eliminar
										</button>
									</div>
								</div>
								<p class="text-xs text-gray-400">
									Pasa el cursor para ver opciones
								</p>
							</div>
							<div
								v-else
								@click="$refs.logoInput.click()"
								class="group border-2 border-dashed rounded-2xl p-10 text-center cursor-pointer transition-all duration-200"
								:class="
									formErrors.cli_logo
										? 'border-rose-300 bg-rose-50/30'
										: 'border-gray-200 hover:border-[#312AFF] hover:bg-[#312AFF]/[0.02]'
								"
							>
								<div
									class="w-14 h-14 rounded-2xl bg-gray-100 group-hover:bg-[#312AFF]/10 transition-colors mx-auto mb-4 flex items-center justify-center"
								>
									<svg
										class="w-7 h-7 text-gray-400 group-hover:text-[#312AFF] transition-colors"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
										stroke-width="1.5"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											d="M12 4v16m8-8H4"
										/>
									</svg>
								</div>
								<p
									class="text-sm font-semibold text-gray-700 group-hover:text-gray-900"
								>
									Selecciona el logotipo
								</p>
								<p class="text-xs text-gray-400 mt-1.5">
									Haz clic para explorar tus archivos
								</p>
							</div>
							<p
								v-if="formErrors.cli_logo"
								class="mt-2.5 text-xs text-rose-500 flex items-center gap-1"
							>
								<svg
									class="w-3.5 h-3.5 shrink-0"
									fill="currentColor"
									viewBox="0 0 20 20"
								>
									<path
										fill-rule="evenodd"
										d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
										clip-rule="evenodd"
									/>
								</svg>
								{{ formErrors.cli_logo[0] }}
							</p>
							<input
								ref="logoInput"
								type="file"
								accept="image/*"
								class="hidden"
								@change="handleLogoChange"
							/>
						</div>
					</div>
				</div>

				<!-- Lateral -->
				<div class="space-y-5">
					<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
						<div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
							<div
								class="w-8 h-8 rounded-xl bg-sky-50 flex items-center justify-center shrink-0"
							>
								<svg
									class="w-4 h-4 text-sky-500"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
									stroke-width="2"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
									/>
								</svg>
							</div>
							<div>
								<h2 class="text-sm font-bold text-gray-800">Publicación</h2>
								<p class="text-xs text-gray-400 mt-0.5">Visibilidad y fechas</p>
							</div>
						</div>
						<div class="px-6 py-6 space-y-5">
							<div class="space-y-2">
								<label
									class="block text-xs font-bold text-gray-500 uppercase tracking-wider"
									>Estado</label
								>
								<div
									class="flex rounded-xl border border-gray-200 overflow-hidden bg-gray-50 p-1 gap-1"
								>
									<button
										type="button"
										@click="form.cli_estatus = 'Guardado'"
										class="flex-1 py-2 rounded-lg text-xs font-bold transition-all duration-200"
										:class="
											form.cli_estatus === 'Guardado'
												? 'bg-white text-amber-600 shadow-sm border border-amber-200'
												: 'text-gray-400 hover:text-gray-600'
										"
									>
										Borrador
									</button>
									<button
										type="button"
										@click="form.cli_estatus = 'Publicado'"
										class="flex-1 py-2 rounded-lg text-xs font-bold transition-all duration-200"
										:class="
											form.cli_estatus === 'Publicado'
												? 'bg-white text-emerald-600 shadow-sm border border-emerald-200'
												: 'text-gray-400 hover:text-gray-600'
										"
									>
										Publicado
									</button>
								</div>
							</div>
							<div class="space-y-2">
								<label
									class="block text-xs font-bold text-gray-500 uppercase tracking-wider"
									>Orden</label
								>
								<input
									v-model="form.cli_orden"
									type="number"
									min="0"
									placeholder="Automático"
									class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 outline-none focus:bg-white focus:ring-2 focus:ring-[#312AFF]/15 focus:border-[#312AFF] transition-all duration-200"
								/>
								<p class="text-xs text-gray-400">
									Se coloca al final si se deja vacío
								</p>
							</div>
							<div class="space-y-2">
								<label
									class="block text-xs font-bold text-gray-500 uppercase tracking-wider"
									>Vigencia <span class="text-rose-500">*</span></label
								>
								<div class="grid grid-cols-2 gap-2.5">
									<div class="space-y-1.5">
										<p class="text-xs font-semibold text-gray-500">Desde</p>
										<input
											v-model="form.cli_fecha_publicacion"
											type="date"
											class="w-full px-3 py-2.5 bg-gray-50 border rounded-xl text-xs text-gray-900 outline-none transition-all duration-200"
											:class="
												formErrors.cli_fecha_publicacion
													? 'border-rose-400 bg-rose-50/40'
													: 'border-gray-200 focus:bg-white focus:ring-2 focus:ring-[#312AFF]/15 focus:border-[#312AFF]'
											"
										/>
										<p
											v-if="formErrors.cli_fecha_publicacion"
											class="text-xs text-rose-500 leading-snug"
										>
											{{ formErrors.cli_fecha_publicacion[0] }}
										</p>
									</div>
									<div class="space-y-1.5">
										<p class="text-xs font-semibold text-gray-500">Hasta</p>
										<input
											v-model="form.cli_fecha_terminacion"
											type="date"
											class="w-full px-3 py-2.5 bg-gray-50 border rounded-xl text-xs text-gray-900 outline-none transition-all duration-200"
											:class="
												formErrors.cli_fecha_terminacion
													? 'border-rose-400 bg-rose-50/40'
													: 'border-gray-200 focus:bg-white focus:ring-2 focus:ring-[#312AFF]/15 focus:border-[#312AFF]'
											"
										/>
										<p
											v-if="formErrors.cli_fecha_terminacion"
											class="text-xs text-rose-500 leading-snug"
										>
											{{ formErrors.cli_fecha_terminacion[0] }}
										</p>
									</div>
								</div>
								<p class="text-xs text-gray-400">La fecha de término es opcional</p>
							</div>
						</div>
					</div>

					<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
						<div class="px-6 py-6 space-y-3">
							<button
								@click="saveCliente('Publicado')"
								:disabled="loading"
								class="w-full inline-flex items-center justify-center gap-2.5 px-5 py-3.5 bg-[#312AFF] text-white text-sm font-bold rounded-xl hover:bg-[#2520e0] active:scale-[0.98] transition-all duration-150 shadow-md shadow-[#312AFF]/20 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
							>
								<svg
									v-if="loading"
									class="w-4 h-4 animate-spin"
									fill="none"
									viewBox="0 0 24 24"
								>
									<circle
										class="opacity-25"
										cx="12"
										cy="12"
										r="10"
										stroke="currentColor"
										stroke-width="4"
									/>
									<path
										class="opacity-75"
										fill="currentColor"
										d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
									/>
								</svg>
								<svg
									v-else
									class="w-4 h-4"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
									stroke-width="2.5"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										d="M5 13l4 4L19 7"
									/>
								</svg>
								Publicar cliente
							</button>
							<button
								@click="saveCliente('Guardado')"
								:disabled="loading"
								class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-gray-50 text-gray-700 text-sm font-semibold rounded-xl border border-gray-200 hover:bg-gray-100 hover:border-gray-300 active:scale-[0.98] transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
							>
								<svg
									class="w-4 h-4"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
									stroke-width="2"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"
									/>
								</svg>
								Guardar borrador
							</button>
							<div class="pt-1 border-t border-gray-100">
								<button
									@click="router.push('/clients')"
									:disabled="loading"
									class="w-full py-2.5 text-xs font-medium text-gray-400 hover:text-gray-600 transition-colors"
								>
									Cancelar y descartar cambios
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped>
.toast-slide-enter-active {
	transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-slide-leave-active {
	transition: all 0.3s ease;
}
.toast-slide-enter-from {
	opacity: 0;
	transform: translateX(20px) translateY(-5px);
}
.toast-slide-leave-to {
	opacity: 0;
	transform: translateX(20px);
}
</style>
