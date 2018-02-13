<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// disk key
require ("include/diskkey.inc.php");

// input parameters
$site = getrequest_esc ("site", "publicationname");
$preview = getrequest ("preview");
$group_name = getrequest_esc ("group_name", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'group') || (!checkglobalpermission ($site, 'groupcreate') && !checkglobalpermission ($site, 'groupedit')) || !valid_publicationname ($site))  killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function markAll ()
{
  f = document.forms[0];
 
  for (i = 0; i < f.elements.length; i++)
  {
    if (f.elements[i].name != "select")
      if (!f.elements[i].checked) f.elements[i].click();
  }
}

function unmarkAll ()
{
  f = document.forms[0];
  
  for (i = 0; i < f.elements.length; i++)
  {
    if (f.elements[i].name != "select")
      if (f.elements[i].checked) f.elements[i].click();
  }
}

function checkMark ()
{
  if (document.forms['groupform'].selectall.checked) markAll();
  else unmarkAll();
}

function goToAccess (target)
{
  document.forms['groupform'].elements['cat'].value = target;
  document.forms['groupform'].submit();

  return true;
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_ok_over.png')">

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
<?php
// check if login is an attribute of a sent string
if (strpos ($group_name, ".php") > 0)
{
  // extract login
  $group_name = getattribute ($group_name, "group_name");
}

if ($group_name != "" && $group_name != false)
{
  $groupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

  $usergrouprecord = selectcontent ($groupdata, "<usergroup>", "<groupname>", $group_name);

  $permission_array = getcontent ($usergrouprecord[0], "<permission>");

  if ($permission_array != false)
  {
    $permission_str = $permission_array[0];
    
    if ($permission_str != "")
    {
      // get permissions number strings
      $desktoppermlist = getattribute ($permission_str, "desktop");
      $sitepermlist = getattribute ($permission_str, "site");
      $userpermlist = getattribute ($permission_str, "user");
      $grouppermlist = getattribute ($permission_str, "group");
      $perspermlist = getattribute ($permission_str, "pers");
      $wfpermlist = getattribute ($permission_str, "workflow");
      $templatepermlist = getattribute ($permission_str, "template");
      $mediapermlist = getattribute ($permission_str, "media");
      $componentpermlist = getattribute ($permission_str, "component");
      $pagepermlist = getattribute ($permission_str, "page");
    }
    
    // set default values for
    // desktop permissions
    $desktopglobal = 0;
    $desktopsetting = 0;
    $desktoptaskmgmt = 0;
    $desktopcheckedout = 0; 
    $desktoptimetravel = 0;
    $desktopfavorites = 0; // added in version 5.7.4
    $desktopprojectmgmt = 0; // added in version 6.0.1
    // site permissions
    $siteglobal = 0;
    $sitecreate = 0;
    $sitedelete = 0;
    $siteedit = 0;
    // user permissions
    $userglobal = 0;
    $usercreate = 0;
    $userdelete = 0;
    $useredit = 0;
    // group permissions
    $groupglobal = 0;
    $groupcreate = 0;
    $groupdelete = 0;
    $groupedit = 0;
    // personalization permissions
    $persglobal = 0;
    $perstrack = 0;
    $perstrackcreate = 0;
    $perstrackdelete = 0;
    $perstrackedit = 0;
    $persprof = 0;
    $persprofcreate = 0;
    $persprofdelete = 0;
    $persprofedit = 0;
    // workflow permissions
    $workflowglobal = 0;
    $workflowproc = 0;
    $workflowproccreate = 0;
    $workflowprocdelete = 0;
    $workflowprocedit = 0;
    $workflowprocfolder = 0;
    $workflowscript = 0;
    $workflowscriptcreate = 0;
    $workflowscriptdelete = 0;
    $workflowscriptedit = 0;      
    // template permissions
    $templateglobal = 0;
    $tpl = 0;
    $tplcreate = 0;
    $tpldelete = 0;
    $tpledit = 0;
    // template media permissions
    $tplmedia = 0;
    $tplmediacatcreate = 0;
    $tplmediacatdelete = 0;
    $tplmediacatrename = 0;
    $tplmediaupload = 0;
    $tplmediadelete = 0;
    // component permissions
    $componentglobal = 0;
    $compupload = 0;
    $compdownload = 0;
    $compsendlink = 0; 
    $compfoldercreate = 0;
    $compfolderdelete = 0;
    $compfolderrename = 0;
    $compcreate = 0;
    $compdelete = 0;
    $comprename = 0;
    $comppublish = 0;
    // content permissions
    $pageglobal = 0;
    $pagesendlink = 0;
    $pagefoldercreate = 0;
    $pagefolderdelete = 0;
    $pagefolderrename = 0;
    $pagecreate = 0;
    $pagedelete = 0;
    $pagerename = 0;
    $pagepublish = 0;

    // read and check permissions for 
    // desktop permissions
    if (!empty ($desktoppermlist[0])) $desktopglobal = $desktoppermlist[0];
    if (!empty ($desktoppermlist[1])) $desktopsetting = $desktoppermlist[1];
    if (!empty ($desktoppermlist[2])) $desktoptaskmgmt = $desktoppermlist[2];
    if (!empty ($desktoppermlist[3])) $desktopcheckedout = $desktoppermlist[3];
    if (!empty ($desktoppermlist[4])) $desktoptimetravel = $desktoppermlist[4];
    if (!empty ($desktoppermlist[5])) $desktopfavorites = $desktoppermlist[5];
    if (!empty ($desktoppermlist[6])) $desktopprojectmgmt = $desktoppermlist[6];
    // site permissions
    if (!empty ($sitepermlist[0])) $siteglobal = $sitepermlist[0];
    if (!empty ($sitepermlist[1])) $sitecreate = $sitepermlist[1];
    if (!empty ($sitepermlist[2])) $sitedelete = $sitepermlist[2];
    if (!empty ($sitepermlist[3])) $siteedit = $sitepermlist[3];
    // user permissions
    if (!empty ($userpermlist[0])) $userglobal = $userpermlist[0];
    if (!empty ($userpermlist[1])) $usercreate = $userpermlist[1];
    if (!empty ($userpermlist[2])) $userdelete = $userpermlist[2];
    if (!empty ($userpermlist[3])) $useredit = $userpermlist[3];
    // group permissions
    if (!empty ($grouppermlist[0])) $groupglobal = $grouppermlist[0];
    if (!empty ($grouppermlist[1])) $groupcreate = $grouppermlist[1];
    if (!empty ($grouppermlist[2])) $groupdelete = $grouppermlist[2];
    if (!empty ($grouppermlist[3])) $groupedit = $grouppermlist[3];
    // personalization permissions
    if (!empty ($perspermlist[0])) $persglobal = $perspermlist[0];
    if (!empty ($perspermlist[1])) $perstrack = $perspermlist[1];
    if (!empty ($perspermlist[2])) $perstrackcreate = $perspermlist[2];
    if (!empty ($perspermlist[3])) $perstrackdelete = $perspermlist[3];
    if (!empty ($perspermlist[4])) $perstrackedit = $perspermlist[4];
    if (!empty ($perspermlist[5])) $persprof = $perspermlist[5];
    if (!empty ($perspermlist[6])) $persprofcreate = $perspermlist[6];
    if (!empty ($perspermlist[7])) $persprofdelete = $perspermlist[7];
    if (!empty ($perspermlist[8])) $persprofedit = $perspermlist[8];
    // workflow permissions
    if (!empty ($wfpermlist[0])) $workflowglobal = $wfpermlist[0];
    if (!empty ($wfpermlist[1])) $workflowproc = $wfpermlist[1];
    if (!empty ($wfpermlist[2])) $workflowproccreate = $wfpermlist[2];
    if (!empty ($wfpermlist[3])) $workflowprocdelete = $wfpermlist[3];
    if (!empty ($wfpermlist[4])) $workflowprocedit = $wfpermlist[4];
    if (!empty ($wfpermlist[5])) $workflowprocfolder = $wfpermlist[5];
    if (!empty ($wfpermlist[6])) $workflowscript = $wfpermlist[6];
    if (!empty ($wfpermlist[7])) $workflowscriptcreate = $wfpermlist[7];
    if (!empty ($wfpermlist[8])) $workflowscriptdelete = $wfpermlist[8];
    if (!empty ($wfpermlist[9])) $workflowscriptedit = $wfpermlist[9];      
    // template permissions
    if (!empty ($templatepermlist[0])) $templateglobal = $templatepermlist[0];
    if (!empty ($templatepermlist[1])) $tpl = $templatepermlist[1];
    if (!empty ($templatepermlist[2])) $tplcreate = $templatepermlist[2];
    if (!empty ($templatepermlist[5])) // older versions before 5.5.11 (template upload still exists)
    {
      if (!empty ($templatepermlist[4])) $tpldelete = $templatepermlist[4];
      if (!empty ($templatepermlist[5])) $tpledit = $templatepermlist[5];
    }
    else
    {
      if (!empty ($templatepermlist[3])) $tpldelete = $templatepermlist[3];
      if (!empty ($templatepermlist[4])) $tpledit = $templatepermlist[4];
    }
    // template media permissions
    if (!empty ($mediapermlist[0])) $tplmedia = $mediapermlist[0];
    if (!empty ($mediapermlist[1])) $tplmediacatcreate = $mediapermlist[1];
    if (!empty ($mediapermlist[2])) $tplmediacatdelete = $mediapermlist[2];
    if (!empty ($mediapermlist[3])) $tplmediacatrename = $mediapermlist[3];
    if (!empty ($mediapermlist[4])) $tplmediaupload = $mediapermlist[4];
    if (!empty ($mediapermlist[5])) $tplmediadelete = $mediapermlist[5];
    // component permissions
    if (!empty ($componentpermlist[0])) $componentglobal = $componentpermlist[0];
    if (!empty ($componentpermlist[1])) $compupload = $componentpermlist[1];
    if (!empty ($componentpermlist[2])) $compdownload = $componentpermlist[2];
    if (!empty ($componentpermlist[3])) $compsendlink = $componentpermlist[3]; 
    if (!empty ($componentpermlist[4])) $compfoldercreate = $componentpermlist[4];
    if (!empty ($componentpermlist[5])) $compfolderdelete = $componentpermlist[5];
    if (!empty ($componentpermlist[6])) $compfolderrename = $componentpermlist[6];
    if (!empty ($componentpermlist[7])) $compcreate = $componentpermlist[7];
    if (!empty ($componentpermlist[8])) $compdelete = $componentpermlist[8];
    if (!empty ($componentpermlist[9])) $comprename = $componentpermlist[9];
    if (!empty ($componentpermlist[10])) $comppublish = $componentpermlist[10];
    // content permissions
    if (!empty ($pagepermlist[0])) $pageglobal = $pagepermlist[0];
    if (!empty ($pagepermlist[1])) $pagesendlink = $pagepermlist[1];
    if (!empty ($pagepermlist[2])) $pagefoldercreate = $pagepermlist[2];
    if (!empty ($pagepermlist[3])) $pagefolderdelete = $pagepermlist[3];
    if (!empty ($pagepermlist[4])) $pagefolderrename = $pagepermlist[4];
    if (!empty ($pagepermlist[5])) $pagecreate = $pagepermlist[5];
    if (!empty ($pagepermlist[6])) $pagedelete = $pagepermlist[6];
    if (!empty ($pagepermlist[7])) $pagerename = $pagepermlist[7];
    if (!empty ($pagepermlist[8])) $pagepublish = $pagepermlist[8];
  }
}

// define php script for form action
if ($preview == "no")
{
  $action = "group_edit_script.php";
  $title = getescapedtext ($hcms_lang['edit-permissions-of-group'][$lang]);
}
elseif ($preview == "yes")
{
  $action = "";
  $title = getescapedtext ($hcms_lang['permissions-of-group'][$lang]);
}
?>

<p class=hcmsHeadline><?php echo $title; ?> <?php echo $group_name; ?></p>

<form name="groupform" action="<?php echo $action; ?>" method="post">
  <input type="hidden" name="sender" value="settings">
  <input type="hidden" name="cat" value="settings">
  <input type="hidden" name="site" value="<?php echo $site; ?>">
  <input type="hidden" name="group_name" value="<?php echo $group_name; ?>">
  <input type="hidden" name="permission[default]" value="dummy">
  <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>">
  
  <table border="0" cellspacing="0" cellpadding="3">
    <tr> 
      <td><?php echo getescapedtext ($hcms_lang['select-all'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="selectall" onClick="checkMark();" /></td>
    </tr>
    <tr> 
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr class="hcmsRowHead1"> 
      <td nowrap="nowrap"><b><img src="<?php echo getthemelocation(); ?>img/desk.png" class="hcmsIconList" align="absmiddle" /> <?php echo getescapedtext ($hcms_lang['grant-desktop-management'][$lang]); ?></b></td>
      <td align="center"><input type="checkbox" name="permission[desktopglobal]" value="1" <?php if ($desktopglobal==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['personal-settings'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[desktopsetting]" value="1" <?php if ($desktopsetting==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php if (is_file ($mgmt_config['abs_path_cms']."project/project_list.php")) { ?>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['project-management'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[desktopprojectmgmt]" value="1" <?php if ($desktopprojectmgmt==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php } ?>
    <?php if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php")) { ?>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['task-management'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[desktoptaskmgmt]" value="1" <?php if ($desktoptaskmgmt==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php } ?>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['favorites'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[desktopfavorites]" value="1" <?php if ($desktopfavorites==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['checked-out-items'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[desktopcheckedout]" value="1" <?php if ($desktopcheckedout==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['travel-though-time'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[desktoptimetravel]" value="1" <?php if ($desktoptimetravel==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php
    if ($diskkey == "server" && !empty ($mgmt_config[$site]['site_admin']))
    {
    ?>
    <tr class="hcmsRowHead1">
      <td nowrap="nowrap"><b><img src="<?php echo getthemelocation(); ?>img/site.png" class="hcmsIconList" align="absmiddle" /> <?php echo getescapedtext ($hcms_lang['grant-publication-management'][$lang]); ?></b></td>
      <td align="center"><input type="checkbox" name="permission[siteglobal]" value="1" <?php if ($siteglobal==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1">
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['create-publication'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[sitecreate]" value="1" <?php if ($sitecreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2">
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-publication'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[sitedelete]" value="1" <?php if ($sitedelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1">
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['edit-publication'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[siteedit]" value="1" <?php if ($siteedit==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php
    }
    ?>
    <tr class="hcmsRowHead1"> 
      <td nowrap="nowrap"><b><img src="<?php echo getthemelocation(); ?>img/user.png" class="hcmsIconList" align="absmiddle" /> <?php echo getescapedtext ($hcms_lang['grant-user-management'][$lang]); ?></b></td>
      <td align="center"><input type="checkbox" name="permission[userglobal]" value="1" <?php if ($userglobal==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['create-user'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[usercreate]" value="1" <?php if ($usercreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-user'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[userdelete]" value="1" <?php if ($userdelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['edit-user'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[useredit]" value="1" <?php if ($useredit==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowHead1"> 
      <td nowrap="nowrap"> <b><img src="<?php echo getthemelocation(); ?>img/usergroup.png" class="hcmsIconList" align="absmiddle" /> <?php echo getescapedtext ($hcms_lang['grant-group-management'][$lang]); ?></b></td>
      <td align="center"><input type="checkbox" name="permission[groupglobal]" value="1" <?php if ($groupglobal==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td><?php echo getescapedtext ($hcms_lang['create-group'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[groupcreate]" value="1" <?php if ($groupcreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-group'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[groupdelete]" value="1" <?php if ($groupdelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['edit-group'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[groupedit]" value="1" <?php if ($groupedit==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php if (!isset ($mgmt_config[$site]['dam']) || $mgmt_config[$site]['dam'] == false) { ?>
    <tr class="hcmsRowHead1"> 
      <td nowrap="nowrap" align="left" valign="bottom"> <b><img src="<?php echo getthemelocation(); ?>img/pers_registration.png" class="hcmsIconList" align="absmiddle" /> <?php echo getescapedtext ($hcms_lang['grant-personalization-management'][$lang]); ?></b></td>
      <td align="center"><input type="checkbox" name="permission[persglobal]" value="1" <?php if ($persglobal==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowHead2"> 
      <td nowrap="nowrap"> <?php echo getescapedtext ($hcms_lang['grant-customer-registration-permissions'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[perstrack]" value="1" <?php if ($perstrack==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td><?php echo getescapedtext ($hcms_lang['create-customer-registration'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[perstrackcreate]" value="1" <?php if ($perstrackcreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-customer-registration'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[perstrackdelete]" value="1" <?php if ($perstrackdelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td><?php echo getescapedtext ($hcms_lang['edit-customer-registration'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[perstrackedit]" value="1" <?php if ($perstrackedit==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowHead2"> 
      <td nowrap="nowrap"> <?php echo getescapedtext ($hcms_lang['grant-customer-profile-permissions'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[persprof]" value="1" <?php if ($persprof==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['create-customer-profile'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[persprofcreate]" value="1" <?php if ($persprofcreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-customer-profile'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[persprofdelete]" value="1" <?php if ($persprofdelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td><?php echo getescapedtext ($hcms_lang['edit-customer-profile'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[persprofedit]" value="1" <?php if ($persprofedit==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php } ?>
    <?php if (is_file ($mgmt_config['abs_path_cms']."workflow/frameset_workflow.php")) { ?>
    <tr class="hcmsRowHead1"> 
      <td nowrap="nowrap" align="left" valign="bottom"> <b><img src="<?php echo getthemelocation(); ?>img/workflow.png" class="hcmsIconList" align="absmiddle" /> <?php echo getescapedtext ($hcms_lang['grant-workflow-management'][$lang]); ?></b></td>
      <td align="center"><input type="checkbox" name="permission[workflowglobal]" value="1" <?php if ($workflowglobal==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowHead2"> 
      <td nowrap="nowrap"> <?php echo getescapedtext ($hcms_lang['grant-workflow-manager-permissions'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[workflowproc]" value="1" <?php if ($workflowproc==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['create-workflow'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[workflowproccreate]" value="1" <?php if ($workflowproccreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-workflow'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[workflowprocdelete]" value="1" <?php if ($workflowprocdelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['edit-workflow'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[workflowprocedit]" value="1" <?php if ($workflowprocedit==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['define-workflow-field-of-application'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[workflowprocfolder]" value="1" <?php if ($workflowprocfolder==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowHead2"> 
      <td nowrap="nowrap"> <?php echo getescapedtext ($hcms_lang['grant-workflow-script-permissions'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[workflowscript]" value="1" <?php if ($workflowscript==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td><?php echo getescapedtext ($hcms_lang['create-workflow-script'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[workflowscriptcreate]" value="1" <?php if ($workflowscriptcreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-workflow-script'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[workflowscriptdelete]" value="1" <?php if ($workflowscriptdelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['edit-workflow-script'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[workflowscriptedit]" value="1" <?php if ($workflowscriptedit==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php } ?>
    <tr class="hcmsRowHead1"> 
      <td nowrap="nowrap" align="left" valign="bottom"> <b><img src="<?php echo getthemelocation(); ?>img/template.png" class="hcmsIconList" align="absmiddle" /> <?php echo getescapedtext ($hcms_lang['grant-template-management'][$lang]); ?></b></td>
      <td align="center"><input type="checkbox" name="permission[templateglobal]" value="1" <?php if ($templateglobal==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowHead2"> 
      <td nowrap="nowrap"> <?php echo getescapedtext ($hcms_lang['grant-template-permissions'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[tpl]" value="1" <?php if ($tpl==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td><?php echo getescapedtext ($hcms_lang['create-template'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[tplcreate]" value="1" <?php if ($tplcreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-template'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[tpldelete]" value="1" <?php if ($tpldelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['edit-template'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[tpledit]" value="1" <?php if ($tpledit==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr> 
      <td nowrap="nowrap" class="hcmsRowHead2"> <?php echo getescapedtext ($hcms_lang['grant-template-media-permissions'][$lang]); ?></td>
      <td class="hcmsRowHead2" align="center"><input type="checkbox" name="permission[tplmedia]" value="1" <?php if ($tplmedia==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['create-template-media-category'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[tplmediacatcreate]" value="1" <?php if ($tplmediacatcreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-template-media-category'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[tplmediacatdelete]" value="1" <?php if ($tplmediacatdelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['rename-template-media-category'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[tplmediacatrename]" value="1" <?php if ($tplmediacatrename==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['upload-template-media'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[tplmediaupload]" value="1" <?php if ($tplmediaupload==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-template-media'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[tplmediadelete]" value="1" <?php if ($tplmediadelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowHead1"> 
      <td nowrap="nowrap"><b><img src="<?php echo getthemelocation(); ?>img/folder_comp.png" class="hcmsIconList" align="absmiddle" /> <?php echo getescapedtext ($hcms_lang['grant-asset-management'][$lang]); ?></b></td>
      <td align="center"><input type="checkbox" name="permission[componentglobal]" value="1" <?php if ($componentglobal==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="<?php if (!empty ($mgmt_config[$site]['sendmail'])) echo "hcmsRowData1"; ?>"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['access-to-folders'][$lang]); ?></td>
      <td align="center"><img onClick="goToAccess('comp');" class="hcmsButtonTiny hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/folder_comp.png" name="go_compaccess" align="absmiddle" alt="<?php echo getescapedtext ($hcms_lang['grantdisable'][$lang]); ?>" <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php if (!empty ($mgmt_config[$site]['sendmail'])) { ?>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['send-mail-link'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[compsendlink]" value="1" <?php if ($compsendlink==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr >
    <?php } ?>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['create-folder'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[compfoldercreate]" value="1" <?php if ($compfoldercreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-folder'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[compfolderdelete]" value="1" <?php if ($compfolderdelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['cutcopypasterename-folder'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[compfolderrename]" value="1" <?php if ($compfolderrename==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['createcheckoutedit-component'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[compcreate]" value="1" <?php if ($compcreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td><?php echo getescapedtext ($hcms_lang['uploadcheckoutedit-file'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[compupload]" value="1" <?php if ($compupload==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2">
      <td><?php echo getescapedtext ($hcms_lang['download-file'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[compdownload]" value="1" <?php if ($compdownload==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr> 
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-component-or-file'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[compdelete]" value="1" <?php if ($compdelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['cutcopypasterename-component-or-file'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[comprename]" value="1" <?php if ($comprename==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap">
        <?php echo getescapedtext ($hcms_lang['publishunpublish-assets'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[comppublish]" value="1" <?php if ($comppublish==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php if (!$mgmt_config[$site]['dam']) { ?>
    <tr class="hcmsRowHead1"> 
      <td nowrap="nowrap"><b><img src="<?php echo getthemelocation(); ?>img/folder_page.png" class="hcmsIconList" align="absmiddle" /> 
        <?php echo getescapedtext ($hcms_lang['grant-page-management'][$lang]); ?></b></td>
      <td align="center"><input type="checkbox" name="permission[pageglobal]" value="1" <?php if ($pageglobal==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="<?php if ($mgmt_config[$site]['sendmail']) echo "hcmsRowData1"; ?>"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['access-to-folders'][$lang]); ?></td>
      <td align="center"><img onClick="goToAccess('page');" class="hcmsButtonTiny hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/folder_page.png" name="go_pageaccess" align="absmiddle" alt="<?php echo getescapedtext ($hcms_lang['grantdisable'][$lang]); ?>" <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php if ($mgmt_config[$site]['sendmail']) { ?>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['send-mail-link'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[pagesendlink]" value="1" <?php if ($pagesendlink==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php } ?>   
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['create-folder'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[pagefoldercreate]" value="1" <?php if ($pagefoldercreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-folder'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[pagefolderdelete]" value="1" <?php if ($pagefolderdelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['cutcopypasterename-folder'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[pagefolderrename]" value="1" <?php if ($pagefolderrename==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['createcheckoutedit-page'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[pagecreate]" value="1" <?php if ($pagecreate==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['delete-page'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[pagedelete]" value="1" <?php if ($pagedelete==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['cutcopypasterename-page'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[pagerename]" value="1" <?php if ($pagerename==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <tr class="hcmsRowData1"> 
      <td nowrap="nowrap">
        <?php echo getescapedtext ($hcms_lang['publishunpublish-page'][$lang]); ?></td>
      <td align="center"><input type="checkbox" name="permission[pagepublish]" value="1" <?php if ($pagepublish==1) {echo "checked=\"checked\"";} ?> <?php if ($preview=="yes") {echo "disabled=\"disabled\"";} ?> /></td>
    </tr>
    <?php } ?>
    <tr class="hcmsRowData2"> 
      <td nowrap="nowrap">&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <?php
    if ($preview == "no")
    {
      echo "<tr>
        <td rowspan=\"2\"><strong>".getescapedtext ($hcms_lang['save-group-settings'][$lang])."</strong>
          <img name=\"Button\" src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onclick=\"document.forms['groupform'].submit();\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button','','".getthemelocation()."img/button_ok_over.png',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" />
        </td>
      </tr>";
    }
    ?>
  </table>
</form>
</div>

</body>
</html>
