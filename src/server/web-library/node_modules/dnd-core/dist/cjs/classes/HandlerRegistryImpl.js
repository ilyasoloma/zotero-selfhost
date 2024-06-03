"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
var _invariant = require("@react-dnd/invariant");
var _registryJs = require("../actions/registry.js");
var _getNextUniqueIdJs = require("../utils/getNextUniqueId.js");
var _interfacesJs = require("../interfaces.js");
var _contractsJs = require("../contracts.js");
var _asap = require("@react-dnd/asap");
function getNextHandlerId(role) {
    const id = (0, _getNextUniqueIdJs).getNextUniqueId().toString();
    switch(role){
        case _interfacesJs.HandlerRole.SOURCE:
            return `S${id}`;
        case _interfacesJs.HandlerRole.TARGET:
            return `T${id}`;
        default:
            throw new Error(`Unknown Handler Role: ${role}`);
    }
}
function parseRoleFromHandlerId(handlerId) {
    switch(handlerId[0]){
        case 'S':
            return _interfacesJs.HandlerRole.SOURCE;
        case 'T':
            return _interfacesJs.HandlerRole.TARGET;
        default:
            throw new Error(`Cannot parse handler ID: ${handlerId}`);
    }
}
function mapContainsValue(map, searchValue) {
    const entries = map.entries();
    let isDone = false;
    do {
        const { done , value: [, value] ,  } = entries.next();
        if (value === searchValue) {
            return true;
        }
        isDone = !!done;
    }while (!isDone)
    return false;
}
class HandlerRegistryImpl {
    addSource(type, source) {
        (0, _contractsJs).validateType(type);
        (0, _contractsJs).validateSourceContract(source);
        const sourceId = this.addHandler(_interfacesJs.HandlerRole.SOURCE, type, source);
        this.store.dispatch((0, _registryJs).addSource(sourceId));
        return sourceId;
    }
    addTarget(type, target) {
        (0, _contractsJs).validateType(type, true);
        (0, _contractsJs).validateTargetContract(target);
        const targetId = this.addHandler(_interfacesJs.HandlerRole.TARGET, type, target);
        this.store.dispatch((0, _registryJs).addTarget(targetId));
        return targetId;
    }
    containsHandler(handler) {
        return mapContainsValue(this.dragSources, handler) || mapContainsValue(this.dropTargets, handler);
    }
    getSource(sourceId, includePinned = false) {
        (0, _invariant).invariant(this.isSourceId(sourceId), 'Expected a valid source ID.');
        const isPinned = includePinned && sourceId === this.pinnedSourceId;
        const source = isPinned ? this.pinnedSource : this.dragSources.get(sourceId);
        return source;
    }
    getTarget(targetId) {
        (0, _invariant).invariant(this.isTargetId(targetId), 'Expected a valid target ID.');
        return this.dropTargets.get(targetId);
    }
    getSourceType(sourceId) {
        (0, _invariant).invariant(this.isSourceId(sourceId), 'Expected a valid source ID.');
        return this.types.get(sourceId);
    }
    getTargetType(targetId) {
        (0, _invariant).invariant(this.isTargetId(targetId), 'Expected a valid target ID.');
        return this.types.get(targetId);
    }
    isSourceId(handlerId) {
        const role = parseRoleFromHandlerId(handlerId);
        return role === _interfacesJs.HandlerRole.SOURCE;
    }
    isTargetId(handlerId) {
        const role = parseRoleFromHandlerId(handlerId);
        return role === _interfacesJs.HandlerRole.TARGET;
    }
    removeSource(sourceId) {
        (0, _invariant).invariant(this.getSource(sourceId), 'Expected an existing source.');
        this.store.dispatch((0, _registryJs).removeSource(sourceId));
        (0, _asap).asap(()=>{
            this.dragSources.delete(sourceId);
            this.types.delete(sourceId);
        });
    }
    removeTarget(targetId) {
        (0, _invariant).invariant(this.getTarget(targetId), 'Expected an existing target.');
        this.store.dispatch((0, _registryJs).removeTarget(targetId));
        this.dropTargets.delete(targetId);
        this.types.delete(targetId);
    }
    pinSource(sourceId) {
        const source = this.getSource(sourceId);
        (0, _invariant).invariant(source, 'Expected an existing source.');
        this.pinnedSourceId = sourceId;
        this.pinnedSource = source;
    }
    unpinSource() {
        (0, _invariant).invariant(this.pinnedSource, 'No source is pinned at the time.');
        this.pinnedSourceId = null;
        this.pinnedSource = null;
    }
    addHandler(role, type, handler) {
        const id = getNextHandlerId(role);
        this.types.set(id, type);
        if (role === _interfacesJs.HandlerRole.SOURCE) {
            this.dragSources.set(id, handler);
        } else if (role === _interfacesJs.HandlerRole.TARGET) {
            this.dropTargets.set(id, handler);
        }
        return id;
    }
    constructor(store){
        this.types = new Map();
        this.dragSources = new Map();
        this.dropTargets = new Map();
        this.pinnedSourceId = null;
        this.pinnedSource = null;
        this.store = store;
    }
}
exports.HandlerRegistryImpl = HandlerRegistryImpl;

//# sourceMappingURL=HandlerRegistryImpl.js.map