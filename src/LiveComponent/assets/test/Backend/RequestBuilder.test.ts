import RequestBuilder from '../../src/Backend/RequestBuilder';

describe('buildRequest', () => {
    it('sets basic data on GET request', () => {
        const builder = new RequestBuilder('/_components?existing_param=1', '_the_csrf_token');
        const { url, fetchOptions } = builder.buildRequest(
            { firstName: 'Ryan' },
            [],
            { firstName: 'Kevin' },
            { 'child-component': '123' }
        );

        expect(url).toEqual('/_components?existing_param=1&props=%7B%22firstName%22%3A%22Ryan%22%7D&updated=%7B%22firstName%22%3A%22Kevin%22%7D&childrenFingerprints=%7B%22child-component%22%3A%22123%22%7D');
        expect(fetchOptions.method).toEqual('GET');
        expect(fetchOptions.headers).toEqual({
            Accept: 'application/vnd.live-component+html',
        });
    });

    it('sets basic data on POST request', () => {
        const builder = new RequestBuilder('/_components', '_the_csrf_token');
        const { url, fetchOptions } = builder.buildRequest(
            { firstName: 'Ryan' },
            [{
                name: 'saveData',
                args: { sendNotification: '1' },
            }],
            { firstName: 'Kevin' },
            { 'child-component': '123' }
        );

        expect(url).toEqual('/_components/saveData');
        expect(fetchOptions.method).toEqual('POST');
        expect(fetchOptions.headers).toEqual({
            Accept: 'application/vnd.live-component+html',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '_the_csrf_token',
        });
        expect(fetchOptions.body).toEqual(JSON.stringify({
            props: { firstName: 'Ryan' },
            updated: { firstName: 'Kevin' },
            childrenFingerprints: { 'child-component': '123' },
            args: { sendNotification: '1' },
        }));
    });

    it('sets basic data on POST request with batch actions', () => {
        const builder = new RequestBuilder('/_components', '_the_csrf_token');
        const { url, fetchOptions } = builder.buildRequest(
            { firstName: 'Ryan' },
            [{
                name: 'saveData',
                args: { sendNotification: '1' },
            }, {
                name: 'saveData',
                args: { sendNotification: '0' },
            }],
            { firstName: 'Kevin' },
            {}
        );

        expect(url).toEqual('/_components/_batch');
        expect(fetchOptions.method).toEqual('POST');
        expect(fetchOptions.body).toEqual(JSON.stringify({
            props: { firstName: 'Ryan' },
            updated: { firstName: 'Kevin' },
            actions: [{
                name: 'saveData',
                args: { sendNotification: '1' },
            }, {
                name: 'saveData',
                args: { sendNotification: '0' },
            }],
        }));
    });

    // when data is too long it makes a post request
    it('makes a POST request when data is too long', () => {
        const builder = new RequestBuilder('/_components', '_the_csrf_token');
        const { url, fetchOptions } = builder.buildRequest(
            { firstName: 'Ryan'.repeat(1000) },
            [],
            { firstName: 'Kevin'.repeat(1000) },
            {}
        );

        expect(url).toEqual('/_components');
        expect(fetchOptions.method).toEqual('POST');
        expect(fetchOptions.headers).toEqual({
            // no token
            Accept: 'application/vnd.live-component+html',
            'Content-Type': 'application/json',
        });
        expect(fetchOptions.body).toEqual(JSON.stringify({
            props: { firstName: 'Ryan'.repeat(1000) },
            updated: { firstName: 'Kevin'.repeat(1000) },
        }));
    });
});
