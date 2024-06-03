"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.createPublishDragSource = createPublishDragSource;
var _typesJs = require("./types.js");
function createPublishDragSource(manager) {
    return function publishDragSource() {
        const monitor = manager.getMonitor();
        if (monitor.isDragging()) {
            return {
                type: _typesJs.PUBLISH_DRAG_SOURCE
            };
        }
        return;
    };
}

//# sourceMappingURL=publishDragSource.js.map