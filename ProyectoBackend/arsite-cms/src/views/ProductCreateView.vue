<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import {
	IMAGE_ACCEPT,
	clearFileInput,
	createFilePreview,
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
const isDragging = ref(false)

const form = ref({ pro_nombre: '', pro_imagen: null, pro_estatus: 'Guardado' })

const showToast = (message, type = 'success') => {
	toast.value = { show: true, message, type }
	setTimeout(() => (toast.value.show = false), 4000)
}

const processFile = async (file) => {
	const validation = validateImageFile(file, {
		label: 'La imagen',
		maxSizeMB: 2,
	})

	if (!validation.valid) {
		form.value.pro_imagen = null
		imagePreview.value = null
		setFieldError(formErrors, 'pro_imagen', validation.message)
		clearFileInput(imageInput)
		return
	}

	form.value.pro_imagen = file
	delete formErrors.value.pro_imagen
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
	form.value.pro_imagen = null
	imagePreview.value = null
	clearFileInput(imageInput)
}

const saveProducto = async (estatus = null) => {
	if (estatus) form.value.pro_estatus = estatus
	loading.value = true
	formErrors.value = {}

	if (!form.value.pro_nombre?.trim())
		formErrors.value.pro_nombre = ['El nombre del producto es obligatorio']
	if (!form.value.pro_imagen) formErrors.value.pro_imagen = ['La imagen es obligatoria']

	if (Object.keys(formErrors.value).length > 0) {
		logClientValidation('ProductCreateView', formErrors.value)
		showToast('Por favor completa todos los campos obligatorios', 'error')
		loading.value = false
		return
	}
	try {
		const formData = new FormData()
		formData.append('pro_nombre', form.value.pro_nombre)
		formData.append('pro_estatus', form.value.pro_estatus)
		if (form.value.pro_imagen instanceof File)
			formData.append('pro_imagen', form.value.pro_imagen)

		const response = await api.post('/productos', formData, {
			headers: { 'Content-Type': 'multipart/form-data' },
		})
		if (response.data.success) {
			showToast(response.data.message, 'success')
			setTimeout(() => router.push('/products'), 1400)
		}
	} catch (err) {
		if (err.response?.status === 422) formErrors.value = err.response.data.errors || {}
		showToast(err.response?.data?.message || 'Error al crear el producto', 'error')
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

		<div class="max-w-4xl mx-auto">
			<div class="mb-10">
				<button
					@click="router.push('/products')"
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
					Volver a Productos
				</button>
				<div class="flex items-start justify-between flex-wrap gap-4">
					<div>
						<h1 class="text-3xl font-bold text-[#1c2321] tracking-tight">
							Crear Producto
						</h1>
						<p class="mt-1.5 text-gray-500 text-sm">
							Agrega un nuevo producto al catálogo
						</p>
					</div>
					<div
						class="flex items-center gap-2 px-4 py-2.5 bg-white rounded-xl border border-gray-200 shadow-sm"
					>
						<span
							class="w-2 h-2 rounded-full"
							:class="
								form.pro_estatus === 'Publicado' ? 'bg-green-400' : 'bg-amber-400'
							"
						></span>
						<span class="text-xs font-semibold text-gray-600">{{
							form.pro_estatus === 'Publicado' ? 'Listo para publicar' : 'Borrador'
						}}</span>
					</div>
				</div>
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-6">
				<div class="space-y-5">
					<!-- Card nombre -->
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
										d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
									/>
								</svg>
							</div>
							<div>
								<h2 class="text-sm font-semibold text-[#1c2321]">
									Información del producto
								</h2>
								<p class="text-xs text-gray-400 mt-0.5">
									El nombre debe ser único en el catálogo
								</p>
							</div>
						</div>
						<div class="px-7 py-7">
							<div class="space-y-1.5">
								<label
									class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
									>Nombre del producto
									<span
										class="text-red-400 font-normal normal-case tracking-normal"
										>*</span
									></label
								>
								<input
									v-model="form.pro_nombre"
									type="text"
									maxlength="50"
									placeholder="Ej: Producto estrella premium"
									class="w-full px-4 py-3 bg-gray-50 border rounded-xl text-sm text-gray-900 placeholder-gray-400 outline-none transition-all duration-200 focus:bg-white focus:ring-2 focus:ring-[#312AFF]/20 focus:border-[#312AFF]"
									:class="
										formErrors.pro_nombre
											? 'border-red-300 bg-red-50/60'
											: 'border-gray-200'
									"
								/>
								<p
									v-if="formErrors.pro_nombre"
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
									{{ formErrors.pro_nombre[0] }}
								</p>
								<p class="text-xs text-gray-400">Máximo 50 caracteres</p>
							</div>
						</div>
					</div>

					<!-- Card imagen -->
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
									Imagen del producto
								</h2>
								<p class="text-xs text-gray-400 mt-0.5">JPG, PNG, GIF, SVG o WEBP · Máx 2 MB</p>
							</div>
						</div>
						<div class="px-7 py-6">
							<div
								v-if="imagePreview"
								class="flex flex-col sm:flex-row gap-5 items-start"
							>
								<!-- Preview cuadrado -->
								<div
									class="relative group rounded-xl overflow-hidden border border-gray-200 w-40 h-40 shrink-0"
								>
									<img
										:src="imagePreview"
										alt="Preview"
										class="w-full h-full object-cover"
									/>
									<div
										class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 flex-col"
									>
										<button
											type="button"
											@click="$refs.imageInput.click()"
											class="px-3 py-1.5 bg-white text-gray-800 text-xs font-semibold rounded-lg hover:bg-gray-100 transition-all"
										>
											Reemplazar
										</button>
										<button
											type="button"
											@click="removeImage"
											class="px-3 py-1.5 bg-red-500 text-white text-xs font-semibold rounded-lg hover:bg-red-600 transition-all"
										>
											Eliminar
										</button>
									</div>
								</div>
								<div class="pt-1">
									<p class="text-sm font-semibold text-gray-700">
										Imagen seleccionada
									</p>
									<p class="text-xs text-gray-400 mt-1 break-all">
										{{ form.pro_imagen?.name }}
									</p>
									<p class="text-xs text-gray-400">
										{{
											form.pro_imagen
												? (form.pro_imagen.size / 1024).toFixed(0) + ' KB'
												: ''
										}}
									</p>
								</div>
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
									formErrors.pro_imagen
										? 'border-red-300 bg-red-50/40'
										: 'border-gray-200 hover:border-[#312AFF] hover:bg-gray-50',
								]"
							>
								<div class="py-12 flex flex-col items-center gap-3">
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
								v-if="formErrors.pro_imagen"
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
								{{ formErrors.pro_imagen[0] }}
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
										d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
									/>
								</svg>
							</div>
							<h2 class="text-sm font-semibold text-[#1c2321]">Estado</h2>
						</div>
						<div class="px-6 py-5">
							<div class="grid grid-cols-2 gap-2">
								<button
									type="button"
									@click="form.pro_estatus = 'Guardado'"
									class="flex items-center justify-center gap-2 py-2.5 rounded-xl border text-sm font-medium transition-all duration-150"
									:class="
										form.pro_estatus === 'Guardado'
											? 'border-amber-300 bg-amber-50 text-amber-700 shadow-sm'
											: 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50'
									"
								>
									<span class="w-2 h-2 rounded-full bg-amber-400"></span>Borrador
								</button>
								<button
									type="button"
									@click="form.pro_estatus = 'Publicado'"
									class="flex items-center justify-center gap-2 py-2.5 rounded-xl border text-sm font-medium transition-all duration-150"
									:class="
										form.pro_estatus === 'Publicado'
											? 'border-green-300 bg-green-50 text-green-700 shadow-sm'
											: 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50'
									"
								>
									<span class="w-2 h-2 rounded-full bg-green-400"></span>Publicado
								</button>
							</div>
						</div>
					</div>

					<div
						class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"
					>
						<div class="px-6 py-5 space-y-3">
							<button
								@click="saveProducto('Publicado')"
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
								Publicar producto
							</button>
							<button
								@click="saveProducto('Guardado')"
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
									@click="router.push('/products')"
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
							El nombre del producto debe ser <strong>único</strong> en el catálogo.
						</p>
					</div>
				</div>
			</div>
		</div>
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
</style>
