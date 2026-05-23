import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

if (import.meta.env.DEV) {
    const pusher = window.Echo.connector.pusher;
    pusher.connection.bind('connected', () => console.log('%c[Reverb] CONNECTED', 'color:#22c55e;font-weight:bold'));
    pusher.connection.bind('disconnected', () => console.log('%c[Reverb] DISCONNECTED', 'color:#ef4444;font-weight:bold'));
    pusher.connection.bind('error', (err) => console.error('[Reverb] ERROR', err));
    pusher.connection.bind('state_change', (states) => console.log(`[Reverb] state: ${states.previous} -> ${states.current}`));
}
