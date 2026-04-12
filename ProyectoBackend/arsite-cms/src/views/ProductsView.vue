<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { formatDate, formatDateForInput } from '@/utils/dateFormatter'

const authStore = useAuthStore()
const router = useRouter()

// Estado
const productos = ref([])
const loading = ref(false)
const error = ref(null)
const showModal = ref(false)
const showDeleteConfirm = ref(false)
const showAdvancedFilters = ref(false)
const showBulkDeleteConfirm = ref(false)
const modalMode = ref('view') // 'edit', 'view'
const selectedProducto = ref(null)
const productoToDelete = ref(null)
const selectedProductos = ref([])
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
	sort_by: 'pro_nombre',
	sort_direction: 'asc',
})

// Formulario
const form = ref({
	pro_nombre: '',
	pro_imagen: null,
	pro_estatus: 'Guardado',
})

const formErrors = ref({})
const imagePreview = ref(null)

// Computed
const hasAdvancedFiltersActive = computed(() => {
	return filters.value.search || filters.value.mine
})

const allSelected = computed({
	get: () =>
		productos.value.length > 0 && selectedProductos.value.length === productos.value.length,
	set: (value) => {
		if (value) {
			selectedProductos.value = productos.value.map((p) => p.pro_id)
		} else {
			selectedProductos.value = []
		}
	},
})

const hasSelectedProductos = computed(() => selectedProductos.value.length > 0)

