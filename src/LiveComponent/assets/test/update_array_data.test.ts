import {updateArrayDataFromChangedElement} from "../src/update_array_data";


describe('getArrayValue', () => {
    it('Correctly adds data from checkbox', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = true;

        expect(updateArrayDataFromChangedElement(input, 'foo', null))
            .toEqual(['foo']);
        expect(updateArrayDataFromChangedElement(input, 'foo', []))
            .toEqual(['foo']);
        expect(updateArrayDataFromChangedElement(input, 'foo', ['bar']))
            .toEqual(['bar', 'foo']);
    })

    it('Correctly removes data from checkbox', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = false;

        expect(updateArrayDataFromChangedElement(input, 'foo', null))
            .toEqual([]);
        expect(updateArrayDataFromChangedElement(input, 'foo', ['foo']))
            .toEqual([]);
        expect(updateArrayDataFromChangedElement(input, 'foo', ['bar']))
            .toEqual(['bar']);
        expect(updateArrayDataFromChangedElement(input, 'foo', ['foo', 'bar']))
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

        expect(updateArrayDataFromChangedElement(select, '', null))
            .toEqual([]);

        fooOption.selected = true;
        expect(updateArrayDataFromChangedElement(select, '', null))
            .toEqual(['foo']);

        barOption.selected = true;
        expect(updateArrayDataFromChangedElement(select, '', null))
            .toEqual(['foo', 'bar']);
    })

    it('Throws on unsupported elements', () => {
        const div = document.createElement('div');

        expect(() => updateArrayDataFromChangedElement(div, '', null))
            .toThrowError('The element used to determine array data from is unsupported (DIV provided)')
    });
});
