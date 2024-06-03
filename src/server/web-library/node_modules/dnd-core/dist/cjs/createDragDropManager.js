"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.createDragDropManager = createDragDropManager;
var _dragDropManagerImplJs = require("./classes/DragDropManagerImpl.js");
var _redux = require("redux");
var _indexJs = require("./reducers/index.js");
var _dragDropMonitorImplJs = require("./classes/DragDropMonitorImpl.js");
var _handlerRegistryImplJs = require("./classes/HandlerRegistryImpl.js");
function createDragDropManager(backendFactory, globalContext = undefined, backendOptions = {}, debugMode = false) {
    const store = makeStoreInstance(debugMode);
    const monitor = new _dragDropMonitorImplJs.DragDropMonitorImpl(store, new _handlerRegistryImplJs.HandlerRegistryImpl(store));
    const manager = new _dragDropManagerImplJs.DragDropManagerImpl(store, monitor);
    const backend = backendFactory(manager, globalContext, backendOptions);
    manager.receiveBackend(backend);
    return manager;
}
function makeStoreInstance(debugMode) {
    // TODO: if we ever make a react-native version of this,
    // we'll need to consider how to pull off dev-tooling
    const reduxDevTools = typeof window !== 'undefined' && window.__REDUX_DEVTOOLS_EXTENSION__;
    return (0, _redux).createStore(_indexJs.reduce, debugMode && reduxDevTools && reduxDevTools({
        name: 'dnd-core',
        instanceId: 'dnd-core'
    }));
}

//# sourceMappingURL=createDragDropManager.js.map