<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent('validating_component', csrf: false)]
final class ValidatingComponent
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: true)]
    #[NotBlank]
    #[Length(min: 3)]
    public string $name = '';

    #[LiveAction]
    public function resetValidationAction(): void
    {
        $this->resetValidation();
    }
}
