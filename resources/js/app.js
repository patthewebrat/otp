// resources/js/app.js
import { createApp } from 'vue';
import OtpComponent from './components/OtpComponent.vue';
import router from './router';
import '../scss/app.scss'

createApp(OtpComponent).use(router).mount('#app');
