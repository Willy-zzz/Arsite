<template>
	<!-- Modal de Cuenta Pendiente -->
	<transition name="modal-fade">
		<div
			v-if="modelValue"
			class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
		>
			<transition name="modal-scale">
				<div
					v-if="modelValue"
					class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden"
				>
					<!-- Header con ícono -->
					<div class="bg-gradient-to-br from-amber-500 to-orange-500 p-8 text-center">
						<div
							class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full mb-4 shadow-lg"
						>
							<svg
								class="w-10 h-10 text-amber-500"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
								/>
							</svg>
						</div>
						<h2 class="text-2xl font-bold text-white mb-2">¡Registro Exitoso!</h2>
						<p class="text-amber-50 text-sm">Tu cuenta ha sido creada correctamente</p>
					</div>

					<!-- Contenido -->
					<div class="p-8">
						<div class="space-y-4 mb-8">
							<div class="flex items-start gap-3">
								<div
									class="flex-shrink-0 w-6 h-6 bg-amber-100 rounded-full flex items-center justify-center mt-0.5"
								>
									<svg
										class="w-4 h-4 text-amber-600"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M5 13l4 4L19 7"
										/>
									</svg>
								</div>
								<div>
									<h3 class="font-semibold text-gray-900 text-sm">
										Cuenta Creada
									</h3>
									<p class="text-sm text-gray-600 mt-1">
										Tu información ha sido registrada exitosamente
									</p>
								</div>
							</div>

							<div class="flex items-start gap-3">
								<div
									class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mt-0.5"
								>
									<svg
										class="w-4 h-4 text-blue-600"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
										/>
									</svg>
								</div>
								<div>
									<h3 class="font-semibold text-gray-900 text-sm">
										Pendiente de Autorización
									</h3>
									<p class="text-sm text-gray-600 mt-1">
										Un administrador debe aprobar tu cuenta antes de que puedas
										acceder al sistema
									</p>
								</div>
							</div>

							<div class="flex items-start gap-3">
								<div
									class="flex-shrink-0 w-6 h-6 bg-green-100 rounded-full flex items-center justify-center mt-0.5"
								>
									<svg
										class="w-4 h-4 text-green-600"
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
									<h3 class="font-semibold text-gray-900 text-sm">
										Te Notificaremos
									</h3>
									<p class="text-sm text-gray-600 mt-1">
										Recibirás una notificación cuando tu cuenta sea activada
									</p>
								</div>
							</div>
						</div>

						<!-- Nota informativa -->
						<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
							<div class="flex gap-3">
								<svg
									class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5"
									fill="currentColor"
									viewBox="0 0 20 20"
								>
									<path
										fill-rule="evenodd"
										d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
										clip-rule="evenodd"
									/>
								</svg>
								<p class="text-xs text-amber-800 leading-relaxed">
									<strong>Importante:</strong> No podrás iniciar sesión hasta que
									un administrador apruebe tu cuenta. Este proceso suele tardar
									entre 24-48 horas.
								</p>
							</div>
						</div>

						<!-- Contador de redirección -->
						<div
							class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 mb-6 text-center"
						>
							<p class="text-sm text-indigo-800">
								Serás redirigido al login en
								<span
									class="inline-flex items-center justify-center w-10 h-10 mx-1 font-black text-xl text-white bg-indigo-600 rounded-full animate-pulse"
								>
									{{ countdown }}
								</span>
								segundo{{ countdown !== 1 ? 's' : '' }}
							</p>
						</div>

						<!-- Botón de acción -->
						<button
							@click="handleClose"
							class="w-full py-3 px-4 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:from-amber-600 hover:to-orange-600 transition-all duration-200 active:scale-95 flex items-center justify-center gap-2"
						>
							<span>Ir al Inicio de Sesión Ahora</span>
							<svg
								class="w-5 h-5"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M13 7l5 5m0 0l-5 5m5-5H6"
								/>
							</svg>
						</button>
					</div>
				</div>
			</transition>
		</div>
	</transition>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'

const props = defineProps({
	modelValue: {
		type: Boolean,
		required: true,
	},
})

const emit = defineEmits(['update:modelValue', 'close'])

const countdown = ref(10)
let countdownInterval = null

const startCountdown = () => {
	countdown.value = 10
	if (countdownInterval) {
		clearInterval(countdownInterval)
	}
	countdownInterval = setInterval(() => {
		countdown.value--
		if (countdown.value <= 0) {
			handleClose()
		}
	}, 1000)
}

const stopCountdown = () => {
	if (countdownInterval) {
		clearInterval(countdownInterval)
		countdownInterval = null
	}
}

const handleClose = () => {
	stopCountdown()
	emit('update:modelValue', false)
	emit('close')
}

// Watch para iniciar countdown cuando se abre el modal
watch(
	() => props.modelValue,
	(newVal) => {
		if (newVal) {
			startCountdown()
		} else {
			stopCountdown()
		}
	},
)

onUnmounted(() => {
	stopCountdown()
})
</script>

<style scoped>
/* Animaciones del Modal */
.modal-fade-enter-active,
.modal-fade-leave-active {
	transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
	opacity: 0;
}

.modal-scale-enter-active {
	transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-scale-leave-active {
	transition: all 0.2s ease;
}

.modal-scale-enter-from {
	opacity: 0;
	transform: scale(0.9) translateY(-20px);
}

.modal-scale-leave-to {
	opacity: 0;
	transform: scale(0.95);
}
</style>
