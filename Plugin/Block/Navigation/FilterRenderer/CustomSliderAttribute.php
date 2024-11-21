<?php

namespace Swissup\AlnStigfabrikken\Plugin\Block\Navigation\FilterRenderer;

/**
 * Class FilterRenderer
 */
class CustomSliderAttribute
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $layout;

    /**
     * Path to RenderLayered Block
     *
     * @var string
     */
    protected $blockClassName = \Swissup\Ajaxlayerednavigation\Block\Navigation\RenderLayered\Slider::class;

    /**
     * @param \Magento\Framework\View\LayoutInterface $layout
     */
    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->layout = $layout;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param \Magento\LayeredNavigation\Block\Navigation\FilterRenderer $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Model\Layer\Filter\FilterInterface $filter
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundRender(
        \Magento\LayeredNavigation\Block\Navigation\FilterRenderer $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Layer\Filter\FilterInterface $filter
    ) {
        /** @var \Swissup\Ajaxlayerednavigation\Model\Layer\Filter\AbstractFilter $filter */
        if ($filter->hasAttributeModel()) {
            $attributeModel = $filter->getAttributeModel();
            $customAttributeCodes = [
                'lofthoejde_max_m',
                'hulmaal_bredde',
                'hulmaal_laengde',
                'uvaerdi',
                'laengde_m',
                'lodret_hoejde_m',
                'platformshoejde_m',
                'lofthojde_interval_m',
            ];

            if (in_array($attributeModel->getAttributeCode(), $customAttributeCodes)) {
                /** @var \Swissup\Ajaxlayerednavigation\Block\Navigation\RenderLayered\Slider $block */
                $block = $this->layout->createBlock($this->blockClassName);
                $block->setFilter($filter);

                $keys = ['product_layer_view_model' , 'view_model'];
                foreach ($keys as $key) {
                    $viewModel = $subject->getData($key);
                    $block->setData($key, $viewModel);
                }

                return $block->toHtml();
            }
        }
        return $proceed($filter);
    }
}
