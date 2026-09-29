<?php

namespace Skeletor\Attribute\Repository;

use League\Event\EventDispatcher;
use Skeletor\Core\Model\Model;
use Skeletor\Core\TableView\Repository\TableViewRepository;
use \Skeletor\Attribute\Mapper\Attribute as Mapper;
use \Skeletor\Attribute\Event\Attribute as Event;

class Attribute extends TableViewRepository
{
    protected $event = Event::class;

    public function __construct(
        Mapper $mapper, ?EventDispatcher $dispatcher, private \DateTime $dt, private AttributeValue $attributeValueRepo
    )
    {
        parent::__construct($mapper, $dispatcher);
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
        return new \Skeletor\Attribute\Model\Attribute(...$data);
    }

    public function getSearchableColumns(): array
    {
        return ['name'];
    }

    public function beforeSave($data)
    {
        unset($data['attributeValues']);
        return $data;
    }

    /**
     * @throws \Exception
     */
    public function afterSave($data, $oldModel): void
    {
        if (isset($data['attributeValues']['new'])){
            foreach ($data['attributeValues']['new'] as $newAttributeValue) {
                $this->attributeValueRepo->create(['attributeId' => $oldModel->getId(), 'attributeValue' => $newAttributeValue]);
            }
        }
        if (isset($data['attributeValues']['deleted'])){
            foreach ($data['attributeValues']['deleted'] as $deletedAttributeValue) {
                $this->attributeValueRepo->delete($deletedAttributeValue);
            }
        }
    }

    public function afterDelete($id): bool
    {
        foreach($this->attributeValueRepo->fetchAll(['attributeId' => $id]) as $attributeValue) {
            $this->attributeValueRepo->delete($attributeValue->getId());
        }
        return true;
    }
}