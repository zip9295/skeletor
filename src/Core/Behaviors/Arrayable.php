<?php
namespace Skeletor\Core\Behaviors;

trait Arrayable
{
    public function toArray($debug = false)
    {
        $data = [];
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties() as $property) {
            $name = $property->name;
            $methodGetter = 'get' . ucfirst($property->name);
            if (!method_exists($this, $methodGetter)) {
                if (!method_exists($this, $property->name)) {
                    continue;
                }
                $methodGetter = $property->name;
            }
            $value = $this->{$methodGetter}();
            if (is_object($value)) {
                if ($value instanceOf \DateTime) {
                    $data[$name] = $value->format('d.m.Y');
                } elseif (method_exists($value, 'toArray')) {
                    $data[$name] = $value->toArray();
                } elseif (get_class($value) === "NumberFormatter") {
                    continue;
                } elseif (method_exists($value, 'getArrayCopy')) {
                    $subModels = [];
                    foreach ($value->getArrayCopy() as $subModel) {
//                        $subModels[] = $subModel->toArray();
                        $subModels[] = $subModel;
                    }
                    $data[$name] = $subModels;
                } else {
                    throw new \InvalidArgumentException('Object type is not recognized.');
                }
            } elseif (is_array($value)) {
                $data[$name] = [];
                foreach ($value as $type => $rows) {
                    if (is_string($type) && is_array($rows)) {
                        foreach ($rows as $key => $row) {
                            $data[$name][$type][$key] = $row->toArray();
                        }
                    } else {
                        if (!is_object($rows)) {
                            $data[$name][] = $rows;
                        } elseif ($rows instanceOf \DateTime) {
                            $data[$name][] = $rows->format('d.m.Y');
                        } else {
                            $data[$name][] = $rows->toArray();
                        }
                    }
                }
            } else {
//                if (is_numeric($value)) {
//                    $value = (int) $value;
//                }
                $data[$name] = $value;
            }
            $data['id'] = null;
            if (method_exists($this, 'getId') && $this->getId() !== null) {
                $data['id'] = $this->getId();
            }
        }
        if (method_exists($this, 'getCreatedAt') && $this->getCreatedAt() instanceof \DateTime) {
            $data['createdAt'] = $this->getCreatedAt()->format('d/m/Y H:i:s');
            $data['updatedAt'] = $this->getUpdatedAt()->format('d/m/Y H:i:s');
        }

        return $data;
    }
}