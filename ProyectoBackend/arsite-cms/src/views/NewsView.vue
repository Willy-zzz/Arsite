<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

// Estado
const noticias = ref([])
const loading = ref(false)
const error = ref(null)
const showModal = ref(false)
const showDeleteConfirm = ref(false)
const showAdvancedFilters = ref(false)
const showBulkDeleteConfirm = ref(false)
const showBulkStatusConfirm = ref(false)
const modalMode = ref('view') // 'edit', 'view'
const selectedNoticia = ref(null)
const noticiaToDelete = ref(null)
const selectedNoticias = ref([])
const activeTab = ref('general')
const bulkStatusTarget = ref('')

// Paginación
const pagination = ref({
	current_page: 1,
	last_page: 1,
	per_page: 15,
	total: 0,
})

// Filtros
const filters = ref({
	search: '',
	estatus: '',
	mine: false,
	fecha_desde: '',
	fecha_hasta: '',
	sort_by: 'not_publicacion',
	sort_direction: 'desc',
})

// Formulario
const form = ref({
	not_titulo: '',
	not_subtitulo: '',
	not_descripcion: '',
	not_portada: null,
	not_imagen: null,
	not_video: '',
	not_publicacion: '',
	not_estatus: 'Guardado',
})

const formErrors = ref({})
const portadaPreview = ref(null)
const imagenPreview = ref(null)
const portadaInput = ref(null)
const imagenInput = ref(null)

// Computed
const hasAdvancedFiltersActive = computed(() => {
	return (
		filters.value.search ||
		filters.value.mine ||
		filters.value.fecha_desde ||
		filters.value.fecha_hasta
	)
})

const allSelected = computed({
	get: () => noticias.value.length > 0 && selectedNoticias.value.length === noticias.value.length,
	set: (value) => {
		if (value) {
			selectedNoticias.value = noticias.value.map((n) => n.not_id)
		} else {
			selectedNoticias.value = []
		}
	},
})

const hasSelectedNoticias = computed(() => selectedNoticias.value.length > 0)

const displayRange = computed(() => {
	if (noticias.value.length === 0) return '0-0'
	const start = (pagination.value.current_page - 1) * pagination.value.per_page + 1
	const end = Math.min(start + noticias.value.length - 1, pagination.value.total)
	return `${start}-${end}`
})

// Métodos de filtros
const setEstatusQuickFilter = (status) => {
	filters.value.estatus = status
	applyFilters()
}

const setSorting = (column) => {
	if (filters.value.sort_by === column) {
		filters.value.sort_direction = filters.value.sort_direction === 'asc' ? 'desc' : 'asc'
	} else {
		filters.value.sort_by = column
		filters.value.sort_direction = 'desc'
	}
	applyFilters()
}

const applyFilters = () => {
	pagination.value.current_page = 1
	fetchNoticias()
}

const clearAdvancedFilters = () => {
	filters.value.search = ''
	filters.value.mine = false
	filters.value.fecha_desde = ''
	filters.value.fecha_hasta = ''
	applyFilters()
}

// Métodos principales
const fetchNoticias = async () => {
	loading.value = true
	error.value = null

	try {
		const params = {
			page: pagination.value.current_page,
			per_page: pagination.value.per_page,
			...filters.value,
		}

		const response = await api.get('/noticias', { params })

		if (response.data.success) {
			noticias.value = response.data.data.data
			pagination.value = {
				current_page: response.data.data.current_page,
				last_page: response.data.data.last_page,
				per_page: response.data.data.per_page,
				total: response.data.data.total,
			}
		}
	} catch (err) {
		error.value = err.response?.data?.message || 'Error al cargar noticias'
		console.error('Error fetching noticias:', err)
	} finally {
		loading.value = false
	}
}

const openModal = (mode, noticia = null) => {
	modalMode.value = mode
	selectedNoticia.value = noticia
	formErrors.value = {}
	activeTab.value = 'general'

	if (mode === 'edit' && noticia) {
		form.value = {
			not_titulo: noticia.not_titulo,
			not_subtitulo: noticia.not_subtitulo || '',
			not_descripcion: noticia.not_descripcion,
			not_portada: null,
			not_imagen: null,
			not_video: noticia.not_video || '',
			not_publicacion: formatDateTimeForInput(noticia.not_publicacion),
			not_estatus: noticia.not_estatus,
		}
		portadaPreview.value = noticia.not_portada ? getImageUrl(noticia.not_portada) : null
		imagenPreview.value = noticia.not_imagen ? getImageUrl(noticia.not_imagen) : null
	}

	showModal.value = true
}

const closeModal = () => {
	showModal.value = false
	selectedNoticia.value = null
	resetForm()
}

const resetForm = () => {
	form.value = {
		not_titulo: '',
		not_subtitulo: '',
		not_descripcion: '',
		not_portada: null,
		not_imagen: null,
		not_video: '',
		not_publicacion: '',
		not_estatus: 'Guardado',
	}
	portadaPreview.value = null
	imagenPreview.value = null
	formErrors.value = {}
}

const handlePortadaChange = (event) => {
	const file = event.target.files[0]
	if (file) {
		form.value.not_portada = file
		const reader = new FileReader()
		reader.onload = (e) => {
			portadaPreview.value = e.target.result
		}
		reader.readAsDataURL(file)
	}
}

const handleImagenChange = (event) => {
	const file = event.target.files[0]
	if (file) {
		form.value.not_imagen = file
		const reader = new FileReader()
		reader.onload = (e) => {
			imagenPreview.value = e.target.result
		}
		reader.readAsDataURL(file)
	}
}

const saveNoticia = async () => {
	loading.value = true
	formErrors.value = {}

	try {
		// VALIDACIÓN FRONTEND
		if (!form.value.not_titulo || form.value.not_titulo.trim() === '') {
			formErrors.value.not_titulo = ['El título es obligatorio']
		}
		if (!form.value.not_descripcion || form.value.not_descripcion.trim() === '') {
			formErrors.value.not_descripcion = ['La descripción es obligatoria']
		}

		if (Object.keys(formErrors.value).length > 0) {
			const camposVacios = Object.values(formErrors.value)
				.map((err) => err[0])
				.join('\n• ')
			showToast(
				`Por favor completa los siguientes campos obligatorios:\n• ${camposVacios}`,
				'error',
			)
			loading.value = false
			return
		}

		// PREPARAR DATOS
		const formData = new FormData()

		Object.keys(form.value).forEach((key) => {
			if (key === 'not_portada' || key === 'not_imagen') return
			if (form.value[key] !== null && form.value[key] !== '') {
				formData.append(key, form.value[key])
			}
		})

		if (form.value.not_portada instanceof File) {
			formData.append('not_portada', form.value.not_portada)
		}
		if (form.value.not_imagen instanceof File) {
			formData.append('not_imagen', form.value.not_imagen)
		}

		formData.append('_method', 'PUT')
		const response = await api.post(`/noticias/${selectedNoticia.value.not_id}`, formData, {
			headers: { 'Content-Type': 'multipart/form-data' },
		})

		if (response.data.success) {
			closeModal()
			fetchNoticias()
			showToast(response.data.message, 'success')
		}
	} catch (err) {
		if (err.response?.status === 422) {
			formErrors.value = err.response.data.errors || {}
		}
		error.value = err.response?.data?.message || 'Error al guardar noticia'
		showToast(error.value, 'error')
	} finally {
		loading.value = false
	}
}

const confirmDelete = (noticia) => {
	noticiaToDelete.value = noticia
	showDeleteConfirm.value = true
}

