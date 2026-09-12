import { el } from './ui-helpers';
import type { InspectorHost } from '../core/inspector-runtime';

export function createPullTab(host: InspectorHost, open: () => void, signal: AbortSignal): HTMLElement {
    const button = el('button', { type: 'button', 'aria-label': 'Open Inspector', text: 'UX' });
    const tab = el('div', { class: 'pull-tab' }, button);
    let rem = 16;
    const measure = () => (rem = Number.parseFloat(getComputedStyle(document.documentElement).fontSize) || 16);
    const reset = () => tab.removeAttribute('data-near');
    measure();
    window.addEventListener('resize', measure, { passive: true, signal });
    window.addEventListener('blur', reset, { signal });
    document.addEventListener('pointerleave', reset, { signal });
    document.addEventListener(
        'pointermove',
        (event) => {
            if (!host.isConnected || host.isOpen || event.pointerType === 'touch') return;
            const near =
                window.innerWidth - event.clientX <= 2.5 * rem &&
                Math.abs(event.clientY - window.innerHeight / 2) <= 3.25 * rem;
            if (near !== tab.hasAttribute('data-near')) tab.toggleAttribute('data-near', near);
        },
        { passive: true, signal }
    );
    button.addEventListener(
        'click',
        () => {
            reset();
            open();
        },
        { signal }
    );
    return tab;
}
