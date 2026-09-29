<?php
namespace Skeletor\Address\Model;

use Doctrine\ORM\EntityManagerInterface;

class AddressFactory
{
    public static function make($data, EntityManagerInterface $em)
    {
        // @TODO maybe this should not receive entity, but data instead ?
        $addressData = $em->getUnitOfWork()->getOriginalEntityData($data[0]);
        $cityData = $em->getUnitOfWork()->getOriginalEntityData($addressData['city']);
        $countryData = $em->getUnitOfWork()->getOriginalEntityData($cityData['country']);
        $cityData['country'] = new Country(...$countryData);
        $addressData['city'] = new City(...$cityData);

        return new Address(...$addressData);
    }

    public static function fromPostData($addressData, $entityManager, $returnType = 'entity')
    {
        $countryData = $addressData['city']['country'];
        $cityData = $addressData['city'];
        $cityData['country'] = null;
        $addressData['city'] = null;

        $country = $entityManager->getRepository(\Skeletor\Address\Entity\Country::class)->find($countryData['id']);
        if (!$country) {
            $country = $entityManager->getRepository(\Skeletor\Address\Entity\Country::class)->findOneBy(['name' => $countryData['name']]);
            if (!$country) {
                $country = new \Skeletor\Address\Entity\Country();
                $countryData['id'] = null;
            }
        }
        $countryDto = new Country(...$countryData);
        $country->populateFromDto($countryDto);
        $entityManager->persist($country);

        $city = $entityManager->getRepository(\Skeletor\Address\Entity\City::class)->find($cityData['id']);
        if (!$city) {
            $city = $entityManager->getRepository(\Skeletor\Address\Entity\City::class)->findOneBy(['name' => $cityData['name']]);
            if (!$city) {
                $city = new \Skeletor\Address\Entity\City();
                $cityData['id'] = null;
            }
        }
        $cityDto = new City(...$cityData);
        $city->populateFromDto($cityDto);
        $city->setCountry($country);
        $entityManager->persist($city);

        $address = $entityManager->getRepository(\Skeletor\Address\Entity\Address::class)->find($addressData['id']);
        if (!$address) {
            $address = new \Skeletor\Address\Entity\Address();
            $addressData['id'] = null;
        }
        $addressDto = new \Skeletor\Address\Model\Address(...$addressData);
        $address->populateFromDto($addressDto);
        $address->setCity($city);
        if ($returnType === 'entity') {
            return $address;
        }

        return $addressDto;
    }
}