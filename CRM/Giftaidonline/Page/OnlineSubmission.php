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
require_once (E::path('govtalk/GovTalk.php'));
require_once (E::path('govtalk/HMRC.php'));
require_once (E::path('govtalk/HmrcGiftAid.php'));

class CRM_Giftaidonline_Page_OnlineSubmission extends CRM_Core_Page {

  private function _get_submission($p_batch_id) {
    $sSql = "
        SELECT id
        ,      batch_id
        ,      created_date
        ,      request_xml
        ,      response_xml
        ,      response_qualifier
        ,      response_errors
        ,      response_end_point
        ,      response_end_point_interval
        ,      response_correlation_id
        ,      transaction_id
        ,      gateway_timestamp
        FROM   civicrm_gift_aid_submission
        WHERE  batch_id = %1
        ORDER BY id DESC
";
    $aQueryParam = [
      1 => [$p_batch_id  , 'Integer']
    ];

    $oDao = CRM_Core_DAO::executeQuery($sSql, $aQueryParam);
    if ($oDao->fetch()) {
      $aSubmission['id']                          = $oDao->id;
      $aSubmission['batch_id']                    = $oDao->batch_id;
      $aSubmission['created_date']                = $oDao->created_date;
      $aSubmission['request_xml']                 = $oDao->request_xml;
      $aSubmission['response_xml']                = $oDao->response_xml;
      $aSubmission['response_qualifier']          = $oDao->response_qualifier;
      $aSubmission['response_errors']             = $oDao->response_errors;
      $aSubmission['response_end_point']          = $oDao->response_end_point;
      $aSubmission['response_end_point_interval'] = $oDao->response_end_point_interval;
      $aSubmission['response_correlation_id']     = $oDao->response_correlation_id;
      $aSubmission['transaction_id']              = $oDao->transaction_id;
      $aSubmission['gateway_timestamp']           = $oDao->gateway_timestamp;
    } else {
      $aSubmission = [];
    }

    return $aSubmission;
  }

  private function _get_polling_request($submission_id) {
    $pSql = "
        SELECT id
        ,      submission_id
        ,      created_date
        ,      request_xml
        ,      response_xml
        ,      response_qualifier
        ,      response_errors
        ,      response_end_point
        ,      response_end_point_interval
        ,      response_correlation_id
        ,      transaction_id
        ,      gateway_timestamp
        FROM   civicrm_gift_aid_polling_request
        WHERE  submission_id = %1
        ORDER BY id DESC
";
    $pQueryParam = [
      1 => [$submission_id  , 'Integer']
    ];

    $oDao = CRM_Core_DAO::executeQuery($pSql, $pQueryParam);
    if ($oDao->fetch()) {
      $pRequest['id']                          = $oDao->id;
      $pRequest['submission_id']               = $oDao->submission_id;
      $pRequest['created_date']                = $oDao->created_date;
      $pRequest['request_xml']                 = $oDao->request_xml;
      $pRequest['response_xml']                = $oDao->response_xml;
      $pRequest['response_qualifier']          = $oDao->response_qualifier;
      $pRequest['response_errors']             = $oDao->response_errors;
      $pRequest['response_end_point']          = $oDao->response_end_point;
      $pRequest['response_end_point_interval'] = $oDao->response_end_point_interval;
      $pRequest['response_correlation_id']     = $oDao->response_correlation_id;
      $pRequest['transaction_id']              = $oDao->transaction_id;
      $pRequest['gateway_timestamp']           = $oDao->gateway_timestamp;
    } else {
      $pRequest = [];
    }

    return $pRequest;
  }

