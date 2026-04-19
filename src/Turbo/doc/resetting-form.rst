How to Reset the Form after a Turbo Stream
==========================================

When you return a Turbo stream, *only* the elements in that stream template will
be updated. This means that if you want to reset the form, you need to include
a new form in the stream template.

To do that, first isolate your form rendering into a block so you can reuse it:

.. code-block:: diff

    {# templates/task/new.html.twig #}
    +{% block task_form %}
     {{ form(form) }}
    +{% endblock %}

Now, create a "fresh" form and pass it into your stream:

.. code-block:: diff

    // src/Controller/TaskController.php
    // ...

    #[Route('/task')]
    class TaskController extends AbstractController
    {
        #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
        public function new(Request $request): Response
        {
            $task = new Task();
            $form = $this->createForm(TaskType::class, $task);

   +        $emptyForm = clone $form;
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // ...

                if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                    return $this->renderBlock('task/new.html.twig', 'success_stream', [
                        'task' => $task,
   +                    'form' => $emptyForm,
                    ]);
                }

                // ...
                return $this->redirectToRoute('app_task_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('task/new.html.twig', [
                'form' => $form,
            ]);
        }
    }

Now, in your stream template, "replace" the entire form:

.. code-block:: diff

    {# templates/task/new.html.twig #}
     {% block success_stream %}
    +<turbo-stream action="replace" targets="form[name=task]">
    +    <template>
    +        {{ block('task_form') }}
    +    </template>
    +</turbo-stream>
     <turbo-stream action="append" targets="#task_list">
         <template>
             <li id="task_{{ task.id }}">{{ task.title }}</li>
         </template>
     </turbo-stream>
     {% endblock %}
