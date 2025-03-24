// resources/js/app.js
import { createApp } from 'vue';
import router from './router';
import App from './components/App.vue';
import '../scss/app.scss';

// Create the main Vue app
const app = createApp(App);
app.use(router).mount('#app');

// Set active class on navigation links based on current path
document.addEventListener('DOMContentLoaded', () => {
  const path = window.location.pathname;
  const passwordLink = document.getElementById('password-link');
  const fileLink = document.getElementById('file-link');
  
  if (path === '/' || path === '/v') {
    passwordLink?.classList.add('router-link-active');
  } else if (path === '/f') {
    fileLink?.classList.add('router-link-active');
  }
});
