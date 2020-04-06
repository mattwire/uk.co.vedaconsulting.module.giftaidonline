<div class="crm-block">
  <div class="crm-section">
  {if $task eq 'VIEW_BATCHES'}
    <table id="batch_table">
      <thead>
      <tr>
        {foreach from=$tableHeaders item=header}
          <th>{$header}</th>
        {/foreach}
      </tr>
      </thead>
      <tbody>
      {foreach from=$batches item=batch}
        <tr>
          <td>{$batch.batch_name}</td>
          <td>{$batch.created_date}</td>
          <td>{$batch.total_amount|crmMoney:$currency}</td>
          <td>{$batch.total_gift_aid_amount|crmMoney:$currency}</td>
          <td>{$batch.status}</td>
          <td>{$batch.action}</td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  {literal}
    <script type="text/javascript">
      cj(document).ready(function() {
        cj('#batch_table').dataTable( {
                  "aoColumns": [
                    { "sWidth": "30%" },
                    { "sWidth": "15%", "sType": "date" },
                    { "sWidth": "10%", "sType": "numeric" },
                    { "sWidth": "10%", "sType": "numeric" },
                    { "sWidth": "20%" },
                    { "sWidth": "15%" }
                  ],
                  "order": [[ 1, "desc" ]]
                }
        );

        cj('#submit_now a').click( function() {
          if (confirm('This action cannot be reversed. Are you sure?') ) {
            ;
          } else {
            return false;
          }
        });
        cj("#batch_table").on("click", ".errorLink", function() {
          displayMessage(cj(this).attr('id'), '#errorMessage_');
        });
        cj("#batch_table").on("click", ".responseLink", function() {
          displayMessage(cj(this).attr('id'), '#responseMessage_');
        });
      } );
      function displayMessage(linkId, responseId) {
        var linkArray = linkId.split('_');
        var linkId = linkArray[1];
        var messageDivHtml = cj(responseId + linkId).html();
        cj(messageDivHtml).dialog({width: '500px'});
      }
    </script>
  {/literal}
  {elseif $task eq 'SUBMITTED'}
    <form id="submission_frm">
      <div class="crm-block crm-form-block">
        <table id="submission_table">
          <thead>
          <tr>
            <th>Batch Name</th>
            <th>Date Created</th>
            <th>Submission Date</th>
            <th>Total</th>
            <th>Gift Aid Amount</th>
            <th>HMRC Response</th>
          </tr>
          </thead>
          <tbody>
          <tr>
            <td>{$submission.batch_name}</td>
            <td>{$submission.created_date}</td>
            <td>{$submission.submission_date}</td>
            <td>{$submission.total_amount|crmMoney:$currency}</td>
            <td>{$submission.total_gift_aid_amount|crmMoney:$currency}</td>
            <td>{$submission.hmrc_response}</td>
          </tr>
          </tbody>
        </table>
      </div>
    </form>
  {literal}
    <script type="text/javascript">
      cj(document).ready(function() {
        cj('#submission_table').dataTable( {
                  "aoColumns": [
                    { "sWidth": "30%" },
                    { "sWidth": "15%", "sType": "date" },
                    { "sWidth": "15%", "sType": "date" },
                    { "sWidth": "10%", "sType": "numeric" },
                    { "sWidth": "10%", "sType": "numeric" },
                    { "sWidth": "20%" }
                  ],
                  "order": [[ 1, "desc" ]]
                }
        );
      } );
    </script>
  {/literal}
  {elseif $task eq 'INVALID'}
    <h3>{$batchTitle} has {$rejectionCount} validation errors</h3>
    <div class="help">The batch has a number of validation errors. To submit to HMRC you will need to fix the errors and validate again.</div>
    <div>To view the validation errors: {$reportLink}</div>
    <div>To re-validate:
      <a href="{crmURL p='civicrm/giftaid/onlinesubmission' q="id=$batchID&validate=1"}" class="action-item">Validate</a>
    </div>
  {elseif $task eq 'VALID'}
    <h3>{$batchTitle} is ready for submission</h3>
    <div class="help">The batch is valid and ready for submission to HMRC</div>
    <div>
      To submit:
      <a href="{crmURL p='civicrm/giftaid/onlinesubmission' q="id=$batchID&submit=1"}" class="action-item">Submit</a>
    </div>
  {/if}
  </div>

  <div class="crm-section crm-giftaid-onlinesubmission-back">
    <a href="{crmURL p='civicrm/giftaid/onlinesubmission' q="reset=1"}" class="button">Back to list of submissions</a>
  </div>

</div>
