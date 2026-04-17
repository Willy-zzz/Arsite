<template>
	<div class="min-h-screen bg-[#f4f6f8] p-4 md:p-6">
		<!-- Header -->
		<div class="max-w-7xl mx-auto mb-4 pl-1">
			<div class="flex justify-between items-center">
				<div>
					<h1 class="text-3xl font-black text-gray-900 tracking-tight">
						Gestión de Usuarios
					</h1>
					<p class="text-gray-600 mt-1 pl-1 pt-1 pb-2">
						Administración de los usuarios y sus permisos en el sistema
					</p>
				</div>
				<div class="flex gap-3">
					<!-- Botón de estadísticas -->
					<button
						@click="toggleStats"
						class="px-4 py-2.5 bg-white border-2 border-gray-300 text-gray-700 font-semibold rounded-xl shadow-sm hover:shadow-md hover:border-indigo-400 transition-all duration-200 flex items-center gap-2"
					>
						<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
							/>
						</svg>
						<span>Estadísticas</span>
					</button>

					<!-- Botón para crear usuario -->
					<button
						v-if="authStore.isAdmin"
						@click="openCreateModal"
						class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:from-indigo-700 hover:to-blue-700 transition-all duration-200 active:scale-95 flex items-center gap-2"
					>
						<svg
							class="w-5 h-5 flex-shrink-0"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M12 6v6m0 0v6m0-6h6m-6 0H6"
							/>
						</svg>
						<span>Nuevo Usuario</span>
					</button>
				</div>
			</div>
		</div>

		<!-- Estadísticas -->
		<transition name="slide-down">
			<div v-if="showStats" class="max-w-7xl mx-auto mb-6">
				<div class="bg-white rounded-2xl shadow-lg p-6 border-2 border-indigo-100">
					<h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
						<svg
							class="w-5 h-5 text-indigo-600"
							fill="currentColor"
							viewBox="0 0 20 20"
						>
							<path
								d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"
							/>
						</svg>
						Estadísticas Generales
					</h3>

					<div v-if="loadingStats" class="text-center py-8">
						<div
							class="animate-spin rounded-full h-10 w-10 border-4 border-indigo-200 border-t-indigo-600 mx-auto"
						></div>
					</div>

					<div v-else class="grid grid-cols-2 gap-4">
						<!-- Total usuarios -->
						<div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4">
							<div class="flex items-center justify-between">
								<div>
									<p class="text-sm font-medium text-blue-700">Total Usuarios</p>
									<p class="text-2xl font-bold text-blue-900 mt-1">
										{{ stats.total_usuarios || 0 }}
									</p>
								</div>
								<div class="bg-blue-500 rounded-full p-3">
									<svg
										class="w-6 h-6 text-white"
										fill="currentColor"
										viewBox="0 0 20 20"
									>
										<path
											d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"
										/>
									</svg>
								</div>
							</div>
						</div>

						<!-- Usuarios activos -->
						<div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4">
							<div class="flex items-center justify-between">
								<div>
									<p class="text-sm font-medium text-green-700">Activos</p>
									<p class="text-2xl font-bold text-green-900 mt-1">
										{{ stats.usuarios_activos || 0 }}
									</p>
								</div>
								<div class="bg-green-500 rounded-full p-3">
									<svg
										class="w-6 h-6 text-white"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
										/>
									</svg>
								</div>
							</div>
						</div>

						<!-- Usuarios pendientes -->
						<div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-4">
							<div class="flex items-center justify-between">
								<div>
									<p class="text-sm font-medium text-amber-700">Pendientes</p>
									<p class="text-2xl font-bold text-amber-900 mt-1">
										{{ stats.usuarios_pendientes || 0 }}
									</p>
								</div>
								<div class="bg-amber-500 rounded-full p-3">
									<svg
										class="w-6 h-6 text-white"
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
							</div>
						</div>

						<!-- Usuarios inactivos -->
						<div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4">
							<div class="flex items-center justify-between">
								<div>
									<p class="text-sm font-medium text-red-700">Inactivos</p>
									<p class="text-2xl font-bold text-red-900 mt-1">
										{{ stats.usuarios_inactivos || 0 }}
									</p>
								</div>
								<div class="bg-red-500 rounded-full p-3">
									<svg
										class="w-6 h-6 text-white"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
										/>
									</svg>
								</div>
							</div>
						</div>

						<!-- Administradores -->
						<div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4">
							<div class="flex items-center justify-between">
								<div>
									<p class="text-sm font-medium text-purple-700">
										Administradores
									</p>
									<p class="text-2xl font-bold text-purple-900 mt-1">
										{{ stats.administradores || 0 }}
									</p>
								</div>
								<div class="bg-purple-500 rounded-full p-3">
									<svg
										class="w-6 h-6 text-white"
										fill="currentColor"
										viewBox="0 0 20 20"
									>
										<path
											fill-rule="evenodd"
											d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
											clip-rule="evenodd"
										/>
									</svg>
								</div>
							</div>
						</div>

						<!-- Editores -->
						<div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-4">
							<div class="flex items-center justify-between">
								<div>
									<p class="text-sm font-medium text-indigo-700">Editores</p>
									<p class="text-2xl font-bold text-indigo-900 mt-1">
										{{ stats.editores || 0 }}
									</p>
								</div>
								<div class="bg-indigo-500 rounded-full p-3">
									<svg
										class="w-6 h-6 text-white"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
										/>
									</svg>
								</div>
							</div>
						</div>

						<!-- Registros hoy -->
						<div class="bg-gradient-to-br from-teal-50 to-teal-100 rounded-xl p-4">
							<div class="flex items-center justify-between">
								<div>
									<p class="text-sm font-medium text-teal-700">Hoy</p>
									<p class="text-2xl font-bold text-teal-900 mt-1">
										{{ stats.registros_hoy || 0 }}
									</p>
								</div>
								<div class="bg-teal-500 rounded-full p-3">
									<svg
										class="w-6 h-6 text-white"
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
							</div>
						</div>

						<!-- Registros esta semana -->
						<div class="bg-gradient-to-br from-cyan-50 to-cyan-100 rounded-xl p-4">
							<div class="flex items-center justify-between">
								<div>
									<p class="text-sm font-medium text-cyan-700">Esta Semana</p>
									<p class="text-2xl font-bold text-cyan-900 mt-1">
										{{ stats.registros_semana || 0 }}
									</p>
								</div>
								<div class="bg-cyan-500 rounded-full p-3">
									<svg
										class="w-6 h-6 text-white"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
									>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="2"
											d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
										/>
									</svg>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</transition>

		<!-- Contenedor principal -->
		<div
			class="max-w-7xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border-2 border-gray-200"
		>
			<!-- Barra de filtros y búsqueda -->
			<div
				class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b-2 border-gray-200 space-y-4"
			>
				<div class="flex items-center gap-2">
					<!-- Búsqueda -->
					<div class="flex-1 relative">
						<input
							v-model="filters.search"
							@input="applyFilters"
							type="text"
							placeholder="Buscar por nombre o email..."
							class="w-full pl-10 pr-4 py-2 border-2 border-gray-300 rounded-lg text-sm text-gray-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
						/>
						<svg
							class="absolute left-3 top-2.5 w-4 h-4 text-gray-500"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
							/>
						</svg>
					</div>

					<!-- Botón filtros avanzados -->
					<button
						@click="showAdvancedFilters = !showAdvancedFilters"
						:class="[
							showAdvancedFilters || hasAdvancedFiltersActive
								? 'border-indigo-500 text-indigo-600 bg-indigo-50'
								: 'border-gray-300 text-gray-600 hover:border-gray-400',
						]"
						class="flex items-center gap-2 px-4 py-2 border rounded-lg text-sm font-medium transition-all relative"
					>
						<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
							/>
						</svg>
						<span class="hidden sm:inline">Filtros</span>
						<span
							v-if="hasAdvancedFiltersActive"
							class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white"
						></span>
					</button>
				</div>

				<!-- Panel de filtros avanzados -->
				<transition
					enter-active-class="transition duration-200 ease-out"
					enter-from-class="transform -translate-y-4 opacity-0"
					enter-to-class="transform translate-y-0 opacity-100"
					leave-active-class="transition duration-150 ease-in"
					leave-from-class="transform translate-y-0 opacity-100"
					leave-to-class="transform -translate-y-4 opacity-0"
				>
					<div
						v-if="showAdvancedFilters"
						class="grid grid-cols-1 md:grid-cols-3 gap-4 p-5 bg-[#f8fafc] rounded-xl border border-gray-200"
					>
						<!-- Estado -->
						<div class="space-y-1">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Estado</label
							>
							<select
								v-model="filters.estado"
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none text-gray-900"
								:class="{ 'font-semibold': filters.estado }"
							>
								<option value="">Todos los estados</option>
								<option value="Activo">Activo</option>
								<option value="Pendiente">Pendiente</option>
								<option value="Inactivo">Inactivo</option>
							</select>
						</div>

						<!-- Rol -->
						<div class="space-y-1">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Rol</label
							>
							<select
								v-model="filters.rol"
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none text-gray-900"
								:class="{ 'font-semibold': filters.rol }"
							>
								<option value="">Todos los roles</option>
								<option value="Administrador">Administrador</option>
								<option value="Editor">Editor</option>
							</select>
						</div>

						<!-- Ordenar por -->
						<div class="space-y-1">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Ordenar por</label
							>
							<select
								v-model="filters.sort_by"
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none text-gray-900"
							>
								<option value="created_at">Fecha de registro</option>
								<option value="usu_nombre">Nombre</option>
								<option value="email">Email</option>
								<option value="usu_rol">Rol</option>
								<option value="usu_estado">Estado</option>
							</select>
						</div>

						<!-- Dirección -->
						<div class="space-y-1">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Dirección</label
							>
							<select
								v-model="filters.sort_direction"
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none text-gray-900"
							>
								<option value="desc">Descendente</option>
								<option value="asc">Ascendente</option>
							</select>
						</div>

						<!-- Por página -->
						<div class="space-y-1">
							<label
								class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
								>Por página</label
							>
							<select
								v-model="pagination.per_page"
								class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none text-gray-900"
							>
								<option :value="10">10</option>
								<option :value="25">25</option>
								<option :value="50">50</option>
								<option :value="100">100</option>
							</select>
						</div>

						<!-- Botones -->
						<div
							class="md:col-span-3 flex justify-end gap-2 pt-1 border-t border-gray-200"
						>
							<button
								@click="clearFilters"
								class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all"
							>
								Limpiar filtros
							</button>
							<button
								@click="
									() => {
										applyFilters()
										showAdvancedFilters = false
									}
								"
								class="px-6 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-all"
							>
								Aplicar filtros
							</button>
						</div>
					</div>
				</transition>
			</div>

			<!-- Acciones en lote - FLOTANTE CON MÁS ESPACIO -->
			<transition
				enter-active-class="transition duration-200 ease-out"
				enter-from-class="transform -translate-y-2 opacity-0"
				enter-to-class="transform translate-y-0 opacity-100"
				leave-active-class="transition duration-150 ease-in"
				leave-from-class="transform translate-y-0 opacity-100"
				leave-to-class="transform -translate-y-2 opacity-0"
			>
				<div v-if="hasSelectedUsers" class="px-6 pb-6 pt-4">
					<div
						class="flex items-center justify-between p-4 bg-blue-50 border-2 border-blue-300 rounded-xl shadow-lg"
					>
						<span class="text-sm font-semibold text-blue-900">
							{{ selectedUsers.length }} usuario(s) seleccionado(s)
						</span>
						<div class="flex gap-2">
							<button
								v-if="authStore.isAdmin"
								@click="bulkActivate"
								class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-all"
							>
								Activar
							</button>
							<button
								v-if="authStore.isAdmin"
								@click="bulkDeactivate"
								class="px-4 py-2 bg-amber-600 text-white text-sm font-semibold rounded-lg hover:bg-amber-700 transition-all"
							>
								Desactivar
							</button>
							<button
								v-if="authStore.isAdmin"
								@click="confirmBulkDelete"
								class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-all"
							>
								Eliminar
							</button>
						</div>
					</div>
				</div>
			</transition>

			<!-- Tabla -->
			<div class="px-6 pb-6">
				<div
					class="bg-[#004A7C] text-white px-5 py-4 flex justify-between items-center rounded-t-xl"
				>
					<h4 class="text-lg font-bold tracking-tight">Usuarios del Sistema</h4>
					<span class="text-sm font-medium">
						Mostrando {{ displayRange }} de {{ pagination.total }} elementos
					</span>
				</div>

				<div
					class="overflow-x-auto border border-t-0 border-gray-300 rounded-b-xl [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300 [&::-webkit-scrollbar-thumb]:rounded-full"
				>
					<table class="w-full border-collapse table-fixed">
						<thead>
							<tr class="bg-[#f8f9fa] border-b border-gray-300">
								<th class="px-4 py-3 text-center w-[50px]">
									<input
										v-model="allSelected"
										type="checkbox"
										class="w-4 h-4 text-indigo-600 rounded border-gray-300"
									/>
								</th>

								<th
									class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-[200px] cursor-pointer hover:bg-gray-200 transition-colors"
									@click="setSorting('usu_nombre')"
								>
									<div class="flex items-center gap-1">
										Usuario
										<svg
											class="w-3 h-3"
											:class="{
												'rotate-180': filters.sort_direction === 'desc',
											}"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M19 9l-7 7-7-7"
											/>
										</svg>
									</div>
								</th>

								<th
									class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-[170px]"
								>
									Email
								</th>

								<th
									class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-[120px]"
								>
									Rol
								</th>

								<th
									class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-[120px]"
								>
									Estado
								</th>

								<th
									class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-[80px]"
								>
									<div class="flex justify-center">Contenidos</div>
								</th>

								<th
									class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-[140px]"
								>
									Registro
								</th>

								<th
									class="px-3 py-3 text-center text-xs font-bold text-gray-500 uppercase w-[50px]"
								>
									Ver
								</th>
								<th
									class="px-3 py-3 text-center text-xs font-bold text-gray-500 uppercase w-[60px]"
								>
									Editar
								</th>
								<th
									class="px-3 py-3 text-center text-xs font-bold text-gray-500 uppercase w-[65px]"
								>
									Estado
								</th>
								<th
									class="px-3 py-3 text-center text-xs font-bold text-gray-500 uppercase w-[75px]"
								>
									Eliminar
								</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-gray-200 bg-white">
							<!-- Loading -->
							<tr v-if="loading">
								<td colspan="11" class="py-20 text-center">
									<div class="flex justify-center items-center">
										<div
											class="animate-spin rounded-full h-10 w-10 border-4 border-indigo-200 border-t-indigo-600"
										></div>
									</div>
								</td>
							</tr>

							<!-- Error -->
							<tr v-else-if="error && users.length === 0">
								<td colspan="11" class="py-20 text-center text-red-600">
									{{ error }}
								</td>
							</tr>

							<!-- Sin resultados -->
							<tr v-else-if="users.length === 0">
								<td colspan="11" class="py-20 text-center text-gray-400 italic">
									No se encontraron usuarios con los criterios seleccionados
								</td>
							</tr>

							<!-- Lista de usuarios -->
							<tr
								v-else
								v-for="user in users"
								:key="user.id"
								class="hover:bg-blue-50/30 transition-colors"
							>
								<!-- Checkbox -->
								<td class="px-4 py-3 text-center">
									<input
										v-model="selectedUsers"
										:value="user.id"
										type="checkbox"
										class="w-4 h-4 text-indigo-600 rounded border-gray-300"
									/>
								</td>

								<!-- Usuario (Avatar + Nombre) -->
								<td class="px-4 py-3 truncate">
									<div class="flex items-center gap-3">
										<!-- Avatar -->
										<div
											v-if="user.avatar && user.avatar.url"
											class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0 border-2"
											:class="
												user.rol === 'Administrador'
													? 'border-amber-400'
													: 'border-indigo-400'
											"
										>
											<img
												:src="user.avatar.url"
												:alt="user.nombre"
												class="w-full h-full object-cover"
											/>
										</div>
										<div
											v-else
											class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white flex-shrink-0 border-2"
											:class="
												user.rol === 'Administrador'
													? 'bg-amber-500 border-amber-400'
													: 'bg-indigo-500 border-indigo-400'
											"
										>
											{{ user.iniciales }}
										</div>
										<div class="min-w-0">
											<p
												class="font-semibold text-gray-900 truncate max-w-[160px]"
												:title="user.nombre"
											>
												{{ user.nombre }}
											</p>
										</div>
									</div>
								</td>

								<!-- Email -->
								<td class="px-4 py-3">
									<p
										class="text-sm text-gray-600 truncate max-w-[220px]"
										:title="user.email"
									>
										{{ user.email }}
									</p>
								</td>

								<!-- Rol -->
								<td class="px-4 py-3 text-center">
									<span
										class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold"
										:class="{
											'bg-amber-100 text-amber-800':
												user.rol === 'Administrador',
											'bg-indigo-100 text-indigo-800': user.rol === 'Editor',
										}"
									>
										{{ user.rol }}
									</span>
								</td>

								<!-- Estado -->
								<td class="px-4 py-3 text-center">
									<span
										class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold"
										:class="{
											'bg-green-100 text-green-800': user.estado === 'Activo',
											'bg-amber-100 text-amber-800':
												user.estado === 'Pendiente',
											'bg-red-100 text-red-800': user.estado === 'Inactivo',
										}"
									>
										{{ user.estado }}
									</span>
								</td>

								<!-- Total contenidos -->
								<td class="px-4 py-3 text-center align-middle">
									<div class="w-full flex justify-center">
										<span class="text-sm font-semibold text-gray-700">
											{{ user.total_contenidos || 0 }}
										</span>
									</div>
								</td>

								<!-- Fecha de registro -->
								<td class="px-4 py-3 text-center">
									<p class="text-sm text-gray-600">{{ user.fecha_registro }}</p>
								</td>

								<!-- Acciones -->
								<!-- Ver -->
								<td class="px-2 py-3 text-center">
									<button
										@click="viewUser(user)"
										class="p-1 text-blue-600 hover:bg-blue-100 rounded-lg transition-all inline-flex"
										title="Ver detalles"
									>
										<svg
											class="w-4 h-4"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
											/>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
											/>
										</svg>
									</button>
								</td>

								<!-- Editar -->
								<td class="px-2 py-3 text-center">
									<button
										v-if="authStore.isAdmin"
										@click="editUser(user)"
										class="p-1 text-indigo-600 hover:bg-indigo-100 rounded-lg transition-all inline-flex"
										title="Editar"
									>
										<svg
											class="w-4 h-4"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
											/>
										</svg>
									</button>
								</td>

								<!-- Activar / Desactivar -->
								<td class="px-2 py-3 text-center">
									<button
										v-if="authStore.isAdmin && user.estado === 'Pendiente'"
										@click="activateUser(user)"
										class="p-1 text-green-600 hover:bg-green-100 rounded-lg transition-all inline-flex"
										title="Aprobar usuario"
									>
										<svg
											class="w-4 h-4"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
											/>
										</svg>
									</button>
									<button
										v-if="authStore.isAdmin && user.estado === 'Activo'"
										@click="deactivateUser(user)"
										class="p-1 text-amber-600 hover:bg-amber-100 rounded-lg transition-all inline-flex"
										title="Desactivar usuario"
									>
										<svg
											class="w-4 h-4"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
											/>
										</svg>
									</button>
								</td>

								<!-- Eliminar -->
								<td class="px-2 py-3 text-center">
									<button
										v-if="authStore.isAdmin"
										@click="confirmDelete(user)"
										class="p-1 text-red-600 hover:bg-red-100 rounded-lg transition-all inline-flex"
										title="Eliminar"
									>
										<svg
											class="w-4 h-4"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
											/>
										</svg>
									</button>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<!-- Paginación -->
				<div v-if="pagination.last_page > 1" class="mt-6 flex items-center justify-between">
					<!-- Info -->
					<p class="text-sm text-gray-500 hidden md:block">
						Página
						<span class="font-semibold text-gray-700">{{
							pagination.current_page
						}}</span>
						de
						<span class="font-semibold text-gray-700">{{ pagination.last_page }}</span>
					</p>

					<!-- Controles -->
					<div class="flex items-center gap-1 pt-2 mx-auto md:mx-0">
						<!-- Anterior -->
						<button
							@click="changePage(pagination.current_page - 1)"
							:disabled="pagination.current_page === 1"
							class="flex items-center gap-1 px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-indigo-400 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
						>
							<svg
								class="w-4 h-4"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M15 19l-7-7 7-7"
								/>
							</svg>
							<span class="hidden sm:inline">Anterior</span>
						</button>

						<!-- Páginas -->
						<div class="flex gap-1">
							<button
								v-for="page in visiblePages"
								:key="page"
								@click="changePage(page)"
								class="w-9 h-9 rounded-lg text-sm font-semibold transition-all"
								:class="
									page === pagination.current_page
										? 'bg-indigo-600 text-white shadow-sm'
										: 'bg-white border border-gray-300 text-gray-600 hover:bg-indigo-50 hover:border-indigo-400'
								"
							>
								{{ page }}
							</button>
						</div>

						<!-- Siguiente -->
						<button
							@click="changePage(pagination.current_page + 1)"
							:disabled="pagination.current_page === pagination.last_page"
							class="flex items-center gap-1 px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-indigo-400 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
						>
							<span class="hidden sm:inline">Siguiente</span>
							<svg
								class="w-4 h-4"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M9 5l7 7-7 7"
								/>
							</svg>
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- Modal de Crear/Editar -->
		<transition name="modal">
			<div
				v-if="showModal"
				class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
				@click.self="closeModal"
			>
				<div
					class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col"
				>
					<!-- Header del modal -->
					<div
						class="bg-gradient-to-r from-indigo-600 to-blue-600 text-white px-6 py-4 flex justify-between items-center sticky top-0 z-10 rounded-t-2xl flex-shrink-0"
					>
						<h3 class="text-xl font-bold">
							{{
								modalMode === 'create'
									? 'Crear Nuevo Usuario'
									: modalMode === 'edit'
										? 'Editar Usuario'
										: 'Detalles del Usuario'
							}}
						</h3>
						<button
							@click="closeModal"
							class="text-white hover:bg-white/20 rounded-lg p-2 transition-all"
						>
							<svg
								class="w-6 h-6"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M6 18L18 6M6 6l12 12"
								/>
							</svg>
						</button>
					</div>

					<!-- Contenido del modal -->
					<div class="max-h-[80vh] overflow-y-auto p-6 custom-scroll">
						<!-- Modo vista -->
						<div v-if="modalMode === 'view' && selectedUser">
							<!-- Header del perfil -->
							<div class="bg-gradient-to-br from-indigo-50 to-blue-50 -m-6 mb-8 p-8">
								<div class="flex flex-col items-center text-center">
									<div
										v-if="selectedUser.avatar && selectedUser.avatar.url"
										class="w-28 h-28 rounded-full overflow-hidden border-4 border-white shadow-xl mb-4"
									>
										<img
											:src="selectedUser.avatar.url"
											:alt="selectedUser.nombre"
											class="w-full h-full object-cover"
										/>
									</div>
									<div
										v-else
										class="w-28 h-28 rounded-full flex items-center justify-center text-4xl font-bold text-white border-4 border-white shadow-xl mb-4"
										:class="
											selectedUser.rol === 'Administrador'
												? 'bg-gradient-to-br from-amber-500 to-orange-500'
												: 'bg-gradient-to-br from-indigo-500 to-blue-500'
										"
									>
										{{ selectedUser.iniciales }}
									</div>

									<h4 class="text-3xl font-bold text-gray-900 mb-2">
										{{ selectedUser.nombre }}
									</h4>
									<p class="text-gray-600 text-base mb-4">
										{{ selectedUser.email }}
									</p>

									<div class="flex gap-3 pt-2">
										<span
											class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold shadow-sm bg-white"
											:class="{
												'text-amber-700 border-2 border-amber-200':
													selectedUser.rol === 'Administrador',
												'text-indigo-700 border-2 border-indigo-200':
													selectedUser.rol === 'Editor',
											}"
										>
											{{ selectedUser.rol }}
										</span>
										<span
											class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold shadow-sm bg-white"
											:class="{
												'text-green-700 border-2 border-green-200':
													selectedUser.estado === 'Activo',
												'text-amber-700 border-2 border-amber-200':
													selectedUser.estado === 'Pendiente',
												'text-red-700 border-2 border-red-200':
													selectedUser.estado === 'Inactivo',
											}"
										>
											{{ selectedUser.estado }}
										</span>
									</div>
								</div>
							</div>

							<!-- Información del Usuario -->
							<div class="mb-10">
								<h5
									class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 pt-2 pb-2"
								>
									Información del Usuario
								</h5>
								<div class="grid grid-cols-3 gap-4">
									<div
										class="bg-gray-50 rounded-lg p-4 text-center border border-gray-100"
									>
										<p class="text-xs text-gray-500 mb-1 font-medium">
											Fecha de Registro
										</p>
										<p class="text-sm text-gray-900 font-semibold">
											{{ selectedUser.fecha_registro }}
										</p>
									</div>
									<div
										class="bg-gray-50 rounded-lg p-4 text-center border border-gray-100"
									>
										<p class="text-xs text-gray-500 mb-1 font-medium">
											Última Actualización
										</p>
										<p class="text-sm text-gray-900 font-semibold">
											{{ selectedUser.ultima_actualizacion }}
										</p>
									</div>
									<div
										class="bg-gray-50 rounded-lg p-4 text-center border border-gray-100"
									>
										<p class="text-xs text-gray-500 mb-1 font-medium">
											Contenidos Creados
										</p>
										<p class="text-sm text-gray-900 font-semibold">
											{{ selectedUser.total_contenidos || 0 }}
										</p>
									</div>
								</div>
							</div>

							<!-- Permisos y Privilegios -->
							<div>
								<h5
									class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 pt-2 pb-2"
								>
									Permisos y Privilegios
								</h5>

								<!-- Permisos de Administrador -->
								<div
									v-if="selectedUser?.rol === 'Administrador'"
									class="grid grid-cols-1 md:grid-cols-2 gap-2"
								>
									<div
										class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-green-300 hover:shadow-sm transition-all"
									>
										<div
											class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center"
										>
											<svg
												class="w-5 h-5 text-green-600"
												fill="currentColor"
												viewBox="0 0 20 20"
											>
												<path
													fill-rule="evenodd"
													d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
													clip-rule="evenodd"
												/>
											</svg>
										</div>
										<div class="flex-1">
											<p class="font-semibold text-gray-900 text-sm">
												Gestión Completa de Usuarios
											</p>
											<p class="text-xs text-gray-600 mt-0.5">
												Crear, editar, activar, desactivar y eliminar
												usuarios
											</p>
										</div>
									</div>

									<div
										class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-green-300 hover:shadow-sm transition-all"
									>
										<div
											class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center"
										>
											<svg
												class="w-5 h-5 text-green-600"
												fill="currentColor"
												viewBox="0 0 20 20"
											>
												<path
													fill-rule="evenodd"
													d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
													clip-rule="evenodd"
												/>
											</svg>
										</div>
										<div class="flex-1">
											<p class="font-semibold text-gray-900 text-sm">
												Control Total de Contenido
											</p>
											<p class="text-xs text-gray-600 mt-0.5">
												Crear, leer, actualizar y eliminar todo el contenido
											</p>
										</div>
									</div>

									<div
										class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-green-300 hover:shadow-sm transition-all"
									>
										<div
											class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center"
										>
											<svg
												class="w-5 h-5 text-green-600"
												fill="currentColor"
												viewBox="0 0 20 20"
											>
												<path
													fill-rule="evenodd"
													d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
													clip-rule="evenodd"
												/>
											</svg>
										</div>
										<div class="flex-1">
											<p class="font-semibold text-gray-900 text-sm">
												Operaciones en Lote
											</p>
											<p class="text-xs text-gray-600 mt-0.5">
												Gestionar múltiples elementos simultáneamente
											</p>
										</div>
									</div>

									<div
										class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-green-300 hover:shadow-sm transition-all"
									>
										<div
											class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center"
										>
											<svg
												class="w-5 h-5 text-green-600"
												fill="currentColor"
												viewBox="0 0 20 20"
											>
												<path
													fill-rule="evenodd"
													d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
													clip-rule="evenodd"
												/>
											</svg>
										</div>
										<div class="flex-1">
											<p class="font-semibold text-gray-900 text-sm">
												Exportación de Datos
											</p>
											<p class="text-xs text-gray-600 mt-0.5">
												Exportar información a Excel y PDF
											</p>
										</div>
									</div>

									<div
										class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-green-300 hover:shadow-sm transition-all"
									>
										<div
											class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center"
										>
											<svg
												class="w-5 h-5 text-green-600"
												fill="currentColor"
												viewBox="0 0 20 20"
											>
												<path
													fill-rule="evenodd"
													d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
													clip-rule="evenodd"
												/>
											</svg>
										</div>
										<div class="flex-1">
											<p class="font-semibold text-gray-900 text-sm">
												Acceso a Estadísticas
											</p>
											<p class="text-xs text-gray-600 mt-0.5">
												Ver todas las métricas del sistema
											</p>
										</div>
									</div>
								</div>

								<!-- Permisos de Editor -->
								<div
									v-else-if="selectedUser?.rol === 'Editor'"
									class="grid grid-cols-1 md:grid-cols-2 gap-2"
								>
									<div
										class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all"
									>
										<div
											class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
										>
											<svg
												class="w-5 h-5 text-blue-600"
												fill="currentColor"
												viewBox="0 0 20 20"
											>
												<path
													fill-rule="evenodd"
													d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
													clip-rule="evenodd"
												/>
											</svg>
										</div>
										<div class="flex-1">
											<p class="font-semibold text-gray-900 text-sm">
												Ver Listados y Detalles
											</p>
											<p class="text-xs text-gray-600 mt-0.5">
												Visualizar todo el contenido del sistema
											</p>
										</div>
									</div>

									<div
										class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all"
									>
										<div
											class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
										>
											<svg
												class="w-5 h-5 text-blue-600"
												fill="currentColor"
												viewBox="0 0 20 20"
											>
												<path
													fill-rule="evenodd"
													d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
													clip-rule="evenodd"
												/>
											</svg>
										</div>
										<div class="flex-1">
											<p class="font-semibold text-gray-900 text-sm">
												Crear Contenido
											</p>
											<p class="text-xs text-gray-600 mt-0.5">
												Agregar nuevos elementos al sistema
											</p>
										</div>
									</div>

									<div
										class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all"
									>
										<div
											class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
										>
											<svg
												class="w-5 h-5 text-blue-600"
												fill="currentColor"
												viewBox="0 0 20 20"
											>
												<path
													fill-rule="evenodd"
													d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
													clip-rule="evenodd"
												/>
											</svg>
										</div>
										<div class="flex-1">
											<p class="font-semibold text-gray-900 text-sm">
												Editar Contenido Propio
											</p>
											<p class="text-xs text-gray-600 mt-0.5">
												Modificar solo lo que ha creado
											</p>
										</div>
									</div>

									<div
										class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all"
									>
										<div
											class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
										>
											<svg
												class="w-5 h-5 text-blue-600"
												fill="currentColor"
												viewBox="0 0 20 20"
											>
												<path
													fill-rule="evenodd"
													d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
													clip-rule="evenodd"
												/>
											</svg>
										</div>
										<div class="flex-1">
											<p class="font-semibold text-gray-900 text-sm">
												Eliminar Contenido Propio
											</p>
											<p class="text-xs text-gray-600 mt-0.5">
												Borrar solo lo que ha creado
											</p>
										</div>
									</div>

									<div
										class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all"
									>
										<div
											class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
										>
											<svg
												class="w-5 h-5 text-blue-600"
												fill="currentColor"
												viewBox="0 0 20 20"
											>
												<path
													fill-rule="evenodd"
													d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
													clip-rule="evenodd"
												/>
											</svg>
										</div>
										<div class="flex-1">
											<p class="font-semibold text-gray-900 text-sm">
												Acceso a Estadísticas
											</p>
											<p class="text-xs text-gray-600 mt-0.5">
												Ver métricas del sistema
											</p>
										</div>
									</div>

									<div
										class="flex items-center gap-3 p-4 bg-amber-50 rounded-lg border border-amber-200"
									>
										<div
											class="flex-shrink-0 w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center"
										>
											<svg
												class="w-5 h-5 text-amber-600"
												fill="currentColor"
												viewBox="0 0 20 20"
											>
												<path
													fill-rule="evenodd"
													d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
													clip-rule="evenodd"
												/>
											</svg>
										</div>
										<div class="flex-1">
											<p class="font-semibold text-gray-900 text-sm">
												Restricciones
											</p>
											<p class="text-xs text-gray-600 mt-0.5">
												No puede gestionar usuarios, operaciones en lote ni
												exportar datos
											</p>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Modo crear/editar -->
						<form v-else @submit.prevent="submitForm" class="space-y-4">
							<!-- Nombre -->
							<div>
								<label class="block text-sm font-semibold text-gray-900 mb-2">
									Nombre Completo
									<span class="text-red-500">*</span>
								</label>
								<input
									v-model="form.usu_nombre"
									type="text"
									required
									maxlength="100"
									class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900"
									:class="{ 'border-red-500': formErrors.usu_nombre }"
								/>
								<p v-if="formErrors.usu_nombre" class="text-red-500 text-sm mt-1">
									{{ formErrors.usu_nombre[0] }}
								</p>
							</div>

							<!-- Email -->
							<div>
								<label class="block text-sm font-semibold text-gray-900 mb-2">
									Email
									<span class="text-red-500">*</span>
								</label>
								<input
									v-model="form.email"
									type="email"
									required
									maxlength="150"
									class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900"
									:class="{ 'border-red-500': formErrors.email }"
								/>
								<p v-if="formErrors.email" class="text-red-500 text-sm mt-1">
									{{ formErrors.email[0] }}
								</p>
							</div>

							<!-- Contraseña (solo en crear) -->
							<div v-if="modalMode === 'create'">
								<label class="block text-sm font-semibold text-gray-700 mb-2">
									Contraseña
									<span class="text-red-500">*</span>
								</label>
								<input
									v-model="form.password"
									type="password"
									:required="modalMode === 'create'"
									minlength="8"
									class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900"
									:class="{ 'border-red-500': formErrors.password }"
								/>
								<p v-if="formErrors.password" class="text-red-500 text-sm mt-1">
									{{ formErrors.password[0] }}
								</p>
								<p class="text-gray-500 text-xs mt-1">Mínimo 8 caracteres</p>
							</div>

							<!-- Rol -->
							<div>
								<label class="block text-sm font-semibold text-gray-700 mb-2">
									Rol
									<span class="text-red-500">*</span>
								</label>
								<select
									v-model="form.usu_rol"
									required
									class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900"
									:class="{ 'border-red-500': formErrors.usu_rol }"
								>
									<option value="">Seleccionar rol</option>
									<option value="Administrador">Administrador</option>
									<option value="Editor">Editor</option>
								</select>
								<p v-if="formErrors.usu_rol" class="text-red-500 text-sm mt-1">
									{{ formErrors.usu_rol[0] }}
								</p>
							</div>

							<!-- Estado -->
							<div>
								<label class="block text-sm font-semibold text-gray-700 mb-2">
									Estado
								</label>
								<select
									v-model="form.usu_estado"
									class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900"
									:class="{ 'border-red-500': formErrors.usu_estado }"
								>
									<option value="Activo">Activo</option>
									<option value="Pendiente">Pendiente</option>
									<option value="Inactivo">Inactivo</option>
								</select>
								<p v-if="formErrors.usu_estado" class="text-red-500 text-sm mt-1">
									{{ formErrors.usu_estado[0] }}
								</p>
							</div>

							<!-- Botones -->
							<div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
								<button
									type="button"
									@click="closeModal"
									class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-all"
								>
									Cancelar
								</button>
								<button
									type="submit"
									:disabled="loading"
									class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-bold rounded-lg hover:from-indigo-700 hover:to-blue-700 disabled:opacity-50 transition-all"
								>
									<span v-if="loading">Guardando...</span>
									<span v-else>
										{{
											modalMode === 'create'
												? 'Crear Usuario'
												: 'Guardar Cambios'
										}}
									</span>
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</transition>

		<!-- Modal de confirmación de eliminación -->
		<transition name="modal">
			<div
				v-if="showDeleteConfirm"
				class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
				@click.self="showDeleteConfirm = false"
			>
				<div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
					<div class="text-center">
						<div
							class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4"
						>
							<svg
								class="h-10 w-10 text-red-600"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
								/>
							</svg>
						</div>
						<h3 class="text-xl font-bold text-gray-900 mb-2">¿Eliminar usuario?</h3>
						<p class="text-gray-600 mb-6">
							Esta acción no se puede deshacer. El usuario
							<span class="font-semibold">{{ userToDelete?.nombre }}</span>
							será eliminado permanentemente.
						</p>
						<div class="flex gap-3 justify-center">
							<button
								@click="showDeleteConfirm = false"
								class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-all"
							>
								Cancelar
							</button>
							<button
								@click="deleteUser"
								class="px-6 py-2.5 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-all"
							>
								Eliminar
							</button>
						</div>
					</div>
				</div>
			</div>
		</transition>

		<!-- Modal de confirmación de eliminación en lote -->
		<transition name="modal">
			<div
				v-if="showBulkDeleteConfirm"
				class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
				@click.self="showBulkDeleteConfirm = false"
			>
				<div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
					<div class="text-center">
						<div
							class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4"
						>
							<svg
								class="h-10 w-10 text-red-600"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
								/>
							</svg>
						</div>
						<h3 class="text-xl font-bold text-gray-900 mb-2">
							¿Eliminar {{ selectedUsers.length }} usuario(s)?
						</h3>
						<p class="text-gray-600 mb-6">
							Esta acción no se puede deshacer. Los usuarios seleccionados serán
							eliminados permanentemente.
						</p>
						<div class="flex gap-3 justify-center">
							<button
								@click="showBulkDeleteConfirm = false"
								class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-all"
							>
								Cancelar
							</button>
							<button
								@click="executeBulkDelete"
								class="px-6 py-2.5 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-all"
							>
								Eliminar Todos
							</button>
						</div>
					</div>
				</div>
			</div>
		</transition>
	</div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

