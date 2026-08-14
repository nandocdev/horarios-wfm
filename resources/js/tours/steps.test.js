import test from 'node:test';
import assert from 'node:assert/strict';
import { filterAvailableSteps } from './steps.js';

const makeDoc = (existingSelectors) => {
    const set = new Set(existingSelectors);

    return { querySelector: (selector) => (set.has(selector) ? {} : null) };
};

test('incluye pasos sin elemento (informativos)', () => {
    const { availableSteps, skipped } = filterAvailableSteps(
        [{ popover: { title: 'intro' } }],
        { document: makeDoc([]) }
    );

    assert.equal(availableSteps.length, 1);
    assert.equal(skipped.length, 0);
});

test('incluye pasos cuyo elemento existe en el DOM', () => {
    const steps = [{ element: '[data-tour="a"]', popover: {} }];
    const { availableSteps, skipped } = filterAvailableSteps(steps, { document: makeDoc(['[data-tour="a"]']) });

    assert.equal(availableSteps.length, 1);
    assert.equal(skipped.length, 0);
});

test('descarta pasos cuyo elemento no existe y no esperan', () => {
    const steps = [{ element: '[data-tour="missing"]', popover: {} }];
    const { availableSteps, skipped } = filterAvailableSteps(steps, { document: makeDoc([]) });

    assert.equal(availableSteps.length, 0);
    assert.deepEqual(skipped, ['[data-tour="missing"]']);
});

test('conserva pasos de modales/elementos dinamicos con waitForElement', () => {
    const steps = [{ element: '[data-tour="modal-content"]', waitForElement: 5000, popover: {} }];
    const { availableSteps, skipped } = filterAvailableSteps(steps, { document: makeDoc([]) });

    assert.equal(availableSteps.length, 1);
    assert.equal(skipped.length, 0);
});

test('usa el waitForElement por defecto del tour para pasos sin configuracion propia', () => {
    const steps = [{ element: '[data-tour="modal-content"]', popover: {} }];
    const { availableSteps, skipped } = filterAvailableSteps(steps, { document: makeDoc([]), waitForElement: 3000 });

    assert.equal(availableSteps.length, 1);
    assert.equal(skipped.length, 0);
});
