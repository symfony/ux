/**
 * Bridge between the upload Stimulus controller and a Live Component.
 *
 * The chunked upload runs entirely on the client. When a file finishes, the
 * upload controller dispatches a `symfony--ux-upload--upload:complete` event
 * carrying the signed upload token. This controller, placed on the Live
 * Component root element, catches that event and forwards the token to a live
 * action (default: `applyUpload`) through the documented LiveComponent JS
 * `Component` API -- `getComponent(element).action(...)`. The server resolves
 * the token and stores it on a #[LiveProp], triggering a re-render. No full
 * form submission, no page reload.
 *
 * Twig wiring on the component root:
 *
 *     <div {{ attributes.defaults(stimulus_controller('symfony/ux-upload/live-upload', { property: 'picture' })) }}>
 *
 * `@symfony/ux-live-component` is an optional peer dependency: this controller
 * is only loaded by apps that use LiveComponent alongside the uploader.
 */

import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller<HTMLElement> {
    static values = {
        property: String,
        action: { type: String, default: 'applyUpload' },
        event: { type: String, default: 'symfony--ux-upload--upload:complete' },
    };

    declare propertyValue: string;
    declare actionValue: string;
    declare eventValue: string;

    private component?: Awaited<ReturnType<typeof getComponent>>;
    private readonly onUploadComplete = (event: Event): void => this.apply(event as CustomEvent);

    async connect(): Promise<void> {
        this.component = await getComponent(this.element);
        this.element.addEventListener(this.eventValue, this.onUploadComplete);
    }

    disconnect(): void {
        this.element.removeEventListener(this.eventValue, this.onUploadComplete);
    }

    private apply(event: CustomEvent): void {
        const token = event.detail?.result?.token;
        if (!token || !this.component) {
            return;
        }

        this.component.action(this.actionValue, { property: this.propertyValue, token });
    }
}
