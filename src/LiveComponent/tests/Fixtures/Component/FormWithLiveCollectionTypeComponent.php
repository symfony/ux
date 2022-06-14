<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\BlogPost;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Comment;
use Symfony\UX\LiveComponent\Tests\Fixtures\Form\BlogPostFormLiveCollectionType;

#[AsLiveComponent('form_with_live_collection_type', template: 'components/form_with_collection_type.html.twig')]
class FormWithLiveCollectionTypeComponent extends AbstractController
{
    use LiveCollectionTrait;
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
        return $this->createForm(BlogPostFormLiveCollectionType::class, $this->post);
    }
}
