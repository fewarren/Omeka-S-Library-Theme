<?php declare(strict_types=1);

namespace OmekaTheme\Helper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Factory for creating SiteSettingsRepository instances with proper dependency injection
 */
class SiteSettingsRepositoryFactory implements FactoryInterface
{
    /**
     * Create and return a SiteSettingsRepository instance
     * 
     * @param ContainerInterface $container Service container
     * @param string $requestedName Service name
     * @param array|null $options Additional options
     * @return SiteSettingsRepository
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): SiteSettingsRepository
    {
        $connection = $container->get('Omeka\Connection');
        
        return new SiteSettingsRepository($connection);
    }
}
