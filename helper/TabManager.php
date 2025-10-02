<?php 
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class TabManager extends AbstractHelper
{
    /**
     * Collects resource page blocks for predefined page regions.
     *
     * Only regions that contain blocks are included in the result.
     *
     * @param mixed $resource The resource (entity or identifier) whose page blocks should be retrieved.
     * @return array<string, array> Associative array mapping region names to their blocks arrays.
     */
    public function getResourcePageBlocks($resource) {
        $regions = ['full_width_main', 'left', 'main', 'right'];
        $view = $this->getView();
        $tabLayout = $view->themeSetting('tab_navigation_layout');
        $tabContent = $view->themeSetting('tab_navigation_content');
        $regionContent = [];
        foreach ($regions as $region) {
            $regionResourcePageBlocks = $view->resourcePageBlocks($resource, $region);
            if ($regionResourcePageBlocks->hasBlocks()) {
                $regionContent[$region] = $regionResourcePageBlocks->getBlocksArray();
            }
        }
        return $regionContent;
    }

    /**
     * Locate which region contains a `tab_navigation` block for the given resource.
     *
     * @param mixed $resource The resource (or resource representation) whose page blocks are inspected.
     * @return string|false The region name that contains `tab_navigation`, or `false` if none is found.
     */
    public function getTabNavigationRegion($resource) {
        $resourcePageBlocks = $this->getResourcePageBlocks($resource);
        $tabNavigationRegion = false;
        foreach ($resourcePageBlocks as $region => $blockArray) {
            if (array_key_exists('tab_navigation', $blockArray)) {
                $tabNavigationRegion = $region;
            }
        }
        return $tabNavigationRegion;
    }

    /**
     * Render the tab panels for a resource's content region.
     *
     * @param mixed  $resource      The resource for which to render panels.
     * @param string $contentRegion The region containing tab content (default: "main").
     * @param string $layout        The tab layout to use, e.g. "vertical" or "horizontal" (default: "vertical").
     * @return string The rendered HTML for the resource's tab panels.
     */
    public function renderPanels($resource, $contentRegion = 'main', $layout = 'vertical')
    {
        $view = $this->getView();
        $tabContentBlocksArray = $view->resourcePageBlocks($resource, $contentRegion)->getBlocksArray();
        return $view->partial('common/tab-panels.phtml', [
            'resource' => $resource,
            'resourcePageBlockArray' => $tabContentBlocksArray,
            'tabLayout' => $layout
        ]);
    }

    /**
     * Renders the tab navigation markup for a given resource region.
     *
     * @param mixed $resource The resource entity or identifier for which to render tab navigation.
     * @param string $contentRegion The region that contains the tab content (defaults to "main").
     * @param string $layout The tab layout style, e.g. "vertical" or "horizontal".
     * @return string The rendered HTML markup for the tab navigation.
     */
    public function renderTabsOnly($resource, $contentRegion = 'main', $layout = 'vertical') 
    {
        $view = $this->getView();
        $tabContentBlocksArray = $view->resourcePageBlocks($resource, $contentRegion)->getBlocksArray();
        return $view->partial('common/tab-navigation-markup.phtml', [
            'resource' => $resource,
            'resourcePageBlocksArray' => $tabContentBlocksArray,
            'layout' => $layout
        ]);
    }

    /**
     * Render a region's blocks with the tab navigation for another region injected.
     *
     * Injects the tab navigation markup for $contentRegion into the blocks of $currentRegion
     * and returns the concatenated HTML for that region.
     *
     * @param mixed  $resource      The resource whose page blocks are rendered.
     * @param string $currentRegion The region whose blocks will receive the injected tab navigation.
     * @param string $contentRegion The region that provides the tab navigation markup (default 'main').
     * @param string $layout        The tab layout to use (e.g., 'vertical', 'horizontal'; default 'vertical').
     * @return string The concatenated HTML of the region's blocks with the tab navigation included.
     */
    public function renderTabsRegion($resource, $currentRegion, $contentRegion = 'main', $layout = 'vertical')
    {
        $view = $this->getView();
        $regionBlockContentArray = $view->resourcePageBlocks($resource, $currentRegion)->getBlocksArray();
        $regionBlockContentArray['tab_navigation'] = $this->renderTabsOnly($resource, $contentRegion, $layout);
        return implode('', $regionBlockContentArray);
    }
}