// Estado
const users = ref([])
const loading = ref(false)
const error = ref(null)
const showModal = ref(false)
const showDeleteConfirm = ref(false)
const showAdvancedFilters = ref(false)
const showBulkDeleteConfirm = ref(false)
const showStats = ref(false)
const loadingStats = ref(false)
const modalMode = ref('view') // 'create', 'edit', 'view'
const selectedUser = ref(null)
const userToDelete = ref(null)
const selectedUsers = ref([])

// Estadísticas
const stats = ref({
	total_usuarios: 0,
	usuarios_activos: 0,
	usuarios_pendientes: 0,
	usuarios_inactivos: 0,
	administradores: 0,
	editores: 0,
	registros_hoy: 0,
	registros_semana: 0,
	registros_mes: 0,
})

// Paginación
const pagination = ref({
	current_page: 1,
	last_page: 1,
	per_page: 10,
	total: 0,
})

// Filtros
const filters = ref({
	search: '',
	estado: '',
	rol: '',
	sort_by: 'created_at',
	sort_direction: 'desc',
})

// Formulario
const form = ref({
	usu_nombre: '',
	email: '',
	password: '',
	usu_rol: '',
	usu_estado: 'Activo',
})

const formErrors = ref({})

// Computed
const hasAdvancedFiltersActive = computed(() => {
	return filters.value.search || filters.value.estado || filters.value.rol
})

