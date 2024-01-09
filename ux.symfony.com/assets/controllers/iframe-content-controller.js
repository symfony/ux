import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        content: String
    }

    connect() {
        /** @type {HTMLIFrameElement} */
        const iframe = this.element;
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(this.contentValue);
        iframeDoc.close();
    }
}
