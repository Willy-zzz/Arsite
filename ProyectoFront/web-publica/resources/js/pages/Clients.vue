<template>
  <!-- Página de clientes con efecto de aparición al hacer scroll -->
  <div class="clients-page">
    <!-- Componente Hero reutilizable con título y subtítulo -->
    <Hero title="Clientes" subtitle="Empresas que confían en nosotros" />

    <section class="clients-content container">
      <h2 class="section-title">Nuestros clientes</h2>
      <p class="section-subtitle">
        A lo largo de los años hemos colaborado con empresas de diversos sectores.
      </p>

      <!-- Skeleton loading: muestra 8 tarjetas esqueleto mientras se cargan los datos -->
      <div v-if="loading" class="clients-grid">
        <div v-for="n in 8" :key="n" class="client-card skeleton"></div>
      </div>

      <!-- Mensaje de error si la petición falla -->
      <div v-else-if="error" class="clients-state clients-state-error">
        {{ error }}
      </div>

      <!-- Mensaje cuando no hay clientes publicados -->
      <div v-else-if="!clients.length" class="clients-state">
        No hay clientes publicados por el momento.
      </div>

      <!-- Grid real de clientes, con animación individual mediante style -->
      <div v-else class="clients-grid">
        <div
          v-for="(client, index) in clients"
          :key="client.id"
          ref="cards"
          class="client-card"
          :style="{ animationDelay: `${index * 0.05}s` }"
        >
          <div class="client-logo">
            <!-- Carga lazy de imágenes para mejorar rendimiento -->
            <img
              :src="client.logo"
              :alt="client.name"
              loading="lazy"
            />
          </div>
          <h3 class="client-name">{{ client.name }}</h3>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
// Importa hooks de Vue, componente Hero y la instancia de axios configurada
import { onMounted, ref, nextTick } from 'vue'
import Hero from '@/components/Hero.vue'
import axios from '@/axios'

// Estado reactivo
const clients = ref([])      // Lista de clientes
const loading = ref(true)    // Indicador de carga
const error = ref('')        // Mensaje de error
const cards = ref([])        // Referencias a las tarjetas DOM para el IntersectionObserver

// URL base del backend (eliminando '/api' si existe)
const backendBaseUrl = import.meta.env.VITE_API_BASE_URL.replace(/\/api$/, '')

// Construye la URL completa del logo
const getClientLogoUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${backendBaseUrl}/storage/${path}`
}

// Transforma un cliente del formato de la API al formato interno del componente
const mapClient = (client) => ({
  id: client.cli_id,
  name: client.cli_nombre,
  logo: getClientLogoUrl(client.cli_logo),
})

// Obtiene los clientes públicos desde el backend
const fetchClients = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await axios.get('/clientes/public', {
      params: {
        sort_by: 'cli_orden',
        sort_direction: 'asc',
      },
    })

    if (response.data?.success) {
      clients.value = (response.data.data || []).map(mapClient)
    } else {
      error.value = 'No fue posible cargar los clientes.'
    }
  } catch (err) {
    console.error(err)
    error.value = 'Error al cargar los clientes.'
  } finally {
    loading.value = false

    // Una vez que el DOM se ha actualizado, inicializa el observer
    nextTick(() => {
      initObserver()
    })
  }
}

// Configura un IntersectionObserver para agregar la clase 'show' cuando cada tarjeta entra en pantalla
const initObserver = () => {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('show')   // Activa la animación CSS
          observer.unobserve(entry.target)     // Deja de observar esa tarjeta
        }
      })
    },
    { threshold: 0.1 }   // Se dispara cuando al menos el 10% del elemento es visible
  )

  // Observa cada tarjeta referenciada
  cards.value.forEach((el) => {
    if (el) observer.observe(el)
  })
}

// Al montar el componente, se obtienen los clientes
onMounted(fetchClients)
</script>

<style scoped>
/* Estilos exclusivos del componente (scoped) */

.clients-page {
  min-height: 100vh;
}

.clients-content {
  padding: 80px 0;
  background: #ffffff;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #65B3CA;
  text-align: center;
}

.section-subtitle {
  text-align: center;
  color: #4a5568;
  margin-bottom: 3rem;
}

.clients-state {
  text-align: center;
  padding: 2rem;
}

.clients-state-error {
  color: #b91c1c;
}

/* Grid responsivo de tarjetas */
.clients-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 2rem;
}

/* Estilo base de cada tarjeta: oculta inicialmente (opacity 0, desplazada) */
.client-card {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem 1rem;
  text-align: center;
  border: 1px solid #eee;
  box-shadow: 0 4px 10px rgba(0,0,0,0.03);
  opacity: 0;
  transform: translateY(30px);
}

/* Cuando el IntersectionObserver agrega la clase 'show', se ejecuta la animación */
.client-card.show {
  animation: fadeInUp 0.6s ease forwards;
}

/* Efecto hover: levanta la tarjeta y cambia la sombra */
.client-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(101, 179, 202, 0.1);
}

/* Contenedor del logo */
.client-logo {
  width: 100px;
  height: 100px;
  margin: auto;
}

.client-logo img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

/* Nombre del cliente */
.client-name {
  margin-top: 1rem;
  font-weight: 600;
}

/* Animación de entrada: desde abajo y transparente hasta su estado final */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Estilo para las tarjetas esqueleto (skeleton) durante la carga */
.skeleton {
  height: 150px;
  background: linear-gradient(
    90deg,
    #f0f0f0 25%,
    #e0e0e0 37%,
    #f0f0f0 63%
  );
  background-size: 400% 100%;
  animation: shimmer 1.4s infinite;
}

/* Animación de brillo (shimmer) para el skeleton */
@keyframes shimmer {
  0% { background-position: 100% 0 }
  100% { background-position: -100% 0 }
}

/* Responsive para móviles: dos columnas en lugar de auto-ajuste */
@media (max-width: 480px) {
  .clients-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>