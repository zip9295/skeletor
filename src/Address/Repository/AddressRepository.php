<?php
namespace Skeletor\Address\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Address\Mapper\Address as Mapper;
use Skeletor\Core\TableView\Repository\TableViewRepository;

class AddressRepository extends TableViewRepository
{
    /**
     * AddressRepository constructor.
     * @param Mapper $mapper
     * @param \DateTime $dt
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em);
    }

    public function getSearchableColumns(): array
    {
        return ['address'];
    }
}
