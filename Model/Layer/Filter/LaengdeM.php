<?php
namespace Swissup\AlnStigfabrikken\Model\Layer\Filter;

use Swissup\AlnStigfabrikken\Model\Layer\Filter\RangeResolver\AbstractFilter;

class LaengdeM extends AbstractFilter
{   
    protected function initRequestVar()
    {
            $this->setRequestVar('laengde_m');
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
        return __('%1 - %2 (m)', $from, $to);
    }

    public function getStep()
    {
        return 0.1;
    }
}
