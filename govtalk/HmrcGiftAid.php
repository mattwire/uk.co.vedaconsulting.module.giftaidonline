<?php

#
#  HmrcGiftAid.php
#
#  Created by Long Luong on 13-03-2013.
#  Copyright 2013, Veda Consulting Limited. All rights reserved.
#
#  This program is free software: you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation, either version 3 of the License, or
#  (at your option) any later version.
#
#  You may obtain a copy of the License at:
#  http://www.gnu.org/licenses/gpl-3.0.txt
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.

/**
 * HMRC Gift Aid API client.  Extends the functionality provided by the
 * GovTalk class to build and parse HMRC Gift Aid submissions.  The php-govtalk
 * base class needs including externally in order to use this extention.
 *
 * @author Long Luong
 * @copyright 2013, Veda Consulting Limited
 * @licence http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License
 */
require_once 'CRM/Core/Page.php';
require_once ( dirname(__FILE__) . '/GiftAidGovTalk.php' );

class HmrcGiftAid extends Hmrc {

  /**
   * Specific Settings required for call HRMC Webservices.
   *
   * @var array
   */
  private $_Settings    = array();

  /* Magic methods. */

  /**
   * Instance constructor. Contains a hard-coded CH XMLGW URL and additional
   * schema location.  Adds a channel route identifying the use of this
   * extension.
   *
   * @param string $govTalkSenderId GovTalk sender ID.
   * @param string $govTalkPassword GovTalk password.
   * @param string $service The service to use ('dev', or 'live').
   */
  public function __construct() {
    $cSettingsSelect = <<<EOD
      SELECT setting.name                                    AS name
      ,      setting.value                                   AS value
      FROM   civicrm_gift_aid_submission_setting setting
EOD;
    $oDao = CRM_Core_DAO::executeQuery( $cSettingsSelect, array() );
    while ( $oDao->fetch() ) {
      $this->_Settings[$oDao->name] = $oDao->value;
    }

    $govTalkSenderId = $this->_Settings['SENDER_ID'];
    $govTalkPassword = $this->_Settings['SENDER_VALUE'];

    switch ($this->_Settings['MODE']) {
      case 'dev':
        parent::__construct( 'https://test-transaction-engine.tax.service.gov.uk/submission'
          , $govTalkSenderId
          , $govTalkPassword
        );
        $this->setTestFlag( true );
        break;
      default:
        parent::__construct( 'https://transaction-engine.tax.service.gov.uk/submission'
          , $govTalkSenderId
          , $govTalkPassword
        );
        $this->setTestFlag( false );
        break;
    }

    $this->setMessageAuthentication( 'clear' );
  }

  /**
   * Sets the message CorrelationID for use in MessageDetails header.
   *
   * @param string $messageCorrelationId The correlation ID to set.
   * @return boolean True if the CorrelationID is valid and set, false if it's invalid (and therefore not set).
   * @see function getResponseCorrelationId
   */
  public function setMessageCorrelationId( $messageCorrelationId = null ) {
    if ( empty( $messageCorrelationId ) ) {
      return true;
    }
    return parent::setMessageCorrelationId( $messageCorrelationId);
  }

  /* Public methods. */

  private function getHouseNo( $p_address_line ) {
    /*
     * split the phrase by any number of commas or space characters,
     * which include " ", \r, \t, \n and \f
     */
    $aAddress = preg_split( "/[,\s]+/", $p_address_line );
    if ( empty( $aAddress ) ) {
      return null;
    } else {
      return $aAddress[0];
    }
  }

