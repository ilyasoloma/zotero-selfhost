import { TouchBackendImpl } from './TouchBackendImpl.mjs';
export * from './interfaces.mjs';
export * from './TouchBackendImpl.mjs';
export const TouchBackend = function createBackend(manager, context = {}, options = {}) {
    return new TouchBackendImpl(manager, context, options);
};

//# sourceMappingURL=index.mjs.map