<?php
declare(strict_types=1);

namespace Skeletor\Core\Login\Service;

use Skeletor\Core\Config\Config;
use Skeletor\Core\Mailer\Service\MailerInterface;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Core\Login\Exception\InvalidCredentials;
use Skeletor\Core\Login\Exception\MagicLinkThrottled;
use Skeletor\Core\Login\Repository\MagicLinkTokenRepository;

/**
 * Magic Link Service
 * Handles generation and sending of magic link emails
 *
 * Three gates sit in front of issuing a link, and all three belong here rather than in a
 * controller: the address must resolve to an account, the account must be active, and the
 * account must not have asked a moment ago. Each app that grew its own login controller
 * previously reimplemented some subset of those, which is how an unverified delegate could
 * mail themselves a working key to the dashboard from one entry point but not another.
 */
class MagicLinkService
{
    private const DEFAULT_EXPIRY_MINUTES = 15;

    public function __construct(
        private TokenGenerator           $tokenGenerator,
        private MagicLinkTokenRepository $tokenRepository,
        private EntityRegistry           $entityRegistry,
        private MailerInterface          $mailer,
        private Config                   $config
    ) {}

    /**
     * Request a magic link for email.
     *
     * Returns the generated token. When $sendEmail is true (default) the service also sends
     * the built-in magic-link email to the URL configured for this entity type. Callers that
     * need their own copy — a different template, a different language — pass false and use
     * the returned token, which is the only moment the plaintext exists.
     *
     * @throws NotFoundException  the address matches no account of this type
     * @throws InvalidCredentials the account exists but may not log in
     * @throws MagicLinkThrottled a link was issued too recently
     */
    public function requestMagicLink(
        string $email,
        string $entityType = 'user',
        bool $sendEmail = true,
        bool $rememberMe = false
    ): string {
        $entity = $this->entityRegistry->findByEmail($entityType, $email);

        if (!$entity) {
            throw new NotFoundException('Email not found in system');
        }

        // The gate that matters. An account can exist and still be barred — a delegate
        // awaiting approval, a donor whose registration lapsed — and a magic link bypasses
        // every later check by handing out a session directly, so "may this account log in"
        // has to be answered before a token is minted, not after it is followed.
        if (!$entity->isActive()) {
            throw new InvalidCredentials('This account is not active.');
        }

        $this->guardCooldown($entityType, $entity);

        // Any link already in flight stops working the moment a new one is asked for, so a
        // forwarded or logged older email cannot be used behind the account holder.
        $this->tokenRepository->invalidateAllForEntity($entityType, (int) $entity->getId());

        $token = $this->tokenGenerator->generate(64);
        $this->tokenRepository->create(
            $token,
            $entityType,
            (int) $entity->getId(),
            $this->expiryMinutes(),
            $rememberMe
        );

        if ($sendEmail) {
            $this->mailer->sendMagicLinkEmail(
                $email,
                $this->getEntityDisplayName($entity),
                $this->buildMagicLinkUrl($token, $entityType)
            );
        }

        return $token;
    }

    /**
     * Build the magic link URL for an entity type.
     *
     * Configurable per type, because the destination is not always in the admin: a donor
     * follows their link into the public site. Set magicLink.urls.<entityType> to a template
     * using {baseUrl}, {adminUrl}, {adminPath}, {entityType} and {token}, for example
     *
     *     'donor' => '{baseUrl}/donor/verifyEmail?token={token}'
     *
     * With no entry configured this falls back to the framework admin route, which is what
     * every backend entity type wants and is the behaviour that was previously hardcoded.
     */
    public function buildMagicLinkUrl(string $token, string $entityType): string
    {
        $adminUrl = (string) ($this->config->adminUrl ?? '');
        $baseUrl = (string) ($this->config->baseUrl ?? $adminUrl);
        $adminPath = trim((string) ($this->config->adminPath ?? ''), '/');

        $template = $this->config->magicLink?->urls?->{$entityType} ?? null;

        if (!is_string($template) || $template === '') {
            $template = $adminPath !== ''
                ? '{adminUrl}/{adminPath}/login/{entityType}/verifyMagicLink/{token}/'
                : '{adminUrl}/login/{entityType}/verifyMagicLink/{token}/';
        }

        return strtr($template, [
            '{baseUrl}' => rtrim($baseUrl, '/'),
            '{adminUrl}' => rtrim($adminUrl, '/'),
            '{adminPath}' => $adminPath,
            '{entityType}' => $entityType,
            '{token}' => $token,
        ]);
    }

    /**
     * Check a token without spending it.
     *
     * The confirm-then-consume flow needs to know a link is alive before it renders a button
     * for it; only the authenticator invalidates. Exists so controllers do not have to reach
     * past this service into the token repository.
     *
     * @return array{tokenId: int, entityType: string, entityId: int}
     * @throws \Skeletor\Core\Login\Exception\InvalidCredentials when the token is unknown, spent or expired
     */
    public function peek(string $token): array
    {
        return $this->tokenRepository->verifyToken($token);
    }

    /**
     * Did the account that owns this token ask to be remembered?
     *
     * Read before the token is spent — the authenticator invalidates it — so the caller has
     * to ask first and log in second.
     */
    public function shouldRemember(string $token): bool
    {
        return $this->tokenRepository->findByToken($token)?->rememberMe ?? false;
    }

    /**
     * Refuse a second link within the cooldown.
     *
     * Off unless magicLink.cooldownSeconds is set. It is not really about load: each request
     * invalidates the previous link, so anyone who can hit this endpoint in a loop can keep a
     * real user's link permanently broken while filling their inbox. A short cooldown ends
     * that without getting in a genuine user's way.
     */
    private function guardCooldown(string $entityType, AuthenticatableInterface $entity): void
    {
        $cooldown = (int) ($this->config->magicLink?->cooldownSeconds ?? 0);
        if ($cooldown <= 0) {
            return;
        }

        $since = new \DateTime(sprintf('-%d seconds', $cooldown));
        if ($this->tokenRepository->hasRequestSince($entityType, (int) $entity->getId(), $since)) {
            throw new MagicLinkThrottled((new \DateTime())->modify(sprintf('+%d seconds', $cooldown)));
        }
    }

    private function expiryMinutes(): int
    {
        $configured = (int) ($this->config->magicLink?->expiryMinutes ?? 0);

        return $configured > 0 ? $configured : self::DEFAULT_EXPIRY_MINUTES;
    }

    /**
     * Get display name from entity
     */
    private function getEntityDisplayName($entity): string
    {
        if (method_exists($entity, 'getDisplayName') && $entity->getDisplayName()) {
            return $entity->getDisplayName();
        }

        if (method_exists($entity, 'getFirstName') && method_exists($entity, 'getLastName')) {
            return trim(sprintf('%s %s', $entity->getFirstName(), $entity->getLastName()));
        }

        if (method_exists($entity, 'getEmail')) {
            return $entity->getEmail();
        }

        return 'User';
    }
}
