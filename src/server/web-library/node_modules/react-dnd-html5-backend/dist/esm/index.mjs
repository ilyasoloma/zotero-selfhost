import { HTML5BackendImpl } from './HTML5BackendImpl.mjs';
import * as _NativeTypes from './NativeTypes.mjs';
export { _NativeTypes as NativeTypes };
export { getEmptyImage } from './getEmptyImage.mjs';
export const HTML5Backend = function createBackend(manager, context, options) {
    return new HTML5BackendImpl(manager, context, options);
};

//# sourceMappingURL=index.mjs.map