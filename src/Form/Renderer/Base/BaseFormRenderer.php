<?php

namespace Skeletor\Form\Renderer\Base;

use Skeletor\Form\Attribute\Contracts\AttributeCollectionInterface;
use Skeletor\Form\Contracts\FormDataInterface;
use Skeletor\Form\InputGroup\Contracts\InputGroupInterface;
use Skeletor\Form\InputTypes\Contracts\AjaxInputSearchInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\AjaxMultipleValuesSearchInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\CheckboxInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\ColorInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\ContentEditorInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\DateInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\DateTimeInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\DocumentInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\DocumentsInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\DynamicInputCollectionInterface;
use Skeletor\Form\InputTypes\Contracts\DynamicInputsInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\EmailInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\GalleryInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\HiddenInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\ImageInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\InputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\MonthInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\MultipleSelectInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\NumberInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\PasswordInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\SelectInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\SelectWithSearchInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\TextAreaInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\TextEditorInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\TextInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\TimeInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\ValuesGeneratorInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\WeekInputTypeInterface;
use Skeletor\Form\Renderer\Contracts\FormRendererInterface;
use WeakMap;

abstract class BaseFormRenderer implements FormRendererInterface
{

    protected WeakMap $beforeInputGroupContent;
    protected WeakMap $beforeInputContent;

    protected WeakMap $afterInputGroupContent;

    protected WeakMap $afterInputContent;

    public function __construct(protected FormDataInterface $form, protected string $formTitle)
    {
        $this->beforeInputGroupContent = new WeakMap();
        $this->beforeInputContent = new WeakMap();
        $this->afterInputGroupContent = new WeakMap();
        $this->afterInputContent = new WeakMap();
    }

    public function setContentBeforeInputGroup(InputGroupInterface $inputGroup, string $content): void
    {
        $this->beforeInputGroupContent[$inputGroup] = $content;
    }

    public function setContentBeforeInput(InputTypeInterface $input, string $content): void
    {
        $this->beforeInputContent[$input] = $content;
    }

    public function setContentAfterInputGroup(InputGroupInterface $inputGroup, string $content): void
    {
        $this->afterInputGroupContent[$inputGroup] = $content;
    }

    public function setContentAfterInput(InputTypeInterface $input, string $content): void
    {
        $this->afterInputContent[$input] = $content;
    }

    public function setDividerBeforeInputGroup(InputGroupInterface $inputGroup): void
    {
        $this->beforeInputGroupContent[$inputGroup] = '<div class="divider"></div>';
    }

    public function setDividerAfterInputGroup(InputGroupInterface $inputGroup): void
    {
        $this->afterInputGroupContent[$inputGroup] = '<div class="divider"></div>';
    }

    protected function renderBeforeInputGroupContent(InputGroupInterface $inputGroup): string
    {
        return $this->beforeInputGroupContent[$inputGroup] ?? '';
    }

    protected function renderBeforeInputContent(InputTypeInterface $input): string
    {
        return $this->beforeInputContent[$input] ?? '';
    }

    protected function renderAfterInputGroupContent(InputGroupInterface $inputGroup): string
    {
        return $this->afterInputGroupContent[$inputGroup] ?? '';
    }

    protected function renderAfterInputContent(InputTypeInterface $input): string
    {
        return $this->afterInputContent[$input] ?? '';
    }

    protected function getFormTitleAsHTML(): string
    {
        return sprintf('<h1>%s</h1>', $this->formTitle);
    }

    protected function getFormStart(): string
    {
        return sprintf(
            '<form id="%s" action="%s" method="%s" enctype="%s" data-action="%s">',
            $this->form->getId(),
            $this->form->getAction(),
            $this->form->getMethod(),
            $this->form->getEnctype(),
            $this->form->getDataAction()
        );
    }

