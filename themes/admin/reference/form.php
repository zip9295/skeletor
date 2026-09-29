<?php

use Skeletor\Form\InputGroup\InputGroup;
use Skeletor\Form\InputGroup\InputGroupWidth;
use Skeletor\Form\InputTypes\Input\Text;
use Skeletor\Form\InputTypes\Select\Collection\OptionCollection;
use Skeletor\Form\InputTypes\Select\Select;
use Skeletor\Form\InputTypes\TextArea\TextArea;
use Skeletor\Form\Renderer\TabbedFormRenderer;
use Skeletor\Form\Tab\Tab;
use Skeletor\Form\TabbedForm;
use Skeletor\Reference\Entity\Reference;

$form = new TabbedForm($data['formAction'], $data['dataAction'], $this->formTokenArray());

$statusCollection = (new OptionCollection())->fromArray(Reference::getHrStatuses(), $data['model']?->status);

$title = (new Text('title', $data['model']?->title, 'Title', 'Title'))
    ->required('Title is required')
    ->minLength(2, 'Title must be at least 2 characters');

$statusSelect = (new Select('status', $statusCollection, 'Status'))
    ->required('Status is required', $statusCollection->getDefaultOption()->getValue());

$content = (new TextArea('content', $data['model']?->content, 'Content'))
    ->required('Content is required');

$comment = new TextArea('comment', $data['model']?->comment, 'Comment');

$groupOne = (new InputGroup(width: InputGroupWidth::FULL_WIDTH))
    ->addInput($title)
    ->addInput($statusSelect);

$groupTwo = (new InputGroup(width: InputGroupWidth::FULL_WIDTH))
    ->addInput($content)
    ->addInput($comment);

$form->addTab((new Tab('Basic Information'))->addInputGroup($groupOne));
$form->addTab((new Tab('Content'))->addInputGroup($groupTwo));

$formRenderer = new TabbedFormRenderer($form, $data['formTitle']);

?>
<?= $formRenderer->render() ?>
