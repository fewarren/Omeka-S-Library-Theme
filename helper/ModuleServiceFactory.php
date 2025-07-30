<?php declare(strict_types=1);

namespace OmekaTheme\Helper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Factory for creating ModuleService instances with proper dependency injection
 */
class ModuleServiceFactory implements FactoryInterface
{
    /**
     * Create and return a ModuleService instance
     * 
     * @param ContainerInterface $container Service container
     * @param string $requestedName Service name
     * @param array|null $options Additional options
     * @return ModuleService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ModuleService
    {
        $apiManager = $container->get('Omeka\ApiManager');
        $moduleManager = $container->get('Omeka\ModuleManager');
        
        return new ModuleService($apiManager, $moduleManager);
    }
}
