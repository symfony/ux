<?php

namespace App\Twig;

use App\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class RegistrationForm extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    #[LiveProp]
    public bool $isSuccessful = false;

    #[LiveProp]
    public ?string $newUserEmail = null;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(RegistrationFormType::class);
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    #[LiveAction]
    public function saveRegistration()
    {
        $this->submitForm();

        // save to the database
        // or, instead of creating a LiveAction, allow the form to submit
        // to a normal controller: that's even better.
        // $newUser = $this->getFormInstance()->getData();

        $this->newUserEmail = $this->getForm()
            ->get('email')
            ->getData();
        $this->isSuccessful = true;
    }
}
