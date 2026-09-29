<?php

use Skeletor\Form\InputGroup\InputGroup;
use Skeletor\Form\InputGroup\InputGroupWidth;
use Skeletor\Form\InputTypes\File\Image;
use Skeletor\Form\InputTypes\Input\Checkbox;
use Skeletor\Form\InputTypes\Input\Text;
use Skeletor\Form\InputTypes\TextArea\TextArea;
use Skeletor\Form\Renderer\TabbedFormRenderer;
use Skeletor\Form\Tab\Tab;
use Skeletor\Form\TabbedForm;

$form = new TabbedForm($data['formAction'], $data['dataAction'], $this->formTokenArray());

$firstName = (new Text('firstName', $data['model']?->firstName, 'First name', 'First name'))
    ->required('First name is required');

$lastName = (new Text('lastName', $data['model']?->lastName, 'Last name', 'Last name'))
    ->required('Last name is required');

$displayName = new Text('displayName', $data['model']?->displayName, 'Display name', 'Defaults to first + last name');

$isActive = new Checkbox('isActive', (bool) ($data['model']?->isActive ?? true), 'Active');

$avatar = new Image(
    'avatarId',
    'Choose Image',
    'Avatar',
    $data['model']?->avatar?->filename ? '/images' . $data['model']?->avatar?->filename : '',
    $data['model']?->avatar?->id,
);

$groupOne = (new InputGroup(width: InputGroupWidth::FULL_WIDTH))
    ->addInput($firstName)
    ->addInput($lastName)
    ->addInput($displayName)
    ->addInput($isActive)
    ->addInput($avatar);

$shortDescription = new TextArea('shortDescription', $data['model']?->shortDescription, 'Short description');
$description = new TextArea('description', $data['model']?->description, 'Description');

$groupTwo = (new InputGroup(width: InputGroupWidth::FULL_WIDTH))
    ->addInput($shortDescription)
    ->addInput($description);

$seoTitle = (new Text('seoTitle', $data['model']?->seoTitle, 'SEO Title'))
    ->required('SEO Title is required');
$seoDescription = (new Text('seoDescription', $data['model']?->seoDescription, 'SEO Description'))
    ->required('SEO Description is required');
$seoImage = new Image(
    'seoImageId',
    'Choose Image',
    'SEO Image',
    $data['model']?->seoImage?->filename ? '/images' . $data['model']?->seoImage?->filename : '',
    $data['model']?->seoImage?->id,
);

$groupThree = (new InputGroup(width: InputGroupWidth::FULL_WIDTH))
    ->addInput($seoTitle)
    ->addInput($seoDescription)
    ->addInput($seoImage);

$form->addTab((new Tab('Basic Information'))->addInputGroup($groupOne));
$form->addTab((new Tab('Content'))->addInputGroup($groupTwo));
$form->addTab((new Tab('SEO'))->addInputGroup($groupThree));

$formRenderer = new TabbedFormRenderer($form, $data['formTitle']);

?>
<?= $formRenderer->render() ?>
