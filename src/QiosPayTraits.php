<?php

namespace Reactmore\QiosPay;

trait QiosPayTraits
{
    /**
     * Provides access to the Customer service.
     *
     * @return \ReactMoreTech\MayarHeadlessAPI\Services\V1\Customer
     */
    public function customer()
    {
        return $this->__call('customer', []);
    }
}