import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { tourDefinitions } from './definitions';

class TourManager {
    constructor() {
        this.currentDriver = null;
        this.storageKey = 'wfm_tours_completed';
    }

    /**
     * Obtiene la lista de tours completados desde LocalStorage
     */
    getCompletedTours() {
        try {
            const data = localStorage.getItem(this.storageKey);
            return data ? JSON.parse(data) : [];
        } catch {
            return [];
        }
    }

    /**
     * Marca un tour como completado
     */
    markTourCompleted(tourKey) {
        try {
            const completed = new Set(this.getCompletedTours());
            completed.add(tourKey);
            localStorage.setItem(this.storageKey, JSON.stringify([...completed]));
        } catch (e) {
            console.warn('[WfmTour] No se pudo guardar estado en localStorage', e);
        }
    }

    /**
     * Comprueba si un tour ya fue completado
     */
    isTourCompleted(tourKey) {
        return this.getCompletedTours().includes(tourKey);
    }

    /**
     * Resetea el historial de tours completados
     */
    resetCompletedTours() {
        localStorage.removeItem(this.storageKey);
    }

    /**
     * Inicia un tour por su identificador
     */
    start(tourKey, force = false) {
        const config = tourDefinitions[tourKey];

        if (!config || !Array.isArray(config.steps) || config.steps.length === 0) {
            console.warn(`[WfmTour] Tour no encontrado o sin pasos: ${tourKey}`);
            return;
        }

        // Filtrar pasos cuyos elementos existan en el DOM actual
        const availableSteps = config.steps.filter((step) => {
            if (!step.element) return true;
            return document.querySelector(step.element) !== null;
        });

        if (availableSteps.length === 0) {
            console.warn(`[WfmTour] Ningún elemento del tour "${tourKey}" está visible en el DOM.`);
            return;
        }

        if (this.currentDriver) {
            this.currentDriver.destroy();
        }

        this.currentDriver = driver({
            showProgress: true,
            animate: true,
            allowClose: true,
            overlayColor: 'rgba(15, 23, 42, 0.75)',
            stagePadding: 6,
            stageRadius: 6,
            nextBtnText: 'Siguiente →',
            prevBtnText: '← Anterior',
            doneBtnText: '¡Entendido!',
            progressText: 'Paso {{current}} de {{total}}',
            steps: availableSteps,
            onDestroyStarted: () => {
                this.markTourCompleted(tourKey);
                if (this.currentDriver) {
                    this.currentDriver.destroy();
                    this.currentDriver = null;
                }
            },
        });

        this.currentDriver.drive();
    }

    /**
     * Auto-inicia un tour si no ha sido visto antes por el usuario
     */
    autoStartIfPending(tourKey) {
        if (!this.isTourCompleted(tourKey)) {
            // Breve delay para asegurar que los elementos del DOM y Livewire estén renderizados
            setTimeout(() => {
                this.start(tourKey, false);
            }, 600);
        }
    }
}

export const wfmTour = new TourManager();
