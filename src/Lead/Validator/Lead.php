<?php

namespace Skeletor\Lead\Validator;

use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

class Lead implements ValidatorInterface
{
    // Was `private $messages;` -- untyped and undefaulted, so getMessages(): array
    // returned null and fatalled on any submission that produced no errors at all.
    private array $messages = [];

    public function __construct(
        protected \Skeletor\Lead\Repository\LeadRepository $repo,
        protected Csrf $csrf,
    ) { }

    public function isValid(array $data): bool
    {
        if (!$this->csrf->validate($data)) {
            throw new InvalidFormTokenException();
        }
        $valid = true;
        // filter_var rather than Laminas\Validator\EmailAddress, which this used to call:
        // laminas-validator is not a dependency of the framework, so that line was a fatal in
        // any app that had not happened to install it. It is also what every other email check
        // in the framework uses -- see Core\Login and User\Validator\Login.
        if (!filter_var((string) ($data['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            $this->messages['email'][] = 'Email you entered is not valid.';
            $valid = false;
        }
        $existingLead = null;
        $oldLeadData = null;
        if($data['id']) {
            try {
                $existingLead = $this->repo->findByEmail($data['email']);
            } catch (\Exception $e) {

            }
            $oldLeadData = $this->repo->getById($data['id']);
        }
        if ($data['id'] === null || ($existingLead && $oldLeadData && $existingLead->email !== $oldLeadData->email)) {
            if($this->repo->emailExists($data['email'])) {
                $this->messages['email'][] = 'Email you entered already exists in the system.';
                $valid = false;
            }
        }

        if($data['id']) {
            try {
                $existingLead = $this->repo->findByPhoneNumber($data['phoneNumber']);
            } catch (\Exception $e) {
                $existingLead = null;
            }
        }
        if($data['id'] === null || ($existingLead && $oldLeadData && $existingLead->phoneNumber !== $oldLeadData->phoneNumber)) {
            if($data['phoneNumber'] && trim($data['phoneNumber']) !== '' &&
                $data['phoneNumber'] &&
                $this->repo->phoneNumberExists(trim($data['phoneNumber']))) {
                $this->messages['phoneNumber'][] = 'The phone number you entered already exists in the system.';
                $valid = false;
            }
        }
        if($data['firstName'] && trim($data['firstName']) !== '' && strlen(trim($data['firstName'])) < 3) {
            $this->messages['firstName'][] = 'First name must be at least 3 characters long.';
            $valid = false;
        }
        if($data['lastName'] && trim($data['lastName']) !== '' && strlen(trim($data['lastName'])) < 3) {
            $this->messages['lastName'][] = 'Last name must be at least 3 characters long.';
            $valid = false;
        }
        return $valid;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}