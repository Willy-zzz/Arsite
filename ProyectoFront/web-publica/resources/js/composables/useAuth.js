import { ref, computed, watch } from 'vue'

/**
 * Claves centralizadas (evita hardcodear en todo el proyecto)
 */
const STORAGE_KEYS = {
    USER: 'support_user',
    TOKEN: 'support_token'
}

/**
 * Parse seguro para evitar que un JSON corrupto rompa la app
 */
function safeParse(value) {
    try {
        return JSON.parse(value)
    } catch (e) {
        console.warn('Auth: JSON corrupto en storage', e)
        return null
    }
}

/**
 * Estado global (reactivo)
 * Se inicializa desde localStorage si existe
 */
const user = ref(safeParse(localStorage.getItem(STORAGE_KEYS.USER)))
const token = ref(localStorage.getItem(STORAGE_KEYS.TOKEN))

/**
 * Estado derivado: si hay token → sesión activa
 */
const isLoggedIn = computed(() => !!token.value)

/**
 * LOGIN
 * @param {Object} userData - datos del usuario
 * @param {String} authToken - token (ej: Sanctum)
 * @param {Boolean} remember - persistir sesión
 */
function login(userData, authToken, remember = true) {
    user.value = userData
    token.value = authToken

    if (remember) {
        localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(userData))
        localStorage.setItem(STORAGE_KEYS.TOKEN, authToken)
    } else {
        // Limpia cualquier sesión previa persistida
        localStorage.removeItem(STORAGE_KEYS.USER)
        localStorage.removeItem(STORAGE_KEYS.TOKEN)
    }
}

/**
 * LOGOUT
 * Limpia todo (estado + storage)
 */
function logout() {
    user.value = null
    token.value = null

    localStorage.removeItem(STORAGE_KEYS.USER)
    localStorage.removeItem(STORAGE_KEYS.TOKEN)
}

/**
 * UPDATE USER
 * Permite actualizar datos parciales del usuario
 */
function updateUser(newData) {
    if (!user.value) return

    user.value = { ...user.value, ...newData }

    // Solo persiste si hay sesión guardada
    if (token.value) {
        localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(user.value))
    }
}

/**
 * Sync entre pestañas (PRO TIP)
 * Si haces logout en otra pestaña → aquí también se actualiza
 */
window.addEventListener('storage', (event) => {
    if (event.key === STORAGE_KEYS.TOKEN) {
        token.value = event.newValue
    }

    if (event.key === STORAGE_KEYS.USER) {
        user.value = safeParse(event.newValue)
    }
})

/**
 * Watcher para debug o side effects
 */
watch(token, (newToken) => {
    console.log('Auth token cambió:', newToken)
})

/**
 * Export del composable
 */
export function useAuth() {
    return {
        user,
        token,
        isLoggedIn,
        login,
        logout,
        updateUser
    }
}