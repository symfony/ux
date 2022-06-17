<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\UX\Dropzone\Form\DropzoneType;

class DropzoneForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', DropzoneType::class, [
                'attr' => [
                    'placeholder' => 'Drag and drop a file or click to browse',
                ],
            ])
        ;
    }
}
