How to Use Multiple Transports
==============================

Symfony UX Turbo allows sending Turbo Streams updates using multiple
transports. For instance, it's possible to use several Mercure hubs with
the following configuration:

.. code-block:: yaml

    # config/packages/mercure.yaml
    mercure:
        hubs:
            hub1:
                url: https://hub1.example.net/.well-known/mercure
                jwt: ...
            hub2:
                url: https://hub2.example.net/.well-known/mercure
                jwt: ...

Use the appropriate Mercure ``HubInterface`` service to send a change
using a specific transport::

    // src/Controller/TaskController.php
    namespace App\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Mercure\HubInterface;
    use Symfony\Component\Mercure\Update;
    use Symfony\Component\Routing\Attribute\Route;

    #[Route('/task')]
    class TaskController extends AbstractController
    {
        #[Route('/publish', name: 'app_task_publish', methods: ['POST'])]
        public function publish(HubInterface $hub1): Response
        {
            $id = $hub1->publish(new Update('tasks', 'content'));

            return new Response("Update #{$id} published.");
        }
    }

Changes made to entities marked with the ``#[Broadcast]`` attribute will
be sent using all configured transports by default. You can specify the
list of transports to use for a specific entity class using the
``transports`` parameter::

    // src/Entity/Task.php
    namespace App\Entity;

    use Doctrine\ORM\Mapping as ORM;
    use Symfony\UX\Turbo\Attribute\Broadcast;

    #[ORM\Entity]
    #[Broadcast(transports: ['hub1', 'hub2'])]
    class Task
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null;

        #[ORM\Column(length: 255)]
        private ?string $title = null;
    }

Finally, pass the name of the transport to use when subscribing the page to
the updates:

.. code-block:: html+twig

    <twig:Turbo:Stream:From topics="App\\Entity\\Task" transport="hub2" />

    {# or, with the Twig function #}
    {{ turbo_stream_from('App\\Entity\\Task', transport: 'hub2') }}
