<?php

namespace Runalyze\Bundle\CoreBundle\Tests\DataFixtures;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\ORM\LoadAccountData;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class AbstractFixturesAwareWebTestCase extends WebTestCase
{
    use FixturesTrait;

    /** @var  Client */
    protected $Client;

    /** @var ReferenceRepository */
    protected $Fixtures;

    /** @var array|null */
    protected $FixtureClasses = null;

    /** @var EntityManager */
    protected $EntityManager;

    protected function setUp() : void
    {
        if (null === $this->FixtureClasses) {
            $this->FixtureClasses = [
                LoadAccountData::class
            ];
        }

        $this->Fixtures = $this->loadFixtures($this->FixtureClasses)->getReferenceRepository();

        $this->Client = $this->getContainer()->get('test.client');
        $this->Client->disableReboot();

        $this->EntityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->EntityManager->clear();

        parent::setUp();
    }

    protected function tearDown() : void
    {
        parent::tearDown();

        if (null !== $this->EntityManager) {
            $this->EntityManager->close();
            $this->EntityManager = null;
        }
    }

    /**
     * @return Account
     */
    protected function getDefaultAccount()
    {
        $ref = $this->Fixtures->getReference('account-default');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository('CoreBundle:Account')->find($ref->getId());
    }

    /**
     * @return Sport
     */
    protected function getDefaultAccountsRunningSport()
    {
        $ref = $this->Fixtures->getReference('account-default.sport-running');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository('CoreBundle:Sport')->find($ref->getId());
    }

    /**
     * @return Sport
     */
    protected function getDefaultAccountsCyclingSport()
    {
        $ref = $this->Fixtures->getReference('account-default.sport-cycling');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository('CoreBundle:Sport')->find($ref->getId());
    }

    /**
     * @return EquipmentType
     */
    protected function getDefaultAccountsClothesType()
    {
        $ref = $this->Fixtures->getReference('account-default.equipment-type-clothes');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository('CoreBundle:EquipmentType')->find($ref->getId());
    }

    /**
     * @return Account
     */
    protected function getEmptyAccount()
    {
        $ref = $this->Fixtures->getReference('account-empty');
        // #TSC do not use directly the ref, reload it, so avoid "A new entity was found through the relationship xxx that was not configured to cascade persist operations for entity
        return $this->EntityManager->getRepository('CoreBundle:Account')->find($ref->getId());
    }

    /**
     * @param int|null $timestamp
     * @param int|float $duration
     * @param float|int|null $distance
     * @param Sport|null $sport
     * @return Training
     */
    protected function getActivityForDefaultAccount(
        $timestamp = null,
        $duration = 3600,
        $distance = null,
        Sport $sport = null
    )
    {
        return (new Training())
            ->setS($duration)
            ->setTime($timestamp ?: time())
            ->setDistance($distance)
            ->setSport($sport ?: $this->getDefaultAccountsRunningSport())
            ->setAccount($this->getDefaultAccount());
    }
}
