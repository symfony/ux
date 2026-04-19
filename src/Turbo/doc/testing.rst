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

.. _`UI tests`: https://martinfowler.com/articles/practical-test-pyramid.html#UiTests
.. _`Symfony Panther`: https://github.com/symfony/panther
.. _`Install Symfony Panther`: https://github.com/symfony/panther#installing-panther
