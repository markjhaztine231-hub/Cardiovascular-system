import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

type EchoChannel = Echo<'reverb'>;

let echoInstance: EchoChannel | null = null;

declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}

export function getEcho(): EchoChannel | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const key = import.meta.env.VITE_REVERB_APP_KEY;

    if (!key) {
        // Broadcasts are not configured; stay quiet instead of letting
        // pusher-js throw "You must pass your app key when you instantiate Pusher."
        return null;
    }

    if (!echoInstance) {
        window.Pusher = Pusher;

        echoInstance = new Echo<'reverb'>({
            broadcaster: 'reverb',
            key,
            wsHost: import.meta.env.VITE_REVERB_HOST as string,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 3456),
            wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 3456),
            forceTLS:
                (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    }

    return echoInstance;
}

export function destroyEcho(): void {
    if (echoInstance) {
        echoInstance.disconnect();
        echoInstance = null;
    }
}
