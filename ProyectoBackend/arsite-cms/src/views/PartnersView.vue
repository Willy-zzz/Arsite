<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { formatDate, formatDateForInput } from '@/utils/dateFormatter'

const authStore = useAuthStore()
const router = useRouter()

// Estado
const partners = ref([])
const loading = ref(false)
const error = ref(null)
const showModal = ref(false)
const showDeleteConfirm = ref(false)
const showAdvancedFilters = ref(false)
const showBulkDeleteConfirm = ref(false)
const modalMode = ref('view') // 'edit', 'view'
const selectedPartner = ref(null)
const partnerToDelete = ref(null)
const selectedPartners = ref([])
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
	mine: false,
	fecha_desde: '',
	fecha_hasta: '',
	sort_by: 'par_orden',
	sort_direction: 'asc',
})

// Formulario
const form = ref({
	par_nombre: '',
	par_logo: null,
	par_orden: 0,
	par_fecha_publicacion: '',
	par_fecha_terminacion: '',
	par_estatus: 'Guardado',
})

const formErrors = ref({})
const imagePreview = ref(null)
const imageInput = ref(null)

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
	get: () => partners.value.length > 0 && selectedPartners.value.length === partners.value.length,
	set: (value) => {
		if (value) {
			selectedPartners.value = partners.value.map((p) => p.par_id)
		} else {
			selectedPartners.value = []
		}
	},
})

const hasSelectedPartners = computed(() => selectedPartners.value.length > 0)

const displayRange = computed(() => {
	if (partners.value.length === 0) return '0-0'
	const start = (pagination.value.current_page - 1) * pagination.value.per_page + 1
	const end = Math.min(start + partners.value.length - 1, pagination.value.total)
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
		filters.value.sort_direction = 'asc'
	}
	applyFilters()
}

const applyFilters = () => {
	pagination.value.current_page = 1
	fetchPartners()
}

const clearAdvancedFilters = () => {
	filters.value.search = ''
	filters.value.mine = false
	filters.value.fecha_desde = ''
	filters.value.fecha_hasta = ''
	applyFilters()
}

// Métodos principales
const fetchPartners = async () => {
	loading.value = true
	error.value = null

	try {
		const params = {
			page: pagination.value.current_page,
			per_page: pagination.value.per_page,
			...filters.value,
		}

		const response = await api.get('/partners', { params })

		if (response.data.success) {
			partners.value = response.data.data.data
			pagination.value = {
				current_page: response.data.data.current_page,
				last_page: response.data.data.last_page,
				per_page: response.data.data.per_page,
				total: response.data.data.total,
			}
		}
	} catch (err) {
		error.value = err.response?.data?.message || 'Error al cargar partners'
		console.error('Error fetching partners:', err)
	} finally {
		loading.value = false
	}
}

const openModal = (mode, partner = null) => {
	modalMode.value = mode
	selectedPartner.value = partner
	formErrors.value = {}
	activeTab.value = 'general'

	if (mode === 'edit' && partner) {
		form.value = {
			par_nombre: partner.par_nombre,
			par_logo: null,
			par_orden: partner.par_orden,
			par_fecha_publicacion: formatDateForInput(partner.par_fecha_publicacion),
			par_fecha_terminacion: formatDateForInput(partner.par_fecha_terminacion),
			par_estatus: partner.par_estatus,
		}
		imagePreview.value = partner.par_logo ? getImageUrl(partner.par_logo) : null
	}

	showModal.value = true
}

const closeModal = () => {
	showModal.value = false
	selectedPartner.value = null
	resetForm()
}

const resetForm = () => {
	form.value = {
		par_nombre: '',
		par_logo: null,
		par_orden: 0,
		par_fecha_publicacion: '',
		par_fecha_terminacion: '',
		par_estatus: 'Guardado',
	}
	imagePreview.value = null
	formErrors.value = {}
}

const handleImageChange = (event) => {
	const file = event.target.files[0]
	if (file) {
		form.value.par_logo = file
		const reader = new FileReader()
		reader.onload = (e) => {
			imagePreview.value = e.target.result
		}
		reader.readAsDataURL(file)
	}
}

