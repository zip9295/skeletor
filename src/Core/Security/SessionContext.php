<?php

declare(strict_types=1);

namespace Skeletor\Core\Security;

use Laminas\Session\ManagerInterface;
use Skeletor\Core\Security\Authentication\AuthenticatableInterface;

/**
 * Read side of the login session, for any authenticatable and either application.
 *
 * LoginService::login() writes a fixed set of keys — loggedIn, loggedInEntityType,
 * loggedInRole, loggedInEmail, loggedInFirstName, loggedInLastName, redirectPath — and until
 * now the only reader was User\Service\Session, which knows about users and lives in the
 * admin. Anything else (a public site with logged-in visitors, a second entity type) had to
 * reach into session storage by hand, and the key names leaked into templates.
 *
 * The cheap getters never touch the database. getUser() rehydrates the full entity through
 * the registry, once per request, and only when something actually needs it.
 */
class SessionContext
{
    private ?AuthenticatableInterface $user = null;

    private bool $userLoaded = false;

    public function __construct(
        protected readonly ManagerInterface $session,
        protected readonly EntityRegistry $entityRegistry,
    ) {}

    public function isLoggedIn(): bool
    {
        return (bool) $this->get('loggedIn');
    }

    public function getId(): int|string|null
    {
        return $this->get('loggedIn');
    }

    public function getEntityType(): ?string
    {
        return $this->get('loggedInEntityType');
    }

    /**
     * Is the current session this kind of account?
     *
     * The check every multi-entity app ends up writing: a delegate and a staff user can hold
     * the same numeric id, so "who is this" is only ever answered by the pair.
     */
    public function is(string $entityType): bool
    {
        return $this->isLoggedIn() && $this->getEntityType() === $entityType;
    }

    public function getRole(): ?int
    {
        $role = $this->get('loggedInRole');

        return $role !== null ? (int) $role : null;
    }

    public function getEmail(): ?string
    {
        return $this->get('loggedInEmail');
    }

    public function getFirstName(): ?string
    {
        return $this->get('loggedInFirstName');
    }

    public function getLastName(): ?string
    {
        return $this->get('loggedInLastName');
    }

    /** Full name when both halves are known, otherwise the address, which always is. */
    public function getDisplayName(): ?string
    {
        $name = trim(sprintf('%s %s', (string) $this->getFirstName(), (string) $this->getLastName()));

        return $name !== '' ? $name : $this->getEmail();
    }

    public function getRedirectPath(): ?string
    {
        return $this->get('redirectPath');
    }

    /**
     * The logged-in entity itself, loaded lazily and cached for the request.
     *
     * Returns null when nobody is logged in, when the type is not registered, or when the
     * record has since been deleted — a session can outlive the row it points at.
     */
    public function getUser(): ?AuthenticatableInterface
    {
        if ($this->userLoaded) {
            return $this->user;
        }
        $this->userLoaded = true;

        $type = $this->getEntityType();
        $id = $this->getId();
        if (!$type || !$id || !$this->entityRegistry->has($type)) {
            return null;
        }

        $entity = $this->entityRegistry->getRepository($type)->getById($id);

        return $this->user = $entity instanceof AuthenticatableInterface ? $entity : null;
    }

    /** Drop the cached entity so the next getUser() reloads it. */
    public function refresh(): void
    {
        $this->user = null;
        $this->userLoaded = false;
    }

    protected function get(string $key): mixed
    {
        $storage = $this->session->getStorage();

        return $storage->offsetExists($key) ? $storage->offsetGet($key) : null;
    }
}
