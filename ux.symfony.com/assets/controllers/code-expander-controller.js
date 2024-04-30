import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['useStatements', 'expandCodeButton', 'codeContent'];

    expandUseStatements(event) {
        this.useStatementsTarget.style.display = 'block';
        event.currentTarget.remove();
    }

    expandCode(event) {
        this.codeContentTarget.style.height = 'auto';
        if (this.hasExpandCodeButtonTarget) {
            this.expandCodeButtonTarget.remove();
        }
    }

    #isOverflowing(element) {
        return element.scrollHeight > element.clientHeight;
    }
}