  private function getDonorAddress( $p_contact_id, $p_contribution_id,  $p_contribution_receive_date ) {
    $oSetting             = new CRM_Giftaidonline_Page_giftAidSubmissionSettings();
    $sSource              = $oSetting->get_contribution_details_source();
    $aAddress['id']       = null;
    $aAddress['address']  = null;
    $aAddress['postcode'] = null;

    $bGetAddressFromDeclaration = stristr( $sSource, 'CONTRIBUTION' ) ? false : true;
    if ( $bGetAddressFromDeclaration ) {
      $sSql =<<<SQL
              SELECT   id         AS id
              ,        address    AS address
              ,        post_code  AS postcode
              FROM     civicrm_value_gift_aid_declaration
              WHERE    entity_id  =  %1
              AND      start_date <= %2
              AND      eligible_for_gift_aid = 1
              ORDER BY start_date ASC
              LIMIT  1
SQL;
      $aParams = array( 1 => array( $p_contact_id               , 'Integer' )
      , 2 => array( $p_contribution_receive_date, 'Timestamp'    )
      );
    } else {
      $sSql =<<<SQL
              SELECT   id        AS id
              ,        address   AS address
              ,        post_code AS postcode
              FROM     civicrm_value_gift_aid_submission
              WHERE    entity_id  = %1
              LIMIT  1
SQL;
      $aParams = array( 1 => array( $p_contribution_id, 'Integer' ) );
    }
    $oDao = CRM_Core_DAO::executeQuery( $sSql
      , $aParams
      , $abort         = TRUE
      , $daoName       = NULL
      , $freeDAO       = FALSE
      , $i18nRewrite   = TRUE
      , $trapException = TRUE /* This must be explicitly set to TRUE for the code below to handle any Exceptions */
    );
    if ( !( is_a( $oDao, 'DB_Error' ) ) ) {
      if ( $oDao->fetch() ) {
        $aAddress['id']       = $oDao->id;
        $aAddress['address']  = self::getHouseNo( $oDao->address );
        $aAddress['postcode'] = $oDao->postcode;
      }
    }

    return $aAddress;
  }

  //format postcode
  function postcodeFormat($postcode)
  {
    //remove non alphanumeric characters
    $cleanPostcode = preg_replace("/[^A-Za-z0-9]/", '', $postcode);

    //make uppercase
    $cleanPostcode = strtoupper($cleanPostcode);

    //insert space
    $postcode = substr($cleanPostcode, 0, -3) . " " . substr($cleanPostcode, -3);

    return $postcode;
  }

  function IsPostcode($postcode)
  {
    $postcode = strtoupper(str_replace(' ','',$postcode));
    if(preg_match("/^[A-Z]{1,2}[0-9]{2,3}[A-Z]{2}$/",$postcode) || preg_match("/^[A-Z]{1,2}[0-9]{1}[A-Z]{1}[0-9]{1}[A-Z]{2}$/",$postcode) || preg_match("/^GIR0[A-Z]{2}$/",$postcode))
    {
      return true;
    }
    else
    {
      return false;
    }
  }

  private function isValidPersonName( $p_name ) {
    $bValid = true;
    if ( empty( $p_name ) ||  !( preg_match('#^[A-Z \'.-]{1,50}$#i', $p_name ) ) ) {
      /* Name must be 1-50 characters Alphabetic including the single quote, dot, and hyphen symbol */
      $bValid = false;
    }

    return $bValid;
  }

