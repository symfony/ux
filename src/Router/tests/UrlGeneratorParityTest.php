<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\UX\Router\JavaScriptRegexConverter;
use Symfony\UX\Router\RouteNormalizer;

final class UrlGeneratorParityTest extends TestCase
{
    private const FIXTURE = __DIR__.'/../assets/test/fixtures/parity.json';
    // The JavaScript context always carries a locale (from <html lang>, "en" by default), so the PHP one does too
    private const DEFAULT_CONTEXT = ['baseUrl' => '', 'pathInfo' => '/', 'host' => 'localhost', 'scheme' => 'http', 'httpPort' => 80, 'httpsPort' => 443, 'parameters' => ['_locale' => 'en']];
    private const REFERENCE_TYPES = [
        'path' => UrlGeneratorInterface::ABSOLUTE_PATH,
        'relative' => UrlGeneratorInterface::RELATIVE_PATH,
        'url' => UrlGeneratorInterface::ABSOLUTE_URL,
        'network' => UrlGeneratorInterface::NETWORK_PATH,
    ];

    public function testFixtureIsUpToDate(): void
    {
        $routes = self::createRoutes();
        $normalizer = new RouteNormalizer();
        $fixture = ['routes' => [], 'cases' => []];

        foreach ($routes->all() as $name => $route) {
            $fixture['routes'][$name] = $normalizer->normalize($route);
        }

        foreach (self::provideCases() as $description => [$name, $parameters, $referenceType, $context]) {
            $context = array_replace(self::DEFAULT_CONTEXT, $context);
            $fixture['cases'][] = [
                'description' => $description,
                'name' => $name,
                'parameters' => (object) $parameters,
                'referenceType' => $referenceType,
                'context' => ['parameters' => (object) $context['parameters']] + $context,
            ] + self::generate($routes, $name, $parameters, $referenceType, $context);
        }

        $json = json_encode($fixture, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR)."\n";

        if (getenv('UPDATE_FIXTURES')) {
            file_put_contents(self::FIXTURE, $json);
        }

        self::assertStringEqualsFile(self::FIXTURE, $json, 'Run "UPDATE_FIXTURES=1 vendor/bin/phpunit" to update the fixture.');
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, string, array<string, mixed>}>
     */
    private static function provideCases(): iterable
    {
        yield 'static path' => ['static', [], 'path', []];
        yield 'static url' => ['static', [], 'url', []];
        yield 'static network path' => ['static', [], 'network', []];
        yield 'relative path' => ['static', [], 'relative', ['pathInfo' => '/blog/foo']];
        yield 'relative path to the current page' => ['static', [], 'relative', ['pathInfo' => '/static/path']];
        yield 'relative path to a parent directory' => ['blog_list', [], 'relative', ['pathInfo' => '/blog/2/comments']];
        yield 'relative path starting with a colon segment' => ['colon', [], 'relative', ['pathInfo' => '/']];
        yield 'variable' => ['blog_show', ['slug' => 'hello'], 'path', []];
        yield 'missing mandatory parameter' => ['blog_show', [], 'path', []];
        yield 'missing host and path parameters' => ['host_and_path', [], 'path', []];
        yield 'null mandatory parameter' => ['blog_show', ['slug' => null], 'path', []];
        yield 'optional variable omitted' => ['blog_list', [], 'path', []];
        yield 'optional variable given' => ['blog_list', ['page' => 2], 'path', []];
        yield 'optional variable equal to its default' => ['blog_list', ['page' => '1'], 'path', []];
        yield 'optional variable set to null' => ['blog_list', ['page' => null], 'path', []];
        yield 'optional variable set to zero' => ['blog_list', ['page' => 0], 'path', []];
        yield 'requirement not met' => ['blog_list', ['page' => 'abc'], 'path', []];
        yield 'default requirement not met' => ['blog_show', ['slug' => ''], 'path', []];
        yield 'trailing optional variables' => ['archive', ['month' => 3], 'path', []];
        yield 'all optional variables omitted' => ['archive', [], 'path', []];
        yield 'important variable' => ['important', [], 'path', []];
        yield 'utf-8 variable' => ['category', ['name' => 'café'], 'path', []];
        yield 'utf-8 word requirement' => ['word', ['name' => 'élan'], 'path', []];
        yield 'utf-8 digit requirement' => ['number', ['value' => '٣٤'], 'path', []];
        yield 'look-around requirement ignored' => ['lookahead', ['id' => 'new'], 'path', []];
        yield 'localized route from context locale' => ['about', [], 'path', ['parameters' => ['_locale' => 'fr']]];
        yield 'localized route from parameter' => ['about', ['_locale' => 'en'], 'path', ['parameters' => ['_locale' => 'fr']]];
        yield 'localized route with region fallback' => ['about', [], 'path', ['parameters' => ['_locale' => 'fr_CA']]];
        yield 'localized variant called directly' => ['about.fr', [], 'path', []];
        yield 'localized route without matching locale' => ['about', ['_locale' => 'de'], 'path', []];
        yield 'locale variable from context' => ['locale_home', [], 'path', ['parameters' => ['_locale' => 'fr']]];
        yield 'locale variable from parameter' => ['locale_home', ['_locale' => 'en'], 'path', ['parameters' => ['_locale' => 'fr']]];
        yield 'locale variable not met' => ['locale_home', ['_locale' => 'de'], 'path', []];
        yield 'locale on a non-localized route' => ['static', ['_locale' => 'fr'], 'path', []];
        yield 'locale equal to a non-variable default' => ['french', ['_locale' => 'fr'], 'path', []];
        yield 'locale different from a non-variable default' => ['french', ['_locale' => 'en'], 'path', []];
        yield 'host with default variable' => ['host', [], 'url', []];
        yield 'host differing from context' => ['host', [], 'path', []];
        yield 'host matching context' => ['host', [], 'path', ['host' => 'www.example.com']];
        yield 'host variable' => ['host', ['sub' => 'm'], 'path', []];
        yield 'host requirement not met' => ['host', ['sub' => 'api'], 'path', []];
        yield 'required scheme from another scheme' => ['secure', [], 'path', []];
        yield 'required scheme from the same scheme' => ['secure', [], 'path', ['scheme' => 'https']];
        yield 'custom http port' => ['static', [], 'url', ['httpPort' => 8080]];
        yield 'custom https port' => ['static', [], 'url', ['scheme' => 'https', 'httpsPort' => 8443]];
        yield 'base url' => ['static', [], 'path', ['baseUrl' => '/app.php']];
        yield 'base url in absolute url' => ['static', [], 'url', ['baseUrl' => '/sub']];
        yield 'default fragment' => ['fragment', [], 'path', []];
        yield 'fragment parameter' => ['static', ['_fragment' => 'a b/c?d@e'], 'path', []];
        yield 'query parameter' => ['static', ['_query' => ['sort' => 'desc', 'page' => 2]], 'path', []];
        yield 'extra parameters' => ['blog_show', ['slug' => 'hello', 'foo' => 'bar', 'empty' => null], 'path', []];
        yield 'nested extra parameters' => ['static', ['filters' => ['tags' => ['a', 'b'], 'author' => 'me']], 'path', []];
        yield 'boolean extra parameters' => ['static', ['yes' => true, 'no' => false], 'path', []];
        yield 'special characters in query' => ['static', ['q' => 'a b&c=d/e?f@g:h!i;j,k*l+m#n'], 'path', []];
        yield 'special characters in path' => ['files', ['path' => "a b/c@d:e;f,g=h+i!j*k|l'm(n)o~p#q?r"], 'path', []];
        yield 'dot segments' => ['files', ['path' => 'a/../b/./c'], 'path', []];
        yield 'single dot segment' => ['files', ['path' => '.'], 'path', []];
        yield 'encoded slash stays encoded' => ['files', ['path' => 'a%2Fb'], 'path', []];
        yield 'unknown route' => ['unknown', [], 'path', []];
    }

