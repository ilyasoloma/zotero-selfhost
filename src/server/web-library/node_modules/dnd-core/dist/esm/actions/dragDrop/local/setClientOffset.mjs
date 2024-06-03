import { INIT_COORDS } from '../types.mjs';
export function setClientOffset(clientOffset, sourceClientOffset) {
    return {
        type: INIT_COORDS,
        payload: {
            sourceClientOffset: sourceClientOffset || null,
            clientOffset: clientOffset || null
        }
    };
}

//# sourceMappingURL=setClientOffset.mjs.map