    protected function getCsrfToken(): string
    {
        $tokenData = $this->form->getCsrfToken();
        $name = array_key_first($tokenData);
        $value = $tokenData[$name];
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            $name,
            $value
        );
    }

    protected function getInputGroup(InputGroupInterface $inputGroup): string
    {
        $html = $this->renderBeforeInputGroupContent($inputGroup);
        $html .= sprintf(
            '<div class="column%s">%s%s</div>',
            $inputGroup->getWidth()?->value ? ' ' . $inputGroup->getWidth()?->value : '',
            $inputGroup->getLabel() ? sprintf('<h2>%s</h2>', $inputGroup->getLabel()) : '',
            $this->getInputs($inputGroup)
        );
        $html .= $this->renderAfterInputGroupContent($inputGroup);
        return $html;
    }

    protected function getInputs(InputGroupInterface $inputGroup): string
    {
        $html = '';
        foreach ($inputGroup->getInputCollection()->getIterator() as $input) {
            $html .= $this->getInput($input);
        }
        return $html;
    }

    protected function getInput(InputTypeInterface $input): string
    {
        $html = $this->renderBeforeInputContent($input);
        $html .= match (true) {
            $input instanceof TextInputTypeInterface => $this->renderInputWithType('text', $input),
            $input instanceof NumberInputTypeInterface => $this->renderInputWithType('number', $input),
            $input instanceof EmailInputTypeInterface => $this->renderInputWithType('email', $input),
            $input instanceof PasswordInputTypeInterface => $this->renderInputWithType('password', $input),
            $input instanceof CheckboxInputTypeInterface => $this->renderInputWithType('checkbox', $input),
            $input instanceof ColorInputTypeInterface => $this->renderInputWithType('color', $input),
            $input instanceof DateInputTypeInterface => $this->renderInputWithType('date', $input),
            $input instanceof TimeInputTypeInterface => $this->renderInputWithType('time', $input),
            $input instanceof DateTimeInputTypeInterface => $this->renderInputWithType('datetime-local', $input),
            $input instanceof WeekInputTypeInterface => $this->renderInputWithType('week', $input),
            $input instanceof MonthInputTypeInterface => $this->renderInputWithType('month', $input),
            $input instanceof HiddenInputTypeInterface => $this->renderInputWithType('hidden', $input),
            $input instanceof SelectInputTypeInterface => $this->renderSelectByType($input),
            $input instanceof ImageInputTypeInterface => $this->renderImageInput($input),
            $input instanceof DocumentInputTypeInterface => $this->renderDocumentInput($input),
            $input instanceof GalleryInputTypeInterface => $this->renderGalleryInput($input),
            $input instanceof DocumentsInputTypeInterface => $this->renderDocumentsInput($input),
            $input instanceof AjaxInputSearchInputTypeInterface => $this->renderAjaxInputSearch($input),
            $input instanceof TextEditorInputTypeInterface => $this->renderTextEditor($input),
            $input instanceof ValuesGeneratorInputTypeInterface => $this->renderValuesGenerator($input),
            $input instanceof TextAreaInputTypeInterface => $this->renderTextArea($input),
            $input instanceof AjaxMultipleValuesSearchInputTypeInterface => $this->renderAjaxMultipleValuesSearch($input),
            $input instanceof ContentEditorInputTypeInterface => $this->renderContentEditor($input),
            $input instanceof DynamicInputsInputTypeInterface => $this->renderDynamicInputs($input),
            default => throw new \Exception('Unknown input type')
        };
        $html .= $this->renderAfterInputContent($input);
        return $html;
    }

    protected function renderSelectByType(SelectInputTypeInterface $input): string
    {
        return match(true) {
            $input instanceof SelectWithSearchInputTypeInterface => $this->renderSelectWithSearchInput($input),
            $input instanceof MultipleSelectInputTypeInterface => $this->renderMultipleSelectInput($input),
            default => $this->renderSelectInput($input)
        };
    }

    protected function renderInputWithType(string $type, InputTypeInterface $input): string
    {
        $placeholder = method_exists($input, 'getPlaceholder') ? $input->getPlaceholder() : '';
        $value = method_exists($input, 'getValue') ? $input->getValue() : '';
        $isChecked = null;
        if($input instanceof CheckboxInputTypeInterface) {
            $isChecked = $input->isChecked();
        }
        $template = '<label class="inputContainer">%s
                        <input %s %s class="input%s" type="%s" name="%s" %s %s %s %s>
                         %s
                    </label>
                   ';
        if ($input instanceof HiddenInputTypeInterface) {
            $template = '<input %s %s %s class="input%s" type="%s" name="%s" %s %s %s %s>%s';
        }

        return sprintf(
            $template,
            $input->hasAttribute('data-required') ? $input->getLabel() . ' *' : $input->getLabel() ?? '',
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'readonly' : '',
            $input->getId() ? 'id="' . $input->getId() . '"' : '',
            $this->getClassListAsString($input->getClassList()) ? ' ' . $this->getClassListAsString($input->getClassList()) : '',
            $type,
            $input->getName(),
            $value !== '' ? 'value="' . $value . '"' : '',
            ($placeholder && $placeholder !== '') ? 'placeholder="' . $placeholder . '"' : '',
            $this->getAttributesAsString($input->getAttributeCollection()),
            $isChecked !== null ? ($isChecked ? 'checked' : '') : '',
            $this->renderTooltip($input)
        );
    }

    protected function renderSelectInput(SelectInputTypeInterface $input): string
    {
        $options = $this->getSelectOptions($input);
        return sprintf(
            '<div class="inputContainer">
                        <label>%s</label>
                        <select %s %s class="input %s" name="%s" %s>
                            %s
                        </select>
                        %s
                    </div>',
            $input->hasAttribute('data-required') ? $input->getLabel() . ' *' : $input->getLabel() ?? '',
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'readonly' : '',
            $input->getId() ? 'id="' . $input->getId() . '"' : '',
            $this->getClassListAsString($input->getClassList()),
            $input->getName(),
            $this->getAttributesAsString($input->getAttributeCollection()),
            $options,
            $this->renderTooltip($input)
        );
    }
    protected function renderImageInput(ImageInputTypeInterface $input): string
    {
        return sprintf(
            '<div class="inputContainer imageSelect mediaLibraryInitiator"
                        data-image-input-name="%s" data-insertable="true" data-multiple="false"
                        data-images="true" data-documents="false" %s>
                        <label>%s</label>
                         <div class="imagePreview">
                                <span class="chooseImageText">%s
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path d="M149.1 64.8L138.7 96H64C28.7 96 0 124.7 0 160V416c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V160c0-35.3-28.7-64-64-64H373.3L362.9 64.8C356.4 45.2 338.1 32 317.4 32H194.6c-20.7 0-39 13.2-45.5 32.8zM256 192a96 96 0 1 1 0 192 96 96 0 1 1 0-192z"/>
                                </svg>
                                </span>
                                <input %s type="text" class="input %s" %s %s>   
                                <div class="removeImage">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>
                                </div>      
                                %s   
                        </div>
                        %s
                    </div>',
            $input->getName(),
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'data-readonly="true"' : '',
            $input->hasAttribute('data-required') ? $input->getLabel() . ' *' : $input->getLabel() ?? '',
            $input->getChooseImageText(),
            $input->getId() ? 'value="' . $input->getId() . '"' : '',
            $this->getClassListAsString($input->getClassList()),
            $this->getAttributesAsString($input->getAttributeCollection()),
            $input->getImageId() ? 'value="' . $input->getImageId() . '"' : '',
            $input->getSrc() ? sprintf('<img src="%s" alt="%s">', $input->getSrc(), $input->getLabel()) : '',
            $this->renderTooltip($input)
        );
    }

    protected function renderDocumentInput(DocumentInputTypeInterface $input): string
    {
        return sprintf(
            '<div class="inputContainer documentSelect mediaLibraryInitiator"
                        data-document-input-name="%s" data-insertable="true" data-multiple="false"
                        data-images="false" data-documents="true" %s>
                        <label>%s</label>
                         <div class="documentPreview">
                                <span class="chooseDocumentText">%s
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                        <path d="M64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V160H256c-17.7 0-32-14.3-32-32V0H64zM256 0V128H384L256 0zM112 256H272c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64H272c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64H272c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16s7.2-16 16-16z"/>
                                    </svg>
                                </span>
                                <input %s type="text" class="input %s" %s %s>   
                                 <div class="removeDocument">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>
                                </div>     
                                %s   
                        </div>
                        %s
                    </div>',
            $input->getName(),
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'data-readonly="true"' : '',
            $input->hasAttribute('data-required') ? $input->getLabel() . ' *' : $input->getLabel() ?? '',
            $input->getChooseDocumentText(),
            $input->getId() ? 'value="' . $input->getId() . '"' : '',
            $this->getClassListAsString($input->getClassList()),
            $this->getAttributesAsString($input->getAttributeCollection()),
            $input->getDocumentId() ? 'value="' . $input->getDocumentId() . '"' : '',
            $input->getFilename() ? sprintf('
            <div class="documentPreviewElement" title="%s">
                <div class="documentIcon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M320 464c8.8 0 16-7.2 16-16V160H256c-17.7 0-32-14.3-32-32V48H64c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H320zM0 64C0 28.7 28.7 0 64 0H229.5c17 0 33.3 6.7 45.3 18.7l90.5 90.5c12 12 18.7 28.3 18.7 45.3V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64z"></path></svg>
                </div>
                <span>%s</span>
            </div> 
            ', $input->getFilename(), $input->getFilename()) : '',
            $this->renderTooltip($input)
        );
    }

    protected function renderGalleryInput(GalleryInputTypeInterface $input): string
    {
        $images = '';
        foreach($input->getImageData() as $id => $src) {
            $images .= sprintf('
            <div class="galleryImage">
                <input type="hidden" name="" value="%s">
                <img src="%s" alt="">
                <div class="removeImage">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>
                </div>
            </div>
            ',
                $id,
                $src
            );
        }
        return sprintf('
        <div class="gallerySelect mediaLibraryInitiator inputContainer" data-insertable="true" data-multiple="true" data-documents="false" data-images="true"
                    data-gallery-input-name="%s" %s>
                        <label>%s</label>
                        <div class="galleryImages">
                                <div class="galleryText">                       
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M0 96C0 60.7 28.7 32 64 32H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM323.8 202.5c-4.5-6.6-11.9-10.5-19.8-10.5s-15.4 3.9-19.8 10.5l-87 127.6L170.7 297c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6h96 32H424c8.9 0 17.1-4.9 21.2-12.8s3.6-17.4-1.4-24.7l-120-176zM112 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg>
                                </div>
                                %s
                        </div>
                        %s
                    </div>'
            ,
            $input->getName(),
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'data-readonly="true"' : '',
            $input->hasAttribute('data-required') ? $input->getLabel() . ' *' : $input->getLabel() ?? '',
            $images,
            $this->renderTooltip($input)
        );
    }

    protected function renderDocumentsInput(DocumentsInputTypeInterface $input): string
    {
        $documents = '';
        foreach($input->getDocumentData() as $id => $filename) {
            $documents .= sprintf('
            <div class="document">
                <input type="hidden" name="" value="%s">
                <div class="documentPreviewInDocuments" title="somefile.pdf">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M320 464c8.8 0 16-7.2 16-16V160H256c-17.7 0-32-14.3-32-32V48H64c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H320zM0 64C0 28.7 28.7 0 64 0H229.5c17 0 33.3 6.7 45.3 18.7l90.5 90.5c12 12 18.7 28.3 18.7 45.3V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64z"></path></svg>
                    </div>
                    <span>%s</span>
                </div>
                <div class="removeDocument">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>
                </div>
            </div>
            ',
                $id,
                $filename
            );
        }
        return sprintf('
        <div class="documentsSelect mediaLibraryInitiator inputContainer" data-insertable="true" data-multiple="true" data-documents="true" data-images="false"
                    data-documents-input-name="%s" %s>
                        <label>%s</label>
                        <div class="documentsContainer">
                                <div class="documentsText">                       
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M384 480h48c11.4 0 21.9-6 27.6-15.9l112-192c5.8-9.9 5.8-22.1 .1-32.1S555.5 224 544 224H144c-11.4 0-21.9 6-27.6 15.9L48 357.1V96c0-8.8 7.2-16 16-16H181.5c4.2 0 8.3 1.7 11.3 4.7l26.5 26.5c21 21 49.5 32.8 79.2 32.8H416c8.8 0 16 7.2 16 16v32h48V160c0-35.3-28.7-64-64-64H298.5c-17 0-33.3-6.7-45.3-18.7L226.7 50.7c-12-12-28.3-18.7-45.3-18.7H64C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H87.7 384z"/></svg>
                                </div>
                               %s
                        </div>
                        %s
                    </div>'
            ,
            $input->getName(),
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'data-readonly="true"' : '',
            $input->hasAttribute('data-required') ? $input->getLabel() . ' *' : $input->getLabel() ?? '',
            $documents,
            $this->renderTooltip($input)
        );
    }

    protected function renderAjaxInputSearch(AjaxInputSearchInputTypeInterface $input): string
    {
        $html = $this->renderBeforeInputContent($input);
        $html .= sprintf('
        <div class="inputContainer ajaxInputSearch" data-endpoint="%s" data-view-column-name="%s" data-id-column-name="%s" data-search-filters="%s" %s>
            <label>%s</label>
            <input class="input targetInput" name="%s" value="%s">
            <input class="input viewInput%s" type="text" readonly value="%s" %s %s>
            %s
            <div class="removeAjaxInputSearchValue">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>
            </div>
        </div>
        ',
            $input->getEndpoint(),
            $input->getViewColumnName(),
            $input->getIdColumnName(),
            htmlentities($input->getFiltersJSON() ?? ''),
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'data-readonly="true"' : '',
            $input->hasAttribute('data-required') ? $input->getLabel() . ' *' : $input->getLabel() ?? '',
            $input->getName(),
            $input->getValue() ?? '',
            $this->getClassListAsString($input->getClassList()) ? ' ' . $this->getClassListAsString($input->getClassList()) : '',
            $input->getViewValue() ?? '',
            $this->getAttributesAsString($input->getAttributeCollection()),
            $input->getPlaceholder() ? 'placeholder="' . $input->getPlaceholder() . '"' : '',
            $this->renderTooltip($input)
        );

        $html .= $this->renderAfterInputContent($input);
        return $html;
    }

    protected function getSelectOptions(SelectInputTypeInterface $input): string
    {
        $options = '';
        foreach ($input->getOptionsCollection()->getIterator() as $option) {
            $options .= sprintf(
                '<option value="%s" %s>%s</option>',
                $option->getValue(),
                $option->isSelected() ? 'selected' : '',
                $option->getText()
            );
        }
        return $options;
    }

    protected function renderSelectWithSearchInput(SelectWithSearchInputTypeInterface $input): string
    {
        $options = $this->getSelectOptions($input);
        return sprintf(
            '<div class="inputContainer selectSearchContainer" %s>
                        <label>%s</label>
                        <select %s %s class="input %s" name="%s" %s>
                            %s
                        </select>
                        %s
                        <div class="selectSearchOverlay"></div>
                    </div>',
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'data-readonly="true"' : '',
            $input->hasAttribute('data-required') ? $input->getLabel() . ' *' : $input->getLabel() ?? '',
            $this->form->isReadOnly() ? 'disabled' : '',
            $input->getId() ? 'id="' . $input->getId() . '"' : '',
            $this->getClassListAsString($input->getClassList()),
            $input->getName(),
            $this->getAttributesAsString($input->getAttributeCollection()),
            $options,
            $this->renderTooltip($input)
        );
    }

    protected function renderMultipleSelectInput(MultipleSelectInputTypeInterface $input): string
    {
        $options = $this->getSelectOptions($input);
        $selectedOptionsHTML = '';
        $optionsToSelectHTML = '';
        foreach($input->getOptionsCollection()->getIterator() as $option) {
            $selectedOptionsHTML .= sprintf('
             <div class="selectedOption%s" data-value="%s">%s
                <div class="remove" data-value="%s">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>
                </div>
            </div>
            ',
                $option->isSelected() ? ' selected' : '',
                $option->getValue(),
                $option->getText(),
                $option->getValue()
            );

            $optionsToSelectHTML .= sprintf('
             <div class="optionToSelect%s" data-value="%s">%s</div>
            ',
                $option->isSelected() ? ' selected' : '',
                $option->getValue(),
                $option->getText(),
            );
        }
        return sprintf(
            '<div class="inputContainer">
                        <label>%s</label>
                            <div class="multipleSelect" %s>
                                <select class="input%s" name="%s" multiple %s>
                                %s
                                </select>
                                <div class="selectClone">
                                        <div class="selectedOptions">
                                        %s
                                        </div>
                                        <div class="optionsToSelect">
                                        %s
                                        </div>
                                </div>                  
                            </div>
                              %s
                    </div>',
            $input->hasAttribute('data-required') ? $input->getLabel() . ' *' : $input->getLabel() ?? '',
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'data-readonly="true"' : '',
            $this->getClassListAsString($input->getClassList()) ? ' ' . $this->getClassListAsString($input->getClassList()) : '',
            $input->getName(),
            $this->getAttributesAsString($input->getAttributeCollection()),
            $options,
            $selectedOptionsHTML,
            $optionsToSelectHTML,
            $this->renderTooltip($input)

        );
    }

    protected function renderTextEditor(TextEditorInputTypeInterface|InputTypeInterface $input): string
    {
        return sprintf('
        <div class="inputContainer">
            <label>%s</label>
            <div class="textEditor" %s %s %s></div>
            <input type="hidden" %s name="%s" class="textEditorInput">
            %s
        </div>
        ',
            $input->hasAttribute('data-required') ? $input->getLabel() . ' *' : $input->getLabel() ?? '',
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'data-readonly="true"' : '',
            $this->getClassListAsString($input->getClassList()) ? ' ' . $this->getClassListAsString($input->getClassList()) : '',
            $this->getAttributesAsString($input->getAttributeCollection()),
            $input->getValue() ? 'value="' . htmlentities($input->getValue() ?? '') . '"' : '',
            $input->getName(),
            $this->renderTooltip($input)
        );
    }

    protected function renderValuesGenerator(ValuesGeneratorInputTypeInterface $input): string
    {
        $valuesHTML = '';
        foreach($input->getValuesCollection()->getIterator() as $value) {
            $valuesHTML .= sprintf('
                            <div class="value">
                                <input type="hidden" value="%s">    
                                <span>%s</span>
                                <div class="removeValue"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"></path></svg></div>
                            </div>'
                ,
                $value->getValue(),
                $value->getText()
            );
        }

        return sprintf('
            <div class="inputContainer valuesGenerator%s" data-input-name-values="%s" data-input-name-new-values="%s" %s>
            <label>%s</label>
            <input type="text" class="input preventEnterSubmit formObserverIgnore inputForNewValues" %s>
            <div class="valuesContainer">
                <input type="text" class="searchValuesInput preventEnterSubmit input formObserverIgnore" %s>
                 %s
            </div>
            %s
            </div>
            '
            ,
            $this->getAttributesAsString($input->getAttributeCollection()),
            $input->getName() . '[' . $input->getExistingValueKey() . '][]',
            $input->getName() . '[' . $input->getNewValueKey() . '][]',
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'data-readonly="true"' : '',
            $input->hasAttribute('data-required') ? $input->getLabel() . ' *' : $input->getLabel() ?? '',
            $input->getPlaceholder() ? 'placeholder="' . $input->getPlaceholder() . '"' : '',
            $input->getSearchPlaceholder() ? 'placeholder="' . $input->getSearchPlaceholder() . '"' : '',
            $valuesHTML,
            $this->renderTooltip($input)
        );
    }

    protected function renderTextArea(TextAreaInputTypeInterface $input): string
    {
        return sprintf('
        <div class="inputContainer">
            <label>%s</label>
            <textarea spellcheck="false" %s class="input%s" name="%s" %s %s>%s</textarea>
            %s
        </div>
        ',
            $input->hasAttribute('data-required') ? $input->getLabel() . ' *' : $input->getLabel() ?? '',
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'readonly' : '',
            $this->getClassListAsString($input->getClassList()) ? ' ' . $this->getClassListAsString($input->getClassList()) : '',
            $input->getName(),
            $this->getAttributesAsString($input->getAttributeCollection()),
            $input->getPlaceholder() ? 'placeholder="' . $input->getPlaceholder() . '"' : '',
            $input->getValue() ?? '',
            $this->renderTooltip($input)
        );
    }

    protected function renderAjaxMultipleValuesSearch(AjaxMultipleValuesSearchInputTypeInterface $input): string
    {
        $valuesHTML = '';
        if($input->getValuesCollection()) {
            foreach ($input->getValuesCollection()->getIterator() as $value) {
                $valuesHTML .= sprintf(
                    '
                            <div class="value">
                                <span class="valueText">%s</span>
                                <input type="hidden" name="%s" value="%s" class="multipleValuesSearchInput">
                                <div class="removeValue">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"></path></svg>
                                </div>
                            </div>'
                    ,
                    $value->getText(),
                    $input->getName() . '[' . $input->getValuesKey() . '][]',
                    $value->getValue()
                );
            }
        }
        $label = '';
        if($input->getLabel()) {
            if($input->hasAttribute('data-required')) {
                $label = '<label>' . $input->getLabel() . ' *' . '</label>';
            } else {
                $label = '<label>' . $input->getLabel() . '</label>';
            }
        }
        return sprintf('
                                 <div class="inputContainer ajaxMultipleValuesSearch%s" 
                                     %s
                                     %s
                                     data-endpoint="%s" 
                                     data-view-column-name="%s" 
                                     data-id-column-name="%s" 
                                     data-search-filters="%s" 
                                     data-input-name="%s" 
                                     %s
                                     >
                                     %s
                                     <input class="input viewInput" type="text" readonly value="" placeholder="%s">
                                     <div class="valuesWrapper">
                                        <input type="text" class="searchValuesInput input formObserverIgnore" placeholder="%s">
                                        <div class="valuesContainer">
                                            %s
                                        </div>
                                     </div>
                                     %s
                                 </div>
            ',

            $this->getClassListAsString($input->getClassList()),
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'data-readonly="true"' : '',
            $this->getAttributesAsString($input->getAttributeCollection()),
            $input->getEndpoint(),
            $input->getViewColumnName(),
            $input->getIdColumnName(),
            htmlentities($input->getFiltersJSON() ?? ''),
            $input->getName() . '[' . $input->getValuesKey() .'][]',
            $input->getCreatedValuesKey() ? sprintf('data-new-input-name="%s"', $input->getName() . '[' . $input->getCreatedValuesKey() .'][]',) : '',
            $label,
            $input->getSearchPlaceholder(),
            $input->getSearchValuesPlaceholder(),
            $valuesHTML,
            $this->renderTooltip($input),
        );
    }

    protected function renderContentEditor(ContentEditorInputTypeInterface $input): string
    {
        return sprintf('
        <div %s class="contentEditor%s" data-content="%s" %s>
            <div class="content">
            </div>
            <div class="addBlockContainer">
               <div class="addBlock">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M64 80c-8.8 0-16 7.2-16 16V416c0 8.8 7.2 16 16 16H384c8.8 0 16-7.2 16-16V96c0-8.8-7.2-16-16-16H64zM0 96C0 60.7 28.7 32 64 32H384c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM200 344V280H136c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V168c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H248v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg>                   
                </div>
                <div class="blockList">
                    <input class="searchBlocks input formObserverIgnore" type="text" placeholder="%s">
                    <div class="blockListWrapper"></div>
                </div>
            </div>   
            <div class="contentEditorShortcuts">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M64 112c-8.8 0-16 7.2-16 16V384c0 8.8 7.2 16 16 16H512c8.8 0 16-7.2 16-16V128c0-8.8-7.2-16-16-16H64zM0 128C0 92.7 28.7 64 64 64H512c35.3 0 64 28.7 64 64V384c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128zM176 320H400c8.8 0 16 7.2 16 16v16c0 8.8-7.2 16-16 16H176c-8.8 0-16-7.2-16-16V336c0-8.8 7.2-16 16-16zm-72-72c0-8.8 7.2-16 16-16h16c8.8 0 16 7.2 16 16v16c0 8.8-7.2 16-16 16H120c-8.8 0-16-7.2-16-16V248zm16-96h16c8.8 0 16 7.2 16 16v16c0 8.8-7.2 16-16 16H120c-8.8 0-16-7.2-16-16V168c0-8.8 7.2-16 16-16zm64 96c0-8.8 7.2-16 16-16h16c8.8 0 16 7.2 16 16v16c0 8.8-7.2 16-16 16H200c-8.8 0-16-7.2-16-16V248zm16-96h16c8.8 0 16 7.2 16 16v16c0 8.8-7.2 16-16 16H200c-8.8 0-16-7.2-16-16V168c0-8.8 7.2-16 16-16zm64 96c0-8.8 7.2-16 16-16h16c8.8 0 16 7.2 16 16v16c0 8.8-7.2 16-16 16H280c-8.8 0-16-7.2-16-16V248zm16-96h16c8.8 0 16 7.2 16 16v16c0 8.8-7.2 16-16 16H280c-8.8 0-16-7.2-16-16V168c0-8.8 7.2-16 16-16zm64 96c0-8.8 7.2-16 16-16h16c8.8 0 16 7.2 16 16v16c0 8.8-7.2 16-16 16H360c-8.8 0-16-7.2-16-16V248zm16-96h16c8.8 0 16 7.2 16 16v16c0 8.8-7.2 16-16 16H360c-8.8 0-16-7.2-16-16V168c0-8.8 7.2-16 16-16zm64 96c0-8.8 7.2-16 16-16h16c8.8 0 16 7.2 16 16v16c0 8.8-7.2 16-16 16H440c-8.8 0-16-7.2-16-16V248zm16-96h16c8.8 0 16 7.2 16 16v16c0 8.8-7.2 16-16 16H440c-8.8 0-16-7.2-16-16V168c0-8.8 7.2-16 16-16z"/></svg>
            </div>           
        </div>'
            ,
            $input->getId() ? 'id="' . $input->getId() . '"' : '',
            $this->getClassListAsString($input->getClassList()),
            htmlentities($input->getJsonContent()),
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'data-readonly="true"' : '',
            $input->getSearchBlocksPlaceholder()
        );
    }

    protected function renderDynamicInputs(DynamicInputsInputTypeInterface $input): string
    {
        $html = sprintf('<div %s data-base-name="%s" %s class="inputContainer dynamicInputs%s" %s %s>%s',
            $input->getId() ? 'id="' . $input->getId() . '"' : '',
            $input->getName(),
            $this->getDynamicInputConfigAsJSONString($input->getInputCollection()),
            $this->getClassListAsString($input->getClassList()),
            ($this->form->isReadOnly() || $input->isReadOnly()) ? 'data-readonly="true"' : '',
            $this->getAttributesAsString($input->getAttributeCollection()),
            $input->getLabel() ? '<label>' . $input->getLabel() . '</label>' : ''
        );
        $html .= ($this->form->isReadOnly() || $input->isReadOnly()) ? '' : '<div class="addDynamicInput btn primary">' . $input->getAddButtonLabel() .'</div>';
        $html .= '<div class="dynamicInputContainer">';
        //@todo implement with objects and not arrays
        foreach ($input->getValues() as $valueGroup) {
            $html .= '<div class="dynamicInputWrapper">';
            foreach($valueGroup as $value) {
                if($value['type'] !== 'textarea') {
                    $inputHTML = sprintf('<input class="input" 
                            %s
                            type="%s" name="%s"
                            value="%s">',
                        ($this->form->isReadOnly() || $input->isReadOnly()) ? 'readonly' : '',
                        $value['type'],
                        $value['name'],
                        $value['value'] ?? ''
                    );
                } else {
                    $inputHTML = sprintf('<textarea spellcheck="false" class="input" %s name="%s">%s</textarea>',
                        ($this->form->isReadOnly() || $input->isReadOnly()) ? 'readonly' : '',
                        $value['name'],
                        $value['value'] ?? '');
                }
                $html .= sprintf(
                    '<div class="inputContainer">
                            <label>%s</label>
                            %s
                            </div>',
                    $value['label'] ?? '',
                    $inputHTML
                );
            }
            if(!$this->form->isReadOnly() &&  !$input->isReadOnly()) {
                $html .= '<div class="removeDynamicInput">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                    <path d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"></path>
                </svg>
            </div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= $this->renderTooltip($input) . '</div>';
        return $html;
    }

    protected function getDynamicInputConfigAsJSONString(DynamicInputCollectionInterface $inputCollection): string
    {
        $count = iterator_count($inputCollection->getIterator());
        $html = "data-dynamic-inputs-config='{";
        $html .= '"inputs":[';
        foreach($inputCollection->getIterator() as $key => $input) {
            $html .= sprintf('{"type": "%s", "name": "%s", "label": "%s"}%s',
                $input->getType(),
                $input->getName(),
                $input->getLabel() ?? '',
                $key < $count - 1 ? ',' : '');
        }
        $html .= ']';
        $html .= "}'";
        return $html;
    }

    protected function getAttributesAsString(AttributeCollectionInterface $attributeCollection): string
    {
        $attributes = '';
        foreach ($attributeCollection->getIterator() as $attribute) {
            $attributes .= sprintf('%s="%s"', $attribute->getKey(), $attribute->getValue());
        }
        return $attributes;
    }

    protected function renderTooltip(InputTypeInterface $input): string
    {
        return $input->getTooltip() ? sprintf('
             <div class="tooltipTrigger">    
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M464 256A208 208 0 1 0 48 256a208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm169.8-90.7c7.9-22.3 29.1-37.3 52.8-37.3h58.3c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24V250.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1H222.6c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
            </div>
            <span class="tooltipContent">
                %s
            </span>
            ', $input->getTooltip()) : '';
    }

    protected function getClassListAsString(array $classList): string
    {
        return ' ' . implode(' ', $classList);
    }


    protected function getFormEnd(): string
    {
        return '</form>';
    }

    protected function getSubmitButton(): string
    {
        return sprintf('
            <div id="submitButtonContainer">
                <input form="%s" type="submit" value="%s" class="btn primary">
            </div>',
            $this->form->getId(),
            $this->form->getSubmitText()
        );
    }
}