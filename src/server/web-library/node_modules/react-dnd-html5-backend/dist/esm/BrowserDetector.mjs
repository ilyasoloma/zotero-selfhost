import { memoize } from './utils/js_utils.mjs';
export const isFirefox = memoize(()=>/firefox/i.test(navigator.userAgent)
);
export const isSafari = memoize(()=>Boolean(window.safari)
);

//# sourceMappingURL=BrowserDetector.mjs.map