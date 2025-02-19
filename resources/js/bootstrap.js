import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Enable detailed Pusher logging
Pusher.logToConsole = true;

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    encrypted: true,
    enabledTransports: ['ws', 'wss'],
    wsHost: undefined,
    wsPort: undefined,
    wssPort: undefined,
    enableLogging: true,
    debug: true,
    authEndpoint: '/broadcasting/auth'
});

// Add global event logging
window.Echo.connector.pusher.connection.bind('state_change', states => {
    console.log('[Pusher] Connection state changed:', states);
});

window.Echo.connector.pusher.connection.bind('error', error => {
    console.error('[Pusher] Connection error:', error);
});