const savePartner = async () => {
	loading.value = true
	formErrors.value = {}

	try {
		// VALIDACIÓN FRONTEND
		if (!form.value.par_nombre || form.value.par_nombre.trim() === '') {
			formErrors.value.par_nombre = ['El nombre es obligatorio']
		}

		if (!imagePreview.value) {
			formErrors.value.par_logo = ['El logo es obligatorio']
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

		// PREPARAR DATOS PARA ENVIAR
		const formData = new FormData()

		Object.keys(form.value).forEach((key) => {
			if (key === 'par_logo') return
			if (form.value[key] !== null && form.value[key] !== '') {
				formData.append(key, form.value[key])
			}
		})

		if (form.value.par_logo instanceof File) {
			formData.append('par_logo', form.value.par_logo)
		}

		// ENVIAR AL BACKEND
		formData.append('_method', 'PUT')
		const response = await api.post(`/partners/${selectedPartner.value.par_id}`, formData, {
			headers: { 'Content-Type': 'multipart/form-data' },
		})

		if (response.data.success) {
			closeModal()
			fetchPartners()
			showToast(response.data.message, 'success')
		}
	} catch (err) {
		if (err.response?.status === 422) {
			formErrors.value = err.response.data.errors || {}
		}
		error.value = err.response?.data?.message || 'Error al guardar partner'
		showToast(error.value, 'error')
	} finally {
		loading.value = false
	}
}

const confirmDelete = (partner) => {
	partnerToDelete.value = partner
	showDeleteConfirm.value = true
}

const handleDeleteSelected = () => {
	if (selectedPartners.value.length === 0) return

	if (selectedPartners.value.length === 1) {
		const partnerId = selectedPartners.value[0]
		const partner = partners.value.find((p) => p.par_id === partnerId)
		if (partner) {
			partnerToDelete.value = partner
			showDeleteConfirm.value = true
		}
	} else {
		showBulkDeleteConfirm.value = true
	}
}

const deletePartner = async () => {
	if (!partnerToDelete.value) return

	loading.value = true
	try {
		const response = await api.delete(`/partners/${partnerToDelete.value.par_id}`)

		if (response.data.success) {
			fetchPartners()
			showToast(response.data.message, 'success')
			selectedPartners.value = []
		}
	} catch (err) {
		error.value = err.response?.data?.message || 'Error al eliminar partner'
		showToast(error.value, 'error')
	} finally {
		loading.value = false
		showDeleteConfirm.value = false
		partnerToDelete.value = null
	}
}

const bulkDelete = async () => {
	if (selectedPartners.value.length === 0) return

	loading.value = true
	try {
		const response = await api.delete('/partners/bulk-delete', {
			data: { ids: selectedPartners.value },
		})

		if (response.data.success) {
			selectedPartners.value = []
			fetchPartners()
			showToast(response.data.message, 'success')
		}
	} catch (err) {
		showToast(err.response?.data?.message || 'Error al eliminar partners', 'error')
	} finally {
		loading.value = false
		showBulkDeleteConfirm.value = false
	}
}

// Exportar a Excel
const exportToExcel = async () => {
	try {
		loading.value = true
		const params = { ...filters.value, export: 'excel' }

		const response = await api.get('/partners/export', {
			params,
			responseType: 'blob',
		})

		const blob = new Blob([response.data], {
			type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		})
		const url = window.URL.createObjectURL(blob)
		const link = document.createElement('a')
		link.href = url
		link.download = `partners_${new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Mexico_City' })}.xlsx`
		document.body.appendChild(link)
		link.click()
		document.body.removeChild(link)
		window.URL.revokeObjectURL(url)

		showToast('Archivo Excel exportado exitosamente', 'success')
	} catch (err) {
		exportToCSV()
	} finally {
		loading.value = false
	}
}

// Exportar a PDF
const exportToPDF = async () => {
	try {
		loading.value = true
		const params = { ...filters.value, export: 'pdf' }

		const response = await api.get('/partners/export', {
			params,
			responseType: 'blob',
		})

		const blob = new Blob([response.data], { type: 'application/pdf' })
		const url = window.URL.createObjectURL(blob)
		const link = document.createElement('a')
		link.href = url
		link.download = `partners_${new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Mexico_City' })}.pdf`
		document.body.appendChild(link)
		link.click()
		document.body.removeChild(link)
		window.URL.revokeObjectURL(url)

		showToast('Archivo PDF exportado exitosamente', 'success')
	} catch (err) {
		showToast('Error al exportar a PDF', 'error')
	} finally {
		loading.value = false
	}
}

// Fallback: exportar a CSV
const exportToCSV = () => {
	try {
		const headers = ['ID', 'Nombre', 'Estado', 'Orden', 'Publicación', 'Terminación']
		const rows = partners.value.map((p) => [
			p.par_id,
			p.par_nombre,
			p.par_estatus,
			p.par_orden,
			p.par_fecha_publicacion || '',
			p.par_fecha_terminacion || '',
		])

		const csvContent = [
			headers.join(','),
			...rows.map((row) => row.map((cell) => `"${cell}"`).join(',')),
		].join('\n')

		const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
		const url = window.URL.createObjectURL(blob)
		const link = document.createElement('a')
		link.href = url
		link.download = `partners_${new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Mexico_City' })}.csv`
		document.body.appendChild(link)
		link.click()
		document.body.removeChild(link)
		window.URL.revokeObjectURL(url)

		showToast('Datos exportados a CSV', 'success')
	} catch (err) {
		showToast('Error al exportar datos', 'error')
	}
}

// Drag & Drop para reordenar
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

	const updatedPartners = [...partners.value]
	const [draggedPartner] = updatedPartners.splice(draggedIndex.value, 1)
	updatedPartners.splice(dropIndex, 0, draggedPartner)

	// Actualizar visualmente de inmediato
	partners.value = updatedPartners

	const reorderedData = updatedPartners.map((partner, index) => ({
		id: partner.par_id,
		orden: index + 1,
	}))

	try {
		const response = await api.put('/partners/update-order', {
			partners: reorderedData,
		})

		if (response.data.success) {
			updatedPartners.forEach((partner, index) => {
				partner.par_orden = index + 1
			})
			showToast('Orden actualizado exitosamente', 'success')
		}
	} catch (err) {
		showToast('Error al actualizar el orden', 'error')
		fetchPartners()
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

const changePage = (page) => {
	pagination.value.current_page = page
	fetchPartners()
}

const getImageUrl = (path) => {
	if (!path) return null
	const baseUrl = import.meta.env.VITE_API_BASE_URL.replace('/api', '')
	return `${baseUrl}/storage/${path}`
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
	fetchPartners()
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
				<h3 class="text-xl font-semibold text-[#1c2321]">Administración de Partners</h3>
			</div>

			<!-- Barra de controles -->
			<div class="px-6 py-4 border-b border-gray-150 space-y-4">
				<div class="flex items-center justify-between gap-4">
					<!-- Botón Crear -->
					<button
						@click="router.push('/partners/create')"
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
						Crear Partner
					</button>

					<!-- Filtros (Derecha) -->
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

						<!-- Botón exportar -->
						<div class="relative group">
							<button
								:disabled="loading || partners.length === 0"
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
							<div
								class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10"
							>
								<button
									@click="exportToExcel"
									:disabled="loading || partners.length === 0"
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
									:disabled="loading || partners.length === 0"
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

						<!-- Botón filtros avanzados -->
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

				<!-- Panel de filtros avanzados -->
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
						<div class="space-y-1">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Buscar por nombre</label
							>
							<input
								v-model="filters.search"
								type="text"
								placeholder="Ej: Empresa S.A..."
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
									>Solo míos</span
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
						v-if="hasSelectedPartners"
						class="flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-lg"
					>
						<span class="text-sm font-semibold text-blue-900">
							{{ selectedPartners.length }} partner(s) seleccionado(s)
						</span>
						<div class="flex gap-2">
							<button
								@click="selectedPartners = []"
								class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
							>
								Cancelar
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

			<!-- Tabla -->
			<div class="px-6 pb-6">
				<div
					class="bg-[#004A7C] text-white px-5 py-4 flex justify-between items-center rounded-t-xl"
				>
					<h4 class="text-lg font-bold tracking-tight">Partners</h4>
					<span class="text-sm font-medium">
						Mostrando {{ displayRange }} de {{ pagination.total }} elementos
					</span>
				</div>

				<div class="overflow-x-auto border border-t-0 border-gray-300 rounded-b-xl">
					<table class="w-full border-collapse table-fixed">
						<colgroup>
							<col style="width: 40px" />
							<!-- Checkbox -->
							<col style="width: 55px" />
							<!-- Orden -->
							<col style="width: 150px" />
							<!-- Nombre -->
							<col style="width: 90px" />
							<!-- Logo -->
							<col style="width: 100px" />
							<!-- Estado -->
							<col style="width: 115px" />
							<!-- Publicación -->
							<col style="width: 115px" />
							<!-- Terminación -->
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
								<!-- Orden -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
									@click="setSorting('par_orden')"
								>
									<div class="flex items-center justify-center gap-1">
										<span>Orden</span>
										<svg
											class="w-3 h-3"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												v-if="
													filters.sort_by === 'par_orden' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'par_orden' &&
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
								<!-- Nombre -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
									@click="setSorting('par_nombre')"
								>
									<div class="flex items-center justify-center gap-1">
										<span>Nombre</span>
										<svg
											class="w-3 h-3"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												v-if="
													filters.sort_by === 'par_nombre' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'par_nombre' &&
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
									Logo
								</th>
								<!-- Estado -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
									@click="setSorting('par_estatus')"
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
													filters.sort_by === 'par_estatus' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'par_estatus' &&
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
								<!-- Publicación -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
									@click="setSorting('par_fecha_publicacion')"
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
													filters.sort_by === 'par_fecha_publicacion' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'par_fecha_publicacion' &&
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
									Terminación
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
							<tr v-else-if="error && partners.length === 0">
								<td colspan="10" class="py-20 text-center text-red-600">
									{{ error }}
								</td>
							</tr>
							<tr v-else-if="partners.length === 0">
								<td colspan="10" class="py-20 text-center text-gray-400 italic">
									No se encontraron partners con los criterios seleccionados
								</td>
							</tr>
							<tr
								v-else
								v-for="(partner, index) in partners"
								:key="partner.par_id"
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
								<td class="px-2 py-3 text-center">
									<input
										v-model="selectedPartners"
										:value="partner.par_id"
										type="checkbox"
										class="w-4 h-4 text-[#312AFF] rounded border-gray-300"
										@click.stop
									/>
								</td>
								<td
									class="px-2 py-3 text-center text-xs font-semibold text-gray-900"
								>
									{{ partner.par_orden ?? '—' }}
								</td>
								<td
									class="px-2 py-3 text-center text-xs font-semibold text-gray-900"
								>
									<div class="truncate" :title="partner.par_nombre">
										{{ partner.par_nombre }}
									</div>
								</td>
								<td class="px-2 py-3 text-center">
									<div class="flex justify-center items-center">
										<!-- Logo con fondo blanco para logos transparentes -->
										<div
											v-if="partner.par_logo"
											class="w-16 h-16 rounded-lg overflow-hidden shadow-md border border-gray-200 bg-white flex items-center justify-center p-1"
										>
											<img
												:src="getImageUrl(partner.par_logo)"
												:alt="partner.par_nombre"
												class="max-w-full max-h-full object-contain hover:scale-110 transition-transform duration-200 cursor-pointer"
												@click="openModal('view', partner)"
											/>
										</div>
										<div
											v-else
											class="w-16 h-16 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center"
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
								<td class="px-2 py-3 text-center">
									<span
										:class="getStatusColor(partner.par_estatus)"
										class="inline-block px-2 py-0.5 text-[11px] font-bold uppercase rounded-full border whitespace-nowrap"
									>
										{{ partner.par_estatus }}
									</span>
								</td>
								<td
									class="px-2 py-3 text-center text-[12px] text-gray-600 whitespace-nowrap"
								>
									{{ formatDate(partner.par_fecha_publicacion) }}
								</td>
								<td
									class="px-2 py-3 text-center text-[12px] text-gray-600 whitespace-nowrap"
								>
									{{ formatDate(partner.par_fecha_terminacion) }}
								</td>
								<td class="px-2 py-3 text-center">
									<button
										@click="openModal('view', partner)"
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
								<td class="px-2 py-3 text-center">
									<button
										@click="openModal('edit', partner)"
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
										@click="confirmDelete(partner)"
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

		<!-- ==================== MODAL EDITAR ==================== -->
		<div
			v-if="showModal && modalMode === 'edit'"
			class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fadeIn"
			@click.self="closeModal"
		>
			<div
				class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden animate-slideUp max-h-[90vh] flex flex-col"
			>
				<!-- HEADER -->
				<div
					class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex justify-between items-center flex-shrink-0"
				>
					<div>
						<h3 class="text-xl font-bold text-white">Editar Partner</h3>
						<p class="text-indigo-100 text-xs mt-0.5">
							Actualiza la información del partner
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
					<div class="flex gap-1 px-6">
						<button
							type="button"
							@click="activeTab = 'general'"
							:class="[
								'flex items-center gap-2 px-5 py-4 font-medium text-sm transition-all duration-200 border-b-2',
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
								'flex items-center gap-2 px-5 py-4 font-medium text-sm transition-all duration-200 border-b-2',
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
									d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
								/>
							</svg>
							Logo
						</button>
						<button
							type="button"
							@click="activeTab = 'schedule'"
							:class="[
								'flex items-center gap-2 px-5 py-4 font-medium text-sm transition-all duration-200 border-b-2',
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

				<!-- CONTENIDO TABS -->
				<div class="p-8 overflow-y-auto flex-1">
					<form @submit.prevent="savePartner" class="space-y-6">
						<transition name="tab-content" mode="out-in">
							<!-- TAB 1: Información General -->
							<div
								v-if="activeTab === 'general'"
								key="general"
								class="space-y-6 animate-fadeIn"
							>
								<!-- Nombre -->
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
												d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
											/>
										</svg>
										Nombre del Partner *
									</label>
									<input
										v-model="form.par_nombre"
										type="text"
										maxlength="50"
										placeholder="Ej: Empresa Tecnológica S.A."
										class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900"
										:class="{ 'border-red-500': formErrors.par_nombre }"
									/>
									<div class="flex justify-between items-center mt-1.5">
										<p
											v-if="formErrors.par_nombre"
											class="text-red-500 text-sm"
										>
											{{ formErrors.par_nombre[0] }}
										</p>
										<p class="text-xs text-gray-500 ml-auto">
											{{ form.par_nombre?.length || 0 }}/50 caracteres
										</p>
									</div>
								</div>

								<!-- Estado -->
								<div>
									<label class="text-sm font-semibold text-gray-700 mb-2 block"
										>Estado *</label
									>
									<select
										v-model="form.par_estatus"
										class="w-full px-4 py-3 pr-10 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900 appearance-none bg-white"
										:class="{ 'border-red-500': formErrors.par_estatus }"
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
									<p
										v-if="formErrors.par_estatus"
										class="text-red-500 text-sm mt-1"
									>
										{{ formErrors.par_estatus[0] }}
									</p>
								</div>
							</div>

							<!-- TAB 2: Logo -->
							<div
								v-else-if="activeTab === 'media'"
								key="media"
								class="space-y-6 animate-fadeIn"
							>
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
												d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
											/>
										</svg>
										Logo del Partner *
									</label>

									<!-- Preview del logo actual -->
									<div v-if="imagePreview" class="mb-4 relative group">
										<div
											class="rounded-xl overflow-hidden border-2 border-indigo-200 shadow-md bg-white flex items-center justify-center p-6"
											style="min-height: 160px"
										>
											<img
												:src="imagePreview"
												alt="Preview logo"
												class="max-h-32 max-w-full object-contain"
											/>
											<!-- Overlay con botón reemplazar -->
											<div
												class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl flex items-center justify-center"
											>
												<button
													type="button"
													@click="imageInput?.click()"
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
													Reemplazar logo
												</button>
											</div>
										</div>
									</div>

									<!-- Input file oculto -->
									<input
										ref="imageInput"
										type="file"
										accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp"
										@change="handleImageChange"
										class="hidden"
									/>

									<!-- Área drag & drop -->
									<div
										@click="imageInput?.click()"
										class="border-2 border-dashed rounded-2xl p-8 text-center hover:border-indigo-400 hover:bg-indigo-50/50 transition-all duration-300 cursor-pointer group"
										:class="[
											imagePreview
												? 'border-gray-200 bg-gray-50'
												: 'border-indigo-300 bg-indigo-50',
											formErrors.par_logo ? 'border-red-500' : '',
										]"
									>
										<div class="flex flex-col items-center gap-4">
											<div
												class="p-4 rounded-full transition-colors duration-300"
												:class="
													imagePreview
														? 'bg-gray-100'
														: 'bg-indigo-100 group-hover:bg-indigo-200'
												"
											>
												<svg
													class="h-8 w-8"
													:class="
														imagePreview
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
											<div>
												<p
													class="text-sm font-semibold"
													:class="
														imagePreview
															? 'text-gray-500'
															: 'text-indigo-600'
													"
												>
													{{
														imagePreview
															? 'Cambiar logo'
															: 'Arrastra tu logo aquí'
													}}
												</p>
												<p class="text-xs text-gray-400 mt-1">
													o haz clic para seleccionar
												</p>
											</div>
											<p class="text-xs text-gray-400">
												PNG, JPG, GIF, SVG, WEBP • Máx. 3MB • Recomendado:
												fondo transparente
											</p>
										</div>
									</div>
									<p v-if="formErrors.par_logo" class="text-red-500 text-sm mt-2">
										{{ formErrors.par_logo[0] }}
									</p>
								</div>
							</div>

							<!-- TAB 3: Programación -->
							<div
								v-else-if="activeTab === 'schedule'"
								key="schedule"
								class="space-y-6 animate-fadeIn"
							>
								<!-- Fechas -->
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
											Fecha de Publicación
										</label>
										<input
											v-model="form.par_fecha_publicacion"
											type="date"
											class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900"
											:class="{
												'border-red-500': formErrors.par_fecha_publicacion,
											}"
										/>
										<p
											v-if="formErrors.par_fecha_publicacion"
											class="text-red-500 text-sm mt-1"
										>
											{{ formErrors.par_fecha_publicacion[0] }}
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
													d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
												/>
											</svg>
											Fecha de Terminación
										</label>
										<input
											v-model="form.par_fecha_terminacion"
											type="date"
											class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900"
											:class="{
												'border-red-500': formErrors.par_fecha_terminacion,
											}"
										/>
										<p
											v-if="formErrors.par_fecha_terminacion"
											class="text-red-500 text-sm mt-1"
										>
											{{ formErrors.par_fecha_terminacion[0] }}
										</p>
									</div>
								</div>

								<!-- Orden -->
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
										Orden de Visualización
									</label>
									<input
										v-model.number="form.par_orden"
										type="number"
										min="0"
										class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900"
										:class="{ 'border-red-500': formErrors.par_orden }"
									/>
									<p
										v-if="formErrors.par_orden"
										class="text-red-500 text-sm mt-1"
									>
										{{ formErrors.par_orden[0] }}
									</p>
								</div>

								<!-- Info box programación automática -->
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
											<p class="font-medium mb-1">Programación automática</p>
											<p class="text-blue-600">
												El partner se publicará y ocultará automáticamente
												según las fechas configuradas.
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
						@click="savePartner"
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
				class="bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden animate-scaleIn"
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
								<p class="text-white/80 text-sm">Información del partner</p>
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
					<!-- Logo del Partner -->
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
							<h4 class="font-semibold text-gray-900">Logo del Partner</h4>
						</div>
						<!-- Fondo blanco para logos con transparencia -->
						<div class="flex justify-center">
							<div
								class="bg-white rounded-xl shadow-md p-8 flex items-center justify-center"
								style="min-height: 160px; min-width: 240px"
							>
								<img
									v-if="selectedPartner?.par_logo"
									:src="getImageUrl(selectedPartner.par_logo)"
									:alt="selectedPartner.par_nombre"
									class="max-h-32 max-w-full object-contain"
								/>
								<div v-else class="flex flex-col items-center gap-2 text-gray-300">
									<svg
										class="w-16 h-16"
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
									<span class="text-sm">Sin logo</span>
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
						<div class="p-6 space-y-5">
							<!-- Nombre -->
							<div>
								<label
									class="flex items-center gap-2 text-sm font-medium text-gray-600 mb-2"
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
											d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
										/>
									</svg>
									Nombre
								</label>
								<div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200">
									<p class="text-gray-900 font-medium">
										{{ selectedPartner?.par_nombre }}
									</p>
								</div>
							</div>

							<!-- Estado y Orden -->
							<div class="grid grid-cols-2 gap-4">
								<div>
									<label
										class="flex items-center gap-2 text-sm font-medium text-gray-600 mb-2"
									>
										<svg
											class="h-4 w-4 text-green-500"
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
									<div
										class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200 flex justify-center"
									>
										<span
											:class="getStatusColor(selectedPartner?.par_estatus)"
											class="inline-block px-3 py-1 text-xs font-bold uppercase rounded-full border"
										>
											{{ selectedPartner?.par_estatus }}
										</span>
									</div>
								</div>
								<div>
									<label
										class="flex items-center gap-2 text-sm font-medium text-gray-600 mb-2"
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
										Orden
									</label>
									<div
										class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200 flex items-center justify-center"
									>
										<span class="text-xl font-bold text-indigo-600">{{
											selectedPartner?.par_orden ?? '—'
										}}</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Programación -->
					<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
						<div
							class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200"
						>
							<div class="flex items-center gap-2">
								<svg
									class="h-5 w-5 text-blue-600"
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
								<h4 class="font-semibold text-gray-900">Programación</h4>
							</div>
						</div>
						<div class="p-6 grid grid-cols-2 gap-4">
							<div>
								<label class="text-sm font-medium text-gray-600 mb-2 block"
									>Fecha de Publicación</label
								>
								<div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200">
									<p class="text-gray-900 font-medium">
										{{ formatDate(selectedPartner?.par_fecha_publicacion) }}
									</p>
								</div>
							</div>
							<div>
								<label class="text-sm font-medium text-gray-600 mb-2 block"
									>Fecha de Terminación</label
								>
								<div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200">
									<p class="text-gray-900 font-medium">
										{{ formatDate(selectedPartner?.par_fecha_terminacion) }}
									</p>
								</div>
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
									>#{{ selectedPartner?.par_id }}</span
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
								<span class="text-gray-600">Creado por:</span>
								<span class="font-medium text-gray-900">{{
									selectedPartner?.user?.usu_nombre ?? '—'
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
									formatDate(selectedPartner?.created_at)
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
									formatDate(selectedPartner?.updated_at)
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

		<!-- Modal de confirmación de eliminación individual -->
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
					<h3 class="text-xl font-bold text-white">¿Eliminar Partner?</h3>
				</div>
				<div class="p-8">
					<p class="text-gray-700 text-center mb-2 text-lg">
						Estás a punto de eliminar el partner:
					</p>
					<p class="text-center font-bold text-gray-900 text-xl mb-6 pb-2">
						"{{ partnerToDelete?.par_nombre }}"
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
									Esta acción no se puede deshacer. El partner será eliminado
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
							@click="deletePartner"
							:disabled="loading"
							class="flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white font-semibold rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
						>
							{{ loading ? 'Eliminando...' : 'Eliminar' }}
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- Modal de confirmación de eliminación en lote -->
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
							>{{ selectedPartners.length }} partner(s)</span
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
									Todos los partners seleccionados serán eliminados
									permanentemente del sistema.
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
