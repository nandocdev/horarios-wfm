import { wfmTour } from './driver-manager';
import { tourDefinitions } from './definitions';
import { setupTourNavigation } from './navigation';

// Exponer globalmente en window
window.WfmTour = wfmTour;
window.startWfmTour = (tourKey) => wfmTour.start(tourKey, true);

// Destruir el tour activo al navegar (wire:navigate) y auto-iniciar el pendiente de la nueva vista
setupTourNavigation(wfmTour);

// Listener para eventos disparados desde Livewire ($dispatch('start-tour', { tour: 'key' }))
window.addEventListener('wfm:start-tour', (event) => {
    const tourKey = event.detail?.tour || event.detail;
    if (tourKey) {
        wfmTour.start(tourKey, true);
    }
});

export { wfmTour, tourDefinitions };