const displayRange = computed(() => {
	if (productos.value.length === 0) return '0-0'
	const start = (pagination.value.current_page - 1) * pagination.value.per_page + 1
	const end = Math.min(start + productos.value.length - 1, pagination.value.total)
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
	fetchProductos()
}

const clearAdvancedFilters = () => {
	filters.value.search = ''
	filters.value.mine = false
	applyFilters()
}

// Métodos principales
const fetchProductos = async () => {
	loading.value = true
	error.value = null

	try {
		const params = {
			page: pagination.value.current_page,
			per_page: pagination.value.per_page,
			...filters.value,
		}

		const response = await api.get('/productos', { params })

		if (response.data.success) {
			productos.value = response.data.data.data
			pagination.value = {
				current_page: response.data.data.current_page,
				last_page: response.data.data.last_page,
				per_page: response.data.data.per_page,
				total: response.data.data.total,
			}
		}
	} catch (err) {
		error.value = err.response?.data?.message || 'Error al cargar productos'
		console.error('Error fetching productos:', err)
	} finally {
		loading.value = false
	}
}

const openModal = (mode, producto = null) => {
	modalMode.value = mode
	selectedProducto.value = producto
	formErrors.value = {}
	activeTab.value = 'general'

	if (mode === 'edit' && producto) {
		form.value = {
			pro_nombre: producto.pro_nombre,
			pro_imagen: null,
			pro_estatus: producto.pro_estatus,
		}
		imagePreview.value = producto.pro_imagen ? getImageUrl(producto.pro_imagen) : null
	}

	showModal.value = true
}

const closeModal = () => {
	showModal.value = false
	selectedProducto.value = null
	resetForm()
}

const resetForm = () => {
	form.value = {
		pro_nombre: '',
		pro_imagen: null,
		pro_estatus: 'Guardado',
	}
	imagePreview.value = null
	formErrors.value = {}
}

const handleImageChange = (event) => {
	const file = event.target.files[0]
	if (file) {
		form.value.pro_imagen = file
		const reader = new FileReader()
		reader.onload = (e) => {
			imagePreview.value = e.target.result
		}
		reader.readAsDataURL(file)
	}
}

const saveProducto = async () => {
	loading.value = true
	formErrors.value = {}

	try {
		// VALIDACIÓN FRONTEND
		const camposObligatorios = {
			pro_nombre: 'El nombre del producto es obligatorio',
		}

		for (const [campo, mensaje] of Object.entries(camposObligatorios)) {
			if (!form.value[campo] || form.value[campo].trim() === '') {
				formErrors.value[campo] = [mensaje]
			}
		}

		if (!imagePreview.value) {
			formErrors.value.pro_imagen = ['La imagen es obligatoria']
		}

		if (Object.keys(formErrors.value).length > 0) {
			const camposVacios = Object.values(formErrors.value)
				.map((err) => err[0])
				.join('\n• ')
			showToast(`Por favor completa los siguientes campos:\n• ${camposVacios}`, 'error')
			loading.value = false
			return
		}

		const formData = new FormData()

		Object.keys(form.value).forEach((key) => {
			if (key === 'pro_imagen') return
			if (form.value[key] !== null && form.value[key] !== '') {
				formData.append(key, form.value[key])
			}
		})

		if (form.value.pro_imagen instanceof File) {
			formData.append('pro_imagen', form.value.pro_imagen)
		}

		formData.append('_method', 'PUT')
		const response = await api.post(`/productos/${selectedProducto.value.pro_id}`, formData, {
			headers: { 'Content-Type': 'multipart/form-data' },
		})

		if (response.data.success) {
			closeModal()
			fetchProductos()
			showToast(response.data.message, 'success')
		}
	} catch (err) {
		if (err.response?.status === 422) {
			formErrors.value = err.response.data.errors || {}
		}
		error.value = err.response?.data?.message || 'Error al guardar producto'
		showToast(error.value, 'error')
	} finally {
		loading.value = false
	}
}

const confirmDelete = (producto) => {
	productoToDelete.value = producto
	showDeleteConfirm.value = true
}

const handleDeleteSelected = () => {
	if (selectedProductos.value.length === 0) return

	if (selectedProductos.value.length === 1) {
		const productoId = selectedProductos.value[0]
		const producto = productos.value.find((p) => p.pro_id === productoId)
		if (producto) {
			productoToDelete.value = producto
			showDeleteConfirm.value = true
		}
	} else {
		showBulkDeleteConfirm.value = true
	}
}

const deleteProducto = async () => {
	if (!productoToDelete.value) return

	loading.value = true
	try {
		const response = await api.delete(`/productos/${productoToDelete.value.pro_id}`)

		if (response.data.success) {
			fetchProductos()
			showToast(response.data.message, 'success')
			selectedProductos.value = []
		}
	} catch (err) {
		error.value = err.response?.data?.message || 'Error al eliminar producto'
		showToast(error.value, 'error')
	} finally {
		loading.value = false
		showDeleteConfirm.value = false
		productoToDelete.value = null
	}
}

const bulkDelete = async () => {
	if (selectedProductos.value.length === 0) return

	loading.value = true
	try {
		const response = await api.delete('/productos/bulk-delete', {
			data: { ids: selectedProductos.value },
		})

		if (response.data.success) {
			selectedProductos.value = []
			fetchProductos()
			showToast(response.data.message, 'success')
		}
	} catch (err) {
		showToast(err.response?.data?.message || 'Error al eliminar productos', 'error')
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

		const response = await api.get('/productos/export', {
			params,
			responseType: 'blob',
		})

		const blob = new Blob([response.data], {
			type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		})
		const url = window.URL.createObjectURL(blob)
		const link = document.createElement('a')
		link.href = url
		link.download = `productos_${new Date().toISOString().split('T')[0]}.xlsx`
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

		const response = await api.get('/productos/export', {
			params,
			responseType: 'blob',
		})

		const blob = new Blob([response.data], { type: 'application/pdf' })
		const url = window.URL.createObjectURL(blob)
		const link = document.createElement('a')
		link.href = url
		link.download = `productos_${new Date().toISOString().split('T')[0]}.pdf`
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
		const headers = ['ID', 'Nombre', 'Estado', 'Creado por', 'Fecha Creación']
		const rows = productos.value.map((p) => [
			p.pro_id,
			p.pro_nombre,
			p.pro_estatus,
			p.user?.usu_nombre || '',
			p.created_at ? new Date(p.created_at).toLocaleDateString('es-MX') : '',
		])

		const csvContent = [
			headers.join(','),
			...rows.map((row) => row.map((cell) => `"${cell}"`).join(',')),
		].join('\n')

		const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
		const url = window.URL.createObjectURL(blob)
		const link = document.createElement('a')
		link.href = url
		link.download = `productos_${new Date().toISOString().split('T')[0]}.csv`
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

	const updatedProductos = [...productos.value]
	const [draggedProducto] = updatedProductos.splice(draggedIndex.value, 1)
	updatedProductos.splice(dropIndex, 0, draggedProducto)

	productos.value = updatedProductos

	const reorderedData = updatedProductos.map((producto, index) => ({
		id: producto.pro_id,
		orden: index + 1,
	}))

	try {
		const response = await api.put('/productos/update-order', {
			productos: reorderedData,
		})

		if (response.data.success) {
			updatedProductos.forEach((producto, index) => {
				producto.pro_orden = index + 1
			})
			showToast('Orden actualizado exitosamente', 'success')
		}
	} catch (err) {
		showToast('Error al actualizar el orden', 'error')
		fetchProductos()
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
	fetchProductos()
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
	fetchProductos()
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
				<h3 class="text-xl font-semibold text-[#1c2321]">Administración de Productos</h3>
			</div>

			<!-- Barra de controles -->
			<div class="px-6 py-4 border-b border-gray-150 space-y-4">
				<div class="flex items-center justify-between gap-4">
					<!-- Botón Crear -->
					<button
						@click="router.push('/products/create')"
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
						Crear Producto
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
								:disabled="loading || productos.length === 0"
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
									:disabled="loading || productos.length === 0"
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
									:disabled="loading || productos.length === 0"
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
								placeholder="Ej: Producto..."
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#312AFF] focus:border-transparent outline-none placeholder-gray-500"
								:class="{ 'font-semibold text-gray-900': filters.search }"
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

						<!-- Botones de acción -->
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
						v-if="hasSelectedProductos"
						class="flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-lg"
					>
						<span class="text-sm font-semibold text-blue-900">
							{{ selectedProductos.length }} producto(s) seleccionado(s)
						</span>
						<div class="flex gap-2">
							<button
								@click="selectedProductos = []"
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
					<h4 class="text-lg font-bold tracking-tight">Productos</h4>
					<span class="text-sm font-medium">
						Mostrando {{ displayRange }} de {{ pagination.total }} elementos
					</span>
				</div>

				<div class="overflow-x-auto border border-t-0 border-gray-300 rounded-b-xl">
					<table class="w-full border-collapse table-fixed">
						<colgroup>
							<col style="width: 40px" />
							<!-- Checkbox -->
							<col style="width: 60px" />
							<!-- Orden -->
							<col style="width: 120px" />
							<!-- Nombre -->
							<col style="width: 90px" />
							<!-- Vista previa -->
							<col style="width: 110px" />
							<!-- Estado -->
							<col style="width: 120px" />
							<!-- Creado por -->
							<col style="width: 100px" />
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
									@click="setSorting('pro_orden')"
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
													filters.sort_by === 'pro_orden' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'pro_orden' &&
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
									@click="setSorting('pro_nombre')"
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
													filters.sort_by === 'pro_nombre' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'pro_nombre' &&
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
									Imagen
								</th>
								<!-- Estado -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
									@click="setSorting('pro_estatus')"
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
													filters.sort_by === 'pro_estatus' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'pro_estatus' &&
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
									Creado por
								</th>
								<!-- Fecha -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
									@click="setSorting('created_at')"
								>
									<div class="flex items-center justify-center gap-1">
										<span>Creación</span>
										<svg
											class="w-3 h-3"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												v-if="
													filters.sort_by === 'created_at' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'created_at' &&
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
							<tr v-else-if="error && productos.length === 0">
								<td colspan="10" class="py-20 text-center text-red-600">
									{{ error }}
								</td>
							</tr>
							<tr v-else-if="productos.length === 0">
								<td colspan="10" class="py-20 text-center text-gray-400 italic">
									No se encontraron productos con los criterios seleccionados
								</td>
							</tr>
							<tr
								v-else
								v-for="(producto, index) in productos"
								:key="producto.pro_id"
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
										v-model="selectedProductos"
										:value="producto.pro_id"
										type="checkbox"
										class="w-4 h-4 text-[#312AFF] rounded border-gray-300"
										@click.stop
									/>
								</td>
								<td
									class="px-2 py-3 text-center text-xs font-semibold text-gray-900"
								>
									{{ producto.pro_orden ?? '—' }}
								</td>
								<td
									class="px-2 py-3 text-center text-xs font-semibold text-gray-900"
								>
									<div class="truncate" :title="producto.pro_nombre">
										{{ producto.pro_nombre }}
									</div>
								</td>
								<td class="px-2 py-3 text-center">
									<div class="flex justify-center items-center">
										<div
											v-if="producto.pro_imagen"
											class="w-16 h-16 rounded-lg overflow-hidden shadow-md border border-gray-200 bg-gray-50"
										>
											<img
												:src="getImageUrl(producto.pro_imagen)"
												:alt="producto.pro_nombre"
												class="w-full h-full object-cover hover:scale-110 transition-transform duration-200 cursor-pointer"
												@click="openModal('view', producto)"
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
										:class="getStatusColor(producto.pro_estatus)"
										class="inline-block px-2 py-0.5 text-[11px] font-bold uppercase rounded-full border whitespace-nowrap"
									>
										{{ producto.pro_estatus }}
									</span>
								</td>
								<td
									class="px-2 py-3 text-center text-[12px] text-gray-600 truncate"
								>
									{{ producto.user?.usu_nombre ?? '—' }}
								</td>
								<td
									class="px-2 py-3 text-center text-[12px] text-gray-600 whitespace-nowrap"
								>
									{{ formatDate(producto.created_at) }}
								</td>
								<td class="px-2 py-3 text-center">
									<button
										@click="openModal('view', producto)"
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
										@click="openModal('edit', producto)"
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
										@click="confirmDelete(producto)"
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

		<!-- ==================== MODAL EDITAR / VER ==================== -->
		<div
			v-if="showModal"
			class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fadeIn"
			@click.self="closeModal"
		>
			<!-- ===== MODO EDICIÓN ===== -->
			<div
				v-if="modalMode === 'edit'"
				class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden animate-slideUp max-h-[90vh] flex flex-col"
			>
				<!-- HEADER -->
				<div
					class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex justify-between items-center flex-shrink-0"
				>
					<div>
						<h3 class="text-xl font-bold text-white">Editar Producto</h3>
						<p class="text-indigo-100 text-xs mt-0.5">
							Actualiza la información del producto
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
							Imagen
						</button>
					</div>
				</div>

				<!-- CONTENIDO -->
				<div class="p-8 overflow-y-auto flex-1">
					<form @submit.prevent="saveProducto" class="space-y-6">
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
												d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
											/>
										</svg>
										Nombre del Producto *
									</label>
									<input
										v-model="form.pro_nombre"
										type="text"
										placeholder="Ej: Producto X"
										maxlength="50"
										class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900 placeholder-gray-400"
										:class="{ 'border-red-500': formErrors.pro_nombre }"
									/>
									<div class="flex justify-between mt-1">
										<p
											v-if="formErrors.pro_nombre"
											class="text-red-500 text-sm"
										>
											{{ formErrors.pro_nombre[0] }}
										</p>
										<span class="text-xs text-gray-400 ml-auto"
											>{{ form.pro_nombre?.length || 0 }}/50</span
										>
									</div>
								</div>

								<!-- Estado -->
								<div>
									<label class="text-sm font-semibold text-gray-700 mb-2 block">
										Estado *
									</label>
									<select
										v-model="form.pro_estatus"
										class="w-full px-4 py-3 pr-10 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-200 outline-none text-gray-900 appearance-none bg-white"
										:class="{ 'border-red-500': formErrors.pro_estatus }"
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
										v-if="formErrors.pro_estatus"
										class="text-red-500 text-sm mt-1"
									>
										{{ formErrors.pro_estatus[0] }}
									</p>
								</div>
							</div>

							<!-- TAB 2: Imagen -->
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
										Imagen del Producto *
									</label>

									<!-- Preview de imagen actual -->
									<div v-if="imagePreview" class="mb-4 relative group">
										<div
											class="rounded-xl overflow-hidden border-2 border-indigo-200 shadow-md"
										>
											<img
												:src="imagePreview"
												alt="Preview"
												class="w-full h-48 object-cover"
											/>
										</div>
										<button
											type="button"
											@click="
												() => {
													imagePreview = null
													form.pro_imagen = null
												}
											"
											class="absolute top-2 right-2 p-1.5 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200 shadow-md hover:bg-red-600"
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
													d="M6 18L18 6M6 6l12 12"
												/>
											</svg>
										</button>
									</div>

									<!-- Área de carga -->
									<label
										class="relative flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-xl cursor-pointer transition-all duration-200"
										:class="
											imagePreview
												? 'border-gray-200 bg-gray-50 hover:border-indigo-300'
												: 'border-indigo-300 bg-indigo-50 hover:border-indigo-500 hover:bg-indigo-100'
										"
									>
										<input
											type="file"
											class="hidden"
											accept="image/jpeg,image/png,image/jpg,image/gif"
											@change="handleImageChange"
										/>
										<div
											class="flex flex-col items-center gap-3 text-center px-4"
										>
											<div
												class="p-3 rounded-full"
												:class="
													imagePreview ? 'bg-gray-100' : 'bg-indigo-100'
												"
											>
												<svg
													class="h-8 w-8"
													:class="
														imagePreview
															? 'text-gray-400'
															: 'text-indigo-500'
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
															? 'Cambiar imagen'
															: 'Arrastra tu imagen aquí'
													}}
												</p>
												<p class="text-xs text-gray-400 mt-1">
													o haz clic para seleccionar
												</p>
											</div>
											<p class="text-xs text-gray-400">
												PNG, JPG, GIF • Máx. 2MB
											</p>
										</div>
									</label>
									<p
										v-if="formErrors.pro_imagen"
										class="text-red-500 text-sm mt-2"
									>
										{{ formErrors.pro_imagen[0] }}
									</p>
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
						@click="saveProducto"
						:disabled="loading"
						class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
					>
						{{ loading ? 'Guardando...' : 'Guardar Cambios' }}
					</button>
				</div>
			</div>

			<!-- ===== MODO VER (solo lectura) ===== -->
			<div
				v-else-if="modalMode === 'view'"
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
								<p class="text-white/80 text-sm">Información del producto</p>
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
					<!-- Imagen del Producto -->
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
							<h4 class="font-semibold text-gray-900">Imagen del Producto</h4>
						</div>
						<div class="flex justify-center">
							<div
								class="bg-white rounded-xl overflow-hidden shadow-md w-full max-w-sm"
							>
								<img
									v-if="selectedProducto?.pro_imagen"
									:src="getImageUrl(selectedProducto.pro_imagen)"
									:alt="selectedProducto.pro_nombre"
									class="w-full h-auto object-cover"
								/>
								<div
									v-else
									class="w-full h-48 flex items-center justify-center bg-gray-100"
								>
									<svg
										class="w-16 h-16 text-gray-300"
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
											d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"
										/>
									</svg>
									Nombre
								</label>
								<div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200">
									<p class="text-gray-900 font-medium">
										{{ selectedProducto?.pro_nombre }}
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
											:class="getStatusColor(selectedProducto?.pro_estatus)"
											class="inline-block px-3 py-1 text-xs font-bold uppercase rounded-full border"
										>
											{{ selectedProducto?.pro_estatus }}
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
											selectedProducto?.pro_orden ?? '—'
										}}</span>
									</div>
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
									>#{{ selectedProducto?.pro_id }}</span
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
								<span class="font-medium text-gray-900">
									{{ selectedProducto?.user?.usu_nombre ?? '—' }}
								</span>
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
								<span class="font-medium text-gray-900">
									{{ formatDate(selectedProducto?.created_at) }}
								</span>
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
								<span class="font-medium text-gray-900">
									{{ formatDate(selectedProducto?.updated_at) }}
								</span>
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
				<!-- HEADER -->
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
					<h3 class="text-xl font-bold text-white">¿Eliminar Producto?</h3>
				</div>

				<!-- CONTENIDO -->
				<div class="p-8">
					<p class="text-gray-700 text-center mb-2 text-lg">
						Estás a punto de eliminar el producto:
					</p>
					<p class="text-center font-bold text-gray-900 text-xl mb-6 pb-2">
						"{{ productoToDelete?.pro_nombre }}"
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
									Esta acción no se puede deshacer. El producto será eliminado
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
							@click="deleteProducto"
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
				<!-- HEADER -->
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

				<!-- CONTENIDO -->
				<div class="p-8">
					<p class="text-gray-700 text-center mb-6 text-lg pb-2">
						Estás a punto de eliminar
						<span class="font-bold text-red-600"
							>{{ selectedProductos.length }} producto(s)</span
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
									Todos los productos seleccionados serán eliminados
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
