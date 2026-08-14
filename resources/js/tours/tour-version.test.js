import test from 'node:test';
import assert from 'node:assert/strict';
import { isTourPending, versionSeen } from './tour-version.js';

test('versionSeen devuelve 0 para tours nunca vistos', () => {
    assert.equal(versionSeen({}, 'wfm-planning'), 0);
    assert.equal(versionSeen({ 'wfm-planning': { version: 2 } }, 'my-schedule'), 0);
});

test('isTourPending es falso si el usuario vio una versión igual o mayor', () => {
    const progress = { 'wfm-planning': { version: 2 } };

    assert.equal(isTourPending(progress, 'wfm-planning', 1), false);
    assert.equal(isTourPending(progress, 'wfm-planning', 2), false);
});

test('isTourPending es verdadero si la definición sube de versión (v2 re-muestra)', () => {
    const progress = { 'wfm-planning': { version: 1 } };

    assert.equal(isTourPending(progress, 'wfm-planning', 2), true);
});

test('isTourPending es verdadero si el usuario nunca vio el tour', () => {
    assert.equal(isTourPending({}, 'wfm-planning', 1), true);
});
