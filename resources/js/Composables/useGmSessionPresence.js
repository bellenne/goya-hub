import { onBeforeUnmount, onMounted } from 'vue';

const randomConnectionId = () => {
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(36).slice(2, 12)}`;
};

const xsrfToken = () => {
    const cookie = document.cookie
        .split('; ')
        .find((entry) => entry.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : '';
};

export function useGmSessionPresence({ enabled, gameId, sessionId, onStatus }) {
    const connectionId = randomConnectionId();
    let heartbeatTimer = null;
    let disconnected = false;

    const routeName = (action) => `games.sessions.gm-presence.${action}`;
    const url = (action) => route(routeName(action), [gameId, sessionId]);

    const post = (action) => window.axios.post(url(action), { connection_id: connectionId });

    const stopHeartbeat = () => {
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
            heartbeatTimer = null;
        }
    };

    const heartbeat = async () => {
        if (!enabled || disconnected) return;

        try {
            const { data } = await post('heartbeat');
            onStatus?.({ event: 'heartbeat', ...data });
        } catch (error) {
            if (error?.response?.status === 409) {
                onStatus?.({ event: 'ended', status: 'ended' });
                stopHeartbeat();
            }
        }
    };

    const connect = async () => {
        if (!enabled) return;

        try {
            const { data } = await post('connect');
            onStatus?.({ event: 'connected', ...data });

            stopHeartbeat();
            heartbeatTimer = setInterval(
                heartbeat,
                Number(data.heartbeat_interval_seconds ?? 30) * 1000,
            );
        } catch (error) {
            if (error?.response?.status === 409) {
                onStatus?.({ event: 'ended', status: 'ended' });
            }
        }
    };

    const disconnect = (keepalive = false) => {
        if (!enabled || disconnected) return;

        disconnected = true;
        stopHeartbeat();

        if (keepalive) {
            const body = new URLSearchParams({ connection_id: connectionId });
            const token = xsrfToken();

            window.fetch(url('disconnect'), {
                method: 'POST',
                body,
                credentials: 'same-origin',
                keepalive: true,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { 'X-XSRF-TOKEN': token } : {}),
                },
            }).catch(() => {});

            return;
        }

        post('disconnect').catch(() => {});
    };

    const handlePageHide = () => disconnect(true);

    onMounted(() => {
        connect();
        window.addEventListener('pagehide', handlePageHide);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('pagehide', handlePageHide);
        disconnect(false);
    });

    return { connectionId };
}
