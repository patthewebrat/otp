// resources/js/router.js
import { createRouter, createWebHistory } from 'vue-router';
import OtpComponent from './components/OtpComponent.vue';

const routes = [
    { path: '/otp', component: OtpComponent },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
