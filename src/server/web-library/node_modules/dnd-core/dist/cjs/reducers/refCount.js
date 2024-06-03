"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.reduce = reduce;
var _registryJs = require("../actions/registry.js");
function reduce(state = 0, action) {
    switch(action.type){
        case _registryJs.ADD_SOURCE:
        case _registryJs.ADD_TARGET:
            return state + 1;
        case _registryJs.REMOVE_SOURCE:
        case _registryJs.REMOVE_TARGET:
            return state - 1;
        default:
            return state;
    }
}

//# sourceMappingURL=refCount.js.map