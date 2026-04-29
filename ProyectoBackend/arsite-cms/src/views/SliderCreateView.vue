<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import {
	IMAGE_ACCEPT,
	clearFileInput,
	createFilePreview,
	isValidUrl,
	logClientValidation,
	setFieldError,
	validateImageFile,
} from '@/utils/formValidation'

const router = useRouter()

const loading = ref(false)
const imagePreview = ref(null)
const imageInput = ref(null)
const formErrors = ref({})
const toast = ref({ show: false, message: '', type: 'success' })
const showPreviewModal = ref(false)
const isDragging = ref(false)

const form = ref({
	des_titulo: '',
	des_subtitulo: '',
	des_texto_boton: '',
	des_enlace_boton: '',
	des_imagen: null,
	des_fecha_publicacion: '',
	des_fecha_terminacion: '',
	des_orden: '',
	des_estatus: 'Guardado',
})

const previewSlider = computed(() => ({
	des_titulo: form.value.des_titulo,
	des_subtitulo: form.value.des_subtitulo,
	des_texto_boton: form.value.des_texto_boton,
	des_imagen: imagePreview.value,
}))

const showToast = (message, type = 'success') => {
	toast.value = { show: true, message, type }
	setTimeout(() => (toast.value.show = false), 4000)
}

const processFile = async (file) => {
	const validation = validateImageFile(file, { label: 'La imagen', maxSizeMB: 3 })

	if (!validation.valid) {
		form.value.des_imagen = null
		imagePreview.value = null
		setFieldError(formErrors, 'des_imagen', validation.message)
		clearFileInput(imageInput)
		return
	}

	form.value.des_imagen = file
	delete formErrors.value.des_imagen
	imagePreview.value = await createFilePreview(file)
}

const handleImageChange = async (e) => {
	const f = e.target.files[0]
	if (f) await processFile(f)
}
const handleDrop = async (e) => {
	isDragging.value = false
	const f = e.dataTransfer.files[0]
	if (f) await processFile(f)
}
const removeImage = () => {
	form.value.des_imagen = null
	imagePreview.value = null
	clearFileInput(imageInput)
}