const handleDeleteSelected = () => {
	if (selectedNoticias.value.length === 0) return

	if (selectedNoticias.value.length === 1) {
		const id = selectedNoticias.value[0]
		const noticia = noticias.value.find((n) => n.not_id === id)
		if (noticia) {
			noticiaToDelete.value = noticia
			showDeleteConfirm.value = true
		}
	} else {
		showBulkDeleteConfirm.value = true
	}
}

const deleteNoticia = async () => {
	if (!noticiaToDelete.value) return

	loading.value = true
	try {
		const response = await api.delete(`/noticias/${noticiaToDelete.value.not_id}`)

		if (response.data.success) {
			fetchNoticias()
			showToast(response.data.message, 'success')
			selectedNoticias.value = []
		}
	} catch (err) {
		showToast(err.response?.data?.message || 'Error al eliminar noticia', 'error')
	} finally {
		loading.value = false
		showDeleteConfirm.value = false
		noticiaToDelete.value = null
	}
}

const bulkDelete = async () => {
	loading.value = true
	try {
		const response = await api.delete('/noticias/bulk-delete', {
			data: { ids: selectedNoticias.value },
		})

		if (response.data.success) {
			selectedNoticias.value = []
			fetchNoticias()
			showToast(response.data.message, 'success')
		}
	} catch (err) {
		showToast(err.response?.data?.message || 'Error al eliminar noticias', 'error')
	} finally {
		loading.value = false
		showBulkDeleteConfirm.value = false
	}
}

// Cambio masivo de estado
const openBulkStatus = (estatus) => {
	bulkStatusTarget.value = estatus
	showBulkStatusConfirm.value = true
}

const bulkUpdateStatus = async () => {
	loading.value = true
	try {
		const response = await api.put('/noticias/bulk-status', {
			ids: selectedNoticias.value,
			estatus: bulkStatusTarget.value,
		})

		if (response.data.success) {
			selectedNoticias.value = []
			fetchNoticias()
			showToast(response.data.message, 'success')
		}
	} catch (err) {
		showToast(err.response?.data?.message || 'Error al actualizar estados', 'error')
	} finally {
		loading.value = false
		showBulkStatusConfirm.value = false
		bulkStatusTarget.value = ''
	}
}

// Exportar a CSV
const exportToCSV = () => {
	try {
		const headers = ['ID', 'Título', 'Subtítulo', 'Estado', 'Publicación', 'Autor']
		const rows = noticias.value.map((n) => [
			n.not_id,
			n.not_titulo,
			n.not_subtitulo || '',
			n.not_estatus,
			n.not_publicacion ? formatDateTime(n.not_publicacion) : '',
			n.user?.usu_nombre || '',
		])

		const csvContent = [
			headers.join(','),
			...rows.map((row) =>
				row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','),
			),
		].join('\n')

		const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
		const url = window.URL.createObjectURL(blob)
		const link = document.createElement('a')
		link.href = url
		link.download = `noticias_${new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Mexico_City' })}.csv`
		document.body.appendChild(link)
		link.click()
		document.body.removeChild(link)
		window.URL.revokeObjectURL(url)

		showToast('Datos exportados a CSV exitosamente', 'success')
	} catch (err) {
		showToast('Error al exportar datos', 'error')
	}
}

const changePage = (page) => {
	pagination.value.current_page = page
	fetchNoticias()
}

// Helpers
const getImageUrl = (path) => {
	if (!path) return null
	const baseUrl = import.meta.env.VITE_API_BASE_URL.replace('/api', '')
	return `${baseUrl}/storage/${path}`
}

const getEmbedUrl = (url) => {
	if (!url) return null
	// Convertir URL de YouTube a embed
	const ytMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/)
	if (ytMatch) return `https://www.youtube.com/embed/${ytMatch[1]}`
	// Vimeo
	const vimeoMatch = url.match(/vimeo\.com\/(\d+)/)
	if (vimeoMatch) return `https://player.vimeo.com/video/${vimeoMatch[1]}`
	// Si no reconoce, devolver tal cual
	return url
}

const formatDate = (date) => {
	if (!date) return 'N/A'

	if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
		const [year, month, day] = date.split('-')
		return `${day}/${month}/${year}`
	}

	return new Date(date).toLocaleDateString('es-MX', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		timeZone: 'America/Mexico_City',
	})
}

const formatDateTime = (date) => {
	if (!date) return 'N/A'
	return new Date(date).toLocaleString('es-MX', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
		timeZone: 'America/Mexico_City',
	})
}

const formatDateTimeForInput = (dateString) => {
	if (!dateString) return ''
	try {
		if (typeof dateString === 'string' && dateString.includes('T')) {
			return dateString.slice(0, 16)
		}

		const date = new Date(dateString)
		const year = date.getFullYear()
		const month = String(date.getMonth() + 1).padStart(2, '0')
		const day = String(date.getDate()).padStart(2, '0')
		const hour = String(date.getHours()).padStart(2, '0')
		const min = String(date.getMinutes()).padStart(2, '0')
		return `${year}-${month}-${day}T${hour}:${min}`
	} catch {
		return ''
	}
}

const getStatusColor = (status) => {
	return status === 'Publicado'
		? 'bg-green-100 text-green-800 border-green-200'
		: 'bg-yellow-100 text-yellow-800 border-yellow-200'
}

// Sistema de notificaciones
const toast = ref({ show: false, message: '', type: 'success' })

const showToast = (message, type = 'success') => {
	toast.value = { show: true, message, type }
	setTimeout(() => {
		toast.value.show = false
	}, 3000)
}

// Lifecycle
onMounted(() => {
	fetchNoticias()
})
</script>

