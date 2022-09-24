import ValueStore from '../src/Component/ValueStore';

describe('ValueStore', () => {
    it('get() returns simple data', () => {
        const container = new ValueStore({
            firstName: 'Ryan'
        });

        expect(container.get('firstName')).toEqual('Ryan');
    });

    it('get() returns undefined if not set', () => {
        const container = new ValueStore({});

        expect(container.get('firstName')).toBeUndefined();
    });

    it('get() returns deep data from property path', () => {
        const container = new ValueStore({
            user: {
                firstName: 'Ryan'
            }
        });

        expect(container.get('user.firstName')).toEqual('Ryan');
    });

    it('has() returns true if path exists', () => {
        const container = new ValueStore({
            user: {
                firstName: 'Ryan'
            }
        });

        expect(container.has('user.firstName')).toBeTruthy();
    });

    it('has() returns false if path does not exist', () => {
        const container = new ValueStore({
            user: {
                firstName: 'Ryan'
            }
        });

        expect(container.has('user.lastName')).toBeFalsy();
    });

    it('set() overrides simple data', () => {
        const container = new ValueStore({
            firstName: 'Kevin'
        });

        container.set('firstName', 'Ryan');

        expect(container.get('firstName')).toEqual('Ryan');
    });

    it('set() overrides deep data', () => {
        const container = new ValueStore({
            user: {
                firstName: 'Ryan'
            }
        });

        container.set('user.firstName', 'Kevin');

        expect(container.get('user.firstName')).toEqual('Kevin');
    });

    it('set() errors if setting key that does not exist', () => {
        const container = new ValueStore({});

        expect(() => {
            container.set('firstName', 'Ryan');
        }).toThrow('was never initialized');
    });

    it('set() errors if setting deep data without parent', () => {
        const container = new ValueStore({});

        expect(() => {
            container.set('user.firstName', 'Ryan');
        }).toThrow('The parent "user" data does not exist');
    });

    it('set() errors if setting deep data that does not exist', () => {
        const container = new ValueStore({
            user: {}
        });

        expect(() => {
            container.set('user.firstName', 'Ryan');
        }).toThrow('was never initialized');
    });

    it('set() errors if setting deep data on a non-object', () => {
        const container = new ValueStore({
            user: 'Kevin'
        });

        expect(() => {
            container.set('user.firstName', 'Ryan');
        }).toThrow('The parent "user" data does not appear to be an object');
    });
});
