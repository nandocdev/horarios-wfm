/**
 * Selección de pasos que pueden mostrarse en el DOM actual.
 *
 * - Pasos sin elemento (informativos) siempre se incluyen.
 * - Pasos cuyo elemento existe se incluyen.
 * - Pasos con `waitForElement` se incluyen aunque su elemento aún no exista:
 *   driver.js los espera (MutationObserver) hasta que aparezca (p.ej. el contenido
 *   de un modal Flux abierto a mitad de tour) y los omite si nunca llega a existir.
 */
export const filterAvailableSteps = (steps, options = {}) => {
    const doc = options.document ?? document;
    const defaultWait = options.waitForElement ?? 0;

    const availableSteps = [];
    const skipped = [];

    for (const step of steps) {
        if (!step.element) {
            availableSteps.push(step);
            continue;
        }

        if (doc.querySelector(step.element)) {
            availableSteps.push(step);
            continue;
        }

        if ((step.waitForElement ?? defaultWait) > 0) {
            availableSteps.push(step);
            continue;
        }

        skipped.push(step.element);
    }

    return { availableSteps, skipped };
};
