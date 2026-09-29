<?php

namespace Skeletor\Category\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Product\Entity\Supplier;
use Skeletor\Core\Entity\Timestampable;
use Skeletor\Category\Model\CategoryMapper as DtoModel;

#[ORM\Entity]
#[ORM\Table(name: 'categoryMapper')]
class CategoryMapper
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING)]
    private string $source1;
    #[ORM\Column(type: Types::STRING)]
    private string $source2;
    #[ORM\Column(type: Types::STRING)]
    private string $source3;
    #[ORM\Column(type: Types::STRING)]
    private string $status;
    #[ORM\Column(type: Types::INTEGER)]
    private int $count;
    #[ORM\Column(type: Types::INTEGER)]
    private int $ignored;

    #[ORM\ManyToOne(targetEntity: Category::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'categoryId', referencedColumnName: 'id', unique: false, nullable: true)]
    private ?Category $category;
    #[ORM\ManyToOne(targetEntity: Supplier::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'supplierId', referencedColumnName: 'id', unique: false)]
    private Supplier $supplier;

    public function populateFromDto(DtoModel $dto)
    {
        if ($dto->getId()) {
            $this->id = $dto->getId();
        }
        $this->source1 = $dto->getSource1();
        $this->source2 = $dto->getSource2();
        $this->source3 = $dto->getSource3();
        $this->status = $dto->getStatus();
        if ($dto->getCount()) {
            $this->count = $dto->getCount();
        }
        $this->ignored = $dto->getIgnored();
    }

    public function setCategory(Category $category)
    {
        $this->category = $category;
    }

    public function setSupplier(Supplier $supplier)
    {
        $this->supplier = $supplier;
    }

    public function getId()
    {
        return $this->id;
    }
}