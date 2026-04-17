<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { formatDate, formatDateForInput } from '@/utils/dateFormatter'

const authStore = useAuthStore()
const router = useRouter()

// Estado
const hitos = ref([])
const loading = ref(false)
const error = ref(null)
const showModal = ref(false)
const showDeleteConfirm = ref(false)
const showBulkDeleteConfirm = ref(false)
const showBulkStatusConfirm = ref(false)
const showAdvancedFilters = ref(false)
const modalMode = ref('view') // 'edit' | 'view' | 'create'
const selectedHito = ref(null)
const hitoToDelete = ref(null)
const selectedHitos = ref([])
const bulkStatusTarget = ref('')
const activeTab = ref('general')
const draggedIndex = ref(null)
const dragoverIndex = ref(null)

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
	categoria: '',
	mine: false,
	fecha_desde: '',
	fecha_hasta: '',
	sort_by: 'hit_orden',
	sort_direction: 'asc',
})

// Formulario
const form = ref({
	hit_titulo: '',
	hit_descripcion: '',
	hit_fecha: '',
	hit_imagen: null,
	hit_categoria: '',
	hit_orden: 0,
	hit_estatus: 'Guardado',
})

const formErrors = ref({})
const imagePreview = ref(null)
const imageInput = ref(null)

// ─── Computed ───────────────────────────────────────────────
const hasAdvancedFiltersActive = computed(() =>
	Boolean(
		filters.value.search ||
			filters.value.categoria ||
			filters.value.mine ||
			filters.value.fecha_desde ||
			filters.value.fecha_hasta,
	),
)

const allSelected = computed({
	get: () => hitos.value.length > 0 && selectedHitos.value.length === hitos.value.length,
	set: (value) => {
		selectedHitos.value = value ? hitos.value.map((h) => h.hit_id) : []
	},
})

const hasSelectedHitos = computed(() => selectedHitos.value.length > 0)

const displayRange = computed(() => {
	if (hitos.value.length === 0) return '0-0'
	const start = (pagination.value.current_page - 1) * pagination.value.per_page + 1
	const end = Math.min(start + hitos.value.length - 1, pagination.value.total)
	return `${start}-${end}`
})

// ─── Toast ──────────────────────────────────────────────────
const toast = ref({ show: false, message: '', type: 'success' })
const showToast = (message, type = 'success') => {
	toast.value = { show: true, message, type }
	setTimeout(() => {
		toast.value.show = false
	}, 3000)
}

// ─── Filtros ────────────────────────────────────────────────
const setEstatusQuickFilter = (status) => {
	filters.value.estatus = status
	applyFilters()
}

const setSorting = (column) => {
	if (filters.value.sort_by === column) {
		filters.value.sort_direction = filters.value.sort_direction === 'asc' ? 'desc' : 'asc'
	} else {
		filters.value.sort_by = column
		filters.value.sort_direction = 'asc'
	}
	applyFilters()
}

const applyFilters = () => {
	pagination.value.current_page = 1
	fetchHitos()
}

const clearAdvancedFilters = () => {
	filters.value.search = ''
	filters.value.categoria = ''
	filters.value.mine = false
	filters.value.fecha_desde = ''
	filters.value.fecha_hasta = ''
	applyFilters()
}

// ─── Fetch ──────────────────────────────────────────────────
const fetchHitos = async () => {
	loading.value = true
	error.value = null
	try {
		const params = {
			page: pagination.value.current_page,
			per_page: pagination.value.per_page,
			...filters.value,
		}
		const response = await api.get('/hitos', { params })
		if (response.data.success) {
			hitos.value = response.data.data.data
			pagination.value = {
				current_page: response.data.data.current_page,
				last_page: response.data.data.last_page,
				per_page: response.data.data.per_page,
				total: response.data.data.total,
			}
		}
	} catch (err) {
		error.value = err.response?.data?.message || 'Error al cargar hitos'
		console.error('Error fetching hitos:', err)
	} finally {
		loading.value = false
	}
}

// ─── Modal ──────────────────────────────────────────────────
const openModal = (mode, hito = null) => {
	modalMode.value = mode
	selectedHito.value = hito
	formErrors.value = {}
	activeTab.value = 'general'

	if (mode === 'edit' && hito) {
		form.value = {
			hit_titulo: hito.hit_titulo,
			hit_descripcion: hito.hit_descripcion || '',
			hit_fecha: formatDateForInput(hito.hit_fecha),
			hit_imagen: null,
			hit_categoria: hito.hit_categoria || '',
			hit_orden: hito.hit_orden ?? 0,
			hit_estatus: hito.hit_estatus,
		}
		imagePreview.value = hito.hit_imagen ? getImageUrl(hito.hit_imagen) : null
	} else if (mode === 'create') {
		resetForm()
	}

	showModal.value = true
}

const closeModal = () => {
	showModal.value = false
	selectedHito.value = null
	resetForm()
}

const resetForm = () => {
	form.value = {
		hit_titulo: '',
		hit_descripcion: '',
		hit_fecha: '',
		hit_imagen: null,
		hit_categoria: '',
		hit_orden: 0,
		hit_estatus: 'Guardado',
	}
	imagePreview.value = null
	formErrors.value = {}
}

// ─── Imagen ─────────────────────────────────────────────────
const handleImageChange = (event) => {
	const file = event.target.files[0]
	if (file) {
		form.value.hit_imagen = file
		const reader = new FileReader()
		reader.onload = (e) => {
			imagePreview.value = e.target.result
		}
		reader.readAsDataURL(file)
	}
}

