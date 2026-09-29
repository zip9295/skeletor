<?php
namespace Skeletor\Category\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Tenant\Entity\Tenant;
use Skeletor\Core\Entity\Timestampable;
use Skeletor\Category\Model\TenantCategoryMapper as DtoModel;

#[ORM\Entity]
#[ORM\Table(name: 'tenantCategoryMapper')]
class TenantCategoryMapper
{
    use Timestampable;

    #[ORM\ManyToOne(targetEntity: Category::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'parent', referencedColumnName: 'id', unique: false)]
    private Category $localCategory;

    #[ORM\ManyToOne(targetEntity: Category::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'parent', referencedColumnName: 'id', unique: false)]
    private Category $remoteCategory;

    #[ORM\ManyToOne(targetEntity: Tenant::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'tenantId', referencedColumnName: 'id', unique: false)]
    private Tenant $tenant;

    public function populateFromDto(DtoModel $dto)
    {
        if ($dto->getId()) {
            $this->id = $dto->getId();
        }
    }

    public function setTenant(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function setLocalCategory(Category $category)
    {
        $this->localCategory = $category;
    }

    public function setRemoteCategory(Category $category)
    {
        $this->remoteCategory = $category;
    }
}