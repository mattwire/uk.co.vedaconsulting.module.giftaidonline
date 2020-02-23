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

abstract class CRM_Giftaidonline_Utils_Hook {

  static $_nullObject = null;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = null;

  /**
   * Constructor and getter for the singleton instance
   * @return instance of $config->userHookClass
   */
  static function singleton( ) {
    if (self::$_singleton == null) {
      $config = CRM_Core_Config::singleton( );
      $class = $config->userHookClass;
      require_once( str_replace( '_', DIRECTORY_SEPARATOR, $config->userHookClass ) . '.php' );
      self::$_singleton = new $class();
    }
    return self::$_singleton;
  }

  abstract function invoke( $numParams,
                            &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6,
                            $fnSuffix );

  /**
   * This hook allows filtering contributions for gift-aid
   * @param bool    $isEligible eligibilty already detected if getDeclaration() method.
   * @param integer $contactID  contact being checked
   * @param date    $date  date gift-aid declaration was made on
   * @param $contributionID  contribution id if any being referred
   *
   * @access public
   */
  static function giftAidOnlineSubmitted( $batchID ) {
    return self::singleton( )->invoke( 1, $batchID, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_giftAidOnlineSubmitted' );
  }

  /**
   * This hook allows doing any extra processing for contributions that were removed due to not meeting the gift aid online rules
   * @param $contributionsRemoved  contribution ids that have been removed from batch
   *
   * @access public
   */
  static function invalidGiftAidOnlineContribution( $batchID, $contributionIDRemoved ) {
    return self::singleton( )->invoke( 2, $batchID, $contributionIDRemoved, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_invalidGiftAidOnlineContribution' );
  }

  /**
   * This hook allows modification of query for selecting batches for Online giftaid submission
   * Example: selecting only giftaid batches for Online giftaid submission page
   * @param $query    query for selecting batches for Online giftaid submission
   *
   * @access public
   */
  static function modifySelectBatchesQuery( &$query ) {
    return self::singleton( )->invoke( 1, $query, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_modifySelectBatchesQuery' );
  }
}

