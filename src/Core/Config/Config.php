<?php

declare(strict_types=1);

namespace Skeletor\Core\Config;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use RuntimeException;
use Traversable;

/**
 * Read-only configuration tree.
 *
 * Replaces laminas/laminas-config, which the Laminas project marked Discontinued on 2024-11-04
 * and which Composer flags as abandoned. It was also the single most-coupled dependency in this
 * framework, so it was the one worth owning.
 *
 * Deliberately API-compatible with the slice of Laminas\Config\Config this codebase used:
 * ArrayAccess, magic property access, get() with a default, toArray(), merge(), iteration and
 * count. Nested arrays are wrapped lazily so $config->mailer->recipients->contactForm keeps
 * working, including foreach over the leaf.
 *
 * One deliberate difference: this is immutable. Nothing in the framework ever wrote to config,
 * and merge() returns a new instance rather than mutating in place, so a config tree cannot be
 * quietly rewritten at runtime from some far-off service.
 */
final class Config implements ArrayAccess, Countable, IteratorAggregate
{
    /** @var array<string|int, mixed> */
    private array $data;

    /** @var array<string|int, self> lazily wrapped child nodes */
    private array $children = [];

    /**
     * @param array<string|int, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Fetch a value, or $default when the key is absent.
     */
    public function get(string|int $name, mixed $default = null): mixed
    {
        if (!array_key_exists($name, $this->data)) {
            return $default;
        }

        return $this->wrap($name);
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __isset(string $name): bool
    {
        // Must not wrap: `$config->adminUrl ?? ''` calls __isset first, and a null-valued key
        // should behave as unset for that idiom, exactly as it did under laminas-config.
        return isset($this->data[$name]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new RuntimeException('Config is read-only; cannot set "' . (string) $offset . '".');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException('Config is read-only; cannot unset "' . (string) $offset . '".');
    }

    public function __set(string $name, mixed $value): void
    {
        throw new RuntimeException('Config is read-only; cannot set "' . $name . '".');
    }

    public function __unset(string $name): void
    {
        throw new RuntimeException('Config is read-only; cannot unset "' . $name . '".');
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator(): Traversable
    {
        foreach (array_keys($this->data) as $key) {
            yield $key => $this->wrap($key);
        }
    }

    /**
     * The whole tree as plain arrays, all the way down.
     *
     * @return array<string|int, mixed>
     */
    public function toArray(): array
    {
        $out = [];
        foreach ($this->data as $key => $value) {
            $out[$key] = is_array($value) ? (new self($value))->toArray() : $value;
        }

        return $out;
    }

    /**
     * Recursively merge another config over this one and return the result as a new instance.
     *
     * Scalars and lists from $other replace; associative arrays merge key by key. That is what
     * config-local.php overriding config.php has always relied on.
     */
    public function merge(self $other): self
    {
        return new self(self::mergeArrays($this->data, $other->toArray()));
    }

    /**
     * @param array<string|int, mixed> $base
     * @param array<string|int, mixed> $over
     * @return array<string|int, mixed>
     */
    private static function mergeArrays(array $base, array $over): array
    {
        foreach ($over as $key => $value) {
            if (!array_key_exists($key, $base)) {
                $base[$key] = $value;
                continue;
            }
            if (is_int($key)) {
                // Laminas appended on integer-key collision rather than overwriting by index,
                // and config-local.php's list-shaped nodes (db.read) depend on that.
                $base[] = $value;
                continue;
            }
            if (is_array($value) && is_array($base[$key])) {
                $base[$key] = self::mergeArrays($base[$key], $value);
                continue;
            }
            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * Arrays become Config nodes (cached, so repeated access returns the same object); everything
     * else is returned as-is.
     */
    private function wrap(string|int $key): mixed
    {
        $value = $this->data[$key] ?? null;
        if (!is_array($value)) {
            return $value;
        }

        return $this->children[$key] ??= new self($value);
    }
}
