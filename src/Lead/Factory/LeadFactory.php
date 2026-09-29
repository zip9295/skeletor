<?php
namespace Skeletor\Lead\Factory;

use Skeletor\Core\Factory\AbstractFactory;

class LeadFactory extends AbstractFactory
{
    public static function compileEntityForUpdate($data, $em)
    {
        $lead = $em->getRepository(\Skeletor\Lead\Entity\Lead::class)->find($data['id']);
        $lead->firstName = $data['firstName'];
        $lead->lastName = $data['lastName'];
        $lead->email = $data['email'];
        $lead->phoneNumber = $data['phoneNumber'];
        $lead->status = $data['status'];
        $lead->source = $data['source'];

        return $lead->id;
    }

    public static function compileEntityForCreate($data, $em)
    {
        $lead = new \Skeletor\Lead\Entity\Lead();
        $lead->firstName = $data['firstName'] ?? null;
        $lead->lastName = $data['lastName'] ?? null;
        $lead->email = $data['email'];
        $lead->phoneNumber = $data['phoneNumber'] ?? null;
        $lead->status = $data['status'] ?? null;
        $lead->source = $data['source'] ?? null;
        $em->persist($lead);
        $em->flush();

        return $lead->id;
    }

    public static function make($entity, $entityData = [], $em = null): \Skeletor\Lead\Model\Lead
    {
        $data = $em->getUnitOfWork()->getOriginalEntityData($entity);
        return new \Skeletor\Lead\Model\Lead(...$data);
    }

}