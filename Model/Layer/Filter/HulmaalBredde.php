<?php
namespace Swissup\AlnStigfabrikken\Model\Layer\Filter;

use Swissup\AlnStigfabrikken\Model\Layer\Filter\AbstractRangeFilter;

class HulmaalBredde extends AbstractRangeFilter
{
    protected function initRequestVar()
    {
        $this->setRequestVar('hulmaal_bredde');
//        $this->setRequestVar($this->getAttributeCode());
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
