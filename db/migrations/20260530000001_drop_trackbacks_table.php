<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropTrackbacksTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('DROP TABLE IF EXISTS `trackbacks`');
    }

    public function down(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE `trackbacks` (
              `trackback_id` int(11) NOT NULL auto_increment,
              `epobject_id` int(11) default NULL,
              `blog_name` varchar(255) default NULL,
              `title` varchar(255) default NULL,
              `excerpt` varchar(255) default NULL,
              `url` varchar(255) default NULL,
              `posted` datetime default NULL,
              `visible` tinyint(1) NOT NULL default '0',
              `source_ip` varchar(20) default NULL,
              PRIMARY KEY (`trackback_id`),
              KEY `visible` (`visible`)
            )
        SQL);
    }
}