  private function _record_submission(
    $p_batch_id
    , $p_request_xml
    , $p_response_xml
    , $p_response_qualifier
    , $p_response_errors
    , $p_response_end_point
    , $p_response_end_point_interval
    , $p_response_correlation_id
    , $p_transaction_id
    , $p_gateway_timestamp
  ) {
    $sSql = "
              INSERT INTO civicrm_gift_aid_submission(
                batch_id
              , request_xml
              , response_xml
              , response_qualifier
              , response_errors
              , response_end_point
              , response_end_point_interval
              , response_correlation_id
              , transaction_id
              , gateway_timestamp
              ) VALUES (
                %1
              , %2
              , %3
              , %4
              , %5
              , %6
              , %7
              , %8
              , %9
              , %10
              );
";
    $aQueryParam = [
      1   => [$p_batch_id, 'Integer'],
      2   => [!empty($p_request_xml) ? $p_request_xml : '', 'String'],
      3   => [!empty($p_response_xml) ? $p_response_xml : '', 'String'],
      4   => [!empty($p_response_qualifier) ? $p_response_qualifier : '', 'String'],
      5   => [!empty($p_response_errors) ? $p_response_errors : '', 'String'],
      6   => [!empty($p_response_end_point) ? $p_response_end_point : '', 'String'],
      7   => [!empty($p_response_end_point_interval) ? $p_response_end_point_interval : '', 'String'],
      8   => [!empty($p_response_correlation_id) ? $p_response_correlation_id : '', 'String'],
      9   => [!empty($p_transaction_id) ? $p_transaction_id : '', 'String'],
      10  => [!empty($p_gateway_timestamp) ? date( "Y-m-d H:i:s", $p_gateway_timestamp) : '', 'String'],
    ];

    $oDao = CRM_Core_DAO::executeQuery($sSql, $aQueryParam);
    if (is_a($oDao, 'DB_Error')) {
      Throw new CRM_Core_Exception('Trying to create a new Submission record failed.');
    }
    return CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
  }

  private function _record_polling (
    $p_submission_id
    , $p_request_xml
    , $p_response_xml
    , $p_response_qualifier
    , $p_response_errors
    , $p_response_end_point
    , $p_response_end_point_interval
    , $p_response_correlation_id
    , $p_transaction_id
    , $p_gateway_timestamp
  ) {
    $sSql = "
              INSERT INTO civicrm_gift_aid_polling_request (
                submission_id
              , request_xml
              , response_xml
              , response_qualifier
              , response_errors
              , response_end_point
              , response_end_point_interval
              , response_correlation_id
              , transaction_id
              , gateway_timestamp
              ) VALUES (
                %1
              , %2
              , %3
              , %4
              , %5
              , %6
              , %7
              , %8
              , %9
              , %10
              );
";
    $aQueryParam = [
      1   => [$p_submission_id, 'Integer'],
      2   => [!empty($p_request_xml) ? $p_request_xml : '', 'String'],
      3   => [!empty($p_response_xml) ? $p_response_xml : '', 'String'],
      4   => [!empty($p_response_qualifier) ? $p_response_qualifier : '', 'String'],
      5   => [!empty($p_response_errors) ? $p_response_errors : '', 'String'],
      6   => [!empty($p_response_end_point) ? $p_response_end_point : '', 'String'],
      7   => [!empty($p_response_end_point_interval) ? $p_response_end_point_interval : 0, 'Integer'],
      8   => [!empty($p_response_correlation_id) ? $p_response_correlation_id : '', 'String'],
      9   => [!empty($p_transaction_id) ? $p_transaction_id : '', 'String'],
      10  => [!empty($p_gateway_timestamp) ? date( "Y-m-d H:i:s", $p_gateway_timestamp) : '', 'String'],
    ];

    $oDao = CRM_Core_DAO::executeQuery($sSql, $aQueryParam);
    if ( is_a( $oDao, 'DB_Error' ) ) {
      Throw new CRM_Core_Exception('Trying to create a new Submission record failed.');
    }

    return NULL;
  }

  private function _get_batch_record_sql($batch_id = NULL) {
    $sWhere = empty($batch_id) ? NULL : ' AND batch.id = ' . $batch_id;
    $sQuery = "
      SELECT batch.id                                        AS batch_id
      ,     batch.title                                      AS batch_name
      ,     batch.created_date                               AS created_date
      ,     SUM( value_gift_aid_submission.amount )          AS total_amount
      ,     SUM( value_gift_aid_submission.gift_aid_amount ) AS total_gift_aid_amount
      FROM  civicrm_entity_batch entity_batch
      INNER JOIN civicrm_contribution contribution                           ON entity_batch.entity_table = 'civicrm_contribution' AND entity_batch.entity_id = contribution.id
      INNER JOIN civicrm_value_gift_aid_submission value_gift_aid_submission ON value_gift_aid_submission.entity_id = contribution.id
      INNER JOIN civicrm_batch batch                                         ON batch.id = entity_batch.batch_id
      {$sWhere}
      GROUP BY batch.id
      ,        batch.title
      ,        batch.created_date
      ORDER BY batch.created_date DESC;
";
    // Hook to modify the query for selection of batches (Useful to select only giftaid batches)
    if (empty($batch_id)) {
      CRM_Giftaidonline_Utils_Hook::modifySelectBatchesQuery($sQuery);
    }
    return $sQuery;
  }