<template>
	<div class="min-h-screen bg-[#f4f6f8] p-4 md:p-6">
		<!-- Toast notification -->
		<transition
			enter-active-class="transition duration-300 ease-out"
			enter-from-class="transform translate-x-full opacity-0"
			enter-to-class="transform translate-x-0 opacity-100"
			leave-active-class="transition duration-200 ease-in"
			leave-from-class="transform translate-x-0 opacity-100"
			leave-to-class="transform translate-x-full opacity-0"
		>
			<div
				v-if="toast.show"
				class="fixed top-4 right-4 z-50 max-w-sm w-full shadow-lg rounded-lg pointer-events-auto"
				:class="
					toast.type === 'success'
						? 'bg-green-50 border-l-4 border-green-500'
						: 'bg-red-50 border-l-4 border-red-500'
				"
			>
				<div class="p-4 flex items-start">
					<div class="flex-shrink-0">
						<svg
							v-if="toast.type === 'success'"
							class="h-6 w-6 text-green-500"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
							/>
						</svg>
						<svg
							v-else
							class="h-6 w-6 text-red-500"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
							/>
						</svg>
					</div>
					<div class="ml-3 w-0 flex-1">
						<p
							class="text-sm font-medium"
							:class="toast.type === 'success' ? 'text-green-800' : 'text-red-800'"
						>
							{{ toast.message }}
						</p>
					</div>
				</div>
			</div>
		</transition>

		<!-- Card Principal -->
		<div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
			<!-- Header -->
			<div class="bg-[#f6f6f6] border-b border-gray-200 px-6 py-4">
				<h3 class="text-xl font-semibold text-[#1c2321]">Administración de Noticias</h3>
			</div>

			<!-- Barra de controles -->
			<div class="px-6 py-4 border-b border-gray-150 space-y-4">
				<div class="flex items-center justify-between gap-4 flex-wrap">
					<!-- Botón Crear -->
					<button
						@click="router.push('/news/create')"
						class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#312AFF] text-white font-semibold rounded-lg hover:bg-[#2821dd] transition-all shadow-sm active:scale-95 whitespace-nowrap"
					>
						<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M12 4v16m8-8H4"
							/>
						</svg>
						Crear Noticia
					</button>

					<!-- Filtros derecha -->
					<div class="flex flex-wrap items-center gap-2 pt-4">
						<!-- Filtros rápidos de estado -->
						<div class="flex bg-gray-100 p-1 rounded-lg border border-gray-200">
							<button
								v-for="opt in [
									{ l: 'Todas', v: '' },
									{ l: 'Publicadas', v: 'Publicado' },
									{ l: 'Guardadas', v: 'Guardado' },
								]"
								:key="opt.v"
								@click="setEstatusQuickFilter(opt.v)"
								:class="[
									filters.estatus === opt.v
										? 'bg-white shadow-sm text-[#312AFF] font-semibold'
										: 'text-gray-600 hover:text-gray-900',
								]"
								class="px-3 py-1.5 text-sm rounded-md transition-all"
							>
								{{ opt.l }}
							</button>
						</div>

						<!-- Exportar CSV -->
						<button
							@click="exportToCSV"
							:disabled="loading || noticias.length === 0"
							class="flex items-center gap-2 px-4 py-2 border border-green-600 text-green-600 rounded-lg text-sm font-medium transition-all hover:bg-green-50 disabled:opacity-50 disabled:cursor-not-allowed"
						>
							<svg
								class="w-4 h-4"
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
								/>
							</svg>
							<span class="hidden sm:inline">Exportar CSV</span>
						</button>

						<!-- Filtros avanzados -->
						<button
							@click="showAdvancedFilters = !showAdvancedFilters"
							:class="[
								showAdvancedFilters || hasAdvancedFiltersActive
									? 'border-[#312AFF] text-[#312AFF] bg-blue-50'
									: 'border-gray-300 text-gray-600 hover:border-gray-400',
							]"
							class="flex items-center gap-2 px-4 py-2 border rounded-lg text-sm font-medium transition-all relative"
						>
							<svg
								class="w-4 h-4"
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
								/>
							</svg>
							<span class="hidden sm:inline">Filtros</span>
							<span
								v-if="hasAdvancedFiltersActive"
								class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white"
							></span>
						</button>
					</div>
				</div>

				<!-- Panel filtros avanzados -->
				<transition
					enter-active-class="transition duration-200 ease-out"
					enter-from-class="transform -translate-y-4 opacity-0"
					enter-to-class="transform translate-y-0 opacity-100"
					leave-active-class="transition duration-150 ease-in"
					leave-from-class="transform translate-y-0 opacity-100"
					leave-to-class="transform -translate-y-4 opacity-0"
				>
					<div
						v-if="showAdvancedFilters"
						class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4 p-5 bg-[#f8fafc] rounded-xl border border-gray-200"
					>
						<div class="space-y-1 md:col-span-2">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Buscar por título o descripción</label
							>
							<input
								v-model="filters.search"
								type="text"
								placeholder="Ej: Inauguración..."
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#312AFF] focus:border-transparent outline-none placeholder-gray-500"
								:class="{ 'font-semibold text-gray-900': filters.search }"
							/>
						</div>
						<div class="space-y-1">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Publicado desde</label
							>
							<input
								v-model="filters.fecha_desde"
								type="date"
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#312AFF] focus:border-transparent outline-none"
								:class="{ 'font-semibold text-gray-900': filters.fecha_desde }"
							/>
						</div>
						<div class="space-y-1">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Publicado hasta</label
							>
							<input
								v-model="filters.fecha_hasta"
								type="date"
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#312AFF] focus:border-transparent outline-none"
								:class="{ 'font-semibold text-gray-900': filters.fecha_hasta }"
							/>
						</div>
						<div class="space-y-1">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Opciones</label
							>
							<label
								class="flex items-center gap-2 h-[42px] px-3 border border-gray-300 rounded-lg bg-white cursor-pointer hover:border-[#312AFF] transition-all"
								:class="{ 'border-[#312AFF] bg-blue-50': filters.mine }"
							>
								<input
									v-model="filters.mine"
									type="checkbox"
									class="w-4 h-4 text-[#312AFF] rounded border-gray-300 focus:ring-[#312AFF]"
								/>
								<span
									class="text-sm font-medium text-gray-700"
									:class="{ 'font-semibold text-[#312AFF]': filters.mine }"
									>Solo mías</span
								>
							</label>
						</div>
						<div
							class="md:col-span-4 flex justify-end gap-2 pt-1 border-t border-gray-200"
						>
							<button
								@click="clearAdvancedFilters"
								class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all"
							>
								Limpiar filtros
							</button>
							<button
								@click="
									() => {
										applyFilters()
										showAdvancedFilters = false
									}
								"
								class="px-6 py-2 text-sm font-semibold text-white bg-[#312AFF] rounded-lg hover:bg-[#2821dd] transition-all"
							>
								Aplicar filtros
							</button>
						</div>
					</div>
				</transition>

				<!-- Barra de acciones en lote -->
				<transition
					enter-active-class="transition duration-200 ease-out"
					enter-from-class="transform -translate-y-2 opacity-0"
					enter-to-class="transform translate-y-0 opacity-100"
					leave-active-class="transition duration-150 ease-in"
					leave-from-class="transform translate-y-0 opacity-100"
					leave-to-class="transform -translate-y-2 opacity-0"
				>
					<div
						v-if="hasSelectedNoticias"
						class="flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-lg flex-wrap gap-3"
					>
						<span class="text-sm font-semibold text-blue-900">
							{{ selectedNoticias.length }} noticia(s) seleccionada(s)
						</span>
						<div class="flex gap-2 flex-wrap">
							<button
								@click="selectedNoticias = []"
								class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
							>
								Cancelar
							</button>
							<button
								@click="openBulkStatus('Publicado')"
								class="px-3 py-1.5 text-sm font-medium text-green-700 bg-green-50 border border-green-300 rounded-lg hover:bg-green-100 transition-colors flex items-center gap-1.5"
							>
								<svg
									class="w-4 h-4"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
									/>
								</svg>
								Publicar
							</button>
							<button
								@click="openBulkStatus('Guardado')"
								class="px-3 py-1.5 text-sm font-medium text-yellow-700 bg-yellow-50 border border-yellow-300 rounded-lg hover:bg-yellow-100 transition-colors flex items-center gap-1.5"
							>
								<svg
									class="w-4 h-4"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"
									/>
								</svg>
								Guardar
							</button>
							<button
								@click="handleDeleteSelected"
								class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors flex items-center gap-1.5"
							>
								<svg
									class="w-4 h-4"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
									/>
								</svg>
								Eliminar
							</button>
						</div>
					</div>
				</transition>
			</div>

			<!-- Tabla -->
			<div class="px-6 pb-6">
				<div
					class="bg-[#004A7C] text-white px-5 py-4 flex justify-between items-center rounded-t-xl"
				>
					<h4 class="text-lg font-bold tracking-tight">Noticias</h4>
					<span class="text-sm font-medium"
						>Mostrando {{ displayRange }} de {{ pagination.total }} elementos</span
					>
				</div>

				<div class="overflow-x-auto border border-t-0 border-gray-300 rounded-b-xl">
					<table class="w-full border-collapse table-fixed">
						<colgroup>
							<col style="width: 40px" />
							<!-- Checkbox -->
							<col style="width: 90px" />
							<!-- Portada -->
							<col style="width: 150px" />
							<!-- Título -->
							<col style="width: 105px" />
							<!-- Estado -->
							<col style="width: 60px" />
							<!-- Media -->
							<col style="width: 115px" />
							<!-- Publicación -->
							<col style="width: 110px" />
							<!-- Autor -->
							<col style="width: 45px" />
							<!-- Ver -->
							<col style="width: 80px" />
							<!-- Modificar -->
							<col style="width: 75px" />
							<!-- Eliminar -->
						</colgroup>
						<thead>
							<tr class="bg-[#f8f9fa] border-b border-gray-300">
								<th class="px-2 py-3 text-center">
									<input
										v-model="allSelected"
										type="checkbox"
										class="w-4 h-4 text-[#312AFF] rounded border-gray-300"
									/>
								</th>
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider"
								>
									Portada
								</th>
								<!-- Título -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
									@click="setSorting('not_titulo')"
								>
									<div class="flex items-center justify-center gap-1">
										<span>Título</span>
										<svg
											class="w-3 h-3"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												v-if="
													filters.sort_by === 'not_titulo' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'not_titulo' &&
													filters.sort_direction === 'desc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M19 9l-7 7-7-7"
											/>
											<g v-else>
												<path
													stroke-linecap="round"
													stroke-linejoin="round"
													stroke-width="2"
													d="M5 10l7-7 7 7"
												/>
												<path
													stroke-linecap="round"
													stroke-linejoin="round"
													stroke-width="2"
													d="M19 14l-7 7-7-7"
												/>
											</g>
										</svg>
									</div>
								</th>
								<!-- Estado -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
									@click="setSorting('not_estatus')"
								>
									<div class="flex items-center justify-center gap-1">
										<span>Estado</span>
										<svg
											class="w-3 h-3"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												v-if="
													filters.sort_by === 'not_estatus' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'not_estatus' &&
													filters.sort_direction === 'desc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M19 9l-7 7-7-7"
											/>
											<g v-else>
												<path
													stroke-linecap="round"
													stroke-linejoin="round"
													stroke-width="2"
													d="M5 10l7-7 7 7"
												/>
												<path
													stroke-linecap="round"
													stroke-linejoin="round"
													stroke-width="2"
													d="M19 14l-7 7-7-7"
												/>
											</g>
										</svg>
									</div>
								</th>
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider"
								>
									Media
								</th>
								<!-- Publicación -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
									@click="setSorting('not_publicacion')"
								>
									<div class="flex items-center justify-center gap-1">
										<span>Publicación</span>
										<svg
											class="w-3 h-3"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												v-if="
													filters.sort_by === 'not_publicacion' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'not_publicacion' &&
													filters.sort_direction === 'desc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M19 9l-7 7-7-7"
											/>
											<g v-else>
												<path
													stroke-linecap="round"
													stroke-linejoin="round"
													stroke-width="2"
													d="M5 10l7-7 7 7"
												/>
												<path
													stroke-linecap="round"
													stroke-linejoin="round"
													stroke-width="2"
													d="M19 14l-7 7-7-7"
												/>
											</g>
										</svg>
									</div>
								</th>
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider"
								>
									Autor
								</th>
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider"
								>
									Ver
								</th>
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider"
								>
									Modificar
								</th>
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider"
								>
									Eliminar
								</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-gray-200 bg-white">
							<tr v-if="loading">
								<td colspan="10" class="py-20 text-center">
									<div class="flex justify-center items-center">
										<div
											class="animate-spin rounded-full h-10 w-10 border-4 border-[#312AFF]/20 border-t-[#312AFF]"
										></div>
									</div>
								</td>
							</tr>
							<tr v-else-if="error && noticias.length === 0">
								<td colspan="10" class="py-20 text-center text-red-600">
									{{ error }}
								</td>
							</tr>
							<tr v-else-if="noticias.length === 0">
								<td colspan="10" class="py-20 text-center text-gray-400 italic">
									No se encontraron noticias con los criterios seleccionados
								</td>
							</tr>
							<tr
								v-else
								v-for="noticia in noticias"
								:key="noticia.not_id"
								class="transition-all duration-200 hover:bg-blue-50/30"
							>
								<td class="px-2 py-3 text-center">
									<input
										v-model="selectedNoticias"
										:value="noticia.not_id"
										type="checkbox"
										class="w-4 h-4 text-[#312AFF] rounded border-gray-300"
										@click.stop
									/>
								</td>
								<!-- Portada -->
								<td class="px-2 py-3 text-center">
									<div class="flex justify-center">
										<div
											v-if="noticia.not_portada"
											class="w-16 h-12 rounded-lg overflow-hidden shadow-md border border-gray-200 bg-gray-50 cursor-pointer"
											@click="openModal('view', noticia)"
										>
											<img
												:src="getImageUrl(noticia.not_portada)"
												:alt="noticia.not_titulo"
												class="w-full h-full object-cover hover:scale-110 transition-transform duration-200"
											/>
										</div>
										<div
											v-else
											class="w-16 h-12 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center"
										>
											<svg
												class="w-5 h-5 text-gray-400"
												fill="none"
												viewBox="0 0 24 24"
												stroke="currentColor"
											>
												<path
													stroke-linecap="round"
													stroke-linejoin="round"
													stroke-width="2"
													d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"
												/>
											</svg>
										</div>
									</div>
								</td>
								<!-- Título -->
								<td class="px-2 py-3">
									<div
										class="text-xs font-semibold text-gray-900 truncate"
										:title="noticia.not_titulo"
									>
										{{ noticia.not_titulo }}
									</div>
									<div
										v-if="noticia.not_subtitulo"
										class="text-[11px] text-gray-500 truncate mt-0.5"
										:title="noticia.not_subtitulo"
									>
										{{ noticia.not_subtitulo }}
									</div>
								</td>
								<!-- Estado -->
								<td class="px-2 py-3 text-center">
									<span
										:class="getStatusColor(noticia.not_estatus)"
										class="inline-block px-2 py-0.5 text-[11px] font-bold uppercase rounded-full border whitespace-nowrap"
									>
										{{ noticia.not_estatus }}
									</span>
								</td>
								<!-- Media indicators -->
								<td class="px-2 py-3 text-center">
									<div class="flex items-center justify-center gap-1">
										<!-- Tiene imagen secundaria -->
										<span
											v-if="noticia.not_imagen"
											title="Tiene imagen"
											class="inline-flex"
										>
											<svg
												class="w-4 h-4 text-indigo-500"
												fill="none"
												stroke="currentColor"
												viewBox="0 0 24 24"
											>
												<path
													stroke-linecap="round"
													stroke-linejoin="round"
													stroke-width="2"
													d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
												/>
											</svg>
										</span>
										<!-- Tiene video -->
										<span
											v-if="noticia.not_video"
											title="Tiene video"
											class="inline-flex"
										>
											<svg
												class="w-4 h-4 text-red-500"
												fill="none"
												stroke="currentColor"
												viewBox="0 0 24 24"
											>
												<path
													stroke-linecap="round"
													stroke-linejoin="round"
													stroke-width="2"
													d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
												/>
											</svg>
										</span>
										<span
											v-if="!noticia.not_imagen && !noticia.not_video"
											class="text-gray-300 text-xs"
											>—</span
										>
									</div>
								</td>
								<!-- Publicación -->
								<td
									class="px-2 py-3 text-center text-[12px] text-gray-600 whitespace-nowrap"
								>
									{{ formatDate(noticia.not_publicacion) }}
								</td>
								<!-- Autor -->
								<td
									class="px-2 py-3 text-center text-[12px] text-gray-600 truncate"
								>
									{{ noticia.user?.usu_nombre ?? '—' }}
								</td>
								<td class="px-2 py-3 text-center">
									<button
										@click="openModal('view', noticia)"
										class="p-1 text-blue-600 hover:bg-blue-50 rounded-lg transition-all inline-flex"
										title="Ver detalle"
									>
										<svg
											class="w-4 h-4"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
											/>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
											/>
										</svg>
									</button>
								</td>
								<td class="px-2 py-3 text-center">
									<button
										@click="router.push(`/news/${noticia.not_id}/edit`)"
										class="p-1 text-amber-600 hover:bg-amber-50 rounded-lg transition-all inline-flex"
										title="Editar"
									>
										<svg
											class="w-4 h-4"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
											/>
										</svg>
									</button>
								</td>
								<td class="px-2 py-3 text-center">
									<button
										@click="confirmDelete(noticia)"
										class="p-1 text-red-600 hover:bg-red-50 rounded-lg transition-all inline-flex"
										title="Eliminar"
									>
										<svg
											class="w-4 h-4"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
											/>
										</svg>
									</button>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<!-- Paginación -->
				<div
					v-if="pagination.last_page > 1"
					class="mt-6 pt-1 flex justify-center items-center gap-2"
				>
					<button
						@click="changePage(pagination.current_page - 1)"
						:disabled="pagination.current_page === 1"
						class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-300 hover:border-[#312AFF] hover:bg-blue-50 disabled:opacity-80 disabled:cursor-not-allowed transition-all"
						:class="
							pagination.current_page === 1
								? ''
								: 'text-gray-700 hover:text-[#312AFF]'
						"
					>
						<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M15 19l-7-7 7-7"
							/>
						</svg>
					</button>
					<span class="px-3 py-1 text-sm font-medium text-gray-600 select-none">
						<span class="text-[#312AFF] font-semibold">{{
							pagination.current_page
						}}</span>
						<span class="text-gray-400 mx-1">/</span>
						<span class="text-gray-500">{{ pagination.last_page }}</span>
					</span>
					<button
						@click="changePage(pagination.current_page + 1)"
						:disabled="pagination.current_page === pagination.last_page"
						class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-300 hover:border-[#312AFF] hover:bg-blue-50 disabled:opacity-80 disabled:cursor-not-allowed transition-all"
						:class="
							pagination.current_page === pagination.last_page
								? ''
								: 'text-gray-700 hover:text-[#312AFF]'
						"
					>
						<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M9 5l7 7-7 7"
							/>
						</svg>
					</button>
				</div>
			</div>
		</div>

		<!-- ==================== MODAL EDITAR ==================== -->
		<div
			v-if="showModal && modalMode === 'edit'"
			class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fadeIn"
			@click.self="closeModal"
		>
			<div
				class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden animate-slideUp max-h-[90vh] flex flex-col"
			>
				<!-- HEADER -->
				<div
					class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex justify-between items-center flex-shrink-0"
				>
					<div>
						<h3 class="text-xl font-bold text-white">Editar Noticia</h3>
						<p class="text-indigo-100 text-xs mt-0.5">
							Actualiza el contenido de la noticia
						</p>
					</div>
					<button
						@click="closeModal"
						class="p-2 hover:bg-white/20 rounded-full transition-all duration-200"
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

				<!-- TABS -->
				<div class="border-b border-gray-200 bg-gray-50 flex-shrink-0">
					<div class="flex gap-1 px-6 overflow-x-auto">
						<button
							type="button"
							@click="activeTab = 'general'"
							:class="[
								'flex items-center gap-2 px-5 py-4 font-medium text-sm whitespace-nowrap transition-all duration-200 border-b-2',
								activeTab === 'general'
									? 'text-indigo-600 border-indigo-600 bg-white'
									: 'text-gray-500 border-transparent hover:text-gray-700',
							]"
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
									d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
								/>
							</svg>
							Contenido
						</button>
						<button
							type="button"
							@click="activeTab = 'media'"
							:class="[
								'flex items-center gap-2 px-5 py-4 font-medium text-sm whitespace-nowrap transition-all duration-200 border-b-2',
								activeTab === 'media'
									? 'text-indigo-600 border-indigo-600 bg-white'
									: 'text-gray-500 border-transparent hover:text-gray-700',
							]"
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
									d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
								/>
							</svg>
							Imágenes
						</button>
						<button
							type="button"
							@click="activeTab = 'video'"
							:class="[
								'flex items-center gap-2 px-5 py-4 font-medium text-sm whitespace-nowrap transition-all duration-200 border-b-2',
								activeTab === 'video'
									? 'text-indigo-600 border-indigo-600 bg-white'
									: 'text-gray-500 border-transparent hover:text-gray-700',
							]"
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
									d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
								/>
							</svg>
							Video
						</button>
						<button
							type="button"
							@click="activeTab = 'schedule'"
							:class="[
								'flex items-center gap-2 px-5 py-4 font-medium text-sm whitespace-nowrap transition-all duration-200 border-b-2',
								activeTab === 'schedule'
									? 'text-indigo-600 border-indigo-600 bg-white'
									: 'text-gray-500 border-transparent hover:text-gray-700',
							]"
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
									d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
								/>
							</svg>
							Publicación
						</button>
					</div>
				</div>

				<!-- CONTENIDO TABS -->
				<div class="p-8 overflow-y-auto flex-1">
					<form @submit.prevent="saveNoticia" class="space-y-6">
						<transition name="tab-content" mode="out-in">
							<!-- TAB 1: Contenido -->
							<div
								v-if="activeTab === 'general'"
								key="general"
								class="space-y-6 animate-fadeIn"
							>
								<!-- Título -->
								<div>
									<label
										class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2"
									>
										<svg
											class="h-4 w-4 text-indigo-500"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
											/>
										</svg>
										Título *
									</label>
									<input
										v-model="form.not_titulo"
										type="text"
										maxlength="100"
										placeholder="Título de la noticia"
										class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900"
										:class="{ 'border-red-500': formErrors.not_titulo }"
									/>
									<div class="flex justify-between mt-1">
										<p
											v-if="formErrors.not_titulo"
											class="text-red-500 text-sm"
										>
											{{ formErrors.not_titulo[0] }}
										</p>
										<p class="text-xs text-gray-400 ml-auto">
											{{ form.not_titulo?.length || 0 }}/100
										</p>
									</div>
								</div>
								<!-- Subtítulo -->
								<div>
									<label
										class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2"
									>
										<svg
											class="h-4 w-4 text-indigo-500"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M4 6h16M4 12h16M4 18h7"
											/>
										</svg>
										Subtítulo
									</label>
									<input
										v-model="form.not_subtitulo"
										type="text"
										maxlength="300"
										placeholder="Subtítulo o resumen breve (opcional)"
										class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900"
									/>
									<p class="text-xs text-gray-400 mt-1 text-right">
										{{ form.not_subtitulo?.length || 0 }}/300
									</p>
								</div>
								<!-- Descripción -->
								<div>
									<label
										class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2"
									>
										<svg
											class="h-4 w-4 text-indigo-500"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
											/>
										</svg>
										Descripción / Contenido *
									</label>
									<textarea
										v-model="form.not_descripcion"
										rows="8"
										placeholder="Escribe aquí el contenido completo de la noticia..."
										class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none resize-none text-gray-900"
										:class="{ 'border-red-500': formErrors.not_descripcion }"
									></textarea>
									<p
										v-if="formErrors.not_descripcion"
										class="text-red-500 text-sm mt-1"
									>
										{{ formErrors.not_descripcion[0] }}
									</p>
								</div>
							</div>

							<!-- TAB 2: Imágenes -->
							<div
								v-else-if="activeTab === 'media'"
								key="media"
								class="space-y-8 animate-fadeIn"
							>
								<!-- Portada -->
								<div>
									<label
										class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-3"
									>
										<svg
											class="h-4 w-4 text-indigo-500"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"
											/>
										</svg>
										Portada (imagen principal)
									</label>
									<div v-if="portadaPreview" class="mb-3 relative group">
										<div
											class="rounded-xl overflow-hidden border-2 border-indigo-200 shadow-md"
										>
											<img
												:src="portadaPreview"
												alt="Portada"
												class="w-full h-40 object-cover"
											/>
											<div
												class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl flex items-center justify-center"
											>
												<button
													type="button"
													@click="portadaInput?.click()"
													class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow-lg hover:bg-blue-700 transition-colors"
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
															d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
														/>
													</svg>
													Reemplazar
												</button>
											</div>
										</div>
									</div>
									<input
										ref="portadaInput"
										type="file"
										accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
										@change="handlePortadaChange"
										class="hidden"
									/>
									<div
										@click="portadaInput?.click()"
										class="border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer group transition-all duration-300"
										:class="
											portadaPreview
												? 'border-gray-200 bg-gray-50 hover:border-indigo-300'
												: 'border-indigo-300 bg-indigo-50 hover:border-indigo-500'
										"
									>
										<div class="flex flex-col items-center gap-3">
											<div
												class="p-3 rounded-full"
												:class="
													portadaPreview
														? 'bg-gray-100'
														: 'bg-indigo-100 group-hover:bg-indigo-200'
												"
											>
												<svg
													class="h-7 w-7"
													:class="
														portadaPreview
															? 'text-gray-400'
															: 'text-indigo-600'
													"
													fill="none"
													viewBox="0 0 24 24"
													stroke="currentColor"
												>
													<path
														stroke-linecap="round"
														stroke-linejoin="round"
														stroke-width="2"
														d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
													/>
												</svg>
											</div>
											<p
												class="text-sm font-semibold"
												:class="
													portadaPreview
														? 'text-gray-500'
														: 'text-indigo-600'
												"
											>
												{{
													portadaPreview
														? 'Cambiar portada'
														: 'Subir portada'
												}}
											</p>
											<p class="text-xs text-gray-400">
												PNG, JPG, WEBP • Máx. 4MB • Recomendado: 1200×630px
											</p>
										</div>
									</div>
								</div>

								<!-- Imagen secundaria -->
								<div>
									<label
										class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-3"
									>
										<svg
											class="h-4 w-4 text-purple-500"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
											/>
										</svg>
										Imagen del cuerpo (opcional)
									</label>
									<div v-if="imagenPreview" class="mb-3 relative group">
										<div
											class="rounded-xl overflow-hidden border-2 border-purple-200 shadow-md"
										>
											<img
												:src="imagenPreview"
												alt="Imagen cuerpo"
												class="w-full h-40 object-cover"
											/>
											<div
												class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl flex items-center justify-center"
											>
												<button
													type="button"
													@click="imagenInput?.click()"
													class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg shadow-lg hover:bg-purple-700 transition-colors"
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
															d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
														/>
													</svg>
													Reemplazar
												</button>
											</div>
										</div>
									</div>
									<input
										ref="imagenInput"
										type="file"
										accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
										@change="handleImagenChange"
										class="hidden"
									/>
									<div
										@click="imagenInput?.click()"
										class="border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer group transition-all duration-300"
										:class="
											imagenPreview
												? 'border-gray-200 bg-gray-50 hover:border-purple-300'
												: 'border-purple-300 bg-purple-50 hover:border-purple-500'
										"
									>
										<div class="flex flex-col items-center gap-3">
											<div
												class="p-3 rounded-full"
												:class="
													imagenPreview
														? 'bg-gray-100'
														: 'bg-purple-100 group-hover:bg-purple-200'
												"
											>
												<svg
													class="h-7 w-7"
													:class="
														imagenPreview
															? 'text-gray-400'
															: 'text-purple-600'
													"
													fill="none"
													viewBox="0 0 24 24"
													stroke="currentColor"
												>
													<path
														stroke-linecap="round"
														stroke-linejoin="round"
														stroke-width="2"
														d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
													/>
												</svg>
											</div>
											<p
												class="text-sm font-semibold"
												:class="
													imagenPreview
														? 'text-gray-500'
														: 'text-purple-600'
												"
											>
												{{
													imagenPreview
														? 'Cambiar imagen'
														: 'Subir imagen del cuerpo'
												}}
											</p>
											<p class="text-xs text-gray-400">
												PNG, JPG, WEBP • Máx. 4MB
											</p>
										</div>
									</div>
								</div>
							</div>

							<!-- TAB 3: Video -->
							<div
								v-else-if="activeTab === 'video'"
								key="video"
								class="space-y-6 animate-fadeIn"
							>
								<div>
									<label
										class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2"
									>
										<svg
											class="h-4 w-4 text-red-500"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
											/>
										</svg>
										URL del Video (opcional)
									</label>
									<input
										v-model="form.not_video"
										type="url"
										placeholder="https://www.youtube.com/watch?v=..."
										class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900"
										:class="{ 'border-red-500': formErrors.not_video }"
									/>
									<p
										v-if="formErrors.not_video"
										class="text-red-500 text-sm mt-1"
									>
										{{ formErrors.not_video[0] }}
									</p>
								</div>

								<!-- Vista previa del video -->
								<div
									v-if="form.not_video && getEmbedUrl(form.not_video)"
									class="mt-4"
								>
									<label class="text-sm font-semibold text-gray-600 mb-3 block"
										>Vista previa</label
									>
									<div
										class="rounded-2xl overflow-hidden border border-gray-200 shadow-md aspect-video bg-black"
									>
										<iframe
											:src="getEmbedUrl(form.not_video)"
											class="w-full h-full"
											frameborder="0"
											allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
											allowfullscreen
										></iframe>
									</div>
								</div>

								<!-- Info box -->
								<div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded-r-xl">
									<div class="flex gap-3">
										<svg
											class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5"
											fill="currentColor"
											viewBox="0 0 20 20"
										>
											<path
												fill-rule="evenodd"
												d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
												clip-rule="evenodd"
											/>
										</svg>
										<div class="text-sm text-blue-700">
											<p class="font-medium mb-1">Plataformas soportadas</p>
											<p class="text-blue-600">
												YouTube, Vimeo y cualquier URL válida de video
												embebible.
											</p>
										</div>
									</div>
								</div>
							</div>

							<!-- TAB 4: Publicación -->
							<div
								v-else-if="activeTab === 'schedule'"
								key="schedule"
								class="space-y-6 animate-fadeIn"
							>
								<!-- Fecha de publicación -->
								<div>
									<label
										class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2"
									>
										<svg
											class="h-4 w-4 text-indigo-500"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
											/>
										</svg>
										Fecha y hora de publicación
									</label>
									<input
										v-model="form.not_publicacion"
										type="datetime-local"
										class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900"
										:class="{ 'border-red-500': formErrors.not_publicacion }"
									/>
									<p
										v-if="formErrors.not_publicacion"
										class="text-red-500 text-sm mt-1"
									>
										{{ formErrors.not_publicacion[0] }}
									</p>
									<p class="text-xs text-gray-400 mt-1">
										Si se deja vacío, se publicará en el momento de guardar.
									</p>
								</div>

								<!-- Estado -->
								<div>
									<label class="text-sm font-semibold text-gray-700 mb-2 block"
										>Estado *</label
									>
									<select
										v-model="form.not_estatus"
										class="w-full px-4 py-3 pr-10 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900 appearance-none bg-white"
										style="
											background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3e%3cpolyline points=%276 9 12 15 18 9%27%3e%3c/polyline%3e%3c/svg%3e');
											background-repeat: no-repeat;
											background-position: right 0.75rem center;
											background-size: 1.25em 1.25em;
										"
									>
										<option value="Guardado">Guardado</option>
										<option value="Publicado">Publicado</option>
									</select>
								</div>

								<!-- Info box -->
								<div class="bg-blue-50 border-l-4 border-blue-500 p-2 rounded-r-xl">
									<div class="flex gap-3">
										<svg
											class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5"
											fill="currentColor"
											viewBox="0 0 20 20"
										>
											<path
												fill-rule="evenodd"
												d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
												clip-rule="evenodd"
											/>
										</svg>
										<div class="text-sm text-blue-700">
											<p class="font-medium mb-1">Publicación programada</p>
											<p class="text-blue-600">
												La noticia solo será visible al público cuando el
												estado sea "Publicado" y la fecha de publicación
												haya llegado.
											</p>
										</div>
									</div>
								</div>
							</div>
						</transition>
					</form>
				</div>

				<!-- FOOTER -->
				<div
					class="border-t border-gray-200 bg-gray-50 px-8 py-4 flex justify-end gap-3 flex-shrink-0"
				>
					<button
						type="button"
						@click="closeModal"
						class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-100 transition-all duration-200"
					>
						Cancelar
					</button>
					<button
						@click="saveNoticia"
						:disabled="loading"
						class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
					>
						{{ loading ? 'Guardando...' : 'Guardar Cambios' }}
					</button>
				</div>
			</div>
		</div>

		<!-- ==================== MODAL VER (solo lectura) ==================== -->
		<div
			v-if="showModal && modalMode === 'view'"
			class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fadeIn"
			@click.self="closeModal"
		>
			<div
				class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden animate-scaleIn"
			>
				<!-- HEADER -->
				<div
					class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 px-6 py-5 flex-shrink-0"
				>
					<div class="flex items-center justify-between">
						<div class="flex items-center gap-3">
							<div class="bg-white/20 backdrop-blur-sm p-2 rounded-xl">
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
										d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"
									/>
								</svg>
							</div>
							<div>
								<h3 class="text-xl font-bold text-white">Vista Detallada</h3>
								<p class="text-white/80 text-sm truncate max-w-md">
									{{ selectedNoticia?.not_titulo }}
								</p>
							</div>
						</div>
						<button
							@click="closeModal"
							class="p-2 hover:bg-white/20 rounded-xl transition-colors duration-200"
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
				</div>

				<!-- CONTENIDO -->
				<div class="flex-1 overflow-y-auto p-8 space-y-8">
					<!-- Portada -->
					<div
						v-if="selectedNoticia?.not_portada"
						class="rounded-2xl overflow-hidden shadow-lg border border-gray-200"
					>
						<img
							:src="getImageUrl(selectedNoticia.not_portada)"
							:alt="selectedNoticia.not_titulo"
							class="w-full h-56 object-cover"
						/>
					</div>

					<!-- Estado y fecha -->
					<div class="flex items-center justify-between flex-wrap gap-3">
						<span
							:class="getStatusColor(selectedNoticia?.not_estatus)"
							class="inline-block px-3 py-1 text-xs font-bold uppercase rounded-full border"
						>
							{{ selectedNoticia?.not_estatus }}
						</span>
						<div class="flex items-center gap-2 text-sm text-gray-500">
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
									d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
								/>
							</svg>
							{{ formatDateTime(selectedNoticia?.not_publicacion) }}
							<span v-if="selectedNoticia?.user" class="text-gray-400"
								>· por {{ selectedNoticia.user.usu_nombre }}</span
							>
						</div>
					</div>

					<!-- Contenido -->
					<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
						<div
							class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200"
						>
							<div class="flex items-center gap-2">
								<svg
									class="h-5 w-5 text-indigo-600"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
									/>
								</svg>
								<h4 class="font-semibold text-gray-900">Contenido</h4>
							</div>
						</div>
						<div class="p-6 space-y-5">
							<div>
								<p
									class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2"
								>
									Título
								</p>
								<p class="text-gray-900 font-semibold text-lg">
									{{ selectedNoticia?.not_titulo }}
								</p>
							</div>
							<div v-if="selectedNoticia?.not_subtitulo">
								<p
									class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2"
								>
									Subtítulo
								</p>
								<p class="text-gray-700 font-medium">
									{{ selectedNoticia.not_subtitulo }}
								</p>
							</div>
							<div>
								<p
									class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2"
								>
									Descripción
								</p>
								<div
									class="bg-gray-50 rounded-xl p-4 border border-gray-200 max-h-48 overflow-y-auto"
								>
									<p
										class="text-gray-800 text-sm leading-relaxed whitespace-pre-wrap"
									>
										{{ selectedNoticia?.not_descripcion }}
									</p>
								</div>
							</div>
						</div>
					</div>

					<!-- Imagen secundaria -->
					<div
						v-if="selectedNoticia?.not_imagen"
						class="bg-white rounded-2xl border border-gray-200 overflow-hidden"
					>
						<div
							class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200"
						>
							<div class="flex items-center gap-2">
								<svg
									class="h-5 w-5 text-purple-600"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
									/>
								</svg>
								<h4 class="font-semibold text-gray-900">Imagen del cuerpo</h4>
							</div>
						</div>
						<div class="p-4">
							<img
								:src="getImageUrl(selectedNoticia.not_imagen)"
								:alt="selectedNoticia.not_titulo"
								class="w-full rounded-xl object-cover max-h-64"
							/>
						</div>
					</div>

					<!-- Video -->
					<div
						v-if="selectedNoticia?.not_video"
						class="bg-white rounded-2xl border border-gray-200 overflow-hidden"
					>
						<div
							class="bg-gradient-to-r from-red-50 to-orange-50 px-6 py-4 border-b border-gray-200"
						>
							<div class="flex items-center gap-2">
								<svg
									class="h-5 w-5 text-red-600"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
									/>
								</svg>
								<h4 class="font-semibold text-gray-900">Video</h4>
							</div>
						</div>
						<div class="p-4">
							<div class="rounded-xl overflow-hidden aspect-video bg-black shadow-md">
								<iframe
									:src="getEmbedUrl(selectedNoticia.not_video)"
									class="w-full h-full"
									frameborder="0"
									allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
									allowfullscreen
								></iframe>
							</div>
						</div>
					</div>

					<!-- Metadatos -->
					<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
						<div
							class="bg-gradient-to-r from-gray-50 to-slate-50 px-6 py-4 border-b border-gray-200"
						>
							<div class="flex items-center gap-2">
								<svg
									class="h-5 w-5 text-gray-500"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
									/>
								</svg>
								<h4 class="font-semibold text-gray-700">Metadatos</h4>
							</div>
						</div>
						<div class="p-6 grid grid-cols-1 gap-3 text-sm">
							<div class="flex items-center gap-2">
								<svg
									class="h-4 w-4 text-gray-400"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"
									/>
								</svg>
								<span class="text-gray-600">ID:</span>
								<span class="font-mono text-gray-900"
									>#{{ selectedNoticia?.not_id }}</span
								>
							</div>
							<div class="flex items-center gap-2">
								<svg
									class="h-4 w-4 text-gray-400"
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
								<span class="text-gray-600">Autor:</span>
								<span class="font-medium text-gray-900">{{
									selectedNoticia?.user?.usu_nombre ?? '—'
								}}</span>
							</div>
							<div class="flex items-center gap-2">
								<svg
									class="h-4 w-4 text-gray-400"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
									/>
								</svg>
								<span class="text-gray-600">Creado:</span>
								<span class="font-medium text-gray-900">{{
									formatDateTime(selectedNoticia?.created_at)
								}}</span>
							</div>
							<div class="flex items-center gap-2">
								<svg
									class="h-4 w-4 text-gray-400"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
									/>
								</svg>
								<span class="text-gray-600">Actualizado:</span>
								<span class="font-medium text-gray-900">{{
									formatDateTime(selectedNoticia?.updated_at)
								}}</span>
							</div>
						</div>
					</div>
				</div>

				<!-- FOOTER -->
				<div
					class="border-t border-gray-200 bg-gray-50 px-6 py-5 flex-shrink-0 flex justify-end"
				>
					<button
						@click="closeModal"
						class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center gap-2"
					>
						<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M6 18L18 6M6 6l12 12"
							/>
						</svg>
						Cerrar
					</button>
				</div>
			</div>
		</div>

		<!-- Modal confirmación eliminar individual -->
		<div
			v-if="showDeleteConfirm"
			class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fadeIn"
			@click.self="showDeleteConfirm = false"
		>
			<div
				class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-scaleIn"
			>
				<div class="bg-gradient-to-br from-red-500 to-red-600 px-6 py-4 text-center">
					<div
						class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full mb-4 animate-bounce"
					>
						<svg
							class="h-8 w-8 text-red-500"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
							/>
						</svg>
					</div>
					<h3 class="text-xl font-bold text-white">¿Eliminar Noticia?</h3>
				</div>
				<div class="p-8">
					<p class="text-gray-700 text-center mb-2 text-lg">
						Estás a punto de eliminar la noticia:
					</p>
					<p class="text-center font-bold text-gray-900 text-xl mb-6 pb-2">
						"{{ noticiaToDelete?.not_titulo }}"
					</p>
					<div class="bg-amber-50 border-l-4 border-amber-500 p-1.5 rounded-r-xl mb-6">
						<div class="flex gap-3">
							<svg
								class="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
								/>
							</svg>
							<div class="text-sm text-amber-800">
								<p class="font-semibold mb-1">Advertencia</p>
								<p>
									Esta acción no se puede deshacer. Se eliminarán también las
									imágenes y el video asociados.
								</p>
							</div>
						</div>
					</div>
					<div class="flex gap-3 pt-3">
						<button
							@click="showDeleteConfirm = false"
							class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-100 transition-all duration-200"
						>
							Cancelar
						</button>
						<button
							@click="deleteNoticia"
							:disabled="loading"
							class="flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white font-semibold rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
						>
							{{ loading ? 'Eliminando...' : 'Eliminar' }}
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- Modal confirmación eliminar masivo -->
		<div
			v-if="showBulkDeleteConfirm"
			class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fadeIn"
			@click.self="showBulkDeleteConfirm = false"
		>
			<div
				class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-scaleIn"
			>
				<div class="bg-gradient-to-br from-red-600 to-orange-600 px-6 py-4 text-center">
					<div
						class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full mb-4 animate-bounce"
					>
						<svg
							class="h-8 w-8 text-red-500"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
							/>
						</svg>
					</div>
					<h3 class="text-xl font-bold text-white">Eliminación Masiva</h3>
				</div>
				<div class="p-8">
					<p class="text-gray-700 text-center mb-6 text-lg pb-2">
						Estás a punto de eliminar
						<span class="font-bold text-red-600"
							>{{ selectedNoticias.length }} noticia(s)</span
						>
						seleccionada(s).
					</p>
					<div class="bg-red-50 border-l-4 border-red-500 p-2 rounded-r-xl mb-6">
						<div class="flex gap-3">
							<svg
								class="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
								/>
							</svg>
							<div class="text-sm text-red-800">
								<p class="font-semibold mb-1">Acción Irreversible</p>
								<p>
									Todas las noticias seleccionadas serán eliminadas
									permanentemente, incluyendo sus imágenes y videos.
								</p>
							</div>
						</div>
					</div>
					<div class="flex gap-3 pt-3">
						<button
							@click="showBulkDeleteConfirm = false"
							class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-100 transition-all duration-200"
						>
							Cancelar
						</button>
						<button
							@click="bulkDelete"
							:disabled="loading"
							class="flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-orange-600 text-white font-semibold rounded-xl hover:from-red-600 hover:to-orange-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
						>
							{{ loading ? 'Eliminando...' : 'Eliminar Todas' }}
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- Modal confirmación cambio masivo de estado -->
		<div
			v-if="showBulkStatusConfirm"
			class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fadeIn"
			@click.self="showBulkStatusConfirm = false"
		>
			<div
				class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-scaleIn"
			>
				<div class="bg-gradient-to-br from-indigo-600 to-purple-600 px-6 py-4 text-center">
					<div
						class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full mb-4"
					>
						<svg
							v-if="bulkStatusTarget === 'Publicado'"
							class="w-8 h-8 text-green-600"
							fill="none"
							stroke="currentColor"
							viewBox="0 0 24 24"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
							/>
						</svg>
						<svg
							v-else
							class="w-8 h-8 text-yellow-600"
							fill="none"
							stroke="currentColor"
							viewBox="0 0 24 24"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"
							/>
						</svg>
					</div>
					<h3 class="text-xl font-bold text-white">Cambio Masivo de Estado</h3>
				</div>
				<div class="p-8">
					<p class="text-gray-700 text-center mb-6 text-lg">
						Estás a punto de marcar
						<span class="font-bold text-indigo-600"
							>{{ selectedNoticias.length }} noticia(s)</span
						>
						como <span class="font-bold">"{{ bulkStatusTarget }}"</span>.
					</p>
					<div class="flex gap-3 pt-3">
						<button
							@click="showBulkStatusConfirm = false"
							class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-100 transition-all duration-200"
						>
							Cancelar
						</button>
						<button
							@click="bulkUpdateStatus"
							:disabled="loading"
							class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
						>
							{{ loading ? 'Actualizando...' : 'Confirmar' }}
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped>
input::placeholder,
textarea::placeholder {
	color: #6b7280;
	opacity: 1;
}

.transition-all {
	transition-property: all;
	transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
	transition-duration: 150ms;
}

.overflow-y-auto {
	scrollbar-width: thin;
	scrollbar-color: #cbd5e0 #f7fafc;
}

.overflow-y-auto::-webkit-scrollbar {
	width: 8px;
}
.overflow-y-auto::-webkit-scrollbar-track {
	background: #f7fafc;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
	background-color: #cbd5e0;
	border-radius: 4px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
	background-color: #a0aec0;
}
</style>
