import test from 'node:test';
import assert from 'node:assert/strict';
import { setupTourNavigation } from './navigation.js';

const makeDoc = ({ autoTour = null } = {}) => {
    const listeners = new Map();

    return {
        addEventListener: (name, fn) => listeners.set(name, fn),
        dispatch: (name) => listeners.get(name)?.(),
        querySelector: (selector) => (selector === '[data-tour-auto]' ? autoTour : null),
    };
};

test('livewire:navigating destruye el tour activo', () => {
    const tour = {
        destroyCalls: 0,
        destroy() {
            this.destroyCalls++;
        },
        autoStartIfPending() {
            throw new Error('no deberia auto-iniciar');
        },
    };
    const doc = makeDoc();

    setupTourNavigation(tour, doc);
    doc.dispatch('livewire:navigating');

    assert.equal(tour.destroyCalls, 1);
});

test('livewire:navigated re-destruye defensivamente antes de auto-iniciar', () => {
    const tour = {
        destroyCalls: 0,
        destroy() {
            this.destroyCalls++;
        },
        autoStartIfPending() {},
    };
    const doc = makeDoc();

    setupTourNavigation(tour, doc);
    doc.dispatch('livewire:navigated');

    assert.equal(tour.destroyCalls, 1);
});

test('livewire:navigated auto-inicia el tour pendiente de la nueva vista', () => {
    const tour = {
        destroy() {},
        autoStartIfPending(tourKey) {
            this.started = tourKey;
        },
    };
    const autoTour = { getAttribute: (name) => (name === 'data-tour-auto' ? 'wfm-planning' : null) };
    const doc = makeDoc({ autoTour });

    setupTourNavigation(tour, doc);
    doc.dispatch('livewire:navigated');

    assert.equal(tour.started, 'wfm-planning');
});

test('livewire:navigated no auto-inicia si no existe data-tour-auto', () => {
    const tour = {
        destroy() {},
        autoStartIfPending() {
            this.started = true;
        },
    };
    const doc = makeDoc();

    setupTourNavigation(tour, doc);
    doc.dispatch('livewire:navigated');

    assert.equal(tour.started, undefined);
});

test('en la primera carga directa auto-inicia el tour pendiente (sin wire:navigate)', () => {
    const tour = {
        destroy() {},
        autoStartIfPending(tourKey) {
            this.started = tourKey;
        },
    };
    const autoTour = { getAttribute: (name) => (name === 'data-tour-auto' ? 'operations.control-tower' : null) };
    const doc = makeDoc({ autoTour });

    setupTourNavigation(tour, doc);

    assert.equal(tour.started, 'operations.control-tower');
});

test('en la primera carga directa no auto-inicia si el tour ya fue visto', () => {
    const tour = {
        destroy() {},
        autoStartIfPending() {
            this.started = true;
        },
    };
    const doc = makeDoc();

    setupTourNavigation(tour, doc);

    assert.equal(tour.started, undefined);
});
