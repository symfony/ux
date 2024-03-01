import { Controller } from '@hotwired/stimulus';
import {
    trans,
    setLocale,
    HELLO,
    SAY_HELLO,
    INVITATION_TITLE,
    NUM_OF_APPLES,
    FINISH_PLACE,
    PUBLISHED_AT,
    PROGRESS,
    VALUE_OF_OBJECT
} from '../translator.js';

import hljs from 'highlight.js/lib/core';
import javascript from 'highlight.js/lib/languages/javascript';

hljs.registerLanguage('javascript', javascript);

function highlight({ code, language = 'javascript' }) {
    return hljs.highlight(code, { language }).value;
}

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = [
        'helloCode',
        'helloOutput',

        'sayHelloNameInput',
        'sayHelloCode',
        'sayHelloOutput',

        'invitationTitleOrganizationGenderInput',
        'invitationTitleOrganizationNameInput',
        'invitationTitleCode',
        'invitationTitleOutput',

        'numOfApplesCountInput',
        'numOfApplesCode',
        'numOfApplesOutput',

        'finishPlacePlaceInput',
        'finishPlaceCode',
        'finishPlaceOutput',

        'publishedAtDateInput',
        'publishedAtCode',
        'publishedAtOutput',

        'progressAtProgressInput',
        'progressAtCode',
        'progressAtOutput',

        'valueOfObjectValueInput',
        'valueOfObjectCode',
        'valueOfObjectOutput',
    ]

    connect() {
        this.render();
    }

    setLocale(e) {
        setLocale(e.target.value);
        this.render();
    }

    render() {
        this.helloCodeTarget.innerHTML =     highlight({ code: `import { trans, HELLO } from '../translator';

trans(HELLO)` });
        this.helloOutputTarget.textContent = trans(HELLO);

        this.sayHelloCodeTarget.innerHTML = highlight({
            code: `import { trans, SAY_HELLO } from '../translator';

trans(SAY_HELLO, {
    name: ${this.#quoteValue(this.sayHelloNameInputTarget.value)}
})`
        });
        this.sayHelloOutputTarget.textContent = trans(SAY_HELLO, {
            name: this.sayHelloNameInputTarget.value
        });

        this.invitationTitleCodeTarget.innerHTML = highlight({
            code: `import { trans, INVITATION_TITLE } from '../translator';

trans(INVITATION_TITLE, {
    organizer_gender: ${this.#quoteValue(this.invitationTitleOrganizationGenderInputTarget.value)},
    organizer_name: ${this.#quoteValue(this.invitationTitleOrganizationNameInputTarget.value)},
})`
        }) ;
        this.invitationTitleOutputTarget.textContent = trans(INVITATION_TITLE, {
            organizer_gender: this.invitationTitleOrganizationGenderInputTarget.value,
            organizer_name: this.invitationTitleOrganizationNameInputTarget.value
        });

        this.numOfApplesCodeTarget.innerHTML = highlight({
            code: `import { trans, NUM_OF_APPLES } from '../translator';

trans(NUM_OF_APPLES, {
    apples: ${this.numOfApplesCountInputTarget.value}
})`
        });
        this.numOfApplesOutputTarget.textContent = trans(NUM_OF_APPLES, {
            apples: this.numOfApplesCountInputTarget.value
        })

        this.finishPlaceCodeTarget.innerHTML = highlight({
            code: `import { trans, FINISH_PLACE } from '../translator';

trans(FINISH_PLACE, {
    place: ${this.finishPlacePlaceInputTarget.value}
})`
        });
        this.finishPlaceOutputTarget.textContent = trans(FINISH_PLACE, {
            place: this.finishPlacePlaceInputTarget.value,
        });

        this.publishedAtCodeTarget.innerHTML = highlight({
            code: `import { trans, PUBLISHED_AT } from '../translator';

trans(PUBLISHED_AT, {
    publication_date: new Date('${this.publishedAtDateInputTarget.value}')
})`
        });
        this.publishedAtOutputTarget.textContent = trans(PUBLISHED_AT, {
            publication_date: new Date(this.publishedAtDateInputTarget.value),
        });

        this.progressAtCodeTarget.innerHTML = highlight({
            code: `import { trans, PROGRESS } from '../translator';

trans(PROGRESS, {
    progress: ${this.progressAtProgressInputTarget.value / 100},
})`
        });
        this.progressAtOutputTarget.textContent = trans(PROGRESS, {
            progress: this.progressAtProgressInputTarget.value / 100,
        });

        this.valueOfObjectCodeTarget.innerHTML = highlight({
            code: `import { trans, VALUE_OF_OBJECT } from '../translator';

trans(VALUE_OF_OBJECT, {
    value: ${this.valueOfObjectValueInputTarget.value}
})`
        });
        this.valueOfObjectOutputTarget.textContent = trans(VALUE_OF_OBJECT, {
            value: this.valueOfObjectValueInputTarget.value
        });
    }

    #quoteValue(value) {
        return '\'' + value.replace('\'', '\\\'') + '\'';
    }
}
