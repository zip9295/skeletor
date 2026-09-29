<?php

namespace Skeletor\Core\Service;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

class TabbedButtonsView implements ExtensionInterface
{
    public $template;
    public function register(Engine $engine): void
    {
        $engine->registerFunction('getTabbedButtonsView', [$this, 'getTabbedButtonsView']);
    }

    /**
     * @param $data
     * @return string
     */
    public function getTabbedButtonsView($data)
    {
        $html = '<div class="buttonsWrapper"><ul>';
        foreach ($data as $key => $entry) {
            $html .= $this->generateButton($key, $entry);
        }
        $html .= '</ul></div>';
        return $html;
    }

    private function generateButton($key, $entry)
    {
        return sprintf('<li%s%s>%s</li>',
            $key === 0 ? ' class="active"' : '',
            isset($entry['targetId']) ? ' data-id="' . $entry['targetId'] . '"' : '',
            isset($entry['buttonText']) ? $entry['buttonText'] : ''
        );
    }
}