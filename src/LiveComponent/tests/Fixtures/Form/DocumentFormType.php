<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Document;

class DocumentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(message: 'The title field should not be blank'),
                ],
            ])
            ->add('file', FileType::class, [
                'required' => false,
                'constraints' => [
                    new File(extensions: ['txt'], extensionsMessage: 'Only text files are allowed'),
                ],
            ])
            ->add('attachments', FileType::class, [
                'multiple' => true,
                'required' => false,
            ])
            ->add($builder->create('details', FormType::class)
                ->add('thumbnail', FileType::class, [
                    'required' => false,
                ])
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
        ]);
    }
}
