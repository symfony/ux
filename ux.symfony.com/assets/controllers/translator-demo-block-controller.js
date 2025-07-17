import { Controller } from '@hotwired/stimulus';

import * as translator from '../translator.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = [
        'code',
        'parameters',
        'outputs',
    ]

    static values = {
        message: String,
    }

    connect() {
        this.render();
    }

    render() {
        const parameters = new Map();
        this.parametersTargets.forEach((target) => {
            if (target.name.includes('date')) {
                parameters.set(target.name, new Date(target.value));
            } else if (target.name.includes('progress')) {
                parameters.set(target.name, Number(target.value) / 100);
            } else {
                parameters.set(target.name, target.value);
            }
        });
        parameters.forEach((value, name) => {
            const code = this.codeTarget.querySelector(`span[data-code-parameter="${name}"]`);
            if (value instanceof Date) {
                code.innerText = value.toLocaleString();
            } else {
                code.innerText = value.toString();
            }
        });

        this.outputsTargets.forEach((target) => {
            target.textContent = translator.trans(translator[this.messageValue], Object.fromEntries(parameters), 'messages', target.dataset.locale);
        });
    }
}