  private function logBadDonorRecord( $batch_id
    , $batch_name
    , $created_date
    , $contribution_id
    , $contact_id
    , $first_name
    , $last_name
    , $amount
    , $gift_aid_amount
    , $address
    , $postcode
    , $validation_msg
  ) {
    $sMessage =<<<EOF
        batch_id: $batch_id
      , batch_name: $batch_name
      , created_date: $created_date
      , contribution_id: $contribution_id
      , contact_id: $contact_id
      , first_name: $first_name
      , last_name: $last_name
      , amount: $amount
      , gift_aid_amount: $gift_aid_amount
      , address: $address
      , postcode: $postcode
      , message: $validation_msg
EOF;

    CRM_Core_Error::debug_log_message( "Invalid Donor Record. Details ...\n$sMessage", TRUE );

    $sSql =<<<EOF
            INSERT INTO civicrm_gift_aid_rejected_contributions(
              batch_id
            , contribution_id
            , rejection_reason
            ) VALUES (
              %1
            , %2
            , %3
            );
EOF;
    $aQueryParam = array( 1   => array( $batch_id, 'Integer' )
    , 2   => array( $contribution_id, 'Integer' )
    , 3   => array( empty( $validation_msg                ) ? '' : $validation_msg               , 'String'  )
    );

    $oDao = CRM_Core_DAO::executeQuery( $sSql, $aQueryParam );
    if ( is_a( $oDao, 'DB_Error' ) ) {
      CRM_Core_Error::fatal( 'Trying to create a new Submission record failed.' );
    }
    $rejectionId = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');

    // Remove the contribution from the Batch
    $cEntityDelete = <<<EOD
      DELETE FROM civicrm_entity_batch
      WHERE batch_id = %1
      AND entity_id = %2
      AND entity_table = 'civicrm_contribution'
EOD;
    $aQueryParam          = array( 1 => array( $batch_id, 'Integer' )
    , 2 => array( $contribution_id, 'Integer' ));

    $oDao                 = CRM_Core_DAO::executeQuery( $cEntityDelete, $aQueryParam );

    // hook to carry out other actions on removal of contribution from a gift aid online batch
    CRM_Giftaidonline_Utils_Hook::invalidGiftAidOnlineContribution( $batch_id, $contribution_id );

    return $rejectionId;
  }

  private function build_giftaid_donors_xml( $pBatchId, &$package, &$rejections ) {
    $cDonorSelect = <<<EOD
      SELECT batch.id                                                  AS batch_id
      ,      batch.title                                               AS batch_name
      ,      contribution.receive_date                                 AS created_date
      ,      contribution.id                                           AS contribution_id
      ,      contact.id                                                AS contact_id
      ,      contact.first_name                                        AS first_name
      ,      contact.last_name                                         AS last_name
      ,      value_gift_aid_submission.amount                          AS amount
      ,      value_gift_aid_submission.gift_aid_amount                 AS gift_aid_amount
      FROM  civicrm_entity_batch entity_batch
      INNER JOIN civicrm_batch batch                                           ON batch.id                             = entity_batch.batch_id
      INNER JOIN civicrm_contribution contribution                             ON entity_batch.entity_table            = 'civicrm_contribution' AND entity_batch.entity_id = contribution.id
      INNER JOIN civicrm_contact      contact                                  ON contact.id                           = contribution.contact_id
      INNER JOIN civicrm_value_gift_aid_submission value_gift_aid_submission   ON value_gift_aid_submission.entity_id  = contribution.id
      WHERE batch.id = %1
EOD;
    $aQueryParam          = array( 1 => array( $pBatchId, 'Integer' ) );
    $oDao                 = CRM_Core_DAO::executeQuery( $cDonorSelect, $aQueryParam );
    $aDonors              = array();
    $bValidDonorData      = true;
    $aAddress['address']  = null;
    $aAddress['postcode'] = null;

    while ( $oDao->fetch() ) {
      $validationMsg = "";
      $bValidDonorData = self::isValidPersonName( $oDao->first_name ) && self::isValidPersonName( $oDao->last_name );
      if ( $bValidDonorData ) {
        $aAddress  = self::getDonorAddress( $oDao->contact_id
          , $oDao->contribution_id
          , date('YmdHis', strtotime( $oDao->created_date ) ) );
        // Need to clean up the postcode before we can submit it
        $formattedPostcode = self::postcodeFormat( $aAddress['postcode'] );

        $bValidAddress = !( empty( $aAddress['address'] ) ) && self::IsPostcode( $formattedPostcode ) ;
        if ( !$bValidAddress ) {
          $bValidDonorData = false;
          $validationMsg = "INVALID DONOR DETAILS : ADDRESS DATA ";
        }
        // Need to check if the amount is greater than 0.00 before submitting the batch
        $isValidAmount = $oDao->amount >= 0.01 ? true : false;
        if ( !$isValidAmount ) {
          $bValidDonorData = false;
          $validationMsg = "INVALID DONOR DETAILS : AMOUNT IS LESS THAN 0.01 ";
        }
      } else {
        $validationMsg = "INVALID DONOR DETAILS : FIRST NAME OR LAST NAME MISSING ";
      }

      // Need to find a way to let the submitter know if the contribution has been knocked off
      // Can then allow the user to fix
      // at the moment just stoppping invalid data from pushing through
      if ( $bValidDonorData ) {
        $aDonors[] = array( 'forename'        => $oDao->first_name
        , 'surname'         => $oDao->last_name
        , 'house_no'        => $aAddress['address']
        , 'postcode'        => $formattedPostcode
        , 'date'            => date('Y-m-d', strtotime( $oDao->created_date ) )
        , 'gift_aid_amount' => $oDao->amount
        );
      } else {
        $rejections[] = self::logBadDonorRecord( $oDao->batch_id
          , $oDao->batch_name
          , $oDao->created_date
          , $oDao->contribution_id
          , $oDao->contact_id
          , $oDao->first_name
          , $oDao->last_name
          , $oDao->amount
          , $oDao->gift_aid_amount
          , $aAddress['address']
          , $aAddress['postcode']
          , $validationMsg
        );
      }
    }

    foreach ( $aDonors as $d ) {
      $package->startElement( 'GAD' );
      $package->startElement( 'Donor' );
      $package->writeElement( 'Fore'    , $d['forename'] );
      $package->writeElement( 'Sur'     , $d['surname']  );
      $package->writeElement( 'House'   , $d['house_no'] );
      $package->writeElement( 'Postcode', $d['postcode'] );
      $package->endElement(); # Donor
      $package->writeElement( 'Date' , $d['date'] );
      $package->writeElement( 'Total', $d['gift_aid_amount'] );
      $package->endElement(); # GAD
    }
  }