    private static function createRoutes(): RouteCollection
    {
        $routes = new RouteCollection();
        $routes->add('static', new Route('/static/path'));
        $routes->add('colon', new Route('/x:y'));
        $routes->add('blog_show', new Route('/blog/{slug}'));
        $routes->add('blog_list', new Route('/blog/{page}', ['page' => 1], ['page' => '\d+']));
        $routes->add('archive', new Route('/archive/{year}/{month}', ['year' => 2026, 'month' => 1]));
        $routes->add('important', new Route('/important/{!page}', ['page' => 1]));
        $routes->add('category', new Route('/category/{name}', [], [], ['utf8' => true]));
        $routes->add('word', new Route('/word/{name}', [], ['name' => '\w+'], ['utf8' => true]));
        $routes->add('number', new Route('/number/{value}', [], ['value' => '\d+'], ['utf8' => true]));
        $routes->add('lookahead', new Route('/look/{id}', [], ['id' => '(?!new)\w+']));
        $routes->add('about.en', new Route('/about', ['_locale' => 'en', '_canonical_route' => 'about'], ['_locale' => 'en']));
        $routes->add('about.fr', new Route('/a-propos', ['_locale' => 'fr', '_canonical_route' => 'about'], ['_locale' => 'fr']));
        $routes->add('locale_home', new Route('/{_locale}/home', [], ['_locale' => 'en|fr']));
        $routes->add('french', new Route('/french', ['_locale' => 'fr']));
        $routes->add('host', new Route('/host', ['sub' => 'www'], ['sub' => 'www|m'], [], '{sub}.example.com'));
        $routes->add('host_and_path', new Route('/users/{id}', [], [], [], '{tenant}.example.com'));
        $routes->add('secure', new Route('/secure', [], [], [], '', ['https']));
        $routes->add('fragment', new Route('/fragment', ['_fragment' => 'top']));
        $routes->add('files', new Route('/files/{path}', [], ['path' => '.+']));

        return $routes;
    }

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $context
     *
     * @return array{expected: string}|array{error: array{name: string, message: string}}
     */
    private static function generate(RouteCollection $routes, string $name, array $parameters, string $referenceType, array $context): array
    {
        $requestContext = new RequestContext($context['baseUrl'], 'GET', $context['host'], $context['scheme'], $context['httpPort'], $context['httpsPort'], $context['pathInfo']);
        $requestContext->setParameters($context['parameters']);

        try {
            return ['expected' => new UrlGenerator($routes, $requestContext)->generate($name, $parameters, self::REFERENCE_TYPES[$referenceType])];
        } catch (RouteNotFoundException $e) {
            return ['error' => ['name' => 'RouteNotFoundError', 'message' => $e->getMessage()]];
        } catch (MissingMandatoryParametersException $e) {
            return ['error' => ['name' => 'MissingMandatoryParametersError', 'message' => $e->getMessage()]];
        } catch (InvalidParameterException $e) {
            // JavaScript reports the requirement it actually checked, i.e. the converted one
            preg_match('/for route "([^"]+)"/', $e->getMessage(), $route);
            $utf8 = (bool) $routes->get($route[1])?->getOption('utf8');
            $message = preg_replace_callback('/must match "(.*)" \("/s', static fn (array $m) => 'must match "'.(JavaScriptRegexConverter::convert($m[1], $utf8) ?? $m[1]).'" ("', $e->getMessage());

            return ['error' => ['name' => 'InvalidParameterError', 'message' => $message]];
        }
    }
}
