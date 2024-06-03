"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.reduce = reduce;
var _indexJs = require("../actions/dragDrop/index.js");
var _registryJs = require("../actions/registry.js");
var _equalityJs = require("../utils/equality.js");
var _dirtinessJs = require("../utils/dirtiness.js");
var _jsUtilsJs = require("../utils/js_utils.js");
function reduce(// eslint-disable-next-line @typescript-eslint/no-unused-vars
_state = _dirtinessJs.NONE, action) {
    switch(action.type){
        case _indexJs.HOVER:
            break;
        case _registryJs.ADD_SOURCE:
        case _registryJs.ADD_TARGET:
        case _registryJs.REMOVE_TARGET:
        case _registryJs.REMOVE_SOURCE:
            return _dirtinessJs.NONE;
        case _indexJs.BEGIN_DRAG:
        case _indexJs.PUBLISH_DRAG_SOURCE:
        case _indexJs.END_DRAG:
        case _indexJs.DROP:
        default:
            return _dirtinessJs.ALL;
    }
    const { targetIds =[] , prevTargetIds =[]  } = action.payload;
    const result = (0, _jsUtilsJs).xor(targetIds, prevTargetIds);
    const didChange = result.length > 0 || !(0, _equalityJs).areArraysEqual(targetIds, prevTargetIds);
    if (!didChange) {
        return _dirtinessJs.NONE;
    }
    // Check the target ids at the innermost position. If they are valid, add them
    // to the result
    const prevInnermostTargetId = prevTargetIds[prevTargetIds.length - 1];
    const innermostTargetId = targetIds[targetIds.length - 1];
    if (prevInnermostTargetId !== innermostTargetId) {
        if (prevInnermostTargetId) {
            result.push(prevInnermostTargetId);
        }
        if (innermostTargetId) {
            result.push(innermostTargetId);
        }
    }
    return result;
}

//# sourceMappingURL=dirtyHandlerIds.js.map