// ─── Guardar ────────────────────────────────────────────────
const saveHito = async () => {
	loading.value = true
	formErrors.value = {}

	const errores = {}
	if (!form.value.hit_titulo?.trim()) errores.hit_titulo = ['El título es obligatorio']
	if (!form.value.hit_fecha) errores.hit_fecha = ['La fecha es obligatoria']
	if (!form.value.hit_estatus) errores.hit_estatus = ['El estado es obligatorio']

	if (Object.keys(errores).length > 0) {
		formErrors.value = errores
		const mensajes = Object.values(errores)
			.map((e) => e[0])
			.join('\n• ')
		showToast(`Por favor completa los campos obligatorios:\n• ${mensajes}`, 'error')
		loading.value = false
		return
	}

	try {
		const formData = new FormData()
		Object.keys(form.value).forEach((key) => {
			if (key === 'hit_imagen') return
			if (form.value[key] !== null && form.value[key] !== '')
				formData.append(key, form.value[key])
		})
		if (form.value.hit_imagen instanceof File)
			formData.append('hit_imagen', form.value.hit_imagen)

		let response
		if (modalMode.value === 'edit' && selectedHito.value) {
			formData.append('_method', 'PUT')
			response = await api.post(`/hitos/${selectedHito.value.hit_id}`, formData, {
				headers: { 'Content-Type': 'multipart/form-data' },
			})
		} else {
			response = await api.post('/hitos', formData, {
				headers: { 'Content-Type': 'multipart/form-data' },
			})
		}

		if (response.data.success) {
			closeModal()
			fetchHitos()
			showToast(response.data.message, 'success')
		}
	} catch (err) {
		if (err.response?.status === 422) {
			formErrors.value = err.response.data.errors || {}
		}
		showToast(err.response?.data?.message || 'Error al guardar hito', 'error')
	} finally {
		loading.value = false
	}
}

// ─── Eliminar individual ────────────────────────────────────
const confirmDelete = (hito) => {
	hitoToDelete.value = hito
	showDeleteConfirm.value = true
}

const deleteHito = async () => {
	if (!hitoToDelete.value) return
	loading.value = true
	try {
		const response = await api.delete(`/hitos/${hitoToDelete.value.hit_id}`)
		if (response.data.success) {
			fetchHitos()
			selectedHitos.value = []
			showToast(response.data.message, 'success')
		}
	} catch (err) {
		showToast(err.response?.data?.message || 'Error al eliminar hito', 'error')
	} finally {
		loading.value = false
		showDeleteConfirm.value = false
		hitoToDelete.value = null
	}
}

// ─── Acciones masivas ────────────────────────────────────────
const handleDeleteSelected = () => {
	if (selectedHitos.value.length === 0) return
	if (selectedHitos.value.length === 1) {
		const hito = hitos.value.find((h) => h.hit_id === selectedHitos.value[0])
		if (hito) {
			hitoToDelete.value = hito
			showDeleteConfirm.value = true
		}
	} else {
		showBulkDeleteConfirm.value = true
	}
}

const bulkDelete = async () => {
	if (selectedHitos.value.length === 0) return
	loading.value = true
	try {
		const response = await api.delete('/hitos/bulk-delete', {
			data: { ids: selectedHitos.value },
		})
		if (response.data.success) {
			selectedHitos.value = []
			fetchHitos()
			showToast(response.data.message, 'success')
		}
	} catch (err) {
		showToast(err.response?.data?.message || 'Error al eliminar hitos', 'error')
	} finally {
		loading.value = false
		showBulkDeleteConfirm.value = false
	}
}

const openBulkStatus = (estatus) => {
	bulkStatusTarget.value = estatus
	showBulkStatusConfirm.value = true
}

const bulkUpdateStatus = async () => {
	loading.value = true
	try {
		const response = await api.put('/hitos/bulk-status', {
			ids: selectedHitos.value,
			estatus: bulkStatusTarget.value,
		})
		if (response.data.success) {
			selectedHitos.value = []
			fetchHitos()
			showToast(response.data.message, 'success')
		}
	} catch (err) {
		showToast(err.response?.data?.message || 'Error al actualizar estado', 'error')
	} finally {
		loading.value = false
		showBulkStatusConfirm.value = false
		bulkStatusTarget.value = ''
	}
}

// ─── Drag & Drop ─────────────────────────────────────────────
const handleDragStart = (index) => {
	draggedIndex.value = index
}

const handleDragOver = (event, index) => {
	event.preventDefault()
	dragoverIndex.value = index
}

const handleDrop = async (event, dropIndex) => {
	event.preventDefault()
	if (draggedIndex.value === dropIndex) {
		draggedIndex.value = null
		dragoverIndex.value = null
		return
	}

	const updated = [...hitos.value]
	const [moved] = updated.splice(draggedIndex.value, 1)
	updated.splice(dropIndex, 0, moved)
	hitos.value = updated

	const reorderedData = updated.map((h, i) => ({ id: h.hit_id, orden: i + 1 }))

	try {
		const response = await api.put('/hitos/update-order', { items: reorderedData })
		if (response.data.success) {
			updated.forEach((h, i) => {
				h.hit_orden = i + 1
			})
			showToast('Orden actualizado exitosamente', 'success')
		}
	} catch {
		showToast('Error al actualizar el orden', 'error')
		fetchHitos()
	} finally {
		draggedIndex.value = null
		dragoverIndex.value = null
	}
}

const handleDragEnd = () => {
	draggedIndex.value = null
	dragoverIndex.value = null
}

const handleDragLeave = () => {
	dragoverIndex.value = null
}

