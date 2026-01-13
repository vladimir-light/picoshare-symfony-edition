<?php

namespace App\Tests\Factory;

use App\Entity\Entry;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Entry>
 */
final class EntryFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Entry::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'contentType' => self::faker()->text(255),
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'filename' => self::faker()->text(255),
            'size' => self::faker()->numberBetween(1024 * 30, 1024 * 1024 * 24),
            'uniqLinkId' => new Ulid(),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }
}
