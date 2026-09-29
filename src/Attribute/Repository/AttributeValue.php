<?php

namespace Skeletor\Attribute\Repository;

use Skeletor\Core\Model\Model;
use Skeletor\Core\TableView\Repository\TableViewRepository;
use \Skeletor\Attribute\Mapper\AttributeValue as Mapper;
class AttributeValue extends TableViewRepository
{
    public function __construct(
        Mapper $mapper, private \DateTime $dt
    )
    {
        parent::__construct($mapper);
    }

    public function make($itemData): Model
    {
        $data = [];
        foreach ($itemData as $name => $value) {
            if (in_array($name, ['createdAt', 'updatedAt'])) {
                $data[$name] = null;
                if ($value) {
                    if (strtotime($value)) {
                        $dt = clone $this->dt;
                        $dt->setTimestamp(strtotime($value));
                        $data[$name] = $dt;
                    } else {
                        $data[$name] = null;
                    }
                }
            } else {
                $data[$name] = $value;
            }
        }

        if (!isset($data['createdAt'])) {
            $data['createdAt'] = null;
        }
        if (!isset($data['updatedAt'])) {
            $data['updatedAt'] = null;
        }
        return new \Skeletor\Attribute\Model\AttributeValue(...$data);
    }

    public function getSearchableColumns(): array
    {
        return ['name'];
    }
}