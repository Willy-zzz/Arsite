// Importa el logger (herramienta para registrar mensajes en consola o servidor)
import logger from '@/utils/logger'

// Lista de tipos MIME de imágenes aceptadas
export const IMAGE_MIME_TYPES = [
	'image/jpeg',
	'image/png',
	'image/jpg',
	'image/gif',
	'image/svg+xml',
	'image/webp',
]

// Cadena con los mismos tipos MIME, útil para el atributo 'accept' de un input file
export const IMAGE_ACCEPT = 'image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp'

/**
 * Limpia el valor de una referencia a un input de tipo archivo.
 * @param {Object} inputRef - Referencia del input (de Vue, React, etc.)
 */
export const clearFileInput = (inputRef) => {
	if (inputRef?.value) {
		inputRef.value.value = ''  // Asigna cadena vacía al valor del input
	}
}

/**
 * Establece un mensaje de error para un campo específico en un objeto de errores de formulario.
 * @param {Object} formErrorsRef - Referencia al objeto que contiene los errores del formulario
 * @param {string} field - Nombre del campo
 * @param {string} message - Mensaje de error
 */
export const setFieldError = (formErrorsRef, field, message) => {
	formErrorsRef.value[field] = [message]  // Asigna el error como un array (formato común en validaciones)
}

/**
 * Elimina el mensaje de error de un campo específico.
 * @param {Object} formErrorsRef - Referencia al objeto de errores del formulario
 * @param {string} field - Nombre del campo a limpiar
 */
export const clearFieldError = (formErrorsRef, field) => {
	if (formErrorsRef.value[field]) {
		delete formErrorsRef.value[field]  // Borra la propiedad si existe
	}
}

/**
 * Valida un archivo de imagen según tamaño, tipo y obligatoriedad.
 * @param {File} file - El archivo a validar (puede ser null/undefined)
 * @param {Object} options - Opciones de validación:
 *   - label: nombre amigable del campo (por defecto 'La imagen')
 *   - maxSizeMB: tamaño máximo en megabytes (por defecto 5)
 *   - allowedTypes: array de tipos MIME permitidos (por defecto IMAGE_MIME_TYPES)
 *   - allowedLabel: texto legible de formatos permitidos (por defecto 'JPG, PNG, GIF, SVG o WEBP')
 * @returns {Object} { valid: boolean, message: string|null }
 */
export const validateImageFile = (
	file,
	{
		label = 'La imagen',
		maxSizeMB = 5,
		allowedTypes = IMAGE_MIME_TYPES,
		allowedLabel = 'JPG, PNG, GIF, SVG o WEBP',
	} = {}
) => {
	// Validar que exista un archivo (campo obligatorio)
	if (!file) {
		return { valid: false, message: `${label} es obligatoria` }
	}

	// Validar que el tipo MIME esté en la lista permitida
	if (!allowedTypes.includes(file.type)) {
		return {
			valid: false,
			message: `${label} debe estar en formato ${allowedLabel}`,
		}
	}

	// Validar que el tamaño no supere el límite (conversión MB a bytes)
	if (file.size > maxSizeMB * 1024 * 1024) {
		return {
			valid: false,
			message: `${label} no puede superar los ${maxSizeMB} MB`,
		}
	}

	// Si pasa todas las validaciones
	return { valid: true, message: null }
}

/**
 * Crea una vista previa en DataURL del archivo de imagen usando FileReader.
 * @param {File} file - El archivo de imagen
 * @returns {Promise<string>} Promesa que resuelve con la URL de datos (base64)
 */
export const createFilePreview = (file) =>
	new Promise((resolve, reject) => {
		const reader = new FileReader()
		reader.onload = (event) => resolve(event.target?.result ?? null)  // Éxito: devuelve el resultado
		reader.onerror = () => reject(new Error('No se pudo leer el archivo seleccionado'))  // Error: lanza excepción
		reader.readAsDataURL(file)  // Inicia la lectura como DataURL
	})

/**
 * Verifica si un string es una URL válida con protocolo http o https.
 * @param {string} value - La cadena a validar
 * @returns {boolean} true si es una URL HTTP/HTTPS válida, false en caso contrario
 */
export const isValidUrl = (value) => {
	if (!value) return false

	try {
		const parsed = new URL(value)  // Intenta construir un objeto URL
		// Solo acepta protocolos http o https (no ftp, file, etc.)
		return parsed.protocol === 'http:' || parsed.protocol === 'https:'
	} catch {
		return false  // Si URL() lanza error, no es válida
	}
}

/**
 * Registra en el logger una advertencia cuando falla una validación visible del formulario.
 * @param {string} formName - Nombre del formulario (para identificar en logs)
 * @param {Object} errors - Objeto con los errores de validación
 */
export const logClientValidation = (formName, errors) => {
	logger.warn(`[${formName}] Validación visible rechazada`, errors)
}