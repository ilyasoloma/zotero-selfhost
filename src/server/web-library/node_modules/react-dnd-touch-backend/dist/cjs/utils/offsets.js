"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.getNodeClientOffset = getNodeClientOffset;
exports.getEventClientTouchOffset = getEventClientTouchOffset;
exports.getEventClientOffset = getEventClientOffset;
var _predicatesJs = require("./predicates.js");
const ELEMENT_NODE = 1;
function getNodeClientOffset(node) {
    const el = node.nodeType === ELEMENT_NODE ? node : node.parentElement;
    if (!el) {
        return undefined;
    }
    const { top , left  } = el.getBoundingClientRect();
    return {
        x: left,
        y: top
    };
}
function getEventClientTouchOffset(e, lastTargetTouchFallback) {
    if (e.targetTouches.length === 1) {
        return getEventClientOffset(e.targetTouches[0]);
    } else if (lastTargetTouchFallback && e.touches.length === 1) {
        if (e.touches[0].target === lastTargetTouchFallback.target) {
            return getEventClientOffset(e.touches[0]);
        }
    }
    return;
}
function getEventClientOffset(e, lastTargetTouchFallback) {
    if ((0, _predicatesJs).isTouchEvent(e)) {
        return getEventClientTouchOffset(e, lastTargetTouchFallback);
    } else {
        return {
            x: e.clientX,
            y: e.clientY
        };
    }
}

//# sourceMappingURL=offsets.js.map