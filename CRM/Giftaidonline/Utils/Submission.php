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

class CRM_Giftaidonline_Utils_Submission {
  /*
   * Function to return the array of submission id & date
   */
  static function getSubmissionIdTitle( $orderBy = 'id' ){
    $query = "SELECT * FROM civicrm_gift_aid_submission ORDER BY " . $orderBy;
    $dao   =& CRM_Core_DAO::executeQuery( $query);

    $result = [];
    while ( $dao->fetch( ) ) {
      $result[$dao->id] = $dao->id." - ".$dao->created_date;
    }
    return $result;
  }

}
