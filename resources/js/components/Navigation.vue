<template>
    <nav v-if="showNavigation" class="main-nav" id="main-nav">
        <div class="nav-container">
            <router-link 
                to="/" 
                class="nav-item" 
                :class="{ 'router-link-active': isPasswordRoute }"
                @click="setActiveRoute('password')"
            >
                Password Sharing
            </router-link>
            <router-link 
                v-if="hasFileUploadAccess" 
                to="/f" 
                class="nav-item" 
                :class="{ 'router-link-active': isFileRoute }"
                @click="setActiveRoute('file')"
            >
                File Sharing
            </router-link>
        </div>
    </nav>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useIPAccess } from '../utils/useIPAccess'

// Reactive state
const activeRoute = ref('password')
const route = useRoute()

// Use IP access utility to check file upload access
const { hasFileUploadAccess, checkFileUploadAccess } = useIPAccess()

// Computed properties
const showNavigation = computed(() => hasFileUploadAccess.value)
const isPasswordRoute = computed(() => 
    route.path === '/' || route.path === '/otp' || route.path === '/v'
)
const isFileRoute = computed(() => route.path === '/f')

const setActiveRoute = (routeType) => {
    activeRoute.value = routeType
}

// Watch for route changes to update active state
watch(() => route.path, (newPath) => {
    if (newPath === '/' || newPath === '/otp' || newPath === '/v') {
        activeRoute.value = 'password'
    } else if (newPath === '/f') {
        activeRoute.value = 'file'
    }
}, { immediate: true })

// Initialize on mount
onMounted(async () => {
    await checkFileUploadAccess()
})

// Expose for debugging if needed
defineExpose({
    hasFileUploadAccess,
    checkFileUploadAccess
})
</script>

<style scoped>
/* Navigation styles moved from app.scss to be component-scoped */
.main-nav {
    background-color: rgba(0, 0, 0, 0.2);
    padding: 1rem;
    margin-bottom: 2rem;
}

.nav-container {
    max-width: 1000px; /* $content-max-width */
    margin: 0 auto;
    display: flex;
    gap: 1rem;
}

.nav-item {
    color: #fff; /* $primary-color */
    text-decoration: none;
    opacity: 0.7; /* $opacity-inactive */
    transition: opacity 0.3s; /* $transition-speed */
    font-size: 1em; /* $font-size-base */
}

.nav-item:hover {
    opacity: 1; /* $opacity-active */
}

.nav-item.router-link-active {
    opacity: 1; /* $opacity-active */
    font-weight: 600;
}
</style>