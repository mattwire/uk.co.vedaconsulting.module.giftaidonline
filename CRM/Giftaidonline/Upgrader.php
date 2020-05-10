<?php
use CRM_Giftaidonline_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Giftaidonline_Upgrader extends CRM_Giftaidonline_Upgrader_Base {

  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1800() {
    $this->ctx->log->info('Add columns to civicrm_gift_aid_rejected_contributions');

    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_gift_aid_rejected_contributions', 'rejection_detail')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_gift_aid_rejected_contributions` ADD COLUMN `rejection_detail` varchar(255) NOT NULL");
    }
    // Check if submission_id exists in the table
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_gift_aid_rejected_contributions', 'submission_id')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_gift_aid_rejected_contributions`
        ADD COLUMN `submission_id` int(10) unsigned AFTER `batch_id`");
    }
    return TRUE;
  }

  public function upgrade_1801() {
    $this->ctx->log->info('Remove CONTRIBUTION_DETAILS_SOURCE setting');

    CRM_Core_DAO::executeQuery('DELETE FROM `civicrm_gift_aid_submission_setting` WHERE `name` = "CONTRIBUTION_DETAILS_SOURCE"');
    return TRUE;
  }

}