const allSelected = computed({
	get: () => users.value.length > 0 && selectedUsers.value.length === users.value.length,
	set: (value) => {
		if (value) {
			selectedUsers.value = users.value.map((u) => u.id)
		} else {
			selectedUsers.value = []
		}
	},
})

const hasSelectedUsers = computed(() => selectedUsers.value.length > 0)

const displayRange = computed(() => {
	if (users.value.length === 0) return '0-0'
	const start = (pagination.value.current_page - 1) * pagination.value.per_page + 1
	const end = Math.min(start + users.value.length - 1, pagination.value.total)
	return `${start}-${end}`
})

const visiblePages = computed(() => {
	const current = pagination.value.current_page
	const last = pagination.value.last_page
	const pages = []

	if (last <= 7) {
		for (let i = 1; i <= last; i++) {
			pages.push(i)
		}
	} else {
		if (current <= 4) {
			for (let i = 1; i <= 5; i++) pages.push(i)
			pages.push('...')
			pages.push(last)
		} else if (current >= last - 3) {
			pages.push(1)
			pages.push('...')
			for (let i = last - 4; i <= last; i++) pages.push(i)
		} else {
			pages.push(1)
			pages.push('...')
			for (let i = current - 1; i <= current + 1; i++) pages.push(i)
			pages.push('...')
			pages.push(last)
		}
	}

	return pages
})

