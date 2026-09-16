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
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    margin-bottom: 2rem;
}

.nav-container {
    max-width: 1000px; /* $content-max-width */
    margin: 0 auto;
    display: flex;
    gap: 2rem;
}

.nav-item {
    color: #fff; /* $primary-color */
    font-family: 'Montserrat', sans-serif; /* $font-family-heading */
    font-weight: 500;
    text-decoration: none;
    opacity: 0.7; /* $opacity-inactive */
    transition: opacity 0.3s, color 0.3s, border-color 0.3s; /* $transition-speed */
    font-size: 1em; /* $font-size-base */
    padding-bottom: 0.75rem;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px; /* sit the active underline over the hairline */
}

.nav-item:hover {
    opacity: 1; /* $opacity-active */
    color: #F27AA5; /* $ind-pink */
}

.nav-item.router-link-active {
    opacity: 1; /* $opacity-active */
    color: #F27AA5; /* $ind-pink */
    border-bottom-color: #F27AA5; /* $ind-pink */
}
</style>