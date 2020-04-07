<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use CRM_Giftaidonline_ExtensionUtil as E;

class CRM_Giftaidonline_Batch {

  /**
   * Remove a contribution from the batch
   *
   * @param int $batchID
   * @param int $contributionID
   *
   * @return bool returns TRUE on success, FALSE if contribution was not in the batch
   * @throws \CiviCRM_API3_Exception
   */
  public static function removeContributionFromBatch($batchID, $contributionID) {
    $queryParams = [
      1 => [$batchID, 'Integer'],
      2 => [$contributionID, 'Integer']
    ];

    // Check we haven't already removed it from batch
    $checkSQL = "SELECT id FROM civicrm_entity_batch
WHERE batch_id=%1 AND entity_id=%2 AND entity_table='civicrm_contribution'";
    $id = CRM_Core_DAO::singleValueQuery($checkSQL, $queryParams);
    if (!$id) {
      return FALSE;
    }

    // Remove the contribution from the Batch
    $sqlEntityBatchDelete = "
      DELETE FROM civicrm_entity_batch
      WHERE batch_id = %1
      AND entity_id = %2
      AND entity_table = 'civicrm_contribution'
";

    CRM_Core_DAO::executeQuery($sqlEntityBatchDelete, $queryParams);

    // Remove batch_name from contribution custom data
    $groupID = civicrm_api3('CustomGroup', 'getvalue', [
      'return' => "id",
      'name' => "gift_aid",
    ]);
    $contributionParams = [
      'entity_id' => $contributionID,
      CRM_Civigiftaid_Utils::getCustomByName('batch_name', $groupID) => '',
    ];
    // We use CustomValue.create instead of Contribution.create because Contribution.create is way too slow
    civicrm_api3('CustomValue', 'create', $contributionParams);

    // Remove entry from rejected_contributions table
    $deleteSQL = "DELETE FROM civicrm_gift_aid_rejected_contributions WHERE batch_id=%1 AND contribution_id=%2";
    CRM_Core_DAO::executeQuery($deleteSQL, $queryParams);

    // hook to carry out other actions on removal of contribution from a gift aid online batch
    CRM_Giftaidonline_Utils_Hook::invalidGiftAidOnlineContribution($batchID, $contributionID);
    return TRUE;
  }
}