// Métodos de filtros
const setSorting = (column) => {
	if (filters.value.sort_by === column) {
		filters.value.sort_direction = filters.value.sort_direction === 'asc' ? 'desc' : 'asc'
	} else {
		filters.value.sort_by = column
		filters.value.sort_direction = 'asc'
	}
	applyFilters()
}

const applyFilters = () => {
	pagination.value.current_page = 1
	fetchUsers()
}

const clearFilters = () => {
	filters.value = {
		search: '',
		estado: '',
		rol: '',
		sort_by: 'created_at',
		sort_direction: 'desc',
	}
	pagination.value.per_page = 10
	applyFilters()
}

const changePage = (page) => {
	if (page >= 1 && page <= pagination.value.last_page) {
		pagination.value.current_page = page
		fetchUsers()
	}
}

// Métodos CRUD
const fetchUsers = async () => {
	loading.value = true
	error.value = null

	try {
		const params = {
			page: pagination.value.current_page,
			per_page: pagination.value.per_page,
			sort_by: filters.value.sort_by,
			sort_direction: filters.value.sort_direction,
		}

		if (filters.value.search) params.search = filters.value.search
		if (filters.value.estado) params.estado = filters.value.estado
		if (filters.value.rol) params.rol = filters.value.rol

		const response = await api.get('/users', { params })

		if (response.data.success) {
			users.value = response.data.data.data
			pagination.value = {
				current_page: response.data.data.current_page,
				last_page: response.data.data.last_page,
				per_page: response.data.data.per_page,
				total: response.data.data.total,
			}
		}
	} catch (err) {
		console.error('Error al cargar usuarios:', err)
		error.value = 'Error al cargar los usuarios'
	} finally {
		loading.value = false
	}
}

