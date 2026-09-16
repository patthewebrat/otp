<template>
    <div class="app-container">
        <header class="app-header">
            <router-link to="/" class="app-logo">
                <img :src="logoUrl" alt="Indulge Digital" />
            </router-link>
        </header>
        <Navigation />
        <router-view></router-view>
    </div>
</template>

<script setup>
import Navigation from './Navigation.vue'

// Served from public/, so bind it rather than let Vite resolve it as a module.
const logoUrl = '/images/brand/indulge-logo-white.svg'
</script>

<style scoped>
.app-header {
    margin-bottom: 2rem;
}

.app-logo {
    display: inline-block;
}

.app-logo img {
    display: block;
    height: 32px;
    width: auto;
}
</style>