// ─── Exportar ────────────────────────────────────────────────
const exportToExcel = async () => {
	try {
		loading.value = true
		const params = { ...filters.value, export: 'excel' }
		const response = await api.get('/hitos/export', { params, responseType: 'blob' })
		const blob = new Blob([response.data], {
			type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		})
		const url = window.URL.createObjectURL(blob)
		const link = document.createElement('a')
		link.href = url
		link.download = `hitos_${new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Mexico_City' })}.xlsx`
		document.body.appendChild(link)
		link.click()
		document.body.removeChild(link)
		window.URL.revokeObjectURL(url)
		showToast('Archivo Excel exportado exitosamente', 'success')
	} catch {
		exportToCSV()
	} finally {
		loading.value = false
	}
}

const exportToPDF = async () => {
	try {
		loading.value = true
		const params = { ...filters.value, export: 'pdf' }
		const response = await api.get('/hitos/export', { params, responseType: 'blob' })
		const blob = new Blob([response.data], { type: 'application/pdf' })
		const url = window.URL.createObjectURL(blob)
		const link = document.createElement('a')
		link.href = url
		link.download = `hitos_${new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Mexico_City' })}.pdf`
		document.body.appendChild(link)
		link.click()
		document.body.removeChild(link)
		window.URL.revokeObjectURL(url)
		showToast('Archivo PDF exportado exitosamente', 'success')
	} catch {
		showToast('Error al exportar a PDF', 'error')
	} finally {
		loading.value = false
	}
}

const exportToCSV = () => {
	try {
		const headers = ['ID', 'Título', 'Categoría', 'Fecha', 'Estado', 'Orden', 'Autor']
		const rows = hitos.value.map((h) => [
			h.hit_id,
			h.hit_titulo,
			h.hit_categoria || '',
			formatDate(h.hit_fecha),
			h.hit_estatus,
			h.hit_orden ?? '',
			h.user?.usu_nombre || '',
		])
		const csvContent = [headers, ...rows]
			.map((r) => r.map((c) => `"${String(c).replace(/"/g, '""')}"`).join(','))
			.join('\n')
		const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
		const url = window.URL.createObjectURL(blob)
		const link = document.createElement('a')
		link.href = url
		link.download = `hitos_${new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Mexico_City' })}.csv`
		document.body.appendChild(link)
		link.click()
		document.body.removeChild(link)
		window.URL.revokeObjectURL(url)
		showToast('Datos exportados a CSV', 'success')
	} catch {
		showToast('Error al exportar datos', 'error')
	}
}

// ─── Paginación ──────────────────────────────────────────────
const changePage = (page) => {
	pagination.value.current_page = page
	fetchHitos()
}

// ─── Helpers ─────────────────────────────────────────────────
const getImageUrl = (path) => {
	if (!path) return null
	return `${import.meta.env.VITE_API_BASE_URL.replace('/api', '')}/storage/${path}`
}

const getStatusColor = (status) =>
	status === 'Publicado'
		? 'bg-green-100 text-green-800 border-green-200'
		: 'bg-yellow-100 text-yellow-800 border-yellow-200'

const getSortIcon = (column) => {
	if (filters.value.sort_by !== column) return 'both'
	return filters.value.sort_direction
}

onMounted(fetchHitos)
</script>

