<?php
declare(strict_types = 1);
namespace Skeletor\Address\Repository;

use Skeletor\Address\Repository\NotFoundException;
use Skeletor\Address\Repository\Page;
use Skeletor\Address\Repository\User;
use Skeletor\Address\Mapper\Country as Mapper;
use Skeletor\Address\Model\Country;

class CountryRepository
{
    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var \DateTime
     */
    private $dt;


    /**
     * AddressRepository constructor.
     * @param Mapper $mapper
     * @param \DateTime $dt
     */
    public function __construct(Mapper $mapper, \DateTime $dt)
    {
        $this->mapper = $mapper;
        $this->dt = $dt;
    }

    /**
     * Fetches a list of Address models.
     *
     * @param array $params
     *
     * @return array
     */
    public function fetchAll($params = array(), $limit = null, $order = null): array
    {
        $items = [];
        foreach ($this->mapper->fetchAll($params, $limit, $order) as $data) {
            $items[] = $this->make($data);
        }

        return $items;
    }

    /**
     * @param $id
     * @return Page
     * @throws NotFoundException
     */
    public function getById(int $id): Country
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
            'name' => $data['name']
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
    public function update($data): Country
    {
        return $this->getById($this->mapper->update([
            'cityId' => $data['cityId'],
            'name' => $data['name'],
            'countryId' => $data['countryId'],
        ]));
    }

    /**
     * Factory method
     *
     * @param $userData
     * @return User
     */
    private function make($pageData): Country
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

        return new Country(
            $data['countryId'],
            $data['name'],
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
