import { useEffect, useState } from 'react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echoInstance = null;

export function usePusher() {
    const [echo, setEcho] = useState(null);
    const [connected, setConnected] = useState(false);

    useEffect(() => {
        if (!echoInstance) {
            window.Pusher = Pusher;

            echoInstance = new Echo({
                broadcaster: 'pusher',
                key: process.env.REACT_APP_PUSHER_APP_KEY || 'your-pusher-key',
                cluster: process.env.REACT_APP_PUSHER_APP_CLUSTER || 'mt1',
                encrypted: true,
                authEndpoint: '/api/broadcasting/auth',
                auth: {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem('token')}`,
                    },
                },
            });

            echoInstance.connector.pusher.connection.bind('connected', () => {
                setConnected(true);
            });

            echoInstance.connector.pusher.connection.bind('disconnected', () => {
                setConnected(false);
            });
        }

        setEcho(echoInstance);
        setConnected(echoInstance.connector.pusher.connection.state === 'connected');

        return () => {
            // Don't disconnect on unmount, keep connection alive
        };
    }, []);

    return { echo, connected };
}

