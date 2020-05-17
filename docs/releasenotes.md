# Release Notes

## 1.8.5

* Fix creating "Gift Aid Submission failure" report on install and change name on upgrade.

## 1.8.4

* Remove support for retrieving address from contribution (this likely has not worked for a long time because the database fields didn't exist).

## 1.8.3

* Fix issues with SQL on install if tables already exist.

## 1.8.2

**Requires GiftAid extension 3.3.8 or newer**

* Moved some internal name/address formatting functions to the giftaid extension so they can be shared between this extension
and the reporting functions of the giftaid extension.

## 1.8.1

* Validate and filter donor first/last name before submitting to comply with data format restrictions.

## 1.8

* Fix postcode validation and support 'X' for postcode which can be entered for non UK addresses.
* Add navigation menu items
* Update onlinesubmission form URL
* Log/update validation errors if submitting or validating so they show in reports
* Display results of submitted for POLL task - it is not clear if POLL still works as it does not work with HMRC in dev mode.
* Fix [civicrm.giftaid#4](https://github.com/mattwire/uk.co.compucorp.civicrm.giftaid/issues/4) getting address from declaration when declaration is 'Yes and past 4 years'.
* Implement function to remove from batch and add link to rejection report (now removes from `civicrm_entity_batch`, contribution `batch_name` field and the rejection report).

##### Add a validate/submit workflow:

![validate form](docs/images/validateform.png)

  * When you have a not yet submitted batch it is no longer possible to submit without validating.
  * First you click on the validate link.
  * Then, once you've resolved the validation errors you can submit.

##### Rejection report

![rejection report](docs/images/rejectionreport.png)

* Fix name clashes in rejection report
* Log/update validation errors if submitting or validating so they show in reports
* Add rejection detail column to rejection report
* Add link to contribution on rejection report

## 1.7.0

Added a new column to the `civicrm_gift_aid_rejected_contributions` table, in order to report rejections based on Batch Id and Submission.

Suggest if your upgrading you add this in manually

ALTER TABLE `civicrm_gift_aid_rejected_contributions` ADD COLUMN `submission_id` int(10) unsigned AFTER `batch_id`;

## 1.6.2

Amended custom search to use contribution dates and not declaration dates as this is not as useful

## 1.6.1

Change permission who can submit the batches to HMRC

'allow giftaid submission' is added to permission list.

Make sure the role has the above permission in place in order to submit the batches to HMRC.

## 1.6

Introduced 2 new features

1. GiftAid Online Failure report - To view the rejection reason for the GiftAid Online Submission
2. GiftAid Contribution Custom search - To search for contributions which are not claimed and those with valid declaration.

If you are doing new installation, skip Step 1 and 2 and go to Step 3.

If you are upgrading, you must do all 3 steps.

Note: If you are upgrading from version prior to v1.5.2, run the ALTER table mentioned in the section above.

Step 1: Register report (Only for upgrade)
Title: giftaidonlinefailure (uk.co.vedaconsulting.module.giftaidonline)
Description: GiftAid Online Failure Report
Report URL: uk.co.vedaconsulting.module.giftaidonline/giftaidonlinefailure
Class Name: CRM_Giftaidonline_Form_Report_giftaidonlinefailure
Component: CiviContribute

Step 2: Register Custom Search (Only for upgrade)
Navigate to Administer >> Customize Data & Screens >> Manage Custom Searches
Click 'Add Custom Search'
Enter the below values in the form and click 'Save'
Custom Search Path: CRM_Giftaidonline_Form_Search_giftaidcontributionsearch
Search Title: giftaidcontributionsearch (uk.co.vedaconsulting.module.giftaidonline)

Step 3: Create report instance (New Install and Upgrade)
Navigate to Administer >> CiviReport >> Create New Report from Template
Click 'giftaidonlinefailure' under 'Contribution Report Templates'
Review the Display Columns section. Not to worry about the Filter, as batch ID will be passed to the report from Online Gift Aid Submission page
Click 'Preview Report' button
Under 'Create Report' section, give a report title and set the permissions and click 'Create Report' button. You dont need to include report in navigation menu, as the report link will be displayed in Online Gift Aid Submission page for each batch.

## 1.5.2

Introduced two new hooks

giftAidOnlineSubmitted( $batchID )
Used if you want to perform an action following successful submission i.e. update another table, send an email etc

invalidGiftAidOnlineContribution( $batchID, $contributionIDRemoved )
Used when contributions aren't submitted due to not meeting gift aid online rules i.e. invalid postcode formats or address lines etc

Also added a new column to the civicrm_gift_aid_submission table
Suggest if your upgrading you add this in manually

ALTER TABLE `civicrm_gift_aid_submission` ADD COLUMN `IRMark` varchar(255);

This column will hold the IR Mark of successfully submitted claims

There is also a new table that holds contributions that were classed as invalid pre-submission and this also needs to be created if your upgrading

The script is in scripts/sql/civi_gift_aid_rejected_contributions
