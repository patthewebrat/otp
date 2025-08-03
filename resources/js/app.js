// resources/js/app.js
import { createApp } from 'vue';
import router from './router';
import App from './components/App.vue';
import '../scss/app.scss';

// Create the main Vue app
const app = createApp(App);
app.use(router).mount('#app');
