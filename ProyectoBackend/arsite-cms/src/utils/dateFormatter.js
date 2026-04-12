// src/utils/dateFormatter.js

export const formatDate = (dateString) => {
	if (!dateString) return 'N/A'

	// SOLUCIÓN DEFINITIVA: Si es YYYY-MM-DD, procesar como TEXTO
	if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
		const [year, month, day] = dateString.split('-')
		const months = [
			'ene',
			'feb',
			'mar',
			'abr',
			'may',
			'jun',
			'jul',
			'ago',
			'sep',
			'oct',
			'nov',
			'dic',
		]
		// Retornamos la cadena construida manualmente sin pasar por new Date()
		return `${day} ${months[parseInt(month) - 1]} ${year}`
	}

	// Para otros formatos (timestamps con hora), mantenemos la lógica local
	const date = new Date(dateString)
	if (isNaN(date.getTime())) return 'Fecha inválida'

	return date.toLocaleDateString('es-MX', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
	})
}

export const formatDateTime = (dateString) => {
	if (!dateString) return 'N/A'
	const date = new Date(dateString)
	if (isNaN(date.getTime())) return 'Fecha inválida'
	return date.toLocaleString('es-MX', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
	})
}

export const formatDateForInput = (dateString) => {
	if (!dateString) return ''
	if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return dateString
	if (typeof dateString === 'string' && dateString.includes('T')) {
		return dateString.split('T')[0]
	}
	const date = new Date(dateString)
	if (isNaN(date.getTime())) return ''
	const year = date.getFullYear()
	const month = String(date.getMonth() + 1).padStart(2, '0')
	const day = String(date.getDate()).padStart(2, '0')
	return `${year}-${month}-${day}`
}
