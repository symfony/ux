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
            // because "@id" exists, it represents the "identity" for "user"
            props: {
                user: {
                    '@id': 111,
                    firstName: 'Ryan'
                }
            },
            name: 'user',
            expected: 111,
        },
        {
            // because NO "@id" exists, the entire object is the identity
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
    ];

    getDataset.forEach(({ props, name, expected }) => {
        it(`get("${name}") with data ${JSON.stringify(props)} returns ${JSON.stringify(expected)}`, () => {
            const store = new ValueStore(props);
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
                user: {
                    firstName: 'Ryan',
                },
            },
            name: 'user.lastName',
            expected: false,
        },
        {
            props: {
                user: {
                    '@id': 111,
                    firstName: 'Ryan',
                },
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
            expected: { user: { firstName: 'Kevin' } },
        },
        {
            props: {
                user: {
                    '@id': 123,
                    firstName: 'Ryan'
                }
            },
            set: 'user',
            to: 456,
            expected: { user: { '@id': 456, firstName: 'Ryan' } },
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
            expect(store.all()).toEqual(expected);
        });
    });

    const setErrorsDataset = [
        {
            props: { },
            set: 'firstName',
            to: 'Ryan',
            throws: 'was never initialized',
        },
        {
            props: { },
            set: 'user.firstName',
            to: 'Ryan',
            throws: 'The parent "user" data does not exist',
        },
        {
            props: { user: {} },
            set: 'user.firstName',
            to: 'Ryan',
            throws: 'was never initialized',
        },
        {
            props: { user: 'Kevin' },
            set: 'user.firstName',
            to: 'Ryan',
            throws: 'The parent "user" data does not appear to be an object',
        },
    ];
    setErrorsDataset.forEach(({ props, set, to, throws }) => {
        it(`set("${set}") with data ${JSON.stringify(props)} throws "${throws}"`, () => {
            const store = new ValueStore(props);
            expect(() => {
                store.set(set, to);
            }).toThrow(throws);
        });
    });

    it('all() returns props', () => {
        const container = new ValueStore(
            { city: 'Grand Rapids', user: 'Kevin' },
        );

        expect(container.all()).toEqual({ city: 'Grand Rapids', user: 'Kevin'});
    });

    const reinitializeProvidedPropsDataset = [
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
                user: {
                    '@id': 123,
                    firstName: 'Ryan',
                }
            },
            newProps: {
                user: {
                    '@id': 456,
                    firstName: 'Kevin',
                }
            },
            expectedProps: {
                user: {
                    '@id': 456,
                    firstName: 'Kevin',
                }
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
                user: {
                    '@id': 123,
                    firstName: 'Ryan',
                }
            },
            // identifier switches from `@id` to object being the identifier
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
                user: {
                    '@id': 123,
                    firstName: 'Ryan',
                }
            },
            // the user has already changed the "firstName" path to "Ryan"
            // now the server responds with the original firstName data
            // that should NOT trigger that this prop "changed". And the
            // writable path should not be updated.
            newProps: {
                user: {
                    '@id': 123,
                    firstName: 'Kevin',
                }
            },
            expectedProps: {
                user: {
                    '@id': 123,
                    firstName: 'Ryan',
                }
            },
            changed: false,
        },
    ];
    reinitializeProvidedPropsDataset.forEach(({ props, newProps, expectedProps, changed }) => {
        it(`reinitializeProvidedProps(${JSON.stringify(newProps)}) with data ${JSON.stringify(props)} results in ${JSON.stringify(expectedProps)}`, () => {
            const store = new ValueStore(props);
            const actualChanged = store.reinitializeProvidedProps(newProps);
            expect(store.all()).toEqual(expectedProps);
            expect(actualChanged).toEqual(changed);
        });
    });
});
