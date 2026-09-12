import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';

// Serve the built package with real Turbo and Stimulus. No import map or bundler is used by the page.
const files = new Map([
    ['/_ux/inspector.js', new URL('../../dist/inspector.js', import.meta.url)],
    [
        '/turbo.js',
        new URL('../../../../Turbo/assets/node_modules/@hotwired/turbo/dist/turbo.es2017-esm.js', import.meta.url),
    ],
    [
        '/stimulus.js',
        new URL('../../../../LiveComponent/assets/node_modules/@hotwired/stimulus/dist/stimulus.js', import.meta.url),
    ],
    ['/app.js', new URL('./app.js', import.meta.url)],
]);
const html = await readFile(new URL('./page.html', import.meta.url));
createServer(async (request, response) => {
    const pathname = new URL(request.url, 'http://localhost').pathname;
    const file = files.get(pathname);
    try {
        if (file) {
            response.writeHead(200, { 'Content-Type': 'text/javascript' });
            response.end(await readFile(file));
        } else if (pathname === '/favicon.ico') {
            response.writeHead(204).end();
        } else if (pathname === '/frame') {
            response.writeHead(200, { 'Content-Type': 'text/html' });
            response.end(
                '<turbo-frame id="frame"><section id="frame-probe" data-controller="probe">Loaded frame</section></turbo-frame>'
            );
        } else {
            response.writeHead(200, { 'Content-Type': 'text/html' });
            response.end(html);
        }
    } catch (error) {
        console.error(error);
        response.writeHead(500).end();
    }
}).listen(9878, '127.0.0.1');
