<?php

namespace Runalyze\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TSC: Creates new column for HR user-max and zones informations.
 */
class Version20240516200000 extends AbstractMigration implements ContainerAwareInterface {
    /** @var ContainerInterface|null */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $prefix = $this->container->getParameter('database_prefix');

        $this->addSql('ALTER TABLE `'.$prefix.'training`
                                ADD `max_hr_user` TINYINT UNSIGNED DEFAULT NULL AFTER `pulse_avg_active`,
                                ADD `hr_zone_bounds` VARCHAR(200) DEFAULT NULL AFTER `max_hr_user`,
                                ADD `fit_seconds_hr_zones` VARCHAR(100) DEFAULT NULL AFTER `fit_stand_time`;
                                ');
        $this->addSql('ALTER TABLE `'.$prefix.'sport`
                                ADD `hr_zone_bounds` VARCHAR(200) DEFAULT NULL AFTER `default_privacy`;
                                ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $prefix = $this->container->getParameter('database_prefix');
        $this->addSql('ALTER TABLE `'.$prefix.'training`
                                DROP `max_hr_user`,
                                DROP `hr_zone_bounds`,
                                DROP `fit_seconds_hr_zones`;
                                ');
        $this->addSql('ALTER TABLE `'.$prefix.'sport`
                                DROP `hr_zone_bounds`;
                                ');
    }
}