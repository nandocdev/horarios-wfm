import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { tourDefinitions } from './definitions';
import { isTourPending, versionSeen as seenVersion } from './tour-version';
import { filterAvailableSteps } from './steps';

const STORAGE_KEY = 'wfm_tours_completed_v1';

/**
 * Lee el progreso inicial de tours.
 * 1. Progreso por usuario renderizado por el servidor (Livewire, sincrono).
 * 2. Caché local de la última sesión (fallback).
 */
const readInitialProgress = () => {
    const el = document.querySelector('[data-user-tour-progress]');

    if (el?.dataset.userTourProgressValue) {
        try {
            return JSON.parse(el.dataset.userTourProgressValue);
        } catch {
            console.warn('[WfmTour] Progreso inicial de tours inválido.');
        }
    }

    try {
        const cached = localStorage.getItem(STORAGE_KEY);

        return cached ? JSON.parse(cached) : {};
    } catch {
        return {};
    }
};

class TourManager {
    constructor() {
        this.currentDriver = null;
        this.progress = readInitialProgress();
    }

    /**
     * Versión de un tour que el usuario ya vio (0 si nunca lo vio).
     */
    versionSeen(tourKey) {
        return seenVersion(this.progress, tourKey);
    }

    /**
     * Un tour se considera completado si se vio con una versión mayor o igual a la actual.
     */
    isTourCompleted(tourKey, version = 1) {
        return !isTourPending(this.progress, tourKey, version);
    }

    /**
     * Registra que el usuario vio un tour, localmente (caché) y en el servidor (por usuario).
     */
    markSeen(tourKey, version = 1, state = 'completed') {
        this.progress[tourKey] = {
            version,
            state,
            seen_at: new Date().toISOString(),
        };

        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(this.progress));
        } catch {
            console.warn('[WfmTour] No se pudo guardar el caché local de tours.');
        }

        window.Livewire?.dispatch?.('tour:record', { tour: tourKey, version, state });
    }

    /**
     * Destruye el tour activo (si existe) sin marcarlo como visto.
     * Se usa al navegar con wire:navigate para no dejar overlays ni listeners huérfanos,
     * y sin registrar el tour como completado (el usuario lo abandonó a mitad de camino).
     */
    destroy() {
        if (this.currentDriver) {
            this.currentDriver.destroy();
            this.currentDriver = null;
        }
    }

    /**
     * Limpia el progreso local (útil para desarrollo).
     */
    resetCompletedTours() {
        this.progress = {};

        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch {
        }
    }

    /**
     * Inicia un tour por su identificador.
     */
    start(tourKey, force = false) {
        const config = tourDefinitions[tourKey];

        if (!config || !Array.isArray(config.steps) || config.steps.length === 0) {
            console.warn(`[WfmTour] Tour no encontrado o sin pasos: ${tourKey}`);
            return;
        }

        const version = config.version ?? 1;

        // Filtrar pasos cuyos elementos existan o puedan aparecer después (modales Flux, lazy loading)
        const { availableSteps, skipped } = filterAvailableSteps(config.steps, {
            waitForElement: config.waitForElement ?? 0,
        });

        if (skipped.length > 0) {
            console.warn(`[WfmTour] Pasos omitidos de "${tourKey}" (elemento no visible): ${skipped.join(', ')}`);
        }

        if (availableSteps.length === 0) {
            console.warn(`[WfmTour] Ningún elemento del tour "${tourKey}" está visible en el DOM.`);
            return;
        }

        if (this.currentDriver) {
            this.currentDriver.destroy();
            this.currentDriver = null;
        }

        this.currentDriver = driver({
            showProgress: true,
            animate: true,
            allowClose: true,
            skipMissingElement: true,
            overlayColor: 'rgba(15, 23, 42, 0.75)',
            stagePadding: 6,
            stageRadius: 6,
            nextBtnText: 'Siguiente →',
            prevBtnText: '← Anterior',
            doneBtnText: '¡Entendido!',
            progressText: 'Paso {{current}} de {{total}}',
            steps: availableSteps,
            onDestroyStarted: () => {
                this.markSeen(tourKey, version);
                if (this.currentDriver) {
                    this.currentDriver.destroy();
                    this.currentDriver = null;
                }
            },
        });

        this.currentDriver.drive();
    }

    /**
     * Auto-inicia un tour si el usuario aún no lo vio en su versión actual.
     */
    autoStartIfPending(tourKey) {
        const config = tourDefinitions[tourKey];
        const version = config?.version ?? 1;

        if (this.isTourCompleted(tourKey, version)) {
            return;
        }

        // Breve delay para asegurar que los elementos del DOM y Livewire estén renderizados
        setTimeout(() => {
            this.start(tourKey, false);
        }, 600);
    }
}

export const wfmTour = new TourManager();
