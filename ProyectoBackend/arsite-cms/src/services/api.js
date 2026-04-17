//src/services/api.js
import axios from 'axios'
import router from '@/router'
import logger from '@/utils/logger'
//import { response } from 'express'

//console.log('Entorno:', import.meta.env.MODE)
//console.log('API Base URL:', import.meta.env.VITE_API_BASE_URL)

//variables de entorno

//URL base para el API
const api = axios.create({
	//Instancia de axios
	baseURL: import.meta.env.VITE_API_BASE_URL,
	headers: {
		'Content-Type': 'application/json',
		Accept: 'application/json',
	},
	withCredentials: true,
	timeout: 10000, //10 segundos
})

//Interceptor: Agregar token de autenticación en cada petición
api.interceptors.request.use(
	(config) => {
		const token = localStorage.getItem('auth-token') || sessionStorage.getItem('auth-token')
		if (token) {
			config.headers.Authorization = `Bearer ${token}`
		}
		return config
	},
	(error) => {
		return Promise.reject(error)
	},
)

//Interceptor: manejar errores globales
api.interceptors.response.use(
	(response) => {
		return response
	},
	(error) => {
		//Error de red
		if (!error.response) {
			logger.error('Error de red:', error.message)
			return Promise.reject({
				message: 'Error de conexión. Verifica tu internet.',
				type: 'network',
			})
		}

		//Manejo de errores específicos
		switch (error.response.status) {
			case 401:
				//No autenticado, redirigir al login
				logger.warn('No autenticado')
				localStorage.removeItem('auth-token')
				localStorage.removeItem('auth-user')

				if (router.currentRoute.value.path !== '/login') {
					router.push({
						path: '/login',
						query: { redirect: router.currentRoute.value.fullPath },
					})
				}
				break

			case 403:
				//No autorizado
				logger.warn('No tienes permisos')
				break

			case 404:
				logger.warn('Recurso no encontrado')
				break

			case 422:
				//Errores de validación
				const validationErrors = error.response.data.errors
				logger.warn('Errores de validación:', validationErrors)
				return Promise.reject({
					message: error.response.data.message || 'Error de validación',
					errors: validationErrors,
					status: 422,
				})

			case 500:
				logger.error('Error del servidor')
				break

			default:
				logger.error('Error:', error.response.status)
		}

		return Promise.reject(error)
	},
)

export default api
