<template>
  <div class="carousel-container" v-if="loading">
    <div class="carousel skeleton-carousel">
      <div class="skeleton-image"></div>
      <div class="overlay"></div>
      <div class="slide-content skeleton-content">
        <div class="skeleton-title"></div>
        <div class="skeleton-description"></div>
        <div class="skeleton-button"></div>
      </div>
      <div class="skeleton-prev"></div>
      <div class="skeleton-next"></div>
      <div class="dots skeleton-dots">
        <div class="dot skeleton-dot"></div>
        <div class="dot skeleton-dot active"></div>
        <div class="dot skeleton-dot"></div>
      </div>
    </div>
  </div>

  <div class="carousel-container" v-else-if="error">
    <div class="carousel-error">Error al cargar: {{ error }}</div>
  </div>

  <div class="carousel-container" v-else-if="slides.length">
    <div class="carousel">
      <div class="slide-wrapper">
        <img :src="slides[current].image" :key="current" alt="Slide image" />
        <div class="overlay"></div>
        <div class="slide-content">
          <h2 class="slide-title" v-html="slides[current].title"></h2>
          <p class="slide-description" v-html="slides[current].description"></p>
          <a
            :href="slides[current].link.url"
            class="slide-button"
            :target="slides[current].link.external ? '_blank' : '_self'"
          >
            {{ slides[current].link.text }}
            <span class="button-arrow">→</span>
          </a>
        </div>
      </div>

      <button class="prev" @click="prev">‹</button>
      <button class="next" @click="next">›</button>

      <div class="dots">
        <button
          v-for="(slide, index) in slides"
          :key="index"
          class="dot"
          :class="{ active: current === index }"
          @click="goToSlide(index)"
          :aria-label="'Ir al slide ' + (index + 1)"
        ></button>
      </div>
    </div>
  </div>

  <div class="carousel-container" v-else>
    <div class="carousel-empty">No hay Sliders disponibles</div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from '@/axios'
import logger from '@/utils/logger'

const slides = ref([])
const current = ref(0)
const loading = ref(true)
const error = ref(null)

const backendBaseUrl = import.meta.env.VITE_API_BASE_URL.replace(/\/api$/, '')

const mapBannerToSlide = (banner) => ({
  image: banner.ban_imagen?.startsWith('http')
    ? banner.ban_imagen
    : `${backendBaseUrl}/storage/${banner.ban_imagen}`,
  title: banner.ban_titulo,
  description: banner.ban_subtitulo,
  link: {
    text: banner.ban_texto_boton || 'Saber más',
    url: banner.ban_enlace_boton || '#',
    external: banner.ban_enlace_boton?.startsWith('http') ?? false
  }
})

const fetchSlides = async () => {
  try {
    const response = await axios.get('/banners/public')
    slides.value = (response.data.success && response.data.data.length)
      ? response.data.data.map(mapBannerToSlide)
      : []
  } catch (err) {
    logger.error('Error al cargar banners públicos', err)
    error.value = err.message || 'Error de conexión'
  } finally {
    loading.value = false
  }
}

// ─── FIX 4: resetInterval en cada interacción ────────────────────────────────
let intervalId = null

const resetInterval = () => {
  if (intervalId) clearInterval(intervalId)
  intervalId = setInterval(autoNext, 8000)
}

const autoNext = () => {
  if (slides.value.length) {
    current.value = (current.value + 1) % slides.value.length
  }
}

const next = () => {
  autoNext()
  resetInterval()
}

const prev = () => {
  if (slides.value.length) {
    current.value = (current.value - 1 + slides.value.length) % slides.value.length
  }
  resetInterval()
}

const goToSlide = (index) => {
  current.value = index
  resetInterval()
}
// ─────────────────────────────────────────────────────────────────────────────

onMounted(() => {
  fetchSlides()
  intervalId = setInterval(autoNext, 8000)
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
})
</script>

<style scoped>
/* ==================================================================
   BASE
   ================================================================== */

/* FIX 5: El contenedor usa width 100% y deja que el padre maneje el padding/margen */
.carousel-container {
  width: 100%;
  max-width: 1440px;
  margin: 0 auto;
  /* Si necesitás los márgenes laterales, ponelos aquí con padding en vez de
     calc(100% - 240px), así el componente no asume que hay un sidebar */
  padding: 0 120px;
  box-sizing: border-box;
}

