import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['useStatements', 'expandCodeButton', 'codeContent'];

    connect() {
        if (this.#isOverflowing(this.codeContentTarget)) {
            this.expandCodeButtonTarget.style.display = 'block';
            // add extra padding so the button doesn't block the code
            this.codeContentTarget.classList.add('pb-5');
        }
    }

    expandUseStatements(event) {
        this.useStatementsTarget.style.display = 'block';
        event.currentTarget.remove();
    }

    expandCode(event) {
        this.codeContentTarget.style.height = 'auto';
        this.codeContentTarget.classList.remove('pb-5');
        this.expandCodeButtonTarget.remove();
    }

    #isOverflowing(element) {
        return element.scrollHeight > element.clientHeight;
    }
}
