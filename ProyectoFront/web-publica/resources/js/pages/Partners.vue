<template>
  <div class="partners-page">
    <!-- 
      Componente Hero reutilizable. Se le pasan título y subtítulo.
      Ideal para mantener consistencia en todas las páginas.
    -->
    <Hero title="Partners" subtitle="Nuestros aliados estratégicos" />

    <section class="partners-content container">
      <h2 class="section-title">Aliados que confían en nosotros</h2>
      <p class="section-subtitle">
        Trabajamos con los líderes de la industria para ofrecerte las mejores soluciones.
      </p>

      <!-- 
        Estado de carga: muestra 8 esqueletos (skeleton) animados.
        Es una buena práctica porque mejora la percepción de rendimiento.
      -->
      <div v-if="loading" class="partners-grid">
        <div v-for="n in 8" :key="n" class="partner-card skeleton">
          <div class="partner-logo"></div>
        </div>
      </div>

      <!-- Estado de error: muestra el mensaje de error -->
      <div v-else-if="error" class="partners-state partners-state-error">
        {{ error }}
      </div>

      <!-- Estado vacío: cuando no hay partners -->
      <div v-else-if="!partners.length" class="partners-state">
        No hay partners publicados por el momento.
      </div>

      <!-- 
        Grid principal de partners.
        Cada tarjeta tiene una animación de entrada escalonada (animationDelay)
        y al hacer clic abre el modal.
      -->
      <div v-else class="partners-grid">
        <div
          v-for="(partner, index) in partners"
          :key="partner.id"
          class="partner-card"
          :style="{ animationDelay: `${index * 0.05}s` }"
          @click="openModal(partner)"
        >
          <div class="partner-logo">
            <!-- 
              Carga lazy de imágenes – mejora el rendimiento.
              El overlay aparece al hover y muestra nombre + descripción.
            -->
            <img :src="partner.logo" :alt="partner.name" loading="lazy" />
            <div class="partner-overlay">
              <h3>{{ partner.name }}</h3>
              <p>{{ partner.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 
      Modal con transición. Se activa con modalVisible.
      @click.self cierra el modal si se hace clic en el fondo oscuro.
    -->
    <transition name="modal-fade">
      <div v-if="modalVisible" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <button class="modal-close" @click="closeModal">&times;</button>
          <div class="modal-body">
            <div class="modal-logo">
              <img :src="selectedPartner?.logo" :alt="selectedPartner?.name" />
            </div>
            <h2>{{ selectedPartner?.name }}</h2>
            <p>{{ selectedPartner?.description }}</p>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
// Imports necesarios
import { onBeforeUnmount, onMounted, ref } from 'vue'
import Hero from '@/components/Hero.vue'
import axios from '@/axios'          // Instancia configurada de axios (interceptores, baseURL, etc.)
import logger from '@/utils/logger' // Logger propio para centralizar el manejo de errores/logs

// Estado reactivo
const partners = ref([])       // Lista de partners
const loading = ref(true)      // Indicador de carga
const error = ref('')          // Mensaje de error si falla la petición
const modalVisible = ref(false) // Controla visibilidad del modal
const selectedPartner = ref(null) // Partner actualmente seleccionado para el modal

// 
// La API devuelve rutas relativas de imágenes (ej: "partners/logo.png").
// Se construye la URL base eliminando "/api" del final de VITE_API_BASE_URL.
// Asume que los archivos están en /storage/...
//
const backendBaseUrl = import.meta.env.VITE_API_BASE_URL.replace(/\/api$/, '')

// Función para obtener la URL completa del logo
const getPartnerLogoUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path // Ya es URL absoluta
  return `${backendBaseUrl}/storage/${path}` // Construye URL pública
}

// Mapea el objeto que viene de la API (con nombres tipo par_id, par_nombre, etc.)
// a la estructura que usa el frontend (id, name, logo, description)
const mapPartner = (partner) => ({
  id: partner.par_id,
  name: partner.par_nombre,
  logo: getPartnerLogoUrl(partner.par_logo),
  description: partner.par_descripcion?.trim() || 'Aliado estratégico de Arsite.',
})

// Función principal que obtiene los partners desde el backend
const fetchPartners = async () => {
  loading.value = true
  error.value = ''

  try {
    // Llama al endpoint público /partners/public con ordenamiento por par_orden (ascendente)
    const response = await axios.get('/partners/public', {
      params: {
        sort_by: 'par_orden',
        sort_direction: 'asc',
      },
    })

    // Verifica la estructura esperada: { success: true, data: [...] }
    if (response.data?.success) {
      partners.value = (response.data.data || []).map(mapPartner)
    } else {
      error.value = 'No fue posible cargar los partners.'
    }
  } catch (err) {
    // Registra el error en el logger (útil para tracking en producción)
    logger.error('Error al cargar partners públicos', err)
    error.value = 'Error al cargar los partners.'
  } finally {
    loading.value = false
  }
}

// Abre el modal con la información del partner seleccionado
// además bloquea el scroll del body para evitar que el fondo se mueva
const openModal = (partner) => {
  selectedPartner.value = partner
  modalVisible.value = true
  document.body.style.overflow = 'hidden' // Evita scroll mientras modal está abierto
}

// Cierra el modal y restaura el scroll del body
const closeModal = () => {
  modalVisible.value = false
  selectedPartner.value = null
  document.body.style.overflow = ''
}

// Ciclo de vida: al montar el componente, carga los datos
onMounted(fetchPartners)

// Limpieza: si el componente se desmonta mientras el modal estaba abierto,
// restaura el scroll por si acaso (medida de seguridad)
onBeforeUnmount(() => {
  document.body.style.overflow = ''
})
</script>

<style scoped>
/* 
  Estilos con scoped – solo afectan a este componente.
  Se usa diseño responsive, grid flexible y animaciones CSS.
*/

.partners-page {
  min-height: 100vh;
}

.partners-content {
  padding: 80px 0;
  background: #ffffff;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #65B3CA;
  text-align: center;
  margin-bottom: 0.5rem;
}

.section-subtitle {
  text-align: center;
  color: #4a5568;
  font-size: 1.2rem;
  margin-bottom: 3rem;
}

/* Estados de vacío / error */
.partners-state {
  text-align: center;
  padding: 2rem;
  color: #4a5568;
}
.partners-state-error {
  color: #b91c1c;
}

/* Grid responsivo: se ajusta automáticamente */
.partners-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}

