<?php
namespace Swissup\AlnStigfabrikken\Model\Layer\Filter;

use Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Slider\AbstractFilter as DefaultSlider; 
use Swissup\Ajaxlayerednavigation\Model\Layer\Filter\ItemFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer;
use Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Item\Builder;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class AbstractFilter extends DefaultSlider
{

    /**
     * @var \Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Slider\RangeFactory
     */
    private $rangeFactory;
    
    /**
     *
     * @var \Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Price\AggregationRange
     */
    private $range;

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
        
        $this->rangeFactory = $rangeFactory;
    }
     
    /**
     *
     * @return \Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Slider\Range
     */
    protected function getRange()
    {
        if ($this->range === null) {
            $this->range = $this->initRange();
//            $this->setRange($range);
        }
        return $this->range;
    }
//
//    /**
//     *
//     * @param \Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Slider\Range $range
//     */
//    protected function setRange(\Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Slider\Range $range)
//    {
//        $this->range = $range;
//        return $this;
//    }
    
    /**
     *
     */
    protected function initRange()
    {
        $range = $this->rangeFactory->create();
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
     *
     * @return array
     */
    protected function getRangeInterval()
    {
        $values = [0, 1];
        return [
            'min' => floor(min($values)),
            'max' => ceil(max($values)),
        ];
    }    
    
    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function getCloneProductionCollection()
    {
        $attributeCode = $this->getAttributeCode();
        $productCollection = clone $this->getLayer()->getProductCollection()
            ->addAttributeToSelect($attributeCode);

        return $productCollection;
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
//            $price = $this->getRange()->getMin();
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
//            $price = $this->getRange()->getMax();
            return ceil((float) $price);
        }

        return $this->getMaxPrice();
    }
}