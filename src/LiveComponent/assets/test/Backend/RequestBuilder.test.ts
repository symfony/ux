import RequestBuilder from '../../src/Backend/RequestBuilder';

describe('buildRequest', () => {
    it('sets basic data on GET request', () => {
        const builder = new RequestBuilder('/_components?existing_param=1', '_the_csrf_token');
        const { url, fetchOptions } = builder.buildRequest(
            { firstName: 'Ryan' },
            [],
            { firstName: 'Kevin' },
            { 'child-component': {fingerprint: '123', tag: 'div' } },
            {},
            {}
        );

        expect(url).toEqual('/_components?existing_param=1&props=%7B%22firstName%22%3A%22Ryan%22%7D&updated=%7B%22firstName%22%3A%22Kevin%22%7D&children=%7B%22child-component%22%3A%7B%22fingerprint%22%3A%22123%22%2C%22tag%22%3A%22div%22%7D%7D');
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
            { 'child-component': {fingerprint: '123', tag: 'div' } },
            {},
            {}
        );

        expect(url).toEqual('/_components/saveData');
        expect(fetchOptions.method).toEqual('POST');
        expect(fetchOptions.headers).toEqual({
            Accept: 'application/vnd.live-component+html',
            'X-CSRF-TOKEN': '_the_csrf_token',
        });
        const body = fetchOptions.body;
        expect(body).toBeInstanceOf(FormData);
        // @ts-ignore body is already asserted to be FormData
        expect(body.get('data')).toEqual(JSON.stringify({
            props: { firstName: 'Ryan' },
            updated: { firstName: 'Kevin' },
            children: { 'child-component': { fingerprint: '123', tag: 'div' } },
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
            {},
            {},
            {}
        );

        expect(url).toEqual('/_components/_batch');
        expect(fetchOptions.method).toEqual('POST');
        const body = fetchOptions.body;
        expect(body).toBeInstanceOf(FormData);
        // @ts-ignore body is already asserted to be FormData
        expect(body.get('data')).toEqual(JSON.stringify({
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
            {},
            {},
            {}
        );

        expect(url).toEqual('/_components');
        expect(fetchOptions.method).toEqual('POST');
        expect(fetchOptions.headers).toEqual({
            // no token
            Accept: 'application/vnd.live-component+html',
        });
        const body = fetchOptions.body;
        expect(body).toBeInstanceOf(FormData);
        // @ts-ignore body is already asserted to be FormData
        expect(body.get('data')).toEqual(JSON.stringify({
            props: { firstName: 'Ryan'.repeat(1000) },
            updated: { firstName: 'Kevin'.repeat(1000) },
        }));
    });

    it('sends propsFromParent when specified', () => {
        const builder = new RequestBuilder('/_components?existing_param=1', '_the_csrf_token');
        const { url } = builder.buildRequest(
            { firstName: 'Ryan' },
            [],
            { firstName: 'Kevin' },
            { },
            { count: 5 },
            {}
        );

        expect(url).toEqual('/_components?existing_param=1&props=%7B%22firstName%22%3A%22Ryan%22%7D&updated=%7B%22firstName%22%3A%22Kevin%22%7D&propsFromParent=%7B%22count%22%3A5%7D');

        // do a POST
        const { fetchOptions } = builder.buildRequest(
            { firstName: 'Ryan' },
            [{
                name: 'saveData',
                args: { sendNotification: '1' },
            }],
            { firstName: 'Kevin' },
            {},
            { count: 5 },
            {}
        );

        const body = fetchOptions.body;
        expect(body).toBeInstanceOf(FormData);
        // @ts-ignore body is already asserted to be FormData
        expect(body.get('data')).toEqual(JSON.stringify({
            props: { firstName: 'Ryan' },
            updated: { firstName: 'Kevin' },
            propsFromParent: { count: 5 },
            args: { sendNotification: '1' },
        }));
    });
});
