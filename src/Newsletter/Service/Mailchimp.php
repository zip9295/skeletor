<?php

namespace Skeletor\Newsletter\Service;

use GuzzleHttp\Exception\ClientException;
use MailchimpMarketing\ApiClient;

class Mailchimp
{
    const STATUS_SUBSCRIBED = 'subscribed';

    public function __construct(private ApiClient $client)
    {}

    public function subscribe($email, $listId, $mergeFields): bool
    {
        try {
            $memberHash = md5($email);
            $member = $this->memberExistsInList($email, $listId);
            if(!$member) {
                $this->client->lists->setListMember($listId, $memberHash, [
                    'email_address' => $email,
                    'status' => self::STATUS_SUBSCRIBED,
                    'status_if_new' => self::STATUS_SUBSCRIBED,
                    'merge_fields' => $mergeFields
                ]);
                return true;
            }
            if(property_exists($member, 'status')) {
                if($member->status !== self::STATUS_SUBSCRIBED) {
                    $this->client->lists->updateListMember($listId, $memberHash, [
                        'status' => self::STATUS_SUBSCRIBED
                    ]);
                }
            }
        } catch(\Exception $e) {
            return false;
        }
        return true;
    }

    public function memberExistsInList($email, $listId)
    {
        try {
            return $this->client->lists->getListMember($listId, md5($email));
        } catch(ClientException $e) {
            return false;
        }
    }
}