const saveSlider = async (estatus = null) => {
	if (estatus) form.value.des_estatus = estatus
	loading.value = true
	formErrors.value = {}

	const required = {
		des_titulo: 'El título es obligatorio',
		des_subtitulo: 'El subtítulo es obligatorio',
		des_texto_boton: 'El texto del botón es obligatorio',
		des_enlace_boton: 'El enlace URL es obligatorio',
		des_fecha_publicacion: 'La fecha de publicación es obligatoria',
	}
	for (const [campo, mensaje] of Object.entries(required)) {
		if (!form.value[campo] || form.value[campo].trim() === '')
			formErrors.value[campo] = [mensaje]
	}
	if (form.value.des_enlace_boton && !isValidUrl(form.value.des_enlace_boton)) {
		formErrors.value.des_enlace_boton = ['Ingresa una URL válida que comience con http:// o https://']
	}
	if (!form.value.des_imagen) formErrors.value.des_imagen = ['La imagen es obligatoria']

	if (Object.keys(formErrors.value).length > 0) {
		logClientValidation('SliderCreateView', formErrors.value)
		showToast('Por favor completa todos los campos obligatorios', 'error')
		loading.value = false
		return
	}

	try {
		const formData = new FormData()
		Object.keys(form.value).forEach((key) => {
			if (key === 'des_imagen') return
			if (form.value[key] !== null && form.value[key] !== '')
				formData.append(key, form.value[key])
		})
		if (form.value.des_imagen instanceof File)
			formData.append('des_imagen', form.value.des_imagen)

		const response = await api.post('/destacados', formData, {
			headers: { 'Content-Type': 'multipart/form-data' },
		})
		if (response.data.success) {
			showToast(response.data.message, 'success')
			setTimeout(() => router.push('/sliders'), 1400)
		}
	} catch (err) {
		if (err.response?.status === 422) formErrors.value = err.response.data.errors || {}
		showToast(err.response?.data?.message || 'Error al crear el slider', 'error')
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<div class="min-h-screen bg-[#f5f6fa] px-4 py-10">
		<transition name="toast-in">
			<div
				v-if="toast.show"
				class="fixed top-5 right-5 z-50 w-full max-w-sm pointer-events-none"
			>
				<div
					class="flex items-start gap-3 px-5 py-4 rounded-2xl shadow-2xl border text-sm font-medium"
					:class="
						toast.type === 'success'
							? 'bg-green-50 border-green-200 text-green-800'
							: 'bg-red-50 border-red-200 text-red-800'
					"
				>
					<div
						class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5"
						:class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
					>
						<svg
							class="w-3 h-3 text-white"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
							stroke-width="3"
						>
							<path
								v-if="toast.type === 'success'"
								stroke-linecap="round"
								stroke-linejoin="round"
								d="M5 13l4 4L19 7"
							/>
							<path
								v-else
								stroke-linecap="round"
								stroke-linejoin="round"
								d="M6 18L18 6M6 6l12 12"
							/>
						</svg>
					</div>
					<p>{{ toast.message }}</p>
				</div>
			</div>
		</transition>

		<div class="max-w-5xl mx-auto">
			<div class="mb-10">
				<button
					@click="router.push('/sliders')"
					class="group inline-flex items-center gap-2.5 mb-6 text-sm font-medium text-gray-500 hover:text-[#312AFF] transition-colors duration-200"
				>
					<span
						class="w-8 h-8 rounded-full border border-gray-200 bg-white shadow-sm flex items-center justify-center group-hover:border-[#312AFF] group-hover:bg-[#312AFF]/5 transition-all duration-200"
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
								d="M15 19l-7-7 7-7"
							/>
						</svg>
					</span>
					Volver a Sliders
				</button>
				<div class="flex items-start justify-between flex-wrap gap-4">
					<div>
						<h1 class="text-3xl font-bold text-[#1c2321] tracking-tight">
							Crear Slider
						</h1>
						<p class="mt-1.5 text-gray-500 text-sm">
							Agrega un nuevo elemento al carrusel de destacados
						</p>
					</div>
					<div
						class="flex items-center gap-2 px-4 py-2.5 bg-white rounded-xl border border-gray-200 shadow-sm"
					>
						<span
							class="w-2 h-2 rounded-full transition-colors"
							:class="
								form.des_estatus === 'Publicado' ? 'bg-green-400' : 'bg-amber-400'
							"
						></span>
						<span class="text-xs font-semibold text-gray-600">{{
							form.des_estatus === 'Publicado' ? 'Listo para publicar' : 'Borrador'
						}}</span>
					</div>
				</div>
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6">
				<div class="space-y-5">
					<!-- Card Contenido -->
					<div
						class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"
					>
						<div class="px-7 py-5 border-b border-gray-100 flex items-center gap-3">
							<div
								class="w-8 h-8 rounded-lg bg-[#312AFF]/10 flex items-center justify-center shrink-0"
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
										d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
									/>
								</svg>
							</div>
							<div>
								<h2 class="text-sm font-semibold text-[#1c2321]">
									Contenido del slider
								</h2>
								<p class="text-xs text-gray-400 mt-0.5">
									Texto que se mostrará sobre la imagen
								</p>
							</div>
						</div>
						<div class="px-7 py-7 space-y-6">
							<div class="space-y-1.5">
								<label
									class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
									>Título
									<span
										class="text-red-400 font-normal normal-case tracking-normal"
										>*</span
									></label
								>
								<input
									v-model="form.des_titulo"
									type="text"
									maxlength="100"
									placeholder="Ej: Nuestros servicios destacados"
									class="w-full px-4 py-3 bg-gray-50 border rounded-xl text-sm text-gray-900 placeholder-gray-400 outline-none transition-all duration-200 focus:bg-white focus:ring-2 focus:ring-[#312AFF]/20 focus:border-[#312AFF]"
									:class="
										formErrors.des_titulo
											? 'border-red-300 bg-red-50/60'
											: 'border-gray-200'
									"
								/>
								<p
									v-if="formErrors.des_titulo"
									class="text-xs text-red-500 flex items-center gap-1"
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
									{{ formErrors.des_titulo[0] }}
								</p>
							</div>
							<div class="space-y-1.5">
								<label
									class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
									>Subtítulo
									<span
										class="text-red-400 font-normal normal-case tracking-normal"
										>*</span
									></label
								>
								<input
									v-model="form.des_subtitulo"
									type="text"
									maxlength="200"
									placeholder="Ej: Descubre todo lo que podemos hacer por ti"
									class="w-full px-4 py-3 bg-gray-50 border rounded-xl text-sm text-gray-900 placeholder-gray-400 outline-none transition-all duration-200 focus:bg-white focus:ring-2 focus:ring-[#312AFF]/20 focus:border-[#312AFF]"
									:class="
										formErrors.des_subtitulo
											? 'border-red-300 bg-red-50/60'
											: 'border-gray-200'
									"
								/>
								<p
									v-if="formErrors.des_subtitulo"
									class="text-xs text-red-500 flex items-center gap-1"
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
									{{ formErrors.des_subtitulo[0] }}
								</p>
							</div>
							<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
								<div class="space-y-1.5">
									<label
										class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
										>Texto del botón
										<span
											class="text-red-400 font-normal normal-case tracking-normal"
											>*</span
										></label
									>
									<input
										v-model="form.des_texto_boton"
										type="text"
										maxlength="50"
										placeholder="Ej: Conoce más"
										class="w-full px-4 py-3 bg-gray-50 border rounded-xl text-sm text-gray-900 placeholder-gray-400 outline-none transition-all duration-200 focus:bg-white focus:ring-2 focus:ring-[#312AFF]/20 focus:border-[#312AFF]"
										:class="
											formErrors.des_texto_boton
												? 'border-red-300 bg-red-50/60'
												: 'border-gray-200'
										"
									/>
									<p
										v-if="formErrors.des_texto_boton"
										class="text-xs text-red-500"
									>
										{{ formErrors.des_texto_boton[0] }}
									</p>
								</div>
								<div class="space-y-1.5">
									<label
										class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
										>Enlace URL
										<span
											class="text-red-400 font-normal normal-case tracking-normal"
											>*</span
										></label
									>
									<div class="relative">
										<svg
											class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
											stroke-width="2"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
											/>
										</svg>
										<input
											v-model="form.des_enlace_boton"
											type="url"
											maxlength="255"
											placeholder="https://..."
											class="w-full pl-9 pr-4 py-3 bg-gray-50 border rounded-xl text-sm text-gray-900 placeholder-gray-400 outline-none transition-all duration-200 focus:bg-white focus:ring-2 focus:ring-[#312AFF]/20 focus:border-[#312AFF]"
											:class="
												formErrors.des_enlace_boton
													? 'border-red-300 bg-red-50/60'
													: 'border-gray-200'
											"
										/>
									</div>
									<p
										v-if="formErrors.des_enlace_boton"
										class="text-xs text-red-500"
									>
										{{ formErrors.des_enlace_boton[0] }}
									</p>
								</div>
							</div>
						</div>
					</div>

					<!-- Card Imagen -->
					<div
						class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"
					>
						<div class="px-7 py-5 border-b border-gray-100 flex items-center gap-3">
							<div
								class="w-8 h-8 rounded-lg bg-violet-50 flex items-center justify-center shrink-0"
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
								<h2 class="text-sm font-semibold text-[#1c2321]">
									Imagen del slider
								</h2>
								<p class="text-xs text-gray-400 mt-0.5">
										JPG, PNG, GIF, SVG o WEBP · Máx 3 MB · Recomendado: 1920 × 600 px
								</p>
							</div>
						</div>
						<div class="px-7 py-6">
							<div v-if="imagePreview" class="space-y-3">
								<div
									class="relative group rounded-xl overflow-hidden border border-gray-200"
								>
									<div class="aspect-[21/9]">
										<img
											:src="imagePreview"
											alt="Preview"
											class="w-full h-full object-cover"
										/>
									</div>
									<div
										class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-between p-4"
									>
										<span
											class="text-white text-xs font-medium bg-black/30 backdrop-blur-sm px-3 py-1.5 rounded-full truncate max-w-[50%]"
											>{{
												form.des_imagen?.name || 'Imagen seleccionada'
											}}</span
										>
										<div class="flex gap-2">
											<button
												type="button"
												@click="$refs.imageInput.click()"
												class="flex items-center gap-1.5 px-3 py-1.5 bg-white text-gray-800 text-xs font-semibold rounded-lg hover:bg-gray-100 transition-all"
											>
												<svg
													class="w-3.5 h-3.5"
													fill="none"
													viewBox="0 0 24 24"
													stroke="currentColor"
													stroke-width="2"
												>
													<path
														stroke-linecap="round"
														stroke-linejoin="round"
														d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
													/>
												</svg>
												Reemplazar
											</button>
											<button
												type="button"
												@click="removeImage"
												class="flex items-center gap-1.5 px-3 py-1.5 bg-red-500 text-white text-xs font-semibold rounded-lg hover:bg-red-600 transition-all"
											>
												<svg
													class="w-3.5 h-3.5"
													fill="none"
													viewBox="0 0 24 24"
													stroke="currentColor"
													stroke-width="2"
												>
													<path
														stroke-linecap="round"
														stroke-linejoin="round"
														d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
													/>
												</svg>
												Eliminar
											</button>
										</div>
									</div>
								</div>
								<button
									type="button"
									@click="showPreviewModal = true"
									class="flex items-center gap-1.5 text-xs text-[#312AFF] hover:text-[#2821dd] font-medium transition-colors"
								>
									<svg
										class="w-3.5 h-3.5"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
										stroke-width="2"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
										/>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
										/>
									</svg>
									Ver preview del slider completo
								</button>
							</div>
							<div
								v-else
								@click="$refs.imageInput.click()"
								@dragover.prevent="isDragging = true"
								@dragleave="isDragging = false"
								@drop.prevent="handleDrop"
								class="border-2 border-dashed rounded-xl cursor-pointer transition-all duration-200 group"
								:class="[
									isDragging
										? 'border-[#312AFF] bg-[#312AFF]/5 scale-[1.01]'
										: '',
									formErrors.des_imagen
										? 'border-red-300 bg-red-50/40'
										: 'border-gray-200 hover:border-[#312AFF] hover:bg-gray-50',
								]"
							>
								<div class="py-14 flex flex-col items-center gap-3">
									<div
										class="w-14 h-14 rounded-2xl bg-gray-100 group-hover:bg-[#312AFF]/10 transition-colors flex items-center justify-center"
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
												d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"
											/>
										</svg>
									</div>
									<div class="text-center">
										<p
											class="text-sm font-semibold text-gray-700 group-hover:text-[#312AFF] transition-colors"
										>
											Arrastra tu imagen aquí
										</p>
										<p class="text-xs text-gray-400 mt-1">
											o
											<span
												class="text-[#312AFF] underline underline-offset-2"
												>haz clic para seleccionar</span
											>
										</p>
									</div>
								</div>
							</div>
							<p
								v-if="formErrors.des_imagen"
								class="mt-2 text-xs text-red-500 flex items-center gap-1"
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
								{{ formErrors.des_imagen[0] }}
							</p>
							<input
								ref="imageInput"
								type="file"
							:accept="IMAGE_ACCEPT"
								class="hidden"
								@change="handleImageChange"
							/>
						</div>
					</div>
				</div>

				<!-- Sidebar -->
				<div class="space-y-5">
					<div
						class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"
					>
						<div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
							<div
								class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0"
							>
								<svg
									class="w-4 h-4 text-amber-500"
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
							<h2 class="text-sm font-semibold text-[#1c2321]">Publicación</h2>
						</div>
						<div class="px-6 py-6 space-y-5">
							<div class="space-y-2">
								<label
									class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
									>Estado</label
								>
								<div class="grid grid-cols-2 gap-2">
									<button
										type="button"
										@click="form.des_estatus = 'Guardado'"
										class="flex items-center justify-center gap-2 py-2.5 rounded-xl border text-sm font-medium transition-all duration-150"
										:class="
											form.des_estatus === 'Guardado'
												? 'border-amber-300 bg-amber-50 text-amber-700 shadow-sm'
												: 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50'
										"
									>
										<span class="w-2 h-2 rounded-full bg-amber-400"></span
										>Borrador
									</button>
									<button
										type="button"
										@click="form.des_estatus = 'Publicado'"
										class="flex items-center justify-center gap-2 py-2.5 rounded-xl border text-sm font-medium transition-all duration-150"
										:class="
											form.des_estatus === 'Publicado'
												? 'border-green-300 bg-green-50 text-green-700 shadow-sm'
												: 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50'
										"
									>
										<span class="w-2 h-2 rounded-full bg-green-400"></span
										>Publicado
									</button>
								</div>
							</div>
							<div class="space-y-1.5">
								<label
									class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
									>Orden en carrusel</label
								>
								<input
									v-model="form.des_orden"
									type="number"
									min="0"
									placeholder="Automático"
									class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 outline-none transition-all duration-200 focus:bg-white focus:ring-2 focus:ring-[#312AFF]/20 focus:border-[#312AFF]"
								/>
								<p v-if="formErrors.des_orden" class="text-xs text-red-500">
									{{ formErrors.des_orden[0] }}
								</p>
								<p class="text-xs text-gray-400">
									Vacío = se agrega al final automáticamente
								</p>
							</div>
							<div class="space-y-2">
								<label
									class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
									>Vigencia
									<span
										class="text-red-400 font-normal normal-case tracking-normal"
										>*inicio requerido</span
									></label
								>
								<div class="grid grid-cols-2 gap-3">
									<div class="space-y-1">
										<span class="text-xs text-gray-400 font-medium">Desde</span>
										<input
											v-model="form.des_fecha_publicacion"
											type="date"
											class="w-full px-3 py-2.5 bg-gray-50 border rounded-xl text-xs text-gray-900 outline-none transition-all duration-200 focus:bg-white focus:ring-2 focus:ring-[#312AFF]/20 focus:border-[#312AFF]"
											:class="
												formErrors.des_fecha_publicacion
													? 'border-red-300 bg-red-50/60'
													: 'border-gray-200'
											"
										/>
										<p
											v-if="formErrors.des_fecha_publicacion"
											class="text-[10px] text-red-500 leading-tight"
										>
											{{ formErrors.des_fecha_publicacion[0] }}
										</p>
									</div>
									<div class="space-y-1">
										<span class="text-xs text-gray-400 font-medium"
											>Hasta
											<em class="not-italic text-gray-300">(opc.)</em></span
										>
										<input
											v-model="form.des_fecha_terminacion"
											type="date"
											class="w-full px-3 py-2.5 bg-gray-50 border rounded-xl text-xs text-gray-900 outline-none transition-all duration-200 focus:bg-white focus:ring-2 focus:ring-[#312AFF]/20 focus:border-[#312AFF]"
											:class="
												formErrors.des_fecha_terminacion
													? 'border-red-300 bg-red-50/60'
													: 'border-gray-200'
											"
										/>
										<p
											v-if="formErrors.des_fecha_terminacion"
											class="text-[10px] text-red-500 leading-tight"
										>
											{{ formErrors.des_fecha_terminacion[0] }}
										</p>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div
						class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"
					>
						<div class="px-6 py-5 space-y-3">
							<button
								@click="saveSlider('Publicado')"
								:disabled="loading"
								class="w-full flex items-center justify-center gap-2.5 px-5 py-3 bg-[#312AFF] text-white text-sm font-semibold rounded-xl hover:bg-[#2821dd] active:scale-[0.98] transition-all duration-150 shadow-md shadow-[#312AFF]/20 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
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
								Publicar slider
							</button>
							<button
								@click="saveSlider('Guardado')"
								:disabled="loading"
								class="w-full flex items-center justify-center gap-2 px-5 py-3 border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 hover:border-gray-300 active:scale-[0.98] transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
							>
								<svg
									class="w-4 h-4 text-gray-400"
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
									@click="router.push('/sliders')"
									:disabled="loading"
									class="w-full py-2.5 text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium"
								>
									Cancelar y volver
								</button>
							</div>
						</div>
					</div>

					<div
						class="flex gap-3 px-4 py-3.5 bg-blue-50 border border-blue-100 rounded-2xl"
					>
						<svg
							class="w-4 h-4 text-blue-400 shrink-0 mt-0.5"
							fill="currentColor"
							viewBox="0 0 20 20"
						>
							<path
								fill-rule="evenodd"
								d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
								clip-rule="evenodd"
							/>
						</svg>
						<p class="text-xs text-blue-600 leading-relaxed">
							Los sliders en <strong>Borrador</strong> no son visibles en el sitio
							hasta ser publicados.
						</p>
					</div>
				</div>
			</div>
		</div>

		<transition name="fade">
			<div
				v-if="showPreviewModal"
				class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
				@click.self="showPreviewModal = false"
			>
				<div class="bg-white rounded-2xl overflow-hidden w-full max-w-4xl shadow-2xl">
					<div
						class="flex items-center justify-between px-6 py-4 border-b border-gray-100"
					>
						<div>
							<h3 class="font-semibold text-gray-900 text-sm">
								Vista previa del slider
							</h3>
							<p class="text-xs text-gray-400 mt-0.5">
								Así se verá en el carrusel del sitio
							</p>
						</div>
						<button
							@click="showPreviewModal = false"
							class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors"
						>
							<svg
								class="w-4 h-4 text-gray-500"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
								stroke-width="2"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									d="M6 18L18 6M6 6l12 12"
								/>
							</svg>
						</button>
					</div>
					<div class="relative aspect-[21/9] bg-gray-900">
						<img
							v-if="previewSlider.des_imagen"
							:src="previewSlider.des_imagen"
							alt="Preview"
							class="absolute inset-0 w-full h-full object-cover opacity-90"
						/>
						<div
							class="absolute inset-0 bg-gradient-to-r from-black/65 to-black/10 flex flex-col justify-center px-12"
						>
							<p
								class="text-white/50 text-xs font-semibold mb-3 uppercase tracking-widest"
							>
								Vista previa
							</p>
							<h2 class="text-white text-2xl lg:text-3xl font-bold mb-2 max-w-lg">
								{{
									previewSlider.des_titulo || 'Aquí aparece el título del slider'
								}}
							</h2>
							<p class="text-white/75 text-base mb-6 max-w-md">
								{{
									previewSlider.des_subtitulo ||
									'Aquí aparece el subtítulo descriptivo'
								}}
							</p>
							<div>
								<span
									class="inline-block px-6 py-2.5 bg-[#312AFF] text-white font-semibold rounded-lg text-sm"
								>
									{{ previewSlider.des_texto_boton || 'Ver más' }}
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</transition>
	</div>
</template>

<style scoped>
.toast-in-enter-active {
	transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-in-leave-active {
	transition: all 0.2s ease;
}
.toast-in-enter-from {
	opacity: 0;
	transform: translateX(20px) translateY(-8px);
}
.toast-in-leave-to {
	opacity: 0;
	transform: translateX(20px);
}
.fade-enter-active {
	transition: opacity 0.25s ease;
}
.fade-leave-active {
	transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
	opacity: 0;
}
</style>