  private function build_claim_xml( $pBatchId, &$package, &$rejections ) {
    $cClaimOrgName         = $this->_Settings['CLAIMER_ORG_NAME'];
    $cClaimOrgHmrcref      = $this->_Settings['CHAR_ID'];
    //    $cClaimOrgHmrcref      = $this->_Settings['CLAIMER_ORG_HMRC_REF'];
    $cRegulatorName        = $this->_Settings['CLAIMER_ORG_REGULATOR_NAME'];
    $cRegulatorNo          = $this->_Settings['CLAIMER_ORG_REGULATOR_NO'];
    $cConnectedCharities   = 'no';
    $cCommBldgs            = 'no';

    $package->startElement(     'Claim'                      );
    $package->writeElement(   'OrgName', $cClaimOrgName    );
    $package->writeElement(   'HMRCref', $cClaimOrgHmrcref );
    $package->startElement(   'Regulator'                  );
    $package->writeElement( 'RegName', $cRegulatorName   );
    $package->writeElement( 'RegNo'  , $cRegulatorNo     );
    $package->endElement(); # Regulator
    $package->startElement(   'Repayment'                  );
    $this->build_giftaid_donors_xml( $pBatchId, $package, $rejections );
    $package->writeElement( 'EarliestGAdate'  , '2012-01-01' );
    $package->endElement(); # Repayment
    $package->startElement(   'GASDS'                                      );
    $package->writeElement( 'ConnectedCharities'  , $cConnectedCharities );
    $package->writeElement( 'CommBldgs'           , $cCommBldgs          );
    $package->endElement(); # GASDS
    $package->endElement(); # Claim
  }