  public function get_submission_status($pEndpoint, $pCorrelation) {
    if (isset($pEndpoint) && isset($pCorrelation)) {
      $oHmrcGiftAid = new HmrcGiftAid();
      $pollResponse = $oHmrcGiftAid->declarationResponsePoll($pCorrelation, $pEndpoint);
      if ($pollResponse) {
        if (isset($pollResponse['endpoint'])) {
          $sMessage = sprintf("Response pending.  Please wait %d seconds and then try again.", $pollResponse['interval']);
        } else {
          $sMessage = sprintf('Response received, delete command sent. See below:<br />%s' . print_r($pollResponse, TRUE));
          if ($oHmrcGiftAid->sendDeleteRequest()) {
            $sMessage .= 'Delete request successful. Resource no longer exists on Gateway.';
          } else {
            $sMessage .= 'Delete request failed. Resource may still exist on Gateway.';
          }
        }
      } else {
        $sMessage = sprintf('Government Gateway returned errors in response to poll request: <br />%s'
          , print_r($oHmrcGiftAid->getResponseErrors(), TRUE)
        );
      }

    } else {
      $sMessage = 'Unable to poll Government Gateway: missing arguments.';
    }

    return $sMessage;
  }

  /**
   * @param int $p_batch_id
   *
   * @return bool
   */
  public function is_submitted($p_batch_id) {
    $bIsSubmitted = NULL;
    $aSubmission = $this->_get_submission($p_batch_id);
    if (empty($aSubmission)) {
      $bIsSubmitted = FALSE;
    } else {
      $bIsSubmitted = empty($aSubmission['response_xml']) ? FALSE : TRUE;
    }

    return $bIsSubmitted;
  }

  public function allow_resubmission($p_batch_id, $pRequest) {
    $allowResubmission = FALSE;
    $aSubmission = $this->_get_submission($p_batch_id);
    if ($aSubmission['response_qualifier'] == 'error' && !empty($aSubmission['response_errors'])) {
      $allowResubmission = TRUE;
    }
    if (!empty($pRequest) && $pRequest['response_qualifier'] == 'error') {
      $allowResubmission = TRUE;
    }
    return $allowResubmission;
  }

  /**
   * @param int $p_batch_id
   * @param \HmrcGiftAid $p_hmrc_gift_aid
   *
   * @return array
   */
  private function _build_submission($p_batch_id, $p_hmrc_gift_aid) {
    $aSubmission = [];
    $sQuery = $this->_get_batch_record_sql($p_batch_id);
    $oBatchDao = CRM_Core_DAO::executeQuery($sQuery);
    if ($oBatchDao->fetch()) {
      $dSubmissionDate = date('Y-m-d H:i:s', $p_hmrc_gift_aid->getGatewayTimestamp());
      $sSuccessMessage = $p_hmrc_gift_aid->getResponseSuccessfullMessage();
      $sResponseStatus = NULL;
      if (!empty($sSuccessMessage)) {
        $sResponseStatus = sprintf("<div>%s</div>", $sSuccessMessage);
        // hook to carry out other actions on success submission
        CRM_Giftaidonline_Utils_Hook::giftAidOnlineSubmitted($p_batch_id);
      } else {
        $aEndPoint = $p_hmrc_gift_aid->getResponseEndpoint();
        $sEndPointInterval = isset($aEndPoint['interval']) ? $aEndPoint['interval'] : NULL;
        $sUrl = CRM_Utils_System::url( 'civicrm/giftaid/onlinesubmission', "id=$p_batch_id&task=POLL");
        $sRefreshLink = sprintf("<a href='%s'>Refresh</a>", $sUrl);
        $sResponseError = $this->_response_error_to_string($p_hmrc_gift_aid->getFullXMLResponse(), '<br /><br />');
        $responseStatus = empty($p_hmrc_gift_aid->getResponseQualifier()) ? 'Unknown' : $p_hmrc_gift_aid->getResponseQualifier();
        $sResponseStatus = sprintf("<div>Status: <strong>%s</strong></div><div>%s</div>", $responseStatus, $sResponseError);
        if (!empty($aEndPoint)) {
          $sResponseStatus .= sprintf( "<div>Please wait for %s seconds then click on the Refresh link to get an update of the submission.</div><div>[%s]</div>",
            $sEndPointInterval,
            $sRefreshLink
          );
        }
      }
      $aSubmission = [
        'batch_id'              => $p_batch_id
        , 'batch_name'            => $oBatchDao->batch_name
        , 'created_date'          => $oBatchDao->created_date
        , 'submision_date'        => $dSubmissionDate
        , 'total_amount'          => $oBatchDao->total_amount
        , 'total_gift_aid_amount' => $oBatchDao->total_gift_aid_amount
        , 'hmrc_response'         => $sResponseStatus
      ];
    }

    return $aSubmission;
  }

