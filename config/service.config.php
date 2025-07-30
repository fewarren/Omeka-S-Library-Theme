<?php
/**
 * Service configuration for dependency injection
 * 
 * This file shows how to register the ModuleService in Omeka's service manager
 * for proper dependency injection. Add this configuration to your theme's
 * module.config.php or include it in your service configuration.
 */

return [
    'service_manager' => [
        'factories' => [
            'OmekaTheme\Helper\ModuleService' => 'OmekaTheme\Helper\ModuleServiceFactory',
            'OmekaTheme\Helper\SiteSettingsRepository' => 'OmekaTheme\Helper\SiteSettingsRepositoryFactory',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'themeFunctions' => function ($container) {
                $helper = new \OmekaTheme\Helper\ThemeFunctions();

                // Inject the ModuleService if available
                if ($container->has('OmekaTheme\Helper\ModuleService')) {
                    $moduleService = $container->get('OmekaTheme\Helper\ModuleService');
                    $helper->setModuleService($moduleService);
                }

                // Inject the SiteSettingsRepository if available
                if ($container->has('OmekaTheme\Helper\SiteSettingsRepository')) {
                    $siteSettingsRepository = $container->get('OmekaTheme\Helper\SiteSettingsRepository');
                    $helper->setSiteSettingsRepository($siteSettingsRepository);
                }

                return $helper;
            },
        ],
    ],
];