<template>
	<div class="min-h-screen bg-[#f4f6f8] p-4 md:p-6">
		<!-- ═══ Toast ═══════════════════════════════════════════════ -->
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

		<!-- ═══ Card Principal ═══════════════════════════════════════ -->
		<div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
			<!-- Header -->
			<div class="bg-[#f6f6f6] border-b border-gray-200 px-6 py-4">
				<h3 class="text-xl font-semibold text-[#1c2321]">
					Administración de Hitos / Timeline
				</h3>
			</div>

			<!-- Barra de controles -->
			<div class="px-6 py-4 border-b border-gray-150 space-y-4">
				<div class="flex items-center justify-between gap-4">
					<!-- Botón Crear -->
					<button
						@click="openModal('create')"
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
						Nuevo Hito
					</button>

					<!-- Filtros rápidos + exportar + filtros avanzados -->
					<div class="flex flex-wrap items-center gap-2 pt-4">
						<!-- Filtros de estado -->
						<div class="flex bg-gray-100 p-1 rounded-lg border border-gray-200">
							<button
								v-for="opt in [
									{ l: 'Todos', v: '' },
									{ l: 'Publicados', v: 'Publicado' },
									{ l: 'Guardados', v: 'Guardado' },
								]"
								:key="opt.v"
								@click="setEstatusQuickFilter(opt.v)"
								:class="
									filters.estatus === opt.v
										? 'bg-white shadow-sm text-[#312AFF] font-semibold'
										: 'text-gray-600 hover:text-gray-900'
								"
								class="px-3 py-1.5 text-sm rounded-md transition-all"
							>
								{{ opt.l }}
							</button>
						</div>

						<!-- Botón exportar con menú desplegable -->
						<div class="relative group">
							<button
								:disabled="loading || hitos.length === 0"
								class="flex items-center gap-2 px-4 py-2 border border-green-600 text-green-600 rounded-lg text-sm font-medium transition-all hover:bg-green-50 disabled:opacity-50 disabled:cursor-not-allowed"
								title="Exportar datos"
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
								<span class="hidden sm:inline">Exportar</span>
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
										d="M19 9l-7 7-7-7"
									/>
								</svg>
							</button>
							<!-- Menú desplegable -->
							<div
								class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10"
							>
								<button
									@click="exportToExcel"
									:disabled="loading || hitos.length === 0"
									class="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-center gap-3 text-sm font-medium text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors rounded-t-lg"
								>
									<svg
										class="w-5 h-5 text-green-600"
										fill="none"
										stroke="currentColor"
										viewBox="0 0 24 24"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
										/>
									</svg>
									Exportar a Excel
								</button>
								<button
									@click="exportToPDF"
									:disabled="loading || hitos.length === 0"
									class="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-center gap-3 text-sm font-medium text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors rounded-b-lg"
								>
									<svg
										class="w-5 h-5 text-red-600"
										fill="none"
										stroke="currentColor"
										viewBox="0 0 24 24"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
										/>
									</svg>
									Exportar a PDF
								</button>
							</div>
						</div>

						<!-- Filtros avanzados -->
						<button
							@click="showAdvancedFilters = !showAdvancedFilters"
							:class="
								showAdvancedFilters || hasAdvancedFiltersActive
									? 'border-[#312AFF] text-[#312AFF] bg-blue-50'
									: 'border-gray-300 text-gray-600 hover:border-gray-400'
							"
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
								>Buscar</label
							>
							<input
								v-model="filters.search"
								type="text"
								placeholder="Título, descripción, categoría..."
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#312AFF] focus:border-transparent outline-none placeholder-gray-500"
								:class="{ 'font-semibold text-gray-900': filters.search }"
							/>
						</div>
						<div class="space-y-1">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Categoría</label
							>
							<input
								v-model="filters.categoria"
								type="text"
								placeholder="Premio, Expansión..."
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#312AFF] focus:border-transparent outline-none"
								:class="{ 'font-semibold text-gray-900': filters.categoria }"
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
									>Solo míos</span
								>
							</label>
						</div>
						<div class="space-y-1">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Fecha desde</label
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
								>Fecha hasta</label
							>
							<input
								v-model="filters.fecha_hasta"
								type="date"
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#312AFF] focus:border-transparent outline-none"
								:class="{ 'font-semibold text-gray-900': filters.fecha_hasta }"
							/>
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
						v-if="hasSelectedHitos"
						class="flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-lg flex-wrap gap-3"
					>
						<span class="text-sm font-semibold text-blue-900"
							>{{ selectedHitos.length }} hito(s) seleccionado(s)</span
						>
						<div class="flex gap-2 flex-wrap">
							<button
								@click="selectedHitos = []"
								class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
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
								class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
							>
								Eliminar seleccionados
							</button>
						</div>
					</div>
				</transition>
			</div>

			<!-- ═══ Tabla ═══════════════════════════════════════════ -->
			<div class="px-6 pb-6">
				<div
					class="bg-[#004A7C] text-white px-5 py-4 flex justify-between items-center rounded-t-xl"
				>
					<h4 class="text-lg font-bold tracking-tight">Hitos / Timeline</h4>
					<span class="text-sm font-medium"
						>Mostrando {{ displayRange }} de {{ pagination.total }} elementos</span
					>
				</div>

				<div class="overflow-x-auto border border-t-0 border-gray-300 rounded-b-xl">
					<table class="w-full border-collapse table-fixed">
						<colgroup>
							<col style="width: 40px" />
							<!-- Checkbox -->
							<col style="width: 55px" />
							<!-- Orden  -->
							<col style="width: 90px" />
							<!-- Imagen -->
							<col style="width: 150px" />
							<!-- Título -->
							<col style="width: 100px" />
							<!-- Categoría -->
							<col style="width: 90px" />
							<!-- Estado -->
							<col style="width: 110px" />
							<!-- Fecha -->
							<col style="width: 45px" />
							<!-- Ver -->
							<col style="width: 80px" />
							<!-- Modificar -->
							<col style="width: 75px" />
							<!-- Eliminar -->
						</colgroup>
						<thead>
							<tr class="bg-[#f8f9fa] border-b border-gray-300">
								<!-- Checkbox -->
								<th class="px-2 py-3 text-center">
									<input
										v-model="allSelected"
										type="checkbox"
										class="w-4 h-4 text-[#312AFF] rounded border-gray-300"
									/>
								</th>
								<!-- Orden sortable -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 transition-colors group"
									@click="setSorting('hit_orden')"
								>
									<div class="flex items-center justify-center gap-1">
										#
										<svg
											class="w-3 h-3 transition-all"
											:class="
												filters.sort_by === 'hit_orden'
													? 'text-[#312AFF]'
													: 'text-gray-400 group-hover:text-gray-600'
											"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												v-if="getSortIcon('hit_orden') === 'asc'"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="getSortIcon('hit_orden') === 'desc'"
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
								<!-- Imagen -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider"
								>
									Imagen
								</th>
								<!-- Título sortable -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 transition-colors group"
									@click="setSorting('hit_titulo')"
								>
									<div class="flex items-center justify-center gap-1">
										Título
										<svg
											class="w-3 h-3 transition-all"
											:class="
												filters.sort_by === 'hit_titulo'
													? 'text-[#312AFF]'
													: 'text-gray-400 group-hover:text-gray-600'
											"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												v-if="getSortIcon('hit_titulo') === 'asc'"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="getSortIcon('hit_titulo') === 'desc'"
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
								<!-- Categoría -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider"
								>
									Categoría
								</th>
								<!-- Estado sortable -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 transition-colors group"
									@click="setSorting('hit_estatus')"
								>
									<div class="flex items-center justify-center gap-1">
										Estado
										<svg
											class="w-3 h-3 transition-all"
											:class="
												filters.sort_by === 'hit_estatus'
													? 'text-[#312AFF]'
													: 'text-gray-400 group-hover:text-gray-600'
											"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												v-if="getSortIcon('hit_estatus') === 'asc'"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="getSortIcon('hit_estatus') === 'desc'"
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
								<!-- Fecha sortable -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 transition-colors"
									@click="setSorting('hit_fecha')"
								>
									<div class="flex items-center justify-center gap-1">
										Fecha
										<svg
											class="w-3 h-3 transition-all"
											:class="
												filters.sort_by === 'hit_fecha'
													? 'text-[#312AFF]'
													: 'text-gray-400 group-hover:text-gray-600'
											"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												v-if="getSortIcon('hit_fecha') === 'asc'"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="getSortIcon('hit_fecha') === 'desc'"
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

								<!-- Acciones separadas en 3 columnas -->
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
							<tr v-else-if="error && hitos.length === 0">
								<td colspan="11" class="py-20 text-center text-red-600">
									{{ error }}
								</td>
							</tr>
							<tr v-else-if="hitos.length === 0">
								<td colspan="11" class="py-20 text-center text-gray-400 italic">
									No se encontraron hitos con los criterios seleccionados
								</td>
							</tr>
							<tr
								v-else
								v-for="(hito, index) in hitos"
								:key="hito.hit_id"
								draggable="true"
								@dragstart="handleDragStart(index)"
								@dragover="handleDragOver($event, index)"
								@drop="handleDrop($event, index)"
								@dragend="handleDragEnd"
								@dragleave="handleDragLeave"
								:class="[
									'transition-all duration-200 cursor-move',
									draggedIndex === index
										? 'opacity-50 bg-blue-100'
										: 'hover:bg-blue-50/30',
									dragoverIndex === index && draggedIndex !== index
										? 'border-t-4 border-blue-500'
										: '',
								]"
							>
								<!-- Checkbox -->
								<td class="px-2 py-3 text-center">
									<input
										v-model="selectedHitos"
										:value="hito.hit_id"
										type="checkbox"
										class="w-4 h-4 text-[#312AFF] rounded border-gray-300"
										@click.stop
									/>
								</td>
								<!-- Orden -->
								<td
									class="px-2 py-3 text-center text-xs font-semibold text-gray-900"
								>
									{{ hito.hit_orden ?? '—' }}
								</td>
								<!-- Imagen -->
								<td class="px-2 py-3 text-center">
									<div class="flex justify-center items-center">
										<div
											v-if="hito.hit_imagen"
											class="w-20 h-12 rounded-lg overflow-hidden shadow-md border border-gray-200 bg-gray-50"
										>
											<img
												:src="getImageUrl(hito.hit_imagen)"
												:alt="hito.hit_titulo"
												class="w-full h-full object-cover hover:scale-110 transition-transform duration-200 cursor-pointer"
												@click="openModal('view', hito)"
											/>
										</div>
										<div
											v-else
											class="w-20 h-12 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center"
										>
											<svg
												class="w-6 h-6 text-gray-400"
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
										</div>
									</div>
								</td>
								<!-- Título + descripción -->
								<td class="px-2 py-3">
									<div class="w-full text-left">
										<div class="truncate text-xs font-semibold text-gray-900">
											{{ hito.hit_titulo }}
										</div>
										<div
											v-if="hito.hit_descripcion"
											class="truncate text-[11px] text-gray-400 mt-0.5"
										>
											{{ hito.hit_descripcion }}
										</div>
									</div>
								</td>
								<!-- Categoría -->
								<td class="px-2 py-3 text-center">
									<span
										v-if="hito.hit_categoria"
										class="inline-block px-2 py-0.5 text-[11px] font-medium bg-indigo-50 text-indigo-700 rounded-full border border-indigo-100 truncate max-w-full"
									>
										{{ hito.hit_categoria }}
									</span>
									<span v-else class="text-gray-300 text-xs">—</span>
								</td>
								<!-- Estado -->
								<td class="px-2 py-3 text-center">
									<span
										:class="getStatusColor(hito.hit_estatus)"
										class="inline-block px-2 py-0.5 text-[11px] font-bold uppercase rounded-full border whitespace-nowrap"
									>
										{{ hito.hit_estatus }}
									</span>
								</td>
								<!-- Fecha -->
								<td
									class="px-2 py-3 text-center text-[12px] text-gray-600 whitespace-nowrap"
								>
									{{ formatDate(hito.hit_fecha) }}
								</td>
								<!-- Ver -->
								<td class="px-2 py-3 text-center">
									<button
										@click="openModal('view', hito)"
										class="p-1 text-blue-600 hover:bg-blue-50 rounded-lg transition-all inline-flex"
										title="Ver detalles"
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
								<!-- Modificar -->
								<td class="px-2 py-3 text-center">
									<button
										@click="openModal('edit', hito)"
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
								<!-- Eliminar -->
								<td class="px-2 py-3 text-center">
									<button
										@click="confirmDelete(hito)"
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
						class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-300 hover:border-[#312AFF] hover:bg-blue-50 disabled:opacity-80 disabled:cursor-not-allowed disabled:hover:border-gray-300 disabled:hover:bg-transparent transition-all"
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
						class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-300 hover:border-[#312AFF] hover:bg-blue-50 disabled:opacity-80 disabled:cursor-not-allowed disabled:hover:border-gray-300 disabled:hover:bg-transparent transition-all"
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

		<!-- ═══════════════════════════════════════════════════════════
		     MODAL CREAR / EDITAR
		═══════════════════════════════════════════════════════════ -->
		<div
			v-if="showModal && modalMode !== 'view'"
			class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fadeIn"
			@click.self="closeModal"
		>
			<div
				class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full overflow-hidden animate-slideUp max-h-[90vh] flex flex-col"
			>
				<!-- Header gradiente -->
				<div
					class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex justify-between items-center flex-shrink-0"
				>
					<div>
						<h3 class="text-xl font-bold text-white">
							{{ modalMode === 'edit' ? 'Editar Hito' : 'Nuevo Hito' }}
						</h3>
						<p class="text-indigo-100 text-xs mt-0.5">
							{{
								modalMode === 'edit'
									? 'Actualiza la información del hito'
									: 'Agrega un nuevo evento al timeline'
							}}
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

				<!-- Tabs -->
				<div class="border-b border-gray-200 bg-gray-50 flex-shrink-0">
					<div class="flex gap-1 px-8">
						<button
							type="button"
							@click="activeTab = 'general'"
							:class="[
								'flex items-center gap-2 px-6 py-4 font-medium text-sm transition-all duration-200 border-b-2',
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
							Información General
						</button>
						<button
							type="button"
							@click="activeTab = 'media'"
							:class="[
								'flex items-center gap-2 px-6 py-4 font-medium text-sm transition-all duration-200 border-b-2',
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
							Imagen
						</button>
						<button
							type="button"
							@click="activeTab = 'schedule'"
							:class="[
								'flex items-center gap-2 px-6 py-4 font-medium text-sm transition-all duration-200 border-b-2',
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
							Programación
						</button>
					</div>
				</div>

				<!-- Contenido tabs -->
				<div class="p-8 overflow-y-auto flex-1 max-h-[calc(100vh-280px)]">
					<transition name="tab-content" mode="out-in">
						<!-- TAB 1: General -->
						<div
							v-if="activeTab === 'general'"
							key="general"
							class="space-y-6 animate-fadeIn"
						>
							<div class="group">
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
									Título del Hito *
								</label>
								<input
									v-model="form.hit_titulo"
									type="text"
									maxlength="150"
									placeholder="Ej: Fundación de la empresa"
									class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900"
									:class="{ 'border-red-500': formErrors.hit_titulo }"
								/>
								<div class="flex justify-between items-center mt-1.5">
									<p v-if="formErrors.hit_titulo" class="text-red-500 text-sm">
										{{ formErrors.hit_titulo[0] }}
									</p>
									<p class="text-xs text-gray-500 ml-auto">
										{{ form.hit_titulo.length }}/150 caracteres
									</p>
								</div>
							</div>
							<div class="group">
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
									Descripción
								</label>
								<textarea
									v-model="form.hit_descripcion"
									rows="4"
									placeholder="Describe brevemente este evento o logro..."
									class="w-full px-4 py-1 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none resize-none text-gray-900"
								></textarea>
							</div>
							<div class="group">
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
											d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
										/>
									</svg>
									Categoría
								</label>
								<input
									v-model="form.hit_categoria"
									type="text"
									maxlength="50"
									placeholder="Ej: Premio, Fundación, Expansión, Certificación..."
									class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900"
								/>
								<p class="text-xs text-gray-500 mt-1.5">
									Úsala para filtrar y agrupar en el timeline.
								</p>
							</div>
						</div>

						<!-- TAB 2: Imagen -->
						<div
							v-else-if="activeTab === 'media'"
							key="media"
							class="space-y-6 animate-fadeIn"
						>
							<input
								ref="imageInput"
								type="file"
								accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
								@change="handleImageChange"
								class="hidden"
							/>
							<div v-if="imagePreview">
								<label class="text-sm font-semibold text-gray-700 mb-3 block"
									>Vista Previa Actual</label
								>
								<div class="relative group">
									<div
										class="aspect-video rounded-2xl overflow-hidden border-4 border-gray-100 shadow-lg"
									>
										<img
											:src="imagePreview"
											alt="Preview"
											class="w-full h-full object-cover"
										/>
										<div
											class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"
										/>
										<button
											type="button"
											@click="imageInput?.click()"
											class="absolute bottom-4 right-4 flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg opacity-0 group-hover:opacity-100 transform group-hover:scale-100 scale-90 transition-all duration-300 shadow-lg hover:bg-blue-700"
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
											Reemplazar imagen
										</button>
									</div>
								</div>
							</div>
							<div>
								<label class="text-sm font-semibold text-gray-700 mb-2 block">{{
									imagePreview ? 'Cambiar imagen' : 'Imagen'
								}}</label>
								<div
									@click="imageInput?.click()"
									class="border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center hover:border-indigo-400 hover:bg-indigo-50/50 transition-all duration-300 cursor-pointer group"
									:class="{ 'border-red-500': formErrors.hit_imagen }"
								>
									<div class="flex flex-col items-center gap-4">
										<div
											class="p-4 bg-indigo-100 rounded-full group-hover:bg-indigo-200 transition-colors duration-300"
										>
											<svg
												class="h-8 w-8 text-indigo-600"
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
										<div>
											<p class="text-lg font-semibold text-gray-700 mb-1">
												Arrastra tu imagen aquí
											</p>
											<p class="text-sm text-gray-500">
												o haz clic para seleccionar
											</p>
										</div>
										<div class="flex items-center gap-2 text-xs text-gray-400">
											<span>PNG, JPG, WEBP, GIF</span>
											<span>•</span>
											<span>Máx. 4MB</span>
											<span>•</span>
											<span>Máx. 4000×4000px</span>
										</div>
									</div>
								</div>
								<p v-if="formErrors.hit_imagen" class="text-red-500 text-sm mt-2">
									{{ formErrors.hit_imagen[0] }}
								</p>
							</div>
						</div>

						<!-- TAB 3: Programación -->
						<div
							v-else-if="activeTab === 'schedule'"
							key="schedule"
							class="space-y-6 animate-fadeIn"
						>
							<div class="grid grid-cols-2 gap-4">
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
										Fecha del evento *
									</label>
									<input
										v-model="form.hit_fecha"
										type="date"
										class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900"
										:class="{ 'border-red-500': formErrors.hit_fecha }"
									/>
									<p
										v-if="formErrors.hit_fecha"
										class="text-red-500 text-sm mt-1"
									>
										{{ formErrors.hit_fecha[0] }}
									</p>
								</div>
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
												d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"
											/>
										</svg>
										Orden de visualización
									</label>
									<input
										v-model.number="form.hit_orden"
										type="number"
										min="0"
										class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900"
										:class="{ 'border-red-500': formErrors.hit_orden }"
									/>
									<p
										v-if="formErrors.hit_orden"
										class="text-red-500 text-sm mt-1"
									>
										{{ formErrors.hit_orden[0] }}
									</p>
								</div>
							</div>
							<div>
								<label class="text-sm font-semibold text-gray-700 mb-2 block"
									>Estado *</label
								>
								<select
									v-model="form.hit_estatus"
									class="w-full px-4 py-3 pr-10 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900 appearance-none bg-white"
									:class="{ 'border-red-500': formErrors.hit_estatus }"
									style="
										background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3e%3cpolyline points=%276 9 12 15 18 9%27%3e%3c/polyline%3e%3c/svg%3e');
										background-repeat: no-repeat;
										background-position: right 0.75rem center;
										background-size: 1.25em;
									"
								>
									<option value="Guardado">Guardado (Borrador)</option>
									<option value="Publicado">Publicado</option>
								</select>
								<p v-if="formErrors.hit_estatus" class="text-red-500 text-sm mt-1">
									{{ formErrors.hit_estatus[0] }}
								</p>
							</div>
						</div>
					</transition>
				</div>

				<!-- Footer crear/editar -->
				<div
					class="border-t border-gray-200 bg-gray-50 px-8 py-4 flex justify-end gap-3 flex-shrink-0"
				>
					<button
						@click="closeModal"
						class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-100 transition-all duration-200"
					>
						Cancelar
					</button>
					<button
						@click="saveHito"
						:disabled="loading"
						class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
					>
						{{
							loading
								? 'Guardando...'
								: modalMode === 'edit'
									? 'Guardar Cambios'
									: 'Crear Hito'
						}}
					</button>
				</div>
			</div>
		</div>

		<!-- ═══════════════════════════════════════════════════════════
		     MODAL VER DETALLES
		═══════════════════════════════════════════════════════════ -->
		<div
			v-if="showModal && modalMode === 'view'"
			class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fadeIn"
			@click.self="closeModal"
		>
			<div
				class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-scaleIn"
			>
				<!-- Header -->
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
										d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
									/>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
									/>
								</svg>
							</div>
							<div>
								<h3 class="text-xl font-bold text-white">Vista Detallada</h3>
								<p class="text-white/80 text-sm">Información del hito</p>
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

				<!-- Contenido scrollable -->
				<div class="flex-1 overflow-y-auto p-8 space-y-8">
					<!-- Imagen -->
					<div
						class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-6 border border-gray-200"
					>
						<div class="flex items-center gap-2 mb-5">
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
									d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
								/>
							</svg>
							<h4 class="font-semibold text-gray-900">Imagen del Hito</h4>
						</div>
						<div class="flex justify-center">
							<div
								v-if="selectedHito?.hit_imagen"
								class="bg-white rounded-xl overflow-hidden shadow-md max-w-2xl w-full"
							>
								<img
									:src="getImageUrl(selectedHito.hit_imagen)"
									:alt="selectedHito.hit_titulo"
									class="w-full h-auto object-cover"
								/>
							</div>
							<div
								v-else
								class="w-full max-w-2xl h-40 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center"
							>
								<div class="text-center text-gray-400">
									<svg
										class="h-10 w-10 mx-auto mb-2"
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
									<p class="text-sm">Sin imagen</p>
								</div>
							</div>
						</div>
					</div>

					<!-- Información General -->
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
										d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
									/>
								</svg>
								<h4 class="font-semibold text-gray-900">Información General</h4>
							</div>
						</div>
						<div class="p-6 space-y-6">
							<!-- Título -->
							<div>
								<label
									class="flex items-center gap-2 text-sm font-medium text-gray-600 mb-3"
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
											d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"
										/>
									</svg>
									Título
								</label>
								<div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200">
									<p class="text-gray-900 font-medium">
										{{ selectedHito?.hit_titulo }}
									</p>
								</div>
							</div>
							<!-- Descripción -->
							<div>
								<label
									class="flex items-center gap-2 text-sm font-medium text-gray-600 mb-3"
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
											d="M4 6h16M4 12h16M4 18h7"
										/>
									</svg>
									Descripción
								</label>
								<div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200">
									<p class="text-gray-700">
										{{ selectedHito?.hit_descripcion || 'Sin descripción' }}
									</p>
								</div>
							</div>
							<!-- Categoría y Orden -->
							<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
								<div>
									<label
										class="flex items-center gap-2 text-sm font-medium text-gray-600 mb-3"
									>
										<svg
											class="h-4 w-4 text-pink-500"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
											/>
										</svg>
										Categoría
									</label>
									<div
										class="bg-gradient-to-br from-pink-50 to-purple-50 rounded-xl px-4 py-3 border border-pink-200"
									>
										<p class="text-gray-900 font-medium">
											{{ selectedHito?.hit_categoria || 'Sin categoría' }}
										</p>
									</div>
								</div>
								<div>
									<label
										class="flex items-center gap-2 text-sm font-medium text-gray-600 mb-3"
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
												d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"
											/>
										</svg>
										Orden de visualización
									</label>
									<div
										class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200 flex items-center justify-center"
									>
										<span class="text-2xl font-bold text-indigo-600">{{
											selectedHito?.hit_orden ?? '—'
										}}</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Fecha y Estado -->
					<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
						<div
							class="bg-gradient-to-r from-emerald-50 to-teal-50 px-6 py-4 border-b border-gray-200"
						>
							<div class="flex items-center gap-2">
								<svg
									class="h-5 w-5 text-emerald-600"
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
								<h4 class="font-semibold text-gray-900">Fecha y Publicación</h4>
							</div>
						</div>
						<div class="p-6">
							<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
								<div>
									<label
										class="flex items-center gap-2 text-sm font-medium text-gray-600 mb-3"
									>
										<svg
											class="h-4 w-4 text-emerald-500"
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
										Fecha del evento
									</label>
									<div
										class="bg-emerald-50 rounded-xl px-4 py-3 border border-emerald-200"
									>
										<p class="text-gray-900 font-medium">
											{{ formatDate(selectedHito?.hit_fecha) }}
										</p>
									</div>
								</div>
								<div>
									<label
										class="flex items-center gap-2 text-sm font-medium text-gray-600 mb-3"
									>
										<svg
											class="h-4 w-4 text-teal-500"
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
										Estado
									</label>
									<div class="flex items-center gap-3 mt-1">
										<span
											:class="[
												'inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm border-2',
												selectedHito?.hit_estatus === 'Publicado'
													? 'bg-emerald-50 text-emerald-700 border-emerald-200'
													: 'bg-yellow-50 text-yellow-700 border-yellow-200',
											]"
										>
											<span
												:class="[
													'h-2.5 w-2.5 rounded-full',
													selectedHito?.hit_estatus === 'Publicado'
														? 'bg-emerald-500 animate-pulse'
														: 'bg-yellow-500',
												]"
											></span>
											{{ selectedHito?.hit_estatus }}
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Información del Sistema -->
					<div
						class="bg-gradient-to-br from-gray-50 to-slate-100 rounded-2xl border border-gray-200 p-6"
					>
						<div class="flex items-center gap-2 mb-5">
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
									d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
								/>
							</svg>
							<h4 class="font-semibold text-gray-900">Información del Sistema</h4>
						</div>
						<div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
							<div class="flex items-center gap-2">
								<svg
									class="h-4 w-4 text-gray-500"
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
								<span class="text-gray-600">Creado por:</span>
								<span class="font-semibold text-gray-900">{{
									selectedHito?.user?.usu_nombre || 'N/A'
								}}</span>
							</div>
							<div class="flex items-center gap-2">
								<svg
									class="h-4 w-4 text-gray-500"
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
									>#{{ selectedHito?.hit_id }}</span
								>
							</div>
							<div class="flex items-center gap-2">
								<svg
									class="h-4 w-4 text-gray-500"
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
								<span class="text-gray-600">Creado:</span>
								<span class="font-medium text-gray-900">{{
									formatDate(selectedHito?.created_at)
								}}</span>
							</div>
							<div class="flex items-center gap-2">
								<svg
									class="h-4 w-4 text-gray-500"
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
									formatDate(selectedHito?.updated_at)
								}}</span>
							</div>
						</div>
					</div>
				</div>

				<!-- Footer ver -->
				<div class="border-t border-gray-200 bg-gray-50 px-6 py-5 flex-shrink-0">
					<div class="flex justify-between items-center gap-4">
						<button
							@click="openModal('edit', selectedHito)"
							class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold rounded-xl hover:from-amber-600 hover:to-orange-600 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center gap-2"
						>
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
									d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
								/>
							</svg>
							Editar Hito
						</button>
						<button
							@click="closeModal"
							class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center gap-2"
						>
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
									d="M6 18L18 6M6 6l12 12"
								/>
							</svg>
							Cerrar
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- ═══ Modal eliminar individual ════════════════════════════ -->
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
					<h3 class="text-xl font-bold text-white">¿Eliminar Hito?</h3>
				</div>
				<div class="p-8">
					<p class="text-gray-700 text-center mb-2 text-lg">
						Estás a punto de eliminar el hito:
					</p>
					<p class="text-center font-bold text-gray-900 text-xl mb-6 pb-2">
						"{{ hitoToDelete?.hit_titulo }}"
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
									Esta acción no se puede deshacer. El hito será eliminado
									permanentemente.
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
							@click="deleteHito"
							:disabled="loading"
							class="flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white font-semibold rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
						>
							{{ loading ? 'Eliminando...' : 'Eliminar' }}
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- ═══ Modal bulk delete ════════════════════════════════════ -->
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
							>{{ selectedHitos.length }} hito(s)</span
						>
						seleccionado(s).
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
									Todos los hitos seleccionados serán eliminados permanentemente
									del sistema.
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
							{{ loading ? 'Eliminando...' : 'Eliminar Todos' }}
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- ═══ Modal cambio de estado masivo ════════════════════════ -->
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
					<h3 class="text-xl font-bold text-white">Cambio de Estado</h3>
				</div>
				<div class="p-8">
					<p class="text-gray-700 text-center mb-6 text-lg">
						Estás a punto de marcar
						<span class="font-bold text-indigo-600"
							>{{ selectedHitos.length }} hito(s)</span
						>
						como <span class="font-bold">"{{ bulkStatusTarget }}"</span>.
					</p>
					<div class="flex gap-3">
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
