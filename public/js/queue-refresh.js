document.querySelectorAll('[data-queue-refresh]').forEach((container) => {
    const state = document.querySelector('[data-refresh-state]');
    let refreshing = false;
    let stopped = false;
    let slowTimer;

    const announce = (message, kind) => {
        state.dataset.state = kind;
        const text = state.querySelector('[data-refresh-message]');

        if (text) {
            text.textContent = message;
        } else {
            state.textContent = message;
        }
    };

    const refresh = async () => {
        if (refreshing || stopped || document.hidden || container.contains(document.activeElement)) {
            return;
        }

        if (!navigator.onLine) {
            announce('No internet connection. Showing the last loaded queue; updates will resume when you reconnect.', 'offline');

            return;
        }

        refreshing = true;
        container.setAttribute('aria-busy', 'true');
        announce('Checking for queue updates…', 'loading');
        slowTimer = window.setTimeout(() => {
            announce('The network is slow. Showing the last loaded queue while we keep trying.', 'slow');
        }, 4000);

        try {
            const response = await fetch(container.dataset.refreshUrl, {
                cache: 'no-store',
                credentials: 'same-origin',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.redirected || response.status === 401 || response.status === 419) {
                stopped = true;
                state.replaceChildren(document.createTextNode('Your session has ended. '));
                const signIn = document.createElement('a');
                signIn.href = container.dataset.signInUrl;
                signIn.textContent = 'Sign in again';
                state.append(signIn);
                state.dataset.state = 'session';

                return;
            }

            if (response.status === 403) {
                stopped = true;
                announce('You no longer have permission to view this queue. Refresh the page or contact an administrator.', 'error');

                return;
            }

            if (!response.ok) {
                throw new Error(`Queue refresh failed with status ${response.status}.`);
            }

            const html = await response.text();
            container.innerHTML = html;
            announce(`Queue updated at ${new Date().toLocaleTimeString()}. Refreshing every 5 seconds.`, 'success');
        } catch (error) {
            if (!navigator.onLine) {
                announce('No internet connection. Showing the last loaded queue; updates will resume when you reconnect.', 'offline');
            } else {
                announce('Queue update failed. Showing the last loaded queue; retrying in 5 seconds.', 'error');
            }
            console.error(error);
        } finally {
            window.clearTimeout(slowTimer);
            container.removeAttribute('aria-busy');
            refreshing = false;
        }
    };

    window.setInterval(() => {
        refresh();
    }, 5000);

    window.addEventListener('online', refresh);
});
