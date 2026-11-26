/**
 * Инициализация фронтенд-окружения Laravel.
 * CSRF-токен для axios-запросов (необязателен для Inertia, но стандартен).
 */
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
