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

class CRM_Giftaidonline_Form_giftAidSubmissionSettings extends CRM_Core_Form {

  public function buildQuickForm() {
    global $settings_id ;
    global $settings_name;
    if(isset($_GET['sid'])) {
      $settings_id    = $_GET['sid'];
    }
    if(isset($_GET['sname'])) {
      $settings_name  = $_GET['sname'];
    }
    $this->assign( 'settings_id'    ,   $settings_id    );
    $this->assign( 'settings_name'  ,   $settings_name  );
    $buttons = [
      [
        'type' => 'upload',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ];
    $this->addButtons($buttons);
  }

  public function postProcess() {
    $session = CRM_Core_Session::singleton();
    $buttonName = $this->controller->getButtonName();
    $value  =   $_POST['value'];
    $id     =   $_POST['id'];
    $name   =   $_POST['name'];
    if ($buttonName === $this->getButtonName('upload')) {
      CRM_Giftaidonline_Page_giftAidSubmissionSettings::update_gift_aid_submission_setting($id, $name, $value);
    }
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/gift-aid-submission-settings'));
  }
}
