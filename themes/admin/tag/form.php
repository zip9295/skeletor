<?php

use Skeletor\Form\InputGroup\InputGroup;
use Skeletor\Form\InputGroup\InputGroupWidth;
use Skeletor\Form\InputTypes\File\Image;
use Skeletor\Form\InputTypes\Input\Text;
use Skeletor\Form\Renderer\TabbedFormRenderer;
use Skeletor\Form\Tab\Tab;
use Skeletor\Form\TabbedForm;

$form = new TabbedForm($data['formAction'], $data['dataAction'], $this->formTokenArray());

$action = $data['dataAction'] === 'create' ? 'Create' : 'Edit';


$title = (new Text('title', $data['model']?->title, 'Title', 'Title'))
    ->required('Title is required')
    ->minLength(5, 'Title must be at least 5 characters');

$slug = (new Text('slug', $data['model']?->slug, 'Slug', 'Slug'));



$groupOne = (new InputGroup(width: InputGroupWidth::FULL_WIDTH))
    ->addInput($title)
    ->addInput($slug);

$seoTitle = (new Text('seoTitle', $data['model']?->seoTitle, 'SEO Title'))->required('SEO Title is required');
$seoDescription = (new Text('seoDescription', $data['model']?->seoDescription, 'SEO Description'))->required('SEO Description is required');
$seoImage = (new Image(
    'seoImageId',
    'Choose Image',
    'SEO Image',
    $data['model']?->seoImage?->filename ? '/images' . $data['model']?->seoImage?->filename : '',
    $data['model']?->seoImage?->id,
))->required('SEO Image is required');
$groupTwo = (new InputGroup(width: InputGroupWidth::FULL_WIDTH))
    ->addInput($seoTitle)
    ->addInput($seoDescription)
    ->addInput($seoImage);

$basicInfoTab = (new Tab('Basic Information'))->addInputGroup($groupOne);
$seoTab = (new Tab('SEO'))->addInputGroup($groupTwo);

$form->addTab($basicInfoTab);
$form->addTab($seoTab);


$formRenderer = new TabbedFormRenderer($form, $data['formTitle']);

?>
<?= $formRenderer->render() ?>