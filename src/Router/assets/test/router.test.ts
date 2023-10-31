import {ConfigurationType, generate, getConfiguration, ReferenceType, setConfiguration} from '../src/router';

function overrideContext(context: Partial<ConfigurationType['context']>): void {
    setConfiguration({
        context: {
            ...getConfiguration().context,
            scheme: 'http',
        }
    })
}

describe('Router', function () {
    beforeEach(function () {
        setConfiguration(null);
        overrideContext({
            base_url: '/app.php',
        })
        document.documentElement.removeAttribute('data-symfony-ux-router-configuration');
    })

    describe('generate', function () {
        test('absolute url with port 80', function () {
            const ROUTE = {};

            expect(generate(ROUTE, {}, ReferenceType.ABSOLUTE_URL)).toBe('http://localhost/testing');
        })

        test('absolute secure url with port 443', function () {
            overrideContext({
                scheme: 'https',
            })

            const ROUTE = {};

            expect(generate(ROUTE, {}, ReferenceType.ABSOLUTE_URL)).toBe('https://localhost/testing');
        });

        test('absolute url with non standard port', function () {
            overrideContext({
                http_port: 8080,
            })

            const ROUTE = {};

            expect(generate(ROUTE, {}, ReferenceType.ABSOLUTE_URL)).toBe('http://localhost:8080/testing');
        });

        test('absolute secure url with non standard port', function () {
            overrideContext({
                https_port: 8080,
                scheme: 'https',
            })

            const ROUTE = {};

            expect(generate(ROUTE, {}, ReferenceType.ABSOLUTE_URL)).toBe('https://localhost:8080/testing');
        });

        test('relative url without parameters', function () {
            const ROUTE = {};

            expect(generate(ROUTE, {}, ReferenceType.ABSOLUTE_PATH)).toBe('/app.php/testing');
        });

        test('relative url with parameter', function () {
            const ROUTE = {};

            expect(generate(ROUTE, {foo: 'bar'}, ReferenceType.ABSOLUTE_PATH)).toBe('/app.php/testing/bar');
        });

        test('relative url with null parameter', function () {
            const ROUTE = {};

            expect(generate(ROUTE, {format: null}, ReferenceType.ABSOLUTE_PATH)).toBe('/app.php/testing/bar');
        });

        test('relative url with null parameter but not optional', function () {
            const ROUTE = {};

            expect(
                generate(ROUTE, {foo: null}, ReferenceType.ABSOLUTE_PATH)
            ).toThrowError('truc');
        });

        test('relative url with optional zero parameter', function () {
            const ROUTE = {};

            expect(generate(ROUTE, {foo: 0}, ReferenceType.ABSOLUTE_PATH)).toBe('/app.php/testing/0');
        });

        test('not passed optional parameter in between', function () {
            const ROUTE = {};

            expect(generate(ROUTE, {page: 1}, ReferenceType.ABSOLUTE_PATH)).toBe('/app.php/index/1');
            expect(generate(ROUTE, {}, ReferenceType.ABSOLUTE_PATH)).toBe('/app.php/');
        });

        test('relative url with extra parameters', function () {
            const ROUTE = {};

        });

        test('absolute url with extra parameters', function () {
            const ROUTE = {};

        });

        test('url with extra parameters from globals', function () {
            const ROUTE = {};

        });

        test('url with global parameter', function () {
            const ROUTE = {};

        });

        test('global parameter has higher priority than default', function () {
            const ROUTE = {};

        });

        test('generate with default locale', function () {
            const ROUTE = {};

        });

        test('generate with overridden parameter locale', function () {
            const ROUTE = {};

        });

        test('generate with overridden parameter locale from request context', function () {
            const ROUTE = {};

        });

        test('dump with localized routes preserve the good locale in the url', function () {
            const ROUTE = {};

        });

        test('generate without routes', function () {
            const ROUTE = {};

        });

        test('generate with invalid locale', function () {
            const ROUTE = {};

        });

        test('legacy throwing missing mandatory parameters', function () {
            const ROUTE = {};

        });

        test('legacy throwing missing mandatory parameters with all parameters', function () {
            const ROUTE = {};

        });

        test('generate for route without mandatory parameter', function () {
            const ROUTE = {};

        });

        test('generate for route with invalid optional parameter', function () {
            const ROUTE = {};

        });

        test('generate for route with invalid parameter', function () {
            const ROUTE = {};

        });

        test('generate for route with invalid optional parameter non strict', function () {
            const ROUTE = {};

        });

        test('generate for route with invalid optional parameter non strict with logger', function () {
            const ROUTE = {};

        });

        test('generate for route with invalid parameter but disabled requirements check', function () {
            const ROUTE = {};

        });

        test('generate for route with invalid mandatory parameter', function () {
            const ROUTE = {};

        });

        test('generate for route with invalid utf 8 parameter', function () {
            const ROUTE = {};

        });

        test('required param and empty passed', function () {
            const ROUTE = {};

        });

        test('scheme requirement does nothing if same current scheme', function () {
            const ROUTE = {};

        });

        test('scheme requirement forces absolute url', function () {
            const ROUTE = {};

        });

        test('scheme requirement creates url for first required scheme', function () {
            const ROUTE = {};

        });

        test('path with two starting slashes', function () {
            const ROUTE = {};

        });

        test('no trailing slash for multiple optional parameters', function () {
            const ROUTE = {};

        });

        test('with an integer as adefault value', function () {
            const ROUTE = {};

        });

        test('null for optional parameter is ignored', function () {
            const ROUTE = {};

        });

        test('query param same as default', function () {
            const ROUTE = {};

        });

        test('array query param same as default', function () {
            const ROUTE = {};

        });

        test('generate with special route name', function () {
            const ROUTE = {};

        });

        test('url encoding', function () {
            const ROUTE = {};

        });

        test('encoding of relative path segments', function () {
            const ROUTE = {};

        });

        test('encoding of slash in path', function () {
            const ROUTE = {};

        });

        test('encoding of slash in query parameters', function () {
            const ROUTE = {};

        });

        test('adjacent variables', function () {
            const ROUTE = {};

        });

        test('optional variable with no real separator', function () {
            const ROUTE = {};

        });

        test('required variable with no real separator', function () {
            const ROUTE = {};

        });

        test('default requirement of variable', function () {
            const ROUTE = {};

        });

        test('important variable', function () {
            const ROUTE = {};

        });

        test('important variable with no default', function () {
            const ROUTE = {};

        });

        test('default requirement of variable disallows slash', function () {
            const ROUTE = {};

        });

        test('default requirement of variable disallows next separator', function () {
            const ROUTE = {};

        });

        test('with host different from context', function () {
            const ROUTE = {};

        });

        test('with host same as context', function () {
            const ROUTE = {};

        });

        test('with host same as context and absolute', function () {
            const ROUTE = {};

        });

        test('url with invalid parameter in host', function () {
            const ROUTE = {};

        });

        test('url with invalid parameter in host when param has adefault value', function () {
            const ROUTE = {};

        });

        test('url with invalid parameter equals default value in host', function () {
            const ROUTE = {};

        });

        test('url with invalid parameter in host in non strict mode', function () {
            const ROUTE = {};

        });

        test('host is case insensitive', function () {
            const ROUTE = {};

        });

        test('default host is used when context host is empty', function () {
            const ROUTE = {};

        });

        test('default host is used when context host is empty and path reference type', function () {
            const ROUTE = {};

        });

        test('absolute url fallback to path if host is empty and scheme is http', function () {
            const ROUTE = {};

        });

        test('absolute url fallback to network if scheme is empty and host is not', function () {
            const ROUTE = {};

        });

        test('absolute url fallback to path if scheme and host are empty', function () {
            const ROUTE = {};

        });

        test('absolute url with non http scheme and empty host', function () {
            const ROUTE = {};

        });

        test('generate network path', function () {
            const ROUTE = {};

        });

        test('generate relative path', function () {
            const ROUTE = {};

        });

        test('aliases', function () {
            const ROUTE = {};

        });

        test('alias which target route doesnt exist', function () {
            const ROUTE = {};

        });

        test('deprecated alias', function () {
            const ROUTE = {};

        });

        test('deprecated alias with custom message', function () {
            const ROUTE = {};

        });

        test('targetting adeprecated alias should trigger deprecation', function () {
            const ROUTE = {};

        });

        test('circular reference should throw an exception', function () {
            const ROUTE = {};

        });

        test('deep circular reference should throw an exception', function () {
            const ROUTE = {};

        });

        test('indirect circular reference should throw an exception', function () {
            const ROUTE = {};

        });

        test('get relative path', function () {
            const ROUTE = {};

        });

        test('fragments can be appended to urls', function () {
            const ROUTE = {};

        });

        test('fragments do not escape valid characters', function () {
            const ROUTE = {};

        });

        test('fragments can be defined as defaults', function () {
            const ROUTE = {};

        });

        test('look round requirements in path', function () {
            const ROUTE = {};

        });

        test('utf 8 var name', function () {
            const ROUTE = {};

        });

    })
});
