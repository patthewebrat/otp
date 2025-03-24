// resources/js/router.js
import { createRouter, createWebHistory } from 'vue-router';
import OtpComponent from './components/OtpComponent.vue';
import FileShareComponent from './components/FileShareComponent.vue';

const routes = [
    { path: '/', component: OtpComponent },
    { path: '/otp', component: OtpComponent },
    { path: '/v', component: OtpComponent },
    { path: '/f', component: FileShareComponent },
    // Catch all route to redirect to home
    { path: '/:catchAll(.*)', redirect: '/' }
];

const router = createRouter({
    history: createWebHistory('/'),
    routes,
});

export default router;
