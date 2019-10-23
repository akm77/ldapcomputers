DROP TABLE IF EXISTS `glpi_plugin_ldapcomputers_configs`;
CREATE TABLE `glpi_plugin_ldapcomputers_configs` (
   `id` int(11) NOT NULL auto_increment,
   `use_infocom_alert` TINYINT( 1 ) NOT NULL DEFAULT '-1',
   `use_ink_alert` TINYINT( 1 ) NOT NULL DEFAULT '-1',
   `delay_ticket_alert` int(11) NOT NULL default '0',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
