<?php

namespace Skeletor\Notification\Repository;

use Skeletor\Core\Model\Model;
use Skeletor\Core\Repository\ReadRepository;
use Skeletor\Notification\Mapper\NotificationRead;

class NotificationReadRepository extends ReadRepository
{
    public function __construct(
        NotificationRead $mapper,
        private \DateTime $dt,
    )
    {
        parent::__construct($mapper);
    }

    function make($itemData): Model
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

        return new \Skeletor\Notification\Model\Notification(...$data);
    }
}