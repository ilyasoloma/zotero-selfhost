"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.areDirty = areDirty;
exports.ALL = exports.NONE = void 0;
var _jsUtilsJs = require("./js_utils.js");
const NONE = [];
exports.NONE = NONE;
const ALL = [];
exports.ALL = ALL;
NONE.__IS_NONE__ = true;
ALL.__IS_ALL__ = true;
function areDirty(dirtyIds, handlerIds) {
    if (dirtyIds === NONE) {
        return false;
    }
    if (dirtyIds === ALL || typeof handlerIds === 'undefined') {
        return true;
    }
    const commonIds = (0, _jsUtilsJs).intersection(handlerIds, dirtyIds);
    return commonIds.length > 0;
}

//# sourceMappingURL=dirtiness.js.map