  public function giftAidSubmit( $pBatchId, &$rejections ) {
    $cChardId              = $this->_Settings['CHAR_ID'];
    //$cOrganisation         = 'HMRC';
    $cOrganisation         = 'IR';
    $cClientUri            = $this->_Settings['VENDOR_ID'];
    $cClientProduct        = 'VedaGiftAidSubmission';
    $cClientProductVersion = '1.6 Production'; // We should get this from the info.xml
    $dReturnPeriod         = $this->_Settings['PERIOD_END']; //'2013-03-31';
    $sDefaultCurrency      = 'GBP';
    $sSender               = $this->_Settings['SENDER_TYPE']; //'Individual';
    $cAuthOffSurname       = $this->_Settings['AUTH_OFF_SURNAME']; //'Smith';
    $cAuthOffForename      = $this->_Settings['AUTH_OFF_FORENAME']; //'John';
    $cAuthOffPhone         = $this->_Settings['AUTH_OFF_PHONE']; //'';
    $cAuthOffPostcode      = $this->_Settings['AUTH_OFF_POSTCODE']; //'AB12 3CD';
    $cDeclaration          = 'yes';

    // Set the message envelope
    $this->setMessageClass         ( 'HMRC-CHAR-CLM' );
    $this->setMessageQualifier     ( 'request'       );
    $this->setMessageFunction      ( 'submit'        );
    $this->setMessageTransformation( 'XML'           );
    $this->addTargetOrganisation   ( $cOrganisation  );


    $this->addMessageKey( 'CHARID'
      , $cChardId
    );
    $this->addChannelRoute( $cClientUri
      , $cClientProduct
      , $cClientProductVersion
    );
    $this->setIRmarkGeneration( true );
    // Build message body...
    $package = new XMLWriter();
    $package->openMemory();
    $package->setIndent( true );
    $package->startElement('IRenvelope');
    $package->writeAttribute('xmlns', 'http://www.govtalk.gov.uk/taxation/charities/r68/2');
    $package->startElement('IRheader');
    $package->startElement('Keys');
    $package->startElement('Key');
    $package->writeAttribute('Type', 'CHARID');
    $package->text( $cChardId );
    $package->endElement(); # Key
    $package->endElement(); # Keys
    $package->writeElement('PeriodEnd', $dReturnPeriod );
    $package->writeElement('DefaultCurrency', $sDefaultCurrency );
    if ($this->_generateIRmark === true) {
      $package->startElement('IRmark');
      $package->writeAttribute('Type', 'generic');
      $package->text('IRmark+Token');
      $package->endElement(); # IRmark
    }
    $package->writeElement('Sender', $sSender );
    $package->endElement(); #IRheader
    $package->startElement('R68');
    $package->startElement('AuthOfficial');
    $package->startElement('OffName');
    $package->writeElement( 'Fore', $cAuthOffForename );
    $package->writeElement( 'Sur' , $cAuthOffSurname  );
    $package->endElement(); #OffName
    $package->startElement('OffID');
    $package->writeElement( 'Postcode', $cAuthOffPostcode );
    $package->endElement(); #OffID
    $package->writeElement( 'Phone', $cAuthOffPhone );
    $package->endElement(); #AuthOfficial
    $package->writeElement( 'Declaration', $cDeclaration );
    $this->build_claim_xml( $pBatchId, $package, $rejections );
    $package->endElement(); #R68
    $package->endElement(); #IRenvelope

    // Send the message and deal with the response...
    $this->setMessageBody( $package );

    return $this->sendMessage();
  }

  public function declarationResponsePoll( $p_correlation_id = null, $p_poll_url = null ) {
    if ($p_correlation_id === null) {
      $sCorrelationId = $this->getResponseCorrelationId();
    } else {
      $sCorrelationId = $p_correlation_id;
    }

    if ( $p_poll_url !== null ) {
      $this->setGovTalkServer( $p_poll_url );
    } else {
      $aEndPoint  = $this->getResponseEndpoint();
      $sEndPoint  = $aEndPoint['endpoint'];
      $this->setGovTalkServer( $sEndPoint );
    }

    // Set the message envelope
    $this->setMessageClass         ( 'HMRC-CHAR-CLM' );
    $this->setMessageQualifier     ( 'poll'          );
    $this->setMessageFunction      ( 'submit'        );
    $this->setMessageCorrelationId (  $sCorrelationId );
    $this->setMessageTransformation( 'XML'           );
    $this->setMessageBody          ( '' );

    if ($this->sendMessage() && ($this->responseHasErrors() === false)) {
      return $this;
    } else {
      return $this;
      //      return false;
    }
  }

