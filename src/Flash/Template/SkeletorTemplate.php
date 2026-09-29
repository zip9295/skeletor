<?php


namespace Skeletor\Flash\Template;

class SkeletorTemplate extends \Tamtamchik\SimpleFlash\BaseTemplate implements \Tamtamchik\SimpleFlash\TemplateInterface
{

    protected $wrapper = '<div class="message %s">%s</div>';

    protected $prefix = '';

    protected $postfix = '';

    /**
     * Parameters are deliberately left untyped so this template works against every
     * tamtamchik/simple-flash release the framework allows ("*" in composer.json):
     * v2 declares wrapMessages($messages, $type) with no types, v4 declares
     * wrapMessages(string $messages, string $type): string. Widening the params is legal
     * against both; narrowing them to `string` fatals on v2. The `: string` return type is
     * covariant against v2 (no return type) and identical on v4.
     *
     * @param string $messages - message text
     * @param string $type - message type: success, info, warning, error
     */
    public function wrapMessages($messages, $type): string
    {
        return sprintf($this->getWrapper(), $type, $messages);
    }

}
