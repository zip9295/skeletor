<?php

namespace Skeletor\Core\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ManyToMany;
use Skeletor\Image\Entity\Image;

abstract class AbstractFactory
{
    public static function formatForRead($entity)
    {
        return $entity;
    }

    public static function formatForWrite($entity, $entityData, $em)
    {
        $reflection = new \ReflectionClass($entity);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $propertyType = $property->getType()?->getName();

            // ManyToOne: single entity reference
            if ($propertyType && str_contains($propertyType, 'Entity')) {
                if (isset($entityData[$propertyName .'Id']) && $entityData[$propertyName .'Id']) {
                    $relation = $em->getRepository($propertyType)->find($entityData[$propertyName .'Id']);
                    $entity->{$propertyName} = $relation;
                } elseif (isset($entityData[$propertyName]) && $entityData[$propertyName]) {
                    $relation = $em->getRepository($propertyType)->find($entityData[$propertyName]);
                    $entity->{$propertyName} = $relation;
                } elseif ($property->getType()?->allowsNull()) {
                    // Cleared only when the property can actually hold null. A key that is merely
                    // absent - an inline column edit posts the one field it changed, not the whole
                    // entity - must not clobber a required relation. Nulling unconditionally wiped
                    // School::$type on every save while that property was still nullable, and
                    // became a TypeError the moment it was not.
                    $entity->{$propertyName} = null;
                }
            }
            // Collection: ManyToMany or OneToMany
            elseif ($propertyType === Collection::class) {
                if (!isset($entityData[$propertyName])) {
                    continue;
                }
                foreach ($property->getAttributes() as $attribute) {
                    $args = $attribute->getArguments();
                    $targetEntity = $args['targetEntity'] ?? null;

                    if ($attribute->getName() === ManyToMany::class && $targetEntity) {
                        $collection = $entity->{$propertyName};
                        $collection->clear();
                        if (is_array($entityData[$propertyName])) {
                            $repo = $em->getRepository($targetEntity);
                            foreach ($entityData[$propertyName] as $id) {
                                $related = $repo->find((int) $id);
                                if ($related) {
                                    $collection->add($related);
                                }
                            }
                        }
                    }

                    if ($attribute->getName() === OneToMany::class && $targetEntity) {
                        $mappedBy = $args['mappedBy'] ?? null;
                        if (!$mappedBy) {
                            continue;
                        }
                        $repo = $em->getRepository($targetEntity);
                        // Clear owning side on old relations
                        foreach ($entity->{$propertyName} as $existing) {
                            $existing->{$mappedBy} = null;
                        }
                        // Set owning side on new relations
                        if (is_array($entityData[$propertyName])) {
                            foreach ($entityData[$propertyName] as $id) {
                                $related = $repo->find((int) $id);
                                if ($related) {
                                    $related->{$mappedBy} = $entity;
                                }
                            }
                        }
                    }
                }
            }
            // Scalar properties
            else {
                if (isset($entityData[$propertyName]) && $entityData[$propertyName] !== null
//                    && $entityData[$propertyName] !== '') { @todo This is commented out because empty values are not set later. For example, image alt is an empty string by default when creating, if it is an empty string this will not set the property to the entity and will break.
                ){
                    $entity->{$propertyName} = $entityData[$propertyName];
                }
            }
        }
        return $entity;
    }

    public static function formatForMake(object $entity, $entityData, $em)
    {
        $reflection = new \ReflectionClass($entity);
        $properties = $reflection->getProperties();
        $relations = [];
        foreach ($properties as $property) {
            $propertyType = $property->getType()->getName();
            if (str_contains($propertyType, 'Entity')) {
                $relations[$property->getName()] = $propertyType;
            }
            if($propertyType === Collection::class) {
                foreach($property->getAttributes() as $attribute) {
                    if($attribute->getName() === ManyToMany::class) {
                        $arguments = $attribute->getArguments();
                        $relations[$property->getName()] = $arguments['targetEntity'];
                    }
                    if($attribute->getName() === OneToMany::class) {
                        $arguments = $attribute->getArguments();
                        $relations[$property->getName()] = $arguments['targetEntity'];
                    }
                }
            }
        }
        foreach ($relations as $relationName => $relationClassName) {
            $keyToUnset = $relationName . 'Id';
            unset($entityData[$keyToUnset]);
            unset($entityData[$relationName . '_id']);
            if(!isset($entityData[$relationName])) {
                continue;
            }
            $factoryNameSpace = str_replace('Entity', 'Factory', $relationClassName) . 'Factory';
            if ($entityData[$relationName] instanceof PersistentCollection) {
                $list = new ArrayCollection();
                foreach($entityData[$relationName] as $relationEntity) {
                    $list[] = $factoryNameSpace::make($relationEntity, [], $em);
                }
                $entityData[$relationName] = $list;
            } else {
                $originalData = $em->getUnitOfWork()->getOriginalEntityData($entityData[$relationName]);
                $originalData['id'] = $entityData[$relationName]->id;
                $entityAsArray = [];
                foreach($originalData as $key => $value) {
                    $entityAsArray[$key] = $value;
                }
                // @TODO this points to probable data integrity problem
                if (count($entityAsArray) === 0) {
                    throw new \Exception('Data not found. Check relations and data integrity for parents of ' . get_class($entityData[$relationName]));
                }
                $entityData[$relationName] = $factoryNameSpace::make($entityData[$relationName], $entityAsArray, $em);
            }
        }
        return $entityData;
    }

    public static function compileEntityForUpdate($data, $em)
    {
        $entityClass = str_replace('\\Factory', '\\Entity', get_called_class());
        $entityClass = str_replace('Factory', '', $entityClass);
        $entity = static::formatForWrite($em->getRepository($entityClass)->find($data['id']), $data, $em);

        return $entity->id;
    }

    public static function compileEntityForCreate($data, EntityManagerInterface $em)
    {
        $entityClass = str_replace('\\Factory', '\\Entity', get_called_class());
        $entityClass = str_replace('Factory', '', $entityClass);
        $entity = static::formatForWrite(new $entityClass(), $data, $em);
        $em->persist($entity);
        $em->flush();
        // @TODO need to make orm forget about this entity in order to fetch fresh data always
        // detach sounds like a better/faster option
        $em->detach($entity);
//        $em->refresh($entity);

        return $entity->id;
    }

    public static function make($entity, $entityData = [], $em = null)
    {
        if (empty($entityData)) {
            $id = $entity->id;
            $entityData = $em->getUnitOfWork()->getOriginalEntityData($entity);
            $entityData['id'] = $id;
        }
        if (is_string($entity)) {
            $modelClass = str_replace(['Entity', 'DoctrineProxies\__CG__\\'], ['Model', ''], $entity);
            $entity = new $entity();
        } else {
            $modelClass = str_replace(['Entity', 'DoctrineProxies\__CG__\\'], ['Model', ''], get_class($entity));
        }
        return new $modelClass(...
            self::formatForMake(
                $entity, $entityData, $em
            ));
    }
}