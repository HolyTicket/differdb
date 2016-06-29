<?php
return [
    'table' => [
        'create' => 'CREATE TABLE IF NOT EXISTS `%s` (',
        'close' => ' CHARACTER SET %s COLLATE %s ROW_FORMAT=%s  ENGINE=%s;',
        'drop' => 'DROP TABLE `%s`',
        'alter' => 'ALTER TABLE `%(table_name)s` CHANGE `%(original_name)s`',
        'definition' => '`%(name)s` %(type)s %(null)s %(auto_increment)s %(suffix)s %(default)s %(comment)s',
        'rename' => 'RENAME TABLE `%s` TO `%s`',
        'option' => [
            'alter_collation' => 'ALTER TABLE `%s` CONVERT TO CHARACTER SET %s COLLATE %s;',
            'alter_row_format' => 'ALTER TABLE `%s` ROW_FORMAT=%s;',
            'alter_engine' => 'ALTER TABLE `%s` ENGINE=%s;'
        ]
    ],
    'column' => [
        'add' => 'ALTER TABLE `%s` ADD %s %s;',
        'drop' => 'ALTER TABLE `%s` DROP `%s`;',
    ],
    'index' => [
        'drop' => 'DROP INDEX `%s` ON `%s`;',
        'alter_primary_key' => 'ALTER TABLE `%s` DROP PRIMARY KEY,  ADD PRIMARY KEY %s;',
        'alter_unique_key' => 'ALTER TABLE `%s` DROP INDEX `%s`, ADD UNIQUE KEY `%s` %s;',
        'alter_index' => 'ALTER TABLE `%s` DROP INDEX `%s`,  ADD INDEX `%s`%s;',
        'add_primary_key' => 'ALTER TABLE `%s` ADD PRIMARY KEY %s;',
        'add_unique_key' => 'ALTER TABLE `%s` ADD UNIQUE KEY `%s` %s;',
        'add_index' => 'ALTER TABLE `%s` ADD KEY `%s` %s;',
        'implicit' => [
            'primary' => 'PRIMARY KEY %s',
            'unique' => 'UNIQUE KEY `%s` %s',
            'key' => 'KEY `%s` %s'
        ]
    ],
    'constraint' => [
        'drop' => 'ALTER TABLE `%s` DROP FOREIGN KEY `%s`;',
        'add' => 'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY %s REFERENCES `%s`%s ON DELETE %s ON UPDATE %s;'
    ]
];