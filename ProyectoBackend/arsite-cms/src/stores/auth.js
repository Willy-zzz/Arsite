// src/stores/auth.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
	// State
	const token = ref(sessionStorage.getItem('auth-token') || null)
	const user = ref(JSON.parse(sessionStorage.getItem('auth-user')) || null)
	const loading = ref(false)
	const error = ref(null)

	// Getters
	const isAuthenticated = computed(() => !!token.value && !!user.value)
	const isAdmin = computed(() => user.value?.rol === 'Administrador')
	const isEditor = computed(() => user.value?.rol === 'Editor')
	const userName = computed(() => user.value?.usu_nombre || '')
	const userInitials = computed(() => user.value?.iniciales || '')
	const userEmail = computed(() => user.value?.email || '')
	const userRole = computed(() => user.value?.rol || '')
	const userStatus = computed(() => user.value?.estado || '')

	//Helpers

	const getActiveStorage = () => {
		return localStorage.getItem('remember-me') === 'true' ? localStorage : sessionStorage
	}

	/**
	 * Limpia TODOS los datos de autenticación de AMBOS storages
	 */
	const clearAllStorage = () => {
		//Limpiar tokens
		localStorage.removeItem('auth-token')
		sessionStorage.removeItem('auth-token')

		//Limpiar usuarios
		localStorage.removeItem('auth-user')
		sessionStorage.removeItem('auth-user')

		//Limpiar preferencia
		localStorage.removeItem('remember-me')
	}

	/**
	 * Guarda datos en el storage correspondiente, persiste y sincroniza el estado reactivo del store.
	 * @param {string} newToken - Token de autenticación
	 * @param {object} newUser - Datos del usuario
	 * @param {boolean} remember - Si debe persistir la sesión
	 */
	const saveToStorage = (newToken, newUser, remember) => {
		//Limpieza preventiva
		//clearAllStorage()

		//Guardar preferencia
		//localStorage.setItem('remember-me', String(remember))

		//Guardar datos en el storage correcto
		const storage = remember ? localStorage : sessionStorage
		storage.setItem('auth-token', newToken)
		storage.setItem('auth-user', JSON.stringify(newUser))

		//Sincronización reactiva inmediata
		token.value = newToken
		user.value = newUser

		api.defaults.headers.common['Authorization'] = `Bearer ${newToken}`
	}

	// Actions
	/**
	 * Inicializa el estado de autenticación desde el storage
	 * Valida el token con el backend
	 */
	const initAuth = async () => {
		try {
			const storage = getActiveStorage()
			const storedToken = storage.getItem('auth-token')
			const storedUser = storage.getItem('auth-user')

			if (storedToken && storedUser) {
				//Restaurar estado
				token.value = storedToken
				user.value = JSON.parse(storedUser)
				//api.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`
				await fetchUser()
			}
		} catch (err) {
			//Si la validación falla, limpiar todo
			console.error('Token inválido o expirado:', err)
			clearAuth()
		}
	}

	/**
	 * Inicia sesión del usuario
	 * @param {object} credentials - { email, password, remember }
	 */
	const login = async (credentials) => {
		loading.value = true
		error.value = null

		try {
			const response = await api.post('/login', credentials)

			if (response.data.success) {
				//Extraer datos
				const { token: newToken, user: newUser } = response.data.data

				const remember = !!credentials.remember

				//Actualizar estado reactivo
				token.value = newToken
				user.value = newUser

				// Guardar en Storage
				saveToStorage(newToken, newUser, remember)

				console.log('Login exitoso:', {
					email: newUser.email,
					rol: newUser.rol,
					remember,
					storage: remember ? 'localStorage' : 'sessionStorage',
				})

				// Configurar token en axios
				//api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`

				return { success: true }
			} else {
				throw new Error(response.data.message || 'Error al iniciar sesión')
			}
		} catch (err) {
			//Manejar diferentes tipos de errores
			let message = 'Error al iniciar sesión'

			if (err.response?.status === 403) {
				message = err.response.data.message || 'Tu cuenta está pendiente de autorización.'
			}
			// Error 401 - Credenciales incorrectas
			else if (err.response?.status === 401) {
				message = err.response.data.message || 'Credenciales incorrectas'
			}
			// Errores de validación (422)
			else if (err.response?.status === 422) {
				const errors = err.response.data.errors
				message = Object.values(errors).flat().join(', ')
			}
			// Mensaje directo del backend
			else if (err.response?.data?.message) {
				message = err.response.data.message
			}
			// Error de red
			else if (err.message) {
				message = err.message
			}
			error.value = message
			console.error('Error en login:', message)
			return { success: false, message }
		} finally {
			loading.value = false
		}
	}

	/**
	 * Registra un nuevo usuario
	 * @param {object} userData - Datos del usuario a registrar
	 */
	const register = async (userData) => {
		loading.value = true
		error.value = null

		try {
			const response = await api.post('/register', userData)

			if (response.data.success) {
				const { token: newToken, user: newUser } = response.data.data

				//Por defecto, el registro no persiste (remember = false)
				token.value = newToken
				user.value = newUser

				saveToStorage(newToken, newUser, false)

				//api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`

				return { success: true }
			} else {
				throw new Error(response.data.message || 'Error al registrarse')
			}
		} catch (err) {
			let message = 'Error al registrarse'
			let rawErrors = null

			// Si el error viene del interceptor en api.js
			if (err.errors) {
				rawErrors = err.errors
				message = Object.values(rawErrors).flat().join(', ')
			}
			// Si viene directo de Axios (por si el interceptor falla)
			else if (err.response?.data?.errors) {
				rawErrors = err.response.data.errors
				message = Object.values(rawErrors).flat().join(', ')
			}
			// Otros mensajes de error
			else if (err.response?.data?.message) {
				message = err.response.data.message
			} else if (err.message) {
				message = err.message
			}

			error.value = message
			console.error('Error en registro:', message)

			// Devolvemos tanto el mensaje como el objeto de errores
			return {
				success: false,
				message,
				errors: rawErrors,
			}
			// -------------------------------------------------------
		} finally {
			loading.value = false
		}
	}

	/**
	 * Cierra la sesión del usuario
	 */
	const logout = async () => {
		loading.value = true

		try {
			// Llamar al endpoint de logout
			await api.post('/logout')
		} catch (err) {
			console.error('Error al cerrar sesión:', err)
		} finally {
			// Limpiar estado local independientemente del resultado
			clearAuth()
			router.push('/login')
			loading.value = false
		}
	}

	/**
	 * Obtiene los datos del usuario desde el backend
	 * Usado para validar tokens y actualizar datos
	 */
	const fetchUser = async () => {
		if (!token.value) {
			throw new Error('No hay token disponible')
		}

		try {
			const response = await api.get('/me')

			if (response.data.success) {
				user.value = response.data.data.user

				//Actualizar en el storage activo
				const storage = getActiveStorage()
				storage.setItem('auth-user', JSON.stringify(user.value))
			}
		} catch (err) {
			console.error('Error al obtener usuario:', err)

			//Si es 401, el token es inválido
			if (err.response?.status === 401) {
				clearAuth()
				router.push('/login')
			}
			throw err
		}
	}

	/**
	 * Verifica si el usuario está autenticado con el backend
	 */
	const checkAuth = async () => {
		if (!token.value) return false

		try {
			const response = await api.get('/check')
			return response.data.success && response.data.authenticated
		} catch (err) {
			clearAuth()
			return false
		}
	}

	/**
	 * Actualiza el perfil del usuario
	 * @param {object} profileData - Datos a actualizar
	 */
	const updateProfile = async (profileData) => {
		loading.value = true
		error.value = null

		try {
			const response = await api.put('/profile', profileData)

			if (response.data.success) {
				user.value = { ...user.value, ...response.data.data }

				//Actualizar en el storage activo
				const storage = getActiveStorage()
				storage.setItem('auth-user', JSON.stringify(user.value))

				return { success: true, message: response.data.message }
			}
		} catch (err) {
			const message = err.response?.data?.message || 'Error al actualizar perfil'
			error.value = message
			return { success: false, message }
		} finally {
			loading.value = false
		}
	}

	const forgotPassword = async (email) => {
		loading.value = true
		error.value = null

		try {
			// POST /auth/forgot-password (pública)
			const response = await api.post('/auth/forgot-password', { email })

			if (response.data.success) {
				return { success: true, message: response.data.message }
			}
		} catch (err) {
			const message = err.response?.data?.message || 'Error al solicitar recuperación'
			error.value = message
			return { success: false, message }
		} finally {
			loading.value = false
		}
	}

	const getTokens = async () => {
		try {
			// GET /tokens
			const response = await api.get('/tokens')
			return response.data.success ? response.data.data : []
		} catch (err) {
			console.error('Error al obtener tokens:', err)
			return []
		}
	}

	const revokeToken = async (tokenId) => {
		try {
			const response = await api.delete(`/tokens/${tokenId}`)
			return { success: response.data.success, message: response.data.message }
		} catch (err) {
			return {
				success: false,
				message: err.response?.data?.message || 'Error al revocar token',
			}
		}
	}

	/**
	 * Cambia la contraseña del usuario
	 * @param {object} passwords - { current_password, new_password, new_password_confirmation }
	 */
	const changePassword = async (passwords) => {
		loading.value = true
		error.value = null

		try {
			const response = await api.put('/change-password', passwords)

			if (response.data.success) {
				return { success: true, message: response.data.message }
			}
		} catch (err) {
			const message = err.response?.data?.message || 'Error al cambiar contraseña'
			error.value = message
			return { success: false, message }
		} finally {
			loading.value = false
		}
	}

	/**
	 * Limpia el estado de autenticación (local y storage)
	 */
	const clearAuth = () => {
		token.value = null
		user.value = null
		error.value = null

		//delete api.defaults.headers.common['Authorization']

		clearAllStorage()
	}

	// Permisos
	const can = (permission) => {
		if (!user.value) return false

		//conecta con el método getAbilitiesByRole
		const permissions = {
			//Permisos de Administrador
			create: user.value.rol === 'Administrador' || user.value.rol === 'Editor',
			read: true, //Todos pueden leer
			update: user.value.rol === 'Administrador' || user.value.rol === 'Editor',
			delete: user.value.rol === 'Administrador',
			'manage-users': user.value.rol === 'Administrador',
			'view-statistics': true,
			'export-data': user.value.rol === 'Administrador',
			'bulk-operations': user.value.rol === 'Administrador',
		}

		return permissions[permission] || false
	}

	return {
		// State
		user,
		token,
		loading,
		error,
		// Getters
		isAuthenticated,
		isAdmin,
		isEditor,
		userName,
		userInitials,
		userEmail,
		userRole,
		userStatus,
		// Actions
		login,
		register,
		logout,
		fetchUser,
		checkAuth,
		updateProfile,
		changePassword,
		clearAuth,
		initAuth,
		can,
	}
})
