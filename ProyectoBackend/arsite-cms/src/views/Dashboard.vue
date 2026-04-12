<script setup>
import { ref } from 'vue'

const dashboardCards = [
	{ title: 'Banner', href: '/banners', accent: 'blue' },
	{ title: 'Sliders', href: '/sliders', accent: 'cyan' },
	{ title: 'Productos', href: '/products', accent: 'orange' },
	{ title: 'Servicios', href: '/services', accent: 'purple' },
	{ title: 'Partners', href: '/partners', accent: 'blue' },
	{ title: 'Clientes', href: '/clients', accent: 'cyan' },
	{ title: 'Hitos', href: '/milestones', accent: 'orange' },
	{ title: 'Noticias', href: '/news', accent: 'purple' },
]

const accents = {
	blue: 'from-[#5D78FF] to-[#1D1999]',
	cyan: 'from-[#0ED4F6] to-[#2C6EE9]',
	orange: 'from-[#FFAE3C] to-[#FF8C00]',
	purple: 'from-[#A78BFA] to-[#6D28D9]',
}

// Iconos personalizados por módulo
const moduleIcons = {
	Banner: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />`,
	Sliders: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />`,
	Productos: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />`,
	Servicios: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />`,
	Partners: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />`,
	Clientes: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />`,
	Hitos: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />`,
	Noticias: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />`,
}
</script>

<template>
	<div>
		<h1 class="mb-4 md:mb-[25px] pb-5 text-[22px] md:text-[26px] font-bold text-[#1c2321]">
			Dashboard
		</h1>

		<div class="cards-grid grid gap-4 md:gap-6">
			<router-link
				v-for="card in dashboardCards"
				:key="card.title"
				:to="card.href"
				class="group block bg-transparent hover:bg-transparent outline-none focus:outline-none focus:ring-0 decoration-none select-none"
			>
				<div
					class="flex h-[100px] md:h-[110px] w-full items-stretch overflow-hidden rounded-[8px] bg-white shadow-[0px_4px_12px_rgba(0,0,0,0.08)] transition-all duration-200 group-hover:-translate-y-[3px] group-hover:shadow-[0px_6px_18px_rgba(0,0,0,0.12)]"
				>
					<div
						:class="['w-2 bg-gradient-to-b flex-shrink-0', accents[card.accent]]"
					></div>

					<div
						class="m-[20px] md:m-[10px] flex flex-1 items-center gap-[10px] md:gap-[12px] rounded-[6px] bg-[rgba(220,235,255,0.45)] p-[12px] md:p-[15px]"
					>
						<div
							class="h-[20px] w-[20px] md:h-[22px] md:w-[22px] rounded bg-white/60 flex items-center justify-center flex-shrink-0"
						>
							<svg
								class="h-4 w-4 text-[#312AFF]"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
							>
								<g v-html="moduleIcons[card.title]"></g>
							</svg>
						</div>
						<h3 class="text-[14px] md:text-[15px] font-medium text-[#1c2321]">
							{{ card.title }}
						</h3>
					</div>
				</div>
			</router-link>
		</div>
	</div>
</template>

<style scoped>
.cards-grid {
	grid-template-columns: 1fr;
}

@media (min-width: 768px) {
	.cards-grid {
		grid-template-columns: repeat(4, 1fr);
	}
}
</style>
