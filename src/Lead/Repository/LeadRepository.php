<?php

namespace Skeletor\Lead\Repository;

use Skeletor\Core\TableView\Repository\TableViewRepository;
use Skeletor\Lead\Factory\LeadFactory;
use Skeletor\Core\Mapper\NotFoundException;

/**
 * Class UserRepository.
 *
 */
class LeadRepository extends TableViewRepository
{
    const FACTORY = LeadFactory::class;
    const ENTITY = \Skeletor\Lead\Entity\Lead::class;

    public function phoneNumberExists($phoneNumber)
    {
        try {
            $this->findByPhoneNumber($phoneNumber);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function findByEmail(string $email)
    {
        $leadEntity = $this->entityManager->getRepository(static::ENTITY)->findBy(['email' => $email]);
        if (!isset($leadEntity[0])) {
            throw new NotFoundException();
        }
        return $leadEntity[0];
    }

    public function emailExists($email): bool
    {
        try {
            $this->findByEmail($email);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function findByPhoneNumber($phoneNumber)
    {
        $leadEntity = $this->entityManager->getRepository(static::ENTITY)->findBy(['phoneNumber' => $phoneNumber]);
        if (!isset($leadEntity[0])) {
            throw new NotFoundException();
        }
        return $leadEntity[0];
    }

    public function getSearchableColumns(): array
    {
        return ['firstName', 'lastName', 'email', 'phoneNumber'];
    }
}

