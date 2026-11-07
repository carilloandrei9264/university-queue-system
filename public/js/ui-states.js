const setFormProgress = (form, message, state) => {
    let progress = form.querySelector('[data-form-progress]');

    if (!progress) {
        progress = document.createElement('p');
        progress.className = 'form-progress state-message';
        progress.dataset.formProgress = '';
        progress.setAttribute('role', 'status');
        progress.setAttribute('aria-live', 'polite');
        form.append(progress);
    }

    progress.dataset.state = state;
    progress.textContent = message;
};

const showConnectionState = () => {
    let banner = document.querySelector('[data-connection-banner]');

    if (!banner) {
        banner = document.createElement('div');
        banner.className = 'connection-banner';
        banner.dataset.connectionBanner = '';
        banner.setAttribute('role', 'status');
        banner.setAttribute('aria-live', 'assertive');
        document.body.prepend(banner);
    }

    banner.textContent = 'No internet connection. Your current page is still available; updates may be delayed.';
};

const clearConnectionState = () => {
    document.querySelector('[data-connection-banner]')?.remove();
};

if (!navigator.onLine) {
    showConnectionState();
}

window.addEventListener('offline', showConnectionState);
window.addEventListener('online', clearConnectionState);

document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!navigator.onLine) {
            event.preventDefault();
            setFormProgress(form, 'Your device is offline. Reconnect, then submit this form again.', 'offline');

            return;
        }

        form.setAttribute('aria-busy', 'true');
        setFormProgress(form, 'Submitting…', 'loading');

        const timer = window.setTimeout(() => {
            if (form.isConnected) {
                setFormProgress(form, 'This is taking longer than usual. Please keep this page open.', 'slow');
            }
        }, 4000);

        window.addEventListener('pagehide', () => window.clearTimeout(timer), { once: true });

        form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
            button.disabled = true;
        });
    });
});

const validationSummary = document.querySelector('[data-validation-summary]');

if (validationSummary) {
    validationSummary.focus();
}
