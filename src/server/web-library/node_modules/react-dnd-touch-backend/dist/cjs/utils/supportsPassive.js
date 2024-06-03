"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.supportsPassive = void 0;
const supportsPassive = (()=>{
    // simular to jQuery's test
    let supported = false;
    try {
        addEventListener('test', ()=>{
        // do nothing
        }, Object.defineProperty({}, 'passive', {
            get () {
                supported = true;
                return true;
            }
        }));
    } catch (e) {
    // do nothing
    }
    return supported;
})();
exports.supportsPassive = supportsPassive;

//# sourceMappingURL=supportsPassive.js.map