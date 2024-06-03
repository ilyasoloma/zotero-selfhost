"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.eventShouldStartDrag = eventShouldStartDrag;
exports.eventShouldEndDrag = eventShouldEndDrag;
exports.isTouchEvent = isTouchEvent;
// Used for MouseEvent.buttons (note the s on the end).
const MouseButtons = {
    Left: 1,
    Right: 2,
    Center: 4
};
// Used for e.button (note the lack of an s on the end).
const MouseButton = {
    Left: 0,
    Center: 1,
    Right: 2
};
function eventShouldStartDrag(e) {
    // For touch events, button will be undefined. If e.button is defined,
    // then it should be MouseButton.Left.
    return e.button === undefined || e.button === MouseButton.Left;
}
function eventShouldEndDrag(e) {
    // Touch events will have buttons be undefined, while mouse events will have e.buttons's left button
    // bit field unset if the left mouse button has been released
    return e.buttons === undefined || (e.buttons & MouseButtons.Left) === 0;
}
function isTouchEvent(e) {
    return !!e.targetTouches;
}

//# sourceMappingURL=predicates.js.map