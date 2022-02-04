import {getArrayValue} from "../src/get_array_value";


describe('getArrayValue', () => {
    it('Correctly adds data from checkbox', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = true;

        expect(getArrayValue(input, 'foo', null))
            .toEqual(['foo']);
        expect(getArrayValue(input, 'foo', []))
            .toEqual(['foo']);
        expect(getArrayValue(input, 'foo', ['bar']))
            .toEqual(['bar', 'foo']);
    })

    it('Correctly removes data from checkbox', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = false;

        expect(getArrayValue(input, 'foo', null))
            .toEqual(null);
        expect(getArrayValue(input, 'foo', ['foo']))
            .toEqual(null);
        expect(getArrayValue(input, 'foo', ['bar']))
            .toEqual(['bar']);
        expect(getArrayValue(input, 'foo', ['foo', 'bar']))
            .toEqual(['bar']);
    })

    it('Correctly sets data from select multiple', () => {
        const select = document.createElement('select');
        select.multiple = true;
        const fooOption = document.createElement('option');
        fooOption.value = 'foo';
        select.add(fooOption);
        const barOption = document.createElement('option');
        barOption.value = 'bar';
        select.add(barOption);

        expect(getArrayValue(select, '', null))
            .toEqual(null);

        fooOption.selected = true;
        expect(getArrayValue(select, '', null))
            .toEqual(['foo']);

        barOption.selected = true;
        expect(getArrayValue(select, '', null))
            .toEqual(['foo', 'bar']);
    })
});
