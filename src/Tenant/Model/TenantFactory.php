<?php

namespace Skeletor\Tenant\Model;

use Skeletor\Address\Model\AddressFactory;
use Skeletor\Tenant\Model\Tenant as Model;

class TenantFactory
{
    public static function make($itemData, $entityManager): Model
    {
        $itemData['address'] = AddressFactory::make($itemData['address'], $entityManager);

        return new Model(...$itemData);
    }

    public static function compileEntityForUpdate($data, $entityManager)
    {
        $tenant = $entityManager->getRepository(\Skeletor\Tenant\Entity\Tenant::class)->find($data['id']);
        $tenantDto = new \Skeletor\Tenant\Model\Tenant(...$data);
        $tenant->populateFromDto($tenantDto);

        return $tenant->getId();
    }

    public static function compileEntityForCreate($data, $entityManager)
    {
        $tenant = new \Skeletor\Tenant\Entity\Tenant();
        $data['id'] = null;
        $tenantDto = new \Skeletor\Tenant\Model\Tenant(...$data);
        $tenant->populateFromDto($tenantDto);
        $entityManager->persist($tenant);

        return $tenant->getId();
    }
}