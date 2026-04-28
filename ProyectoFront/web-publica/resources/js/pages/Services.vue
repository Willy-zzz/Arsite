<template>
  <div class="services-page">
    <Hero
      title="Servicios"
      subtitle="Soluciones integrales en tecnología, seguridad y conectividad"
    />

    <section class="services-content container">
      <h2 class="section-title">Nuestros Servicios</h2>
      <p class="section-subtitle">
        Acompañamos tu negocio en cada etapa con soluciones a medida
      </p>

      <div class="benefits-section">
        <h3 class="benefits-title">
          Beneficios que nuestros clientes obtienen:
        </h3>
        <div class="benefits-grid">
          <div v-for="beneficio in beneficios" :key="beneficio.id" class="benefit-item">
            <span class="benefit-number">{{ beneficio.id }}</span>
            <p class="benefit-text">{{ beneficio.texto }}</p>
          </div>
        </div>
      </div>

      <div v-if="loading" class="services-sections">
        <section
          v-for="n in 4"
          :key="`skeleton-${n}`"
          class="service-section service-section-skeleton"
          :class="{ reverse: n % 2 === 0 }"
        >
          <div class="service-image-wrapper">
            <div class="service-image-skeleton skeleton-block"></div>
          </div>

          <div class="service-content">
            <div class="service-title-skeleton skeleton-block"></div>
            <div class="service-text-skeleton">
              <div class="skeleton-line skeleton-block"></div>
              <div class="skeleton-line skeleton-block"></div>
              <div class="skeleton-line skeleton-block"></div>
              <div class="skeleton-line skeleton-block short"></div>
            </div>
          </div>
        </section>
      </div>

      <div v-else-if="error" class="services-state services-state-error">
        {{ error }}
      </div>

      <div v-else-if="!services.length" class="services-state">
        No hay servicios publicados por el momento.
      </div>

      <div v-else class="services-sections">
        <section
          v-for="(service, index) in services"
          :key="service.id"
          class="service-section"
          :class="{ reverse: index % 2 !== 0 }"
        >
          <div class="service-image-wrapper">
            <img :src="service.image" :alt="service.title" loading="lazy" />
          </div>

          <div class="service-content">
            <h3>{{ service.title }}</h3>
            <p style="white-space: pre-line;">
              {{ service.description }}
            </p>
          </div>
        </section>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import Hero from '@/components/Hero.vue'
import axios from '@/axios'
import logger from '@/utils/logger'

const services = ref([])
const loading = ref(true)
const error = ref('')

const backendBaseUrl = import.meta.env.VITE_API_BASE_URL.replace(/\/api$/, '')

const getServiceImageUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${backendBaseUrl}/storage/${path}`
}

const mapService = (service) => ({
  id: service.ser_id,
  title: service.ser_titulo,
  description: service.ser_descripcion,
  image: getServiceImageUrl(service.ser_imagen),
})

const fetchServices = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await axios.get('/servicios/public', {
      params: {
        sort_by: 'ser_orden',
        sort_direction: 'asc',
      },
    })

    if (response.data?.success) {
      services.value = (response.data.data || []).map(mapService)
    } else {
      error.value = 'No fue posible cargar los servicios.'
    }
  } catch (err) {
    logger.error('Error al cargar servicios publicos', err)
    error.value = 'Error al cargar los servicios.'
  } finally {
    loading.value = false
  }
}

const beneficios = [
  { id: '01', texto: 'Alineación de la tecnología con los objetivos del negocio' },
  { id: '02', texto: 'Apoyo importante para la toma de decisiones' },
  { id: '03', texto: 'Aumento sustancial en la calidad en los principales procesos de la empresa' },
  { id: '04', texto: 'Incremento en rentabilidad' },
  { id: '05', texto: 'Mejora de los canales de comunicación con clientes y proveedores' },
  { id: '06', texto: 'Reducción de los ciclos de desarrollo de sus productos y servicios' },
  { id: '07', texto: 'Reducción de tiempos y costos' },
  { id: '08', texto: 'Respuesta rápida a las necesidades de los clientes' },
  { id: '09', texto: 'Ventajas competitivas respecto a la competencia' },
]

onMounted(fetchServices)
</script>

<style scoped>
.services-page {
  min-height: 100vh;
}

.services-content {
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

.services-state {
  text-align: center;
  padding: 2rem;
  color: #4a5568;
}

.services-state-error {
  color: #b91c1c;
}

.benefits-section {
  margin-bottom: 60px;
  background: #f8fafc;
  padding: 40px;
  border-radius: 20px;
}

.benefits-title {
  font-size: 2rem;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 2rem;
  text-align: center;
}

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.benefit-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  background: white;
  padding: 1.2rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

.benefit-number {
  font-size: 2rem;
  font-weight: 700;
  color: #65B3CA;
  min-width: 50px;
  text-align: right;
}

.benefit-text {
  color: #4a5568;
  line-height: 1.6;
}

.services-sections {
  display: flex;
  flex-direction: column;
  gap: 100px;
  margin-top: 3rem;
}

.service-section {
  display: flex;
  align-items: center;
  gap: 60px;
}

.service-section.reverse {
  flex-direction: row-reverse;
}

.service-section-skeleton {
  align-items: stretch;
}

.service-image-wrapper {
  flex: 1;
}

.service-image-wrapper img {
  width: 100%;
  border-radius: 20px;
  object-fit: cover;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.service-image-skeleton {
  width: 100%;
  min-height: 320px;
  border-radius: 20px;
}

.service-content {
  flex: 1;
}

.service-content h3 {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: #65B3CA;
}

.service-content p {
  font-size: 1.05rem;
  color: #4a5568;
  line-height: 1.7;
  margin-bottom: 1.5rem;
}

.service-title-skeleton {
  height: 2.25rem;
  width: min(280px, 70%);
  border-radius: 999px;
  margin-bottom: 1.5rem;
}

.service-text-skeleton {
  display: grid;
  gap: 0.85rem;
}

.skeleton-line {
  height: 1rem;
  width: 100%;
  border-radius: 999px;
}

.skeleton-line.short {
  width: 72%;
}

.skeleton-block {
  background: linear-gradient(
    90deg,
    #f0f0f0 25%,
    #e2e8f0 37%,
    #f0f0f0 63%
  );
  background-size: 400% 100%;
  animation: shimmer 1.4s infinite;
}

@keyframes shimmer {
  0% {
    background-position: 100% 0;
  }

  100% {
    background-position: -100% 0;
  }
}

@media (max-width: 992px) {
  .benefits-grid {
    grid-template-columns: 1fr;
  }

  .service-section,
  .service-section.reverse {
    flex-direction: column;
  }

  .service-section {
    gap: 30px;
  }
}
</style>
