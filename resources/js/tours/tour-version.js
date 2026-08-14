/**
 * Lógica pura de versionado de tours.
 * Un tour se vuelve a mostrar cuando la versión de su definición supera
 * la última versión que el usuario ya vio (progreso persistido por usuario).
 */

export const versionSeen = (progress, tourKey) => progress?.[tourKey]?.version ?? 0;

export const isTourPending = (progress, tourKey, version = 1) =>
    versionSeen(progress, tourKey) < version;
