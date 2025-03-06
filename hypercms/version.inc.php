<?php
// current version
$mgmt_config['version'] = "Version 10.2.6";
?>

[hyperCMS:pagetracking infotype='meta' default='none']
[hyperCMS:tplinclude file='ServiceFunctions.inc.tpl']
<?php
// VMS E-Signature
// last updated: 2025-03-03
// Tarifversion: 10/2024

// example: https://autohaus.uniqa.at/portal/_Testtarifrechner/e-signatur.php?hash=QUVS60DHWAEXKXR5AG

error_reporting (E_ALL & ~E_NOTICE & ~E_STRICT);


// delete invalid files
$invalid_delete = false;

if ("%view%" == "publish")
{
  // clean input
  $_REQUEST = secure_parameter (@$_REQUEST);

  $action= @$_REQUEST['action'];
  $signaturehash = @$_REQUEST['hash'];
  $token = @$_REQUEST['token'];
  $step = @$_REQUEST['step'];
  $Ort_Beratung = @$_REQUEST['Ort_Beratung'];
  $Signatur_Beratung = @$_REQUEST['Signatur_Beratung'];

  // get hash of application and user role
  $result_sighash = getsignaturehash ($signaturehash);

  if (!empty ($result_sighash['hash'])) $hash = $result_sighash['hash'];
  else exit ("Hash ungültig");

  if (!empty ($result_sighash['role'])) $role = $result_sighash['role'];
  else exit ("Hash ungültig"); 

  // initialize session
  if (empty ($action)) $_SESSION['signature_status'] = Null;

  // initialize tipster
  $tipster = false;

  // get application data
  if (!empty ($hash))
  {
    // get and set signature status in session
    if (empty ($_SESSION['signature_status'])) $signature_status = $_SESSION['signature_status'] = getSignatureStatus ($hash);
    // get signature status from session
    else $signature_status = $_SESSION['signature_status'];

    // document data
    $appdata = getOfferData ($hash);

    // on error
    if (is_string ($appdata))
    {
      $show = $appdata;
    }
    else
    {
      // verkäufer / agent data if tipster
      $agentdata = getUserData ("", $appdata['user_id']);

      // get company data and set tipster
      if (!empty ($agentdata['company_id']))
      {
        $companydata = getCompanyData ($agentdata['company_id']);

        if (!empty ($companydata['tipster'])) $tipster = true;

        // company ID to be used for agent number of the supervisor
        $company_id = $agentdata['company_id'];
      }

      // betreuer / supervisor data
      $supervisordata = getUserData ("", $appdata['autohausbetreuer']);

      // define document file name
      $filename = $appdata['nachname']." ".$appdata['vorname']." - UNIQA-Antrag - ".date("Y-m-d - H-i-s").".pdf";
    }

    // use "autohausbetreuer" as the agent for tipsters
    if ($tipster == true)
    {
      $agentdata = $supervisordata;

      if (!empty ($agentdata['email'])) $email_agent = $agentdata['email'];
      else $email_agent = "";
    }
    // if no tipster
    else
    {
      if (!empty ($agentdata['email'])) $email_agent = $agentdata['email'];
      else $email_agent = "";
    }

    if (!empty ($appdata['email']))  $email_client = $appdata['email'];
    else $email_client = "";
  }

  // verify document and create viewer link
  // document has been signed already
  if (@$signature_status == 2) 
  {
      $show = "Das Dokument wurde bereits unterschrieben"; 
  }
  // check document
  elseif (!empty ($hash) && is_file ($DIR_apps.$hash.".pdf")) 
  {
    // document link (status=1 ... unsigned document, status=2 ... signed document)
    $doc_link = $URL_host."/download.php?media=".$hash.".pdf&status=1&type=inline&name=KFZ-Antrag.pdf";
    $doc_signed_link = $URL_host."/download.php?media=".$hash.".pdf&status=2&type=inline&name=".urlencode($filename);

    // viewer link 
    $viewer_link = $URL_host."/pdfpreview/web/viewer.html?file=".urlencode($doc_link);
  }
  else $show = "Es ist ein Fehler beim Erstellen des Dokuments aufgetreten";

  // save signatures
  if (empty ($show) && $action == "sign" && !empty ($hash) && checkSecurityToken (@$_REQUEST['token']))
  {
    $step = 3;

    // insert place and signatures in document
    if ($signature_status <= 0 && $role == "agent" && is_file ($DIR_apps.$hash.".appl.pdf"))
    {
      // insert city, data and signatures of agent
      // prepare signature
      list ($type, $imgdata) = explode (',', $Signatur_Beratung);
      $temp = base64_decode ($imgdata);
      file_put_contents ($DIR_temp."sig-".$hash.".png", $temp);
            
      $image = array("image"=>$DIR_temp."sig-".$hash.".png", "left"=>100, "bottom"=>45, "resize"=>"195x32");

      $text = array();
      $text[0] = array("text"=>$Ort_Beratung.", ".date("d.m.Y"), "left"=>60, "bottom"=>320);

      $pdf = insertimage2pdf ($DIR_apps.$hash".appl.pdf", $DIR_temp.$hash".pdf", $image, $text, 3, "A4", 144, 12);

      // send e-mail with final application document to agent and client
      if (!empty ($pdf) && is_file ($DIR_apps.$hash.".pdf"))
      {
        // set new status
        file_put_contents ($DIR_temp.$hash.".status", "2");

        // load application document
        $appdoc = file_get_contents ($DIR_apps.$hash.".pdf");

        // to agent
        if (!empty ($email_agent))
        {
          $message = "";

          if (@$agentdata['gender'] == "frau") $message .= "Sehr geehrte Frau ".$agentdata['lastname'].",\n\n";
          else $message .= "Sehr geehrter Herr ".@$agentdata['lastname'].",\n\n";

          $message .= "Ihr Kunde ".@$appdata['vorname']." ".@$appdata['nachname']." hat den KFZ-Antrag unterschrieben, Sie finden diesen im Anhang.";
          $message .= "\n\nFreundliche Grüße von Ihrem UNIQA Team";

          // add "Betreuer" from the "Provisionschleife" in CC if the e-mail address is different
          if ($provisiondata[0]['agent_email'] != $email_agent) $email_cc = $provisiondata[0]['agent_email'];

          $mail_result = sendMail ("", $email_agent, $email_cc, "", "Der UNIQA KFZ-Antrag wurde unterschrieben", $message, $filename, $appdoc, "Provisionsdatenblatt.pdf", $provdoc);

          if ($mail_result == false) $show = "Es trat ein Fehler beim Versenden der E-Mail Nachricht an Ihren Betreuer auf";
        }

        // only Marketingeinwilligung to "Betreuer"
        if (!empty ($Einwilligung_Postfach) && !empty ($provisiondata[0]['agent_email']))
        {
            $message = "";

            if (@$provisiondata[0]['agent_gender'] == "frau") $message .= "Sehr geehrte Frau ".$provisiondata[0]['agent_lastname'].",\n\n";
            else $message .= "Sehr geehrter Herr ".@$provisiondata[0]['agent_lastname'].",\n\n";

            $message .= "der Kunde ".@$appdata['vorname']." ".@$appdata['nachname']." hat die Einwilligung für die Nutzung des elektronisches Postfaches erteilt, bitte setzen Sie die notwendigen Schritte.";
            $message .= "\n\nFreundliche Grüße von Ihrem UNIQA Team";

            $mail_result = sendMail ("", $provisiondata[0]['agent_email'], "", "", "Anlage eines neuen elektronischen Postfaches bei UNIQA", $message, "UNIQA-Marketingeinwilligung.pdf", $marketingdoc);

            if ($mail_result == false) $show = "Es trat ein Fehler beim Versenden der E-Mail Nachricht an Ihren Betreuer auf";
        }

        // to client
        if (!empty ($email_client))
        {
            $message = "";

            if (@$appdata['vn'] == "mann") $message .= "Sehr geehrter Herr ".@$appdata['vorname']." ".@$appdata['nachname'].",\n\n";
            elseif (@$appdata['vn'] == "frau") $message .= "Sehr geehrte Frau ".@$appdata['vorname']." ".@$appdata['nachname'].",\n\n";
            else $message .= "Sehr geehrte Firma ".@$appdata['nachname'].",\n\n";

            $message .= "Vielen Dank für Ihre Unterschrift, Sie finden Ihren UNIQA KFZ-Antrag im Anhang.\n\n";
            $message .= "Freundliche Grüße von Ihrem UNIQA Team";

            $mail_result = sendMail ("", $email_client, "", "", "Ihr UNIQA KFZ-Antrag", $message, $filename, $appdoc);

            if ($mail_result == false) $show = "Es trat ein Fehler beim Versenden der E-Mail Nachricht an Ihren Betreuer auf";
        }
      }
      else $show = "Es trat ein Fehler beim Erstellen des Dokuments auf";

      // new version of log entry
      savetolog ($appdata['offer_id'].";".date("Y-m-d H:i:s").";\"".$appdata['nachname']." ".$appdata['vorname']."\";\"".$appdata['zulassungsbezirk']."-".$appdata['kennzeichen']."\";".$appdata['versicherungsbeginn'].";\"".$email_data['email_client']."\";\"".$email_data['email_agent']."\";".$provisiondata[0]['agent_number'].";".$provisiondata[1]['agent_number'].";ja", "e-signature");

        // remove temp files
        if (is_file ($DIR_temp.$hash.".start")) unlink ($DIR_temp.$hash.".start");
        if (is_file ($DIR_temp.$hash.".status")) unlink ($DIR_temp.$hash.".status");
        if ($pdf && is_file ($DIR_apps.$hash.".appl.pdf")) unlink ($DIR_apps.$hash.".appl.pdf");
      }
      else $show = "Es trat ein Fehler beim Laden der Daten für die E-Mail Nachricht an Ihren Kunden auf";
    }
  }

  // create security token for forms and links
  $token_new = createSecurityToken();

  // default step value
  if (empty ($step)) $step = 1;

  // on error
  if (!empty ($show)) $step = "ERROR";
}
else
{
  $token_new = "";
  $step = 1;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<title>UNIQA PartnerPortal</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<!--  mobile Chrome, Safari, FireFox, Opera Mobile  -->
<meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=no, target-densitydpi=device-dpi">
<meta charset="utf-8" />
<meta name="generator" content="hyper Content & Digital Asset Management Server - hypercms.com" />
<meta name="Author" content="UNIQA" />
<meta name="description" content="UNIQA PartnerPortal für KFZ Händler " />
<meta name="keywords" content="UNIQA" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
<link hypercms_href="/repository/media_tpl/vms/style.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="/repository/media_tpl/vms/jquery.js"></script>
<script type="text/javascript" src="/repository/media_tpl/vms/signature/jSignature.min.js"></script>
<!--[if lt IE 9]>
<script type="text/javascript" src="/repository/media_tpl/vms/signature/flashcanvas.js"></script>
<![endif]-->
<style>
html, body, input, textarea, select, button, a, .text 
{
  font-family: Arial, Helvetica, sans-serif;
  font-size: 18px;
  font-weight: normal;
}
</style>
<script>
var signature_id = "";

function setPage (doclink, number)
{
  if (document.getElementById('viewer'))
  {
    <?php if (!empty ($viewer_link)) { ?>
    document.getElementById('viewer').style.display = 'block';
    var doclink = doclink + "#page=" + parseInt(number);
    document.getElementById('document').src = doclink;
    <?php } else { ?>
    alert ("Das Dokument ist nicht vorhanden");
    <?php } ?>
  }
}

function closePage ()
{
  if (document.getElementById('viewer'))
  {
    document.getElementById('viewer').style.display = 'none';
  }
}

function signDocument (id, step)
{
  if (document.getElementById('signdocument'))
  {
    signature_id = id;

    // set title
    var title = '';
    if (id == 'Beratung') title = 'Beratungsprotokoll';

    document.getElementById('sign_title').innerHTML=title;

    if (step == 0)
    {
      document.getElementById('sign_step0').style.display='block';
      document.getElementById('sign_step1').style.display='none';
      document.getElementById('sign_step2').style.display='none';
    }
    else if (step == 1)
    {
      document.getElementById('sign_step0').style.display='none';
      document.getElementById('sign_step1').style.display='block';
      document.getElementById('sign_step2').style.display='none';
    }
    else if (step == 2)
    {
      if (document.getElementById('ort').value == '')
      {
        alert ('Die Ortsangabe ist erforderlich');
        return false;
      }

      document.getElementById('sign_step0').style.display='none';
      document.getElementById('sign_step1').style.display='none';
      document.getElementById('sign_step2').style.display='block';

      // resize signature in order to work due to display:none when loaded
      $("#signature").resize();
      resetSignature();
    }

    document.getElementById('signdocument').style.display = 'block';
    return true;
  }
}

function closeSignature ()
{
  if (document.getElementById('signdocument'))
  {
    document.getElementById('signdocument').style.display = 'none';
  }
}

function resetSignature ()
{
  // clears the canvas and rerenders the decor on it.
  $("#signature").jSignature("reset");

  // empty hidden field
  if (signature_id != '') $('#Signatur_'+signature_id).val('');

  return false;
}

function checkSignatures ()
{
  if (document.getElementById('button_signed'))
  {
  <?php if (@$signature_status <= 0 && $role == "agent") { ?>

    <?php if ($tipster == false) { ?>
    //  verify signature of agent
    if (document.getElementById('sign_3'))
    {
      if (document.getElementById('Ort_Beratung').value != '' && document.getElementById('Signatur_Beratung').value != '')
      {
        document.getElementById('sign_3').style.backgroundColor = '#bbfabc';
        document.getElementById('check_3').style.display = 'inline';
      }
      else
      {
        document.getElementById('sign_3').style.backgroundColor = '';
        document.getElementById('check_3').style.display = 'none';
      }
    }
    <?php } ?>

    // unlock button
    if (<?php if ($tipster == false) { ?>document.getElementById('check_3').style.display == 'inline' && <?php } ?> 1==1 <?php if (requireInspection ($appdata) == true) { ?> && document.getElementById('check_4').style.display == 'inline'<?php } ?>) document.getElementById('button_signed').disabled = false;
    else document.getElementById('button_signed').disabled = true;

  <?php } elseif (@$signature_status == 1 && $role == "client") { ?>

    // verify all required signatures of client

    <?php if ($appdata['zahlungsmethode'] != "zahlschein") { ?>
    if (document.getElementById('sign_1'))
    {
      if (document.getElementById('Ort_SEPA').value != '' && document.getElementById('Signatur_SEPA').value != '')
      {
        document.getElementById('sign_1').style.backgroundColor = '#bbfabc';
        document.getElementById('check_1').style.display = 'inline';
      }
      else
      {
        document.getElementById('sign_1').style.backgroundColor = '';
        document.getElementById('check_1').style.display = 'none';
      }
    }
    <?php } ?>

    if (document.getElementById('sign_2') && document.getElementById('Ort_Antrag').value != '' && document.getElementById('Signatur_Antrag').value != '')
    {
      document.getElementById('sign_2').style.backgroundColor = '#bbfabc';
      document.getElementById('check_2').style.display = 'inline';
    }
    else if (document.getElementById('sign_2'))
    {
      document.getElementById('sign_2').style.backgroundColor = '';
      document.getElementById('check_2').style.display = 'none';
    }

    <?php if ($tipster == false) { ?>
    if (document.getElementById('sign_3') && document.getElementById('Ort_Beratung') && document.getElementById('Signatur_Beratung'))
    {
      if (document.getElementById('Ort_Beratung').value != '' && document.getElementById('Signatur_Beratung').value != '')
      {
        document.getElementById('sign_3').style.backgroundColor = '#bbfabc';
        document.getElementById('check_3').style.display = 'inline';
      }
      else
      {
        document.getElementById('sign_3').style.backgroundColor = '';
        document.getElementById('check_3').style.display = 'none';
      }
    }
    <?php } ?>

    if (document.getElementById('sign_0') && document.getElementById('Ort_Marketing') && document.getElementById('Signatur_Marketing'))
    {
      if (document.getElementById('Ort_Marketing').value != '' && document.getElementById('Signatur_Marketing').value != '')
      {
        document.getElementById('sign_0').style.backgroundColor = '#bbfabc';
        document.getElementById('check_0').style.display = 'inline';
      }
      else
      {
        document.getElementById('sign_0').style.backgroundColor = '';
        document.getElementById('check_0').style.display = 'none';
      }
    }

    <?php if (requireInspection ($appdata) == true) { ?>
    if (document.getElementById('sign_4') && document.getElementById('Ort_Besichtigung') && document.getElementById('Signatur_Besichtigung'))
    {
      if (document.getElementById('Ort_Besichtigung').value != '' && document.getElementById('Signatur_Besichtigung').value != '')
      {
        document.getElementById('sign_4').style.backgroundColor = '#bbfabc';
        document.getElementById('check_4').style.display = 'inline';
      }
      else
      {
        document.getElementById('sign_4').style.backgroundColor = '';
        document.getElementById('check_4').style.display = 'none';
      }
    }
    <?php } ?>

    // unlock button
    if (<?php if ($appdata['zahlungsmethode'] != "zahlschein") { ?>document.getElementById('check_1').style.display == 'inline' && <?php } ?>document.getElementById('check_2').style.display == 'inline' <?php if ($tipster == false) { ?> && document.getElementById('check_3').style.display == 'inline'<?php } ?><?php if (requireInspection ($appdata) == true) { ?> && document.getElementById('check_4').style.display == 'inline'<?php } ?>) document.getElementById('button_signed').disabled = false;
    else document.getElementById('button_signed').disabled = true;

  <?php } ?>
  }
}

function saveSignature ()
{
  if ($('#signature').jSignature('getData', 'native').length > 0 && document.getElementById('ort').value != '' && signature_id != '')
  {
    // image = PNG, svgbase64 = SVG
    var imagedata= $("#signature").jSignature("getData", "image");

    // set image data string
    $('#Signatur_'+signature_id).val(imagedata);

    // set e-post consent
    if (document.getElementById('postfacheinwilligung') && document.getElementById('postfacheinwilligung').checked==true)  document.getElementById('Einwilligung_Postfach').value = 1;

    // set marketing consent
    if (document.getElementById('marketingeinwilligung') && document.getElementById('marketingeinwilligung').checked==true)  document.getElementById('Einwilligung_Marketing').value = 1;

    // set city
    document.getElementById('Ort_'+signature_id).value = document.getElementById('ort').value;

    // check signatures
    checkSignatures();

    closeSignature();
  }
  else alert ("Die Ortsangabe oder Ihre Unterschrift fehlt"); 
}

function createSignedDocument ()
{
  // check signatures
  checkSignatures();

  if (document.getElementById('button_signed').disabled == false)
  {
    document.getElementById('form_signatures').submit();
  }
}

function downloadDocument ()
{
<?php if (@$signature_status >= 1 && !empty ($doc_signed_link)) { ?>
  var a = document.createElement('A');
  a.href = '<?php echo $doc_signed_link; ?>';
  console.log('Download-URL: <?php echo $doc_signed_link; ?>');
  a.download = '<?php echo $filename; ?>';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
<?php } else { ?>
  alert ('Fehler: Der Download des Dokumentes ist nicht verfügbar');
<?php } ?>
}

$(document).ready(function() {
  // Options 
  var options = {'lineWidth': 3, 'decor-color': 'transparent' };

  // inits the jSignature widget
  $("#signature").jSignature(options);

  // ignore enter and submit via keyboard
  $(document).keypress(
    function(event){
      if (event.which == '13')
      {
        event.preventDefault();
      }
  });
})

</script>
</head>

<body class="formbody">


<?php if ($step == "ERROR") { ?>
<!-- Error Message -->
<div class="signatureheader">
  <img src="/repository/media_tpl/vms/uniqa/UNIQA_Logo.svg" style="height:44px; border:0; margin:0; float:left;" alt="UNIQA" />
  <span class="headline" style="float:right;">Unterzeichnung Ihres KFZ-Antrags</span>
</div>
<div class="signatureoverlay" style="display:block; background-color:#ff9999; padding:12px; font-weight:bold;"><?php echo $show; ?></div>
<div class="signaturefooter"></div>
<?php } ?>


<!-- PDF Viewer -->
<div id="viewer" class="signatureoverlay">
  <iframe id="document" src="" style="width:100%; height:100%; border:0;"></iframe>
  <div class="signaturefooter">
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="closePage();">Weiter</button>
  </div>
</div>


<!-- Signature -->
<div id="signdocument" class="signatureoverlay" style="padding:8px; bottom:0; overflow:auto;">
  <form id="form_signatures" method="post">
    <input name="action" type="hidden" value="sign" />
    <input name="hash" type="hidden" value="<?php echo @$signaturehash; ?>" />
    <input name="token" type="hidden" value="<?php echo @$token_new; ?>" />
    <input id="Ort_Beratung" name="Ort_Beratung" type="hidden" value="" />
    <input id="Signatur_Beratung" name="Signatur_Beratung" type="hidden" value="" />

    <div id="sign_title" style="display:block; font-weight:bold; margin-bottom:8px;"></div>

    <! -- Marketing Consent -->
    <div id="sign_step0" style="display:none;">
      <label style="all:unset;"><input type="checkbox" id="postfacheinwilligung" value="1" onclick="if (this.checked==true) document.getElementById('button_weiter').disabled=false; else if (document.getElementById('marketingeinwilligung'),checked==false) document.getElementById('button_weiter').disabled=true;" /> Sie sind damit einverstanden, dass Sie Ihre Versicherungsunterlagen in Ihr elektronisches Postfach bekommen.</label>
      <br/>
      <label style="all:unset;"><input type="checkbox" id="marketingeinwilligung" value="1" onclick="if (this.checked==true) document.getElementById('button_weiter').disabled=false; else if (document.getElementById('postfacheinwilligung'),checked==false) document.getElementById('button_weiter').disabled=true;" /> Sie willigen ein, dass UNIQA Ihre persönlichen Daten zur elektronischen und telefonischen Kontaktaufnahme
für werbliche Zwecke verarbeitet. Sie bekommen z.B. individuelle Angebote, Zufriedenheitsbefragungen oder Infos über Aktionen und Gewinnspiele.</label>
      <br/><br/>
      <span style="font-size:24px;">ⓘ</span> Bitte aktivieren Sie eine Checkbox, um mit der Einwilligung fortzufahren. Wählen Sie "Zurück", wenn Sie die Einwilligung nicht geben möchten. 
      <br/>
      <hr/>
      <table style="width:100%;" style="display:none;">
        <tr>
          <td style="width:50%;"><button type="button" style="width:100%;" onclick="closeSignature();">Zurück</button></td>
          <td><button id="button_weiter" type="button" style="width:100%;" onclick="signDocument (signature_id, 1);" disabled>Weiter</button></td>
        </tr>
      </table>
    </div>

    <! -- City -->
    <div id="sign_step1" style="display:block;">
      Ort<br/>
      <input type="text" id="ort" value="" style="width:100%;" /><br/>
      <br/>
      <hr/>
      <span style="font-size:24px;">ⓘ</span> Bitte drehen Sie nun bei Bedarf Ihr Gerät für die Unterschrift in das Querformat.<br/>
      </br>
      <table style="width:100%;" style="display:none;">
        <tr>
          <td style="width:50%;"><button type="button" style="width:100%;" onclick="closeSignature();">Zurück</button></td>
          <td><button type="button" style="width:100%;" onclick="signDocument (signature_id, 2);">Weiter</button></td>
        </tr>
      </table>
    </div>

    <! -- Signature -->
    <div id="sign_step2" style="display:none;">
      <div style="position:absolute; top:40px; left:calc(50% - 60px); z-index:30; font-size:16px;">Hier unterschreiben</div>
      <div id="signature" style="width:96%;"></div>
      <div style="margin:8px auto;">
        <table style="width:96%;">
          <tr>
            <td style="width:33.3%;"><button type="button" style="width:100%;" onclick="closeSignature();">Zurück</button></td>
            <td style="width:33.3%;"><button type="button" style="width:100%;" onclick="resetSignature();">Löschen</button></td>
            <td><button type="button" style="width:100%;" onclick="saveSignature();">Speichern</button></td>
          </tr>
        </table>
      </div>
    </div>

  </form>
</div>


<?php if ($step == 1) { ?>
<!-- Step 1 -->
<div class="signatureheader">
  <img src="/repository/media_tpl/vms/uniqa/UNIQA_Logo.svg" style="height:44px; border:0; margin:0; float:left;" alt="UNIQA" />
  <span class="headline" style="float:right;">Unterzeichnung Ihres KFZ-Antrags</span>
</div>

<div class="signaturecontent">

<?php if (@$signature_status <= 0 && $role == "agent") { 
  $temp = array();
  if ($tipster == false) $temp[] = "Beratungsprotokoll";
  if (requireInspection ($appdata) == true) $temp[] = "Besichtigungsformular";
  $temp_str = implode (" und ", $temp);
?>
  <?php if (@$agentdata['gender'] == "mann") echo "Sehr geehrter Herr "; else echo "Sehr geehrte Frau "; echo @$agentdata['firstname']." ".@$agentdata['lastname']; ?>,<br/>
  <br/>
  bitte unterschreiben Sie das <?php echo $temp_str; ?> Ihres Kunden <?php echo @$appdata['vorname']." ".@$appdata['nachname']; ?>, damit dieser im nächsten Schritt den KFZ-Antrag unterzeichnen kann.<br/>
  <br/>
  Sie können sich das Dokument ansehen und herunterladen.<br/>
  Jetzt einfach direkt auf Ihrem Smartphone unterschreiben und damit vollinhaltlich zustimmen.<br/>
  <br/>
<?php } elseif ($role == "client") { ?>
  <?php if (@$appdata['vn'] == "mann") echo "Sehr geehrter Herr "; elseif (@$appdata['vn'] == "frau") echo "Sehr geehrte Frau "; else echo "Sehr geehrte Firma "; echo @$appdata['vorname']." ".@$appdata['nachname']; ?>,<br/>
  <br/>
  gerne schicke ich Ihnen die bei unserem Beratungsgespräch vereinbarten Dokumente zum UNIQA KFZ-Antrag.<br/>
  <br/>
  Diese Unterlagen können Sie sich ansehen und herunterladen.<br/>
  Jetzt einfach direkt auf Ihrem Smartphone unterschreiben und damit vollinhaltlich zustimmen.<br/>
  <br/>
  <?php if (@$supervisordata['gender'] == "frau") "Ihre Betreuerin"; else echo "Ihr Betreuer"; ?><br/>
  <?php echo @$supervisordata['firstname']." ".@$supervisordata['lastname']; ?><br/>
  <?php if (!empty ($supervisordata['email'])) echo "<a href=\"mailto:".$supervisordata['email']."\">".$supervisordata['email']."</a><br/>"; ?>
  <?php if (!empty ($supervisordata['phone'])) echo $supervisordata['phone']."<br/>"; ?>
<?php } ?>

  <br/></br>
  <button style="width:100%; padding:6px; margin:4px 0px;" onclick="setPage('<?php echo $viewer_link; ?>', 0);">Dokument ansehen</button>
</div>

<div class="signaturefooter">
  <button style="width:100%; padding:6px; margin:4px 0px;" onclick="location.href='?hash=<?php echo @$signaturehash; ?>&step=2';">Dokument unterschreiben</button>
</div>
<?php } ?>


<?php if ($step == 2) { ?>
<!-- Step 2 -->
<div class="signatureheader">
  <img src="/repository/media_tpl/vms/uniqa/UNIQA_Logo.svg" style="height:44px; border:0; margin:0; float:left;" alt="UNIQA" />
  <span class="headline" style="float:right;"><a href="?hash=<?php echo @$signaturehash; ?>&step=1" style="display:none; font-weight:bold; font-size:20px; text-decoration: none;">‹</a> Unterschreiben</span>
</div>

<div class="signaturecontent">
  <?php if (@$signature_status <= 0 && $role == "agent") { ?>

  Nachdem Sie die erforderliche Unterschrift geleistet haben, leiten Sie bitte die Unterlagen mit "Abschließen" an Ihren Kunden weiter.<br/>
  <br/>
  Unterschriften:<br/>
  <?php if ($tipster == false) { ?>
  <div id="sign_3" style="padding:8px; border-top:1px solid grey;">
    <b>Beratungsprotokoll</b> <div id="check_3" style="display:none; color:#009102;">✔</div><br/>
    erfordert die Unterschrift des Beraters und Kunden <!-- Seite 5 --><br/>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="setPage('<?php echo $viewer_link; ?>', 5);">Anzeigen</button>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="signDocument ('Beratung', 1);">Unterschreiben</button>
  </div>
  <?php } ?>
  <?php if (requireInspection ($appdata) == true) { ?>
  <div id="sign_4" style="padding:8px; border-top:1px solid grey;">
    <b>Besichtigungsformular für Kasko von Gebrauchtfahrzeugen</b> <div id="check_4" style="display:none; color:#009102;">✔</div><br/>
    erfordert die Unterschrift des Beraters und Kunden <!-- Seite 11 --><br/>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="setPage('<?php echo $viewer_link; ?>', <?php if ($tipster == true) echo "9"; else echo "11"; ?>);">Anzeigen</button>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="signDocument ('Besichtigung', 1);">Unterschreiben</button>
  </div>
  <?php } ?>

  <?php } elseif (@$signature_status == 1 && $role == "client") { ?>

  Wenn alle erforderlichen Unterschriften geleistet wurden, leiten Sie bitte die Unterlagen mit "Abschließen" an Ihren Betreuer weiter.<br/>
  <br/>
  Unterschriften:<br/>
  <?php if ($tipster == false) { ?>
  <div id="sign_3" style="padding:8px; border-top:1px solid grey;">
    <b>Beratungsprotokoll</b> <div id="check_3" style="display:none; color:#009102;">✔</div><br/>
    erfordert die Unterschrift des Betreuers und Kunden <!-- Seite 5 --><br/>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="setPage('<?php echo $viewer_link; ?>', 5);">Anzeigen</button>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="signDocument ('Beratung', 1);">Unterschreiben</button>
  </div>
  <?php } ?>
  <div id="sign_2" style="padding:8px; border-top:1px solid grey;">
    <b>KFZ-Antrag</b> <div id="check_2" style="display:none; color:#009102;">✔</div><br/>
      erfordert die Unterschrift des Antragstellers <!-- Seite 1 --><br/>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="setPage('<?php echo $viewer_link; ?>', 1);">Anzeigen</button>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="signDocument ('Antrag', 1);">Unterschreiben</button>
  </div>
  <?php if ($appdata['zahlungsmethode'] != "zahlschein") { ?>
  <div id="sign_1" style="padding:8px; border-top:1px solid grey;">
    <b>SEPA Lastschriftmandat für Bankeinzug</b> <div id="check_1" style="display:none; color:#009102;">✔</div><br/>
    erfordert die Unterschrift des Kontoinhabers <!-- Seite 2 --><br/>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="setPage('<?php echo $viewer_link; ?>', 2);">Anzeigen</button>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="signDocument ('SEPA', 1);">Unterschreiben</button>
  </div>
  <?php } ?>
  <?php if (requireInspection ($appdata) == true) { ?>
  <div id="sign_4" style="padding:8px; border-top:1px solid grey;">
    <b>Besichtigungsformular für Kasko von Gebrauchtfahrzeugen</b> <div id="check_4" style="display:none; color:#009102;">✔</div><br/>
    erfordert die Unterschrift des Beraters und Kunden <!-- Seite 2 --><br/>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="setPage('<?php echo $viewer_link; ?>', <?php if ($tipster == true) echo "9"; else echo "11"; ?>);">Anzeigen</button>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="signDocument ('Besichtigung', 1);">Unterschreiben</button>
  </div>
  <?php } ?>
  <div id="sign_0" style="padding:8px; border-top:1px solid grey;">
    <b>Elektronisches Postfach und Marketingeinwilligung</b> <div id="check_0" style="display:none; color:#009102;">✔</div><br/>
      optionale Unterschrift des Antragstellers<!-- Seite 1 --><br/>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="setPage('<?php echo $viewer_link_marketing; ?>', 1);">Anzeigen</button>
    <button style="width:100%; padding:6px; margin:4px 0px;" onclick="signDocument ('Marketing', 0);">Unterschreiben</button>
  </div>

  <?php } else { ?>
  <div style="padding:8px;">
    <b>Keine Unterschrift erforderlich</b> 
  </div>
  <?php } ?>
</div>

<div class="signaturefooter">
  <button id="button_signed" style="width:100%; padding:6px; margin:4px 0px;" onclick="createSignedDocument();" disabled>Abschließen</button>
</div>
<?php } ?>


<?php if ($step == 3) { ?>
<!-- Step 3 -->
<div class="signatureheader">
  <img src="/repository/media_tpl/vms/uniqa/UNIQA_Logo.svg" style="height:44px; border:0; margin:0; float:left;" alt="UNIQA" />
  <span class="headline" style="float:right;">Ihr unterschriebenes Dokument</span>
</div>

<div class="signaturecontent">
<?php if (@$signature_status >= 1) { ?>
  Sie können nun das unterzeichnete Dokument herunterladen und aufbewahren.<br/>
  <br/>
  <button style="width:100%; padding:6px; margin:4px 0px;" onclick="downloadDocument();">Dokument herunterladen</button><br/>
  </br>
  <?php if (@$supervisordata['gender'] == "frau") "Ihre Betreuerin"; else echo "Ihr Betreuer"; ?><br/>
  <?php echo @$supervisordata['firstname']." ".@$supervisordata['lastname']; ?><br/>
  <?php if (!empty ($supervisordata['email'])) echo "<a href=\"mailto:".$supervisordata['email']."\">".$supervisordata['email']."</a><br/>"; ?>
  <?php if (!empty ($supervisordata['phone'])) echo $supervisordata['phone']."<br/>"; ?>
<?php } else { ?>
  Danke für Ihre Unterschrift, das Dokument wurde weitergeleitet.<br/>
<?php } ?>
</div>
<?php } ?>


[hyperCMS:tplinclude file='GoogleAnalytics-PartnerPortal.inc.tpl']
</body>
</html>