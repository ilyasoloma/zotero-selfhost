"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
var _exportNames = {};
exports.TouchBackend = void 0;
var _touchBackendImplJs = _interopRequireWildcard(require("./TouchBackendImpl.js"));
Object.keys(_touchBackendImplJs).forEach(function(key) {
    if (key === "default" || key === "__esModule") return;
    if (Object.prototype.hasOwnProperty.call(_exportNames, key)) return;
    if (key in exports && exports[key] === _touchBackendImplJs[key]) return;
    Object.defineProperty(exports, key, {
        enumerable: true,
        get: function() {
            return _touchBackendImplJs[key];
        }
    });
});
var _interfacesJs = _interopRequireWildcard(require("./interfaces.js"));
Object.keys(_interfacesJs).forEach(function(key) {
    if (key === "default" || key === "__esModule") return;
    if (Object.prototype.hasOwnProperty.call(_exportNames, key)) return;
    if (key in exports && exports[key] === _interfacesJs[key]) return;
    Object.defineProperty(exports, key, {
        enumerable: true,
        get: function() {
            return _interfacesJs[key];
        }
    });
});
function _interopRequireWildcard(obj) {
    if (obj && obj.__esModule) {
        return obj;
    } else {
        var newObj = {};
        if (obj != null) {
            for(var key in obj){
                if (Object.prototype.hasOwnProperty.call(obj, key)) {
                    var desc = Object.defineProperty && Object.getOwnPropertyDescriptor ? Object.getOwnPropertyDescriptor(obj, key) : {};
                    if (desc.get || desc.set) {
                        Object.defineProperty(newObj, key, desc);
                    } else {
                        newObj[key] = obj[key];
                    }
                }
            }
        }
        newObj.default = obj;
        return newObj;
    }
}
const TouchBackend = function createBackend(manager, context = {}, options = {}) {
    return new _touchBackendImplJs.TouchBackendImpl(manager, context, options);
};
exports.TouchBackend = TouchBackend;

//# sourceMappingURL=index.js.map