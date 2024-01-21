import RequestBuilder from '../../src/Backend/RequestBuilder';

describe('buildRequest', () => {
    it('sets basic data on GET request', () => {
        const builder = new RequestBuilder('/_components?existing_param=1', 'get', '_the_csrf_token');
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
            'X-Requested-With': 'XMLHttpRequest',
        });
    });

    it('sets basic data on POST request', () => {
        const builder = new RequestBuilder('/_components', 'post', '_the_csrf_token');
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
            'X-Requested-With': 'XMLHttpRequest',
        });
        const body = <FormData>fetchOptions.body;
        expect(body).toBeInstanceOf(FormData);
        expect(body.get('data')).toEqual(JSON.stringify({
            props: { firstName: 'Ryan' },
            updated: { firstName: 'Kevin' },
            children: { 'child-component': { fingerprint: '123', tag: 'div' } },
            args: { sendNotification: '1' },
        }));
    });

    it('sets basic data on POST request with batch actions', () => {
        const builder = new RequestBuilder('/_components', 'post', '_the_csrf_token');
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
        const body = <FormData>fetchOptions.body;
        expect(body).toBeInstanceOf(FormData);
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
        const builder = new RequestBuilder('/_components', 'get', '_the_csrf_token');
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
            'X-Requested-With': 'XMLHttpRequest',
        });
        const body = <FormData>fetchOptions.body;
        expect(body).toBeInstanceOf(FormData);
        expect(body.get('data')).toEqual(JSON.stringify({
            props: { firstName: 'Ryan'.repeat(1000) },
            updated: { firstName: 'Kevin'.repeat(1000) },
        }));
    });

    it('makes a POST request when method is post', () => {
        const builder = new RequestBuilder('/_components', 'post', '_the_csrf_token');
        const { url, fetchOptions } = builder.buildRequest(
            {
                firstName: 'Ryan'
            },
            [],
            { firstName: 'Kevin' },
            {},
            {},
            {}
        );

        expect(url).toEqual('/_components');
        expect(fetchOptions.method).toEqual('POST');
        expect(fetchOptions.headers).toEqual({
            // no token
            Accept: 'application/vnd.live-component+html',
            'X-Requested-With': 'XMLHttpRequest',
        });
        const body = <FormData>fetchOptions.body;
        expect(body).toBeInstanceOf(FormData);
        expect(body.get('data')).toEqual(JSON.stringify({
            props: {
                firstName: 'Ryan'
            },
            updated: { firstName: 'Kevin' },
        }));
    });

    it('sends propsFromParent when specified', () => {
        const builder = new RequestBuilder('/_components?existing_param=1', 'get', '_the_csrf_token');
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

        const body = <FormData>fetchOptions.body;
        expect(body).toBeInstanceOf(FormData);
        expect(body.get('data')).toEqual(JSON.stringify({
            props: { firstName: 'Ryan' },
            updated: { firstName: 'Kevin' },
            propsFromParent: { count: 5 },
            args: { sendNotification: '1' },
        }));
    });

    // Helper method for FileList mocking
    const getFileList = (length = 1) => {
        const blob = new Blob([''], { type: 'text/html' });
        // @ts-ignore This is a mock and those are needed to mock a File object
        blob['lastModifiedDate'] = '';
        // @ts-ignore This is a mock and those are needed to mock a File object
        blob['name'] = 'filename';
        const file = <File>blob;
        const fileList: FileList = {
            length: length,
            item: () => file
        };
        for (let i= 0; i < length; ++i) {
            fileList[i] = file;
        }
        return fileList;
    };

    it('Sends file with request', () => {
        const builder = new RequestBuilder('/_components', 'post', '_the_csrf_token');

        const { url, fetchOptions } = builder.buildRequest(
            { firstName: 'Ryan' },
            [],
            {},
            {},
            {},
            { 'file': getFileList()}
        );

        expect(url).toEqual('/_components');
        expect(fetchOptions.method).toEqual('POST');
        expect(fetchOptions.headers).toEqual({
            Accept: 'application/vnd.live-component+html',
            'X-CSRF-TOKEN': '_the_csrf_token',
            'X-Requested-With': 'XMLHttpRequest',
        });
        const body = <FormData>fetchOptions.body;
        expect(body).toBeInstanceOf(FormData);
        expect(body.get('file')).toBeInstanceOf(File);
        expect(body.getAll('file').length).toEqual(1);
    });

    it('Sends multiple files with request', () => {
        const builder = new RequestBuilder('/_components', 'post', '_the_csrf_token');

        const { url, fetchOptions } = builder.buildRequest(
            { firstName: 'Ryan' },
            [],
            {},
            {},
            {},
            { 'file[]': getFileList(3), 'otherFile': getFileList()}
        );

        expect(url).toEqual('/_components');
        expect(fetchOptions.method).toEqual('POST');
        expect(fetchOptions.headers).toEqual({
            Accept: 'application/vnd.live-component+html',
            'X-CSRF-TOKEN': '_the_csrf_token',
            'X-Requested-With': 'XMLHttpRequest',
        });
        const body = <FormData>fetchOptions.body;
        expect(body).toBeInstanceOf(FormData);
        expect(body.get('file[]')).toBeInstanceOf(File);
        expect(body.getAll('file[]').length).toEqual(3);
        expect(body.get('otherFile')).toBeInstanceOf(File);
        expect(body.getAll('otherFile').length).toEqual(1);
    });
});
