import getModelBinding from '../../src/Directive/get_model_binding';
import {parseDirectives} from '../../src/Directive/directives_parser';

describe('get_model_binding', () => {
    it('returns correctly with simple directive', () => {
        const directive = parseDirectives('firstName')[0];
        expect(getModelBinding(directive)).toEqual({
            modelName: 'firstName',
            innerModelName: null,
            shouldRender: true,
            debounce: false,
            targetEventName: null,
        });
    });

    it('returns all modifiers correctly', () => {
        const directive = parseDirectives('on(change)|norender|debounce(100)|firstName')[0];
        expect(getModelBinding(directive)).toEqual({
            modelName: 'firstName',
            innerModelName: null,
            shouldRender: false,
            debounce: 100,
            targetEventName: 'change',
        });
    });

    it('parses the parent:inner model name correctly', () => {
        const directive = parseDirectives('firstName:first')[0];
        expect(getModelBinding(directive)).toEqual({
            modelName: 'firstName',
            innerModelName: 'first',
            shouldRender: true,
            debounce: false,
            targetEventName: null,
        });
    });
});
