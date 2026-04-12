<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { Underline } from '@tiptap/extension-underline'
import { TextAlign } from '@tiptap/extension-text-align'
import { TextStyle } from '@tiptap/extension-text-style'
import { Color } from '@tiptap/extension-color'
import { Table } from '@tiptap/extension-table'
import { TableRow } from '@tiptap/extension-table-row'
import { TableHeader } from '@tiptap/extension-table-header'
import { TableCell } from '@tiptap/extension-table-cell'
import { CodeBlockLowlight } from '@tiptap/extension-code-block-lowlight'
import { Highlight } from '@tiptap/extension-highlight'
import { CharacterCount } from '@tiptap/extension-character-count'
import { Image } from '@tiptap/extension-image'
import { createLowlight, common } from 'lowlight'
import api from '@/services/api'

const router = useRouter()
const lowlight = createLowlight(common)

// Estado
const loading = ref(false)
const error = ref(null)
const successMessage = ref(null)
const isFullscreen = ref(false)
const showPreview = ref(false)
const formErrors = ref({})
const portadaPreview = ref(null)
const imagenPreview = ref(null)
const portadaInput = ref(null)
const imagenInput = ref(null)
const editorImageInput = ref(null)
const showColorPicker = ref(false)
const colorPickerPos = ref({ top: 0, left: 0 })

const toggleColorPicker = (e) => {
	const btn = e.currentTarget
	const rect = btn.getBoundingClientRect()
	colorPickerPos.value = {
		top: rect.bottom + 6,
		left: rect.left,
	}
	showColorPicker.value = !showColorPicker.value
}

// Paleta de colores tipo Canva
const colorPalette = [
	'#000000',
	'#434343',
	'#666666',
	'#999999',
	'#b7b7b7',
	'#cccccc',
	'#d9d9d9',
	'#ffffff',
	'#ff0000',
	'#ff4500',
	'#ff9900',
	'#ffff00',
	'#00ff00',
	'#00ffff',
	'#4a90d9',
	'#9900ff',
	'#f4cccc',
	'#fce5cd',
	'#fff2cc',
	'#d9ead3',
	'#d0e0e3',
	'#cfe2f3',
	'#d9d2e9',
	'#ead1dc',
]

const form = ref({
	not_titulo: '',
	not_subtitulo: '',
	not_portada: null,
	not_imagen: null,
	not_video: '',
	not_publicacion: '',
	not_estatus: 'Guardado',
})

const editor = useEditor({
	extensions: [
		StarterKit.configure({ codeBlock: false, underline: false }),
		Underline,
		TextAlign.configure({ types: ['heading', 'paragraph'] }),
		TextStyle,
		Color,
		Highlight.configure({ multicolor: true }),
		Table.configure({ resizable: true }),
		TableRow,
		TableHeader,
		TableCell,
		CodeBlockLowlight.configure({ lowlight }),
		Image,
		CharacterCount,
	],
	content: '',
	editorProps: { attributes: { class: 'tiptap-content' } },
})

// Computed
const wordCount = computed(() => {
	if (!editor.value) return 0
	const text = editor.value.getText().trim()
	return text.length > 0 ? text.split(/\s+/).filter((w) => w.length > 0).length : 0
})
const charCount = computed(() => editor.value?.storage.characterCount.characters() ?? 0)
const videoEmbedUrl = computed(() => {
	const url = form.value.not_video
	if (!url) return null
	const yt = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/)
	if (yt) return `https://www.youtube.com/embed/${yt[1]}`
	const vimeo = url.match(/vimeo\.com\/(\d+)/)
	if (vimeo) return `https://player.vimeo.com/video/${vimeo[1]}`
	return url
})
const previewContent = computed(() => editor.value?.getHTML() ?? '')

const isActive = (name, attrs = {}) => editor.value?.isActive(name, attrs) ?? false

// Fullscreen
const toggleFullscreen = () => {
	isFullscreen.value = !isFullscreen.value
	document.body.style.overflow = isFullscreen.value ? 'hidden' : ''
}
const handleEscKey = (e) => {
	if (e.key === 'Escape') {
		if (isFullscreen.value) toggleFullscreen()
		if (showPreview.value) showPreview.value = false
		if (showColorPicker.value) showColorPicker.value = false
	}
}
document.addEventListener('keydown', handleEscKey)
onBeforeUnmount(() => {
	document.removeEventListener('keydown', handleEscKey)
	document.body.style.overflow = ''
	editor.value?.destroy()
})

// Color
const applyColor = (color) => {
	editor.value?.chain().focus().setColor(color).run()
	showColorPicker.value = false
}

// Imágenes portada/cuerpo
const handlePortadaChange = (e) => {
	const file = e.target.files[0]
	if (!file) return
	form.value.not_portada = file
	const reader = new FileReader()
	reader.onload = (ev) => {
		portadaPreview.value = ev.target.result
	}
	reader.readAsDataURL(file)
}
const handleImagenChange = (e) => {
	const file = e.target.files[0]
	if (!file) return
	form.value.not_imagen = file
	const reader = new FileReader()
	reader.onload = (ev) => {
		imagenPreview.value = ev.target.result
	}
	reader.readAsDataURL(file)
}
const removePortada = () => {
	form.value.not_portada = null
	portadaPreview.value = null
	if (portadaInput.value) portadaInput.value.value = ''
}
const removeImagen = () => {
	form.value.not_imagen = null
	imagenPreview.value = null
	if (imagenInput.value) imagenInput.value.value = ''
}

