<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed"

use CRM_Giftaidonline_ExtensionUtil as E;

return [
  0 =>
  [
    'name' => 'CRM_Giftaidonline_Form_Report_giftaidonlinefailure',
    'entity' => 'ReportTemplate',
    'params' =>
    [
      'version' => 3,
      'label' => 'Gift Aid Submission failure report',
      'description' => 'Show validation errors in batches ready for submission',
      'class_name' => 'CRM_Giftaidonline_Form_Report_giftaidonlinefailure',
      'report_url' => E::SHORT_NAME . '/giftaidonlinefailure',
      'component' => 'CiviContribute',
    ],
  ],
];
