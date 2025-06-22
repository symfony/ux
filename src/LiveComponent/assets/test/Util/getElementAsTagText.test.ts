import { htmlToElement } from '../../src/dom_utils';
import getElementAsTagText from '../../src/Util/getElementAsTagText';

describe('getElementAsTagText', () => {
    it('returns self-closing tag correctly', () => {
        const element = htmlToElement('<input name="user[firstName]">');

        expect(getElementAsTagText(element)).toEqual('<input name="user[firstName]">');
    });

    it('returns tag text without the innerHTML', () => {
        const element = htmlToElement('<div class="foo">Name: <input name="user[firstName]"></div>');

        expect(getElementAsTagText(element)).toEqual('<div class="foo">');
    });
});