const fetchStatistics = async () => {
	loadingStats.value = true
	try {
		const response = await api.get('/users/statistics')
		if (response.data.success) {
			stats.value = response.data.data
		}
	} catch (err) {
		console.error('Error al cargar estadísticas:', err)
	} finally {
		loadingStats.value = false
	}
}

const toggleStats = async () => {
	showStats.value = !showStats.value

	if (showStats.value) {
		await fetchStatistics()
	}
}

const openCreateModal = () => {
	modalMode.value = 'create'
	form.value = {
		usu_nombre: '',
		email: '',
		password: '',
		usu_rol: '',
		usu_estado: 'Activo',
	}
	formErrors.value = {}
	showModal.value = true
}

const viewUser = async (user) => {
	try {
		const response = await api.get(`/users/${user.id}`)
		if (response.data.success) {
			selectedUser.value = response.data.data
			modalMode.value = 'view'
			showModal.value = true
		}
	} catch (err) {
		console.error('Error al cargar usuario:', err)
		alert('Error al cargar los detalles del usuario')
	}
}

const editUser = async (user) => {
	try {
		const response = await api.get(`/users/${user.id}`)
		if (response.data.success) {
			selectedUser.value = response.data.data
			form.value = {
				usu_nombre: response.data.data.nombre,
				email: response.data.data.email,
				password: '',
				usu_rol: response.data.data.rol,
				usu_estado: response.data.data.estado,
			}
			formErrors.value = {}
			modalMode.value = 'edit'
			showModal.value = true
		}
	} catch (err) {
		console.error('Error al cargar usuario:', err)
		alert('Error al cargar los datos del usuario')
	}
}

