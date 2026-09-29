<?php

use Skeletor\Form\InputGroup\InputGroup;
use Skeletor\Form\InputTypes\Input\Email;
use Skeletor\Form\InputTypes\Input\Hidden;
use Skeletor\Form\InputTypes\Input\Text;
use Skeletor\Form\Renderer\TabbedFormRenderer;
use Skeletor\Form\Tab\Tab;
use Skeletor\Form\TabbedForm;

$form = new TabbedForm($data['formAction'], $data['dataAction'], $this->formTokenArray());

$action = $data['dataAction'] === 'create' ? 'Create' : 'Edit';


$email = (new Email('email', $data['model']?->email ?? '', 'Email'))
    ->required('Email is required')
    ->emailInvalidMessage('Email is invalid');

$firstName = (new Text('firstName', $data['model']?->firstName ?? '', 'First Name'))
    ->minLength(3, 'First Name must be at least 3 characters long', applyOnlyWhenPopulated: true);

$lastName = (new Text('lastName', $data['model']?->lastName ?? '', 'Last Name'))
    ->minLength(3, 'Last Name must be at least 3 characters long', applyOnlyWhenPopulated: true);

$phoneNumber = (new Text('phoneNumber', $data['model']?->phoneNumber ?? '', 'Phone Number'));

$source = (new Text('source', $data['model']?->source ?? '', 'Source'));

$status = (new Hidden('status', $data['model']?->status ?? 'active'));



$groupOne = (new InputGroup())
    ->addInput($email)
    ->addInput($firstName)
    ->addInput($lastName)
    ->addInput($phoneNumber)
    ->addInput($source);


$userInfoTab = (new Tab('Lead Info'))
    ->addInputGroup($groupOne);

$form->addTab($userInfoTab);




$formRenderer = new TabbedFormRenderer($form, $data['formTitle']);



?>
<?= $formRenderer->render() ?>

