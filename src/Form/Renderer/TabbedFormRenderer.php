<?php

namespace Skeletor\Form\Renderer;

use Skeletor\Form\Contracts\TabbedFormInterface;
use Skeletor\Form\Renderer\Base\BaseFormRenderer;
use Skeletor\Form\Tab\Contracts\TabInterface;
use WeakMap;

class TabbedFormRenderer extends BaseFormRenderer
{
    protected WeakMap $additionalTabContents;

    public function __construct(TabbedFormInterface $form, string $formTitle)
    {
        parent::__construct($form, $formTitle);
        $this->additionalTabContents = new WeakMap();
    }

    public function render(): string
    {
        $html = $this->getFormTitleAsHTML();
        $html .= $this->getFormStart();
        $html .= $this->getCsrfToken();
        $html .= $this->getTabs();
        $html .= $this->getContentForTabs();
        $html .= $this->getFormEnd();
        if(!$this->form->isReadOnly()) {
            $html .= $this->getSubmitButton();
        }

        return $html;
    }

    public function setAdditionalTabContent(TabInterface $tab, string $content): void
    {
        $this->additionalTabContents[$tab] = $content;
    }

    public function getAdditionalTabContent(TabInterface $tab): string
    {
        return $this->additionalTabContents[$tab] ?? '';
    }

    protected function getTabs(): string
    {
        $tabsHTML = '<div id="formTabs" class="tabs">';
        $tabsContentHTML = '';
        foreach ($this->form->getTabCollection()->getIterator() as $key => $tab) {
            $active = $key === 0 ? 'active' : '';
            $tabsHTML .= $this->getTab($tab, $key, $active);
        }
        $tabsHTML .= '</div>';

        return $tabsHTML . $tabsContentHTML;
    }

    protected function getTab(TabInterface $tab, int $index, bool $isActive = false): string
    {
        return sprintf(
            '<div class="tab %s" data-tab="%d">%s</div>',
            $isActive ? 'active' : '',
            $index,
            $tab->getLabel()
        );
    }

    protected function getContentForTabs(): string
    {
        $tabsContentHTML = '';
        foreach($this->form->getTabCollection()->getIterator() as $key => $tab) {
            $active = $key !== 0 ? 'hidden' : '';
            $tabsContentHTML .= sprintf(
                '<div class="row tabContent %s" data-tab="%d">%s</div>',
                $active,
                $key,
                $this->getTabContent($tab)
            );
        }
        return $tabsContentHTML;
    }

    protected function getTabContent(TabInterface $tab): string
    {
        $html = '';
        foreach ($tab->getInputGroupCollection()->getIterator() as $inputGroup) {
            $html .= $this->getInputGroup($inputGroup);
        }
        $html .= $this->getAdditionalTabContent($tab);
        return $html;
    }
}