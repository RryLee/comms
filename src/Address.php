<?php

namespace Concerto\Comms;

use Concerto\Sockets\Address as BaseAddress;

class Address extends BaseAddress implements AddressInterface
{
    public function isLocalResource() {
        return (
            $this->isLocal()
            && in_array($this->scheme, ['unix', 'udg'])
        );
    }
}