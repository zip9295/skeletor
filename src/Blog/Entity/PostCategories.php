<?php

namespace Skeletor\Blog\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

class PostCategories
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id;

    #[ORM\ManyToMany(targetEntity: Category::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'categoryId', referencedColumnName: 'id', unique: false, nullable: false)]
    public Category $category;

    #[ORM\ManyToMany(targetEntity: Post::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'postId', referencedColumnName: 'id', unique: false, nullable: false)]
    public Post $post;
}