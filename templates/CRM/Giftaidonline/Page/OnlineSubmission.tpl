{if $task eq 'VIEW_BATCH'}
  <table id="batch_table">
    <thead>
    <tr>
      <th>Batch Name</th>
      <th>Date Created</th>
      <th>Total</th>
      <th>Gift Aid Amount</th>
      <th>Status</th>
      <th>Rejection Report</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$batches item=batch}
      <tr>
        <td>{$batch.batch_name}</td>
        <td>{$batch.created_date}</td>
        <td>{$batch.total_amount}</td>
        <td>{$batch.total_gift_aid_amount}</td>
        <td>{$batch.action}</td>
        <td>{$batch.report_link}</td>
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
                ]
              }
      );


      cj('#submit_now a').click( function() {
        if (confirm('This action cannot be reversed.  Are you sure?') ) {
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
{elseif $task eq 'POLLING'}
  <div>
    {$poll_message}
  </div>
{elseif $task eq 'VIEW_SUBMISSION'}
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
          <td>{$submission.submision_date}</td>
          <td>{$submission.total_amount}</td>
          <td>{$submission.total_gift_aid_amount}</td>
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
                ]
              }
      );
    } );
  </script>
{/literal}
{elseif $task eq 'VIEW_INVALID'}
  <table id="invalid_table">
    <thead>
    <tr>
    {foreach from=$tableHeaders item=header}
      <th>{$header}</th>
    {/foreach}
    </tr>
    </thead>
    <tbody>
    {foreach from=$submission item=rejectrow}
      <tr>
      {foreach from=$rejectrow item=field}
        <td>{$field}</td>
      {/foreach}
      </tr>
    {/foreach}
    </tbody>
  </table>
{literal}
  <script type="text/javascript">
    CRM.$(document).ready(function() {
      CRM.$('#batch_table').dataTable( {
                "aoColumns": [
                  { "sWidth": "30%" },
                  { "sWidth": "15%", "sType": "date" },
                  { "sWidth": "10%", "sType": "numeric" },
                  { "sWidth": "10%", "sType": "numeric" },
                  { "sWidth": "20%" },
                  { "sWidth": "15%" }
                ]
              }
      );
    });
  </script>
{/literal}
{/if}