  private function _response_error_to_string($p_response_errors, $p_separator = "\n") {
    $aError = [];
    if (!empty($p_response_errors)) {
      $oXmlReader = new XMLReader();
      $oXmlReader->XML($p_response_errors);
      while ($oXmlReader->read()) {
        if ($oXmlReader->name === 'Error') {
          $aError[] = $oXmlReader->readString();
          $oXmlReader->next();
        }
      }
    }

    return implode($p_separator, $aError);
  }

  /**
   * @param int $p_batch_id
   * @param string $task
   * @param bool $isValidate Are we in validation mode - don't submit, just check the data
   *
   * @return array [ array submissions, array rejectionIDs ]
   * @throws \CRM_Core_Exception
   */
  public function process_batch($p_batch_id, $task = NULL, $isValidate = FALSE) {
    $oHmrcGiftAid = new HmrcGiftAid();
    $rejectionIDs = [];
    $submissionId = '';

    if ($task == 'POLL') {
      $aSubmission = $this->_get_submission($p_batch_id);
      if (empty($aSubmission)) {
        Throw new CRM_Core_Exception("Cannot locate Submission record for batch: {$p_batch_id}");
      }
      $sEndPoint    = $aSubmission['response_end_point'];
      $sCorrelation = $aSubmission['response_correlation_id'];
      $oHmrcGiftAid = $oHmrcGiftAid->giftAidPoll($sEndPoint, $sCorrelation);
      $aEndPoint         = $oHmrcGiftAid->getResponseEndpoint();
      if (!$aEndPoint) {
        CRM_Core_Session::setStatus('Could not get new status from HMRC.', E::SHORT_NAME);
      }
      else {
        $sEndPoint = isset($aEndPoint['endpoint']) ? $aEndPoint['endpoint'] : NULL;
        $sEndPointInterval = isset($aEndPoint['interval']) ? $aEndPoint['interval'] : NULL;
        $submissionId = $this->_record_polling(
          $aSubmission['id'],
          $oHmrcGiftAid->getFullXMLRequest(),
          $oHmrcGiftAid->getFullXMLResponse(),
          $oHmrcGiftAid->getResponseQualifier(),
          $this->_response_error_to_string($oHmrcGiftAid->getFullXMLResponse()),
          $sEndPoint,
          $sEndPointInterval,
          $oHmrcGiftAid->getResponseCorrelationId(),
          $oHmrcGiftAid->getTransactionId(),
          $oHmrcGiftAid->getGatewayTimestamp()
        );
      }
    }
    else {
      $oHmrcGiftAid->giftAidSubmit($p_batch_id, $rejectionIDs, $isValidate);

      // We're in validation mode? Don't record submission
      if ($isValidate) {
        return [NULL, $rejectionIDs];
      }

      if (!$this->is_submitted($p_batch_id) || ($task === 'RESUBMIT')) {
        $aEndPoint = $oHmrcGiftAid->getResponseEndpoint();
        $sEndPoint = isset($aEndPoint['endpoint']) ? $aEndPoint['endpoint'] : NULL;
        $sEndPointInterval = isset($aEndPoint['interval']) ? $aEndPoint['interval'] : NULL;
        $submissionId = $this->_record_submission($p_batch_id
          , $oHmrcGiftAid->getFullXMLRequest()
          , $oHmrcGiftAid->getFullXMLResponse()
          , $oHmrcGiftAid->getResponseQualifier()
          , $this->_response_error_to_string($oHmrcGiftAid->getFullXMLResponse())
          , $sEndPoint
          , $sEndPointInterval
          , $oHmrcGiftAid->getResponseCorrelationId()
          , $oHmrcGiftAid->getTransactionId()
          , $oHmrcGiftAid->getGatewayTimestamp()
        );
      }
    }

    // Update submission_id in rejected contributions table
    if (!empty($rejectionIDs) && !empty($submissionId)) {
      self::update_submission_id_for_rejections($rejectionIDs, $submissionId);
    }

    $aSubmission = $this->_build_submission($p_batch_id, $oHmrcGiftAid);
    return [$aSubmission, NULL];
  }

