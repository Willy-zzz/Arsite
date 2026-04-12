// src/composables/useInactivityLogout.js
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { getInactivityConfig, isInactivityMonitoringEnabled } from '@/config/security.config'

/**
 * Composable para cerrar sesión automáticamente por inactividad
 * Solo se activa cuando remember = false (sesiones temporales)
 * @returns {object} - { showWarning, remainingSeconds, resetTimer, cancelLogout, stopMonitoring }
 */
export function useInactivityLogout() {
	const authStore = useAuthStore()

	//Leer configuración
	const config = getInactivityConfig()

	//si está deshabilitado, retornar objeto dummy
	if (!isInactivityMonitoringEnabled()) {
		return {
			showWarning: ref(false),
			remainingSeconds: ref(0),
			resetTimer: () => {},
			cancelLogout: () => {},
			stopMonitoring: () => {},
		}
	}

	// Estado reactivo
	const showWarning = ref(false)
	const remainingSeconds = ref(0)

	// Timers
	let inactivityTimer = null
	let warningTimer = null
	let countdownInterval = null

	// Configuración
	const TIMEOUT_MS = config.timeoutMinutes * 60 * 1000
	const WARNING_MS = config.warningMinutes * 60 * 1000
	const WARNING_THRESHOLD_MS = TIMEOUT_MS - WARNING_MS

	/**
	 * Verifica si debe monitorear inactividad
	 * Solo cuando: usuario autenticado Y remember = false
	 */
	const shouldMonitor = () => {
		if (!config.enabled) return false
		const isRemembered = localStorage.getItem('remember-me') === 'true'

		//Si está configurado para solo sesiones temporales
		if (config.onlyForTemporarySessions && isRemembered) {
			return false
		}
		return authStore.isAuthenticated
	}

	/**
	 * Ejecuta el logout automático
	 */
	const executeLogout = async () => {
		console.warn(' Cerrando sesión por inactividad...')

		// Limpiar timers
		clearAllTimers()

		//Guardar razón del logout en sessionstorage
		sessionStorage.setItem('logout-reason', 'inactivity')

		// Cerrar sesión
		await authStore.logout()

		// Mostrar notificación (opcional)
		alert('Tu sesión se cerró por inactividad de 15 minutos.')
	}

	/**
	 * Inicia el countdown visual en los últimos minutos
	 */
	const startCountdown = () => {
		remainingSeconds.value = Math.floor(WARNING_MS / 1000)

		countdownInterval = setInterval(() => {
			remainingSeconds.value -= 1

			if (remainingSeconds.value <= 0) {
				clearInterval(countdownInterval)
			}
		}, 1000)
	}

	/**
	 * Resetea el timer de inactividad
	 */
	const resetTimer = () => {
		// Solo resetear si debe monitorear
		if (!shouldMonitor()) return

		// Limpiar timers anteriores
		clearAllTimers()

		// Ocultar advertencia si estaba visible
		if (showWarning.value) {
			showWarning.value = false
		}

		// Timer para mostrar advertencia
		warningTimer = setTimeout(() => {
			showWarning.value = true
			startCountdown()

			console.warn(
				`⚠️ Advertencia: sesión se cerrará en ${config.warningMinutes} minutos por inactividad`,
			)
		}, WARNING_THRESHOLD_MS)

		// Timer para ejecutar logout
		inactivityTimer = setTimeout(executeLogout, TIMEOUT_MS)
	}

	/**
	 * Cancela el logout y resetea el timer
	 */
	const cancelLogout = () => {
		console.log(' Sesión extendida por actividad del usuario')
		showWarning.value = false
		resetTimer()
	}

	/**
	 * Limpia todos los timers
	 */
	const clearAllTimers = () => {
		if (inactivityTimer) {
			clearTimeout(inactivityTimer)
			inactivityTimer = null
		}

		if (warningTimer) {
			clearTimeout(warningTimer)
			warningTimer = null
		}

		if (countdownInterval) {
			clearInterval(countdownInterval)
			countdownInterval = null
		}
	}

	/**
	 * Detiene completamente el monitoreo
	 */
	const stopMonitoring = () => {
		clearAllTimers()
		showWarning.value = false
		removeEventListeners()
		console.log(' Monitoreo de inactividad detenido')
	}

	// ================== EVENT LISTENERS ==================

	const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']

	const addEventListeners = () => {
		events.forEach((event) => {
			document.addEventListener(event, resetTimer, true)
		})
	}

	const removeEventListeners = () => {
		events.forEach((event) => {
			document.removeEventListener(event, resetTimer, true)
		})
	}

	// ================== LIFECYCLE ==================

	onMounted(() => {
		if (shouldMonitor()) {
			console.log(
				`Monitoreando inactividad: ${config.timeoutMinutes} min ` +
					`(advertencia a los ${config.timeoutMinutes - config.warningMinutes} min)`,
			)
			addEventListeners()
			resetTimer()
		} else {
			console.log('Monitoreo de inactividad no aplica (remember=true o deshabilitado')
		}
	})

	onUnmounted(() => {
		stopMonitoring()
	})

	// ================== RETURN ==================

	return {
		showWarning,
		remainingSeconds,
		resetTimer,
		cancelLogout,
		stopMonitoring,
	}
}
