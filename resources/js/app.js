// resources/js/app.js
import { createApp } from 'vue';
import router from './router';
import App from './components/App.vue';
import '../scss/app.scss';

// Create the main Vue app
const app = createApp(App);
app.use(router).mount('#app');

// Check IP access for file upload and show/hide entire navigation
async function checkFileUploadAccess() {
  try {
    const response = await fetch('/api/file/ip-access');
    const data = await response.json();
    
    const mainNav = document.getElementById('main-nav');
    if (mainNav && data.allowed) {
      mainNav.style.display = 'block';
    }
  } catch (error) {
    console.error('Error checking file upload access:', error);
    // In case of error, keep the navigation hidden for security
  }
}

// Set active class on navigation links based on current path
document.addEventListener('DOMContentLoaded', async () => {
  // Check if file upload is allowed for this IP
  await checkFileUploadAccess();
  
  const path = window.location.pathname;
  const passwordLink = document.getElementById('password-link');
  const fileLink = document.getElementById('file-link');
  
  if (path === '/' || path === '/v') {
    passwordLink?.classList.add('router-link-active');
  } else if (path === '/f') {
    fileLink?.classList.add('router-link-active');
  }
});
