<?php
namespace Swissup\AlnStigfabrikken\Model\Layer;

class AttributeValueRangeResolver 
{
    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    private $layer;
    
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory
     */
    private $configurableProductCollectionFactory;
    
    /**
     *
     * @var StoreManagerInterface
     */
    private $storeManager;
    
    private $attributeModel;

    private $options = [];

    /**
     *
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory $configurableProductCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->layer = $layerResolver->get();
        $this->productCollectionFactory = $productCollectionFactory;
        $this->configurableProductCollectionFactory = $configurableProductCollectionFactory;
        $this->storeManager = $storeManager;
    }
    
    public function setAttributeModel($model) 
    {
        $this->attributeModel = $model;
        return $this;
    }

    private function getInputType()
    {
        return $this->attributeModel->getFrontend()->getInputType();
    }

    private function getAttributeCode()
    {
        return $this->attributeModel->getAttributeCode();
    }
    
    public function resolve() 
    {
        $attributeCode = $this->getAttributeCode();
        // $attributeId = $this->attributeModel->getAttributeId();
        $layerProductCollection = clone $this->layer->getProductCollection();
        // $layerProductCollection = $this->getProductCollectionResolver()->resolve();
        $layerProductCollection = $layerProductCollection->addAttributeToSelect($attributeCode);

        $layerProductCollection->clear();
        $layerProductCollection->loadWithFilter();

        $parentIds = $layerProductCollection->getAllIds();
        $variationIds = $this->getVariationIds($parentIds);
        $variationValues = $this->getVariationValues($variationIds);

        $inputType = $this->getInputType();
        if ($inputType === 'text') {
            $columnValues = $layerProductCollection->getColumnValues($attributeCode);
            $rawValues = array_merge($columnValues, $variationValues);
        } else { //$inputType === 'select'
            $rawValues = $variationValues;
        }

        $this->options = $this->buildOptions($rawValues);

        $values = [];
        foreach($this->options as $range) {
            $values = array_merge($values, array_values($range));
        }
        $values = array_unique($values);
        // $values = array_filter($values);
        $values = array_filter($values, function($value) {
            return ($value !== null && $value !== false && $value !== '');
        });
        asort($values);

        return $values;

    }

    public function getOptions()
    {
        return $this->options;
    }

    private function getVariationIds($parentIds)
    {
        $attributeCode = $this->getAttributeCode();
        $collection = $this->configurableProductCollectionFactory->create()
            ->setFlag(
                'product_children',
                true
            )
            ->addFilterByRequiredOptions()
            ->addStoreFilter($this->storeManager->getStore()->getId())
        ;

        $collection->getSelect()->where(
            'link_table.parent_id IN (?)',
            $parentIds,
            \Zend_Db::INT_TYPE
        );

        return $collection->getAllIds();
    }

    private function getVariationValues($ids)
    {
        $attributeCode = $this->getAttributeCode();

        $collection = $this->productCollectionFactory->create()
            ->addIdFilter($ids)
            ->addAttributeToSelect($attributeCode)
        ;

        $columnValues = $collection->getColumnValues($attributeCode);

        $attributeModelFrontend = $this->attributeModel->getFrontend();
        $inputType = $this->getInputType();

        $values = [];
        if ($inputType === 'text') {
            $values = $columnValues;
        } elseif ($inputType === 'select') {
            $values = [];
            $optionIds = $columnValues;
            $optionIds = array_unique($optionIds);
            foreach($optionIds as $optionId) {
                $values[$optionId] = $attributeModelFrontend->getOption($optionId);
            }
        }
        return $values;
    }

    /**
     * @param $rawValues
     * @return array
     */
    private function buildOptions($rawValues): array
    {
        $rawValues = array_unique($rawValues);
        $rawValues = array_filter($rawValues, function($value) {
            return ($value !== null && $value !== false && $value !== '');
        });
        $options = [];
        foreach ($rawValues as $optionId => $value) {
            if (is_string($value)) {
                $value = str_replace(',', '.', $value);
            }
            if (is_string($value) && strstr($value, '-')) {
                list($start, $end) = explode('-', $value, 2);
                $options[$optionId] = [
                    /*'start' =>*/ (float) $start,
                    /*'end' => */(float) $end,
                ];
            } else {
                // $options[$optionId] = (float) $value;
                $options[$optionId] = [
                    /*'start' => (float) $value,
                    'end' => */(float) $value,
                ];;
            }
        }
        ksort($options);

        return $options;
    }
}
