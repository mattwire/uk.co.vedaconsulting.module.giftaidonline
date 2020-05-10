CREATE TABLE IF NOT EXISTS `civicrm_gift_aid_rejected_contributions` (
`id`                          int(10) unsigned NOT NULL auto_increment,
`batch_id`                    int(10) unsigned NOT NULL,
`contribution_id`             int(10) unsigned NOT NULL,
`created_date`                timestamp        DEFAULT CURRENT_TIMESTAMP,
`rejection_reason`            varchar(255)      NOT NULL,
`rejection_detail`            varchar(255)      NOT NULL,
PRIMARY KEY  (`id`),
KEY `git_aid_rejections_contribution_id` (`contribution_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `civicrm_gift_aid_submission` (
`id`                          int(10) unsigned NOT NULL auto_increment,
`batch_id`                    int(10) unsigned NOT NULL,
`created_date`                timestamp        DEFAULT CURRENT_TIMESTAMP,
`request_xml`                 longtext         NOT NULL,
`response_xml`                longtext         NOT NULL,
`response_qualifier`          varchar(50),
`response_errors`             longtext,
`response_end_point`          longtext,
`response_end_point_interval` int(3),
`response_correlation_id`     varchar(255),
`transaction_id`              varchar(255),
`IRMark`                      varchar(255),
`gateway_timestamp`           timestamp,
PRIMARY KEY  (`id`),
KEY `batch_id` (`batch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `civicrm_gift_aid_polling_request` (
`id`                          int(10) unsigned NOT NULL auto_increment,
`submission_id`               int(10) unsigned NOT NULL,
`created_date`                timestamp        DEFAULT CURRENT_TIMESTAMP,
`request_xml`                 longtext         NOT NULL,
`response_xml`                longtext         NOT NULL,
`response_qualifier`          varchar(50),
`response_errors`             longtext,
`response_end_point`          longtext,
`response_end_point_interval` int(3),
`response_correlation_id`     varchar(255),
`transaction_id`              varchar(255),
`gateway_timestamp`           timestamp,
PRIMARY KEY  (`id`),
CONSTRAINT `FK_civicrm_gift_aid_polling_request_submission_id`
FOREIGN KEY (`submission_id`) REFERENCES `civicrm_gift_aid_submission` (`id`)
ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `civicrm_gift_aid_submission_setting` (
`id`                        int(10) unsigned NOT NULL auto_increment,
`name`                      varchar(100)     NOT NULL,
`value`                     longtext,
`description`               longtext,
PRIMARY KEY  (`id`),
KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1;

INSERT IGNORE INTO `civicrm_gift_aid_submission_setting` ( `name`, `value`, `description`)
VALUES
('VENDOR_ID', '2355', 'The Vendor Id credential for communicating with HMRC Gateway'),
('SENDER_ID', null, 'Your HMRC User ID (SDS Reference is Sender ID)'),
('SENDER_VALUE', null, 'Your HMRC User Password (SDS Reference is Sender Value)'),
('CHAR_ID', null, 'The Charity Id credential for communicating with HMRC Gateway. This is the XR number you would have been supplied by the HMRC.'),
('CLAIMER_ORG_NAME', null, 'Name of the Organisation that is submitting the claim'),
('CLAIMER_ORG_HMRC_REF', null, 'The Charity Id credential for communicating with HMRC Gateway. This is the XR number you would have been supplied by the HMRC. Same value as CHAR_ID.'),
('CLAIMER_ORG_REGULATOR_NAME', 'CCEW', 'Abbreviated name of the Regulator belonging the Claimant organisation, for example Charity Commission of England and Wales is CCEW.'),
('CLAIMER_ORG_REGULATOR_NO', 'A1234', 'The Regulator Number belonging the Claimant organisation'),
('MODE', 'live', 'Are we in Live or Dev Mode'),
('PERIOD_END', '2013-03-31', 'The period end date of the current claim (set to YYYY-MM-DD format)'),
('SENDER_TYPE', 'Individual', 'Should be either Individual or Organisation'),
('AUTH_OFF_SURNAME', '', 'Surname of the Authorising Officer for your organisation'),
('AUTH_OFF_FORENAME', '', 'Forename of the Authorising Officer for your organisation'),
('AUTH_OFF_PHONE', '', 'Phone Number of the Authorising Officer for your organisation'),
('AUTH_OFF_POSTCODE', '', 'Postcode of the Authorising Officer for your organisation e.g. EC2A 3AY');