  /**
   * Polls the Gateway for a submission response / error following a VAT
   * declaration request. By default the correlation ID from the last response
   * is used for the polling, but this can be over-ridden by supplying a
   * correlation ID. The correlation ID can be skipped by passing a null value.
   *
   * If the resource is still pending this method will return the same array
   * as declarationRequest() -- 'endpoint', 'interval' and 'correlationid' --
   * if not then it'll return lots of useful information relating to the return
   * and payment of any VAT due in the following array format:
   *
   *  message => an array of messages ('Thank you for your submission', etc.).
   *  accept_time => the time the submission was accepted by the HMRC server.
   *  period => an array of information relating to the period of the return:
   *    id => the period ID.
   *    start => the start date of the period.
   *    end => the end date of the period.
   *  payment => an array of information relating to the payment of the return:
   *    narrative => a string representation of the payment (generated by HMRC)
   *    netvat => the net value due following this return.
   *    payment => an array of information relating to the method of payment:
   *      method => the method to be used to pay any money due, options are:
   *        - nilpayment: no payment is due.
   *        - repayment: a repayment from HMRC is due.
   *        - directdebit: payment will be taken by previous direct debit.
   *        - payment: payment should be made by alternative means.
   *      additional => additional information relating to this payment.
   *
   * @param string $correlationId The correlation ID of the resource to poll. Can be skipped with a null value.
   * @param string $pollUrl The URL of the Gateway to poll.
   * @return mixed An array of details relating to the return and payment, or false on failure.
   */
  public function XdeclarationResponsePoll($correlationId = null, $pollUrl = null) {

    if ($correlationId === null) {
      $correlationId = $this->getResponseCorrelationId();
    }

    if ($this->setMessageCorrelationId($correlationId)) {
      if ($pollUrl !== null) {
        $this->setGovTalkServer($pollUrl);
      }
      $this->setMessageClass( 'HMRC-CHAR-CLM' );
      $this->setMessageQualifier('poll');
      $this->setMessageFunction('submit');
      $this->setMessageTransformation( 'XML' );
      $this->resetMessageKeys();
      $this->setMessageBody('');
      if ($this->sendMessage() && ($this->responseHasErrors() === false)) {

        $messageQualifier = (string) $this->_fullResponseObject->Header->MessageDetails->Qualifier;
        if ($messageQualifier == 'response') {

          $successResponse = $this->_fullResponseObject->Body->SuccessResponse;

          if (isset($successResponse->IRmarkReceipt)) {
            $irMarkReceipt = (string) $successResponse->IRmarkReceipt->Message;
          }

          $responseMessage = array();
          foreach ($successResponse->Message AS $message) {
            $responseMessage[] = (string) $message;
          }
          $responseAcceptedTime = strtotime($successResponse->AcceptedTime);

          $declarationResponse = $successResponse->ResponseData->VATDeclarationResponse;
          $declarationPeriod = array('id' => (string) $declarationResponse->Header->VATPeriod->PeriodId,
            'start' => strtotime($declarationResponse->Header->VATPeriod->PeriodStartDate),
            'end' => strtotime($declarationResponse->Header->VATPeriod->PeriodEndDate));

          $paymentDueDate = strtotime($declarationResponse->Body->PaymentDueDate);

          $paymentDetails = array('narrative' => (string) $declarationResponse->Body->PaymentNotification->Narrative,
            'netvat' => (string) $declarationResponse->Body->PaymentNotification->NetVAT);

          $paymentNotifcation = $successResponse->ResponseData->VATDeclarationResponse->Body->PaymentNotification;
          if (isset($paymentNotifcation->NilPaymentIndicator)) {
            $paymentDetails['payment'] = array('method' => 'nilpayment', 'additional' => null);
          } else if (isset($paymentNotifcation->RepaymentIndicator)) {
            $paymentDetails['payment'] = array('method' => 'repayment', 'additional' => null);
          } else if (isset($paymentNotifcation->DirectDebitPaymentStatus)) {
            $paymentDetails['payment'] = array('method' => 'directdebit', 'additional' => strtotime($paymentNotifcation->DirectDebitPaymentStatus->CollectionDate));
          } else if (isset($paymentNotifcation->PaymentRequest)) {
            $paymentDetails['payment'] = array('method' => 'payment', 'additional' => (string) $paymentNotifcation->PaymentRequest->DirectDebitInstructionStatus);
          }

          return array('message' => $responseMessage,
            'irmark' => $irMarkReceipt,
            'accept_time' => $responseAcceptedTime,
            'period' => $declarationPeriod,
            'payment' => $paymentDetails);

        } else if ($messageQualifier == 'acknowledgement') {
          $returnable = $this->getResponseEndpoint();
          $returnable['correlationid'] = $this->getResponseCorrelationId();
          //					return $returnable;
        } else {
          //					return false;
        }
      } else {
        //				return false;
      }
    } else {
      //			return false;
    }
    return $this;
  }

