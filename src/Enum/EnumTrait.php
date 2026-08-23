<?php

namespace App\Enum;

use App\Service\SidService;

/**
 * Trait for Enum classes with numeric SID.
 */
trait EnumTrait
{
    /**
     * Returns the SID string value for the given element.
     *
     * @param string $name Element name.
     *
     * @return string SID string value.
     */
    public static function constantSid(string $name): string
    {
        return $name;
    }

    /**
     * Returns a list of all identifiers.
     *
     * This method returns strings in value for compatibility with {@link SidTrait}.
     * For example, {@link SidTrait::idSid()}, {@link SidTrait::sidId()} and
     * {@link SidTrait::random()} call this method and require that value be a string.
     *
     * @return string[] A key - <tt>id</tt>, value - <tt>sid</tt>.
     */
    public static function all(): array
    {
        $class_name = get_called_class();

        if (isset(SidService::$all[$class_name])) {
            return SidService::$all[$class_name];
        }

        $all = [];
        foreach (static::cases() as $item) {
            if (!is_int($item->value) || $item->value <= 0) {
                continue;
            }

            $all[$item->value] = static::constantSid($item->name);
        }

        SidService::$all[$class_name] = $all;

        return $all;
    }

    /**
     * Returns the default element (default is the first case in the enum).
     *
     * @return static The default element.
     */
    public static function default(): static
    {
        return static::cases()[0];
    }

    /**
     * Checks if an element with the given numeric ID (SID) exists.
     *
     * @param int $id Numeric ID (SID) to check.
     *
     * @return bool True if an element with the given ID exists, false otherwise.
     */
    public static function idExists(int $id): bool
    {
        return in_array($id, static::cases());
    }

    /**
     * Search for an element by numeric ID (SID).
     *
     * @param int $id Numeric ID (SID) to search for.
     *
     * @return EnumTrait The element with the specified ID, or throws if the element is not found.
     * @throws \InvalidArgumentException if the element is not found.
     */
    public static function idSid(int $id): static
    {
        if (!static::idExists($id)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown SID %d for enum %s',
                $id,
                static::class
            ));
        }

        return static::cases()[$id];
    }

    /**
     * Checks if an element with the given letter code exists.
     *
     * @param string $sid Letter code to check.
     *
     * @return bool True if an element with the given letter code exists, false otherwise.
     */
    public static function sidExists(string $sid): bool
    {
        return in_array($sid, array_flip(static::cases()));
    }

    /**
     * Search for an element by letter code (case insensitive).
     *
     * @param string $sid Letter code for search.
     *
     * @return EnumTrait The element with the specified letter code, or throws an exception if the element is not found.
     * @throws \InvalidArgumentException if the element is not found.
     */
    public static function sidId(string $sid): static
    {
        if (!static::sidExists($sid)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown code "%s" for enum %s',
                $sid,
                static::class
            ));
        }

        return static::cases()[array_flip(static::cases())[$sid]];
    }
}