  /**
   * Function to update submission_id in civicrm_gift_aid_rejected_contributions table
   * in order to report rejections based on submission
   *
   * @param array $rejectionIDs
   * @param int $submissionId
   */
  public function update_submission_id_for_rejections($rejectionIDs, $submissionId) {
    $updateQuery = "UPDATE civicrm_gift_aid_rejected_contributions SET submission_id = %1 WHERE id IN (%2)";
    $updateParams = [
      '1' => [$submissionId, 'Integer'],
      '2' => [implode(',', $rejectionIDs), 'CommaSeparatedIntegers'],
    ];
    CRM_Core_DAO::executeQuery($updateQuery, $updateParams);
  }

  public function get_all_giftaid_batch() {
    $cQuery   = $this->_get_batch_record_sql();
    $oDao     = CRM_Core_DAO::executeQuery($cQuery);
    $aBatches = [];

    while ($oDao->fetch()) {
      $responseErrors = $responseMessage = $sQuerySt = $linkLabel = $sUrl = '';
      $cLink = '';
      if (!$this->is_submitted($oDao->batch_id)) {
        $statusText = 'Not submitted';
        $validateUrl = CRM_Utils_System::url( 'civicrm/giftaid/onlinesubmission', "id=$oDao->batch_id&validate=1");
        $cLink .= "<a href='{$validateUrl}'>" . E::ts('Validate') . "</a><br />";
      } else {
        $statusText = 'Submitted';
        $aSubmission = $this->_get_submission($oDao->batch_id);
        $pRequest = $this->_get_polling_request($aSubmission['id']);
        // Allow resubmission of the batch, if previously reported as 'error'
        if ($this->allow_resubmission($oDao->batch_id, $pRequest)) {
          $sQueryStr = "id=$oDao->batch_id&submit=1&task=RESUBMIT";
          $linkLabel = 'Re-Submit now';
          // Get submission response error
          if ($aSubmission['response_qualifier'] == 'error') {
            $responseErrors = $aSubmission['response_xml'];
          }

          // Get response error from polling, if available (as polling reponse is the latest)
          if (!empty($pRequest) && $pRequest['response_qualifier'] == 'error') {
            $responseErrors = $pRequest['response_xml'];
          }
        } else {
          // Submitted, not allowed to re-submit
          $sQueryStr = "id=$oDao->batch_id&task=POLL";
          $linkLabel = 'Get new status';
        }

        if (isset($pRequest['response_qualifier']) && $pRequest['response_qualifier'] == 'response') {
          $responseMessage = $pRequest['response_xml'];
        }
        else {
          $sUrl  = CRM_Utils_System::url( 'civicrm/giftaid/onlinesubmission', $sQueryStr);
          $cLink .= sprintf("<a href='%s' class='action-item'>{$linkLabel}</a><br />", $sUrl);
        }
      }

      if (!empty($responseErrors)) {
        $responseErrorObj = simplexml_load_string($responseErrors);
        $responseErrorMsg = "Raised By:<br />".$responseErrorObj->GovTalkDetails->GovTalkErrors->Error->RaisedBy;
        $responseErrorMsg .= "<br /><br />Number:<br />".$responseErrorObj->GovTalkDetails->GovTalkErrors->Error->Number;
        $responseErrorMsg .= "<br /><br />Type:<br />".$responseErrorObj->GovTalkDetails->GovTalkErrors->Error->Type;
        $responseErrorMsg .= "<br /><br />Error Text:<br />".$responseErrorObj->GovTalkDetails->GovTalkErrors->Error->Text;
        $rLink = sprintf( "<a style='cursor: pointer;' id='errorLink_%s' class='errorLink'>View Failure Message</a>
                        <div id='errorMessage_%s' style='display: none;'><div title='Failure Message'>%s</div></div>"
          , $oDao->batch_id
          , $oDao->batch_id
          , $responseErrorMsg
        );
        $cLink .= $rLink;
      }

      if (!empty($responseMessage)) {
        $responseObj = simplexml_load_string($responseMessage);
        $responseMsg = '';
        if (isset($responseObj->Body->SuccessResponse->IRmarkReceipt->Message)) {
          $responseMsg .= "Message:<br />".$responseObj->Body->SuccessResponse->IRmarkReceipt->Message;
        }
        $responseMsg .= "<br /><br />CorrelationID:<br />".$responseObj->Header->MessageDetails->CorrelationID;
        $responseMsg .= "<br /><br />GatewayTimestamp:<br />".$responseObj->Header->MessageDetails->GatewayTimestamp;
        $rLink = sprintf( "<a style='cursor: pointer;' id='responseLink_%s' class='responseLink'>View Response</a>
                        <div id='responseMessage_%s' style='display: none;'><div title='Response Message'>%s</div></div>"
          , $oDao->batch_id
          , $oDao->batch_id
          , $responseMsg
        );
        $cLink .= $rLink;
      }

      $aBatches[] = [
        'batch_id'              => $oDao->batch_id,
        'batch_name'            => $oDao->batch_name,
        'created_date'          => $oDao->created_date,
        'total_amount'          => $oDao->total_amount,
        'total_gift_aid_amount' => $oDao->total_gift_aid_amount,
        'status'                => $statusText,
        'action'                => $cLink . $this->getReportLink($oDao->batch_id)
      ];
    }

    return $aBatches;
  }

