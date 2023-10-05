<?php
namespace Swissup\AlnStigfabrikken\Model\Layer\Filter;

use Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Slider\AbstractFilter;
use Swissup\Ajaxlayerednavigation\Model\Layer\Filter\ItemFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer;
use Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Item\Builder;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class LofthoejdeMaxM extends AbstractFilter
{
    /**
     *
     * @var array of \Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Price\AggregationRange
     */
    private $ranges;

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
        $this->setRequestVar('lofthoejde_max_m');
//        $this->setRequestVar($this->getAttributeCode());

        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemBuilder,
            $algorithmFactory,
            $dataProviderFactory,
            $resourceFilterPrice,
            $data
        );

        $range = $rangeFactory->create();

        $range = $this->initRange($range);
        $this->setRange($range);
    }

    /**
     *
     * @return \Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Slider\Range
     */
    private function getRange()
    {
        return $this->ranges[$this->getAttributeCode()];
    }

    /**
     *
     * @param \Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Slider\Range $range
     */
    private function setRange(\Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Slider\Range $range)
    {
        $this->ranges[$this->getAttributeCode()] = $range;

        return $this;
    }

    /**
     *
     * @return array
     */
    private function getRangeInterval()
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
     */
    private function initRange($range)
    {
        if ($range->getCount() < 0) {
            $rangeInterval = $this->getRangeInterval();
            $range
                ->setCount(0)
                ->setMin($rangeInterval['min'])
                ->setMax($rangeInterval['max']);
        }

        return $range;
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    private function getCloneProductionCollection()
    {
        $attributeCode = $this->getAttributeCode();
        $productCollection = clone $this->getLayer()->getProductCollection()
            ->addAttributeToSelect($attributeCode);

        return $productCollection;
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
     * @return string
     */
    protected function getFlagName()
    {
        return "swissup_aln_{$this->getAttributeCode()}_filter_applied";
    }

    /**
     *
     * @return array
     */
    protected function _getItemsData()
    {
        if ($this->getMaxPrice() - $this->getMinPrice() < 2) {
            return [];
        }

        return parent::_getItemsData();
    }

    /**
     *
     * Fix for getPriceUrlTemplate() ->setRawAppliedOptions('');
     *
     * @return boolean
     */
    public function hasAppliedOption()
    {
        $raw = $this->getRawAppliedOptions();
        return !empty($raw);
    }

    /**
     *
     * @return float
     */
    public function getMinPrice()
    {
        $price = $this->getRange()->getMin();
        $price = $price > 0 ? $price : 0;

        return floor((float) $price);
    }

    /**
     *
     * @return float
     */
    public function getMaxPrice()
    {
        $price = $this->getRange()->getMax();
        return ceil((float) $price);
    }

    /**
     *
     * @return float
     */
    public function getFromMinPrice()
    {
        if ($this->hasAppliedOption() || $this->hasAppliedOptionInState()) {
            $rangeInterval = $this->getRangeInterval();
            $price = $rangeInterval['min'];
            return floor((float) $price);
        }

        return $this->getMinPrice();
    }

    /**
     *
     * @return float
     */
    public function getToMaxPrice()
    {
        if ($this->hasAppliedOption() || $this->hasAppliedOptionInState()) {
            $rangeInterval = $this->getRangeInterval();
            $price = $rangeInterval['max'];
            return ceil((float) $price);
        }

        return $this->getMaxPrice();
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
