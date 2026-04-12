<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// Estado
const contactos = ref([])
const loading = ref(false)
const error = ref(null)
const showDetailModal = ref(false)
const showDeleteConfirm = ref(false)
const showBulkDeleteConfirm = ref(false)
const showBulkStatusConfirm = ref(false)
const selectedContacto = ref(null)
const contactoToDelete = ref(null)
const selectedContactos = ref([])
const showAdvancedFilters = ref(false)
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
	estado: '',
	fecha_desde: '',
	fecha_hasta: '',
	sort_by: 'created_at',
	sort_direction: 'desc',
})

// Computed
const hasAdvancedFiltersActive = computed(() => {
	return filters.value.search || filters.value.fecha_desde || filters.value.fecha_hasta
})

const allSelected = computed({
	get: () =>
		contactos.value.length > 0 && selectedContactos.value.length === contactos.value.length,
	set: (value) => {
		if (value) {
			selectedContactos.value = contactos.value.map((c) => c.con_id)
		} else {
			selectedContactos.value = []
		}
	},
})

const hasSelectedContactos = computed(() => selectedContactos.value.length > 0)

const displayRange = computed(() => {
	if (contactos.value.length === 0) return '0-0'
	const start = (pagination.value.current_page - 1) * pagination.value.per_page + 1
	const end = Math.min(start + contactos.value.length - 1, pagination.value.total)
	return `${start}-${end}`
})

// Métodos de filtros
const setEstadoQuickFilter = (estado) => {
	filters.value.estado = estado
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
	fetchContactos()
}

const clearAdvancedFilters = () => {
	filters.value.search = ''
	filters.value.fecha_desde = ''
	filters.value.fecha_hasta = ''
	applyFilters()
}

// Métodos principales
const fetchContactos = async () => {
	loading.value = true
	error.value = null

	try {
		const params = {
			page: pagination.value.current_page,
			per_page: pagination.value.per_page,
			...filters.value,
		}

		const response = await api.get('/contactos', { params })

		if (response.data.success) {
			contactos.value = response.data.data.data
			pagination.value = {
				current_page: response.data.data.current_page,
				last_page: response.data.data.last_page,
				per_page: response.data.data.per_page,
				total: response.data.data.total,
			}
		}
	} catch (err) {
		error.value = err.response?.data?.message || 'Error al cargar contactos'
		console.error('Error fetching contactos:', err)
	} finally {
		loading.value = false
	}
}

// Abrir detalle y marcar como leído automáticamente
const openDetail = async (contacto) => {
	selectedContacto.value = contacto
	showDetailModal.value = true

	// Si está Nuevo, marcarlo como Leído al abrir
	if (contacto.con_estado === 'Nuevo') {
		try {
			await api.put(`/contactos/${contacto.con_id}`, { con_estado: 'Leido' })
			// Actualizar localmente
			const idx = contactos.value.findIndex((c) => c.con_id === contacto.con_id)
			if (idx !== -1) contactos.value[idx].con_estado = 'Leido'
			selectedContacto.value = { ...selectedContacto.value, con_estado: 'Leido' }
		} catch (err) {
			console.error('Error marcando como leído:', err)
		}
	}
}

const closeDetail = () => {
	showDetailModal.value = false
	selectedContacto.value = null
}

// Cambiar estado individual
const changeEstado = async (contacto, nuevoEstado) => {
	try {
		const response = await api.put(`/contactos/${contacto.con_id}`, {
			con_estado: nuevoEstado,
		})

		if (response.data.success) {
			const idx = contactos.value.findIndex((c) => c.con_id === contacto.con_id)
			if (idx !== -1) contactos.value[idx].con_estado = nuevoEstado
			if (selectedContacto.value?.con_id === contacto.con_id) {
				selectedContacto.value = { ...selectedContacto.value, con_estado: nuevoEstado }
			}
			showToast(`Estado actualizado a "${nuevoEstado}"`, 'success')
		}
	} catch (err) {
		showToast('Error al actualizar el estado', 'error')
	}
}

