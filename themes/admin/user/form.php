<?php

use Skeletor\Form\InputGroup\InputGroup;
use Skeletor\Form\InputGroup\InputGroupWidth;
use Skeletor\Form\InputTypes\ContentEditor\ContentEditor;
use Skeletor\Form\InputTypes\Input\Email;
use Skeletor\Form\InputTypes\Input\Password;
use Skeletor\Form\InputTypes\Input\Text;
use Skeletor\Form\InputTypes\Select\Collection\OptionCollection;
use Skeletor\Form\InputTypes\Select\Select;
use Skeletor\Form\Renderer\TabbedFormRenderer;
use Skeletor\Form\Tab\Tab;
use Skeletor\Form\TabbedForm;

$form = new TabbedForm($data['formAction'], $data['dataAction'], $this->formTokenArray());

$action = $data['dataAction'] === 'create' ? 'Create' : 'Edit';

//@todo some roles can't change some settings, for example staff cant change their role or other user roles
$roles = [1 => 'Admin', 5 => 'Staff'];
$statuses = [1 => 'Active', 0 => 'Inactive'];
$rolesCollection = (new OptionCollection())->fromArray($roles, $data['model']?->getRole());
$statusCollection = (new OptionCollection())->fromArray($statuses, $data['model']?->getIsActive());

//$email = (new Email('email', $data['model']?->getEmail(), 'Email'))
//    ->required('Email is required')
//    ->emailInvalidMessage('Email is invalid');

$email = (new Text('email', $data['model']?->getEmail(), 'Email'))
    ->required('Email field is required');

$firstName = (new Text('firstName', $data['model']?->getFirstName(), 'First Name'))
    ->required('First Name is required')
    ->minLength(1, 'First Name must be at least 1 characters');

$lastName = (new Text('lastName', $data['model']?->getLastName(), 'Last Name'))
    ->required('Last Name is required')
    ->minLength(1, 'Last Name must be at least 1 characters');

$displayName = (new Text('displayName', $data['model']?->getDisplayName(), 'Display Name'))
    ->required('Display Name is required')
    ->minLength(2, 'Display Name must be at least 2 characters');

$password = (new Password('password', '', 'Password', id: 'password'))
    ->required('Password is required', $action === 'Create')
    ->minLength(6, 'Password must be at least 6 characters', $action === 'Create')
    ->matches('confirmPassword', 'Passwords do not match');

$confirmPassword = (new Password('password2', '', 'Confirm Password', id: 'confirmPassword'))
    ->required('Confirm Password is required', $action === 'Create')
    ->minLength(6, 'Confirm Password must be at least 6 characters', $action === 'Create')
    ->matches('password', 'Passwords do not match');

$rolesSelect = (new Select('role', $rolesCollection, 'Role'))
    ->required('Role is required', $rolesCollection->getDefaultOption()->getValue());

$statusSelect = (new Select('isActive', $statusCollection, 'Status'))
    ->required('Status is required', $statusCollection->getDefaultOption()->getValue());



$groupOne = (new InputGroup())
    ->addInput($email)
    ->addInput($firstName)
    ->addInput($lastName);

$groupTwo = (new InputGroup())
    ->addInput($displayName)
    ->addInput($password)
    ->addInput($confirmPassword);

$groupThree = (new InputGroup())
    ->addInput($rolesSelect)
    ->addInput($statusSelect);

$userInfoTab = (new Tab('User Info'))
    ->addInputGroup($groupOne)
    ->addInputGroup($groupTwo)
    ->addInputGroup($groupThree);

$form->addTab($userInfoTab);




$formRenderer = new TabbedFormRenderer($form, $data['formTitle']);



?>
<?= $formRenderer->render() ?>

