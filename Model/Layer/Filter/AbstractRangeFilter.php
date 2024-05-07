<?php
namespace Swissup\AlnStigfabrikken\Model\Layer\Filter;

use Swissup\AlnStigfabrikken\Model\Layer\Filter\AbstractFilter as DefaultFilter;
use Swissup\Ajaxlayerednavigation\Model\Layer\Filter\ItemFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer;
use Swissup\Ajaxlayerednavigation\Model\Layer\Filter\Item\Builder;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class AbstractRangeFilter extends DefaultFilter
{
    /**
     *
     * @var \Swissup\AlnStigfabrikken\Model\Layer\AttributeValueRangeResolver
     */
    private $rangeResolver;

    /**
     *
     * @var array
     */
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
     * @param \Swissup\AlnStigfabrikken\Model\Layer\AttributeValueRangeResolver $rangeResolver
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
        \Swissup\AlnStigfabrikken\Model\Layer\AttributeValueRangeResolver $rangeResolver,
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

        $this->rangeResolver = $rangeResolver;
    }

    /**
     *
     * @return array
     */
    protected function getRangeInterval()
    {
        $values = $this->rangeResolver
            ->setAttributeModel($this->getAttributeModel())
            ->resolve();

        $this->options = $this->rangeResolver->getOptions();

        if (empty($values)) {
            return [
                'min' => 0,
                'max' => 0,
            ];
        }
        $this->setData('step', 0.1);

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

        list($from, $to) = $this->splitRangeFilter($filter);

        $collection = $this->getLayer()->getProductCollection();

        $flagName = $this->getFlagName();
        if ($collection->hasFlag($flagName)) {
            return $this;
        }
        $collection->setFlag($flagName, true);

        $attributeCode = $this->getAttributeCode();
        $inputType = $this->getAttributeModel()->getFrontend()->getInputType();

        if ($inputType === 'select') {
            $attributeFilter = [];

            if ($this->options === null) {
                $this->getRangeInterval();
            }

            $step = 0.001;//$this->getStep();//getData('step');
            $filterRange = range($from, $to, $step);
            foreach ($this->options as $optionId => $optionValue) {
                $value = is_array($optionValue) ? $optionValue : [$optionValue];

                $start = min($value);
                $end = max($value);

                $optionRange = range($start, $end, $step);
                // if ($from <= $start && $end <= $to) {
                if (count(array_intersect($filterRange, $optionRange)) > 0) {
                    $attributeFilter[] = $optionId;
                }
            }

            if (!empty($attributeFilter)) {
                $collection->addFieldToFilter($attributeCode, $attributeFilter);
            } else {
                // nothing found
                $collection->addFieldToFilter($attributeCode, [null]);
            }
        } else {
            $_collection = $this->getCloneProductionCollection();
            $_collection->addAttributeToFilter($attributeCode, ['from' => $from, 'to' => $to]);
            $entityIds = count($_collection)/*->getSize()*/ ? $_collection->getAllIds() : [];
            $collection->getSelect()->where('e.entity_id IN (?)', $entityIds);
        }

        // $collection->addIdFilter($entityIds);
        // $collection->addFieldToFilter('entity_id', $entityIds);
        // $collection->getSelect()->where('e.entity_id IN (?)', $entityIds);

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
        return __('%1 - %2 (cm)', $from, $to);
    }
}
