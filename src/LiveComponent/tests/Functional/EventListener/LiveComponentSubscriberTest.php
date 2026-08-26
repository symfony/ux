<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\EventListener;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\Persistence\persist;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentSubscriberTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use LiveComponentTestHelper;
    use ResetDatabase;

    /**
     * The deterministic id of the "component2" component in render_embedded_with_blocks.html.twig.
     * If that template changes, this will need to be updated.
     */
    public const DETERMINISTIC_ID = 21098427781;
    /**
     * The deterministic id of the "component2" component in render_multiple_embedded_with_blocks.html.twig.
     * If that template changes, this will need to be updated.
     */
    public const DETERMINISTIC_ID_MULTI_2 = 30904230242;

    public function testCanRenderComponentAsHtml()
    {
        $component = $this->mountComponent('component1', [
            'prop1' => $entity = persist(Entity1::class),
            'prop2' => $date = new \DateTime('2021-03-05 9:23'),
            'prop3' => 'value3',
            'prop4' => 'value4',
        ]);

        $dehydrated = $this->dehydrateComponent($component);

        $this->browser()
            ->throwExceptions()
            ->post('/_components/component1', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Prop1: '.$entity->id)
            ->assertContains('Prop2: 2021-03-05 9:23')
            ->assertContains('Prop3: value3')
            ->assertContains('Prop4: (none)')
        ;
    }

    public function testCanRenderComponentAsHtmlWithAlternateRoute()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('alternate_route'));

        $this->browser()
            ->throwExceptions()
            ->post('/alt/alternate_route', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertOn('/alt/alternate_route', parts: ['path'])
            ->assertContains('From alternate route. (count: 0)')
        ;
    }

    #[Group('transient-on-windows')]
    public function testCanExecuteComponentActionNormalRoute()
    {
        $templateName = 'render_embedded_with_blocks.html.twig';
        $obscuredName = '4bd9245af4594aa28cb77583c29e188e';
        $this->addTemplateMap($obscuredName, $templateName);

        $dehydrated = $this->dehydrateComponent(
            $this->mountComponent(
                'component2',
                [
                    'data-host-template' => $obscuredName,
                    'data-embedded-template-index' => self::DETERMINISTIC_ID,
                ]
            )
        );

        $this->browser()
            ->throwExceptions()
            ->post('/_components/component2', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 1')
            ->post('/_components/component2/increase', [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Count: 2')
            ->assertSee('Embedded content with access to context, like count=2')
        ;
    }

    public function testCanExecuteComponentActionWithAlternateRoute()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('alternate_route'));

        $this->browser()
            ->throwExceptions()
            ->post('/alt/alternate_route', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertContains('count: 0')
            ->post('/alt/alternate_route/increase', [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertSuccessful()
            ->assertOn('/alt/alternate_route/increase')
            ->assertContains('count: 1')
        ;
    }

    public function testCannotExecuteComponentActionForGetRequest()
    {
        $this->browser()
            ->get('/_components/component2/increase')
            ->assertStatus(405)
        ;
    }

    public function testCannotExecuteComponentDefaultActionForGetRequestWhenMethodIsPost()
    {
        $this->browser()
            ->get('/_components/with_method_post/__invoke')
            ->assertStatus(405)
        ;
    }

    public function testPreReRenderHookOnlyExecutedDuringAjax()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('component2'));

        $this->browser()
            ->visit('/render-template/render_component2')
            ->assertSuccessful()
            ->assertSee('PreReRenderCalled: No')
            ->post('/_components/component2', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertSee('PreReRenderCalled: Yes')
        ;
    }

    #[Group('transient-on-windows')]
    public function testItAddsEmbeddedTemplateContextToEmbeddedComponents()
    {
        $templateName = 'render_embedded_with_blocks.html.twig';
        $obscuredName = '1918f197faab43278ba06c0a672a2b97';
        $this->addTemplateMap($obscuredName, $templateName);

        $dehydrated = $this->dehydrateComponent(
            $this->mountComponent(
                'component2',
                [
                    'data-host-template' => $obscuredName,
                    'data-embedded-template-index' => self::DETERMINISTIC_ID,
                ]
            )
        );

        $this->browser()
            ->visit('/render-template/render_embedded_with_blocks')
            ->assertSuccessful()
            ->assertSee('PreReRenderCalled: No')
            ->assertSee('Embedded content with access to context, like count=1')
            ->assertSeeElement('.component2')
            ->assertElementAttributeContains('.component2', 'data-live-props-value', '"data-host-template":"'.$obscuredName.'"')
            ->assertElementAttributeContains('.component2', 'data-live-props-value', '"data-embedded-template-index":'.self::DETERMINISTIC_ID)
            ->post('/_components/component2', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertSee('PreReRenderCalled: Yes')
            ->assertSee('Embedded content with access to context, like count=1')
        ;
    }

    #[Group('transient-on-windows')]
    public function testItWorksWithNamespacedTemplateNamesForEmbeddedComponents()
    {
        $templateName = 'render_embedded_with_blocks.html.twig';
        $obscuredName = 'fb7992f74bbb43c08e47b7cf5c880edb';
        $this->addTemplateMap($obscuredName, $templateName);

        $this->browser()
            ->visit('/render-namespaced-template/render_embedded_with_blocks')
            ->assertSuccessful()
            ->assertElementAttributeContains('.component2', 'data-live-props-value', '"data-host-template":"'.$obscuredName.'"')
        ;
    }

    #[Group('transient-on-windows')]
    public function testItUseBlocksFromEmbeddedContextUsingMultipleComponents()
    {
        $templateName = 'render_multiple_embedded_with_blocks.html.twig';
        $obscuredName = '5c474b02358c46cca3da7340cc79cc2e';

        $this->addTemplateMap($obscuredName, $templateName);

        $dehydrated = $this->dehydrateComponent(
            $this->mountComponent(
                'component2',
                [
                    'data-host-template' => $obscuredName,
                    'data-embedded-template-index' => self::DETERMINISTIC_ID_MULTI_2,
                ]
            )
        );

        $this->browser()
            ->visit('/render-template/render_multiple_embedded_with_blocks')
            ->assertSuccessful()
            ->assertSeeIn('#component1', 'Overridden content from component 1')
            ->assertSeeIn('#component2', 'Overridden content from component 2 on same line - count: 1')
            ->assertSeeIn('#component3', 'PreReRenderCalled: No')
            ->post('/_components/component2/increase', [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertSee('Overridden content from component 2 on same line - count: 2')
        ;
    }

    #[Group('transient-on-windows')]
    public function testItUseBlocksFromEmbeddedContextUsingMultipleComponentsWithNamespacedTemplate()
    {
        $templateName = 'render_multiple_embedded_with_blocks.html.twig';
        $obscuredName = '5c474b02358c46cca3da7340cc79cc2e';

        $this->addTemplateMap($obscuredName, $templateName);

        $dehydrated = $this->dehydrateComponent(
            $this->mountComponent(
                'component2',
                [
                    'data-host-template' => $obscuredName,
                    'data-embedded-template-index' => self::DETERMINISTIC_ID_MULTI_2,
                ]
            )
        );

        $this->browser()
            ->visit('/render-namespaced-template/render_multiple_embedded_with_blocks')
            ->assertSuccessful()
            ->assertSeeIn('#component1', 'Overridden content from component 1')
            ->assertSeeIn('#component2', 'Overridden content from component 2 on same line - count: 1')
            ->assertSeeIn('#component3', 'PreReRenderCalled: No')
            ->post('/_components/component2/increase', [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertSee('Overridden content from component 2 on same line - count: 2')
        ;
    }

    public function testCanRedirectFromComponentAction()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('component2'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/component2', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->interceptRedirects()
            // with no custom header, it redirects like a normal browser
            ->post('/_components/component2/redirect', [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertRedirectedTo('/')

            // with custom header, a special 204 is returned
            ->post('/_components/component2/redirect', [
                'headers' => [
                    'Accept' => 'application/vnd.live-component+html',
                    'X-Requested-With' => 'XMLHttpRequest',
                ],
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertStatus(204)
            ->assertHeaderEquals('Location', '/')
            ->assertHeaderContains('X-Live-Redirect', '1')
            ->assertHeaderEquals('X-Custom-Header', '1')
        ;
    }

    public function testInjectsLiveArgs()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('component6'));

        $arguments = ['arg1' => 'hello', 'arg2' => 666, 'custom' => '33.3'];
        $this->browser()
            ->throwExceptions()
            ->post('/_components/component6', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Arg1: not provided')
            ->assertContains('Arg2: not provided')
            ->assertContains('Arg3: not provided')
            ->post('/_components/component6/inject', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                        'args' => $arguments,
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'html')
            ->assertContains('Arg1: hello')
            ->assertContains('Arg2: 666')
            ->assertContains('Arg3: 33.3')
        ;
    }

    public function testWithNullableEntity()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_nullable_entity'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/with_nullable_entity', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertContains('Prop1: default')
        ;
    }

    public function testCanHaveControllerAttributes()
    {
        if (!class_exists(IsGranted::class)) {
            $this->markTestSkipped('The security attributes are not available.');
        }

        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_security'));

        $this->browser()
            ->post('/_components/with_security?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertStatus(401)
            ->actingAs(new InMemoryUser('kevin', 'pass', ['ROLE_USER']))
            ->assertAuthenticated('kevin')
            ->post('/_components/with_security?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertSuccessful()
        ;
    }

    public function testCanInjectSecurityUserIntoAction()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('with_security'));

        $this->browser()
            ->actingAs(new InMemoryUser('kevin', 'pass', ['ROLE_USER']))
            ->post('/_components/with_security', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertNotSee('username: kevin')
            ->throwExceptions()
            ->post('/_components/with_security/setUsername', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                        'args' => [],
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertSee('username: kevin')
        ;
    }

    /**
     * @return array{html: string, file: string, headers: \Symfony\Component\HttpFoundation\HeaderBag}
     */
    private function postDownloadAction(string $action, array $props): array
    {
        $browser = $this->browser()
            ->throwExceptions()
            ->post('/_components/download_file/'.$action, [
                'body' => ['data' => json_encode(['props' => $props])],
            ])
            ->assertStatus(200)
        ;

        $response = $browser->client()->getInternalResponse();
        $body = $response->getContent();
        $headers = $browser->client()->getResponse()->headers;

        $htmlLength = (int) $headers->get('X-Live-Html-Length');

        return [
            'html' => substr($body, 0, $htmlLength),
            'file' => substr($body, $htmlLength),
            'headers' => $headers,
        ];
    }

    public function testDownloadRidesAlongTheRenderedComponent()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('download', $dehydrated->getProps());

        // the HTML part is a real component render, not a stub
        self::assertStringContainsString('data-live-props-value', $result['html']);
        self::assertSame(['foo' => 'bar'], json_decode($result['file'], true));
        self::assertSame('foo.json', rawurldecode($result['headers']->get('X-Live-Download-Filename')));
        self::assertSame('application/json', $result['headers']->get('X-Live-Download-Type'));
        self::assertSame('application/vnd.live-component+html', $result['headers']->get('Content-Type'));
    }

    public function testDownloadKeepsStateChangedByTheAction()
    {
        // the whole point of riding along: the action's LiveProp change survives
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('download', $dehydrated->getProps());

        self::assertStringContainsString('<span id="count">1</span>', $result['html']);
    }

    public function testHtmlLengthIsExactSoTheSplitIsLossless()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('download', $dehydrated->getProps());
        $htmlLength = (int) $result['headers']->get('X-Live-Html-Length');

        self::assertSame($htmlLength, \strlen($result['html']));
        // nothing is lost or duplicated between the two parts
        self::assertSame(
            $htmlLength + \strlen($result['file']),
            \strlen($result['html'].$result['file'])
        );
    }

    public function testDownloadOfBytesThatAreNotValidUtf8()
    {
        // the split must happen on bytes: decoding first would corrupt this payload
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('downloadBinary', $dehydrated->getProps());

        self::assertSame("\x00\x01\x02\xFF\xFE", $result['file']);
        self::assertSame(5, \strlen($result['file']));
    }

    public function testDownloadWithMultibyteHtmlSplitsOnBytesNotCharacters()
    {
        // the rendered HTML carries a multibyte filename, so a character-based
        // offset would land mid-sequence and shift the file
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('downloadNonAsciiFilename', $dehydrated->getProps());

        self::assertSame('résumé content', $result['file']);
        self::assertSame('résumé.txt', rawurldecode($result['headers']->get('X-Live-Download-Filename')));
    }

    public function testDownloadFilenameIsPercentEncodedForTheHeader()
    {
        // headers are ASCII-only: encoding sidesteps the RFC 5987 dance entirely
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('downloadNonAsciiFilename', $dehydrated->getProps());
        $raw = $result['headers']->get('X-Live-Download-Filename');

        self::assertSame('r%C3%A9sum%C3%A9.txt', $raw);
        self::assertSame($raw, preg_replace('/[^\x20-\x7e]/', '', $raw));
    }

    public function testDownloadDefaultsToOctetStream()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('downloadWithoutContentType', $dehydrated->getProps());

        self::assertSame('application/octet-stream', $result['headers']->get('X-Live-Download-Type'));
        self::assertSame('plain content', $result['file']);
    }

    public function testDownloadOfEmptyContent()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('downloadEmpty', $dehydrated->getProps());

        self::assertSame('', $result['file']);
        self::assertSame('empty.txt', rawurldecode($result['headers']->get('X-Live-Download-Filename')));
    }

    public function testActionWithoutDownloadCarriesNoDownloadHeaders()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/download_file/noDownload', [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertStatus(200)
            ->assertHeaderEquals('X-Live-Html-Length', null)
            ->assertHeaderEquals('X-Live-Download-Filename', null)
            ->assertSeeIn('#count', '1')
        ;
    }

    public function testStreamedDownloadFromAResource()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('streamFromResource', $dehydrated->getProps());

        self::assertStringContainsString('data-live-props-value', $result['html']);
        self::assertSame(['foo' => 'bar'], json_decode($result['file'], true));
        // the component still re-rendered, exactly like the buffered variant
        self::assertStringContainsString('<span id="count">1</span>', $result['html']);
    }

    public function testStreamedDownloadFromAClosure()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('streamFromClosure', $dehydrated->getProps());

        self::assertSame('chunk-1chunk-2', $result['file']);
        self::assertSame('chunks.txt', rawurldecode($result['headers']->get('X-Live-Download-Filename')));
    }

    public function testStreamedDownloadWithSizeCarriesContentLength()
    {
        // both lengths known: the browser can report progress over the whole body
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('streamFromResource', $dehydrated->getProps());
        $htmlLength = (int) $result['headers']->get('X-Live-Html-Length');

        self::assertSame(
            (string) ($htmlLength + \strlen($result['file'])),
            $result['headers']->get('Content-Length')
        );
    }

    public function testStreamedDownloadWithoutSizeOmitsContentLength()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('streamBinaryWithoutSize', $dehydrated->getProps());

        self::assertFalse($result['headers']->has('Content-Length'));
        // streaming must not corrupt bytes either
        self::assertSame("\x00\x01\x02\xFF\xFE", $result['file']);
    }

    public function testStreamedAndBufferedDownloadsProduceTheSameWireFormat()
    {
        // the client cannot tell the two apart: same headers, same layout
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $buffered = $this->postDownloadAction('download', $dehydrated->getProps());
        $streamed = $this->postDownloadAction('streamFromResource', $dehydrated->getProps());

        self::assertSame($buffered['file'], $streamed['file']);
        self::assertSame(
            $buffered['headers']->get('X-Live-Download-Filename'),
            $streamed['headers']->get('X-Live-Download-Filename')
        );
        self::assertSame(
            $buffered['headers']->get('X-Live-Html-Length'),
            $streamed['headers']->get('X-Live-Html-Length')
        );
    }

    public function testDownloadUrlLeavesTheRenderUntouched()
    {
        // the recommended path: the browser fetches the file itself, nothing rides along
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/download_file/downloadUrl', [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertStatus(200)
            ->assertHeaderEquals('X-Live-Download-Url', '/downloads/report.csv')
            ->assertHeaderEquals('X-Live-Html-Length', null)
            ->assertHeaderEquals('X-Live-Download-Filename', null)
            ->assertSeeIn('#count', '1')
        ;
    }

    public function testDownloadFileFromAnSplFileInfo()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('downloadFromSplFileInfo', $dehydrated->getProps());

        self::assertSame(['foo' => 'bar'], json_decode($result['file'], true));
        // no filename was given: it comes from the basename
        self::assertSame('foo.json', rawurldecode($result['headers']->get('X-Live-Download-Filename')));
    }

    public function testStreamedDownloadFromAGenerator()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('streamFromGenerator', $dehydrated->getProps());

        self::assertSame('part-1part-2', $result['file']);
    }

    public function testDownloadFromALiveListener()
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('onRefreshRequested', $dehydrated->getProps());

        self::assertSame('from a listener', $result['file']);
        self::assertStringContainsString('<span id="count">1</span>', $result['html']);
    }

    public function testDownloadAlongsideABrowserEvent()
    {
        // both ride on the same render: the event lands in the attributes, the file after the HTML
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $result = $this->postDownloadAction('downloadAndDispatchBrowserEvent', $dehydrated->getProps());

        self::assertSame('with an event', $result['file']);
        self::assertStringContainsString('data-live-events-to-dispatch-value', $result['html']);
        self::assertStringContainsString('export:done', $result['html']);
    }

    public function testDownloadFromTheDefaultActionIsRejected()
    {
        // the default action runs on every re-render: a polling component would otherwise
        // fire a download every few hundred milliseconds
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_from_default_action'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('cannot be returned from the default action');

        $this->browser()
            ->throwExceptions()
            ->post('/_components/download_from_default_action', [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
        ;
    }

    public function testAnActionCanStillRedirect()
    {
        // combining a redirect and a download is impossible by construction now: an action
        // returns one or the other
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $this->browser()
            ->throwExceptions()
            ->interceptRedirects()
            ->post('/_components/download_file/downloadThenRedirect', [
                'headers' => [
                    'Accept' => ['application/vnd.live-component+html'],
                    'X-Requested-With' => ['XMLHttpRequest'],
                ],
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertStatus(204)
            ->assertHeaderContains('X-Live-Redirect', '1')
            ->assertHeaderEquals('X-Live-Html-Length', null)
        ;
    }

    public function testDownloadIsNotCarriedOverToTheNextResponse()
    {
        // the responder is a shared service: a consumed download must not leak
        $dehydrated = $this->dehydrateComponent($this->mountComponent('download_file'));

        $this->postDownloadAction('download', $dehydrated->getProps());

        $this->browser()
            ->throwExceptions()
            ->post('/_components/download_file/noDownload', [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
            ->assertStatus(200)
            ->assertHeaderEquals('X-Live-Html-Length', null)
        ;
    }
}
