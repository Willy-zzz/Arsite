<template>
	<!-- Overlay con transición -->
	<Transition name="fade">
		<div
			v-if="show"
			class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm"
			@click="handleOverlayClick"
		>
			<!-- Modal -->
			<Transition name="scale">
				<div
					v-if="show"
					class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4 overflow-hidden"
					@click.stop
				>
					<!-- Header -->
					<div class="bg-amber-500 px-6 py-4">
						<div class="flex items-center space-x-3">
							<!-- Icono de advertencia animado -->
							<div class="flex-shrink-0">
								<svg
									class="h-8 w-8 text-white animate-pulse"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
									/>
								</svg>
							</div>

							<!-- Título -->
							<div>
								<h3 class="text-lg font-bold text-white">Sesión por expirar</h3>
							</div>
						</div>
					</div>

					<!-- Body -->
					<div class="px-6 py-5">
						<p class="text-gray-700 mb-4">
							Tu sesión se cerrará automáticamente por inactividad en:
						</p>

						<!-- Countdown grande -->
						<div
							class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-lg p-6 mb-4 text-center border-2 border-amber-200"
						>
							<div class="text-5xl font-bold text-amber-600 tabular-nums">
								{{ formatTime(remainingSeconds) }}
							</div>
							<div class="text-sm text-gray-600 mt-2">minutos:segundos</div>
						</div>

						<!-- Mensaje informativo -->
						<p class="text-sm text-gray-600 text-center">
							Haz clic en "Continuar" para mantener tu sesión activa
						</p>
					</div>

					<!-- Footer con botones -->
					<div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
						<button
							@click="handleLogoutNow"
							class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors"
						>
							Cerrar sesión ahora
						</button>

						<button
							@click="handleContinue"
							class="px-4 py-2 text-sm font-medium text-white bg-amber-500 border border-transparent rounded-md hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors shadow-sm"
						>
							✓ Continuar sesión
						</button>
					</div>
				</div>
			</Transition>
		</div>
	</Transition>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

// Props
const props = defineProps({
	show: {
		type: Boolean,
		required: true,
	},
	remainingSeconds: {
		type: Number,
		default: 0,
	},
})

// Emits
const emit = defineEmits(['continue', 'logout-now'])

// Store
const authStore = useAuthStore()

/**
 * Formatea los segundos a formato MM:SS
 */
const formatTime = (seconds) => {
	const mins = Math.floor(seconds / 60)
	const secs = seconds % 60
	return `${mins}:${secs.toString().padStart(2, '0')}`
}

/**
 * Handler para continuar la sesión
 */
const handleContinue = () => {
	emit('continue')
}

/**
 * Handler para cerrar sesión inmediatamente
 */
const handleLogoutNow = async () => {
	await authStore.logout()
	emit('logout-now')
}

/**
 * Handler para clic en overlay (continuar sesión)
 */
const handleOverlayClick = () => {
	emit('continue')
}
</script>

<style scoped>
/* Transición de fade para el overlay */
.fade-enter-active,
.fade-leave-active {
	transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
	opacity: 0;
}

/* Transición de scale para el modal */
.scale-enter-active,
.scale-leave-active {
	transition: all 0.3s ease;
}

.scale-enter-from,
.scale-leave-to {
	opacity: 0;
	transform: scale(0.95);
}
</style>