/* Tarjeta de cada partner */
.partner-card {
  background: transparent;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0;
  text-align: center;
  /* Animación de entrada: fade + zoom + desplazamiento */
  opacity: 0;
  transform: scale(0.9) translateY(30px);
  animation: zoomFadeIn 0.6s ease forwards;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.partner-card:hover {
  transform: translateY(-5px) scale(1.02);
}
/* Esqueletos: sin animación hover y con shimmer */
.partner-card.skeleton {
  cursor: default;
  opacity: 1;
  transform: none;
  animation: none;
}

/* Contenedor del logo (cuadrado) */
.partner-logo {
  position: relative;
  width: 100%;
  aspect-ratio: 1/1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  border: 1px solid #eaeaea;
  overflow: hidden;
  transition: all 0.3s ease;
}
/* Skeleton animation */
.skeleton .partner-logo {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 37%, #f0f0f0 63%);
  background-size: 400% 100%;
  animation: shimmer 1.4s infinite;
}
.partner-logo img {
  width: 80%;
  height: 80%;
  object-fit: contain;
  transition: transform 0.4s ease;
}
.partner-card:hover .partner-logo {
  box-shadow: 0 15px 30px rgba(101, 179, 202, 0.2);
  border-color: #65B3CA;
}
.partner-card:hover .partner-logo img {
  transform: scale(1.1);
}

/* Overlay que aparece al hover – muestra nombre y descripción */
.partner-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(4px);
  color: white;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 1rem;
  opacity: 0;
  transition: opacity 0.3s ease;
  text-align: center;
  border-radius: 16px;
}
.partner-card:hover .partner-overlay {
  opacity: 1;
}
.partner-overlay h3 {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #65B3CA;
}
.partner-overlay p {
  font-size: 0.85rem;
  line-height: 1.4;
  margin: 0;
  color: #f0f0f0;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 5;
  overflow: hidden;
}

/* Animaciones clave */
@keyframes zoomFadeIn {
  0% {
    opacity: 0;
    transform: scale(0.9) translateY(30px);
  }
  100% {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}
@keyframes shimmer {
  0% {
    background-position: 100% 0;
  }
  100% {
    background-position: -100% 0;
  }
}

/* 
  Nota: el autor definió delays para hasta 20 elementos, 
  pero también se aplica un animationDelay inline.
  Las reglas nth-child podrían entrar en conflicto.
  Se podría unificar usando solo el style dinámico.
*/
.partner-card:nth-child(1) { animation-delay: 0.05s; }
.partner-card:nth-child(2) { animation-delay: 0.1s; }
/* ... (se omiten los demás por brevedad, pero siguen el mismo patrón) */

/* MODAL */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(5px);
}
.modal-content {
  background: white;
  border-radius: 20px;
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
  position: relative;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: modalSlideUp 0.3s ease;
}
.modal-close {
  position: absolute;
  top: 15px;
  right: 20px;
  font-size: 2rem;
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
  line-height: 1;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.2s;
  z-index: 10;
}
.modal-close:hover {
  background: #f0f0f0;
  color: #000;
  transform: scale(1.1);
}
.modal-body {
  padding: 2.5rem 2rem;
  text-align: center;
}
.modal-logo {
  width: 180px;
  height: 180px;
  margin: 0 auto 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  border-radius: 50%;
  padding: 1.5rem;
  border: 1px solid #eaeaea;
}
.modal-logo img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}
.modal-body h2 {
  font-size: 2rem;
  color: #1a202c;
  margin-bottom: 1rem;
}
.modal-body p {
  color: #4a5568;
  line-height: 1.8;
  font-size: 1.1rem;
}

/* Transiciones para el modal */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
@keyframes modalSlideUp {
  from {
    transform: translateY(50px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Responsive */
@media (max-width: 768px) {
  .partners-content { padding: 60px 0; }
  .section-title { font-size: 2rem; }
  .section-subtitle { font-size: 1rem; }
  .modal-logo { width: 140px; height: 140px; }
  .modal-body h2 { font-size: 1.6rem; }
  .modal-body p { font-size: 1rem; }
}
@media (max-width: 480px) {
  .partners-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>