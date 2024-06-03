"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.addSource = addSource;
exports.addTarget = addTarget;
exports.removeSource = removeSource;
exports.removeTarget = removeTarget;
exports.REMOVE_TARGET = exports.REMOVE_SOURCE = exports.ADD_TARGET = exports.ADD_SOURCE = void 0;
const ADD_SOURCE = 'dnd-core/ADD_SOURCE';
exports.ADD_SOURCE = ADD_SOURCE;
const ADD_TARGET = 'dnd-core/ADD_TARGET';
exports.ADD_TARGET = ADD_TARGET;
const REMOVE_SOURCE = 'dnd-core/REMOVE_SOURCE';
exports.REMOVE_SOURCE = REMOVE_SOURCE;
const REMOVE_TARGET = 'dnd-core/REMOVE_TARGET';
exports.REMOVE_TARGET = REMOVE_TARGET;
function addSource(sourceId) {
    return {
        type: ADD_SOURCE,
        payload: {
            sourceId
        }
    };
}
function addTarget(targetId) {
    return {
        type: ADD_TARGET,
        payload: {
            targetId
        }
    };
}
function removeSource(sourceId) {
    return {
        type: REMOVE_SOURCE,
        payload: {
            sourceId
        }
    };
}
function removeTarget(targetId) {
    return {
        type: REMOVE_TARGET,
        payload: {
            targetId
        }
    };
}

//# sourceMappingURL=registry.js.map