"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.setClientOffset = setClientOffset;
var _typesJs = require("../types.js");
function setClientOffset(clientOffset, sourceClientOffset) {
    return {
        type: _typesJs.INIT_COORDS,
        payload: {
            sourceClientOffset: sourceClientOffset || null,
            clientOffset: clientOffset || null
        }
    };
}

//# sourceMappingURL=setClientOffset.js.map