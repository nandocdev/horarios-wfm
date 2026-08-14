/**
 * Ciclo de vida de los tours ante la navegación SPA de Livewire (wire:navigate).
 *
 * - `livewire:navigating`: se dispara antes de reemplazar el DOM → se destruye el
 *   tour activo para no dejar overlays, listeners ni estado inconsistente.
 * - `livewire:navigated`: se dispara tras el swap → se re-destruye de forma
 *   defensiva y se auto-inicia el tour pendiente de la nueva vista (data-tour-auto).
 * - Carga inicial: `livewire:navigated` NO se emite en la primera carga directa,
 *   por lo que también se comprueba `[data-tour-auto]` al configurar.
 *
 * `driver.destroy()` NO invoca `onDestroyStarted`, por lo que navegar a mitad de
 * un tour no lo marca como visto: podrá volver a mostrarse la próxima visita.
 */
export const setupTourNavigation = (tour, doc = document) => {
    const destroy = () => tour.destroy();

    const autoStart = () => {
        const autoTourEl = doc.querySelector('[data-tour-auto]');

        if (autoTourEl) {
            const tourKey = autoTourEl.getAttribute('data-tour-auto');

            if (tourKey) {
                tour.autoStartIfPending(tourKey);
            }
        }
    };

    const onNavigated = () => {
        destroy();
        autoStart();
    };

    doc.addEventListener('livewire:navigating', destroy);
    doc.addEventListener('livewire:navigated', onNavigated);

    // Primera carga directa (sin wire:navigate)
    autoStart();
};
