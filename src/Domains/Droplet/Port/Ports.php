<?php

namespace SuperV\Platform\Domains\Droplet\Port;

use SuperV\Platform\Support\Collection;

class Ports extends Collection
{
    public function push($value)
    {
        return parent::push($value);
    }

    public function byHostname($hostname)
    {
        /** @var \SuperV\Platform\Domains\Droplet\Port\Port $port */
        foreach ($this->items as $port) {
            if ($port->getHostname() == $hostname) {
                return $port;
            }
        }
    }

    public function byRequest($hostname, $uri)
    {
        /** @var \SuperV\Platform\Domains\Droplet\Port\Port $port */
        foreach ($this->items as $port) {
            if ($hostname == $port->getHostname()) {
                if (! $port->getPrefix() || starts_with(ltrim($uri, '/'), $port->getPrefix())) {
                    return $port;
                }
            }
        }
    }
}