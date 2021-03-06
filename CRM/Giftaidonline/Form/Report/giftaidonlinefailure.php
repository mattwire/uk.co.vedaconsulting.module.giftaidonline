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

class CRM_Giftaidonline_Form_Report_giftaidonlinefailure extends CRM_Report_Form {

  protected $_addressField  = FALSE;

  protected $_emailField    = FALSE;

  protected $_summary       = NULL;

  protected $_customGroupExtends = ['Membership'];
  protected $_customGroupGroupBy = FALSE;

  /**
   * @var int
   */
  protected $batchID;

  public function __construct() {
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'contact_id' => [
            'name' => 'id',
            'title' => 'Contact ID',
            'required'    => TRUE,
          ],
          'sort_name' => [
            'title'       => ts('Contact Name'),
            'required'    => TRUE,
            'default'     => TRUE,
            'no_repeat'   => TRUE,
          ],
          'first_name' => [
            'title'       => ts('First Name'),
            'no_repeat'   => TRUE,
          ],
          'last_name' => [
            'title'       => ts('Last Name'),
            'no_repeat'   => TRUE,
          ],
        ],
      ],
      'civicrm_gift_aid_rejected_contributions' => [
        'fields' => [
          'rejection_reason' => [
            'name'        => 'rejection_reason',
            'title'       => 'Rejection reason',
            'required'    => TRUE,
            'default'     => TRUE,
            'no_repeat'   => TRUE,
          ],
          'rejection_detail' => [
            'name'        => 'rejection_detail',
            'title'       => 'Rejection detail',
            'required'    => FALSE,
            'default'     => TRUE,
            'no_repeat'   => TRUE,
          ],
          'batch_id' => [
            'name'     => 'batch_id',
            'title'    => 'Batch ID',
            'required' => TRUE,
            'default'  => TRUE,
            'no_repeat'=> TRUE,
          ],
        ],
        'filters'   => [
          'submission_id'    => [
            'title'           => 'Submission',
            'operatorType'    => CRM_Report_Form::OP_MULTISELECT,
            'options'         => CRM_Giftaidonline_Utils_Submission::getSubmissionIdTitle('id desc'),
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],
      ],
      'civicrm_batch' => [
        'dao'       => 'CRM_Batch_DAO_Batch',
        'filters'   => [
          'batch_id'    => [
            'title'           => 'Batch Name',
            'operatorType'    => CRM_Report_Form::OP_MULTISELECT,
            'options'         => CRM_Civigiftaid_Utils_Contribution::getBatchIdTitle( 'id desc' ),
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],
      ],
      'civicrm_contribution'  => [
        'dao'      => 'CRM_Contribute_DAO_Contribution',
        'fields'   => [
          'contribution_id'   => [
            'name'       => 'id',
            'title'      => 'Contribution ID',
            'no_display' => FALSE,
            'default'    => TRUE,
            'required' => TRUE,
          ],
          'total_amount'   => [
            'title'      => ts('Total Amount'),
            'default'    => TRUE,
            'no_display' => FALSE,
            'statistics' => [
              'sum'        => ts('Total Amount'),
            ],
          ],
          'actionlinks' => [
            'title' => ts('Actions'),
            'default' => TRUE,
            'required' => TRUE,
            'name' => 'id',
          ],
        ],
        'grouping' => 'contri-fields',
        'filters' => [
          'total_sum' => [
            'title'     => ts('Total Amount'),
            'type'      => CRM_Report_Form::OP_INT,
            'dbAlias'   => 'civicrm_contribution_total_amount_sum',
          ],
        ],
      ],
    ];
    parent::__construct();
  }

  public function preProcess() {
    $this->batchID = CRM_Utils_Request::retrieveValue('batch_id', 'Positive', NULL, FALSE, 'GET');
    if ($this->batchID) {
      $this->_force = 1;
    }
    $removeFromBatchContributionID = CRM_Utils_Request::retrieveValue('remove_contribution_id', 'Positive', NULL, FALSE, 'GET');
    if ($this->batchID && $removeFromBatchContributionID) {
      $removed = CRM_Giftaidonline_Batch::removeContributionFromBatch($this->batchID, $removeFromBatchContributionID);
      if (!$removed) {
        CRM_Core_Session::setStatus("Contribution ID {$removeFromBatchContributionID} is not in batch {$this->batchID}", 'Could not remove from batch', 'alert');
      }
      else {
        CRM_Core_Session::setStatus("Removed contribution ID {$removeFromBatchContributionID} from batch {$this->batchID}", 'Removed from batch', 'success');
      }
    }
    $this->assign('reportTitle', ts('Gift Aid Online Failure'));
    parent::preProcess();
  }

  public function select() {
    $select = $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            // only include statistics columns if set
            if ( CRM_Utils_Array::value('statistics', $field) ) {
              foreach ( $field['statistics'] as $stat => $label ) {
                switch (strtolower($stat)) {
                  case 'sum':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type']  = $field['type'];
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;
                }
              }
            } else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type']  = CRM_Utils_Array::value('type', $field);
            }
          }
        }
      }
    }
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  public function from() {
    $this->_from = NULL;
    $this->_from = "
           FROM  civicrm_contact {$this->_aliases['civicrm_contact']}{$this->_aclFrom}
                  LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                            ON {$this->_aliases['civicrm_contribution']}.contact_id =
                                {$this->_aliases['civicrm_contact']}.id
                  JOIN civicrm_gift_aid_rejected_contributions {$this->_aliases['civicrm_gift_aid_rejected_contributions']}
                            ON {$this->_aliases['civicrm_gift_aid_rejected_contributions']}.contribution_id =
                                {$this->_aliases['civicrm_contribution']}.id
                  JOIN civicrm_batch {$this->_aliases['civicrm_batch']}
                            ON {$this->_aliases['civicrm_batch']}.id =
                                {$this->_aliases['civicrm_gift_aid_rejected_contributions']}.batch_id ";
  }

  public function where() {
    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);
            $clause   = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field, $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }
          if(!empty($clause)){
            $clauses[] =  $clause;
          }
        }
      }
    }
    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }
    if ($this->batchID) {
      $this->_where = "WHERE {$this->_aliases['civicrm_batch']}.id IN ({$this->batchID})";
    }
    $submissionId = CRM_Utils_Request::retrieveValue('submissionId', 'Positive', NULL, FALSE, 'GET');
    if ($submissionId) {
      $this->_where = "WHERE {$this->_aliases['civicrm_gift_aid_rejected_contributions']}.submission_id IN ({$submissionId})";
    }
  }

  public function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id ";
  }

  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    $select     = "SELECT SUM( contribution_civireport.total_amount ) as amount";
    $sql        = "{$select} {$this->_from} {$this->_where}";
    $dao        = CRM_Core_DAO::executeQuery($sql);
    if ($dao->fetch()) {
      $statistics['counts']['amount'] = [
        'value' => $dao->amount,
        'title' => 'Total Amount',
        'type'  => CRM_Utils_Type::T_MONEY
      ];
    }
    return $statistics;
  }

  public function postProcess() {
    $this->beginPostProcess();
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);
    $rows = [];
    $this->buildRows($sql, $rows);
    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  /**
   * @param array $rows
   */
  public function alterDisplay(&$rows) {
    foreach ($rows as $rowNum => $row) {
      if (array_key_exists('civicrm_contact_sort_name', $row)) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link']  = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
      }
      if (array_key_exists('civicrm_contribution_contribution_id', $row)) {
        $url = CRM_Utils_System::url("civicrm/contact/view/contribution",
          "reset=1&cid={$row['civicrm_contact_contact_id']}&id={$row['civicrm_contribution_contribution_id']}&action=view&context=contribution",
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contribution_contribution_id_link']  = $url;
        $rows[$rowNum]['civicrm_contribution_contribution_id_hover'] = ts('View contribution');
      }
      if (array_key_exists('civicrm_contribution_actionlinks', $row)) {
        if ($this->batchID) {
          $urlQuery = [
            'force' => 1,
            'reset' => 1,
            'remove_contribution_id' => $rows[$rowNum]['civicrm_contribution_contribution_id'],
            'batch_id' => $this->batchID,
          ];
          $url = CRM_Utils_System::url("civicrm/report/instance/{$this->_id}", $urlQuery);
          $rows[$rowNum]['civicrm_contribution_actionlinks'] = "<a href='{$url}'>Remove from batch</a>";
        }
        else {
          $rows[$rowNum]['civicrm_contribution_actionlinks'] = '';
        }
      }
    }
  }

}