.carousel {
  position: relative;
  height: 80vh;
  min-height: 400px;
  overflow: hidden;
  border-radius: 12px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
}

.slide-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
}

/* FIX 2: object-fit cambiado de contain → cover para evitar bandas laterales */
.carousel img {
  width: 100%;
  height: 100%;
  object-fit: contain;  /* cover */
  object-position: center center;
  /* background-color ya no es necesario como "relleno" porque cover siempre cubre */
  background-color: #78c2e7;
  transition: opacity 0.5s ease;
  display: block;
}

.overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    to right,
    rgba(0, 0, 0, 0.6) 0%,
    rgba(0, 0, 0, 0.4) 50%,
    rgba(0, 0, 0, 0.2) 100%
  );
  z-index: 1;
}

/* ==================================================================
   CONTENIDO DEL SLIDE
   ================================================================== */

.slide-content {
  position: absolute;
  top: 50%;
  left: 80px;
  transform: translateY(-50%);
  color: white;
  z-index: 2;
  max-width: 600px;
  text-align: left;
  animation: fadeInUp 0.8s ease-out;

  /* FIX 1 (base): flex column para que título, descripción y botón
     siempre sean un bloque unido, sin riesgo de separarse */
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.slide-title {
  font-size: 3.5rem;
  font-weight: 800;
  margin-bottom: 1rem;
  line-height: 1.2;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.slide-description {
  font-size: 1.25rem;
  margin-bottom: 1.5rem;
  line-height: 1.5;
  opacity: 0.95;
  max-width: 500px;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
}

.slide-button {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: #3b82f6;
  color: white;
  padding: 12px 28px;
  border-radius: 50px;
  text-decoration: none;
  font-size: 1.1rem;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
  border: 2px solid transparent;
  /* FIX 1: aseguramos que el botón no sea absolute ni se salga del flujo */
  position: relative;
  flex-shrink: 0;
}

.slide-button:hover {
  background: #2563eb;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
  border-color: white;
}

.button-arrow {
  font-size: 1.2rem;
  transition: transform 0.3s ease;
}

.slide-button:hover .button-arrow {
  transform: translateX(5px);
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px) translateY(-50%);
  }
  to {
    opacity: 1;
    transform: translateY(0) translateY(-50%);
  }
}

/* ==================================================================
   NAVEGACIÓN (prev/next)
   ================================================================== */

button.prev,
button.next {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  font-size: 40px;
  background: rgba(0, 0, 0, 0.5);
  color: white;
  border: none;
  padding: 0;
  cursor: pointer;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  z-index: 10;
}

button.prev:hover,
button.next:hover {
  background: rgba(0, 0, 0, 0.8);
  transform: translateY(-50%) scale(1.1);
}

.prev { left: 20px; }
.next { right: 20px; }

/* ==================================================================
   DOTS
   ================================================================== */

.dots {
  position: absolute;
  bottom: 30px;
  left: 0;
  right: 0;
  display: flex;
  justify-content: center;
  gap: 12px;
  z-index: 10;
}

.dot {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.5);
  border: 2px solid transparent;
  cursor: pointer;
  transition: all 0.3s ease;
  padding: 0;
}

.dot:hover {
  background: rgba(255, 255, 255, 0.8);
  transform: scale(1.2);
}