// Responder por webmail (Roundcube) — abre el compositor con datos precargados
// Y copia los datos al portapapeles simultáneamente como respaldo
// Marca automáticamente el mensaje como Respondido
const responder = async (contacto) => {
	const webmailUrl = import.meta.env.VITE_WEBMAIL_URL

	// Construir el cuerpo del mensaje
	const cuerpoPlano =
		`Para: ${contacto.con_email}\n` +
		`Asunto: Re: ${contacto.con_asunto}\n\n` +
		`Estimado/a ${contacto.con_nombre},\n\n` +
		`En respuesta a su mensaje recibido el ${formatDate(contacto.created_at)}, ` +
		`nos comunicamos con usted para informarle que:\n\n` +
		`[Escribe aquí tu respuesta]\n\n` +
		`Quedamos a sus órdenes para cualquier consulta adicional.\n\n` +
		`Atentamente,`

	// 1) Copiar datos al portapapeles (siempre, como respaldo)
	try {
		await navigator.clipboard.writeText(cuerpoPlano)
	} catch (err) {
		console.warn('No se pudo copiar al portapapeles:', err)
	}

	// 2) Abrir webmail o fallback a mailto:
	if (webmailUrl) {
		// Roundcube: parámetros de composición precargados
		const to = encodeURIComponent(contacto.con_email)
		const subject = encodeURIComponent(`Re: ${contacto.con_asunto}`)
		const body = encodeURIComponent(
			`Estimado/a ${contacto.con_nombre},\n\n` +
				`En respuesta a su mensaje recibido el ${formatDate(contacto.created_at)}, ` +
				`nos comunicamos con usted para informarle que:\n\n` +
				`[Escribe aquí tu respuesta]\n\n` +
				`Quedamos a sus órdenes para cualquier consulta adicional.\n\n` +
				`Atentamente,`,
		)
		const url = `${webmailUrl}/?_task=mail&_action=compose&_to=${to}&_subject=${subject}&_body=${body}`
		window.open(url, '_blank')
	} else {
		// Fallback a mailto: si no está configurado el webmail
		const asunto = encodeURIComponent(`Re: ${contacto.con_asunto}`)
		const cuerpo = encodeURIComponent(
			`Estimado/a ${contacto.con_nombre},\n\n` +
				`En respuesta a su mensaje recibido el ${formatDate(contacto.created_at)}, ` +
				`nos comunicamos con usted para informarle que:\n\n` +
				`[Escribe aquí tu respuesta]\n\n` +
				`Quedamos a sus órdenes para cualquier consulta adicional.\n\n` +
				`Atentamente,`,
		)
		window.open(`mailto:${contacto.con_email}?subject=${asunto}&body=${cuerpo}`, '_blank')
	}

	// 3) Toast informativo explicando ambas acciones
	toast.value = {
		show: true,
		message: '📋 Datos copiados al portapapeles. Si Roundcube pide login, pégalos manualmente.',
		type: 'info',
	}
	setTimeout(() => {
		toast.value.show = false
	}, 5000)

	// 4) Marcar automáticamente como Respondido si no lo estaba ya
	if (contacto.con_estado !== 'Respondido') {
		await changeEstado(contacto, 'Respondido')
	}
}

// Cambio masivo de estado
const openBulkStatus = (estado) => {
	bulkStatusTarget.value = estado
	showBulkStatusConfirm.value = true
}

