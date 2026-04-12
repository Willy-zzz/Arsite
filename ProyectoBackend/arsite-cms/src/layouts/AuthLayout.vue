<template>
	<div
		class="h-screen flex flex-col bg-gradient-to-br from-gray-50 to-gray-100 font-sans overflow-hidden"
	>
		<header class="flex-none bg-white shadow-sm border-b border-gray-200">
			<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
				<h1
					class="text-xs sm:text-sm lg:text-base font-semibold text-gray-900 leading-tight break-words"
				>
					Sistema de Administración de Contenido de Ar-Site Integradores
				</h1>
			</div>
		</header>

		<main class="flex-1 flex items-center justify-center px-4 py-3 pt-3 overflow-y-auto">
			<router-view v-slot="{ Component }">
				<transition name="fade" mode="out-in">
					<component :is="Component" />
				</transition>
			</router-view>
		</main>

		<footer class="flex-none bg-indigo-700 text-white py-2">
			<div class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8 text-[12px]">
				<div class="flex flex-col sm:flex-row justify-between items-center">
					<p class="text-xs opacity-85">
						© {{ currentYear }} Ar-Site Integradores S.A. de C.V.
					</p>
					<div class="flex space-x-3 text-xs">
						<a href="#" class="opacity-70 hover:opacity-100 underline">Privacidad</a>
						<a href="#" class="opacity-70 hover:opacity-100 underline"
							>Términos y Condiciones</a
						>
					</div>
				</div>
			</div>
		</footer>
	</div>
</template>

<script setup>
import { computed } from 'vue'

// Función para obtener el año actual automáticamente
const currentYear = computed(() => new Date().getFullYear())
</script>

<!-- Transición suave entre Login y Registro -->
<style scoped>
.fade-enter-active,
.fade-leave-active {
	transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
	opacity: 0;
}
main {
	min-height: 0;
}
</style>
