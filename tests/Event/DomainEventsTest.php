<?php

namespace App\Tests\Event;

use App\Event\EntityChangedEvent;
use App\Event\NotificationRequestedEvent;
use App\Event\PatientNotificationEvent;
use App\Event\UserLoggedInEvent;
use App\Event\UserLoggedOutEvent;
use PHPUnit\Framework\TestCase;

class DomainEventsTest extends TestCase
{
    public function testEntityChangedEventExposesConstructorArguments() : void
    {
        $oldData = ['status' => 'draft'];
        $newData = ['status' => 'published'];

        $event = new EntityChangedEvent('Article', 42, 'update', $oldData, $newData);

        $this->assertSame('Article', $event->entityType);
        $this->assertSame(42, $event->entityId);
        $this->assertSame('update', $event->action);
        $this->assertSame($oldData, $event->oldData);
        $this->assertSame($newData, $event->newData);
    }

    public function testEntityChangedEventDefaultsOldAndNewDataToNull() : void
    {
        $event = new EntityChangedEvent('Article', 1, 'delete');

        $this->assertNull($event->oldData);
        $this->assertNull($event->newData);
    }

    public function testNotificationRequestedEventExposesConstructorArguments() : void
    {
        $event = new NotificationRequestedEvent('email', 'doctor@medcore.pp.ua', 'New appointment');

        $this->assertSame('email', $event->type);
        $this->assertSame('doctor@medcore.pp.ua', $event->recipient);
        $this->assertSame('New appointment', $event->message);
    }

    public function testPatientNotificationEventExposesConstructorArguments() : void
    {
        $context = ['appointment_id' => 7];

        $event = new PatientNotificationEvent(5, 'sms', 'Your visit is confirmed', $context);

        $this->assertSame(5, $event->patientId);
        $this->assertSame('sms', $event->type);
        $this->assertSame('Your visit is confirmed', $event->message);
        $this->assertSame($context, $event->context);
    }

    public function testPatientNotificationEventDefaultsContextToNull() : void
    {
        $event = new PatientNotificationEvent(5, 'sms', 'Your visit is confirmed');

        $this->assertNull($event->context);
    }

    public function testUserLoggedInEventExposesConstructorArguments() : void
    {
        $event = new UserLoggedInEvent(10, 'admin@medcore.pp.ua');

        $this->assertSame(10, $event->userId);
        $this->assertSame('admin@medcore.pp.ua', $event->email);
    }

    public function testUserLoggedOutEventExposesConstructorArguments() : void
    {
        $event = new UserLoggedOutEvent(10, 'admin@medcore.pp.ua');

        $this->assertSame(10, $event->userId);
        $this->assertSame('admin@medcore.pp.ua', $event->email);
    }
}
