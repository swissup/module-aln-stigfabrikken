<?php
namespace Swissup\AlnStigfabrikken\Plugin\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;

class ConfigPlugin
{
    /**
     * @var LayerResolver
     */
    private $layerResolver;

    /**
     * YourClass constructor.
     *
     * @param LayerResolver $layerResolver
     */
    public function __construct(
        LayerResolver $layerResolver
    ) {
        $this->layerResolver = $layerResolver;
    }
    
    /**
     * Get the current category.
     *
     * @return \Magento\Catalog\Model\Category|null
     */
    private function getCurrentCategory()
    {
        return $this->layerResolver->get()->getCurrentCategory();
    }

    /**
     *
     * @return boolean
     */
    public function afterIsInfiniteScrollEnabled(
        AbstractHelper $helper,
        $result
    ) {
        return $result;
        
        if (!$result) {
            return false;
        }
        
        $currentCategory = $this->getCurrentCategory();
        
        if ($currentCategory && $currentCategory->getIsAnchor()
            //$currentCategory->getDisplayMode() == \Magento\Catalog\Model\Category::DM_PRODUCT
        ) {
            return true;
        }

        return false;
    }
}
