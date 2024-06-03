"use strict";
Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.areCoordsEqual = areCoordsEqual;
exports.areArraysEqual = areArraysEqual;
exports.strictEquality = void 0;
const strictEquality = (a, b)=>a === b
;
exports.strictEquality = strictEquality;
function areCoordsEqual(offsetA, offsetB) {
    if (!offsetA && !offsetB) {
        return true;
    } else if (!offsetA || !offsetB) {
        return false;
    } else {
        return offsetA.x === offsetB.x && offsetA.y === offsetB.y;
    }
}
function areArraysEqual(a, b, isEqual = strictEquality) {
    if (a.length !== b.length) {
        return false;
    }
    for(let i = 0; i < a.length; ++i){
        if (!isEqual(a[i], b[i])) {
            return false;
        }
    }
    return true;
}

//# sourceMappingURL=equality.js.map