const bulkUpdateStatus = async () => {
	if (selectedContactos.value.length === 0) return

	loading.value = true
	try {
		const response = await api.put('/contactos/bulk-status', {
			ids: selectedContactos.value,
			estado: bulkStatusTarget.value,
		})

		if (response.data.success) {
			selectedContactos.value = []
			fetchContactos()
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

// Eliminar individual
const confirmDelete = (contacto) => {
	contactoToDelete.value = contacto
	showDeleteConfirm.value = true
}

const handleDeleteSelected = () => {
	if (selectedContactos.value.length === 0) return

	if (selectedContactos.value.length === 1) {
		const id = selectedContactos.value[0]
		const contacto = contactos.value.find((c) => c.con_id === id)
		if (contacto) {
			contactoToDelete.value = contacto
			showDeleteConfirm.value = true
		}
	} else {
		showBulkDeleteConfirm.value = true
	}
}

const deleteContacto = async () => {
	if (!contactoToDelete.value) return

	loading.value = true
	try {
		const response = await api.delete(`/contactos/${contactoToDelete.value.con_id}`)

		if (response.data.success) {
			if (showDetailModal.value) closeDetail()
			fetchContactos()
			showToast(response.data.message, 'success')
			selectedContactos.value = []
		}
	} catch (err) {
		showToast(err.response?.data?.message || 'Error al eliminar contacto', 'error')
	} finally {
		loading.value = false
		showDeleteConfirm.value = false
		contactoToDelete.value = null
	}
}

// Eliminar masivo
const bulkDelete = async () => {
	if (selectedContactos.value.length === 0) return

	loading.value = true
	try {
		const response = await api.delete('/contactos/bulk-delete', {
			data: { ids: selectedContactos.value },
		})

		if (response.data.success) {
			selectedContactos.value = []
			fetchContactos()
			showToast(response.data.message, 'success')
		}
	} catch (err) {
		showToast(err.response?.data?.message || 'Error al eliminar contactos', 'error')
	} finally {
		loading.value = false
		showBulkDeleteConfirm.value = false
	}
}

// Exportar a CSV
const exportToCSV = async () => {
	try {
		loading.value = true
		const params = { ...filters.value }
		const response = await api.get('/contactos/export', { params })

		if (response.data.success) {
			const rows = response.data.data
			const csvContent = rows
				.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
				.join('\n')

			const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
			const url = window.URL.createObjectURL(blob)
			const link = document.createElement('a')
			link.href = url
			link.download = `contactos_${new Date().toISOString().split('T')[0]}.csv`
			document.body.appendChild(link)
			link.click()
			document.body.removeChild(link)
			window.URL.revokeObjectURL(url)

			showToast('Datos exportados a CSV exitosamente', 'success')
		}
	} catch (err) {
		showToast('Error al exportar datos', 'error')
	} finally {
		loading.value = false
	}
}

const changePage = (page) => {
	pagination.value.current_page = page
	fetchContactos()
}

// Helpers de estilo
const getEstadoColor = (estado) => {
	const colors = {
		Nuevo: 'bg-blue-100 text-blue-800 border-blue-200',
		Leido: 'bg-gray-100 text-gray-700 border-gray-300',
		Respondido: 'bg-green-100 text-green-800 border-green-200',
		Archivado: 'bg-purple-100 text-purple-800 border-purple-200',
	}
	return colors[estado] ?? 'bg-gray-100 text-gray-700 border-gray-300'
}

const isNuevo = (contacto) => contacto.con_estado === 'Nuevo'

const formatDate = (date) => {
	if (!date) return 'N/A'
	return new Date(date).toLocaleDateString('es-MX', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
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
	})
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
	fetchContactos()
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
						: toast.type === 'info'
							? 'bg-blue-50 border-l-4 border-blue-500'
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
							v-else-if="toast.type === 'info'"
							class="h-6 w-6 text-blue-500"
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
							:class="
								toast.type === 'success'
									? 'text-green-800'
									: toast.type === 'info'
										? 'text-blue-800'
										: 'text-red-800'
							"
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
				<h3 class="text-xl font-semibold text-[#1c2321]">
					Bandeja de Mensajes de Contacto
				</h3>
			</div>

			<!-- Barra de controles -->
			<div class="px-6 py-4 border-b border-gray-150 space-y-4">
				<div class="flex items-center justify-between gap-4 flex-wrap">
					<!-- Filtros rápidos de estado -->
					<div
						class="flex bg-gray-100 p-1 rounded-lg border border-gray-200 flex-wrap gap-1"
					>
						<button
							v-for="opt in [
								{ l: 'Todos', v: '' },
								{ l: 'Nuevos', v: 'Nuevo' },
								{ l: 'Leídos', v: 'Leido' },
								{ l: 'Respondidos', v: 'Respondido' },
								{ l: 'Archivados', v: 'Archivado' },
							]"
							:key="opt.v"
							@click="setEstadoQuickFilter(opt.v)"
							:class="[
								filters.estado === opt.v
									? 'bg-white shadow-sm text-[#312AFF] font-semibold'
									: 'text-gray-600 hover:text-gray-900',
							]"
							class="px-3 py-1.5 text-sm rounded-md transition-all"
						>
							{{ opt.l }}
						</button>
					</div>

					<!-- Acciones derecha -->
					<div class="flex flex-wrap items-center gap-2">
						<!-- Botón exportar CSV -->
						<button
							@click="exportToCSV"
							:disabled="loading || contactos.length === 0"
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
						<div class="space-y-1 md:col-span-2">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Buscar por nombre, email, empresa o asunto</label
							>
							<input
								v-model="filters.search"
								type="text"
								placeholder="Ej: empresa@correo.com..."
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#312AFF] focus:border-transparent outline-none placeholder-gray-500"
								:class="{ 'font-semibold text-gray-900': filters.search }"
							/>
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
						v-if="hasSelectedContactos"
						class="flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-lg flex-wrap gap-3"
					>
						<span class="text-sm font-semibold text-blue-900">
							{{ selectedContactos.length }} mensaje(s) seleccionado(s)
						</span>
						<div class="flex gap-2 flex-wrap">
							<button
								@click="selectedContactos = []"
								class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
							>
								Cancelar
							</button>
							<!-- Cambio masivo de estado -->
							<button
								@click="openBulkStatus('Leido')"
								class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-1.5"
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
								Marcar Leído
							</button>
							<button
								@click="openBulkStatus('Respondido')"
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
								Respondido
							</button>
							<button
								@click="openBulkStatus('Archivado')"
								class="px-3 py-1.5 text-sm font-medium text-purple-700 bg-purple-50 border border-purple-300 rounded-lg hover:bg-purple-100 transition-colors flex items-center gap-1.5"
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
								Archivar
							</button>
							<button
								@click="handleDeleteSelected"
								class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
							>
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
					<h4 class="text-lg font-bold tracking-tight">Mensajes</h4>
					<span class="text-sm font-medium">
						Mostrando {{ displayRange }} de {{ pagination.total }} elementos
					</span>
				</div>

				<div class="overflow-x-auto border border-t-0 border-gray-300 rounded-b-xl">
					<table class="w-full border-collapse table-fixed">
						<colgroup>
							<col style="width: 30px" />
							<!-- Checkbox -->
							<col style="width: 105px" />
							<!-- Estado -->
							<col style="width: 140px" />
							<!-- Nombre -->
							<col style="width: 140px" />
							<!-- Email -->
							<col style="width: 110px" />
							<!-- Empresa -->
							<col style="width: 150px" />
							<!-- Asunto -->
							<col style="width: 80px" />
							<!-- Fecha -->
							<col style="width: 40px" />
							<!-- Ver -->
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
								<!-- Estado -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
									@click="setSorting('con_estado')"
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
													filters.sort_by === 'con_estado' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'con_estado' &&
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
									@click="setSorting('con_nombre')"
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
													filters.sort_by === 'con_nombre' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'con_nombre' &&
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
									Email
								</th>
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider"
								>
									Empresa
								</th>
								<!-- Asunto -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
									@click="setSorting('con_asunto')"
								>
									<div class="flex items-center justify-center gap-1">
										<span>Asunto</span>
										<svg
											class="w-3 h-3"
											fill="none"
											stroke="currentColor"
											viewBox="0 0 24 24"
										>
											<path
												v-if="
													filters.sort_by === 'con_asunto' &&
													filters.sort_direction === 'asc'
												"
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 15l7-7 7 7"
											/>
											<path
												v-else-if="
													filters.sort_by === 'con_asunto' &&
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
								<!-- Fecha -->
								<th
									class="px-2 py-3 text-center text-[12px] font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
									@click="setSorting('created_at')"
								>
									<div class="flex items-center justify-center gap-1">
										<span>Fecha</span>
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
									Eliminar
								</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-gray-200 bg-white">
							<tr v-if="loading">
								<td colspan="9" class="py-20 text-center">
									<div class="flex justify-center items-center">
										<div
											class="animate-spin rounded-full h-10 w-10 border-4 border-[#312AFF]/20 border-t-[#312AFF]"
										></div>
									</div>
								</td>
							</tr>
							<tr v-else-if="error && contactos.length === 0">
								<td colspan="9" class="py-20 text-center text-red-600">
									{{ error }}
								</td>
							</tr>
							<tr v-else-if="contactos.length === 0">
								<td colspan="9" class="py-20 text-center text-gray-400 italic">
									No se encontraron mensajes con los criterios seleccionados
								</td>
							</tr>
							<tr
								v-else
								v-for="contacto in contactos"
								:key="contacto.con_id"
								:class="[
									'transition-all duration-200 hover:bg-blue-50/30 cursor-pointer',
									isNuevo(contacto) ? 'bg-blue-50/20 font-semibold' : '',
								]"
								@click="openDetail(contacto)"
							>
								<td class="px-2 py-3 text-center" @click.stop>
									<input
										v-model="selectedContactos"
										:value="contacto.con_id"
										type="checkbox"
										class="w-4 h-4 text-[#312AFF] rounded border-gray-300"
									/>
								</td>
								<td class="px-2 py-3 text-center">
									<span
										:class="getEstadoColor(contacto.con_estado)"
										class="inline-block px-2 py-0.5 text-[11px] font-bold uppercase rounded-full border whitespace-nowrap"
									>
										{{ contacto.con_estado }}
									</span>
								</td>
								<td class="px-2 py-3 text-xs text-gray-900">
									<div class="flex items-center gap-1.5 truncate">
										<!-- Punto azul si es Nuevo -->
										<span
											v-if="isNuevo(contacto)"
											class="flex-shrink-0 w-2 h-2 rounded-full bg-blue-500"
										></span>
										<span class="truncate" :title="contacto.con_nombre">{{
											contacto.con_nombre
										}}</span>
									</div>
								</td>
								<td
									class="px-2 py-3 text-[12px] text-gray-600 truncate"
									:title="contacto.con_email"
								>
									{{ contacto.con_email }}
								</td>
								<td
									class="px-2 py-3 text-[12px] text-gray-600 truncate"
									:title="contacto.con_empresa"
								>
									{{ contacto.con_empresa || '—' }}
								</td>
								<td
									class="px-2 py-3 text-[12px] text-gray-800 truncate"
									:title="contacto.con_asunto"
								>
									{{ contacto.con_asunto }}
								</td>
								<td
									class="px-2 py-3 text-center text-[12px] text-gray-500 whitespace-nowrap"
								>
									{{ formatDate(contacto.created_at) }}
								</td>
								<td class="px-2 py-3 text-center" @click.stop>
									<button
										@click="openDetail(contacto)"
										class="p-1 text-blue-600 hover:bg-blue-50 rounded-lg transition-all inline-flex"
										title="Ver mensaje"
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
												d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
											/>
										</svg>
									</button>
								</td>
								<td class="px-2 py-3 text-center" @click.stop>
									<button
										@click="confirmDelete(contacto)"
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

		<!-- ==================== MODAL DETALLE DEL MENSAJE ==================== -->
		<div
			v-if="showDetailModal && selectedContacto"
			class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fadeIn"
			@click.self="closeDetail"
		>
			<div
				class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-scaleIn"
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
										d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
									/>
								</svg>
							</div>
							<div>
								<h3 class="text-xl font-bold text-white">Detalle del Mensaje</h3>
								<p class="text-white/80 text-sm">
									{{ selectedContacto.con_asunto }}
								</p>
							</div>
						</div>
						<button
							@click="closeDetail"
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
				<div class="flex-1 overflow-y-auto p-8 space-y-6">
					<!-- Estado actual + Cambiar estado -->
					<div
						class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-200"
					>
						<div class="flex items-center gap-3">
							<span class="text-sm font-medium text-gray-600">Estado actual:</span>
							<span
								:class="getEstadoColor(selectedContacto.con_estado)"
								class="inline-block px-3 py-1 text-xs font-bold uppercase rounded-full border"
							>
								{{ selectedContacto.con_estado }}
							</span>
						</div>
						<div class="flex gap-2 flex-wrap">
							<button
								v-for="estado in ['Leido', 'Archivado']"
								:key="estado"
								@click="changeEstado(selectedContacto, estado)"
								:disabled="selectedContacto.con_estado === estado"
								class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5"
								:class="{
									'bg-gray-100 border-gray-300 text-gray-700 hover:bg-gray-200':
										estado === 'Leido',
									'bg-purple-50 border-purple-300 text-purple-700 hover:bg-purple-100':
										estado === 'Archivado',
								}"
							>
								<!-- Leido -->
								<svg
									v-if="estado === 'Leido'"
									class="w-3.5 h-3.5"
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
								<!-- Archivado -->
								<svg
									v-else-if="estado === 'Archivado'"
									class="w-3.5 h-3.5"
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
								{{ estado }}
							</button>
						</div>
					</div>

					<!-- Datos del remitente -->
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
										d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
									/>
								</svg>
								<h4 class="font-semibold text-gray-900">Datos del Remitente</h4>
							</div>
						</div>
						<div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
							<!-- Nombre -->
							<div>
								<label
									class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2"
								>
									<svg
										class="h-3.5 w-3.5 text-indigo-400"
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
									Nombre
								</label>
								<div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200">
									<p class="text-gray-900 font-medium text-sm">
										{{ selectedContacto.con_nombre }}
									</p>
								</div>
							</div>
							<!-- Email -->
							<div>
								<label
									class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2"
								>
									<svg
										class="h-3.5 w-3.5 text-indigo-400"
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
									Email
								</label>
								<div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200">
									<a
										:href="`mailto:${selectedContacto.con_email}`"
										class="text-indigo-600 font-medium text-sm hover:underline"
									>
										{{ selectedContacto.con_email }}
									</a>
								</div>
							</div>
							<!-- Teléfono -->
							<div>
								<label
									class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2"
								>
									<svg
										class="h-3.5 w-3.5 text-indigo-400"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
										/>
									</svg>
									Teléfono
								</label>
								<div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200">
									<a
										:href="`tel:${selectedContacto.con_telefono}`"
										class="text-indigo-600 font-medium text-sm hover:underline"
									>
										{{ selectedContacto.con_telefono }}
									</a>
								</div>
							</div>
							<!-- Empresa -->
							<div>
								<label
									class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2"
								>
									<svg
										class="h-3.5 w-3.5 text-indigo-400"
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
									Empresa
								</label>
								<div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-200">
									<p class="text-gray-900 font-medium text-sm">
										{{ selectedContacto.con_empresa || '—' }}
									</p>
								</div>
							</div>
						</div>
					</div>

					<!-- Mensaje -->
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
										d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
									/>
								</svg>
								<h4 class="font-semibold text-gray-900">Mensaje</h4>
							</div>
						</div>
						<div class="p-6">
							<p
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3"
							>
								Asunto: {{ selectedContacto.con_asunto }}
							</p>
							<div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
								<p
									class="text-gray-800 text-sm leading-relaxed whitespace-pre-wrap"
								>
									{{ selectedContacto.con_mensaje }}
								</p>
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
									>#{{ selectedContacto.con_id }}</span
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
										d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
									/>
								</svg>
								<span class="text-gray-600">Recibido:</span>
								<span class="font-medium text-gray-900">{{
									formatDateTime(selectedContacto.created_at)
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
										d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
									/>
								</svg>
								<span class="text-gray-600">IP:</span>
								<span class="font-mono text-gray-700 text-xs">{{
									selectedContacto.con_ip || '—'
								}}</span>
							</div>
						</div>
					</div>
				</div>

				<!-- FOOTER -->
				<div
					class="border-t border-gray-200 bg-gray-50 px-6 py-5 flex-shrink-0 flex justify-between items-center"
				>
					<!-- Botón eliminar -->
					<button
						@click="
							() => {
								confirmDelete(selectedContacto)
								closeDetail()
							}
						"
						class="px-5 py-2.5 bg-red-50 border border-red-300 text-red-600 font-semibold rounded-xl hover:bg-red-100 transition-all duration-200 flex items-center gap-2"
					>
						<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
							/>
						</svg>
						Eliminar
					</button>
					<!-- Botones derecha: Responder + Cerrar -->
					<div class="flex gap-3">
						<!-- Botón responder -->
						<button
							@click="responder(selectedContacto)"
							class="px-6 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center gap-2"
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
									d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
								/>
							</svg>
							Responder
						</button>
						<!-- Botón cerrar -->
						<button
							@click="closeDetail"
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
					<h3 class="text-xl font-bold text-white">¿Eliminar Mensaje?</h3>
				</div>
				<div class="p-8">
					<p class="text-gray-700 text-center mb-2 text-lg">
						Estás a punto de eliminar el mensaje de:
					</p>
					<p class="text-center font-bold text-gray-900 text-xl mb-6 pb-2">
						"{{ contactoToDelete?.con_nombre }}"
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
									Esta acción no se puede deshacer. El mensaje será eliminado
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
							@click="deleteContacto"
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
							>{{ selectedContactos.length }} mensaje(s)</span
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
									Todos los mensajes seleccionados serán eliminados
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
						<!-- Leido -->
						<svg
							v-if="bulkStatusTarget === 'Leido'"
							class="w-8 h-8 text-gray-600"
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
						<!-- Respondido -->
						<svg
							v-else-if="bulkStatusTarget === 'Respondido'"
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
						<!-- Archivado -->
						<svg
							v-else-if="bulkStatusTarget === 'Archivado'"
							class="w-8 h-8 text-purple-600"
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
							>{{ selectedContactos.length }} mensaje(s)</span
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
input::placeholder {
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
