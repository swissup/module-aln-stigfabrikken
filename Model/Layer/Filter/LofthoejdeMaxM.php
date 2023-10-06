<?php
namespace Swissup\AlnStigfabrikken\Model\Layer\Filter;

use Swissup\AlnStigfabrikken\Model\Layer\Filter\AbstractFilter;
use Swissup\Ajaxlayerednavigation\Model\Layer\Filter\ItemFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer;
use Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Item\Builder;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class LofthoejdeMaxM extends AbstractFilter
{
    private $options;

    /**
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param StoreManagerInterface  $storeManager
     * @param Layer                  $layer
     * @param Builder                $itemBuilder
     * @param array                  $data
     * @param \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory
     * @param \Swissup\Ajaxlayerednavigation\Model\ResourceModel\Layer\Filter\Price $resourceFilterPrice
     * @param \Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Slider\RangeFactory $rangeFactory
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        Builder $itemBuilder,
        \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory,
        \Swissup\Ajaxlayerednavigation\Model\ResourceModel\Layer\Filter\Price $resourceFilterPrice,
        \Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Slider\RangeFactory $rangeFactory,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemBuilder,
            $algorithmFactory,
            $dataProviderFactory,
            $resourceFilterPrice,
            $rangeFactory,
            $data
        );
    }
    
    protected function initRequestVar()
    {
        $this->setRequestVar('lofthoejde_max_m');
//        $this->setRequestVar($this->getAttributeCode());
    }

    /**
     *
     * @return array
     */
    protected function getRangeInterval()
    {
        $attributeCode = $this->getAttributeCode();
        $productCollection = $this->getCloneProductionCollection();

        $optionIds = $productCollection->getColumnValues($attributeCode);
        $optionIds = array_filter($optionIds);
        $optionIds = array_unique($optionIds);

        $options = [];
        foreach($optionIds as $optionId) {
            $options[$optionId] = (float) str_replace(',', '.', $this->getOptionText($optionId));
        }

        $this->options = $options;
        $values = $options;
        $values = array_filter($values);
        // $values = array_map('intval', $values);
        asort($values);

        unset($productCollection);

        return [
            'min' => floor(min($values)),
            'max' => ceil(max($values)),
        ];
    }

    /**
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return Slider
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        $filter = $request->getParam($this->getRequestVar());

        if (!$filter || is_array($filter)) {
            return $this;
        }

        $this->setRawAppliedOptions($filter);

        $sfilter = explode('-', $filter);
        $from = isset($sfilter[0]) ? (float) $sfilter[0] : $this->getMinPrice();
        $to = isset($sfilter[1]) ? (float) $sfilter[1] : $this->getMaxPrice();

        $_collection = $this->getCloneProductionCollection();

        $collection = $this->getLayer()->getProductCollection();

        $flagName = $this->getFlagName();
        $collection->setFlag($flagName, true);

        $attributeCode = $this->getAttributeCode();

        $attributeFilter = [];
        foreach ($this->options as $optionId => $optionValue) {

            if ($from <= $optionValue && $optionValue <= $to) {
                $attributeFilter[] = $optionId;
            }
        }

        $entityIds = [];
        if (!empty($attributeFilter))  {
            $_collection->addFieldToFilter($attributeCode, $attributeFilter);
            $entityIds = $_collection->getAllIds();
        }

        $collection->getSelect()->where('e.entity_id IN (?)', $entityIds);

        $this->getState()->addFilter(
            $this->_createItem(
                $this->_renderLabel(empty($from) ? 0 : $from, $to),
                $filter
            )
        );

        return $this;
    }

    /**
     *
     * @param  float $from
     * @param  float $to
     * @return string
     */
    protected function _renderLabel($from, $to)
    {
        return __('%1 - %2 (m)', $from, $to);
    }
}
