<?php

namespace Skeletor\Blog\Entity;


use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Author\Entity\Author;
use Skeletor\Core\Entity\Seo;
use Skeletor\Core\Entity\Timestampable;
use Skeletor\Image\Entity\Image;

// @TODO add migration for full text index: ALTER TABLE post ADD FULLTEXT(title, blockData, shortDescription)

#[ORM\Entity]
#[ORM\Index(name: 'ft_post_search', columns: ['title', 'shortDescription'], flags: ['fulltext'])]
#[ORM\Table(name: 'post')]
class Post
{
    use Seo;
    const STATUS_PENDING = 3;
    const STATUS_PUBLISHED = 1;
    const STATUS_DRAFT = 2;

    const STATUS_SCHEDULED = 4;

    use Timestampable;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false)]
    public string $title;

    #[ORM\Column(type: Types::STRING, length: 128, unique: true, nullable: false)]
    public string $slug;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $shortDescription;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    public ?array $blockData;

    #[ORM\Column(type: Types::INTEGER)]
    public int $status;

    #[ORM\ManyToOne(targetEntity: Image::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'featuredImageId', referencedColumnName: 'id', unique: false, nullable: true)]
    public ?Image $featuredImage;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'posts', fetch: 'EAGER')]
    public Collection $tags;

    #[ORM\ManyToOne(targetEntity: Category::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'categoryId', referencedColumnName: 'id', unique: false, nullable: false)]
    public Category $mainCategory;

    #[ORM\ManyToMany(targetEntity: Category::class, fetch: 'EAGER')]
    public Collection $categories;

    #[ORM\ManyToOne(targetEntity: Author::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'authorId', referencedColumnName: 'id', unique: false, nullable: false)]
    public Author $author;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isLiveBlogPost = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    public ?\DateTime $publishAt = null;

    public static function getHrStatuses(): array
    {
        return [
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SCHEDULED => 'Scheduled',
        ];
    }

    public static function getHrStatus(int $status): string
    {
        return self::getHrStatuses()[$status];
    }
}