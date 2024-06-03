"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.createEndDrag = createEndDrag;
var _invariant = require("@react-dnd/invariant");
var _typesJs = require("./types.js");
function createEndDrag(manager) {
    return function endDrag() {
        const monitor = manager.getMonitor();
        const registry = manager.getRegistry();
        verifyIsDragging(monitor);
        const sourceId = monitor.getSourceId();
        if (sourceId != null) {
            const source = registry.getSource(sourceId, true);
            source.endDrag(monitor, sourceId);
            registry.unpinSource();
        }
        return {
            type: _typesJs.END_DRAG
        };
    };
}
function verifyIsDragging(monitor) {
    (0, _invariant).invariant(monitor.isDragging(), 'Cannot call endDrag while not dragging.');
}

//# sourceMappingURL=endDrag.js.map