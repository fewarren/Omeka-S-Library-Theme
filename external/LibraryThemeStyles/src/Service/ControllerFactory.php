<?php declare(strict_types=1);

namespace LibraryThemeStyles\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use LibraryThemeStyles\Controller\AdminController;

/**
 * Factory for AdminController
 */
class ControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AdminController
    {
        $moduleConfigService = $container->get(ModuleConfigService::class);
        
        return new AdminController($moduleConfigService);
    }
}
