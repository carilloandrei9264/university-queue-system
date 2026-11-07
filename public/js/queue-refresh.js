document.querySelectorAll('[data-queue-refresh]').forEach((container) => {
    const state = document.querySelector('[data-refresh-state]');
    let refreshing = false;

    const refresh = async () => {
        if (refreshing || document.hidden || container.contains(document.activeElement)) {
            return;
        }

        refreshing = true;

        try {
            const response = await fetch(container.dataset.refreshUrl, {
                cache: 'no-store',
                credentials: 'same-origin',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`Queue refresh failed with status ${response.status}.`);
            }

            const html = await response.text();
            container.innerHTML = html;
            state.textContent = `Queue updated at ${new Date().toLocaleTimeString()}. Refreshing every 5 seconds.`;
        } catch (error) {
            state.textContent = 'Live queue update failed. Retrying in 5 seconds.';
            console.error(error);
        } finally {
            refreshing = false;
        }
    };

    window.setInterval(refresh, 5000);
});