const submitForm = async () => {
	loading.value = true
	formErrors.value = {}

	try {
		let response

		if (modalMode.value === 'create') {
			response = await api.post('/users', form.value)
		} else if (modalMode.value === 'edit') {
			response = await api.put(`/users/${selectedUser.value.id}`, form.value)
		}

		if (response.data.success) {
			closeModal()
			await fetchUsers()
			if (showStats.value) await fetchStatistics()
			alert(
				modalMode.value === 'create'
					? 'Usuario creado exitosamente'
					: 'Usuario actualizado exitosamente',
			)
		}
	} catch (err) {
		console.error('Error al guardar usuario:', err)
		if (err.response?.data?.errors) {
			formErrors.value = err.response.data.errors
		} else {
			alert(err.response?.data?.message || 'Error al guardar el usuario')
		}
	} finally {
		loading.value = false
	}
}

const closeModal = () => {
	showModal.value = false
	selectedUser.value = null
	form.value = {
		usu_nombre: '',
		email: '',
		password: '',
		usu_rol: '',
		usu_estado: 'Activo',
	}
	formErrors.value = {}
}

const confirmDelete = (user) => {
	userToDelete.value = user
	showDeleteConfirm.value = true
}

const deleteUser = async () => {
	if (!userToDelete.value) return

	try {
		const response = await api.delete(`/users/${userToDelete.value.id}`)

		if (response.data.success) {
			showDeleteConfirm.value = false
			userToDelete.value = null
			await fetchUsers()
			if (showStats.value) await fetchStatistics()
			alert('Usuario eliminado exitosamente')
		}
	} catch (err) {
		console.error('Error al eliminar usuario:', err)
		alert(err.response?.data?.message || 'Error al eliminar el usuario')
	}
}

