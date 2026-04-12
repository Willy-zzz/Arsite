<template>
	<div class="flex-1 flex flex-col">
		<!-- Modal de advertencia de inactividad -->
		<InactivityWarning
			:show="showWarning"
			:remaining-seconds="remainingSeconds"
			@continue="cancelLogout"
			@logout-now="handleLogoutNow"
		/>

		<!-- Contenido principal -->
		<router-view />
	</div>
</template>

<script setup>
import { watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useInactivityLogout } from '@/composables/useInactivityLogout'
import InactivityWarning from '@/components/modals/InactivityWarning.vue'

const authStore = useAuthStore()

//  Configurar monitoreo de inactividad
// 15 minutos de timeout, advertencia 2 minutos antes
const { showWarning, remainingSeconds, cancelLogout, stopMonitoring } = useInactivityLogout(15, 2)

/**
 * Handler para logout inmediato desde el modal
 */
const handleLogoutNow = () => {
	stopMonitoring()
}

/**
 * Monitorear cambios en autenticación para detener/iniciar monitoreo
 */
watch(
	() => authStore.isAuthenticated,
	(isAuth) => {
		if (!isAuth) {
			// Si cierra sesión, detener monitoreo
			stopMonitoring()
		}
	},
)
</script>

<style>
/* Estilos globales si los necesitas */
</style>