  /**
   * Get the HTML URL for the rejection report
   * @param int $batchID
   *
   * @return string
   */
  private function getReportLink($batchID) {
    if (!isset(\Civi::$statics[__CLASS__]['reportURL'])) {
      // Get report instance
      try {
        $result = civicrm_api3('ReportInstance', 'getsingle', ['report_id' => E::SHORT_NAME . '/giftaidonlinefailure']);
        if (!empty($result['id'])) {
          \Civi::$statics[__CLASS__]['reportURL'] = 'civicrm/report/instance/' . $result['id'];
        }
      } catch (Exception $e) {
        \Civi::$statics[__CLASS__]['reportURL'] = NULL;
      }
    }

    if (\Civi::$statics[__CLASS__]['reportURL']) {
      $rLink = CRM_Utils_System::url(\Civi::$statics[__CLASS__]['reportURL'], "batch_id={$batchID}&force=1&reset=1");
      $reportLink = sprintf("<a href='%s' target='_blank' class='action-item'>Rejection report</a>", $rLink);
    }
    return $reportLink ?? '';
  }

  public function run() {
    CRM_Utils_System::setTitle(ts('Online Submission'));
    $iBatchId = CRM_Utils_Request::retrieve('id', 'Positive');
    $task = CRM_Utils_Request::retrieve('task', 'String');
    $isValidate = CRM_Utils_Request::retrieveValue('validate', 'Boolean', FALSE);
    $isSubmit = CRM_Utils_Request::retrieveValue('submit', 'Boolean', FALSE);

    if (empty($iBatchId)) {
      $batches = $this->get_all_giftaid_batch();
      $tableHeaders = ['Batch Name', 'Date Created', 'Total', 'Gift Aid Amount', 'Status', 'Actions'];
      $this->assign('tableHeaders', $tableHeaders);
      $this->assign('batches', $batches);
      $this->assign('currency', 'GBP');
      $sTask = 'VIEW_BATCHES';
    }
    else {
      list($processed, $rejectionIDs) = $this->process_batch($iBatchId, $task, $isValidate);
      $this->assign('batchID', $iBatchId);
      $this->assign('batchTitle', civicrm_api3('Batch', 'getvalue', ['id' => $iBatchId, 'return' => 'title']));
      if ($isValidate && !empty($rejectionIDs)) {
        $sTask = 'INVALID';
        $this->assign('rejectionCount', count($rejectionIDs));
        $this->assign('reportLink', $this->getReportLink($iBatchId));
      }
      elseif ($isValidate) {
        $sTask = 'VALID';
      }
      elseif ($isSubmit || ($task === 'POLL')) {
        // We have now submitted the batch to HMRC - view the results
        $sTask = 'SUBMITTED';
        $this->assign('submission', $processed);
      }
    }
    $this->assign('task', $sTask);

    parent::run();
  }

}