// Imagen dentro del editor
const insertImage = () => editorImageInput.value?.click()
const handleEditorImageUpload = (e) => {
	const file = e.target.files[0]
	if (!file) return
	const reader = new FileReader()
	reader.onload = (ev) => {
		editor.value?.chain().focus().setImage({ src: ev.target.result }).run()
	}
	reader.readAsDataURL(file)
	e.target.value = ''
}

// Link
const setLink = () => {
	const url = window.prompt('URL del enlace:')
	if (!url) return
	editor.value?.chain().focus().setLink({ href: url, target: '_blank' }).run()
}

// Tabla
const insertTable = () => {
	editor.value?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()
}

// Eliminar tabla o imagen activa
const deleteActiveNode = () => {
	if (editor.value?.isActive('table')) {
		editor.value?.chain().focus().deleteTable().run()
	} else {
		editor.value?.chain().focus().deleteSelection().run()
	}
}

// Añadir párrafo después de tabla/imagen
const addParagraphAfter = () => {
	editor.value?.chain().focus().exitCode().run() ||
		editor.value?.commands.insertContentAt(editor.value.state.doc.content.size, {
			type: 'paragraph',
		})
}

// Validación y submit
const validateForm = () => {
	formErrors.value = {}
	const content = editor.value?.getHTML() ?? ''
	const isEmpty = content === '' || content === '<p></p>'
	if (!form.value.not_titulo.trim()) formErrors.value.not_titulo = ['El título es obligatorio']
	if (isEmpty) formErrors.value.not_descripcion = ['El contenido es obligatorio']
	return Object.keys(formErrors.value).length === 0
}

