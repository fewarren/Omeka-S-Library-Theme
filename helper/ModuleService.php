<?php declare(strict_types=1);

namespace OmekaTheme\Helper;

use Omeka\Api\Manager as ApiManager;
use Omeka\Module\Manager as ModuleManager;

/**
 * Service for checking module activation status and versions
 * 
 * This service provides a clean interface for module-related queries
 * without tight coupling to database connections or internal Omeka structures.
 */
class ModuleService
{
    private ApiManager $apiManager;
    private ModuleManager $moduleManager;
    private array $moduleCache = [];
    
    public function __construct(ApiManager $apiManager, ModuleManager $moduleManager)
    {
        $this->apiManager = $apiManager;
        $this->moduleManager = $moduleManager;
    }
    
    /**
     * Check if a module is active and optionally meets minimum version requirement
     * 
     * @param string $module Module identifier
     * @param string|null $minimumVersion Minimum version required (optional)
     * @return bool True if module is active and meets version requirement
     */
    public function isModuleActive(string $module, ?string $minimumVersion = null): bool
    {
        $moduleData = $this->getModuleData($module);
        
        if (!$moduleData || !$moduleData['is_active']) {
            return false;
        }
        
        return $minimumVersion
            ? version_compare($moduleData['version'], $minimumVersion, '>=')
            : true;
    }
    
    /**
     * Get module data with caching
     * 
     * @param string $module Module identifier
     * @return array|null Module data or null if not found
     */
    public function getModuleData(string $module): ?array
    {
        // Check cache first
        if (isset($this->moduleCache[$module])) {
            return $this->moduleCache[$module];
        }
        
        try {
            // Try API first
            $response = $this->apiManager->searchOne('modules', ['id' => $module]);
            
            if ($response) {
                $moduleRepresentation = $response->getContent();
                $moduleData = [
                    'id' => $moduleRepresentation->getId(),
                    'version' => $moduleRepresentation->getVersion(),
                    'is_active' => $moduleRepresentation->getState() === ModuleManager::STATE_ACTIVE
                ];
                
                // Cache the result
                $this->moduleCache[$module] = $moduleData;
                return $moduleData;
            }
        } catch (\Exception $e) {
            // API failed, try module manager
            try {
                $moduleEntity = $this->moduleManager->getModule($module);
                if ($moduleEntity) {
                    $moduleData = [
                        'id' => $moduleEntity->getId(),
                        'version' => $moduleEntity->getVersion(),
                        'is_active' => $moduleEntity->getState() === ModuleManager::STATE_ACTIVE
                    ];
                    
                    // Cache the result
                    $this->moduleCache[$module] = $moduleData;
                    return $moduleData;
                }
            } catch (\Exception $e) {
                // Both methods failed
            }
        }
        
        // Cache negative result to avoid repeated lookups
        $this->moduleCache[$module] = null;
        return null;
    }
    
    /**
     * Get all active modules with their versions
     * 
     * @return array Array of module_id => version pairs
     */
    public function getActiveModules(): array
    {
        try {
            $response = $this->apiManager->search('modules', ['state' => ModuleManager::STATE_ACTIVE]);
            $activeModules = [];
            
            foreach ($response->getContent() as $module) {
                $activeModules[$module->getId()] = $module->getVersion();
            }
            
            return $activeModules;
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Clear the module cache
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $this->moduleCache = [];
    }
    
    /**
     * Check if a specific module version is compatible
     * 
     * @param string $module Module identifier
     * @param string $requiredVersion Exact version required
     * @return bool True if module has the exact version
     */
    public function hasModuleVersion(string $module, string $requiredVersion): bool
    {
        $moduleData = $this->getModuleData($module);
        
        return $moduleData && $moduleData['version'] === $requiredVersion;
    }
}
