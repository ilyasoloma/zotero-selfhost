"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.isSafari = exports.isFirefox = void 0;
var _jsUtilsJs = require("./utils/js_utils.js");
const isFirefox = (0, _jsUtilsJs).memoize(()=>/firefox/i.test(navigator.userAgent)
);
exports.isFirefox = isFirefox;
const isSafari = (0, _jsUtilsJs).memoize(()=>Boolean(window.safari)
);
exports.isSafari = isSafari;

//# sourceMappingURL=BrowserDetector.js.map