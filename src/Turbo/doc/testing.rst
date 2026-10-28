Writing Tests
=============

Under the hood, Symfony UX Turbo relies on JavaScript to update the HTML
page. To test if your website works properly, you will have to write `UI tests`_.

`Symfony Panther`_ is a convenient testing tool using real browsers to test
your Symfony application. It shares the same API as BrowserKit, the functional
testing tool shipped with Symfony.

`Install Symfony Panther`_ and write a test for the task Turbo Frame::

    // tests/TaskFrameTest.php
    namespace App\Tests;

    use Symfony\Component\Panther\PantherTestCase;

    class TaskFrameTest extends PantherTestCase
    {
        public function testEditTaskFrame(): void
        {
            $client = self::createPantherClient();
            $client->request('GET', '/task/1');

            $client->clickLink('Edit this task');
            $this->assertSelectorWillContain('turbo-frame#task_1', 'Save');
        }
    }

Run ``bin/phpunit`` to execute the test. Symfony Panther automatically
starts your application with a web server and tests it using Google
Chrome or Firefox.

You can even watch changes happening in the browser by using:
``PANTHER_NO_HEADLESS=1 bin/phpunit --debug``

Testing Turbo Stream Responses
------------------------------

Responses with stream messages don't need a browser. Use a regular
`application test`_ and send the ``Accept`` header that Turbo adds to form
submissions::

    // tests/Controller/TaskControllerTest.php
    namespace App\Tests\Controller;

    use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
    use Symfony\UX\Turbo\TurboBundle;

    class TaskControllerTest extends WebTestCase
    {
        public function testNewTaskRespondsWithStreamMessages(): void
        {
            $client = static::createClient();
            $client->request('GET', '/task/new');

            $client->submitForm('Save', ['task[title]' => 'Buy some milk'], 'POST', [
                'HTTP_ACCEPT' => TurboBundle::STREAM_MEDIA_TYPE,
            ]);

            $this->assertResponseHeaderSame('Content-Type', 'text/vnd.turbo-stream.html; charset=UTF-8');
            $this->assertSelectorExists('turbo-stream[action="append"][targets="#task_list"]');
        }
    }

.. _`UI tests`: https://martinfowler.com/articles/practical-test-pyramid.html#UiTests
.. _`Symfony Panther`: https://github.com/symfony/panther
.. _`Install Symfony Panther`: https://github.com/symfony/panther#installing-panther
.. _`application test`: https://symfony.com/doc/current/testing.html#functional-tests
