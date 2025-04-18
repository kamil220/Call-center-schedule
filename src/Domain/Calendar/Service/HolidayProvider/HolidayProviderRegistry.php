<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Service\HolidayProvider;

use InvalidArgumentException;

final class HolidayProviderRegistry
{
    private const DEFAULT_COUNTRY = 'PL';

    /**
     * @var array<HolidayProviderInterface>
     */
    private array $providers;

    public function __construct(iterable $providers)
    {
        $this->providers = is_array($providers) ? $providers : iterator_to_array($providers);
    }

    public function getDefaultProvider(): HolidayProviderInterface
    {
        return $this->getProviderForCountry(self::DEFAULT_COUNTRY);
    }

    public function getProviderForCountry(string $country): HolidayProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($country)) {
                return $provider;
            }
        }

        if ($country !== self::DEFAULT_COUNTRY) {
            return $this->getDefaultProvider();
        }

        throw new InvalidArgumentException(sprintf('No holiday provider found for country: %s', $country));
    }
} 