  /* Protected methods. */

  /**
   * Adds a valid IRmark to the given package.
   *
   * This function over-rides the packageDigest() function provided in the main
   * php-govtalk class.
   *
   * @param string $package The package to add the IRmark to.
   * @return string The new package after addition of the IRmark.
   */
  protected function packageDigest( $package ) {

    if ($this->_generateIRmark === true) {
      $packageSimpleXML = simplexml_load_string( $package );
      $packageNamespaces = $packageSimpleXML->getNamespaces();

      /*Replaced by iMacdonald Patch
			preg_match('/<Body>(.*?)<\/Body>/', str_replace("\n", '¬', $package), $matches);
			$packageBody = str_replace('¬', "\n", $matches[1]);

       * Described as
       * That preg_match function will not match anything if $package contains
       * any UTF-8 characters such as accented characters. Thus, the 'u' modifier
       * to the regular expression is necessary to make preg_match UTF-8 compatible.
       * The str_replace functions are being used so that the preg_match that looks
       * for all the content between the body tags despite the presence of new lines.
       * The newlines are being replaced with '¬', then preg_match runs, then those
       * characters are being converted back to newline characters. It seems better to
       * just give the regular expression the 's' modifier, which will make the dot
       * character match all characters, including newlines. Then the substitutions
       * are is no longer necessary.
      */
      preg_match('/<Body>(.*)<\/Body>/su', $package, $matches);
      $packageBody = $matches[1];

      $irMark = base64_encode($this->_generateIRMark($packageBody, $packageNamespaces));
      $package = str_replace('IRmark+Token', $irMark, $package);
    }

    return $package;

  }

  /* Private methods. */

  function giftAidPoll( $p_endpoint, $p_correlation ) {
    //    $sOutcome = null;
    $pollResponse = $this->declarationResponsePoll( $p_correlation, $p_endpoint );

    //    if ( $pollResponse ) {
    //      if ( isset( $pollResponse['endpoint'] ) ) {
    //        $sOutcome = 'Response pending.  Please wait '.$pollResponse['interval'].' seconds and then refresh this page to try again.';
    //      } else {
    //        $sOutcome = 'Response received, delete command sent.  See below:';
    ////        var_dump($pollResponse);
    //        if ( $hmrcVat->sendDeleteRequest() ) {
    //          $sOutcome = 'Delete request successful. Resource no longer exists on Gateway.';
    //        } else {
    //          $sOutcome = 'Delete request failed. Resource may still exist on Gateway.';
    //        }
    //      }
    //    } else {
    //      return false;
    ////      $sOutcome = 'Government Gateway returned errors in response to poll request:';
    ////      var_dump($hmrcVat->getResponseErrors());
    //    }

    return $pollResponse;
  }
}
