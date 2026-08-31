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
     * This method returns enum cases in values indexed by positive numeric IDs.
     *
     * @return array<int, static> A key - <tt>id</tt>, value - enum case.
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

            $all[$item->value] = $item;
        }

        if (!$all) {
            throw new \LogicException(sprintf(
                'Enum %s has no valid positive integer SID cases.',
                static::class
            ));
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
        $a_id_list = array_keys(static::all());
        $id = min($a_id_list);

        return static::idSid($id);
    }

    /**
     * Returns the default id of element (default is the first case in the enum).
     *
     * @return int The default id element.
     */
    public static function defaultId(): int
    {
        return static::default()->value;
    }

    /**
     * Returns the default sid name of element (default is the first case in the enum).
     *
     * @return string Sid name.
     */
    public static function defaultSid(): string
    {
        $sid = strtoupper(static::default()->name);
        return static::constantSid($sid);
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
        return isset(static::all()[$id]);
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

        return static::all()[$id];
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
        foreach (static::all() as $item) {
            if (static::constantSid($item->name) === $sid) {
                return true;
            }
        }

        return false;
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
        foreach (static::all() as $item) {
            if (static::constantSid($item->name) === $sid) {
                return $item;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Unknown code "%s" for enum %s',
            $sid,
            static::class
        ));
    }
}
