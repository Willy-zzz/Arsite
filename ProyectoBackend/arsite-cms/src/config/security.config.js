// src/config/security.config.js

/**
 * Configuración de seguridad de la aplicación
 */
export const securityConfig = {
	/**
	 * Configuración de logout por inactividad
	 */
	inactivity: {
		//  Activar/desactivar globalmente
		enabled: true,

		//  Minutos de inactividad antes del logout
		timeoutMinutes: 15,

		//  Minutos antes del logout para mostrar advertencia
		warningMinutes: 2,

		//  Solo aplica cuando remember = false
		onlyForTemporarySessions: true,
	},

	/**
	 * Configuración de tokens
	 */
	tokens: {
		// Nombre del token en storage
		tokenKey: 'auth-token',

		// Nombre del usuario en storage
		userKey: 'auth-user',

		// Nombre de la preferencia remember
		rememberKey: 'remember-me',
	},

	/**
	 * Eventos que resetean el timer de inactividad
	 */
	activityEvents: ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'],
}

/**
 * Obtiene la configuración de inactividad
 */
export function getInactivityConfig() {
	return securityConfig.inactivity
}

/**
 * Verifica si el monitoreo de inactividad está habilitado
 */
export function isInactivityMonitoringEnabled() {
	return securityConfig.inactivity.enabled
}
