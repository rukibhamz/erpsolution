import axios from 'axios';
window.axios = axios;

// SECURITY FIX: Setup CSRF token for Axios
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Setup default headers
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.headers.common['Content-Type'] = 'application/json';

// Add request interceptor for CSRF token
window.axios.interceptors.request.use(function (config) {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token.content;
    }
    return config;
}, function (error) {
    return Promise.reject(error);
});

// Add response interceptor for error handling
window.axios.interceptors.response.use(function (response) {
    return response;
}, function (error) {
    if (error.response?.status === 419) {
        // CSRF token mismatch - reload page
        window.location.reload();
    }
    return Promise.reject(error);
});
