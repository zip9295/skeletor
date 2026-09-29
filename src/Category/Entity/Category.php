<?php

namespace Skeletor\Category\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Tenant\Entity\Tenant;
use Skeletor\Core\Entity\Timestampable;
use Skeletor\Category\Model\Category as DtoModel;
use Skeletor\Image\Entity\Image;

#[ORM\Entity]
#[ORM\Table(name: 'category')]
class Category
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING)]
    private string $title;
    #[ORM\Column(type: Types::INTEGER)]
    private string $count;
    #[ORM\Column(type: Types::INTEGER)]
    private string $level;
    #[ORM\Column(type: Types::INTEGER)]
    private string $status;
    #[ORM\Column(type: Types::TEXT)]
    private string $description;
    #[ORM\Column(type: Types::STRING)]
    private string $slug;
    #[ORM\Column(type: Types::TEXT)]
    private ?string $secondDescription;
    #[ORM\ManyToOne(targetEntity: Image::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'imageId', referencedColumnName: 'id', unique: false)]
    private ?Image $image;
    #[ORM\ManyToOne(targetEntity: Category::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'parent', referencedColumnName: 'id', unique: false)]
    private ?Category $parent;
    #[ORM\ManyToOne(targetEntity: Tenant::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'tenantId', referencedColumnName: 'id', unique: false)]
    private ?Tenant $tenant;

    public function populateFromDto(DtoModel $dto)
    {
        if ($dto->getId()) {
            $this->id = $dto->getId();
        }
        $this->title = $dto->getName();
        $this->level = $dto->getLevel();
        $this->count = $dto->getCount();
        $this->status = $dto->getStatus();
        $this->description = $dto->getDescription();
        $this->secondDescription = $dto->getSecondDescription();
        $this->slug = $dto->getSlug();
    }

    public function setImage(Image $image)
    {
        $this->image = $image;
    }

    public function setTenant(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function setParent(Category $category)
    {
        $this->parent = $category;
    }

    public function getId()
    {
        return $this->id;
    }
}