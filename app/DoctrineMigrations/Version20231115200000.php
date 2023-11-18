<?php

namespace Runalyze\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TSC: Creates new column for Pace-Pro additional informations.
 */
class Version20231115200000 extends AbstractMigration implements ContainerAwareInterface {
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
                                ADD `pace_goal` JSON DEFAULT NULL AFTER `partner`,
                                ADD `fit_run_time` MEDIUMINT UNSIGNED DEFAULT NULL AFTER `fit_load_peak`,
                                ADD `fit_walk_time` MEDIUMINT UNSIGNED DEFAULT NULL AFTER `fit_run_time`,
                                ADD `fit_stand_time` SMALLINT UNSIGNED DEFAULT NULL AFTER `fit_walk_time`;
                                ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $prefix = $this->container->getParameter('database_prefix');
        $this->addSql('ALTER TABLE `'.$prefix.'training`
                                DROP `pace_goal`,
                                DROP `fit_run_time`,
                                DROP `fit_walk_time`,
                                DROP `fit_stand_time`;
                                ');
    }
}