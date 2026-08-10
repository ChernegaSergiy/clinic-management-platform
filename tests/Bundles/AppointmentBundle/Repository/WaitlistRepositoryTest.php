<?php

namespace App\Tests\Bundles\AppointmentBundle\Repository;

use App\Domain\Appointment\WaitlistRepository;
use App\Domain\Appointment\Waitlist;
use App\Tests\RepositoryTestCase;

class WaitlistRepositoryTest extends RepositoryTestCase
{
    private WaitlistRepository $repository;

    protected function setUp() : void
    {
        parent::setUp();

        $registry = $this->createMockManagerRegistry(Waitlist::class);
        $this->repository = new WaitlistRepository($registry);
    }

    public function testGenerateWaitlistTicketFormat() : void
    {
        $this->createMockQueryBuilder(5, true);
        $result = $this->repository->generateWaitlistTicket();
        $this->assertStringStartsWith('WL-', $result);
        $this->assertStringContainsString('-00006', $result);
    }
}
