<?php
use CRM_Giftaidonline_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Giftaidonline_Upgrader extends CRM_Giftaidonline_Upgrader_Base {

  public function postInstall() {
    $this->createReportInstance();
    return TRUE;
  }

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

  public function upgrade_1802() {
    $this->ctx->log->info('Update giftaidonlinefailure report instance');
    $this->createReportInstance();
    return TRUE;
  }

  private function createReportInstance() {
    // Check if we have one with the old name and update to new name
    $query = "UPDATE `civicrm_report_instance` SET report_id='" . E::SHORT_NAME . "/giftaidonlinefailure' WHERE report_id='uk.co.vedaconsulting.module.giftaidonline/giftaidonlinefailure'";
    CRM_Core_DAO::executeQuery($query);
    $query = "UPDATE `civicrm_report_instance` SET title='Gift Aid Submission failure report' WHERE title='giftaidonlinefailure'";
    CRM_Core_DAO::executeQuery($query);
    $query = "UPDATE `civicrm_report_instance` SET description='Show validation errors in batches ready for submission' WHERE report_id='" . E::SHORT_NAME . "/giftaidonlinefailure'";
    CRM_Core_DAO::executeQuery($query);

    // Now see if we have a report and create one if we don't
    try {
      $reportID = CRM_Core_DAO::singleValueQuery("SELECT id FROM `civicrm_report_instance` WHERE report_id='" . E::SHORT_NAME . "/giftaidonlinefailure'");
    }
    catch (Exception $e) {
      $reportID = NULL;
    }

    if (!$reportID) {
      try {
        $reportTemplate = civicrm_api3('ReportTemplate', 'getsingle', [
          'value' => E::SHORT_NAME . '/giftaidonlinefailure',
        ]);
        civicrm_api3('ReportInstance', 'create', [
          'title' => 'Gift Aid Submission failure report',
          'report_id' => $reportTemplate['value'],
          'description' => 'Show validation errors in batches ready for submission',
          'permission' => 'access CiviReport',
        ]);
      }
      catch (Exception $e) {
        \Civi::log()->error('Failed to create giftaidonlinefailure report instance. ' . $e->getMessage());
      }
    }
  }

}