// Acciones individuales
const activateUser = async (user) => {
	try {
		const response = await api.put(`/users/${user.id}/activate`)

		if (response.data.success) {
			// Recargar la lista completa para asegurar que los datos estén sincronizados
			await fetchUsers()
			if (showStats.value) await fetchStatistics()
			alert('Usuario activado exitosamente')
		}
	} catch (err) {
		console.error('Error al activar usuario:', err)
		alert(err.response?.data?.message || 'Error al activar el usuario')
	}
}

const deactivateUser = async (user) => {
	if (
		!confirm(
			`¿Estás seguro de desactivar a ${user.nombre}? Se cerrarán todas sus sesiones activas.`,
		)
	)
		return
	try {
		const response = await api.put(`/users/${user.id}/deactivate`)

		if (response.data.success) {
			// Recargar la lista completa para asegurar que los datos estén sincronizados
			await fetchUsers()
			if (showStats.value) await fetchStatistics()
			alert('Usuario desactivado exitosamente')
		}
	} catch (err) {
		console.error('Error al desactivar usuario:', err)
		alert(err.response?.data?.message || 'Error al desactivar el usuario')
	}
}

// Acciones en lote
const bulkActivate = async () => {
	if (selectedUsers.value.length === 0) return

	try {
		const response = await api.post('/users/bulk-action', {
			action: 'activate',
			user_ids: selectedUsers.value,
		})

		if (response.data.success) {
			selectedUsers.value = []
			await fetchUsers()
			if (showStats.value) await fetchStatistics()
			alert(`${response.data.data.affected_count} usuario(s) activado(s) exitosamente`)
		}
	} catch (err) {
		console.error('Error en acción en lote:', err)
		alert(err.response?.data?.message || 'Error al activar usuarios')
	}
}

