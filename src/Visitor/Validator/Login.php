<?php
namespace Skeletor\Core\Login\Service;

use Skeletor\Core\Config\Config;
use Laminas\Session\ManagerInterface;
use Skeletor\Core\Mailer\Service\PhpMailer;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Login\Exception\InvalidCredentials;
use Skeletor\Core\Login\Provider\ProviderInterface;
use Skeletor\Core\Login\Repository\ForgotPasswordRepository;

class Login
{
    protected $message;

    public function __construct(
        public readonly ProviderInterface $provider, private ManagerInterface $session, private PhpMailer $mailer,
        private ForgotPasswordRepository  $tokenRepo, private Config $config
    ) {
    }

    public function resetPassword($userId, $password)
    {
        $this->provider->updatePassword($userId, $password);
    }

    public function sendForgotToken($email)
    {
        $user = $this->getByEmail($email);
        $pwd = $this->generateForgotPasswordHash();
        $displayName = '';
        if(method_exists($user, 'getDisplayName')) {
            $displayName = $user->getDisplayName();
        } elseif(method_exists($user, 'getFirstName') && method_exists($user, 'getLastName')) {
            $displayName = sprintf('%s %s',$user->getFirstName(), $user->getLastName());
        }
        $this->mailer->sendForgotPasswordMail($user->getEmail(), $pwd, $displayName, $user->getId());
        $this->tokenRepo->create([
            'entityId' => $user->getId(),
            'entityType' => 1, // @TODO solve this
            'token' => password_hash($pwd, PASSWORD_BCRYPT),
        ]);
    }

    public function generateForgotPasswordHash()
    {
        return md5(sprintf(
            '%s%s%s', microtime(true), random_int(PHP_INT_MIN, PHP_INT_MAX), random_bytes(1024)
        ));
    }

    public function verifyToken($hash)
    {
        $data = explode('$', $hash);
        $verifyToken = $data[2];
        $userId = $data[1];
        // @TODO implement time limit
        $requestedAt = (new \DateTime('now', new \DateTimeZone('Europe/Belgrade')))
            ->modify('-3 hour')->format('Y-m-d H:i:s');

        // @todo could do this via query
        $token = $this->tokenRepo->fetchAll(['entityId' => $userId, 'entityType' => 1], 1, ['createdAt' => 'DESC']);
        if (!$token || !count($token)) {
            throw new \Exception('A non existing or expired token was submitted.');
        }
        $token = $token[0];
        if (!password_verify($verifyToken, $token->token)) {
            throw new \Exception('An invalid token was submitted.');
        }

        return ['userId' => $userId, 'tokenId' => $token->getId()];
    }

    public function login($data)
    {
        $model = $this->provider->login($data);
        if (isset($data['rememberMe']) && $data['rememberMe']) {
            $this->session->rememberMe();
        }
        $this->session->regenerateId(true);
        //@TODO update this var if user info changes
        $this->session->getStorage()->offsetSet('loggedIn', $model->getId());
        $this->session->getStorage()->offsetSet('loggedInRole', $model->getRole());
        $this->session->getStorage()->offsetSet('loggedInEmail', $model->getEmail());
        if (property_exists($model, 'tenant') && $model->tenant) {
            $this->session->getStorage()->offsetSet('tenantId', $model->tenant->getId());
        }
        // @TODO allow different naming for tenant filter. should probably wrap this with upper check
        if (method_exists($model, 'getTenant') && $model->getTenant()) {
            $this->session->getStorage()->offsetSet('tenantId', $model->getTenant()->getId());
        }
//        $this->session->getStorage()->offsetSet('user', $model);
        $this->session->getStorage()->offsetSet('redirectPath', $this->config->offsetGet('redirectUri'));

        //@TODO add redirect to last page user tried to visit before timeout
//            $this->session->getStorage()->offsetSet('redirectPath', '/');
    }

    public function logout()
    {
        $this->session->getStorage()->offsetUnset('loggedIn');
        $this->session->getStorage()->offsetUnset('loggedInRole');
        $this->session->getStorage()->offsetUnset('redirectPath');
        $this->session->getStorage()->offsetUnset('tenantId');
        $this->session->getStorage()->offsetUnset('loggedInEmail');
        $this->session->forgetMe();
        $this->session->destroy();
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getSsoState()
    {
        return $this->provider->getSsoState();
    }

    public function getAuthorizationUrl()
    {
        return $this->provider->getAuthorizationUrl();
    }

    public function getByEmail($email)
    {
        return $this->provider->getByEmail($email);
    }
}