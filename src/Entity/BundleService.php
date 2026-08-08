<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'bundle_services')]
class BundleService
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $bundle_id = null;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $service_id = null;

    public function getBundleId() : ?int
    {
        return $this->bundle_id;
    }
    public function setBundleId(?int $bundle_id) : self
    {
        $this->bundle_id = $bundle_id;
        return $this;
    }
    public function getServiceId() : ?int
    {
        return $this->service_id;
    }
    public function setServiceId(?int $service_id) : self
    {
        $this->service_id = $service_id;
        return $this;
    }
}
