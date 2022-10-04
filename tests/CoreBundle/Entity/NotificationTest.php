<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Notification;

class NotificationTest extends \PHPUnit\Framework\TestCase
{
    /** @var Notification */
    protected $Notification;

    public function setUp() : void
    {
        $this->Notification = new Notification();
    }

    public function testThatConstructorSetsCurrentDate()
    {
        $this->assertEquals((new \DateTime('NOW'))->format('d.m.Y'), (new \DateTime())->setTimestamp($this->Notification->getCreatedAt())->format('d.m.Y'));
    }

    public function testSetNoLifetime()
    {
        $this->assertNull($this->Notification->setLifetime(null)->getExpirationAt());
    }

    public function testSetLifetime()
    {
        $this->assertEquals('+2 days', (new \DateTime('NOW'))->diff((new \DateTime())->setTimestamp($this->Notification->setLifetime(2)->getExpirationAt()))->format('%R%a days'));
    }
}
