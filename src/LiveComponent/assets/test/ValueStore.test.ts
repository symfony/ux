import ValueStore from '../src/Component/ValueStore';

describe('ValueStore', () => {
    const getDataset = [
        {
            props: { firstName: 'Ryan' },
            name: 'firstName',
            expected: 'Ryan',
        },
        {
            props: {},
            name: 'firstName',
            expected: undefined,
        },
        {
            props: {
                user: {
                    firstName: 'Ryan',
                },
            },
            name: 'user.firstName',
            expected: 'Ryan',
        },
        {
            props: {
                user: 5,
                'user.firstName': 'Ryan',
            },
            name: 'user.firstName',
            expected: 'Ryan',
        },
        {
            props: {
                user: 111,
                'user.FirstName': 'Ryan'
            },
            name: 'user',
            expected: 111,
        },
        {
            props: {
                user: {
                    firstName: 'Ryan',
                },
            },
            name: 'user',
            expected: {
                firstName: 'Ryan',
            },
        },
        {
            props: { firstName: null },
            name: 'firstName',
            expected: null,
        },
        {
            props: { firstName: 'Ryan' },
            updated: [ { prop: 'firstName', value: 'Kevin' }],
            name: 'firstName',
            expected: 'Kevin',
        },
        {
            props: {
                user: {
                    firstName: 'Ryan',
                },
            },
            updated: [ { prop: 'user.firstName', value: 'Kevin' }],
            name: 'user.firstName',
            expected: 'Kevin',
        },
    ];

    getDataset.forEach(({ props, name, expected, updated = [] }) => {
        it(`get("${name}") with data ${JSON.stringify(props)} returns ${JSON.stringify(expected)}`, () => {
            const store = new ValueStore(props);
            updated.forEach(({ prop, value }) => {
                store.set(prop, value);
            });
            expect(store.get(name)).toEqual(expected);
        });
    });

    const hasDataset = [
        {
            props: { firstName: 'Ryan' },
            name: 'firstName',
            expected: true,
        },
        {
            props: { firstName: 'Ryan' },
            name: 'lastName',
            expected: false,
        },
        {
            props: {
                user: {
                    firstName: 'Ryan',
                },
            },
            name: 'user.firstName',
            expected: true,
        },
        {
            props: {
                user: 5,
                'user.firstName': 'Ryan'
            },
            name: 'user.firstName',
            expected: true,
        },
        {
            props: {
                user: {
                    firstName: 'Ryan',
                },
            },
            name: 'user.lastName',
            expected: false,
        },
        {
            props: {
                user: 111,
                'user.firstName': 'Ryan'
            },
            name: 'user',
            expected: true,
        },
        {
            props: {
                user: {
                    firstName: 'Ryan',
                },
            },
            name: 'user',
            expected: true,
        },
        {
            props: { firstName: null },
            name: 'firstName',
            expected: true,
        },
    ];

    hasDataset.forEach(({ props, name, expected }) => {
        it(`has("${name}") with data ${JSON.stringify(props)} returns ${JSON.stringify(expected)}`, () => {
            const store = new ValueStore(props);
            expect(store.has(name)).toEqual(expected);
        });
    });

    const setDataset = [
        {
            props: { firstName: 'Kevin' },
            set: 'firstName',
            to: 'Ryan',
            expected: { firstName: 'Ryan' },
        },
        {
            props: {
                user: {
                    firstName: 'Ryan',
                },
            },
            set: 'user.firstName',
            to: 'Kevin',
            expected: { 'user.firstName': 'Kevin' },
        },
        {
            props: {
                user: 123,
            },
            set: 'user',
            to: 456,
            expected: { user: 456 },
        },
        {
            props: {
                user: {
                    firstName: 'Ryan',
                    lastName: 'Weaver',
                }
            },
            set: 'user',
            to: {
                firstName: 'Kevin',
                lastName: 'Bond',
            },
            expected: { user: { firstName: 'Kevin', lastName: 'Bond' } },
        },
        {
            props: { firstName: null },
            set: 'firstName',
            to: 'Ryan',
            expected: { firstName: 'Ryan' },
        },
    ];

    setDataset.forEach(({ props, set, to, expected }) => {
        it(`set("${set}", ${JSON.stringify(to)}) with data ${JSON.stringify(props)} results in ${JSON.stringify(expected)}`, () => {
            const store = new ValueStore(props);
            store.set(set, to);
            expect(store.getDirtyProps()).toEqual(expected);
        });
    });

    it('correctly tracks pending changes', () => {
        const store = new ValueStore({ firstName: 'Ryan' });

        store.set('firstName', 'Kevin');
        store.flushDirtyPropsToPending();
        expect(store.get('firstName')).toEqual('Kevin');

        // imitate an Ajax failure: new value still exists
        store.pushPendingPropsBackToDirty();
        expect(store.get('firstName')).toEqual('Kevin');

        // imitate an Ajax success (but the server changes the data)
        store.flushDirtyPropsToPending();
        store.reinitializeAllProps({ firstName: 'KEVIN' });
        expect(store.get('firstName')).toEqual('KEVIN');

        // imitate an Ajax success where the value is changed during the request
        store.reinitializeAllProps({ firstName: 'Ryan' });
        store.set('firstName', 'Kevin');
        store.flushDirtyPropsToPending();
        store.set('firstName', 'Wouter');
        expect(store.get('firstName')).toEqual('Wouter');
        // ajax call finishes, the props has updated correctly
        store.reinitializeAllProps({ firstName: 'Kevin' });
        // the updating state still exists
        expect(store.get('firstName')).toEqual('Wouter');
    });


    it('getOriginalProps() returns props', () => {
        const container = new ValueStore(
            { city: 'Grand Rapids', user: 'Kevin', product: 5 },
        );

        expect(container.getOriginalProps()).toEqual({ city: 'Grand Rapids', user: 'Kevin', product: 5 });
    });

    const storeNewPropsFromParentDataset = [
        {
            props: {},
            newProps: {},
            expectedProps: {},
            changed: false,
        },
        {
            props: {
                firstName: 'Ryan',
                lastName: 'Weaver',
            },
            newProps: {
                firstName: 'Kevin',
            },
            expectedProps: {
                firstName: 'Kevin',
                lastName: 'Weaver',
            },
            changed: true,
        },
        {
            props: {
                firstName: 'Ryan',
                lastName: 'Weaver',
            },
            newProps: {
                firstName: 'Ryan',
            },
            expectedProps: {
                firstName: 'Ryan',
                lastName: 'Weaver',
            },
            changed: false,
        },
        {
            props: {
                user: 123,
            },
            newProps: {
                user: 456,
            },
            expectedProps: {
                user: 456,
            },
            changed: true,
        },
        {
            props: {
                user: {
                    firstName: 'Ryan',
                    lastName: 'Weaver',
                }
            },
            newProps: {
                user: {
                    firstName: 'Kevin',
                    lastName: 'Bond',
                },
            },
            expectedProps: {
                user: {
                    firstName: 'Kevin',
                    lastName: 'Bond',
                },
            },
            changed: true,
        },
        {
            props: {
                user: 123,
            },
            // identifier switches to an array identifier
            newProps: {
                user: {
                    firstName: 'Kevin',
                    lastName: 'Bond',
                },
            },
            expectedProps: {
                user: {
                    firstName: 'Kevin',
                    lastName: 'Bond',
                },
            },
            changed: true,
        },
    ];
    storeNewPropsFromParentDataset.forEach(({ props, newProps, expectedProps, changed }) => {
        it(`storeNewPropsFromParent(${JSON.stringify(newProps)}) with data ${JSON.stringify(props)} results in ${JSON.stringify(expectedProps)}`, () => {
            const store = new ValueStore(props);
            const actualChanged = store.storeNewPropsFromParent(newProps);
            expect(actualChanged).toEqual(changed);
            expect(store.getUpdatedPropsFromParent()).toEqual(changed ? newProps : {});
        });
    });
});
