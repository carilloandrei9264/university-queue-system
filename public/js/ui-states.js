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
    document.querySelectorAll('[data-form-progress][data-state="offline"]').forEach((progress) => progress.remove());
};

if (!navigator.onLine) {
    showConnectionState();
}

window.addEventListener('offline', showConnectionState);
window.addEventListener('online', clearConnectionState);

document.querySelectorAll('form').forEach((form) => {
    if (form.method.toUpperCase() === 'GET') {
        form.addEventListener('submit', (event) => {
            if (!navigator.onLine) {
                event.preventDefault();
                setFormProgress(form, 'Your device is offline. Reconnect, then submit this form again.', 'offline');
            }
        });

        return;
    }

    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();

            return;
        }

        if (!navigator.onLine) {
            event.preventDefault();
            setFormProgress(form, 'Your device is offline. Reconnect, then submit this form again.', 'offline');

            return;
        }

        const submitter = event.submitter instanceof HTMLButtonElement
            ? event.submitter
            : form.querySelector('button[type="submit"], button:not([type])');

        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');

        if (submitter) {
            submitter.dataset.originalLabel = submitter.textContent;
            submitter.dataset.wasDisabled = String(submitter.disabled);
            submitter.textContent = submitter.dataset.loadingText || 'Submitting…';
            submitter.classList.add('is-submitting');
            submitter.setAttribute('aria-busy', 'true');
            submitter.setAttribute('aria-live', 'polite');
        }

        const timer = window.setTimeout(() => {
            if (form.isConnected) {
                if ((form.matches('[data-logout-form]') || submitter?.dataset.loadingText === 'Signing out…') && submitter) {
                    submitter.textContent = 'Still signing out…';
                } else {
                    setFormProgress(form, 'This is taking longer than usual. Please keep this page open.', 'slow');
                }
            }
        }, 4000);

        window.addEventListener('pagehide', () => window.clearTimeout(timer), { once: true });

        form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
            if (button !== submitter) {
                button.dataset.wasDisabled = String(button.disabled);
            }
            button.disabled = true;
        });
    });
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-submitting="true"]').forEach((form) => {
        form.removeAttribute('aria-busy');
        delete form.dataset.submitting;
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
            button.disabled = button.dataset.wasDisabled === 'true';
            delete button.dataset.wasDisabled;
            button.classList.remove('is-submitting');
            button.removeAttribute('aria-busy');
            button.removeAttribute('aria-live');

            if (button.dataset.originalLabel !== undefined) {
                button.textContent = button.dataset.originalLabel;
                delete button.dataset.originalLabel;
            }
        });
        form.querySelector('[data-form-progress]')?.remove();
    });
});

const validationSummary = document.querySelector('[data-validation-summary]');

if (validationSummary) {
    validationSummary.focus();
}
