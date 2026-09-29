<?php
declare(strict_types = 1);
namespace Skeletor\Address\Repository;

use Skeletor\Address\Repository\NotFoundException;
use Skeletor\Address\Repository\Page;
use Skeletor\Address\Repository\User;
use Skeletor\Address\Mapper\City as Mapper;
use Skeletor\Address\Mapper\Country;
use Skeletor\Address\Model\City;

class CityRepository
{
    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var \DateTime
     */
    private $dt;

    private $country;

    private $city;


    /**
     * AddressRepository constructor.
     * @param Mapper $mapper
     * @param \DateTime $dt
     */
    public function __construct(Mapper $mapper, Country $country, \DateTime $dt)
    {
        $this->mapper = $mapper;
        $this->country = $country;
        $this->dt = $dt;
    }

    /**
     * Fetches a list of Address models.
     *
     * @param array $params
     *
     * @return array
     */
    public function fetchAll($params = array()): array
    {
        $items = [];
        foreach ($this->mapper->fetchAll($params) as $data) {
            $items[] = $this->make($data);
        }

        return $items;
    }

    /**
     * @param $id
     * @return Page
     * @throws NotFoundException
     */
    public function getById(int $id): City
    {
        return $this->make($this->mapper->fetchById($id));
    }

    /**
     * Persists page model.
     *
     * @param array $data
     *
     * @return int
     * @throws \Exception
     */
    public function create($data): int
    {
        return $this->mapper->insert([
            'name' => $data['name'],
            'zip' => $data['zip'],
            'countryId' => $data['countryId'],
        ]);
    }

    /**
     * Updates page model.
     *
     * @param $data
     * @return Page
     * @throws \Exception
     *
     */
    public function update($data): City
    {
        return $this->getById($this->mapper->update([
            'cityId' => $data['cityId'],
            'name' => $data['name'],
            'zip' => $data['zip'],
            'countryId' => $data['countryId'],
        ]));
    }

    /**
     * Factory method
     *
     * @param $userData
     * @return User
     */
    private function make($pageData): City
    {
        $data = [];
        foreach ($pageData as $name => $value) {
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
        $country = $this->country->fetchById((int) $data['countryId']);

        return new City(
            $data['cityId'],
            $data['name'],
            $country,
            $data['zip'],
            $data['createdAt'],
            $data['updatedAt']
        );
    }

    /**
     * Deletes a single entity.
     *
     * @param int $id
     *
     * @return bool
     */
    public function delete($id): bool
    {
        return $this->mapper->delete($id);
    }

}
