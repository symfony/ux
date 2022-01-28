<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixture\Component;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Tests\Fixture\Dto\BlogPost;
use Symfony\UX\LiveComponent\Tests\Fixture\Dto\Comment;
use Symfony\UX\LiveComponent\Tests\Fixture\Form\BlogPostFormType;

#[AsLiveComponent('form_with_collection_type')]
class FormWithCollectionTypeComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    public BlogPost $post;

    public function __construct()
    {
        $this->post = new BlogPost();
        // start with 1 comment
        $this->post->comments[] = new Comment();
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(BlogPostFormType::class, $this->post);
    }

    #[LiveAction]
    public function addComment()
    {
        $this->formValues['comments'][] = [];
    }

    #[LiveAction]
    public function removeComment(#[LiveArg] int $index)
    {
        unset($this->formValues['comments'][$index]);
    }
}