const handleSubmit = async () => {
	error.value = null
	successMessage.value = null
	if (!validateForm()) {
		error.value = 'Por favor completa todos los campos obligatorios'
		window.scrollTo({ top: 0, behavior: 'smooth' })
		return
	}
	loading.value = true
	try {
		const formData = new FormData()
		formData.append('not_titulo', form.value.not_titulo)
		if (form.value.not_subtitulo) formData.append('not_subtitulo', form.value.not_subtitulo)
		formData.append('not_descripcion', editor.value?.getHTML() ?? '')
		if (form.value.not_video) formData.append('not_video', form.value.not_video)
		if (form.value.not_publicacion)
			formData.append('not_publicacion', form.value.not_publicacion)
		formData.append('not_estatus', form.value.not_estatus)
		if (form.value.not_portada instanceof File)
			formData.append('not_portada', form.value.not_portada)
		if (form.value.not_imagen instanceof File)
			formData.append('not_imagen', form.value.not_imagen)
		const response = await api.post('/noticias', formData, {
			headers: { 'Content-Type': 'multipart/form-data' },
		})
		if (response.data.success) {
			successMessage.value = response.data.message
			setTimeout(() => router.push('/news'), 1500)
		}
	} catch (err) {
		if (err.response?.status === 422) {
			formErrors.value = err.response.data.errors || {}
			error.value = err.response.data.message || 'Error de validación'
		} else {
			error.value = err.response?.data?.message || 'Error al guardar la noticia'
		}
		window.scrollTo({ top: 0, behavior: 'smooth' })
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<div class="min-h-screen bg-[#f4f6f8] p-6 md:p-8">
		<!-- Header -->
		<div
			class="bg-white rounded-2xl shadow-sm border border-gray-200 px-8 py-5 mb-8 flex items-center justify-between"
		>
			<div>
				<h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
					<div class="p-2 bg-indigo-100 rounded-xl">
						<svg
							class="w-6 h-6 text-indigo-600"
							fill="none"
							stroke="currentColor"
							viewBox="0 0 24 24"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"
							/>
						</svg>
					</div>
					Crear Nueva Noticia
				</h1>
				<p class="text-gray-500 text-sm mt-1 ml-14">
					Completa el formulario para publicar una nueva noticia
				</p>
			</div>
			<button
				@click="router.push('/news')"
				class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition-all font-medium text-sm"
			>
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path
						stroke-linecap="round"
						stroke-linejoin="round"
						stroke-width="2"
						d="M15 19l-7-7 7-7"
					/>
				</svg>
				Volver
			</button>
		</div>

		<!-- Alertas -->
		<div
			v-if="error"
			class="mb-8 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-center gap-3 text-red-800 font-medium"
		>
			<svg
				class="w-5 h-5 flex-shrink-0 text-red-500"
				fill="none"
				stroke="currentColor"
				viewBox="0 0 24 24"
			>
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="2"
					d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
				/>
			</svg>
			{{ error }}
		</div>
		<div
			v-if="successMessage"
			class="mb-8 p-4 bg-green-50 border-l-4 border-green-500 rounded-r-xl flex items-center gap-3 text-green-800 font-medium"
		>
			<svg
				class="w-5 h-5 flex-shrink-0 text-green-500"
				fill="none"
				stroke="currentColor"
				viewBox="0 0 24 24"
			>
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="2"
					d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
				/>
			</svg>
			{{ successMessage }}
		</div>

		<!-- Layout -->
		<div class="flex gap-8 items-start">
			<!-- COLUMNA PRINCIPAL -->
			<div class="flex-1 min-w-0 space-y-8">
				<!-- Título y Subtítulo -->
				<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
					<h2 class="text-base font-semibold text-gray-800 mb-6 flex items-center gap-2">
						<svg
							class="w-4 h-4 text-indigo-500"
							fill="none"
							stroke="currentColor"
							viewBox="0 0 24 24"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
							/>
						</svg>
						Información General
					</h2>
					<div class="mb-6">
						<label class="block text-sm font-semibold text-gray-700 mb-2"
							>Título <span class="text-red-500">*</span></label
						>
						<input
							v-model="form.not_titulo"
							type="text"
							maxlength="100"
							placeholder="Título de la noticia"
							class="w-full px-4 py-3 border-2 rounded-xl outline-none transition-all text-gray-900 placeholder-gray-400"
							:class="
								formErrors.not_titulo
									? 'border-red-400'
									: 'border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100'
							"
						/>
						<div class="flex justify-between mt-2">
							<p v-if="formErrors.not_titulo" class="text-red-500 text-sm">
								{{ formErrors.not_titulo[0] }}
							</p>
							<p class="text-xs text-gray-400 ml-auto">
								{{ form.not_titulo.length }}/100
							</p>
						</div>
					</div>
					<div>
						<label class="block text-sm font-semibold text-gray-700 mb-2"
							>Subtítulo
							<span class="text-gray-400 font-normal">(opcional)</span></label
						>
						<input
							v-model="form.not_subtitulo"
							type="text"
							maxlength="300"
							placeholder="Subtítulo o resumen breve"
							class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 outline-none transition-all text-gray-900 placeholder-gray-400"
						/>
						<p class="text-xs text-gray-400 mt-2 text-right">
							{{ form.not_subtitulo.length }}/300
						</p>
					</div>
				</div>

				<!-- Editor -->
				<div
					class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden"
					:class="{ 'fixed inset-0 z-50 rounded-none m-0 flex flex-col': isFullscreen }"
				>
					<!-- Header editor -->
					<div
						class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50 flex-shrink-0"
					>
						<div class="flex items-center gap-3">
							<h2 class="text-base font-semibold text-gray-800">
								Contenido <span class="text-red-500">*</span>
							</h2>
							<span class="text-xs text-gray-400 bg-gray-100 px-2.5 py-1 rounded-full"
								>{{ wordCount }} palabras · {{ charCount }} chars</span
							>
						</div>
						<button
							type="button"
							@click="toggleFullscreen"
							class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all"
							:title="isFullscreen ? 'Salir (Esc)' : 'Pantalla completa'"
						>
							<svg
								v-if="!isFullscreen"
								class="w-5 h-5"
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"
								/>
							</svg>
							<svg
								v-else
								class="w-5 h-5"
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24"
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

					<!-- Toolbar -->
					<div
						class="flex flex-wrap items-center gap-1 px-5 py-3 border-b border-gray-200 bg-gray-50 overflow-x-auto flex-shrink-0"
					>
						<!-- Encabezados -->
						<div class="flex items-center gap-0.5 pr-3 border-r border-gray-200 mr-2">
							<button
								type="button"
								@click="editor?.chain().focus().toggleHeading({ level: 1 }).run()"
								:class="
									isActive('heading', { level: 1 })
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="px-2 py-1.5 rounded text-xs font-bold transition-all"
							>
								H1
							</button>
							<button
								type="button"
								@click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
								:class="
									isActive('heading', { level: 2 })
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="px-2 py-1.5 rounded text-xs font-bold transition-all"
							>
								H2
							</button>
							<button
								type="button"
								@click="editor?.chain().focus().toggleHeading({ level: 3 }).run()"
								:class="
									isActive('heading', { level: 3 })
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="px-2 py-1.5 rounded text-xs font-bold transition-all"
							>
								H3
							</button>
						</div>

						<!-- Formato -->
						<div class="flex items-center gap-0.5 pr-3 border-r border-gray-200 mr-2">
							<button
								type="button"
								@click="editor?.chain().focus().toggleBold().run()"
								:class="
									isActive('bold')
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="w-8 h-8 rounded flex items-center justify-center font-bold text-sm transition-all"
								title="Negrita (Ctrl+B)"
							>
								B
							</button>
							<button
								type="button"
								@click="editor?.chain().focus().toggleItalic().run()"
								:class="
									isActive('italic')
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="w-8 h-8 rounded flex items-center justify-center italic text-sm transition-all"
								title="Cursiva (Ctrl+I)"
							>
								I
							</button>
							<button
								type="button"
								@click="editor?.chain().focus().toggleUnderline().run()"
								:class="
									isActive('underline')
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="w-8 h-8 rounded flex items-center justify-center underline text-sm transition-all"
								title="Subrayado (Ctrl+U)"
							>
								U
							</button>
							<button
								type="button"
								@click="editor?.chain().focus().toggleStrike().run()"
								:class="
									isActive('strike')
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="w-8 h-8 rounded flex items-center justify-center line-through text-sm transition-all"
								title="Tachado"
							>
								S
							</button>
						</div>

						<!-- Color de texto (paleta tipo Canva) -->
						<div class="relative pr-3 border-r border-gray-200 mr-2">
							<button
								type="button"
								@click="toggleColorPicker"
								class="w-8 h-8 rounded flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-all"
								title="Color de texto"
							>
								<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
									<path
										d="M11 2L5.5 17h2.25l1.12-3h6.25l1.12 3h2.25L13 2h-2zm-1.38 10L12 5.67 14.38 12H9.62z"
									/>
								</svg>
							</button>
							<!-- Paleta desplegable — usa Teleport para evitar quedar atrapado en overflow -->
							<Teleport to="body">
								<div
									v-if="showColorPicker"
									class="fixed z-[9999] bg-white border border-gray-200 rounded-xl shadow-2xl p-3 w-52"
									:style="{
										top: colorPickerPos.top + 'px',
										left: colorPickerPos.left + 'px',
									}"
								>
									<p class="text-xs font-semibold text-gray-500 mb-2">
										Color de texto
									</p>
									<div class="grid grid-cols-8 gap-1 mb-3">
										<button
											v-for="color in colorPalette"
											:key="color"
											type="button"
											@click="applyColor(color)"
											class="w-5 h-5 rounded-sm border border-gray-200 hover:scale-125 transition-transform"
											:style="{ backgroundColor: color }"
											:title="color"
										></button>
									</div>
									<div class="border-t border-gray-100 pt-2">
										<p class="text-xs text-gray-400 mb-1.5">Personalizado</p>
										<label class="flex items-center gap-2 cursor-pointer">
											<input
												type="color"
												class="w-8 h-8 rounded cursor-pointer border-0 bg-transparent"
												@input="(e) => applyColor(e.target.value)"
											/>
											<span class="text-xs text-gray-500">Elegir color</span>
										</label>
									</div>
								</div>
							</Teleport>
						</div>

						<!-- Alineación -->
						<div class="flex items-center gap-0.5 pr-3 border-r border-gray-200 mr-2">
							<button
								type="button"
								@click="editor?.chain().focus().setTextAlign('left').run()"
								:class="
									isActive({ textAlign: 'left' })
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="p-1.5 rounded transition-all"
								title="Izquierda"
							>
								<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
									<path
										d="M15 15H3v2h12v-2zm0-8H3v2h12V7zM3 13h18v-2H3v2zm0 8h18v-2H3v2zM3 3v2h18V3H3z"
									/>
								</svg>
							</button>
							<button
								type="button"
								@click="editor?.chain().focus().setTextAlign('center').run()"
								:class="
									isActive({ textAlign: 'center' })
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="p-1.5 rounded transition-all"
								title="Centro"
							>
								<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
									<path
										d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"
									/>
								</svg>
							</button>
							<button
								type="button"
								@click="editor?.chain().focus().setTextAlign('right').run()"
								:class="
									isActive({ textAlign: 'right' })
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="p-1.5 rounded transition-all"
								title="Derecha"
							>
								<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
									<path
										d="M3 21h18v-2H3v2zm6-4h12v-2H9v2zm-6-4h18v-2H3v2zm6-4h12V7H9v2zM3 3v2h18V3H3z"
									/>
								</svg>
							</button>
							<button
								type="button"
								@click="editor?.chain().focus().setTextAlign('justify').run()"
								:class="
									isActive({ textAlign: 'justify' })
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="p-1.5 rounded transition-all"
								title="Justificado"
							>
								<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
									<path
										d="M3 21h18v-2H3v2zm0-4h18v-2H3v2zm0-4h18v-2H3v2zm0-4h18V7H3v2zm0-6v2h18V3H3z"
									/>
								</svg>
							</button>
						</div>

						<!-- Listas -->
						<div class="flex items-center gap-0.5 pr-3 border-r border-gray-200 mr-2">
							<button
								type="button"
								@click="editor?.chain().focus().toggleBulletList().run()"
								:class="
									isActive('bulletList')
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="p-1.5 rounded transition-all"
								title="Lista viñetas"
							>
								<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
									<path
										d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"
									/>
								</svg>
							</button>
							<button
								type="button"
								@click="editor?.chain().focus().toggleOrderedList().run()"
								:class="
									isActive('orderedList')
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="p-1.5 rounded transition-all"
								title="Lista numerada"
							>
								<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
									<path
										d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"
									/>
								</svg>
							</button>
							<button
								type="button"
								@click="editor?.chain().focus().toggleBlockquote().run()"
								:class="
									isActive('blockquote')
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="p-1.5 rounded transition-all"
								title="Cita"
							>
								<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
									<path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z" />
								</svg>
							</button>
						</div>

						<!-- Link, imagen, tabla -->
						<div class="flex items-center gap-0.5 pr-3 border-r border-gray-200 mr-2">
							<button
								type="button"
								@click="setLink"
								:class="
									isActive('link')
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="p-1.5 rounded transition-all"
								title="Insertar enlace"
							>
								<svg
									class="w-4 h-4"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
									/>
								</svg>
							</button>
							<button
								type="button"
								@click="insertImage"
								class="p-1.5 rounded text-gray-600 hover:bg-gray-100 transition-all"
								title="Insertar imagen desde archivo"
							>
								<svg
									class="w-4 h-4"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
									/>
								</svg>
							</button>
							<button
								type="button"
								@click="insertTable"
								class="p-1.5 rounded text-gray-600 hover:bg-gray-100 transition-all"
								title="Insertar tabla"
							>
								<svg
									class="w-4 h-4"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M3 10h18M3 14h18M10 3v18M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6z"
									/>
								</svg>
							</button>
						</div>

						<!-- Código y limpiar -->
						<div class="flex items-center gap-0.5 pr-3 border-r border-gray-200 mr-2">
							<button
								type="button"
								@click="editor?.chain().focus().toggleCodeBlock().run()"
								:class="
									isActive('codeBlock')
										? 'bg-indigo-100 text-indigo-700'
										: 'text-gray-600 hover:bg-gray-100'
								"
								class="p-1.5 rounded transition-all"
								title="Bloque de código"
							>
								<svg
									class="w-4 h-4"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"
									/>
								</svg>
							</button>
							<button
								type="button"
								@click="editor?.chain().focus().clearNodes().unsetAllMarks().run()"
								class="p-1.5 rounded text-gray-600 hover:bg-gray-100 transition-all"
								title="Limpiar formato"
							>
								<svg
									class="w-4 h-4"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
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

						<!-- Acciones sobre imagen/tabla seleccionada -->
						<div
							v-if="isActive('image') || isActive('table')"
							class="flex items-center gap-1 ml-1 px-2 py-1 bg-red-50 border border-red-200 rounded-lg"
						>
							<span class="text-xs text-red-600 font-medium mr-1"
								>{{ isActive('table') ? 'Tabla' : 'Imagen' }} seleccionada:</span
							>
							<button
								type="button"
								@click="deleteActiveNode"
								class="flex items-center gap-1 px-2 py-1 text-xs text-red-700 hover:bg-red-100 rounded transition-all font-medium"
							>
								<svg
									class="w-3.5 h-3.5"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
									/>
								</svg>
								Eliminar
							</button>
							<button
								type="button"
								@click="
									editor
										?.chain()
										.focus()
										.insertContentAt(editor.state.selection.to + 1, {
											type: 'paragraph',
										})
										.run()
								"
								class="flex items-center gap-1 px-2 py-1 text-xs text-indigo-700 hover:bg-indigo-100 rounded transition-all font-medium"
							>
								<svg
									class="w-3.5 h-3.5"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M12 4v16m8-8H4"
									/>
								</svg>
								Añadir párrafo después
							</button>
						</div>
					</div>

					<!-- Área del editor -->
					<div
						class="tiptap-wrapper"
						:class="{ 'flex-1 overflow-y-auto': isFullscreen }"
						@click="showColorPicker = false"
					>
						<input
							ref="editorImageInput"
							type="file"
							accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
							@change="handleEditorImageUpload"
							class="hidden"
						/>
						<EditorContent :editor="editor" />
					</div>
					<p v-if="formErrors.not_descripcion" class="px-6 pb-3 text-red-500 text-sm">
						{{ formErrors.not_descripcion[0] }}
					</p>
					<div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex-shrink-0">
						<p class="text-xs text-gray-400">
							<strong class="text-gray-500">Atajos:</strong> Ctrl+B (Negrita) · Ctrl+I
							(Cursiva) · Ctrl+U (Subrayado)
						</p>
					</div>
				</div>

				<!-- Video -->
				<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
					<h2 class="text-base font-semibold text-gray-800 mb-5 flex items-center gap-2">
						<svg
							class="w-4 h-4 text-red-500"
							fill="none"
							stroke="currentColor"
							viewBox="0 0 24 24"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
							/>
						</svg>
						Video <span class="text-gray-400 font-normal text-sm ml-1">(opcional)</span>
					</h2>
					<input
						v-model="form.not_video"
						type="url"
						placeholder="https://www.youtube.com/watch?v=..."
						class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 outline-none transition-all text-gray-900 placeholder-gray-400"
					/>
					<p class="text-xs text-gray-400 mt-2">Compatible con YouTube y Vimeo</p>
					<div
						v-if="videoEmbedUrl"
						class="mt-5 rounded-2xl overflow-hidden border border-gray-200 shadow-sm aspect-video bg-black"
					>
						<iframe
							:src="videoEmbedUrl"
							class="w-full h-full"
							frameborder="0"
							allowfullscreen
						></iframe>
					</div>
				</div>
			</div>

			<!-- SIDEBAR -->
			<div class="w-80 flex-shrink-0 space-y-6">
				<!-- Publicar -->
				<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
					<div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5">
						<h3 class="text-sm font-semibold text-white flex items-center gap-2">
							<svg
								class="w-4 h-4"
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
								/>
							</svg>
							Publicación
						</h3>
					</div>
					<div class="p-6 space-y-5">
						<div>
							<label
								class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2"
								>Estado</label
							>
							<select
								v-model="form.not_estatus"
								class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-xl focus:border-indigo-500 outline-none transition-all text-sm text-gray-900 bg-white appearance-none"
								style="
									background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27%3e%3cpolyline points=%276 9 12 15 18 9%27%3e%3c/polyline%3e%3c/svg%3e');
									background-repeat: no-repeat;
									background-position: right 0.75rem center;
									background-size: 1.25em;
								"
							>
								<option value="Guardado">Guardado (Borrador)</option>
								<option value="Publicado">Publicado</option>
							</select>
						</div>
						<div>
							<label
								class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2"
								>Fecha de publicación</label
							>
							<input
								v-model="form.not_publicacion"
								type="datetime-local"
								class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-xl focus:border-indigo-500 outline-none transition-all text-sm text-gray-900"
							/>
							<p class="text-xs text-gray-400 mt-1.5">Vacío = publicar ahora</p>
						</div>
						<div class="pt-2 space-y-3 border-t border-gray-100">
							<!-- Vista previa -->
							<button
								type="button"
								@click="showPreview = true"
								class="w-full px-4 py-2.5 border-2 border-indigo-200 text-indigo-600 font-medium rounded-xl hover:bg-indigo-50 transition-all text-sm flex items-center justify-center gap-2"
							>
								<svg
									class="w-4 h-4"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
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
								Vista previa
							</button>
							<button
								type="button"
								@click="handleSubmit"
								:disabled="loading"
								class="w-full px-4 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg disabled:opacity-50 flex items-center justify-center gap-2"
							>
								<svg
									v-if="loading"
									class="w-4 h-4 animate-spin"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
									/>
								</svg>
								<svg
									v-else
									class="w-4 h-4"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M5 13l4 4L19 7"
									/>
								</svg>
								{{ loading ? 'Guardando...' : 'Crear Noticia' }}
							</button>
							<button
								type="button"
								@click="router.push('/news')"
								:disabled="loading"
								class="w-full px-4 py-2.5 border-2 border-gray-200 text-gray-600 font-medium rounded-xl hover:bg-gray-50 transition-all text-sm disabled:opacity-50"
							>
								Cancelar
							</button>
						</div>
					</div>
				</div>

				<!-- Portada -->
				<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
					<div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
						<h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
							<svg
								class="w-4 h-4 text-indigo-500"
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"
								/>
							</svg>
							Imagen de Portada
						</h3>
					</div>
					<div class="p-6">
						<input
							ref="portadaInput"
							type="file"
							accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
							@change="handlePortadaChange"
							class="hidden"
						/>
						<div v-if="portadaPreview" class="relative group mb-4">
							<img
								:src="portadaPreview"
								alt="Portada"
								class="w-full h-44 object-cover rounded-xl border border-gray-200"
							/>
							<button
								type="button"
								@click="removePortada"
								class="absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600 shadow-lg"
							>
								<svg
									class="w-4 h-4"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
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
						<button
							type="button"
							@click="portadaInput?.click()"
							class="w-full py-4 border-2 border-dashed rounded-xl text-sm font-medium transition-all flex flex-col items-center gap-2"
							:class="
								portadaPreview
									? 'border-gray-200 text-gray-500 hover:border-indigo-300 hover:text-indigo-600'
									: 'border-indigo-300 text-indigo-600 bg-indigo-50 hover:bg-indigo-100'
							"
						>
							<svg
								class="w-5 h-5"
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
								/>
							</svg>
							{{ portadaPreview ? 'Cambiar portada' : 'Subir portada' }}
						</button>
						<p class="text-xs text-gray-400 mt-2.5 text-center">
							JPG, PNG, WEBP · Máx. 4MB · Rec. 1200×630px
						</p>
					</div>
				</div>

				<!-- Imagen del cuerpo -->
				<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
					<div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
						<h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
							<svg
								class="w-4 h-4 text-purple-500"
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
								/>
							</svg>
							Imagen del Cuerpo
							<span class="text-xs text-gray-400 font-normal ml-auto"
								>(opcional)</span
							>
						</h3>
					</div>
					<div class="p-6">
						<input
							ref="imagenInput"
							type="file"
							accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
							@change="handleImagenChange"
							class="hidden"
						/>
						<div v-if="imagenPreview" class="relative group mb-4">
							<img
								:src="imagenPreview"
								alt="Imagen"
								class="w-full h-44 object-cover rounded-xl border border-gray-200"
							/>
							<button
								type="button"
								@click="removeImagen"
								class="absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600 shadow-lg"
							>
								<svg
									class="w-4 h-4"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
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
						<button
							type="button"
							@click="imagenInput?.click()"
							class="w-full py-4 border-2 border-dashed rounded-xl text-sm font-medium transition-all flex flex-col items-center gap-2"
							:class="
								imagenPreview
									? 'border-gray-200 text-gray-500 hover:border-purple-300 hover:text-purple-600'
									: 'border-purple-300 text-purple-600 bg-purple-50 hover:bg-purple-100'
							"
						>
							<svg
								class="w-5 h-5"
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
								/>
							</svg>
							{{ imagenPreview ? 'Cambiar imagen' : 'Subir imagen' }}
						</button>
						<p class="text-xs text-gray-400 mt-2.5 text-center">
							JPG, PNG, WEBP · Máx. 4MB
						</p>
					</div>
				</div>
			</div>
		</div>

		<!-- ===== MODAL VISTA PREVIA ===== -->
		<div
			v-if="showPreview"
			class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-start justify-center p-6 overflow-y-auto"
			@click.self="showPreview = false"
		>
			<div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl my-6 overflow-hidden">
				<!-- Header modal -->
				<div
					class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-gray-50"
				>
					<div class="flex items-center gap-3">
						<div class="p-2 bg-indigo-100 rounded-xl">
							<svg
								class="w-5 h-5 text-indigo-600"
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24"
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
						</div>
						<div>
							<h3 class="font-bold text-gray-900 text-lg">Vista Previa</h3>
							<p class="text-xs text-gray-500">Así se verá la noticia publicada</p>
						</div>
					</div>
					<button
						@click="showPreview = false"
						class="p-2 hover:bg-gray-200 rounded-xl transition-all"
					>
						<svg
							class="w-5 h-5 text-gray-600"
							fill="none"
							stroke="currentColor"
							viewBox="0 0 24 24"
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

				<!-- Contenido de la vista previa -->
				<div class="overflow-y-auto max-h-[75vh]">
					<!-- Portada -->
					<div v-if="portadaPreview" class="w-full h-72 overflow-hidden">
						<img
							:src="portadaPreview"
							alt="Portada"
							class="w-full h-full object-cover"
						/>
					</div>
					<div
						v-else
						class="w-full h-48 bg-gradient-to-br from-indigo-50 to-purple-50 flex items-center justify-center"
					>
						<div class="text-center text-gray-400">
							<svg
								class="w-12 h-12 mx-auto mb-2 opacity-40"
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="1.5"
									d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"
								/>
							</svg>
							<p class="text-sm">Sin portada</p>
						</div>
					</div>

					<div class="px-10 py-8 space-y-5">
						<!-- Estado y fecha -->
						<div class="flex items-center gap-3 flex-wrap">
							<span
								class="px-3 py-1 text-xs font-bold uppercase rounded-full border"
								:class="
									form.not_estatus === 'Publicado'
										? 'bg-green-100 text-green-700 border-green-200'
										: 'bg-yellow-100 text-yellow-700 border-yellow-200'
								"
							>
								{{ form.not_estatus }}
							</span>
							<span v-if="form.not_publicacion" class="text-xs text-gray-400">
								{{
									new Date(form.not_publicacion).toLocaleString('es-MX', {
										year: 'numeric',
										month: 'long',
										day: 'numeric',
										hour: '2-digit',
										minute: '2-digit',
									})
								}}
							</span>
						</div>

						<!-- Título -->
						<div>
							<h1 class="text-3xl font-bold text-gray-900 leading-tight">
								{{ form.not_titulo || 'Sin título' }}
							</h1>
							<p
								v-if="form.not_subtitulo"
								class="text-lg text-gray-500 mt-2 leading-relaxed"
							>
								{{ form.not_subtitulo }}
							</p>
						</div>

						<!-- Divisor -->
						<hr class="border-gray-200" />

						<!-- Contenido -->
						<div
							class="preview-content"
							v-html="
								previewContent ||
								'<p class=\'text-gray-400 italic\'>El contenido aparecerá aquí...</p>'
							"
						></div>

						<!-- Imagen del cuerpo -->
						<div
							v-if="imagenPreview"
							class="rounded-2xl overflow-hidden border border-gray-200 shadow-sm"
						>
							<img
								:src="imagenPreview"
								alt="Imagen del cuerpo"
								class="w-full object-cover max-h-80"
							/>
						</div>

						<!-- Video -->
						<div
							v-if="videoEmbedUrl"
							class="rounded-2xl overflow-hidden border border-gray-200 shadow-sm aspect-video bg-black"
						>
							<iframe
								:src="videoEmbedUrl"
								class="w-full h-full"
								frameborder="0"
								allowfullscreen
							></iframe>
						</div>
					</div>
				</div>

				<!-- Footer modal -->
				<div class="px-8 py-5 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
					<button
						@click="showPreview = false"
						class="px-6 py-2.5 border-2 border-gray-200 text-gray-600 font-medium rounded-xl hover:bg-gray-100 transition-all text-sm"
					>
						Cerrar
					</button>
					<button
						@click="
							() => {
								showPreview = false
								handleSubmit()
							}
						"
						:disabled="loading"
						class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all text-sm shadow-lg disabled:opacity-50"
					>
						{{ loading ? 'Guardando...' : 'Crear Noticia' }}
					</button>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped>
.tiptap-wrapper {
	padding: 1.5rem 2rem;
	min-height: 440px;
}

/* Editor */
:deep(.ProseMirror) {
	outline: none;
	min-height: 400px;
	font-size: 0.9375rem;
	line-height: 1.8;
	color: #1f2937;
}
:deep(.ProseMirror strong) {
	font-weight: 700;
}
:deep(.ProseMirror em) {
	font-style: italic;
}
:deep(.ProseMirror u) {
	text-decoration: underline;
}
:deep(.ProseMirror s) {
	text-decoration: line-through;
}
:deep(.ProseMirror a) {
	color: #4f46e5;
	text-decoration: underline;
	cursor: pointer;
}
:deep(.ProseMirror p) {
	margin-bottom: 0.875rem;
}
:deep(.ProseMirror p:last-child) {
	margin-bottom: 0;
}
:deep(.ProseMirror h1) {
	font-size: 1.875rem;
	font-weight: 700;
	margin: 1.75rem 0 0.875rem;
	color: #111827;
	line-height: 1.3;
}
:deep(.ProseMirror h2) {
	font-size: 1.5rem;
	font-weight: 700;
	margin: 1.5rem 0 0.75rem;
	color: #111827;
}
:deep(.ProseMirror h3) {
	font-size: 1.25rem;
	font-weight: 600;
	margin: 1.25rem 0 0.625rem;
	color: #1f2937;
}
:deep(.ProseMirror ul) {
	list-style-type: disc;
	padding-left: 1.75rem;
	margin-bottom: 0.875rem;
}
:deep(.ProseMirror ol) {
	list-style-type: decimal;
	padding-left: 1.75rem;
	margin-bottom: 0.875rem;
}
:deep(.ProseMirror li) {
	margin-bottom: 0.375rem;
}
:deep(.ProseMirror blockquote) {
	border-left: 4px solid #6366f1;
	padding-left: 1.25rem;
	color: #6b7280;
	font-style: italic;
	margin: 1.25rem 0;
	background: #f8f9ff;
	padding-top: 0.5rem;
	padding-bottom: 0.5rem;
	border-radius: 0 0.5rem 0.5rem 0;
}
:deep(.ProseMirror pre) {
	background: #1e1e2e;
	color: #cdd6f4;
	padding: 1.25rem 1.5rem;
	border-radius: 0.75rem;
	overflow-x: auto;
	font-size: 0.875rem;
	margin: 1.25rem 0;
}
:deep(.ProseMirror code) {
	background: #f1f5f9;
	color: #7c3aed;
	padding: 0.15rem 0.4rem;
	border-radius: 0.3rem;
	font-size: 0.875em;
}
:deep(.ProseMirror pre code) {
	background: transparent;
	color: inherit;
	padding: 0;
}
:deep(.ProseMirror img) {
	max-width: 100%;
	border-radius: 0.75rem;
	margin: 1rem 0;
	display: block;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
	cursor: pointer;
}
:deep(.ProseMirror img.ProseMirror-selectednode) {
	outline: 3px solid #6366f1;
	border-radius: 0.75rem;
}
:deep(.ProseMirror table) {
	border-collapse: collapse;
	width: 100%;
	margin: 1.25rem 0;
}
:deep(.ProseMirror td, .ProseMirror th) {
	border: 1px solid #e5e7eb;
	padding: 0.625rem 0.875rem;
	text-align: left;
	min-width: 80px;
}
:deep(.ProseMirror th) {
	background: #f9fafb;
	font-weight: 600;
	color: #374151;
}
:deep(.ProseMirror tr:hover td) {
	background: #f9fafb;
}
:deep(.ProseMirror .selectedCell) {
	background: #ede9fe !important;
}
:deep(.ProseMirror p.is-editor-empty:first-child::before) {
	content: 'Escribe el contenido de la noticia aquí...';
	color: #9ca3af;
	pointer-events: none;
	float: left;
	height: 0;
}

/* Vista previa */
.preview-content :deep(strong) {
	font-weight: 700;
}
.preview-content :deep(em) {
	font-style: italic;
}
.preview-content :deep(u) {
	text-decoration: underline;
}
.preview-content :deep(s) {
	text-decoration: line-through;
}
.preview-content :deep(p) {
	margin-bottom: 1rem;
	color: #374151;
	line-height: 1.8;
}
.preview-content :deep(h1) {
	font-size: 1.75rem;
	font-weight: 700;
	margin: 1.5rem 0 0.75rem;
	color: #111827;
}
.preview-content :deep(h2) {
	font-size: 1.4rem;
	font-weight: 700;
	margin: 1.25rem 0 0.625rem;
	color: #111827;
}
.preview-content :deep(h3) {
	font-size: 1.2rem;
	font-weight: 600;
	margin: 1rem 0 0.5rem;
	color: #1f2937;
}
.preview-content :deep(ul) {
	list-style-type: disc;
	padding-left: 1.5rem;
	margin-bottom: 1rem;
}
.preview-content :deep(ol) {
	list-style-type: decimal;
	padding-left: 1.5rem;
	margin-bottom: 1rem;
}
.preview-content :deep(li) {
	margin-bottom: 0.375rem;
	color: #374151;
}
.preview-content :deep(blockquote) {
	border-left: 4px solid #6366f1;
	padding: 0.75rem 1.25rem;
	color: #6b7280;
	font-style: italic;
	background: #f8f9ff;
	border-radius: 0 0.5rem 0.5rem 0;
	margin: 1rem 0;
}
.preview-content :deep(pre) {
	background: #1e1e2e;
	color: #cdd6f4;
	padding: 1rem 1.25rem;
	border-radius: 0.75rem;
	overflow-x: auto;
	font-size: 0.875rem;
	margin: 1rem 0;
}
.preview-content :deep(code) {
	background: #f1f5f9;
	color: #7c3aed;
	padding: 0.15rem 0.4rem;
	border-radius: 0.3rem;
	font-size: 0.875em;
}
.preview-content :deep(img) {
	max-width: 100%;
	border-radius: 0.75rem;
	margin: 1rem 0;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.preview-content :deep(a) {
	color: #4f46e5;
	text-decoration: underline;
}
.preview-content :deep(table) {
	border-collapse: collapse;
	width: 100%;
	margin: 1rem 0;
}
.preview-content :deep(td, th) {
	border: 1px solid #e5e7eb;
	padding: 0.625rem 0.875rem;
}
.preview-content :deep(th) {
	background: #f9fafb;
	font-weight: 600;
}
</style>