.dot.active {
  background: white;
  transform: scale(1.3);
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

/* ==================================================================
   SKELETON
   ================================================================== */

.skeleton-carousel {
  position: relative;
  background: #f0f2f5;
  height: 80vh;
  min-height: 400px;
  border-radius: 12px;
  overflow: hidden;
}

.skeleton-image {
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, #e0e0e0 25%, #f5f5f5 50%, #e0e0e0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-title {
  width: 70%;
  height: 48px;
  background: linear-gradient(90deg, #ccc 25%, #e0e0e0 50%, #ccc 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.skeleton-description {
  width: 90%;
  height: 60px;
  background: linear-gradient(90deg, #ccc 25%, #e0e0e0 50%, #ccc 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.skeleton-button {
  width: 160px;
  height: 48px;
  background: linear-gradient(90deg, #ccc 25%, #e0e0e0 50%, #ccc 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 50px;
}

.skeleton-content {
  position: absolute;
  top: 50%;
  left: 80px;
  transform: translateY(-50%);
  z-index: 2;
  max-width: 600px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.skeleton-prev,
.skeleton-next {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 60px;
  height: 60px;
  background: linear-gradient(90deg, #b0b0b0 25%, #d0d0d0 50%, #b0b0b0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 50%;
  z-index: 10;
}

.skeleton-prev { left: 20px; }
.skeleton-next { right: 20px; }

.skeleton-dots { bottom: 30px; }

.skeleton-dot {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: linear-gradient(90deg, #aaa 25%, #ccc 50%, #aaa 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-dot.active {
  background: white;
  animation: none;
  opacity: 0.8;
}

@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ==================================================================
   RESPONSIVE
   ================================================================== */

@media (max-width: 1600px) {
  .carousel-container {
    padding: 0 100px;
  }
}

@media (max-width: 1200px) {
  .carousel-container {
    padding: 0 50px;
  }
  .carousel,
  .skeleton-carousel {
    height: 70vh;
  }
  .slide-content,
  .skeleton-content {
    left: 60px;
    max-width: 500px;
  }
  .slide-title {
    font-size: 3rem;
  }
}

@media (max-width: 992px) {
  .slide-content,
  .skeleton-content {
    left: 50px;
    max-width: 450px;
  }
  .slide-title {
    font-size: 2.5rem;
  }
  .slide-description {
    font-size: 1.1rem;
  }
}

/* =============================================
   📱 Móvil ≤768px
   ============================================= */
@media (max-width: 768px) {

  .carousel-container {
    padding: 0 20px;
  }

  .carousel,
  .skeleton-carousel {
    height: 70vh;
    min-height: 320px;
    /* Con contain, el fondo expuesto arriba/abajo lo oscurecemos aquí
       para que el overlay tenga algo uniforme sobre qué actuar */
    background-color: #1a1a2e;
  }

  /* El overlay en móvil cubre TODO el carousel (incluyendo las bandas
     del contain) con una capa oscura uniforme + gradiente al fondo
     para dar contraste al texto */
  .overlay {
    background:
      linear-gradient(
        to top,
        rgba(0, 0, 0, 0.85) 0%,
        rgba(0, 0, 0, 0.5) 40%,
        rgba(0, 0, 0, 0.35) 100%
      );
  }

  .slide-content,
  .skeleton-content {
    position: absolute;
    top: auto;
    bottom: 54px;
    left: 0;
    right: 0;
    transform: none;

    padding: 2rem 1.4rem 1rem;
    max-width: 100%;
    text-align: center;
    align-items: center;

    /* Sin background propio: el overlay ya oscurece todo el carrusel */
    background: none;
  }

  .slide-title {
    font-size: 1.9rem;
    margin-bottom: 1.5rem;
    line-height: 1.15;
  }

  .slide-description {
    font-size: 0.95rem;
    margin-bottom: 0.9rem;
    max-width: 100%;
    line-height: 1.4;
  }

  .slide-button {
    padding: 9px 22px;
    font-size: 0.95rem;
  }

  button.prev,
  button.next,
  .skeleton-prev,
  .skeleton-next {
    width: 44px;
    height: 44px;
    font-size: 28px;
  }

  .prev, .skeleton-prev { left: 10px; }
  .next, .skeleton-next { right: 10px; }

  .dots,
  .skeleton-dots {
    bottom: 18px;
  }
}

/* =============================================
   📱 Muy pequeño ≤480px
   ============================================= */
@media (max-width: 480px) {

  .carousel-container {
    padding: 0 10px;
  }

  .carousel,
  .skeleton-carousel {
    height: 65vh;
    min-height: 280px;
  }

  .slide-content,
  .skeleton-content {
    padding: 1.5rem 0.8rem 0.8rem;
    bottom: 48px;
  }

  .slide-title {
    font-size: 1.6rem;
    line-height: 1.6;
    position: relative;
    top: -60px
  }

  .slide-description {
    font-size: 0.85rem;
    line-height: 1.35;
    position: relative;
    top: -60px
  }

  .slide-button {
    padding: 8px 18px;
    font-size: 0.85rem;
    position: relative;
    top: -50px
  }

  button.prev,
  button.next,
  .skeleton-prev,
  .skeleton-next {
    width: 36px;
    height: 36px;
    font-size: 24px;
  }
}
</style>

<style>
.slide-title span.highlight {
  color: #fbbf24;
  font-weight: 800;
  text-shadow: 0 0 10px rgba(251, 191, 36, 0.3);
}
</style>