const bulkDeactivate = async () => {
	if (selectedUsers.value.length === 0) return

	try {
		const response = await api.post('/users/bulk-action', {
			action: 'deactivate',
			user_ids: selectedUsers.value,
		})

		if (response.data.success) {
			selectedUsers.value = []
			await fetchUsers()
			if (showStats.value) await fetchStatistics()
			alert(`${response.data.data.affected_count} usuario(s) desactivado(s) exitosamente`)
		}
	} catch (err) {
		console.error('Error en acción en lote:', err)
		alert(err.response?.data?.message || 'Error al desactivar usuarios')
	}
}

const confirmBulkDelete = () => {
	if (selectedUsers.value.length === 0) return
	showBulkDeleteConfirm.value = true
}

const executeBulkDelete = async () => {
	if (selectedUsers.value.length === 0) return

	try {
		const response = await api.post('/users/bulk-action', {
			action: 'delete',
			user_ids: selectedUsers.value,
		})

		if (response.data.success) {
			showBulkDeleteConfirm.value = false
			selectedUsers.value = []
			await fetchUsers()
			if (showStats.value) await fetchStatistics()
			alert(`${response.data.data.affected_count} usuario(s) eliminado(s) exitosamente`)
		}
	} catch (err) {
		console.error('Error en acción en lote:', err)
		alert(err.response?.data?.message || 'Error al eliminar usuarios')
	}
}

// Lifecycle
onMounted(() => {
	fetchUsers()
})
</script>

<style scoped>
/* Animaciones */
.slide-down-enter-active,
.slide-down-leave-active {
	transition: all 0.3s ease;
	max-height: 500px;
	overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
	max-height: 0;
	opacity: 0;
}

.modal-enter-active,
.modal-leave-active {
	transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
	opacity: 0;
}

.modal-enter-active > div,
.modal-leave-active > div {
	transition: transform 0.3s ease;
}

.modal-enter-from > div,
.modal-leave-to > div {
	transform: scale(0.95);
}
</style>
