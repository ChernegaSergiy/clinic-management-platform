<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Entity;

use App\Bundles\BillingBundle\Repository\BundleServiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BundleServiceRepository::class)]
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
