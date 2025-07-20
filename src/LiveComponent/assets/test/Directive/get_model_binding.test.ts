import { describe, expect, it } from 'vitest';
import { parseDirectives } from '../../src/Directive/directives_parser';
import getModelBinding from '../../src/Directive/get_model_binding';

describe('get_model_binding', () => {
    it('returns correctly with simple directive', () => {
        const directive = parseDirectives('firstName')[0];

        expect(getModelBinding(directive)).toEqual({
            modelName: 'firstName',
            innerModelName: null,
            shouldRender: true,
            debounce: false,
            targetEventName: null,
            minLength: null,
            maxLength: null,
            minValue: null,
            maxValue: null,
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
            minLength: null,
            maxLength: null,
            minValue: null,
            maxValue: null,
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
            minLength: null,
            maxLength: null,
            minValue: null,
            maxValue: null,
        });
    });

    it('parses min_length and max_length modifiers', () => {
        const directive = parseDirectives('min_length(3)|max_length(20)|username')[0];

        expect(getModelBinding(directive)).toEqual({
            modelName: 'username',
            innerModelName: null,
            shouldRender: true,
            debounce: false,
            targetEventName: null,
            minLength: 3,
            maxLength: 20,
            minValue: null,
            maxValue: null,
        });
    });

    it('parses min_value and max_value modifiers', () => {
        const directive = parseDirectives('min_value(18)|max_value(65)|age')[0];

        expect(getModelBinding(directive)).toEqual({
            modelName: 'age',
            innerModelName: null,
            shouldRender: true,
            debounce: false,
            targetEventName: null,
            minLength: null,
            maxLength: null,
            minValue: 18,
            maxValue: 65,
        });
    });

    it('handles mixed modifiers correctly', () => {
        const directive = parseDirectives('on(change)|norender|debounce(100)|min_value(18)|max_value(65)|age:years')[0];

        expect(getModelBinding(directive)).toEqual({
            modelName: 'age',
            innerModelName: 'years',
            shouldRender: false,
            debounce: 100,
            targetEventName: 'change',
            minLength: null,
            maxLength: null,
            minValue: 18,
            maxValue: 65,
        });
    });

    it('handles empty modifier values gracefully', () => {
        const directive = parseDirectives('min_length|max_length|username')[0];
        const binding = getModelBinding(directive);

        expect(binding.minLength).toBeNull();
        expect(binding.maxLength).toBeNull();
    });
});
