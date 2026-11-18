<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Model\PostCategory;
use App\Model\PostFormData;
use App\Model\PostStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PostFormData>
 */
final class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => ['placeholder' => 'How we made our dashboard 10x faster'],
            ])
            ->add('author', TextType::class, [
                'required' => false,
            ])
            ->add('category', EnumType::class, [
                'class' => PostCategory::class,
                'choice_label' => static fn (PostCategory $category): string => $category->label(),
                'placeholder' => 'Choose a category',
            ])
            ->add('status', EnumType::class, [
                'class' => PostStatus::class,
                'choice_label' => static fn (PostStatus $status): string => $status->label(),
                'expanded' => true,
            ])
            ->add('featured', CheckboxType::class, [
                'label' => 'Feature this post on the homepage',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PostFormData::class,
        ]);
    }
}
