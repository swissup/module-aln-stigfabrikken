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

    private $clonedProductCollection;
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

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    private function getCloneProductionCollection()
    {
        if ($this->clonedProductCollection === null) {
            $this->clonedProductCollection = clone $this->layer->getProductCollection();
        }
        return $this->clonedProductCollection;
    }
    
    public function resolve() 
    {
        $attributeCode = $this->attributeModel->getAttributeCode();
        $attributeId = $this->attributeModel->getAttributeId();
        $layerProductCollection = $this->getCloneProductionCollection()
            ->addAttributeToSelect($attributeCode);

        $values = $layerProductCollection->getColumnValues($attributeCode);

        $parentIds = $layerProductCollection->getAllIds();
        $variationIds = $this->getVariationIds($parentIds);
        $variationValues = $this->getVariationValues($variationIds);

        $values = array_unique(array_merge($values, $variationValues));
        $values = array_filter($values);
        $values = array_map('floatval', $values);
        asort($values);
        return $values;
        // foreach($layerProductCollection->getItems() as $item) {
        //     if($item->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
        //         $productTypeInstance = $item->getTypeInstance();
        //         $usedProducts = $productTypeInstance->getUsedProducts($item, [$attributeId]);

        //         foreach($usedProducts as $usedProduct) {
        //             $values[] = $usedProduct->getData($attributeCode);
        //         }
        //         // var_dump([$item->getId(), count($usedProducts)]);
        //         // die;
        //     }
        // }

        // $values = array_map('floatval', $values);
        // // $values = array_filter($values);
        // $values = array_unique($values);
        // asort($values);

        // unset($layerProductCollection);
        
        return $values;
    }

    private function getVariationIds($parentIds)
    {
        $attributeCode = $this->attributeModel->getAttributeCode();
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
        $attributeCode = $this->attributeModel->getAttributeCode();

        $collection = $this->productCollectionFactory->create()
            ->addIdFilter($ids)
            ->addAttributeToSelect($attributeCode)
        ;

        return $collection->getColumnValues($attributeCode);
    }
}
