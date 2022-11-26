import { UnsyncedInputContainer } from '../src/Component/UnsyncedInputsTracker';
import { htmlToElement } from '../src/dom_utils';

describe('UnsyncedInputContainer', () => {
    it('returns items added to it', () => {
        const container = new UnsyncedInputContainer();
        const element1 = htmlToElement('<span>element1</span');
        const element2 = htmlToElement('<span>element2</span');
        container.add(element1);
        container.add(element2, 'some_model');

        expect(container.allUnsyncedInputs()).toEqual([element1, element2]);
    });

    it('markModelAsSynced removes unsynced models but not fields', () => {
        const container = new UnsyncedInputContainer();
        const element1 = htmlToElement('<span>element1</span');
        const element2 = htmlToElement('<span>element2</span');
        const element3 = htmlToElement('<span>element3</span');
        container.add(element1);
        container.add(element2, 'some_model2');
        container.add(element3, 'some_model3');

        container.markModelAsSynced('some_model2');

        expect(container.allUnsyncedInputs()).toEqual([element1, element2, element3]);
    });

    it('returns modified models via getModifiedModels()', () => {
        const container = new UnsyncedInputContainer();
        const element1 = htmlToElement('<span>element1</span');
        const element2 = htmlToElement('<span>element2</span');
        const element3 = htmlToElement('<span>element3</span');
        container.add(element1);
        container.add(element2, 'some_model2');
        container.add(element3, 'some_model3');

        container.markModelAsSynced('some_model2');
        expect(container.getUnsyncedModelNames()).toEqual(['some_model3'])
    });

    it('resetUnsyncedFields removes all model fields except those unsynced', () => {
        const container = new UnsyncedInputContainer();
        const element1 = htmlToElement('<span>element1</span');
        const element2 = htmlToElement('<span>element2</span');
        const element3 = htmlToElement('<span>element3</span');
        container.add(element1);
        container.add(element2, 'some_model2');
        container.add(element3, 'some_model3');

        container.markModelAsSynced('some_model2');

        container.resetUnsyncedFields();
        expect(container.allUnsyncedInputs()).toEqual([element1, element3]);
    });
});
