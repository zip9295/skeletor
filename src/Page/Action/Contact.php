<?php
namespace Skeletor\Page\Action;

use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Action\Web\Html;
use Skeletor\Page\Validator\ContactForm;
use Twig\Environment;

//use \Skeletor\Memo\Service\Mailer;

class Contact extends Html
{
    private $contactFormValidator;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * PageAction constructor.
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        Logger $logger, Config $config, Environment $template, ContactForm $contactFormValidator, Session $session
//        Mailer $mailer
    ) {
        parent::__construct($logger, $config, $template);
        $this->contactFormValidator = $contactFormValidator;
        $this->session = $session;
//        $this->mailer = $mailer;
    }

    /**
     * Parses data for provided merchantId
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request request
     * @param \Psr\Http\Message\ResponseInterface $response response
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        $data = $request->getParsedBody();
        $uri = '/kontakt/';
        if ($this->contactFormValidator->isValid($data)) {
            $uri = '/kontakt/?sent=true';
            $this->mailer->sendContactForm($data);
        } else {
            $this->session->getStorage()->offsetSet('errors', $this->contactFormValidator->getMessages());
            $this->session->getStorage()->offsetSet('formData', $data);
        }
        return $response
            ->withStatus(302)
            ->withHeader('Location', $this->getConfig()->offsetGet('baseUrl') . $uri);
    }


}