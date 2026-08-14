import { wfmTour } from './driver-manager';
import { tourDefinitions } from './definitions';

// Exponer globalmente en window
window.WfmTour = wfmTour;
window.startWfmTour = (tourKey) => wfmTour.start(tourKey, true);

// Registrar directiva o listener de navegación Livewire
document.addEventListener('livewire:navigated', () => {
    // Si la vista actual contiene un elemento con data-tour-auto="tourKey"
    const autoTourEl = document.querySelector('[data-tour-auto]');
    if (autoTourEl) {
        const tourKey = autoTourEl.getAttribute('data-tour-auto');
        if (tourKey) {
            wfmTour.autoStartIfPending(tourKey);
        }
    }
});

// Listener para eventos disparados desde Livewire ($dispatch('start-tour', { tour: 'key' }))
window.addEventListener('wfm:start-tour', (event) => {
    const tourKey = event.detail?.tour || event.detail;
    if (tourKey) {
        wfmTour.start(tourKey, true);
    }
});

export { wfmTour, tourDefinitions };
