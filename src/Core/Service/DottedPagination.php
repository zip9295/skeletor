<?php

namespace Skeletor\Core\Service;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

class DottedPagination implements ExtensionInterface
{
    private int $page;
    private int $lastPage;
    private string $buttonClass;
    private string $activeButtonClass;
    private string $previousPageButtonClass;
    private string $nextPageButtonClass;
    private string $previousPageButtonText;
    private string $nextPageButtonText;
    private string $dotsClassName;

    public function register(Engine $engine): void
    {
        $engine->registerFunction('getDottedPagination', [$this, 'getDottedPagination']);
    }

    public function getDottedPagination(
        int $page,
        int $lastPage,
        string $buttonClass,
        string $activeButtonClass,
        string $previousPageButtonClass,
        string $nextPageButtonClass,
        string $previousPageButtonText,
        string $nextPageButtonText,
        string $dotsClassName
    ) {
        $this->page = $page;
        $this->lastPage = $lastPage;
        $this->buttonClass = $buttonClass;
        $this->activeButtonClass = $activeButtonClass;
        $this->previousPageButtonClass = $previousPageButtonText;
        $this->nextPageButtonClass = $nextPageButtonClass;
        $this->previousPageButtonText = $previousPageButtonText;
        $this->nextPageButtonText = $nextPageButtonText;
        $this->dotsClassName = $dotsClassName;

        $html = '';
        if($lastPage > 7) {
            if($page === 1) {
                $html .= $this->getButton(1, true);
            } else {
                $html .= $this->getButton($page - 1, false, $this->previousPageButtonClass, $this->previousPageButtonText);
                $html .= $this->getButton(1);
            }

            if(($lastPage - $page) > 3) {
                if($page > 4) {
                    $html .= $this->getDots();
                    $html .= $this->getButton($page - 1);
                    $html .= $this->getButton($page, true);
                    $html .= $this->getButton($page + 1);
                } else {
                    for($i = 2; $i <= 5; $i++) {
                        $html .= $this->getButton($i, $page === $i);
                    }
                }
            }
            if(($lastPage - $page) < 4) {
                $html .= $this->getDots();
                for($j = $lastPage - 4; $j <= $lastPage - 1; $j++) {
                    $html .= $this->getButton($j, $page === $j);
                }
            } else {
                $html .= $this->getDots();
            }

            if($page === $lastPage) {
                $html .= $this->getButton($lastPage, true);
            } else {
                $html .= $this->getButton($lastPage);
                $html .= $this->getButton($page + 1, false, $this->nextPageButtonClass, $this->nextPageButtonText);
            }
        } else {
            if($page !== 1)  {
                $html .= $this->getButton($page - 1,false, $this->previousPageButtonClass, $this->previousPageButtonText);
            }
            for($k = 1; $k <= $lastPage; $k++) {
                $html .= $this->getButton($k, $page === $k);
            }
            if($page === $lastPage) {
            } else {
                $html .= $this->getButton($page + 1, false, $this->nextPageButtonClass, $this->nextPageButtonText);
            }
        }
        return $html;
    }

    private function getButton($page, $active = false, $customClass = false, $text = false, $disabled = false)
    {
        if(!$text) {
            $text = $page;
        }
        return sprintf('<button class="%s%s%s" %s data-page="%s">%s</button>',
            $this->buttonClass,
            ($customClass ? ' ' . $customClass : ''),
            ($active ? ' ' . $this->activeButtonClass : ''),
            $disabled ? 'disabled' : '',
            $page,
            $text);
    }

    private function getDots()
    {
        return sprintf('<button class="%s">...</button>', $this->dotsClassName);
    }
}