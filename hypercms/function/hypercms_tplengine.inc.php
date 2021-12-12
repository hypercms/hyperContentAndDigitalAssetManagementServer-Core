<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */

// ===================================== TEMPLATE ENGINE CORE ===========================================
// the following functions are the core of the template engine.
// every view on templates, content objects in preview mode or edit mode and publishing
// is done by these functions.

// --------------------------------------- template functions -----------------------------------------------
// These functions are used for creating the desired output depending on the presentation technology.
// The functions are used by buildview and viewinclusions.

// inclusions of files (depending on OS)
function tpl_compinclude ($application, $file, $os_cms)
{
  $application = strtolower (trim ($application));

  if ($application == "php")
  {
    if (strtoupper ($os_cms) == "WIN") return "<?php echo @file_get_contents ('".$file."'); ?>";
    else return "<?php @include ('".$file."'); ?>";
  }
  elseif ($application == "jsp")
  {
    // the file (component) that will be included has to be inside the application root!
    // an absolute path is not allowed!
    return "<%@ include file=\"".$file."\" %>";
  }
  elseif ($application == "asp")
  {
    return "<!--#include file=\"".$file."\"-->";
  }
  elseif ($application == "aspx")
  {
    return "<% Response.WriteFile (\"".$file."\") %>";
  }
  elseif ($application == "htm")
  {
    return @file_get_contents ($file);
  }
  else return "";
}

// define start tag syntax
function tpl_tagbegin ($application)
{
  $application = strtolower (trim ($application));

  if ($application == "php" || $application == "generator") return "<?php";
  elseif ($application == "jsp") return "<%";
  elseif ($application == "asp") return "<%";
  elseif ($application == "aspx") return "<script runat=server>";
  elseif ($application == "htm") return "<script type=\"text/javascript\">";
  else return "";
}

// define end tag syntax
function tpl_tagend ($application)
{
  $application = strtolower (trim ($application));

  if ($application == "php" || $application == "generator") return "?>";
  elseif ($application == "jsp") return "%>";
  elseif ($application == "asp") return "%>";
  elseif ($application == "aspx") return "</script>";
  elseif ($application == "htm") return "</script>";
  else return "";
}

// code used for customer tracking
function tpl_pagetracking ($application, $code)
{
  $application = strtolower (trim ($application));

  if ($application == "php") return "<?php\n".$code."\n?>";
  elseif ($application == "jsp") return "<%\n".$code."\n%>";
  elseif ($application == "asp") return "<%\n".$code."\n%>";
  elseif ($application == "aspx") return "<script runat=server>\n".$code."\n</script>";
  elseif ($application == "htm") return "<script type=\"text/javascript\">\n".$code."\n</script>";
  else return "";
}

// code used for language session
function tpl_languagesession ($application, $name, $values, $default)
{
  $application = strtolower (trim ($application));

  if ($application == "php") return "<?php\nif (!empty(\$_REQUEST['".$name."']) && substr_count (\"|$values|\", \"|\".\$_REQUEST['$name'].\"|\")==1) \$_SESSION['$name']=\$_REQUEST['$name'];\nelseif (empty(\$_SESSION['$name'])) \$_SESSION['$name']=\"$default\";\n?>";
  elseif ($application == "jsp") return "<%\nString hypercms_language=\"|$values|\";\nif (request.getParameter('$name')!=\"\" && hypercms_language.indexOf (\"|\"+request.getParameter('$name')+\"|\")>0) session.setAttribute(\"$name\", request.getParameter(\"$name\"));\nelseif (session.getAttribute(\"$name\")==\"\") session.setAttribute(\"$name\", \"$default\");\n%>";
  elseif ($application == "asp") return "<%\nif (Request.QueryString('$name')<>\"\" and InStr (\"|$values|\", \"|\"&Request.QueryString('$name')&\"|\")>0) then Session['$name']=Request.QueryString('$name');\nelseif (Session['$name']==\"\") then Session['$name']=\"$default\";\nend if\n%>";
  elseif ($application == "aspx") return "<script runat=server>\nif (Request.QueryString['$name']!=\"\" && InStr (\"|$values|\", \"|\"+Request.QueryString['$name']+\"|\"]>0) { Session['$name']=Request.QueryString['$name']; }\nelseif (Session['$name']==\"\") { Session['$name']=\"$default\"; }\n</script>";
  else return "";
}

// code used for time switched text, media, linktarget and linktext elements
function tpl_tselement ($application, $datefrom, $dateto, $content)
{
  $application = strtolower (trim ($application));

  if ($application == "php")
  {
   // escape double quotes
    $content = str_replace ("\"", "\\\"", $content);

    return "<?php if (\$hypercms_today >= ".$datefrom." && \$hypercms_today <= ".$dateto.") echo \"".$content."\"; ?>";
  }
  elseif ($application == "jsp")
  {
    // escape double quotes
    $content = str_replace ("\"", "\\\"", $content);

    return "<% if (hypercms_today.compareTo(\"".$datefrom."\")>=0 && hypercms_today.compareTo(\"".$dateto."\")<=0) out.print(\"".$content."\"); %>";
  }
  elseif ($application == "asp")
  {
    // escape double quotes
    $content = str_replace ("\"", "\"\"", $content);

    return "<% if (hypercms_today >= ".$datefrom." and hypercms_today =< ".$dateto.") then
  Response.Write(\"".$content."\")
end if %>";
  }
  elseif ($application == "aspx")
  {
    // escape double quotes
    $content = str_replace ("\"", "\"\"", $content);

    return "<% if (hypercms_today >= ".$datefrom." and hypercms_today =< ".$dateto.") {
  Response.Write(\"".$content."\");
} %>";
  }
  elseif ($application == "htm" || $application == "xml")
  {
    return $content;
  }
  else return "";
}

// code used for link function
function tpl_insertlink ($application, $linkindex, $id)
{
  $application = strtolower (trim ($application));

  if ($application == "php") return "<?php insertlink (\$$linkindex, \"$id\"); ?>";
  elseif ($application == "jsp") return "<%=insertlink ($linkindex, \"$id\", properties) %>";
  elseif ($application == "asp") return "<% call insertlink ($linkindex, \"$id\") %>";
  elseif ($application == "aspx") return "<% insertlink ($linkindex, \"$id\"); %>";
  else return "";
}

// code used for time switched link function
function tpl_tsinsertlink ($application, $datefrom, $dateto, $linkindex, $id)
{
  $application = strtolower (trim ($application));

  if ($application == "php")
  {
    return "<?php if (\$hypercms_today >= ".$datefrom." && \$hypercms_today <= ".$dateto.") insertlink (\$$linkindex, \"$id\");
else echo \"#\"; ?>";
  }
  elseif ($application == "jsp")
  {
    return "<% if (hypercms_today.compareTo(\"".$datefrom."\")>=0 && hypercms_today.compareTo(\"".$dateto."\")<=0) insertlink ($linkindex, \"$id\", properties);
else out.print('#'); %>";
  }
  elseif ($application == "asp")
  {
    return "<% if (hypercms_today >= ".$datefrom." and hypercms_today =< ".$dateto.") then
  call insertlink ($linkindex,\"$id\")
end if %>";
  }
  elseif ($application == "aspx")
  {
    return "<% if (hypercms_today >= ".$datefrom." && hypercms_today =< ".$dateto.") {
  insertlink ($linkindex,\"$id\");
} %>";
  }
  else return "";
}

// code used for time switched link without link management
function tpl_tslink ($application, $datefrom, $dateto, $content)
{
  $application = strtolower (trim ($application));

  // escape double quotes
  $content = str_replace ("\"", "\\\"", $content);

  if ($application == "php")
  {
    // escape double quotes
    $content = str_replace ("\"", "\\\"", $content);

    return "<?php if (\$hypercms_today >= ".$datefrom." && \$hypercms_today <= ".$dateto.") echo '".$content."';
else echo \"#\"; ?>";
  }
  elseif ($application == "jsp")
  {
    // escape double quotes
    $content = str_replace ("\"", "\\\"", $content);

    return "<% if (hypercms_today.compareTo(\"".$datefrom."\")>=0 && hypercms_today.compareTo(\"".$dateto."\")<=0) out.println('".$content."');
else out.println('#'); %>";
  }
  elseif ($application == "asp")
  {
    // escape double quotes
    $content = str_replace ("\"", "\"\"", $content);

    return "<% if (hypercms_today >= ".$datefrom." and hypercms_today =< ".$dateto.") then
  response.write (\"".$content."\")
else
  response.write(\"#\")
end if %>";
  }
  elseif ($application == "aspx")
  {
    // escape double quotes
    $content = str_replace ("\"", "\"\"", $content);

    return "<% if (hypercms_today >= ".$datefrom." && hypercms_today =< ".$dateto.") {
  response.write (\"".$content."\");
} else {
  response.write(\"#\");
} %>";
  }
  else return "";
}

// code used for component function
function tpl_insertcomponent ($application, $linkindex, $id)
{
  $application = strtolower (trim ($application));

  if (substr_count ($linkindex, "%comp%") > 0) $linkindex = str_replace ("%comp%", "", $linkindex);

  if ($application == "php")
  {
    if ($id != "") return "<?php insertcomponent (\$$linkindex, \"$id\"); ?>";
    else return "<?php insertcomponent (\"$linkindex\", \"$id\"); ?>";
  }
  elseif ($application == "jsp")
  {
    if ($id != "") return "<% tok = insertcomponent ($linkindex, \"$id\", properties, out);
if(tok != null){
  String component;
   while (tok.hasMoreTokens()) {
    component = tok.nextToken();
    %><jsp:include page=\"<%=component%>\"/><%
  }
} %>";
    else return "<% tok = insertcomponent (\"$linkindex\", \"$id\", properties, out);
if(tok != null){
  String component;
   while (tok.hasMoreTokens()) {
    component = tok.nextToken();
    %><jsp:include page=\"<%=component%>\"/><%
  }
} %>";
  }
  elseif ($application == "asp")
  {
    if ($id != "") return "<% call insertcomponent ($linkindex, \"$id\") %>";
    else return "<% call insertcomponent_wol (\"$linkindex\", \"$id\") %>";
  }
  elseif ($application == "aspx")
  {
    if ($id != "") return "<% insertcomponent ($linkindex, \"$id\"); %>";
    else return "<% insertcomponent_wol (\"$linkindex\", \"$id\"); %>";
  }
  else return "";
}

// code used for personalized/conditional component function
function tpl_persinsertcomponent ($application, $condition, $linkindex, $id)
{
  $application = strtolower (trim ($application));

  if (substr_count ($linkindex, "%comp%") > 0) $linkindex = str_replace ("%comp%", "", $linkindex);

  if ($application == "php")
  {
    if ($id != "") return "<?php if ($condition) insertcomponent (\$$linkindex, \"$id\"); ?>";
    else return "<?php if ($condition) insertcomponent (\"$linkindex\", \"$id\"); ?>";
  }
  elseif ($application == "jsp")
  {
    if ($id != "") return "<% if ($condition) {
  tok = insertcomponent ($linkindex, \"$id\", properties, out);
  if(tok != null){
    String component;
     while (tok.hasMoreTokens()) {
      component = tok.nextToken();
      %><jsp:include page=\"<%=component%>\"/><%
    }
  }
} %>";
    else return "<% if ($condition) {
  tok = insertcomponent (\"$linkindex\", \"$id\", properties, out);
  if(tok != null){
    String component;
     while (tok.hasMoreTokens()) {
      component = tok.nextToken();
      %><jsp:include page=\"<%=component%>\"/><%
    }
  }
} %>";
  }
  elseif ($application == "asp")
  {
    if ($id != "") return "<% if ($condition) then
  call insertcomponent ($linkindex, \"$id\")
end if %>";
    else return "<% if ($condition) then
  call insertcomponent_wol (\"$linkindex\", \"$id\")
end if %>";
  }
  elseif ($application == "aspx")
  {
    if ($id != "") return "<% if ($condition) {
  insertcomponent ($linkindex, \"$id\");
} %>";
    else return "<% if ($condition) {
  insertcomponent_wol (\"$linkindex\", \"$id\");
} %>";
  }
  else return "";
}

// code used for time switched component function
function tpl_tsinsertcomponent ($application, $datefrom, $dateto, $linkindex, $id)
{
  $application = strtolower (trim ($application));

  if (substr_count ($linkindex, "%comp%") > 0) $linkindex = str_replace ("%comp%", "", $linkindex);

  if ($application == "php")
  {
    if ($id != "") return "<?php if (\$hypercms_today >= ".$datefrom." && \$hypercms_today <= ".$dateto.") insertcomponent (\$$linkindex, \"$id\"); ?>";
    else return "<?php if (\$hypercms_today >= ".$datefrom." && \$hypercms_today <= ".$dateto.") insertcomponent (\"$linkindex\", \"$id\"); ?>";
  }
  elseif ($application == "jsp")
  {
    if ($id != "") return "<% if (hypercms_today.compareTo(\"".$datefrom."\")>=0 && hypercms_today.compareTo(\"".$dateto."\")<=0) {
  tok = insertcomponent ($linkindex, \"$id\", properties, out);
  if(tok != null){
    String component;
     while (tok.hasMoreTokens()) {
      component = tok.nextToken();
      %><jsp:include page=\"<%=component%>\"/><%
    }
  }
} %>";
    else return "<% if (hypercms_today.compareTo(\"".$datefrom."\")>=0 && hypercms_today.compareTo(\"".$dateto."\")<=0) {
  tok = insertcomponent (\"$linkindex\", \"$id\", properties, out);
  if(tok != null){
    String component;
     while (tok.hasMoreTokens()) {
      component = tok.nextToken();
      %><jsp:include page=\"<%=component%>\"/><%
    }
  }
} %>";
  }
  elseif ($application == "asp")
  {
    if ($id != "") return "<% If (hypercms_today >= ".$datefrom." and hypercms_today =< ".$dateto.") then
  call insertcomponent ($linkindex, \"$id\")
end if %>";
    else return "<% if (hypercms_today >= ".$datefrom." and hypercms_today =< ".$dateto.") then
  call insertcomponent_wol (\"$linkindex\", \"$id\")
end if  %>";
  }
  elseif ($application == "aspx")
  {
    if ($id != "") return "<% If (hypercms_today >= ".$datefrom." and hypercms_today =< ".$dateto.") then
  call insertcomponent ($linkindex, \"$id\")
end if %>";
    else return "<% if (hypercms_today >= ".$datefrom." && hypercms_today =< ".$dateto.") {
  insertcomponent_wol (\"$linkindex\", \"$id\");
}  %>";
  }
  else return "";
}

// code used for time switched and personalized/conditional component function
function tpl_tspersinsertcomponent ($application, $datefrom, $dateto, $condition, $linkindex, $id)
{
  $application = strtolower (trim ($application));

  if (substr_count ($linkindex, "%comp%") > 0) $linkindex = str_replace ("%comp%", "", $linkindex);

  if ($application == "php")
  {
    if ($id != "") return "<?php if (($condition) && \$hypercms_today >= ".$datefrom." && \$hypercms_today <= ".$dateto.") insertcomponent (\$$linkindex, \"$id\"); ?>";
    else return "<?php if (($condition) && \$hypercms_today >= ".$datefrom." && \$hypercms_today <= ".$dateto.") insertcomponent (\"$linkindex\", \"$id\"); ?>";
  }
  elseif ($application == "jsp")
  {
    if ($id != "") return "<% if (($condition) && hypercms_today.compareTo(\"".$datefrom."\")>=0 && hypercms_today.compareTo(\"".$dateto."\")<=0) {
  tok = insertcomponent ($linkindex, \"$id\", properties, out);
  if(tok != null){
    String component;
     while (tok.hasMoreTokens()) {
      component = tok.nextToken();
      %><jsp:include page=\"<%=component%>\"/><%
    }
  }
} %>";
    else return "<% if (($condition) && hypercms_today.compareTo(\"".$datefrom."\")>=0 && hypercms_today.compareTo(\"".$dateto."\")<=0) {
  tok = insertcomponent (\"$linkindex\", \"$id\", properties, out);
  if(tok != null){
    String component;
     while (tok.hasMoreTokens()) {
      component = tok.nextToken();
      %><jsp:include page=\"<%=component%>\"/><%
    }
  }
} %>";
  }
  elseif ($application == "asp")
  {
    if ($id != "") return "<% if (($condition) and hypercms_today >= ".$datefrom." and hypercms_today =< ".$dateto.") then
  call insertcomponent ($linkindex, \"$id\")
end if %>";
    else return "<% if (($condition) and hypercms_today >= ".$datefrom." and hypercms_today =< ".$dateto.") then
  call insertcomponent_wol (\"$linkindex\", \"$id\")
end if %>";
  }
  elseif ($application == "aspx")
  {
    if ($id != "") return "<% if (($condition) and hypercms_today >= ".$datefrom." && hypercms_today =< ".$dateto.") {
  insertcomponent ($linkindex, \"$id\");
} %>";
    else return "<% if (($condition) && hypercms_today >= ".$datefrom." && hypercms_today =< ".$dateto.") {
  insertcomponent_wol (\"$linkindex\", \"$id\");
} %>";
  }
  else return "";
}

// set variables used for programming, content container name and time stamp
function tpl_globals ($application, $container, $charset)
{
  $application = strtolower (trim ($application));

  // URL rewrting is only supported by PHP, no translations to JSP and ASP
  if ($application == "php")
  {
    return "<?php \$hypercms_contentcontainer = \"$container\"; \$hypercms_today = date(\"YmdHi\", time()); if (!empty(\$hypercms_session) && is_array(\$hypercms_session)) foreach (\$hypercms_session as \$key=>\$value) \$_SESSION[\$key] = \$value; ?>\n";
  }
  elseif ($application == "jsp")
  {
    return "<%@ page import=\"java.io.*\" %>
<%@ page import=\"java.util.*\" %>
<% String hypercms_contentcontainer = \"$container\";
java.util.Date myDate = new Date();
java.text.SimpleDateFormat sdf = new java.text.SimpleDateFormat(\"yyyyMMddHHmm\");
String hypercms_today = sdf.format(myDate);
StringTokenizer tok;  %>\n";
  }
  elseif ($application == "asp")
  {
    return "<%@ Page Language=\"VB\" %>
<% hypercms_contentcontainer = \"$container\"
hypercms_today = CDbl(Year(Date) & Month(Date) & Day(Date))
hypercms_charset = \"$charset\" %>\n";
  }
  elseif ($application == "aspx")
  {
    return "<%@ Page Language=\"C#\" %>
<% hypercms_contentcontainer = \"$container\";
hypercms_today = CDbl(Year(Date) + Month(Date) + Day(Date));
hypercms_charset = \"$charset\"; %>\n";
  }
  else return "";
}

// include livelink function
function tpl_livelink ($application, $abs_publ_config, $site)
{
  $application = strtolower (trim ($application));

  if ($application == "php")
  {
    return "<?php \$publ_config = parse_ini_file ('".$abs_publ_config.$site.".ini'); if (empty (\$hypercms_livelink_set)) {include_once ('".$abs_publ_config."livelink.inc.php'); \$hypercms_livelink_set = true;} ?>\n";
  }
  elseif ($application == "jsp")
  {
    // in JSP components can only be included via http, so livelink.inc.jsp will be loaded each time a object is requested.
    // in Java/JSP functions can be overloaded. this is not possible in PHP.
    // if you have each publication as one webapplication, you will have to locate livelink.inc.php to the application root
    // of each application. otherwise livelink.inc.jsp can not be included. java based web applications mostly don't allow
    // to static include files outside the application root!
    return "<%@ include file=\"/livelink.inc.jsp\" %>
<%
Properties properties = new Properties();

try
{
  properties.load(new FileInputStream(\"".$abs_publ_config.$site.".properties\"));
}

catch (IOException e)
{
  out.println(\"Error loading properties\");
}
%>\n";
  }
  elseif ($application == "asp")
  {
    // in ASP components can only be included via http, so livelink.inc.asp will be loaded each time a object is requested.
    // in ASP/VBScript functions can not be included using absolute paths in #include file.
    // This means you will have to locate livelink.inc.asp to the root of a virtual directory to include it using 'include virtual.
    // otherwise livelink.inc.asp can not be included. therefore you have to create a virtual directory named "include" in IIS.
    return "<% if (IsNull(hypercms_livelink) and hypercms_livelink <> 1) then %><!--#include virtual=\"/include/livelink.inc.asp\"--><% hypercms_livelink = 1\nend if %>
<%
PathINI = \"".$abs_publ_config.$site.".ini\"
Set objFSO = Server.CreateObject(\"Scripting.FileSystemObject\")
Set publ_config = Server.CreateObject(\"Scripting.Dictionary\")

if objFSO.FileExists(PathINI) then
  set fileINI = objFSO.OpenTextFile(PathINI)

  do while fileINI.AtEndOfStream <> true
    lineINI = fileINI.ReadLine

    if (left(lineINI,1)<>\";\" and len(lineINI) > 1) then
      MyArray = split(lineINI, \" = \")
      publ_config.Add MyArray(0), replace(MyArray(1),\"\"\"\",\"\")
    end if
  loop
end if
%>\n";
  }
  elseif ($application == "aspx")
  {
    // in ASP.net (C#) components can only be included via http, so livelink.inc.asp will be loaded each time a object is requested.
    // in ASP/C# functions can not be included using absolute paths in #include file.
    // This means you will have to locate livelink.inc.asp to the root of a virtual directory to include it using 'include virtual.
    // otherwise livelink.inc.asp can not be included. therefore you have to create a virtual directory named "include" in IIS.
    return "<% if (IsNull(hypercms_livelink) && hypercms_livelink!=1) { %><!--#include virtual=\"/include/livelink.inc.aspx\"--><% hypercms_livelink=1;\n} %>
<%
PathINI = \"".$abs_publ_config.$site.".ini\"
Set objFSO = Server.CreateObject(\"Scripting.FileSystemObject\")
Set publ_config = Server.CreateObject(\"Scripting.Dictionary\")

if objFSO.FileExists(PathINI) then
  set fileINI = objFSO.OpenTextFile(PathINI)

  do while fileINI.AtEndOfStream <> true
    lineINI = fileINI.ReadLine

    if (left(lineINI,1)<>\";\" and len(lineINI) > 1) then
      MyArray = split(lineINI, \" = \")
      publ_config.Add MyArray(0), replace(MyArray(1),\"\"\"\",\"\")
    end if
  loop
end if
%>\n";
  }
  else return "";
}

// include publication target configuration and set array based on linkindex file
function tpl_linkindex ($application, $abs_publ_config, $site, $container_id)
{
  global $location;

  $application = strtolower ($application);

  if ($application == "php")
  {
    return "<?php \$hypercms_$container_id = @file (\$publ_config['abs_publ_link'].'".$container_id."'); ?>\n";
  }
  elseif ($application == "jsp")
  {
    return "<%
String abs_publ_link = properties.getProperty(\"abs_publ_link\");
abs_publ_link += \"$container_id\";

File f = new File(abs_publ_link);
Vector hypercms_$container_id = new Vector();

if(f.exists())
{
  FileInputStream fis = new FileInputStream(abs_publ_link);
  int buffersize = (int)f.length();
  byte[] contents = new byte[buffersize];

  long n = fis.read(contents, 0, buffersize);
  fis.close();
  fis = null;

  String data = new String(contents);
  StringTokenizer st = new StringTokenizer(data, \"\\n\");
  String line = \"\";

  while(st.hasMoreTokens())
  {
    line = st.nextToken();
    hypercms_$container_id.addElement(line);
  }
}
%>\n";
  }
  elseif ($application == "asp")
  {
    return "<%
PathLinkIndex = publ_config.item(\"abs_publ_link\") & \"".$container_id."\"
Set objFSO = Server.CreateObject(\"Scripting.FileSystemObject\")
Set hypercms_".$container_id." = Server.CreateObject(\"Scripting.Dictionary\")

if objFSO.FileExists(PathLinkIndex) then
  set fileLinkIndex = objFSO.OpenTextFile(PathLinkIndex)
  i = 0

  do while fileLinkIndex.AtEndOfStream <> true
    lineLinkIndex = fileLinkIndex.ReadLine

    if len(lineLinkIndex) > 1 Then
      hypercms_".$container_id.".Add i, (lineLinkIndex)
      i = i + 1
    end if
  loop
end if
%>\n";
  }
  elseif ($application == "aspx")
  {
    return "<%
PathLinkIndex = publ_config.item(\"abs_publ_link\") & \"".$container_id."\"
Set objFSO = Server.CreateObject(\"Scripting.FileSystemObject\")
Set hypercms_".$container_id." = Server.CreateObject(\"Scripting.Dictionary\")

if objFSO.FileExists(PathLinkIndex) then
  set fileLinkIndex = objFSO.OpenTextFile(PathLinkIndex)
  i = 0

  do while fileLinkIndex.AtEndOfStream <> true
    lineLinkIndex = fileLinkIndex.ReadLine

    if len(lineLinkIndex) > 1 Then
      hypercms_".$container_id.".Add i, (lineLinkIndex)
      i = i + 1
    end if
  loop
end if
%>\n";
  }
  else return "";
}

function tpl_globals_extended ($application, $abs_path_cms, $abs_path_rep, $site, $location)
{
  global $siteaccess;

  $application = strtolower ($application);

  if ($application == "php")
  {
    if (!empty ($siteaccess) && is_array ($siteaccess) && sizeof ($siteaccess) > 0)
    {
      $siteaccessvar = "\$siteaccess = array('".implode ("','", $siteaccess)."');";
    }
    else $siteaccessvar = "\$siteaccess = array('".$site."');";

    return "<?php
\$site = '".$site."';
".$siteaccessvar."
define ('SESSION', 'create');
include ('".$abs_path_cms."config.inc.php');
include_once ('".$abs_path_cms."function/hypercms_api.inc.php');
include_once ('".$abs_path_cms."function/hypercms_tplengine.inc.php');
if (valid_publicationname (\$site)) require (\$mgmt_config['abs_path_data'].'config/'.\$site.'.conf.php');
\$publ_config = parse_ini_file ('".$abs_path_rep."config/".$site.".ini');

\$url_publ_page = \$publ_config['url_publ_page'];
\$abs_publ_page = \$publ_config['abs_publ_page'];
\$url_publ_rep = \$publ_config['url_publ_rep'];
\$abs_publ_rep = \$publ_config['abs_publ_rep'];
\$http_incl = \$publ_config['http_incl'];
\$url_publ_config = \$publ_config['url_publ_config'];
\$abs_publ_config = \$publ_config['abs_publ_config'];
\$url_publ_comp = \$publ_config['url_publ_comp'];
\$abs_publ_comp = \$publ_config['abs_publ_comp'];
\$url_publ_link = \$publ_config['url_publ_link'];
\$abs_publ_link = \$publ_config['abs_publ_link'];
\$url_publ_media = \$publ_config['url_publ_media'];
\$abs_publ_media = \$publ_config['abs_publ_media'];
\$url_publ_tplmedia = \$publ_config['url_publ_tplmedia'];
\$abs_publ_tplmedia = \$publ_config['abs_publ_tplmedia'];

@chdir ('".$location."');

if (!empty (\$_GET['hcms_session']) && is_array (\$_GET['hcms_session']))
{
  foreach (\$_GET['hcms_session'] as \$key => \$value)
  {
    // if session key is allowed (prefix hcms_ must not be used for the name)
    if (\$key != \"\" && substr (\$key, 0, 5) != \"hcms_\")
    {
      \$_SESSION[\$key] = \$value;
    }
  }
}
?>
";
  }
  else return "";
}

// --------------------------------------- transformlink -----------------------------------------------
// function: transformlink()
// input: view of object
// output: view with transformed links for easyedit mode

function transformlink ($viewstore)
{
  // define global variables
  global $site, $location_esc, $page, $ctrlreload, $mgmt_config;

  // arrays holding the possible expression for a hyperreference
  $link_array = array();
  $link_array[0] = " href = \"";
  $link_array[1] = " href =\"";
  $link_array[2] = " href= \"";
  $link_array[3] = " href=\"";
  $link_array[4] = " href = '";
  $link_array[5] = " href ='";
  $link_array[6] = " href= '";
  $link_array[7] = " href='";
  $link_array[8] = ".href = \"";
  $link_array[9] = ".href =\"";
  $link_array[10] = ".href= \"";
  $link_array[11] = ".href=\"";
  $link_array[12] = ".href = '";
  $link_array[13] = ".href ='";
  $link_array[14] = ".href= '";
  $link_array[15] = ".href='";
  $link_array[16] = ".location.replace = \"";
  $link_array[17] = ".location.replace =\"";
  $link_array[18] = ".location.replace= \"";
  $link_array[19] = ".location.replace=\"";
  $link_array[20] = ".location.replace = '";
  $link_array[21] = ".location.replace ='";
  $link_array[22] = ".location.replace= '";
  $link_array[23] = ".location.replace='";
  $link_array[24] = ".location = \"";
  $link_array[25] = ".location =\"";
  $link_array[26] = ".location= \"";
  $link_array[27] = ".location=\"";
  $link_array[28] = ".location = '";
  $link_array[29] = ".location ='";
  $link_array[30] = ".location= '";
  $link_array[31] = ".location='";

  // escape all javascript calls and anchors
  // so they won't be followed by hyperCMS.
  // e.g.: href="javascript:do();" will be escaped to href=hypercms_js:"do();"
  $viewstore = str_ireplace ("\"javascript:", "hypercms_js:\"", $viewstore);
  $viewstore = str_ireplace ("'javascript:", "hypercms_js:'", $viewstore);
  $viewstore = str_replace ("\"#", "hypercms_#:\"", $viewstore);
  $viewstore = str_replace ("'#", "hypercms_#:'", $viewstore);

  foreach ($link_array as $link)
  {
    if (strpos (strtolower ($viewstore), $link) > 0)
    {
      $link_new = $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&ctrlreload=".url_encode($ctrlreload)."&follow=";

      $viewstore = str_ireplace ($link, $link.$link_new, $viewstore);
    }
  }

  // unescape all javascript calls and anchors
  $viewstore = str_replace ("hypercms_js:\"", "\"javascript:", $viewstore);
  $viewstore = str_replace ("hypercms_js:'", "'javascript:", $viewstore);
  $viewstore = str_replace ("hypercms_#:\"", "\"#", $viewstore);
  $viewstore = str_replace ("hypercms_#:'", "'#", $viewstore);

  return $viewstore;
}

// --------------------------------------- followlink -----------------------------------------------
// function: followlink()
// input: publication name, link to follow
// output: prepared input (location plus page) for easyedit mode (buildview) / false on error

function followlink ($site, $follow)
{
  // define global variables
  global $mgmt_config;

  // definition of index file names
  $index_page_array['asp'] = "default.asp";
  $index_page_array['aspx'] = "default.aspx";
  $index_page_array['dhtm'] = "default.htm";
  $index_page_array['jsp'] = "index.jsp";
  $index_page_array['htm'] = "index.htm";
  $index_page_array['html'] = "index.html";
  $index_page_array['xhtml'] = "index.xhtml";
  $index_page_array['php'] = "index.php";

  if (valid_publicationname ($site) && $follow != "")
  {
    // load ini
    $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");

    // absolute link (hardcoded)
    if (substr_count ($follow, "://") > 0)
    {
      if (!empty ($mgmt_config[$site]['url_path_page']) && substr_count ($follow, $mgmt_config[$site]['url_path_page']) > 0)
      {
        $follow = str_replace ($mgmt_config[$site]['url_path_page'], "%page%/".$site."/", $follow);
      }
      elseif (!empty ($publ_config['url_publ_page']) && substr_count ($follow, $publ_config['url_publ_page']) > 0)
      {
        $follow = str_replace ($publ_config['url_publ_page'], "%page%/".$site."/", $follow);
      }
      elseif (!empty ($mgmt_config['url_path_comp']) && substr_count ($follow, $mgmt_config['url_path_comp']) > 0)
      {
        $follow = str_replace ($mgmt_config['url_path_comp'], "%comp%/", $follow);
      }
      elseif (!empty ($publ_config['url_publ_comp']) && substr_count ($follow, $publ_config['url_publ_comp']) > 0)
      {
        $follow = str_replace ($publ_config['url_publ_comp'], "%comp%/", $follow);
      }
    }
    // page link
    elseif (substr ($follow, 0, 1) == "/")
    {
      // extract path (URI) without domain from URL
      if (!empty ($publ_config['url_publ_page']) && strpos ($publ_config['url_publ_page'], "://") > 0)
      {
        $pos = strpos ($publ_config['url_publ_page'], "/", strpos ($publ_config['url_publ_page'], "://") + 3);
        $rooturl = substr ($publ_config['url_publ_page'], 0, $pos);
      }
      elseif (!empty ($publ_config['url_publ_page']) && substr ($publ_config['url_publ_page'], -1) == "/") $rooturl = substr ($publ_config['url_publ_page'], 0, -1);
      else $rooturl = $publ_config['url_publ_page'];

      $follow = $rooturl.$follow;
      $follow = str_replace ($publ_config['url_publ_page'], "%page%/".$site."/", $follow);
    }

    // deconvertpath
    $follow_abs = deconvertpath ($follow, "file");

    // try to locate index page if not given
    if (!is_file ($follow_abs))
    {
      foreach ($index_page_array as $index_page)
      {
        if (is_file ($follow_abs.$index_page))
        {
          // if object has no slash at the end
          if (substr ($follow, -1) != "/") $follow = $follow."/";

          $follow = $follow.$index_page;
          break;
        }
      }
    }

    return $follow;
  }
  else return false;
}

// --------------------------------------- errorhandler -----------------------------------------------
// function: errorhandler ()
// input: source code, return code, error identifier
// output: error message and view of the code with line identifiers

function errorhandler ($source_code, $return_code, $error_identifier)
{
  // error handling
  if (strpos ("_".$return_code, $error_identifier) > 0 && (strpos ("_".$return_code, " on line ") > 0 || strpos ("_".$return_code, "TCPDF ERROR:") > 0))
  {
    $source_code = str_replace ("<", "&lt;", $source_code);
    $source_code = str_replace (">", "&gt;", $source_code);
    $source_code = str_replace (" ", "&nbsp;", $source_code);
    $source_code_array = explode ("\n", $source_code);

    $source_code = "";
    $i = 0;

    foreach ($source_code_array as $buffer)
    {
      $i++;
      $source_code .= "<b>".$i."</b>&nbsp;&nbsp;".$buffer."<br />\n";
    }

    // clean return code for PHP notice
    if (strlen ($return_code) > 800)
    {
      if (strpos ("_".$return_code, "Notice: ") > 0 && strpos ("_".$return_code, " on line ", strpos ($return_code, "Notice: ")) > 0)
      {
        $start = strpos ($return_code, "Notice: ");
        $length = strpos ($return_code, PHP_EOL, strpos ($return_code, "Notice: ")) - $start;
        $return_code = substr ($return_code, $start, $length);
      }
    }

    return "
    <!-- hyperCMS:ErrorCodeBegin -->
    <span style=\"font-size:11px; font-family:Arial, Helvetica, sans-serif;\">
      <span style=\"color:red;\">".$return_code."</span><br />
      ".$source_code."
    </span>
    <!-- hyperCMS:ErrorCodeEnd -->";
  }
  else return $return_code;
}

// --------------------------------------- viewinclusions -----------------------------------------------
// function: viewinclusions()
// input: view of object, hypertag to create view of inlcuded objects, view parameter, application, character set used (optional)
//        view-parameter explanation:
//        "template or any other word": the standard text (in table) will be included for the view
//        "preview": preview of the content of the included file
//        "publish": view the content of the included file as it is (for publishing)
// output: view of the content including the content of included objects
// requirements: $mgmt_config (set as global variables inside function)

// generate view of included objects
function viewinclusions ($site, $viewstore, $hypertag, $view, $application, $charset="UTF-8")
{
  // define global variables
  global $user, $mgmt_config, $location, $hcms_lang, $lang;

  // change to current location
  if ($location != "") @chdir ($location);

  // create view of included template files
  // check if page/code has inclusions
  if (valid_publicationname ($site) && !empty ($viewstore) && !empty ($hypertag)  && substr_count (strtolower ($viewstore), strtolower ($hypertag)) > 0 && !empty ($view) && !empty ($application))
  {
    // get file name
    $include_file = getattribute ($hypertag, "file");

    if ($include_file != false && $include_file != "")
    {
      // --------------------------------------- preview -------------------------------------------------
      // $view = preview means preview the content of the included file
      if ($view == "preview")
      {
        // load external data for inclusion:
        // template include
        if (substr_count (strtolower ($hypertag), "hypercms:tplinclude") == 1)
        {
          $result = loadtemplate ($site, $include_file);

          // on error
          if (empty ($result['result']))
          {
            $includedata = "
            <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
              <tr>
                <td><span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>".getescapedtext ($hcms_lang['template'][$lang], $charset, $lang)." '".specialchr_decode (getobject ($include_file))."' ".getescapedtext ($hcms_lang['that-should-be-included-is-missing'][$lang], $charset, $lang)."<br />".getescapedtext ($hcms_lang['please-upload-the-template'][$lang], $charset, $lang)."</b></span></td>
              </tr>
            </table>";
          }
          elseif (!empty ($result['content']))
          {
            $temp = getcontent ($result['content'], "<content>", true);
            $includedata = $temp[0];
          }
        }
        // file include
        elseif (substr_count (strtolower ($hypertag), "hypercms:fileinclude") == 1)
        {
          // file include (via HTTP)
          if (substr_count ($include_file, "://") == 1)
          {
            $includedata = @file_get_contents ($include_file);
          }
          else $includedata = tpl_compinclude ($application, $include_file, $mgmt_config['os_cms']);

          if ($includedata == false)
          {
            $includedata = "
            <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
              <tr>
                <td><span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>".getescapedtext ($hcms_lang['component'][$lang], $charset, $lang)." '".specialchr_decode (getobject ($include_file))."' ".getescapedtext ($hcms_lang['will-be-included-here-for-publishing'][$lang], $charset, $lang)."<br />".getescapedtext ($hcms_lang['please-dont-forget-to-publish-the-component'][$lang], $charset, $lang)."</b></span></td>
              </tr>
            </table>";
          }
        }
      }
      // ----------------------------------------- publish ---------------------------------------------
      // $view = publish means view the content of the included file as it is (for publishing)
      elseif ($view == "publish")
      {
        // load external data for inclusion:
        // template include
        if (@substr_count (strtolower ($hypertag), "hypercms:tplinclude") == 1)
        {
          $result = loadtemplate ($site, $include_file);

          if (!empty ($result['content']))
          {
            $temp = getcontent ($result['content'], "<content>", true);
            if (!empty ($temp[0])) $includedata = $temp[0];
          }
        }
        // php include
        elseif (@substr_count (strtolower ($hypertag), "hypercms:fileinclude") == 1)
        {
          $includedata = tpl_compinclude ($application, $include_file, $mgmt_config['os_cms']);
        }
      }
      // -------------------------------------------- template ----------------------------------------------
      // show alternative text if view is set to "no"
      else
      {
        // load external data for inclusion:
        // template include
        if (@substr_count (strtolower ($hypertag), "hypercms:tplinclude") == 1)
        {
          $result = loadtemplate ($site, $include_file);

          // on error
          if (empty ($result['result']))
          {
            $includedata = "
            <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
              <tr>
                <td><span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>".getescapedtext ($hcms_lang['template'][$lang], $charset, $lang)." '".specialchr_decode (getobject ($include_file))."' ".getescapedtext ($hcms_lang['will-be-included-here-for-publishing'][$lang], $charset, $lang)."<br />".getescapedtext ($hcms_lang['please-upload-the-template'][$lang], $charset, $lang)."</b></span></td>
              </tr>
            </table>";
          }
          else
          {
            $includedata = "
            <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
              <tr>
                <td><span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>".getescapedtext ($hcms_lang['template'][$lang], $charset, $lang)." '".specialchr_decode (getobject ($include_file))."' ".getescapedtext ($hcms_lang['will-be-included-here-for-publishing'][$lang], $charset, $lang)."</b></span></td>
              </tr>
            </table>";
          }
        }
        // file include
        elseif (@substr_count (strtolower ($hypertag), "hypercms:fileinclude") == 1)
        {
          $includedata = "
          <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
            <tr>
              <td><span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>".getescapedtext ($hcms_lang['component'][$lang], $charset, $lang)." '".specialchr_decode (getobject ($include_file))."' ".getescapedtext ($hcms_lang['will-be-included-here-for-publishing'][$lang], $charset, $lang)."<br />".getescapedtext ($hcms_lang['please-dont-forget-to-publish-the-component'][$lang], $charset, $lang)."</b></span></td>
            </tr>
          </table>";
        }
      }

      // ---------------------------------------- build view -----------------------------------------
      if (!empty ($hypertag))
      {
        // include external data
        $viewstore = str_replace ($hypertag, $includedata, $viewstore);

        // recursive inclusions of file includes
        if (@substr_count (strtolower ($includedata), "hypercms:fileinclude") > 0)
        {
          $hypertag_array = gethypertag ($includedata, "fileinclude", 0);

          if (is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
          {
            foreach ($hypertag_array as $hypertag)
            {
              $viewstore = viewinclusions ($site, $viewstore, $hypertag, $view, $application, $charset);
            }
          }
        }

        // recursive inclusions of template includes
        if (@substr_count (strtolower ($includedata), "hypercms:tplinclude") > 0)
        {
          $hypertag_array = gethypertag ($includedata, "tplinclude", 0);

          if (is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
          {
            foreach ($hypertag_array as $hypertag)
            {
              $viewstore = viewinclusions ($site, $viewstore, $hypertag, $view, $application, $charset);
            }
          }
        }
      }
    }
  }

  return $viewstore;
}

// --------------------------------- buildview -------------------------------------------
// function: buildview()
// input: publication name [string], location [string], object name [string], user name [string], view parameter [string] (optional), reload workplace control frame and add html & body tags if missing [yes,no] (optional),
//        template name [string] (optional), container name [string] (optional),
//        force category to use different location path [page,comp] (optional), execute_code [boolean] (optional), recognize faces service in use [boolean] (optional)
// output: result array with view of the content / false on error
//
// requires:
// buildview requires the following functions and files: config.inc.php, hypercms_api.inc.php
// these functions must be inluded before you can use buildview.
// to be able to save the content, the secure token mus be provided to this function.
//
// description:
// buildview parameter may have the following values:
// "formedit": use form for content editing
// "formmeta": use form for content viewing only for meta informations (tag-type must be meta)
// "formlock": use form for content viewing
// "cmsview": view of page based on template, includes hyperCMS specific code (buttons)
// "inlineview": view of page based on template, includes hyperCMS specific code (buttons) and inline text editing
// "publish": view of page for publishing based on template without CMS specific code (editing)
// "unpublish": execution of the code for unpublishing an object
// "preview": view of page based on template for preview (inactive hyperlinks) without CMS specific code (buttons)
// "template": view of template based on template for preview (inactive hyperlinks) without CMS specific code (buttons)

function buildview ($site, $location, $page, $user, $buildview="template", $ctrlreload="no", $template="", $container="", $force_cat="", $execute_code=true, $recognizefaces_service=false)
{
  global $container_collection,
         $eventsystem,
         $db_connect,
         $mgmt_config,
         $siteaccess, $adminpermission, $setlocalpermission, $token, $is_mobile, $is_iphone, $viewportwidth,
         $mgmt_lang_shortcut_default, $hcms_charset, $hcms_lang_name, $hcms_lang_shortcut, $hcms_lang_codepage, $hcms_lang_date, $hcms_lang, $lang;

  $error = array();

  // define default values for the result array
  $cat = "";
  $viewstore = "";
  $wf_role = "";
  $wf_token = "";
  $contentfile = "";
  $contentdata = "";
  $templatefile = "";
  $templatedata = "";
  $templateext = "";
  $application = "";
  $name_orig = "";
  $filetype = "";
  $add_onload = "";
  $add_constraint = "";
  $add_submittext = "";
  $add_submitlanguage = "";
  $add_submitlink = "";
  $add_submitcomp = "";
  $show_meta = false;

  // set default view values
  $valid_views = array ("formedit", "formmeta", "formlock", "cmsview", "inlineview", "publish", "unpublish", "preview", "template");

  // validate required input parameters
  if ($buildview == "" || !in_array ($buildview, $valid_views) || !valid_publicationname ($site)) return false;
  elseif ($buildview == "template" && (!valid_publicationname ($site) || !valid_objectname ($user))) return false;
  elseif ($buildview != "template" && (!valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page) || !valid_objectname ($user))) return false;

  // check for temp view directory (new since version 5.6.2)
  if (!is_dir ($mgmt_config['abs_path_view']))
  {
    mkdir ($mgmt_config['abs_path_view'], $mgmt_config['fspermission']);
    file_put_contents ($mgmt_config['abs_path_view'].".htaccess", "php_flag display_errors 1\nOrder allow,deny\nAllow from all");
  }

  // check viewport width
  if ($is_mobile && $viewportwidth > 0) $maxwidth = $viewportwidth * 1.6;
  else $maxwidth = 0;

  // correct field width for mobile devices
  if ($maxwidth > 0 && 350 > $maxwidth) $fieldwidth = $maxwidth;
  else $fieldwidth = 350;

  // validate publication access for all views except for publish and unpublish and not a face recogntion service
  if ($buildview != "publish" && $buildview != "unpublish" && empty ($recognizefaces_service))
  {
    // validate inheritance if site is outside of users publication access scope
    $valid_publicationaccess = checkpublicationpermission ($site, false);

    // no access allowed
    if ($valid_publicationaccess == false)
    {
      return false;
    }
    // only access by inheritance
    elseif ($valid_publicationaccess == "inherited")
    {
      // access allowed but no edit permissions
      if ($buildview == "cmsview" || $buildview == "inlineview") $buildview = "preview";
      elseif ($buildview == "formedit" || $buildview == "formmeta") $buildview = "formlock";
    }
  }

  // format file extensions
  require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

  // publication management config
  if ((empty ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) && valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
  {
    require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
  }

  // create unique ID for temporary pageview file
  $unique_id = uniqid ();

  // get local permissions of user
  $ownergroup = array();
  $setlocalpermission = array();

  if ($buildview != "template")
  {
    // define category if undefined
    if ($force_cat == "") $cat = getcategory ($site, $location);
    else $cat = $force_cat;

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // convert location
    $location = deconvertpath ($location, "file");

    // check for folder and correct location
    if ($page != ".folder" && is_dir ($location.$page))
    {
      $location = $location.$page."/";
      $page = ".folder";
    }

    // deconvert location
    $location_esc = convertpath ($site, $location, $cat);

    $ownergroup = accesspermission ($site, $location, $cat);
    $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

    // set required permissions for the service user (required for service savecontent, function buildview and function showmedia)
    if (!empty ($recognizefaces_service) && !empty ($user) && is_facerecognition ($user))
    {
      $setlocalpermission['root'] = 1;
      $setlocalpermission['create'] = 1;
    }
  }

  // get browser info
  $user_client = getbrowserinfo ();

  // ----------------------------------- build view of page -----------------------------------------

  // include publication target settings
  if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_rep']."config/".$site.".ini")) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");

  // eventsystem
  if (!empty ($eventsystem['oneditobject_pre']) && $eventsystem['hide'] == 0 && ($buildview == "cmsview" || $buildview == "inlineview"))
  {
    // include hyperCMS Event System
    @include_once ($mgmt_config['abs_path_data']."eventsystem/hypercms_eventsys.inc.php");
    oneditobject_pre ($site, $cat, $location, $page, $user);
  }

  // resolve variables from array for template programming in hyperCMS-Scripts
  // deprected but still in use for older versions
  $url_publ_page = $publ_config['url_publ_page'];
  $abs_publ_page = $publ_config['abs_publ_page'];
  $url_publ_rep = $publ_config['url_publ_rep'];
  $abs_publ_rep = $publ_config['abs_publ_rep'];
  $http_incl = $publ_config['http_incl'];
  $url_publ_config = $publ_config['url_publ_config'];
  $abs_publ_config = $publ_config['abs_publ_config'];
  $url_publ_comp = $publ_config['url_publ_comp'];
  $abs_publ_comp = $publ_config['abs_publ_comp'];
  $url_publ_link = $publ_config['url_publ_link'];
  $abs_publ_link = $publ_config['abs_publ_link'];
  $url_publ_media = $publ_config['url_publ_media'];
  $abs_publ_media = $publ_config['abs_publ_media'];
  $url_publ_tplmedia = $publ_config['url_publ_tplmedia'];
  $abs_publ_tplmedia = $publ_config['abs_publ_tplmedia'];

  if (in_array ($buildview, array ("formedit", "formmeta", "formlock", "cmsview", "inlineview", "publish", "unpublish", "preview")))
  {
    // collect object info and get associated template and content
    // get file info
    $fileinfo = getfileinfo ($site, $location.$page, $cat);
    $filename = $fileinfo['file'];
    $name_orig = $fileinfo['name'];
    $filetype = $fileinfo['type'];
    $page = correctfile ($location, $page, $user);
    $objectview = Null;

    if (valid_locationname ($location) && valid_objectname ($page))
    {
      // check for security token
      if ($token == "") $token = createtoken ($user);

      $pagedata = loadfile ($location, $page);

      // get name of content file
      $contentfile = getfilename ($pagedata, "content");

      // get object name
      $namefile = getfilename ($pagedata, "name");

      // get media file
      $mediafile = getfilename ($pagedata, "media");

      // get container id
      $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));

      // load given input content container if container ID matches with the one of the object
      if ($container != "" && substr_count ($container, $container_id) == 1)
      {
        $contentfile = $container;

        // get object info of version
        $objectinfo_version = getobjectinfo ($site, $location, $page, $user, $container);

        if (!empty ($objectinfo_version['name'])) $name_orig = $objectinfo_version['name'];
        if (!empty ($objectinfo_version['media'])) $mediafile = $objectinfo_version['media'];
      }

      // get template file
      if (isset ($template) && valid_objectname ($template)) $templatefile = $template;
      else $templatefile = getfilename ($pagedata, "template");

      // ---------------------- load version for history view ----------------------------
      if (is_file ($mgmt_config['abs_path_temp'].session_id().".dates.php"))
      {
        include ($mgmt_config['abs_path_temp'].session_id().".dates.php");

        // allow only preview mode for history view
        $buildview = "preview";
        $container_collection = "live";

        // time machine date is given
        if ($date_content != "")
        {
          $date_content = str_replace ("-", "", $date_content);

          // find the correct content container
          $versiondir = getcontentlocation ($container_id, 'abs_path_content');
          $scandir = scandir ($versiondir);
          $files_v = array();

          if ($scandir)
          {
            foreach ($scandir as $entry)
            {
              if ($entry != "." && $entry != ".." && @!is_dir ($versiondir.$entry) && (@preg_match ("/".$contentfile.".v_/i", $entry) || @preg_match ("/_hcm".$container_id."/i", $entry)))
              {
                $files_v[] = $entry;
              }
            }
          }

          if (is_array ($files_v) && sizeof ($files_v) > 0)
          {
            sort ($files_v);
            reset ($files_v);

            foreach ($files_v as $file_v)
            {
              $file_v_ext = substr (strrchr ($file_v, "."), 3);
              $date_v = substr ($file_v_ext, 0, strpos ($file_v_ext, "_"));
              $date_v = str_replace ("-", "", $date_v);

              if ($date_v > $date_content)
              {
                if ($file_v_buffer != "") $contentfile = $file_v_buffer;
                else $contentfile = $file_v;
                break;
              }

              $file_v_buffer = $file_v;
            }
          }

          $files_v = null;
          $file_v_buffer = null;
        }

        if ($date_template != "")
        {
          $date_template = str_replace ("-", "", $date_template);

          // find the correct template
          $versiondir = $mgmt_config['abs_path_template'].$site."/";

          if (!is_file ($versiondir.$templatefile))
          {
            $inherit_db = inherit_db_read ();
            $parent_array = inherit_db_getparent ($inherit_db, $site);

            if (is_array ($parent_array))
            {
              sort ($parent_array);
              reset ($parent_array);

              foreach ($parent_array as $parent)
              {
                if (is_file ($mgmt_config['abs_path_template'].$parent."/".$template))
                {
                  $versiondir = $mgmt_config['abs_path_template'].$parent."/";
                  break;
                }
              }
            }
          }

          $files_v = array();
          $scandir = scandir ($versiondir);

          if ($scandir)
          {
            foreach ($scandir as $entry)
            {
              if ($entry != "." && $entry != ".." && @!is_dir ($versiondir.$entry) && @preg_match ("/".$templatefile.".v_/i", $entry))
              {
                $files_v[] = $entry;
              }
            }
          }

          if (is_array ($files_v) && sizeof ($files_v) > 0)
          {
            sort ($files_v);
            reset ($files_v);

            foreach ($files_v as $file_v)
            {
              $file_v_ext = substr (strrchr ($file_v, "."), 3);
              $date_v = substr ($file_v_ext, 0, strpos ($file_v_ext, "_"));
              $date_v = str_replace ("-", "", $date_v);

              if ($date_v > $date_template)
              {
                if ($file_v_buffer != "") $templatefile = $file_v_buffer;
                else $templatefile = $file_v;
                break;
              }

              $file_v_buffer = $file_v;
            }
          }

          $files_v = null;
          $file_v_buffer = null;
        }
      }
    }
  }
  elseif ($buildview == "template")
  {
    if (isset ($template) && $template != "") $templatefile = $template;
    else $templatefile = false;
  }

  // load content container and template
  if ((isset ($contentfile) && $contentfile != false && isset ($templatefile) && $templatefile != false) || $template != "")
  {
    // =================================================== load template =================================================
    // load associated template xml file and read information
    $result = loadtemplate ($site, $templatefile);

    // template does not exist, assign default template and try again
    if ($result == false)
    {
      $templatefile = "default.meta.tpl";
      $result = loadtemplate ($site, $templatefile);
    }

    if (is_array ($result))
    {
      $templatedata = $result['content'];
      $templatesite = $result['publication'];

      $bufferdata = getcontent ($templatedata, "<extension>");
      $templateext = $bufferdata[0];

      $bufferdata = getcontent ($templatedata, "<application>");
      $application = $bufferdata[0];

      $bufferdata = getcontent ($templatedata, "<content>", true);
      $templatedata = $bufferdata[0];
    }
    else
    {
      echo showinfopage ($hcms_lang['the-template-holds-no-information'][$lang], $lang);
      return false;
    }

    // add newline at the begin to correct errors in tag-search
    $templatedata = "\n".$templatedata;

    // deconvert all pathes in template (do not transform special characters)
    $templatedata = deconvertpath ($templatedata, "file", false);

    // set viewstore initially
    $viewstore = $templatedata;

    // get view from template
    $hypertag_array = gethypertag ($viewstore, "objectview", 0);

    if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
    {
      foreach ($hypertag_array as $hypertag)
      {
        if ($buildview != "publish" && $buildview != "unpublish" && $buildview != "preview" && $buildview != "template")
        {
          // last objectview entry will set the view option
          $objectview = $buildview = getattribute ($hypertag, "name");
        }

        // remove tags
        $viewstore = str_replace ($hypertag, "", $viewstore);
      }
    }

    // =============================== get content-type and character set ===============================

    $result = getcharset ($site, $viewstore);

    $contenttype = $result['contenttype'];
    $hcms_charset = $charset = $result['charset'];

    // set default character set
    if (!empty ($charset)) ini_set ('default_charset', $charset);

    // get content-type from component template, if set
    $hypertag_array = gethypertag ($viewstore, "compcontenttype", 0);

    // remove tag
    if (is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
    {
      foreach ($hypertag_array as $hypertag)
      {
        $viewstore = str_replace ($hypertag, "", $viewstore);
      }

      $compcontenttype = true;
    }

    // =============================== reset view and charset for media assets ===============================

    // if object is media file or folder
    if ((!empty ($mediafile) && $application != "generator") || $page == ".folder")
    {
      // reset view
      if ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "formmeta") $buildview = "formedit";
      elseif ($buildview == "preview" || $buildview == "template") $buildview = "formlock";

      // reset charset
      $hcms_charset = $charset = "UTF-8";
    }

    // disable form fields
    if ($buildview == "formlock") $disabled = " disabled=\"disabled\"";
    else $disabled = "";

    // ============================================== meta data templates ==============================================

    // remove tags in meta-data templates
    if (strpos ($templatefile, ".meta.tpl") > 0 && $buildview == "template")
    {
      // remove all tags
      $viewstore = strip_tags ($viewstore);
      $viewstore = str_replace ("document.cookie", "", $viewstore);

      // character set must be UTF-8 for media files
      if (!empty ($mediafile))
      {
        $charset = "UTF-8";
        $contenttype = "text/html; charset=".$charset;
      }

      // for the template view
      $viewstore = "<!DOCTYPE html>
  <html>
  <head>
  <title>hyperCMS</title>
  <meta charset=\"".$charset."\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />
  </head>
  <body class=\"hcmsWorkplaceGeneric\">
  <div class=\"hcmsWorkplaceFrame\">".$viewstore."</div>
  </body>
  </html>";
    }

    // ============================================== included files ==============================================

    // ---------------- file include -------------------
    // create view for included content

    // get all hyperCMS tags
    $hypertag_array = gethypertag ($viewstore, "fileinclude", 0);

    if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
    {
      // loop for each hyperCMS tag found in template
      foreach ($hypertag_array as $hypertag)
      {
        if ($buildview == "preview" || $buildview == "cmsview" || $buildview == "inlineview" || $buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
        {
          // create view of php file inclusions
          $inclview = "preview";
        }
        elseif ($buildview == "template")
        {
          // create view of php file inclusions
          $inclview = "template";
        }
        elseif ($buildview == "publish")
        {
          // create view of php file inclusions
          $inclview = "publish";
        }
        elseif ($buildview == "unpublish")
        {
          // create view of php file inclusions
          $inclview = "unpublish";
        }
        else $inclview = "preview";

        $viewstore = viewinclusions ($site, $viewstore, $hypertag, $inclview, $application, $charset); 
      }
    }

    // ---------------- template include -------------------
    // create view for included content

    // get all hyperCMS tags
    $hypertag_array = gethypertag ($viewstore, "tplinclude", 0);

    if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
    {
      // loop for each hyperCMS tag found in template
      foreach ($hypertag_array as $hypertag)
      {
        if ($buildview == "preview" || $buildview == "cmsview" || $buildview == "inlineview" || $buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
        {
          // create view of php file inclusions
          $inclview = "preview";
        }
        elseif ($buildview == "template")
        {
          // create view of php file inclusions
          $inclview = "preview";
        }
        elseif ($buildview == "publish")
        {
          // create view of php file inclusions
          $inclview = "publish";
        }
        elseif ($buildview == "unpublish")
        {
          // create view of php file inclusions
          $inclview = "unpublish";
        }
        else $inclview = "preview";

        $viewstore = viewinclusions ($site, $viewstore, $hypertag, $inclview, $application, $charset);
      }
    }

    // ================================= XML-document in template view ===================================
    if ($application == "xml" && ($buildview == "template" || $buildview == "cmsview" || $buildview == "inlineview") && @substr_count (strtolower($viewstore), "[hypercms:scriptbegin") == 0)
    {
      // if template is a XML-document escape all < and > and add <br />
      $viewstore = str_replace ("<![CDATA[", "&lt;![CDATA[", $viewstore);
      $viewstore = str_replace ("]]>", "]]&gt;", $viewstore);
      $viewstore = str_replace ("<", "<span style=\"color:#0000FF; font-size:11px; font-family:Arial, Helvetica, sans-serif;\">&lt;", $viewstore);
      $viewstore = str_replace (">", "&gt;</span>", $viewstore);
      $viewstore = str_replace ("\n", "<br />", $viewstore);
    }

    // ========================================= define database connectivity =============================================
    // get db_connect
    // no multiple values are allowed, in that case only the first valid db-connect value will be the valid one

    // get all hyperCMS tags
    $hypertag_array = gethypertag ($viewstore, "dbconnect", 0);

    if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
    {
      foreach ($hypertag_array as $hypertag)
      {
        $db_connect = getattribute ($hypertag, "file");
        $viewstore = str_replace ($hypertag, "", $viewstore);

        if (!empty ($db_connect) && is_file ($mgmt_config['abs_path_data']."db_connect/".$db_connect))
        {
          // include db_connect function
          @include_once ($mgmt_config['abs_path_data']."db_connect/".$db_connect);

          // set flag
          $db_connect_incl = true;

          // break after entry found
          break;
        }
        else $db_connect = null;
      }
    }

    // check if config db_connect will be overruled by template db_connect
    if (isset ($db_connect) && $db_connect != "" && (!isset ($db_connect_incl) || $db_connect_incl != true) && is_file ($mgmt_config['abs_path_data']."db_connect/".$db_connect))
    {
      @include_once ($mgmt_config['abs_path_data']."db_connect/".$db_connect);
    }

    // =========================================== load content container ==============================================
    // define container collection
    if ($container_collection != "live") $container_collection = "work";

    // load live container from filesystem
    // get user who checked the object out and read associated working content container file

    $usedby = "";
    
    if ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview" || $buildview == "publish" || $buildview == "unpublish" || $buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
    {
      // load working container for none-live-view
      if ($container_collection != "live" && $container == "")
      {
        // try to load container (also restore a deleted container)
        $contentdata = loadcontainer ($contentfile, "work", $user);

        // get container name
        $result_containername = getcontainername ($contentfile);

        if (!empty ($result_containername['container']))
        {
          $contentfile_load = $result_containername['container'];
          $usedby = $result_containername['user'];
        }

        // check container content and try to reload
        if ($contentdata == false) $contentdata = loadcontainer ($contentfile_load, "version", $user);
      }
      // else load given container
      else
      {
        $contentdata = loadcontainer ($contentfile, "version", $user);
        $usedby = "";
      }
    }

    // define popup message if container is locked by another user
    if ($usedby != "" && $usedby != $user) $bodytag_popup = "alert(hcms_entity_decode('".$hcms_lang['object-is-checked-out'][$lang]."\\r".$hcms_lang['by-user'][$lang]." \'".$usedby."\''));";
    else $bodytag_popup = "";

    // ============================================ workflow ================================================

    // no service
    if (empty ($recognizefaces_service) && $buildview != "template")
    {
      $result_workflow = checkworkflow ($site, $location, $page, $cat, $contentfile, $contentdata, $buildview, $viewstore, $user);

      $viewstore = $result_workflow['viewstore'];
      $buildview = $result_workflow['viewname'];
      $wf_id = $result_workflow['wf_id'];
      $wf_role = $result_workflow['wf_role'];
      $wf_token = $result_workflow['wf_token'];

      // redefine view if content container is locked by another user (popup message will appear)
      // and check access and edit permissions to correct view
      if (
          ($usedby != "" && $usedby != $user) ||
          ($setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1)
        )
      {
        // user has no permissions to edit the content
        if ($buildview == "cmsview" || $buildview == "inlineview") $buildview = "preview";
        elseif ($buildview == "formedit" || $buildview == "formmeta") $buildview = "formlock";
      }

      // disable form fields if workflow set buildview
      if ($buildview == "formlock") $disabled = " disabled=\"disabled\"";
      else $disabled = "";
    }
    // no workflow for services
    else $wf_role = 5;

    // check workflow role
    if (
         ($wf_role >= 1 && $wf_role <= 4) ||
         ($wf_role == 5 && !empty ($mgmt_config[$site]['dam']) && ($user == "sys" || (isset ($setlocalpermission['root']) && $setlocalpermission['root'] == 1))) ||
         ($wf_role == 5 && empty ($mgmt_config[$site]['dam'])) ||
         $buildview == "template"
       )
    {
      //  =================================================== view switcher  ===================================================

      if (empty ($objectview) && ($buildview == "cmsview" || $buildview == "inlineview"))
      {
        if ($buildview == "cmsview")
        {
          $switcher_title = getescapedtext ($hcms_lang['activate-inline-mode'][$lang], $charset, $lang);
          $switcher_view = 'inlineview';
        }
        else
        {
          $switcher_title = getescapedtext ($hcms_lang['deactivate-inline-mode'][$lang], $charset, $lang);
          $switcher_view = 'cmsview';
        }

        $headstoreview = "<img src=\"".getthemelocation()."img/edit_easyedit.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."page_view.php?view=".url_encode($switcher_view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."';\" style=\"display:inline !important; height:32px; padding:0; margin:0; border:0; vertical-align:top; text-align:left; cursor:pointer; z-index:9999999;\" alt=\"".$switcher_title."\" title=\"".$switcher_title."\">";
      }
      else
      {
        $headstoreview = "";
      }

      // =================================================== session language setting ===================================================

      // get all hyperCMS tags
      $hypertag_array = gethypertag ($viewstore, "language", 0);

      $language_sessionvalues_array = Null;
      $label = Null;
      $constraint_array = array();
      $language_sessionvar = Null;
      $headstorelang = Null;
      $headstore = Null;
      $scriptarray = Null;

      if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
      {
        // loop for each hyperCMS tag found in template
        $i = 0;

        foreach ($hypertag_array as $key => $hypertag)
        {
          // counter for media name
          $i++;

          // get tag name
          $hypertagname = gethypertagname ($hypertag);

          // get session variable name
          $language_sessionvar = getattribute ($hypertag, "name");

          // get values
          $language_sessionvalues = getattribute ($hypertag, "list");

          $language_sessionvalues_array = Null;

          if (substr_count ($language_sessionvalues, "|") > 0) $language_sessionvalues_array = explode ("|", $language_sessionvalues);
          else $language_sessionvalues_array = array ($language_sessionvalues);

          // get session variable name
          $language_sessionlabels = getattribute ($hypertag, "label");

          $language_sessionlabels_array = Null;

          if ($language_sessionlabels != "")
          {
            if (substr_count ($language_sessionlabels, ";") > 0) $language_sessionlabels = str_replace (";", "|", $language_sessionlabels);

            if (substr_count ($language_sessionlabels, "|") > 0) $language_sessionlabels_array = explode ("|", $language_sessionlabels);
            else $language_sessionlabels_array = array ($language_sessionlabels);
          }

          // get default value
          $session_defaultvalue = getattribute ($hypertag, "default");

          // set default value in session if not already set (DO NOT USE function setsession, since it is not a system session variable!)
          if (empty ($_SESSION[$language_sessionvar])) $_SESSION[$language_sessionvar] = $session_defaultvalue;

          if ($buildview != "template")
          {
            // include CMS head edit buttons
            $headstorelang = "<select style=\"background:#FFFFFF url('".getthemelocation()."img/edit_language.png') no-repeat left top !important; margin:0 !important; padding:1px 1px 2px 30px !important; width:204px !important; height:32px !important; color:#000000 !important; font-family:Arial, Helvetica, sans-serif !important; font-size:18px !important; font-weight:normal !important; vertical-align:top !important; border:0 !important; z-index:90000 !important;\" title=\"".getescapedtext ($hcms_lang['language'][$lang], $charset, $lang)."\" onchange=\"document.location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."page_view.php?hcms_session[".$language_sessionvar."]=' + this.options[this.selectedIndex].value + '&view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."';\">";

            if (is_array ($language_sessionvalues_array))
            {
              $i = 0;

              foreach ($language_sessionvalues_array as $value)
              {
                // get label for language if it exists
                if ($language_sessionlabels_array[$i] != "") $label = $language_sessionlabels_array[$i];
                else $label = $value;

                $headstorelang .= "<option value=\"".$value."\"";

                if (isset ($_SESSION[$language_sessionvar]) && $_SESSION[$language_sessionvar] == $value) $headstorelang .= " selected=\"selected\"";
                elseif (isset ($_SESSION[$language_sessionvar]) && $_SESSION[$language_sessionvar] == "" && $session_defaultvalue == $value) $headstorelang .= " selected=\"selected\"";

                $headstorelang .= ">".$label."</option>";

                $i++;
              }
            }

            $headstorelang .= "</select>";

            // use JS to set the language selectbox after the document has been loaded in order to avoid manipulation by Js frameworks
            $bodytag_selectlang = "function hcms_headstorelang ()
{
  var hcms_headstorelang='".str_replace ("'", "\'", $headstorelang)."';
  document.getElementById('hcms_select_language').innerHTML=hcms_headstorelang;
}\n";
            $add_onload .= "
    if (typeof hcms_headstorelang === 'function') setTimeout (hcms_headstorelang, 200);";
            $headstorelang = "<span id=\"hcms_select_language\" style=\"all:unset;\"></span>";

            // replace CMS tag with content for page view
            if ($buildview == "publish") $viewstore = str_replace ($hypertag, tpl_languagesession ($application, $language_sessionvar, $language_sessionvalues, $session_defaultvalue), $viewstore);
            else $viewstore = str_replace ($hypertag, "", $viewstore);
          }
          elseif ($buildview == "template")
          {
            $viewstore = str_replace ($hypertag, "", $viewstore);
          }
        }
      }


      // ========================================== replace template variables =============================================

      // replace the template view variables in the template with the view mode (equals $buildview)
      // since cmsview and inlineview should be treated equally in templates, the $view% template variabel will be set to cmsview to support older templates
      if ($buildview == "inlineview" && substr_count ($viewstore, "\"inlineview\"") == 0 && substr_count ($viewstore, "'inlineview'") == 0) $buildview_tplvar = "cmsview";
      else $buildview_tplvar = $buildview;

      $viewstore = str_replace ("%view%", $buildview_tplvar, $viewstore);

      // replace the template media variables in the template with the template images-url
      if ($buildview == "publish") $url_tplmedia = $publ_config['url_publ_tplmedia'];
      else $url_tplmedia = $mgmt_config['url_path_tplmedia'];

      if (isset ($url_tplmedia)) $viewstore = str_replace ("%tplmedia%", $url_tplmedia.$templatesite, $viewstore);

      // replace the media variables in the template with the images-url
      if ($buildview == "publish" || $buildview == "unpublish")
      {
        $url_media = $publ_config['url_publ_media'];
        $abs_media = $publ_config['abs_publ_media'];
      }
      else
      {
        $url_media = $mgmt_config['url_path_media'];
        $abs_media = $mgmt_config['abs_path_media'];
      }

      // %media% is deprecated and should not be used in templates anymore:
      if (isset ($url_media)) $viewstore = str_replace ("%media%", substr ($url_media, 0, strlen ($url_media)-1), $viewstore);
      if (isset ($url_media)) $viewstore = str_replace ("%url_media%", substr ($url_media, 0, strlen ($url_media)-1), $viewstore);
      if (isset ($abs_media)) $viewstore = str_replace ("%abs_media%", substr ($abs_media, 0, strlen ($abs_media)-1), $viewstore);

      // replace the object variables in the template with the used object name
      if (!empty ($user)) $viewstore = str_replace ("%user%", $user, $viewstore);
      else $viewstore = str_replace ("%user%", "sys", $viewstore);

      // replace the date variables in the template with the actual date
      if (isset ($mgmt_config['today'])) $viewstore = str_replace ("%date%", $mgmt_config['today'], $viewstore);

      // replace the container variables in the template with the container name
      if (isset ($contentfile)) $viewstore = str_replace ("%container%", $contentfile, $viewstore);

      // replace the container variables in the template with the container ID
      if (isset ($container_id)) $viewstore = str_replace ("%container_id%", $container_id, $viewstore);

      // replace the object variables in the template with object hash
      if ($mgmt_config['db_connect_rdbms'] != "" && isset ($location_esc) && isset ($page)) $objecthash = rdbms_getobject_hash ($location_esc.$page);
      else $objecthash = "";

      $viewstore = str_replace ("%objecthash%", $objecthash, $viewstore);

      // replace the object variables in the template with the object ID
      if ($mgmt_config['db_connect_rdbms'] != "" && isset ($location_esc) && isset ($page)) $object_id = rdbms_getobject_id ($location_esc.$page);
      else $object_id = "";

      $viewstore = str_replace ("%object_id%", $object_id, $viewstore);

      // replace the template variables in the template with the used template
      if (isset ($templatefile)) $viewstore = str_replace ("%template%", $templatefile, $viewstore);

      // replace the page/comp variables in the template
      if ($buildview == "publish" || $buildview == "unpublish")
      {
        $url_page = $publ_config['url_publ_page'];
        $abs_page = $publ_config['abs_publ_page'];
        $url_comp = $publ_config['url_publ_comp'];
        $abs_comp = $publ_config['abs_publ_comp'];
        $url_rep = $publ_config['url_publ_rep'];
        $abs_rep = $publ_config['abs_publ_rep'];
      }
      else
      {
        $url_page = $mgmt_config[$site]['url_path_page'];
        $abs_page = $mgmt_config[$site]['abs_path_page'];
        $url_comp = $mgmt_config['url_path_comp'];
        $abs_comp = $mgmt_config['abs_path_comp'];
        $url_rep = $mgmt_config['url_path_rep'];
        $abs_rep = $mgmt_config['abs_path_rep'];
      }

      // replace the object variables in the template with the used object name
      if (isset ($filename)) $viewstore = str_replace ("%object%", $filename, $viewstore);

      // replace the url_page variables in the template with the URL of the page root
      if (isset ($url_page)) $viewstore = str_replace ("%url_page%", substr ($url_page, 0, strlen ($url_page)-1), $viewstore);

      // replace the abs_page variables in the template with the abs. path to the page root
      if (isset ($abs_page)) $viewstore = str_replace ("%abs_page%", substr ($abs_page, 0, strlen ($abs_page)-1), $viewstore);

      // replace the url_comp variables in the template with the URL of the component root
      if (isset ($url_comp)) $viewstore = str_replace ("%url_comp%", substr ($url_comp, 0, strlen ($url_comp)-1), $viewstore);
      // deprected: if (isset ($url_comp)) $viewstore = str_replace ("%comp%", substr ($url_comp, 0, strlen ($url_comp)-1), $viewstore);

      // replace the abs_comp variables in the template with the abs. path to the component root
      if (isset ($abs_comp)) $viewstore = str_replace ("%abs_comp%", substr ($abs_comp, 0, strlen ($abs_comp)-1), $viewstore);

      // replace the url_comp variables in the template with the URL of the component root
      if (isset ($url_rep)) $viewstore = str_replace ("%url_rep%", substr ($url_rep, 0, strlen ($url_rep)-1), $viewstore);

      // replace the abs_comp variables in the template with the abs. path to the component root
      if (isset ($abs_rep)) $viewstore = str_replace ("%abs_rep%", substr ($abs_rep, 0, strlen ($abs_rep)-1), $viewstore);

      // replace the url_hypercms variables in the template with the URL of the hypercms root
      if (isset ($mgmt_config['url_path_cms'])) $viewstore = str_replace ("%url_hypercms%", substr ($mgmt_config['url_path_cms'], 0, strlen ($mgmt_config['url_path_cms'])-1), $viewstore);

      // replace the abs_hypercms variables in the template with the abs. path to the hypercms root
      if (isset ($mgmt_config['abs_path_cms'])) $viewstore = str_replace ("%abs_hypercms%", substr ($mgmt_config['abs_path_cms'], 0, strlen ($mgmt_config['abs_path_cms'])-1), $viewstore);

      // replace the location variables in the template
      if (isset ($cat))
      {
        if ($cat == "page") $url_location = str_replace ($mgmt_config[$site]['abs_path_page'], $url_page, $location);
        elseif ($cat == "comp") $url_location = str_replace ($mgmt_config['abs_path_comp'], $url_comp, $location);
      }

      if (isset ($url_location)) $viewstore = str_replace ("%url_location%", substr ($url_location, 0, strlen ($url_location)-1), $viewstore);
      if (isset ($location)) $viewstore = str_replace ("%abs_location%", substr ($location, 0, strlen ($location)-1), $viewstore);

      // replace the publication varibales in the template with the used publication
      if (isset ($site)) $viewstore = str_replace ("%publication%", $site, $viewstore);

      // PHPmailer path
      $viewstore = str_replace ("%phpmailer%", $mgmt_config['abs_path_cms']."library/phpmailer/class.phpmailer.php", $viewstore);

      // ========================================== replace template variables =============================================

      // replace the template view variables in the template with the view mode (equals $buildview)
      // since cmsview and inlineview should be treated equally in templates, the $view% template variabel will be set to cmsview to support older templates
      if ($buildview == "inlineview" && substr_count ($viewstore, "\"inlineview\"") == 0 && substr_count ($viewstore, "'inlineview'") == 0) $buildview_tplvar = "cmsview";
      else $buildview_tplvar = $buildview;

      $viewstore = str_replace ("%view%", $buildview_tplvar, $viewstore);

      // replace the template media variables in the template with the template images-url
      if ($buildview == "publish" || $buildview == "unpublish") $url_tplmedia = $publ_config['url_publ_tplmedia'];
      else $url_tplmedia = $mgmt_config['url_path_tplmedia'];

      if (isset ($url_tplmedia)) $viewstore = str_replace ("%tplmedia%", $url_tplmedia.$templatesite, $viewstore);
      if (isset ($url_tplmedia)) $viewstore = str_replace ("%url_tplmedia%", $url_tplmedia.$templatesite, $viewstore);
      $viewstore = str_replace ("%abs_tplmedia%", $mgmt_config['abs_path_tplmedia'].$templatesite, $viewstore);

      // replace the media variables in the template with the images-url
      if ($buildview == "publish" || $buildview == "unpublish")
      {
        $url_media = $publ_config['url_publ_media'];
        $abs_media = $publ_config['abs_publ_media'];
      }
      else
      {
        $url_media = $mgmt_config['url_path_media'];
        $abs_media = $mgmt_config['abs_path_media'];
      }

      // %media% is deprecated and should not be used in templates anymore:
      if (isset ($url_media)) $viewstore = str_replace ("%media%", substr ($url_media, 0, strlen ($url_media)-1), $viewstore);
      if (isset ($url_media)) $viewstore = str_replace ("%url_media%", substr ($url_media, 0, strlen ($url_media)-1), $viewstore);
      if (isset ($abs_media)) $viewstore = str_replace ("%abs_media%", substr ($abs_media, 0, strlen ($abs_media)-1), $viewstore);

      // replace the date variables in the template with the actual date
      if (isset ($mgmt_config['today'])) $viewstore = str_replace ("%date%", $mgmt_config['today'], $viewstore);

      // replace the container variables in the template with the container name
      if (isset ($contentfile)) $viewstore = str_replace ("%container%", $contentfile, $viewstore);

      // replace the container variables in the template with the container ID
      if (isset ($container_id)) $viewstore = str_replace ("%container_id%", $container_id, $viewstore);

      // replace the object variables in the template with object hash
      if ($mgmt_config['db_connect_rdbms'] != "" && isset ($location_esc) && isset ($page)) $objecthash = rdbms_getobject_hash ($location_esc.$page);
      else $objecthash = "";

      $viewstore = str_replace ("%objecthash%", $objecthash, $viewstore);

      // replace the object variables in the template with the object ID
      if ($mgmt_config['db_connect_rdbms'] != "" && isset ($location_esc) && isset ($page)) $object_id = rdbms_getobject_id ($location_esc.$page);
      else $object_id = "";

      $viewstore = str_replace ("%object_id%", $object_id, $viewstore);

      // replace the template variables in the template with the used template
      if (isset ($templatefile)) $viewstore = str_replace ("%template%", $templatefile, $viewstore);

      // replace the page/comp variables in the template
      if ($buildview == "publish" || $buildview == "unpublish")
      {
        $url_page = $publ_config['url_publ_page'];
        $abs_page = $publ_config['abs_publ_page'];
        $url_comp = $publ_config['url_publ_comp'];
        $abs_comp = $publ_config['abs_publ_comp'];
        $url_rep = $publ_config['url_publ_rep'];
        $abs_rep = $publ_config['abs_publ_rep'];
      }
      else
      {
        $url_page = $mgmt_config[$site]['url_path_page'];
        $abs_page = $mgmt_config[$site]['abs_path_page'];
        $url_comp = $mgmt_config['url_path_comp'];
        $abs_comp = $mgmt_config['abs_path_comp'];
        $url_rep = $mgmt_config['url_path_rep'];
        $abs_rep = $mgmt_config['abs_path_rep'];
      }

      // replace the object variables in the template with the used object name
      if (isset ($filename)) $viewstore = str_replace ("%object%", $filename, $viewstore);

      // replace the url_page variables in the template with the URL of the page root
      if (isset ($url_page)) $viewstore = str_replace ("%url_page%", substr ($url_page, 0, strlen ($url_page)-1), $viewstore);

      // replace the abs_page variables in the template with the abs. path to the page root
      if (isset ($abs_page)) $viewstore = str_replace ("%abs_page%", substr ($abs_page, 0, strlen ($abs_page)-1), $viewstore);

      // replace the url_comp variables in the template with the URL of the component root
      if (isset ($url_comp)) $viewstore = str_replace ("%url_comp%", substr ($url_comp, 0, strlen ($url_comp)-1), $viewstore);
      // deprected: if (isset ($url_comp)) $viewstore = str_replace ("%comp%", substr ($url_comp, 0, strlen ($url_comp)-1), $viewstore);

      // replace the abs_comp variables in the template with the abs. path to the component root
      if (isset ($abs_comp)) $viewstore = str_replace ("%abs_comp%", substr ($abs_comp, 0, strlen ($abs_comp)-1), $viewstore);

      // replace the url_comp variables in the template with the URL of the component root
      if (isset ($url_rep)) $viewstore = str_replace ("%url_rep%", substr ($url_rep, 0, strlen ($url_rep)-1), $viewstore);

      // replace the abs_comp variables in the template with the abs. path to the component root
      if (isset ($abs_rep)) $viewstore = str_replace ("%abs_rep%", substr ($abs_rep, 0, strlen ($abs_rep)-1), $viewstore);

      // replace the url_hypercms variables in the template with the URL of the hypercms root
      if (isset ($mgmt_config['url_path_cms'])) $viewstore = str_replace ("%url_hypercms%", substr ($mgmt_config['url_path_cms'], 0, strlen ($mgmt_config['url_path_cms'])-1), $viewstore);

      // replace the abs_hypercms variables in the template with the abs. path to the hypercms root
      if (isset ($mgmt_config['abs_path_cms'])) $viewstore = str_replace ("%abs_hypercms%", substr ($mgmt_config['abs_path_cms'], 0, strlen ($mgmt_config['abs_path_cms'])-1), $viewstore);

      // replace the location variables in the template
      if (isset ($cat))
      {
        if ($cat == "page") $url_location = str_replace ($mgmt_config[$site]['abs_path_page'], $url_page, $location);
        elseif ($cat == "comp") $url_location = str_replace ($mgmt_config['abs_path_comp'], $url_comp, $location);
      }

      if (isset ($url_location)) $viewstore = str_replace ("%url_location%", substr ($url_location, 0, strlen ($url_location)-1), $viewstore);
      if (isset ($location)) $viewstore = str_replace ("%abs_location%", substr ($location, 0, strlen ($location)-1), $viewstore);

      // replace the publication varibales in the template with the used publication
      if (isset ($site)) $viewstore = str_replace ("%publication%", $site, $viewstore);


      // =================================================== head content ===================================================

      $pagetracking = "";
      $label = "";
      $language_info = "";
      $headstoremeta = "";

      // get all hyperCMS tags
      $hypertag_array = gethypertag ($viewstore, "page", 0);

      if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
      {
        // loop for each hyperCMS tag found in template
        $i = 0;

        foreach ($hypertag_array as $key => $hypertag)
        {
          // counter for media name
          $i++;

          // get tag name
          $hypertagname = gethypertagname ($hypertag);

          // cut of page-prefix
          if (substr_count ($hypertagname, "page") >= 1) $metaname = ucwords (substr ($hypertagname, 4));

          // get tag id
          $id = getattribute ($hypertag, "id");

          // if id uses special characters
          if (trim ($id) != "" && specialchr ($id, ":-_") == true)
          {
            $result['view'] = "<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset=\"".getcodepage ($lang)."\" />
<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />
<link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />
</head>
<body class=\"hcmsWorkplaceGeneric\">
  <p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters-in-the-content-identification-name'][$lang], $charset, $lang)." '".$id."':<br/>[\]{}()*+?.,\\^$</p>
</body>
</html>";

            $result['release'] = 0;
            $result['container'] = $contentfile;
            $result['containerdata'] = $contentdata;
            $result['template'] = $templatefile;
            $result['templatedata'] = $templatedata;
            $result['templateext'] = $templateext;
            $result['name'] = $name_orig;
            $result['objecttype'] = $filetype;

            return $result;
          }

          // get label text
          $label = getattribute ($hypertag, "label");
          $labelname = "";

          // get type of content
          $infotype = getattribute (strtolower ($hypertag), "infotype");
          if ($infotype == "meta") $show_meta = true;

          // get visibility on publish
          $onpublish = getattribute (strtolower ($hypertag), "onpublish");

          // get visibility on edit
          $onedit = getattribute (strtolower ($hypertag), "onedit");

          // get value of tag
          $defaultvalue = getattribute ($hypertag, "default");

          // get height in pixel of text field
          $sizeheight = getattribute ($hypertag, "height");

          if ($sizeheight == false || $sizeheight <= 0) $sizeheight = "300";
          elseif ($is_mobile && $sizeheight <= 30) $sizeheight = "34";
          elseif ($sizeheight <= 28) $sizeheight = "30";

          // get width in pixel of text field
          $sizewidth = getattribute ($hypertag, "width");

          if ($sizewidth == false || $sizewidth <= 0) $sizewidth = "600";

          // correct width for mobile devices
          if ($maxwidth > 0 && $sizewidth > $maxwidth) $sizewidth = $maxwidth;

          // get language attribute
          $language_info = getattribute ($hypertag, "language");

          // get readonly attribute
          $readonly = getattribute ($hypertag, "readonly");

          if ($buildview != "formlock")
          {
            if ($readonly != false) $disabled = " disabled=\"disabled\"";
            else $disabled = "";
          }

          // get group access
          $groupaccess = getattribute ($hypertag, "groups");
          $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);

          // read content using db_connect
          $contentbot = "";
          $db_connect_data = false;

          if (!empty ($db_connect))
          {
            $db_connect_data = db_read_metadata ($site, $contentfile, $contentdata, $hypertagname, $user);

            if ($db_connect_data != false)
            {
              $contentbot = $db_connect_data['content'];

              // set true
              $db_connect_data = true;
            }
          }

          // read content from content container
          if ($db_connect_data == false)
          {
            $temp = getcontent ($contentdata, "<".$hypertagname.">");
            if (!empty ($temp[0])) $contentbot = $temp[0];
          }

          // set default value given eventually by tag
          if (empty ($contentbot) && $defaultvalue != "") $contentbot = $defaultvalue;

          // encode scripts in content
          $contentbot = scriptcode_encode ($contentbot);

          // in order to access the content via JS
          if ($groupaccess != true && ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock"))
          {
            $formitem[$key] = "
            <input type=\"hidden\" id=\"".$hypertagname."\" value=\"".$contentbot."\" />";
          }

          // create head-buttons depending on buildview parameter setting
          if ($buildview != "template" && (!isset ($editmeta[$hypertagname]) || $editmeta[$hypertagname] != false) && $onedit != "hidden" && $groupaccess == true)
          {
            // set flag for found tag
            $editmeta[$hypertagname] = false;

            // if language value is in given language scope
            if (checklanguage ($language_sessionvalues_array, $language_info))
            {
              // if page content-type
              if ($hypertagname == "pagecontenttype" || (!isset ($compcontenttype) && !isset ($contenttype)))
              {
                $contenttype = $contentbot;

                if ($buildview == "formedit" || $buildview == "formlock" || $buildview == "formmeta")
                {
                  // set flag
                  $setcontenttype = "yes";

                  if ($label != "") $labelname = $label;
                  else $labelname = "Content-type";

                  $formitem[$key] = "
                  <div class=\"hcmsFormRowLabel ".$hypertagname."\">
                    <b>".$labelname."</b>
                  </div>
                  <div class=\"hcmsFormRowContent ".$hypertagname."\">
                    <table class=\"hcmsTableNarrow\">
                      <tr>
                        <td style=\"width:150px;\">
                          ".getescapedtext ($hcms_lang['character-set'][$lang], $charset, $lang)."
                        </td>
                        <td>";

                  //load code page index file
                  $codepage_array = file ($mgmt_config['abs_path_cms']."include/codepage.dat");

                  if ($codepage_array != false)
                  {
                    $formitem[$key] .= "
                          <select id=\"".$hypertagname."\" name=\"".$hypertagname."\" ".$disabled.">";

                    foreach ($codepage_array as $codepage)
                    {
                      list ($code, $description, $language) = explode ("|", $codepage);

                      $formitem[$key] .= "
                            <option value=\"text/html; charset=".$code."\"".(substr_count ($contenttype, $code) == 1 ? " selected" : "").">".$code." ".$description."</option>";
                    }

                    $formitem[$key] .= "
                          </select>";
                  }
                  else $formitem[$key] = "
                          <span class=\"hcmsHeadline\">".$hcms_lang['could-not-find-code-page-index'][$lang]."</span>";

                  $formitem[$key] .= "
                          <img src=\"".getthemelocation()."img/button_help.png\" onClick=\"hcms_openWindow('".cleandomain ($mgmt_config['url_path_cms'])."head_contenttype.php', 'help', 'location=no,menubar=no,toolbar=no,titlebar=no,resizable=yes,scrollbars=yes', ".windowwidth ("object").", ".windowheight ("object").")\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['help'][$lang], $charset, $lang)."\" />
                        </td>
                      </tr>
                    </table>
                  </div>";
                }
              }
              // if page language
              elseif ($hypertagname == "pagelanguage")
              {
                $content = $contentbot;

                if ($label != "") $labelname = $label;
                else $labelname = "Language";

                if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                {
                  $add_submitlanguage = "
                  submitLanguage ('list2', '".$hypertagname."');";

                  $formitem[$key] = "
                  <div class=\"hcmsFormRowLabel ".$hypertagname."\">
                    <b>".$labelname."</b>
                  </div>
                  <div class=\"hcmsFormRowContent ".$hypertagname."\">
                    <input type=\"hidden\" name=\"".$hypertagname."\" value=\"\">
                    <table class=\"hcmsTableNarrow\">
                      <tr>
                        <td>
                          ".getescapedtext ($hcms_lang['available-languages'][$lang], $charset, $lang)."<br />";

                  // get languages
                  $langcode_array = getlanguageoptions();

                  if ($langcode_array != false)
                  {
                    $formitem[$key] .= "
                         <select multiple size=\"10\" name=\"list1\" style=\"width:250px; height:160px;\"".$disabled.">";

                    foreach ($langcode_array as $code => $lang_short)
                    {
                      if (substr_count ($content, $code) == 0)
                      {
                        $formitem[$key] .= "
                            <option value=\"".$code."\">".$lang_short."</option>";
                      }
                      else
                      {
                        $list2_array[] = "
                            <option value=\"".$code."\">".$lang_short."</option>";
                      }
                    }

                    $formitem[$key] .= "
                        </select>";
                  }
                  else $formitem[$key] .= "
                        <span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['could-not-find-language-code-index'][$lang], $charset, $lang)."</span>";

                  $formitem[$key] .= "
                      </td>
                      <td style=\"text-align:center; vertical-align:middle;\">
                        <br />
                        <div id=\"".$hypertagname."_controls\" style=\"display:inline-block;\">
                          <button type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"moveBoxEntry(this.form.elements['list2'],this.form.elements['list1']);\" ".$disabled.">&lt;&lt;</button>
                          <button type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"moveBoxEntry(this.form.elements['list1'],this.form.elements['list2']);\" ".$disabled.">&gt;&gt;</button>
                        </div>
                      </td>
                      <td>
                        ".getescapedtext ($hcms_lang['selected-languages'][$lang], $charset, $lang)."<br />
                        <select id=\"".$hypertagname."\" multiple size=\"10\" name=\"list2\" style=\"width:250px; height:160px;\" ".$disabled.">";

                  if (!empty ($list2_array) && is_array ($list2_array) && sizeof ($list2_array) > 0)
                  {
                    foreach ($list2_array as $list2)
                    {
                      $formitem[$key] .= $list2;
                    }
                  }

                  $formitem[$key] .= "
                        </select>
                      </td>
                    </tr>
                  </table>
                </div>";
                }
              }
              // if page customer tracking
              elseif ($hypertagname == "pagetracking")
              {
                if ($label == "") $labelname = $metaname;
                else $labelname = $label;

                if ($contentbot != "" && ($buildview == "publish" || $buildview == "unpublish"))
                {
                  $pagetracking_name = $contentbot;
                  $contentbot = loadfile ($mgmt_config['abs_path_data']."customer/".$site."/", $pagetracking_name.".track.dat");

                  if ($contentbot == false)
                  {
                    $errcode = "10101";
                    $error[] = $mgmt_config['today']."|hypercms_tplengine.inc.php|error|".$errcode."|loadfile failed for '".$mgmt_config['abs_path_data']."customer/".$site."/".$pagetracking_name.".track.dat'";
                  }
                  else $contentbot = tpl_pagetracking ($application, $contentbot);

                  // save page tracking to add it later due to the reason that session handling must be added on top of a page
                  $pagetracking = $contentbot."\n";
                }

                if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                {
                  $formitem[$key] = "
                  <div class=\"hcmsFormRowLabel ".$hypertagname."\">
                    <b>".$labelname."</b>
                  </div>
                  <div class=\"hcmsFormRowContent ".$hypertagname."\">
                    <table class=\"hcmsTableNarrow\">
                      <tr>
                        <td style=\"width:150px;\">
                          ".getescapedtext ($hcms_lang['customer-tracking'][$lang], $charset, $lang)."
                        </td>
                        <td>
                          <select id=\"".$hypertagname."\" name=\"".$hypertagname."\" style=\"width:250px;\" ".$disabled.">
                            <option value=\"\">".getescapedtext ($hcms_lang['select'][$lang], $charset, $lang)."</option>";

                  $scandir = scandir ($mgmt_config['abs_path_data']."customer/".$site."/");

                  $i = 0;

                  if ($scandir)
                  {
                    foreach ($scandir as $entry)
                    {
                      if ($entry != "." && $entry != ".." && !is_dir ($entry))
                      {
                        if (strpos ($entry, ".track.dat") > 0)
                        {
                          $item_files[$i] = $entry;
                        }
                        $i++;
                      }
                    }

                    if (sizeof ($item_files) >= 1)
                    {
                      sort ($item_files);
                      reset ($item_files);

                      foreach ($item_files as $persfile)
                      {
                        $pers_name = substr ($persfile, 0, strpos ($persfile, ".track.dat"));

                        $formitem[$key] .= "
                            <option value=\"".$pers_name."\" ".($pers_name == $contentbot ? "selected=\"selected\"" : "").">".$pers_name."</option>";
                      }
                    }
                  }

                  $formitem[$key] .= "
                          </select>
                        </td>
                      </tr>
                    </table>
                  </div>";
                }

                // empty contentbot for customer tracking since the content won't replace the tag
                $contentbot = "";
              }
              // all other cases
              else
              {
                if ($label == "") $labelname = $metaname;
                else $labelname = $label;

                if ($buildview == "formedit" || $buildview == "formlock" || $buildview == "formmeta")
                {
                  $formitem[$key] = "
                  <div class=\"hcmsFormRowLabel ".$hypertagname."\">
                    <b>".$labelname."</b>
                  </div>
                  <div class=\"hcmsFormRowContent ".$hypertagname."\">
                    <textarea name=\"".$hypertagname."\" wrap=\"VIRTUAL\" style=\"width:".$sizewidth."px; height:".$sizeheight."px;\"".$disabled.">".$contentbot."</textarea>
                  </div>";
                }
              }
            }

            // replace CMS tag with contentbot for page view
            if ($onpublish != "hidden") $viewstore = str_replace ($hypertag, $contentbot, $viewstore);
            elseif ($onpublish == "hidden") $viewstore = str_replace ($hypertag, "", $viewstore);
          }
          elseif ($buildview == "template")
          {
            $viewstore = str_replace ($hypertag, "", $viewstore);
          }
        }
      }

      // if no content-type is set so far
      if (!isset ($compcontenttype) && !isset ($contenttype) && $setcontenttype != "yes")
      {
        if ($buildview == "formedit" || $buildview == "formlock" || $buildview == "formmeta")
        {
          // set flag
          $setcontenttype = "yes";

          $formitem['0'] = "
              <div class=\"hcmsFormRowLabel ".$hypertagname."\">
                <b>Content-type</b>
              </div>
              <div class=\"hcmsFormRowContent ".$hypertagname."\">
                <table class=\"hcmsTableNarrow\">
                  <tr>
                    <td style=\"width:150px;\">
                      ".getescapedtext ($hcms_lang['character-set'][$lang], $charset, $lang)."
                    </td>
                    <td>";

          //load code page index file
          $codepage_array = file ($mgmt_config['abs_path_data']."codepage.dat");

          if ($codepage_array != false)
          {
            $formitem['0'] .= "
                    <select id=\"".$hypertagname."\" name=\"".$hypertagname."\" ".$disabled.">";

            foreach ($codepage_array as $codepage)
            {
              list ($code, $description, $language) = explode ("|", $codepage);

              $formitem['0'] .= "
                       <option value=\"text/html; charset=".$code."\" ".(substr_count ($contenttype, $code) == 1 ? "selected=\"selected\"" : "").">".$code." ".$description."</option>";
            }

            $formitem['0'] .= "
                     </select>";
          }
          else $formitem['0'] = "
                     <span class=\"hcmsHeadline\">".$hcms_lang['could-not-find-code-page-index'][$lang]."</span>";

          $formitem['0'] .= "
                     <img src=\"".getthemelocation()."img/button_help.png\" onClick=\"hcms_openWindow('".cleandomain ($mgmt_config['url_path_cms'])."head_contenttype.php', 'help', 'location=no,menubar=no,toolbar=no,titlebar=no,resizable=yes,scrollbars=yes', ".windowwidth ("object").", ".windowheight ("object").")\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['help'][$lang], $charset, $lang)."\" />
                   </td>
                 </tr>
               </table>
             </div>";
        }
      }

      // =================================================== article settings ===================================================

      $hypertag_array = array();
      $artid_array = array();
      $hypertagname_array = array();

      // get all hyperCMS tags
      $hypertag_array = gethypertag ($viewstore, "art", 0);

      if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
      {
        // loop for each hyperCMS arttag found in template
        foreach ($hypertag_array as $hypertag)
        {
          $id = getattribute ($hypertag, "id");

          $artid = getartid ($id);
          if (!in_array ($artid, $artid_array)) $artid_array[] = $artid;

          // if id uses special characters
          if (trim ($id) != "" && specialchr ($id, ":-_") == true)
          {
            $result['view'] = "<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset=\"".getcodepage ($lang)."\" />
<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />
<link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />
</head>
<body class=\"hcmsWorkplaceGeneric\">
  <p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters-in-the-content-identification-name'][$lang], $charset, $lang)." '".$id."':<br/>[\]{}()*+?.,\\^$</p>
</body>
</html>";

            $result['release'] = 0;
            $result['container'] = $contentfile;
            $result['containerdata'] = $contentdata;
            $result['template'] = $templatefile;
            $result['templatedata'] = $templatedata;
            $result['templateext'] = $templateext;
            $result['name'] = $name_orig;
            $result['objecttype'] = $filetype;

            return $result;
          }

          // get tag name
          $hypertagname_array[$artid] = gethypertagname ($hypertag);
        }

        $artid_array = array_unique ($artid_array);

        $i = 0;

        // loop for each hyperCMS tag found in template
        foreach ($artid_array as $artid)
        {
          $i++;

          $arttitle[$artid] = "";
          $artdatefrom[$artid] = "";
          $artdateto[$artid] = "";
          $artstatus[$artid] = "";

          if ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "formedit" || $buildview == "formmeta" || $buildview == "publish")
          {
            // read content using db_connect
            $db_connect_data = false;

            if (!empty ($db_connect))
            {
              $db_connect_data = db_read_article ($site, $contentfile, $contentdata, $artid, $user);

              if ($db_connect_data != false)
              {
                $arttitle[$artid] = $db_connect_data['title'];
                $artdatefrom[$artid] = $db_connect_data['datefrom'];
                $artdateto[$artid] = $db_connect_data['dateto'];
                $artstatus[$artid] = $db_connect_data['status'];

                // set true
                $db_connect_data = true;
              }
            }

            // read content from content container
            if ($db_connect_data == false)
            {
              $artarray = selectcontent ($contentdata, "<article>", "<article_id>", $artid);
              if (!empty ($artarray[0])) $bufferarray = getcontent ($artarray[0], "<articletitle>");
              if (!empty ($bufferarray[0])) $arttitle[$artid] = $bufferarray[0];

              $bufferarray = getcontent ($artarray[0], "<articledatefrom>");
              if (!empty ($bufferarray[0])) $artdatefrom[$artid] = $bufferarray[0];

              $bufferarray = getcontent ($artarray[0], "<articledateto>");
              if (!empty ($bufferarray[0])) $artdateto[$artid] = $bufferarray[0];

              $bufferarray = getcontent ($artarray[0], "<articlestatus>");
              if (!empty ($bufferarray[0])) $artstatus[$artid] = $bufferarray[0];
            }
          }

          if ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "formedit" || $buildview == "formmeta")
          {
            $hypertagname = $hypertagname_array[$artid];

            // create tag link for editor
            if ($buildview == "cmsview" || $buildview == "inlineview") $arttaglink[$artid] = "<img src=\"".getthemelocation()."img/edit_article.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."article_edit.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&artid=".url_encode($artid)."&tagname=".url_encode($hypertagname)."&arttitle=".urlencode ($arttitle[$artid])."&artdatefrom=".url_encode($artdatefrom[$artid])."&artdateto=".url_encode($artdateto[$artid])."&artstatus=".url_encode($artstatus[$artid])."&contenttype=".url_encode($contenttype)."';\" alt=\"".$artid.": ".getescapedtext ($hcms_lang['define-settings-for-article'][$lang], $charset, $lang)."';\" title=\"".$artid.": ".getescapedtext ($hcms_lang['define-settings-for-article'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />";
            elseif ($buildview == "formedit" || $buildview == "formmeta") $arttaglink[$artid] = "<img onClick=\"setSaveType('form_so', '".cleandomain ($mgmt_config['url_path_cms'])."article_edit.php?view=".url_encode($buildview)."&savetype=form_so&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&artid=".url_encode($artid)."&tagname=".url_encode($hypertagname)."&arttitle=".urlencode ($arttitle[$artid])."&artdatefrom=".url_encode($artdatefrom[$artid])."&artdateto=".url_encode($artdateto[$artid])."&artstatus=".url_encode($artstatus[$artid])."&contenttype=".url_encode($contenttype)."', 'post');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_time.png\" alt=\"".getescapedtext ($hcms_lang['define-settings-for-article'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['define-settings-for-article'][$lang], $charset, $lang)."\" />";
            else $arttaglink[$artid] = "";
          }
        }
      }

      // =========================================== help content for form views ============================================

      $searchtag_array = array();
      $searchtag_array[0] = "help";
      $repl_offset = 0;

      foreach ($searchtag_array as $searchtag)
      {
        // get all hyperCMS tags
        $hypertag_array = gethypertag ($viewstore, $searchtag, 0);

        if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
        {
          reset ($hypertag_array);

          // loop for each hyperCMS tag found in template
          foreach ($hypertag_array as $key => $hypertag)
          {
            // get tag name
            $hypertagname = gethypertagname ($hypertag);

            // get tag id
            $id = getattribute ($hypertag, "id");

            // get group access
            $groupaccess = getattribute ($hypertag, "groups");
            $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);

            // extract text value of checkbox
            $value = getattribute ($hypertag, "value", false);

            if (trim ($value) != "" && $groupaccess == true && ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock"))
            {
              // get height in pixel of text field
              $sizeheight = getattribute ($hypertag, "height");

              if ($sizeheight == false || $sizeheight <= 0) $sizeheight = "";

              // get width in pixel of text field
              $sizewidth = getattribute ($hypertag, "width");

              if ($sizewidth == false || $sizewidth <= 0) $sizewidth = "600";

              // define style
              $style = "";
              
              if (!empty ($sizewidth)) $style .= "width:".intval($sizewidth)."px; ";
              if (!empty ($sizeheight)) $style .= "height:".intval($sizeheight)."px; ";

              // form item
              $formitem[$key] = "<div id=\"".$id."\" class=\"hcmsInfoBox ".$hypertagname."_".$id."\" style=\"display:block; overflow:auto; margin:12px 0px 3px 0px; ".$style."\">".$value."</div>";
            }
            // for template view
            elseif ($buildview == "template")
            {
              $taglink = "
              <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                <tr>
                  <td>
                    <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Help: ".$id."</b><br />
                    ".getescapedtext ($hcms_lang['information'][$lang], $charset, $lang)."</span>
                  </td>
                </tr>
              </table>";

              // insert taglink
              $viewstore = str_replace ($hypertag, $taglink, $viewstore);
            }
            // for all other views
            else
            {
              // remove tag
              $viewstore = str_replace ($hypertag, "", $viewstore);
            }
          }
        }
      }

      // =================================================== text content ===================================================

      $searchtag_array = array();
      $searchtag_array[0] = "arttext";
      $searchtag_array[1] = "text";
      $searchtag_array[2] = "comment";
      $infotype = "";
      $position = array();
      $onpublish = "";
      $onedit = "";
      $constraint = "";
      $toolbar = "";
      $label = "";
      $language_info = "";
      $add_submittext = "";
      $dpi = "";
      $colorspace = "";
      $iccprofile = "";
      $mediapathtype = "";
      $prefix = "";
      $suffix = "";
      $replace = "";
      $contentbot = "";

      foreach ($searchtag_array as $searchtag)
      {
        // get all hyperCMS tags
        $hypertag_array = gethypertag ($viewstore, $searchtag, 0);

        if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
        {
          $id_array = array();

          reset ($hypertag_array);

          // loop for each hyperCMS tag found in template
          foreach ($hypertag_array as $key => $hypertag)
          {
            // get mediatype
            $mediatype = getattribute ($hypertag, "mediatype");

            // verify mediatype for assets only
            if (!empty ($mediatype) && !empty ($mediafile))
            {
              $continue = true;

              if (strpos (strtolower ("_".$mediatype), "audio") > 0 && is_audio ($mediafile)) $continue = false;
              elseif (strpos (strtolower ("_".$mediatype), "image") > 0 && is_image ($mediafile)) $continue = false;
              elseif ((strpos (strtolower ("_".$mediatype), "document") > 0 || strpos (strtolower ("_".$mediatype), "text") > 0) && is_document ($mediafile)) $continue = false;
              elseif (strpos (strtolower ("_".$mediatype),"video") > 0 && is_video ($mediafile)) $continue = false;
              elseif (strpos (strtolower ("_".$mediatype), "compressed") > 0 && is_compressed ($mediafile)) $continue = false;

              if ($continue == true) continue;
            }

            // get tag name
            $hypertagname = gethypertagname ($hypertag);

            // get tag id
            $id = getattribute ($hypertag, "id");

            // if id uses special characters
            if (trim ($id) != "" && specialchr ($id, ":-_ ") == true)
            {
              $result['view'] = "<!DOCTYPE html>
  <html>
  <head>
  <title>hyperCMS</title>
  <meta charset=\"".getcodepage ($lang)."\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />
  </head>
  <body class=\"hcmsWorkplaceGeneric\">
    <p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters-in-the-content-identification-name'][$lang], $charset, $lang)." '".$id."':<br/>[\]{}()*+?.,\\^$</p>
  </body>
  </html>";

              $result['release'] = 0;
              $result['container'] = $contentfile;
              $result['containerdata'] = $contentdata;
              $result['template'] = $templatefile;
              $result['templatedata'] = $templatedata;
              $result['templateext'] = $templateext;
              $result['name'] = $name_orig;
              $result['objecttype'] = $filetype;

              return $result;
            }

            // get label text
            $label = getattribute ($hypertag, "label");
            $labelname = "";
            $artid = "";
            $elementid = "";

            if ($searchtag == "arttext")
            {
              // get article id
              $artid = getartid ($id);

              // element id
              $elementid = getelementid ($id);

              // define label
              if ($label == "") $labelname = $artid." - ".$elementid;
              else $labelname = $artid." - ".$label;
            }
            else
            {
              // define label
              if ($label == "") $labelname = $id;
              else $labelname = $label;
            } 

            // get visibility on publish
            $onpublish = getattribute (strtolower ($hypertag), "onpublish");

            // get visibility on edit
            $onedit = getattribute (strtolower ($hypertag), "onedit");

            // get constraint
            $constraint = getattribute ($hypertag, "constraint");

            // get type of content
            $infotype = getattribute (strtolower ($hypertag), "infotype");
            if ($infotype == "meta") $show_meta = true;

            // extract text value of checkbox
            $value = getattribute ($hypertag, "value");

            // get value of tag
            $defaultvalue = getattribute ($hypertag, "default");

            // get format (if date)
            $format = getattribute ($hypertag, "format");
            if ($format == "") $format = "%Y-%m-%d";

            // get active (for comment tags if comments can be added)
            $active = getattribute ($hypertag, "active");

            // get toolbar
            $toolbar = getattribute ($hypertag, "toolbar");
            if ($toolbar == "" && $mgmt_config[$site]['dam'] == true) $toolbar = "DAM";

            if ($toolbar == false && ($buildview == "formedit" || ($buildview == "formmeta" && $infotype == "meta") || $buildview == "formlock")) $toolbar = "DefaultForm";
            elseif ($toolbar == false) $toolbar = "Default";

            // get height in pixel of text field
            $sizeheight = getattribute ($hypertag, "height");

            if ($sizeheight == false || $sizeheight <= 0) $sizeheight = "300";
            elseif ($is_mobile && $sizeheight <= 30) $sizeheight = "34";
            elseif ($sizeheight <= 28) $sizeheight = "30";

            // get width in pixel of text field
            $sizewidth = getattribute ($hypertag, "width");

            if ($sizewidth == false || $sizewidth <= 0) $sizewidth = "600";

            // correct width for mobile devices
            if ($maxwidth > 0 && $sizewidth > $maxwidth) $sizewidth = $maxwidth;

            // get language attribute
            $language_info = getattribute ($hypertag, "language");

            // get readonly attribute
            $readonly = getattribute ($hypertag, "readonly");

            if ($buildview != "formlock")
            {
              if ($readonly != false) $disabled = " disabled=\"disabled\"";
              else $disabled = "";
            }

            // get group access
            $groupaccess = getattribute ($hypertag, "groups");
            $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);

            // get dpi
            $dpi = getattribute ($hypertag, "dpi");

            // get colorspace and ICC profile
            $colorspace = getattribute ($hypertag, "colorspace");
            $iccprofile = getattribute ($hypertag, "iccprofile");

            // get path type [file,url,abs,wrapper,download]
            $mediapathtype = getattribute ($hypertag, "pathtype");

            // preview window for URL
            $preview_window = getattribute ($hypertag, "preview");

            // collect unique id's and set position/key of hypertag
            if (!in_array ($id, $id_array) && $onedit != "hidden")
            {
              $id_array[] = $id;

              // get key (position) of array item
              $position[$id] = $key;
            }

            // set position for form item
            if (!empty ($position[$id])) $key = $position[$id];

            // set flag for edit button or text field
            if (empty ($foundtxt[$id]) && $onedit != "hidden") $foundtxt[$id] = true;
            elseif (!empty ($foundtxt[$id])) $foundtxt[$id] = false;

            // check uniqueness
            $tagu = "hyperCMS:".$searchtag."u id='".$id."'";
            $tagf = "hyperCMS:".$searchtag."f id='".$id."'";
            $tagl = "hyperCMS:".$searchtag."l id='".$id."'";
            $tagc = "hyperCMS:".$searchtag."c id='".$id."'";
            $tagd = "hyperCMS:".$searchtag."d id='".$id."'";
            $tagk = "hyperCMS:".$searchtag."k id='".$id."'";
            $tags = "hyperCMS:".$searchtag."s id='".$id."'";

            $tagucount = substr_count ($viewstore, $tagu);
            $tagfcount = substr_count ($viewstore, $tagf);
            $taglcount = substr_count ($viewstore, $tagl);
            $tagccount = substr_count ($viewstore, $tagc);
            $tagdcount = substr_count ($viewstore, $tagd);
            $tagkcount = substr_count ($viewstore, $tagk);
            $tagscount = substr_count ($viewstore, $tags);

            $control_sum = 0;

            if ($tagucount > 0) $control_sum++;
            if ($tagfcount > 0) $control_sum++;
            if ($taglcount > 0) $control_sum++;
            if ($tagccount > 0) $control_sum++;
            if ($tagdcount > 0) $control_sum++;
            if ($tagkcount > 0) $control_sum++;
            if ($tagscount > 0) $control_sum++;

            // if textu, textf or textl tag have the same id => error
            if ($control_sum >= 2)
            {
              $result['view'] = "<!DOCTYPE html>
  <html>
  <head>
  <title>hyperCMS</title>
  <meta charset=\"".getcodepage ($lang)."\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />
  </head>
  <body class=\"hcmsWorkplaceGeneric\">
    <p class=\"hcmsHeadline\">".$hcms_lang['the-tags'][$lang]." [".$tagu."], [".$tagf."], [".$tagl."], [".$tagc."], [".$tagd."] ".$hcms_lang['and-or'][$lang]." [".$tagk."] ".$hcms_lang['have-the-same-identification-id'][$lang]."</p>
    ".$hcms_lang['please-note-the-tag-identification-must-be-unique-for-different-tag-types-of-the-same-tag-set'][$lang]."
  </body>
  </html>";

              $result['release'] = 0;
              $result['container'] = $contentfile;
              $result['containerdata'] = $contentdata;
              $result['template'] = $templatefile;
              $result['templatedata'] = $templatedata;
              $result['templateext'] = $templateext;
              $result['name'] = $name_orig;
              $result['objecttype'] = $filetype;

              return $result;
            }
            else
            {
              // initialize
              $contentbot = "";

              if ($buildview != "template")
              {
                // read content using db_connect
                $db_connect_data = false;

                if (!empty ($db_connect))
                {
                  $db_connect_data = db_read_text ($site, $contentfile, $contentdata, $id, $artid, $user);

                  if ($db_connect_data != false)
                  {
                    $contentbot = $db_connect_data['text'];

                    // set true
                    $db_connect_data = true; 
                  }
                }

                // read content from content container
                if ($db_connect_data == false)
                {
                  // for comments
                  if ($searchtag == "comment")
                  {
                    $contentcomment = "";
                    $bufferarray = selectcontent ($contentdata, "<text>", "<text_id>", $id.':*');

                    if (is_array ($bufferarray))
                    {
                      $contentcomment = "
                      <div class=\"".$hypertagname."_".$id."\" style=\"display:block; width:".$sizewidth.(strpos ($sizewidth, "%") > 0 ? "" : "px").";\">";

                      foreach ($bufferarray as $data)
                      {
                        $tmpid = getcontent ($data, "<text_id>");
                        $tmpuser = getcontent ($data, "<textuser>");
                        $tmpcontent = getcontent ($data, "<textcontent>", true);

                        if (!empty ($tmpid[0]) && !empty ($tmpcontent[0]))
                        {
                          // only for form views
                          if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") $contentcomment .= "
                        <div style=\"margin-bottom:2px;\">";
                          elseif ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview") $contentcomment .= "
                        <div class=\"hcms_comment\">";

                          list ($name, $microtime) = explode (":", $tmpid[0]);

                          $date_format = 'Y-m-d H:i:s';
                          if (is_array ($hcms_lang_date) && $hcms_lang_date[$lang] != false) $date_format = $hcms_lang_date[$lang];

                          if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") $contentcomment .= "
                          <div class=\"hcmsWorkplaceExplorer\" style=\"width:100%; height:20px; padding:3px 0px 3px 0px;\">&nbsp;".getescapedtext (str_replace(array('%date%', '%user%'), array(date($date_format, $microtime), $tmpuser[0]), $hcms_lang['date-by-user'][$lang]), $charset, $lang);
                          elseif ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview") $contentcomment .= "
                          <div class=\"hcms_comment_header\">".getescapedtext (str_replace(array('%date%', '%user%'), array(date($date_format, $microtime), $tmpuser[0]), $hcms_lang['date-by-user'][$lang]), $charset, $lang);

                          // is the current user allowed to delete a comment
                          if (($tmpuser[0] == $user || checkadminpermission () || checkglobalpermission ($site, 'user')) && empty ($disabled))
                          {
                            if ($buildview == "formedit" || $buildview == "formmeta") $contentcomment .= "
                          <span style=\"float:right;\"><input id=\"textf_".$tmpid[0]."\" type=\"hidden\" name=\"textf[".$tmpid[0]."]\" disabled=\"disabled\" /><input id=\"delete_".$tmpid[0]."\" class=\"is_comment ".$hypertagname."_".$id."\" type=\"checkbox\" onclick=\"deleteComment(document.getElementById('textf_".$tmpid[0]."'), !this.checked);\"/>&nbsp;<label for=\"delete_".$tmpid[0]."\">".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."</label>&nbsp;</span>";
                            elseif ($buildview == "cmsview" || $buildview == "inlineview") $contentcomment .= "
                          <span style=\"float:right;\"><img src=\"".getthemelocation()."img/edit_delete.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."service/savecontent.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&textf[".$tmpid[0]."]=&token=".$token."';\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" /></span> ";
                          }

                          $contentcomment .= "
                        </div>";

                          if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") $contentcomment .= "
                        <div class=\"hcmsRowData2\" style=\"padding:3px;\">".$tmpcontent[0]."</div>\n";
                          elseif ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview") $contentcomment .= "
                        <div class=\"hcms_comment_content\">".$tmpcontent[0]."</div>\n";

                          $contentcomment .= "
                      </div>";
                        }
                      }

                      if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                      {
                        // only for form views
                        $contentcomment .= "
                    </div>";
                      }
                    }
                  }
                  // all other cases
                  else
                  {
                    // get content
                    $bufferarray = selectcontent ($contentdata, "<text>", "<text_id>", $id);
                    if (!empty ($bufferarray[0])) $bufferarray = getcontent ($bufferarray[0], "<textcontent>", true);
                    if (!empty ($bufferarray[0])) $contentbot = $bufferarray[0];
                  }
                }

                // set default value defined by tag
                if (empty ($contentbot) && $defaultvalue != "") $contentbot = $defaultvalue;

                // encode scripts in content
                $contentbot = scriptcode_encode ($contentbot);

                // un-comment html tags (important for formatted texts)
                if (!empty ($contentbot))
                {
                  if ($hypertagname == $searchtag."f")
                  {
                    $contentbot = str_replace ("<![CDATA[", "", $contentbot);
                    $contentbot = str_replace ("]]>", "", $contentbot);
                  }
                  // replace \r and \n with <br /> (important for non-formatted texts)
                  elseif ($hypertagname == $searchtag."u" && $application != "xml" && $buildview != "formedit" && $buildview != "formmeta" && $buildview != "formlock")
                  {
                    if (@substr_count ($contentbot, "\r\n") >= 1)
                    {
                      $contentbot = str_replace ("\r\n", "<br />", $contentbot);
                    }
                    if (@substr_count ($contentbot, "\n\r") >= 1)
                    {
                      $contentbot = str_replace ("\n\r", "<br />", $contentbot);
                    }
                    if (@substr_count ($contentbot, "\n") >= 1)
                    {
                      $contentbot = str_replace ("\n", "<br />", $contentbot);
                    }
                    if (@substr_count ($contentbot, "\r") >= 1)
                    {
                      $contentbot = str_replace ("\r", "<br />", $contentbot);
                    }

                    // escape special characters
                    if (in_array ($buildview, array("formmeta","formedit","formlock")))
                    {
                      $contentbot = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $contentbot);
                    }
                  }
                }

                // replace img-src with reference to the newly generated image if a colorspace or ICC profile is requested
                if (!empty ($contentbot) && ($buildview == "publish") && $hypertagname == $searchtag."f" && ($colorspace != "" || $iccprofile != ""))
                {
                  //create new dom object
                  $dom = new DOMDocument();
                  $dom->loadHTML($contentbot);
                  $imagelocation = array();

                  // parse for img tags
                  foreach ($dom->getElementsByTagName('img') as $img)
                  {
                    //get src attribute of img tag
                    $source = $img->getAttribute('src');
                    $imagelocation['source'][] = $source;

                    // get abs location of the image
                    $imgdir = getmedialocation ($site, $source, "abs_path_media");
                    $imginfo = getfileinfo ($site, $source, "comp");

                    //convert image to PNG in the requested colorspace or ICC profile
                    $destination_file = convertimage ($site, $imgdir.$site."/".$imginfo['file'], $mgmt_config['abs_path_view'], "png", $colorspace, $iccprofile);

                    // define url of converted image
                    $imagelocation['destination'][] = $mgmt_config['url_path_view'].$destination_file;
                  }

                  // replace the src attributes in the img tags with
                  if (!empty ($imagelocation['source']) && !empty ($imagelocation['destination']))
                  {
                    $contentbot = str_replace ($imagelocation['source'], $imagelocation['destination'], $contentbot);
                  }
                }

                // replace img-src with reference to the requested pathtype (file, url, uri, download, wrapper, location)
                if (!empty ($contentbot) && $buildview == "publish" && !empty ($mediapathtype) && $mediapathtype != "url")
                {
                  $link_array = extractlinks ($contentbot, "href");

                  if (is_array ($link_array) && sizeof ($link_array) > 0)
                  {
                    foreach ($link_array as $temp)
                    {
                      // only replace media links
                      if (strpos ("_".$temp, "%media%/") > 0)
                      {
                        // file path
                        if ($mediapathtype == "file")
                        {
                          // replace the media variables with the media root
                          $temp_new = str_replace ("%media%", substr ($publ_config['abs_publ_media'], 0, strlen ($publ_config['abs_publ_media'])-1), $temp);
                        }
                        // if pathytpe == uri (deprecated value: abs) (URI = URL w/o protocol and domain)
                        elseif ($mediapathtype == "uri" || $mediapathtype == "abs")
                        {
                          // replace the media variables with the media root
                          $temp_new = str_replace ("%media%", substr ($publ_config['url_publ_media'], 0, strlen ($publ_config['url_publ_media'])-1), $temp); 
                          $temp_new = cleandomain ($temp_new);
                        }
                        // if pathytpe == wrapper (wrapper link)
                        elseif ($mediapathtype == "wrapper" && getmediacontainerid ($temp))
                        {
                          $temp_new = createwrapperlink ("", "", "", "", "", getmediacontainerid ($temp));
                        }
                        // if pathytpe == download (download link)
                        elseif ($mediapathtype == "download" && getmediacontainerid ($temp))
                        {
                          $temp_new = createdownloadlink ("", "", "", "", "", getmediacontainerid ($temp));
                        }
                        // if pathytpe == location (media location path since the object path bis not available)
                        elseif ($mediapathtype == "location")
                        {
                          $temp_new = "%media%/".$temp;
                        }

                        // replace media link
                        if (!empty ($temp)) $contentbot = str_replace ($temp, $temp_new, $contentbot);
                      }
                    }
                  }
                }

                // replace %variables% with pathes in text content
                if (!empty ($contentbot))
                {
                  // transform cms link used for video player
                  $contentbot = str_replace ("%hcms%", substr ($mgmt_config['url_path_cms'], 0, strlen ($mgmt_config['url_path_cms'])-1), $contentbot);

                  // transform links in old versions before 5.5.5 (%url_page%, %url_comp%)
                  $contentbot = str_replace ("%url_page%", "%page%/".$site, $contentbot);
                  $contentbot = str_replace ("%url_comp%", "%comp%", $contentbot);

                  // use publish settings
                  if ($buildview == "publish")
                  {
                    // replace the media variables with the media root
                    $contentbot = str_replace ("%media%", substr ($publ_config['url_publ_media'], 0, strlen ($publ_config['url_publ_media'])-1), $contentbot);

                    // replace the object variables with the URL of the page root
                    $contentbot = str_replace ("%page%/".$site, substr ($publ_config['url_publ_page'], 0, strlen ($publ_config['url_publ_page'])-1), $contentbot);

                    // replace the url_comp variables with the URL of the component root
                    $contentbot = str_replace ("%comp%", substr ($publ_config['url_publ_comp'], 0, strlen ($publ_config['url_publ_comp'])-1), $contentbot);
                  }
                  // use management settings
                  else
                  {
                    // replace the media variables with the media root
                    $contentbot = str_replace ("%media%", substr ($mgmt_config['url_path_media'], 0, strlen ($mgmt_config['url_path_media'])-1), $contentbot);

                    // replace the object variables with the URL of the page root
                    $contentbot = str_replace ("%page%/".$site, substr ($mgmt_config[$site]['url_path_page'], 0, strlen ($mgmt_config[$site]['url_path_page'])-1), $contentbot);

                    // replace the url_comp variables with the URL of the component root
                    $contentbot = str_replace ("%comp%", substr ($mgmt_config['url_path_comp'], 0, strlen ($mgmt_config['url_path_comp'])-1), $contentbot);
                  }
                }

                // escape variable and add slashes if onedit=hidden
                if ($onedit == "hidden")
                {
                  $contentbot = addslashes ($contentbot);
                  $contentbot = str_replace ("\$", "\\\$", $contentbot);
                }
              }

              // -------------------------- cmsview and hcms_formviews ---------------------------

              // in order to access the content via JS
              if ($groupaccess != true && ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock"))
              {
                // for articles
                $id = str_replace (":", "_", $id);

                $formitem[$key] = "
                <input type=\"hidden\" id=\"".$hypertagname."_".$id."\" value=\"".$contentbot."\" />";
              }

              if (
                   checklanguage ($language_sessionvalues_array, $language_info) && $onedit != "hidden" && $groupaccess == true &&
                   (
                     (($buildview == "cmsview" || $buildview == 'inlineview') && $infotype != "meta") ||
                     $buildview == "formedit" || ($buildview == "formmeta" && $infotype == "meta") || $buildview == "formlock" || $buildview == "template"
                   )
                 )
              {
                $taglink = "";

                // replace hyperCMS tag with given conent/data
                if ($hypertagname == $searchtag."u" || $hypertagname == $searchtag."k")
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;

                  // loop for unique media names for rollover effect
                  while (!empty ($hypertag) && substr_count ($viewstore_offset, $hypertag) > 0)
                  {
                    if ($searchtag == "text")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_textu.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_unformat.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&constraint=".url_encode($constraint)."&contenttype=".url_encode($contenttype)."&width=".url_encode($sizewidth)."&height=".url_encode($sizeheight)."&default=".url_encode($defaultvalue)."&token=".$token."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-unformatted-text'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-unformatted-text'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        // extract display
                        $display = strtolower (getattribute ($hypertag, "display"));

                        // submitText and constraints do not apply for the taxonomy tree (checkboxes)
                        if ($display != "taxonomy")
                        {
                          $add_submittext .= "
                          submitText ('".$hypertagname."_".$id."', '".$hypertagname."[".$id."]');";

                          if ($constraint != "") $constraint_array[$key] = "'".$hypertagname."_".$id."', '".$labelname."', '".$constraint."'";
                        }

                        // if keyword list
                        if ($hypertagname == $searchtag."k")
                        {
                          $list = "";

                          // extract source file (file path or URL) for text list
                          $list_sourcefile = getattribute ($hypertag, "file");

                          // taxonomy tree view
                          if ($display == "taxonomy")
                          {
                            // list_sourcefile must be a valid taxonomy path: %taxonomy%/site/language-code/taxonomy-ID/taxonomy-child-levels
                            $formitem[$key] = "
                            <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                              <b>".$labelname."</b>
                            </div>
                            <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\" style=\"position:relative; width:".$sizewidth.(strpos ($sizewidth, "%") > 0 ? "" : "px")."; height:".$sizeheight."px;\">
                              ".showtaxonomytree ($site, $container_id, $id, $hypertagname, $lang, $list_sourcefile, $sizewidth, $sizeheight)."
                            </div>";
                          }
                          // keyword list view (default)
                          else
                          {
                            // get list items (incl. taxonomy)
                            if ($list_sourcefile != "")
                            {
                              $list .= getlistelements ($list_sourcefile);
                            }
  
                            // extract text list
                            $list_add = getattribute ($hypertag, "list");
  
                            // add separator
                            if ($list_add != "") $list = $list_add.",".$list;
  
                            // extract text list
                            $onlylist = strtolower (getattribute ($hypertag, "onlylist"));

                            // get list entries
                            if ($list != "")
                            {
                              // replace line breaks
                              $list = str_replace ("\r\n", ",", $list);
                              $list = str_replace ("\n", ",", $list);
                              $list = str_replace ("\r", ",", $list);
                              // escape single quotes
                              $list = str_replace ("'", "\\'", $list);
                              // create array
                              $list_array = explode (",", $list);
                              // create keyword string for Javascript
                              $keywords = "['".implode ("', '", $list_array)."']";

                              $keywords_tagit = "availableTags:".$keywords.", ";

                              if ($onlylist == "true" || $onlylist == "yes" || $onlylist == "1")
                              {
                                $keywords_tagit .= "beforeTagAdded: function(event, ui) { if ($.inArray(ui.tagLabel, ".$keywords.") == -1) { return false; } }, ";
                              }
                            }
                            else $keywords_tagit = "availableTags:[], ";

                            $add_onload .= "
      $('#".$hypertagname."_".$id."').tagit({".$keywords_tagit.(!empty ($disabled) ? "readOnly:true, " : "")."singleField:true, allowSpaces:true, singleFieldDelimiter:',', singleFieldNode:$('#".$hypertagname."_".$id."')});";

                            $formitem[$key] = "
                            <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                              <b>".$labelname."</b>
                            </div>
                            <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\" style=\"position:relative; width:".$sizewidth.(strpos ($sizewidth, "%") > 0 ? "" : "px").";\">
                              <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" />
                              <input type=\"text\" id=\"".$hypertagname."_".$id."\" name=\"".$hypertagname."_".$id."\" style=\"width:".$sizewidth."px;\" ".$disabled." value=\"".$contentbot."\" />
                              <div id=\"".$hypertagname."_".$id."_protect\" style=\"position:absolute; top:0; left:0; width:".$sizewidth."px; height:100%; display:none;\"></div>
                            </div>";
                          }
                        }
                        // if unformatted text (supports preview window)
                        else
                        {
                          if (strtolower ($preview_window) == "url")
                          {
                            $iconwidth = 36;
                          }
                          else
                          {
                            $iconwidth = 0;
                          }

                          $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                            <b>".$labelname."</b>
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                            <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" />
                            ".showtranslator ($site, $hypertagname."_".$id, "u", $charset, $lang, "width:".($sizewidth + 4)."px; text-align:right; padding:1px 0px 1px 0px; border-top:1px solid #C0C0C0;")."
                            <textarea id=\"".$hypertagname."_".$id."\" name=\"".$hypertagname."_".$id."\" style=\"width:".($sizewidth - $iconwidth)."px; height:".$sizeheight."px; display:inline;\" ".$disabled.">".$contentbot."</textarea>";

                            if (strtolower ($preview_window) == "url") $formitem[$key] .= "<div onClick=\"if (document.getElementById('".$hypertagname."_".$id."').value != '') hcms_openWindow(document.getElementById('".$hypertagname."_".$id."').value, 'preview', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', ".windowwidth ("object").", ".windowheight ("object").")\" class=\"hcmsButtonSizeSquare\" style=\"display:inline;\"><img name=\"ButtonView\" src=\"".getthemelocation()."img/icon_newwindow.png\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['in-new-browser-window'][$lang])."\" title=\"".getescapedtext ($hcms_lang['in-new-browser-window'][$lang])."\" /></div>";

                            $formitem[$key] .= "
                          </div>";
                        }
                      } 
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-text-entries'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    // unformatted text
                    elseif ($searchtag == "arttext")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview" && $infotype != "meta")
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_textu.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_unformat.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&constraint=".url_encode($constraint)."&contenttype=".url_encode($contenttype)."&width=".url_encode($sizewidth)."&height=".url_encode($sizeheight)."&default=".url_encode($defaultvalue)."&token=".$token."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-unformatted-text'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-unformatted-text'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />".$arttaglink[$artid];
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        $add_submittext .= "
                        submitText ('".$hypertagname."_".$artid."_".$elementid."', '".$hypertagname."[".$id."]');";

                        if ($constraint != "") $constraint_array[$key] = "'".$hypertagname."_".$id."', '".$labelname."', '".$constraint."'";

                        $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname."_".$artid."_".$elementid."\">
                            <b>".$labelname."</b> ".$arttaglink[$artid]."
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$artid."_".$elementid."\">
                            <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" />
                            ".showtranslator ($site, $hypertagname."_".$id, "u", $charset, $lang, "width:".($sizewidth + 4)."px; text-align:right; padding:1px 0px 1px 0px; border-top:1px solid #C0C0C0;")."
                            <textarea id=\"".$hypertagname."_".$artid."_".$elementid."\" name=\"".$hypertagname."_".$artid."_".$elementid."\" style=\"width:".($sizewidth)."px; height:".$sizeheight."px;\" ".$disabled.">".$contentbot."</textarea>
                          </div>";
                      }
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Article: ".$artid."</b><br />
                              <b>Element: ".$elementid."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-text-entries'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    // unformatted comments
                    elseif ($searchtag == "comment")
                    {
                      if (!empty ($contentcomment) && ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview"))
                      {
                        $taglink = $contentcomment;
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        $add_submittext .= "
                        submitText ('".$hypertagname."_".$id."', '".$hypertagname."[".$id."]');";

                        $formitem[$key] = "
                        <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                          <b>".$labelname."</b>
                        </div>";

                        if (!empty ($contentcomment)) $formitem[$key] .= "
                        <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                          ".$contentcomment."
                        </div>";

                        $formitem[$key] .= "
                        <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                          <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" />
                          <textarea class=\"is_comment\" name=\"".$hypertagname."_".$id."\" style=\"width:".$sizewidth."px; height:".$sizeheight."px;\" ".$disabled."></textarea>
                        </div>";
                      } 
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-comments'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }

                    // insert taglink
                    $repl_start = strpos ($viewstore, $hypertag, $repl_offset);
                    $repl_offset = $repl_start + strlen ($taglink.$hypertag);
                    $viewstore = substr_replace ($viewstore, $taglink, $repl_start, 0);
                    $viewstore_offset = substr ($viewstore, $repl_offset);
                  }
                }
                elseif ($hypertagname == $searchtag."f")
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;

                  while (!empty ($hypertag) && substr_count ($viewstore_offset, $hypertag) > 0)
                  {
                    if ($searchtag == "text")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_textf.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_format.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&width=".url_encode($sizewidth)."&height=".url_encode($sizeheight)."&dpi=".url_encode($dpi)."&toolbar=".url_encode($toolbar)."&default=".url_encode($defaultvalue)."&token=".$token."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-formatted-text'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-formatted-text'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        // setting the toolbar
                        if (empty ($toolbar)) $toolbar = 'Default';

                        if ($buildview == "formlock" || !empty ($disabled))
                        {
                          $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                            <b>".$labelname."</b>
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                            <div style=\"width:".$sizewidth."px; height:".$sizeheight."px; border:1px solid #000000; background-color:#FFFFFF; padding:2px;\">
                              ".$contentbot."
                            </div>
                          </div>";
                        }
                        else
                        {
                          $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                            <b>".$labelname."</b>
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                            ".showtranslator ($site, $hypertagname."_".$id, "f", $charset, $lang, "width:".($sizewidth + 4)."px; text-align:right; padding:1px 0px 1px 0px; border-top:1px solid #C0C0C0;")."
                            ".showeditor ($site, $hypertagname, $id, $contentbot, $sizewidth, $sizeheight, $toolbar, $lang, $dpi)."
                          </div>";
                        }
                      } 
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-formatted-text-entries'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    // formatted text
                    elseif ($searchtag == "arttext")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_textf.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."editorf.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&contentfile=".url_encode($contentfile)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&width=".url_encode($sizewidth)."&height=".url_encode($sizeheight)."&dpi=".url_encode($dpi)."&toolbar=".url_encode($toolbar)."&default=".url_encode($defaultvalue)."&token=".$token."';\" alt=\"".$artid.": ".$elementid.": ".getescapedtext ($hcms_lang['edit-formatted-text'][$lang], $charset, $lang)."\" title=\"".$artid.": ".$elementid.": ".getescapedtext ($hcms_lang['edit-formatted-text'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />".$arttaglink[$artid];
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        // setting the toolbar
                        if (empty ($toolbar)) $toolbar = 'Default'; 

                        if ($buildview == "formlock" || !empty ($disabled))
                        {
                          $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname."_".$artid."_".$elementid."\">
                            <b>".$labelname."</b>
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$artid."_".$elementid."\">
                            <div style=\"width:".$sizewidth."px; height:".$sizeheight."px; border:1px solid #000000; background-color:#FFFFFF; padding:2px;\">
                              ".$contentbot."
                            <div>
                          </div>";
                        }
                        else
                        {
                          $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname."_".$artid."_".$elementid."\">
                            <b>".$labelname."</b> ".$arttaglink[$artid]."
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$artid."_".$elementid."\">
                            ".showtranslator ($site, $hypertagname."_".$id, "f", $charset, $lang, "width:".($sizewidth + 4)."px; text-align:right; padding:1px 0px 1px 0px border-top:1px solid #C0C0C0;")."
                            ".showeditor ($site, $hypertagname, $id, $contentbot, $sizewidth, $sizeheight, $toolbar, $lang, $dpi)."
                          </div>";
                        }
                      } 
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Article: ".$artid."</b><br />
                              <b>Element: ".$elementid."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-formatted-text-entries'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    // formatted comment
                    elseif ($searchtag == "comment")
                    {
                      if (!empty ($contentcomment) && ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview"))
                      {
                        $taglink = $contentcomment;
                      }
                      // create tag link for editor
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        // setting the toolbar
                        if (empty ($toolbar)) $toolbar = 'Default';

                        if ($buildview == "formlock" || !empty ($disabled))
                        {
                          $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                            <b>".$labelname."</b>
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                            ".$contentcomment."
                          </div>";
                        }
                        else
                        {
                          $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                            <b>".$labelname."</b>
                          </div>";

                          if (!empty ($contentcomment)) $formitem[$key] .= "
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                            ".$contentcomment."
                          </div>";

                          $formitem[$key] .= "
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                            ".showtranslator ($site, $hypertagname."_".$id, "f", $charset, $lang, "width:".($sizewidth + 4)."px; text-align:right; padding:1px 0px 1px 0px; border-top:1px solid #C0C0C0;")."
                            ".showeditor ($site, $hypertagname, $id, $contentbot, $sizewidth, $sizeheight, $toolbar, $lang, $dpi)."
                          </div>";
                        }
                      } 
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".$id."</b><br />
                              ".$hcms_lang['this-place-is-reserved-for-formatted-comments'][$lang]."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }

                    // insert taglink
                    $repl_start = strpos ($viewstore, $hypertag, $repl_offset);
                    $repl_offset = $repl_start + strlen ($taglink.$hypertag);
                    $viewstore = substr_replace ($viewstore, $taglink, $repl_start, 0);
                    $viewstore_offset = substr ($viewstore, $repl_offset);
                  }
                }
                elseif ($hypertagname == $searchtag."l")
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;

                  while (!empty ($hypertag) && substr_count ($viewstore_offset, $hypertag) > 0)
                  {
                    $list = "";

                    // extract source file (file path or URL) for text list
                    $list_sourcefile = getattribute ($hypertag, "file");

                    if ($list_sourcefile != "")
                    {
                      $list .= getlistelements ($list_sourcefile);
                      // replace commas
                      $list = str_replace (",", "|", $list);
                    }

                    // extract text list
                    $list_add = getattribute ($hypertag, "list");

                    // add seperator
                    if ($list_add != "") $list = $list_add."|".$list;

                    if ($searchtag == "text")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_textl.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_list.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&list=".url_encode($list)."&contenttype=".url_encode($contenttype)."&default=".url_encode($defaultvalue)."&token=".$token."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-text-options'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-text-options'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        // get list entries
                        if ($list != "")
                        {
                          $list = rtrim ($list, "|");
                          $list_array = explode ("|", $list);

                          $formitem[$key] = "
                            <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                              <b>".$labelname."</b>
                            </div>
                            <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                              <select id=\"".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" ".$disabled.">";

                          foreach ($list_array as $list_entry)
                          {
                            $list_entry = trim ($list_entry);
                            $end_val = strlen ($list_entry)-1;

                            if (($start_val = strpos($list_entry, "{")) > 0 && strpos($list_entry, "}") == $end_val)
                            {
                              $diff_val = $end_val-$start_val-1;
                              $list_value = substr ($list_entry, $start_val+1, $diff_val);
                              $list_text = substr ($list_entry, 0, $start_val);
                            }
                            else $list_value = $list_text = $list_entry;
 
                            $formitem[$key] .= "
                                <option value=\"".$list_value."\"".($list_value == $contentbot ? " selected" : "").">".$list_text."</option>";
                          }

                          $formitem[$key] .= "
                              </select>
                            </div>";
                        }
                      }
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-text-options'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    elseif ($searchtag == "arttext")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_textl.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_list.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&contentfile=".url_encode($contentfile)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&list=".url_encode($list)."&contenttype=".url_encode($contenttype)."&default=".url_encode($defaultvalue)."&token=".$token."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-text-options'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-text-options'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />".$arttaglink[$artid]."\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        // get list entries
                        $list_array = null;
                        $list_array = explode ("|", $list);

                        $formitem[$key] = "
                        <div class=\"hcmsFormRowLabel ".$hypertagname."_".$artid."_".$elementid."\">
                          <b>".$labelname."</b> ".$arttaglink[$artid]."
                        </div>
                        <div class=\"hcmsFormRowContent ".$hypertagname."_".$artid."_".$elementid."\">
                          <select id=\"".$hypertagname."_".$artid."_".$elementid."\" name=\"".$hypertagname."[".$id."]\" ".$disabled.">\n";

                        foreach ($list_array as $list_entry)
                        {
                          $end_val = strlen($list_entry)-1;

                          if (($start_val = strpos($list_entry, "{")) > 0 && strpos($list_entry, "}") == $end_val)
                          {
                            $diff_val = $end_val-$start_val-1;
                            $list_value = substr($list_entry, $start_val+1, $diff_val);
                            $list_text = substr($list_entry, 0, $start_val);
                          }
                          else $list_value = $list_text = $list_entry;

                          $formitem[$key] .= "
                            <option value=\"".$list_value."\"".($list_entry == $contentbot ? " selected" : "").">".$list_text."</option>\n";
                        }

                        $formitem[$key] .= "
                          </select>
                        </div>";
                      } 
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Article: ".$artid."</b><br />
                              <b>Element: ".$elementid."</b><br />
                              ".$hcms_lang['this-place-is-reserved-for-text-options'][$lang]."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }

                    // insert taglink
                    $repl_start = strpos ($viewstore, $hypertag, $repl_offset);
                    $repl_offset = $repl_start + strlen ($taglink.$hypertag);
                    $viewstore = substr_replace ($viewstore, $taglink, $repl_start, 0);
                    $viewstore_offset = substr ($viewstore, $repl_offset); 
                  }
                }
                // Check Box
                elseif ($hypertagname == $searchtag."c")
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;

                  while (!empty ($hypertag) && substr_count ($viewstore_offset, $hypertag) > 0)
                  { 
                    if ($searchtag == "text")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_textc.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_checkbox.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&value=".url_encode($value)."&contentbot=".url_encode($contentbot)."&default=".url_encode($defaultvalue)."&token=".$token."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['set-checkbox'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-checkbox'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        if ($value == $contentbot) $checked = " checked";
                        else $checked = "";

                        $formitem[$key] = "
                        <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                          <b>".$labelname."</b>
                        </div>
                        <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                          <input type=\"hidden\" name=\"".$hypertagname."[".$id."]"."\" value=\"\" />
                          <label><input type=\"checkbox\" id=\"".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" value=\"".$value."\"".$checked.$disabled." /> ".$value."</label>
                        </div>";
                      }
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\"0>
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-a-checkbox'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    elseif ($searchtag == "arttext")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_textc.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_checkbox.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&value=".url_encode($value)."&contentbot=".url_encode($contentbot)."&default=".url_encode($defaultvalue)."&token=".$token."';\" style=\"all:unset; display:inline !important;\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['set-checkbox'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-checkbox'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />".$arttaglink[$artid]."\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        if ($value == $contentbot) $checked = " checked";
                        else $checked = "";

                        $formitem[$key] = "
                        <div class=\"hcmsFormRowLabel ".$hypertagname."_".$artid."_".$elementid."\">
                          <b>".$labelname."</b> ".$arttaglink[$artid]."
                        </div>
                        <div class=\"hcmsFormRowContent ".$hypertagname."_".$artid."_".$elementid."\">
                          <input type=\"hidden\" name=\"".$hypertagname."[".$id."]"."\" value=\"\" />
                          <label><input type=\"checkbox\" id=\"".$hypertagname."_".$artid."_".$elementid."\" name=\"".$hypertagname."[".$id."]\" value=\"".$value."\"".$checked.$disabled." /> ".$value."</label>
                        </div>";
                      } 
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Article: ".$artid."</b><br />
                              <b>Element: ".$elementid."</b><br />
                              ".$hcms_lang['this-place-is-reserved-for-a-checkbox'][$lang]."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }

                    // insert taglink 
                    $repl_start = strpos ($viewstore, $hypertag, $repl_offset);
                    $repl_offset = $repl_start + strlen ($taglink.$hypertag);
                    $viewstore = substr_replace ($viewstore, $taglink, $repl_start, 0);
                    $viewstore_offset = substr ($viewstore, $repl_offset);
                  }
                }
                // Date
                elseif ($hypertagname == $searchtag."d")
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;

                  while (!empty ($hypertag) && substr_count ($viewstore_offset, $hypertag) > 0)
                  { 
                    if ($searchtag == "text")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_textd.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_date.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&format=".url_encode($format)."&contentbot=".url_encode($contentbot)."&default=".url_encode($defaultvalue)."&token=".$token."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      { 
                        if (empty ($disabled)) $showcalendar = "onclick=\"show_cal(this, '".$hypertagname."_".$id."', '".$format."');\"";
                        else $showcalendar = "";

                        $formitem[$key] = "
                        <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                          <b>".$labelname."</b>
                        </div>
                        <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                          <input type=\"text\" id=\"".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" readonly=\"readonly\" ".$disabled." /><img id=\"".$hypertagname."_".$id."_controls\" src=\"".getthemelocation()."img/button_datepicker.png\" ".$showcalendar." class=\"hcmsButtonTiny hcmsButtonSizeSquare\" style=\"z-index:9999999;\" alt=\"".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" ".$disabled." /> 
                        </div>";
                      }
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\"0>
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-a-date-field'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    elseif ($searchtag == "arttext")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_textd.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_date.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&format=".url_encode($format)."&contentbot=".url_encode($contentbot)."&default=".url_encode($defaultvalue)."&token=".$token."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />".$arttaglink[$artid]."\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        if ($disabled == "") $showcalendar = "onclick=\"show_cal(this, '".$hypertagname."_".$artid."_".$elementid."', '".$format."');\"";
                        else $showcalendar = "";

                        $formitem[$key] = "
                        <div class=\"hcmsFormRowLabel ".$hypertagname."_".$artid."_".$elementid."\">
                          <b>".$labelname."</b> ".$arttaglink[$artid]."
                        </div>
                        <div class=\"hcmsFormRowContent ".$hypertagname."_".$artid."_".$elementid."\">
                          <input type=\"text\" id=\"".$hypertagname."_".$artid."_".$elementid."\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" readonly=\"readonly\" ".$disabled." /><img id=\"".$hypertagname."_".$artid."_".$elementid."_controls\" src=\"".getthemelocation()."img/button_datepicker.png\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" ".$showcalendar." style=\"z-index:9999999;\" alt=\"".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" ".$disabled." />  
                        </div>";
                      } 
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Article: ".$artid."</b><br />
                              <b>Element: ".$elementid."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-a-checkbox'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }

                    // insert taglink 
                    $repl_start = strpos ($viewstore, $hypertag, $repl_offset);
                    $repl_offset = $repl_start + strlen ($taglink.$hypertag);
                    $viewstore = substr_replace ($viewstore, $taglink, $repl_start, 0);
                    $viewstore_offset = substr ($viewstore, $repl_offset);
                  }
                }
                // Signature
                elseif ($hypertagname == $searchtag."s")
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;

                  while (!empty ($hypertag) && substr_count ($viewstore_offset, $hypertag) > 0)
                  { 
                    if ($searchtag == "text")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_signature.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_signature.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&constraint=".url_encode($constraint)."&contenttype=".url_encode($contenttype)."&default=".url_encode($defaultvalue)."&width=".url_encode($sizewidth)."&height=".url_encode($sizeheight)."&token=".$token."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['signature'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['signature'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />\n";

                        // signature field size
                        $style = "";
                        if ($sizewidth > 0) $style .= "width:".$sizewidth."px; ";
                        if ($sizeheight > 0) $style .= "height:".$sizeheight."px; ";

                        // existing signature
                        if (!empty ($contentbot) && strlen ($contentbot) > 30) $contentbot = "<img id=\"signatureimage_".$hypertagname."_".$id."\" src=\"data:".$contentbot."\" style=\"".$style." padding:0 !important; max-width:100%; max-height:100%;\" />";
                        else $contentbot = "";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        if ($constraint != "") $constraint_array[$key] = "'".$hypertagname."_".$id."', '".$labelname."', '".$constraint."'";

                        // signature field size
                        $style = "";
                        if ($sizewidth > 0) $style .= "width:".$sizewidth."px;";
                        // no height will be used since the signature canvas will define the height
                        // if ($sizeheight > 0) $style .= "height:".$sizeheight."px; ";

                        // existing signature
                        if (!empty ($contentbot)) $signature_image = "<img id=\"signatureimage_".$hypertagname."_".$id."\" onclick=\"$('#signatureimage_".$hypertagname."_".$id."').hide(); $('#signaturefield_".$hypertagname."_".$id."').show();\" src=\"data:".$contentbot."\" class=\"hcmsTextArea\" style=\"".$style." display:none; padding:0 !important; max-width:100%; max-height:100%;\" />";
                        else $signature_image = "";

                        $formitem[$key] = "
                        <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                          <b>".$labelname."</b>
                        </div>
                        <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                          ".$signature_image."
                          <div id=\"signaturefield_".$hypertagname."_".$id."\" style=\"".$style."\">
                            <div id=\"signature_".$hypertagname."_".$id."\" style=\"outline:2px dotted #000000; background-color:#FFFFFF; color:darkblue;\"></div>
                            <div style=\"position:relative; float:right; margin:-36px 5px 0px 0px;\">
                              <img src=\"".getthemelocation("day")."img/button_delete.png\" onclick=\"resetSignature('".$hypertagname."_".$id."');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" />
                            </div>
                            <input id=\"".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" type=\"hidden\" value=\"".$contentbot."\" />
                          </div>

                          <script type=\"text/javascript\">
                          $(document).ready(function(){
                            initializeSignature ('".$hypertagname."_".$id."');
                          });
                          </script>

                        </div>";
                      }
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\"0>
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-text-entries'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else
                      {
                        $taglink = "";
                      }
                    }
                    elseif ($searchtag == "arttext")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_signature.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."text_edit_signature.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&default=".url_encode($defaultvalue)."&width=".url_encode($sizewidth)."&height=".url_encode($sizeheight)."&token=".$token."';\" style=\"all:unset; display:inline !important;\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['signature'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['signature'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />".$arttaglink[$artid]."\n";

                        // signature field size
                        $style = "";
                        if ($sizewidth > 0) $style .= "width:".$sizewidth."px; ";
                        if ($sizeheight > 0) $style .= "height:".$sizeheight."px; ";

                        // existing signature
                        if (!empty ($contentbot) && strlen ($contentbot) > 30) $contentbot = "<img id=\"signatureimage_".$hypertagname."_".$artid."_".$elementid."\" src=\"data:".$contentbot."\" style=\"".$style." padding:0 !important; max-width:100%; max-height:100%;\" />";
                        else $contentbot = "";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        $formitem[$key] = "
                        <div class=\"hcmsFormRowLabel ".$hypertagname."_".$artid."_".$elementid."\">
                          <b>".$labelname."</b> ".$arttaglink[$artid]."
                        </div>
                        <div class=\"hcmsFormRowContent ".$hypertagname."_".$artid."_".$elementid."\">
                          <input type=\"hidden\" name=\"".$hypertagname."[".$id."]"."\" value=\"\" />
                          <label><input type=\"checkbox\" id=\"".$hypertagname."_".$artid."_".$elementid."\" name=\"".$hypertagname."[".$id."]\" value=\"".$value."\"".$checked.$disabled." /> ".$value."</label>
                        </div>";

                        if ($constraint != "") $constraint_array[$key] = "'".$hypertagname."_".$id."', '".$labelname."', '".$constraint."'";

                        // signature field size
                        $style = "";
                        if ($sizewidth > 0) $style .= "width:".$sizewidth."px;";
                        // no height will be used since the signature canvas will define the height
                        // if ($sizeheight > 0) $style .= "height:".$sizeheight."px; ";

                        // existing signature
                        if (!empty ($contentbot)) $signature_image = "<img id=\"signatureimage_".$hypertagname."_".$artid."_".$elementid."\" onclick=\"$('#signatureimage_".$hypertagname."_".$artid."_".$elementid."').hide(); $('#signaturefield_".$hypertagname."_".$artid."_".$elementid."').show();\" src=\"data:".$contentbot."\" class=\"hcmsTextArea\" style=\"".$style." display:none; padding:0 !important; max-width:100%; max-height:100%;\" />";
                        else $signature_image = "";

                        $formitem[$key] = "
                        <div class=\"hcmsFormRowLabel ".$hypertagname."_".$artid."_".$elementid."\">
                          <b>".$labelname."</b>
                        </div>
                        <div class=\"hcmsFormRowContent ".$hypertagname."_".$artid."_".$elementid."\">
                          ".$signature_image."
                          <div id=\"signaturefield_".$hypertagname."_".$artid."_".$elementid."\" style=\"".$style."\">
                            <div id=\"signature_".$hypertagname."_".$artid."_".$elementid."\" style=\"outline:2px dotted #000000; background-color:#FFFFFF; color:darkblue;\"></div>
                            <div style=\"position:relative; float:right; margin:-36px 5px 0px 0px;\">
                              <img src=\"".getthemelocation()."img/button_delete.png\" onclick=\"resetSignature('signature_".$hypertagname."_".$artid."_".$elementid."');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" />
                            </div>
                            <input id=\"".$hypertagname."_".$artid."_".$elementid."\" name=\"".$hypertagname."[".$id."]\" type=\"hidden\" value=\"".$contentbot."\" />
                          </div>

                          <script type=\"text/javascript\">
                          $(document).ready(function(){
                            initializeSignature ('".$hypertagname."_".$artid."_".$elementid."');
                          });
                          </script>

                        </div>";
                      } 
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Article: ".$artid."</b><br />
                              <b>Element: ".$elementid."</b><br />
                              ".$hcms_lang['this-place-is-reserved-for-text-entries'][$lang]."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else
                      {
                        $taglink = "";
                      }
                    }

                    // insert taglink 
                    $repl_start = strpos ($viewstore, $hypertag, $repl_offset);
                    $repl_offset = $repl_start + strlen ($taglink.$hypertag);
                    $viewstore = substr_replace ($viewstore, $taglink, $repl_start, 0);
                    $viewstore_offset = substr ($viewstore, $repl_offset);
                  }
                }
              }
            }

            // ------------------- publish / insert content -------------------
            if ($buildview != "template" && $buildview != "formedit" && $buildview != "formmeta" && $buildview != "formlock")
            {
              // prefix and suffix if the content is not empty
              $prefix = getattribute ($hypertag, "prefix", false);
              $suffix = getattribute ($hypertag, "suffix", false);

              // get replace attribute
              $replaces = getattribute ($hypertag, "replace");

              if ($contentbot != "" && $replaces != "")
              {
                // function getattribute escapes < and >
                $replaces = str_replace (array("&quot;", "&#039;", "&lt;", "&gt;"), array("\"", "'", "<", ">"), $replaces);
                $temp_array = explode ("|", $replaces);

                if (is_array ($temp_array))
                {
                  foreach ($temp_array as $temp)
                  {
                    if ($temp != "" && strpos ($temp, "=>") > 0)
                    {
                      list ($search, $replace) = explode ("=>", $temp);
                      $contentbot = str_replace ($search, $replace, $contentbot);
                    }
                  }
                }
              }

              // include time management code for article
              if ($buildview == "publish" && $searchtag == "arttext")
              {
                // reset prefix and suffix if content is empty
                if (empty ($contentbot))
                {
                  $prefix = "";
                  $suffix = "";
                }

                if ($artstatus[$artid] == "timeswitched")
                {
                  // escape specific characters
                  $contentbot = str_replace ("'", "\\'", $contentbot);
                  $contentbot = str_replace ("\$", "\\\$'", $contentbot);
                  $contentbot = str_replace ("\\", "\\\\", $contentbot);

                  $contentbot = tpl_tselement ($application, $artdatefrom[$artid], $artdateto[$artid], $prefix.$contentbot.$suffix);
                }
                elseif ($artstatus[$artid] == "inactive" && $searchtag == "arttext")
                {
                  $contentbot = "";
                }
              }

              // only for inline editing mode
              if ($buildview == "inlineview" && $onedit != "hidden" && $infotype != "meta" && $groupaccess == true)
              {
                // escape links to prevent transformlink to transform links used in the inline content
                $contentbot = str_replace (array (" href="," href =",".href=",".href ="), array(" hypercms_href="," hypercms_href =",".hypercms_href=",".hypercms_href ="), $contentbot);

                $contentbot = (!empty ($arttaglink[$artid]) ? $arttaglink[$artid] : "").showinlineeditor ($site, $hypertag, $id, $contentbot, $sizewidth, $sizeheight, $toolbar, $lang, $contenttype, $cat, $location_esc, $page, $contentfile, $db_connect, $token);

                // insert content
                $viewstore = str_replace ($hypertag, $prefix.$contentbot.$suffix, $viewstore);
              }
              // for all other modes
              else
              {
                // reset prefix and suffix if content is empty
                if (empty ($contentbot))
                {
                  $prefix = "";
                  $suffix = "";
                }

                // if signature image (base64 encoded)
                if (strpos ("_".$contentbot, "image/") == 1 && strpos ($contentbot, ";base64,") > 0)
                {
                  // signature field size (use width or height)
                  $style = "";
                  if ($sizewidth > 0) $style .= "width:".$sizewidth."px; ";
                  elseif ($sizeheight > 0) $style .= "height:".$sizeheight."px; ";

                  // existing signature
                  if (!empty ($contentbot) && strlen ($contentbot) > 30) $contentbot = "<img src=\"data:".$contentbot."\" style=\"".$style." padding:0 !important; max-width:100%; max-height:100%;\" />";
                  else $contentbot = "";
                }

                // insert content
                if ($onpublish != "hidden") $viewstore = str_replace ($hypertag, $prefix.$contentbot.$suffix, $viewstore);
                elseif ($onpublish == "hidden") $viewstore = str_replace ($hypertag, "", $viewstore);
              }
            }
            elseif ($buildview == "template")
            {
              // remove tags
              $viewstore = str_replace ($hypertag, "", $viewstore);
            }
          }
        }
      }

      // =================================================== media content ===================================================

      // create view for media content
      $searchtag_array = array();
      $searchtag_array[0] = "artmedia";
      $searchtag_array[1] = "media";
      $infotype = array();
      $position = array();
      $onpublish_file = array();
      $onpublish_text = array();
      $onpublish_align = array();
      $onpublish_width = array();
      $onpublish_height = array();
      $onedit_file = array();
      $label = array();
      $mediadpi= array();
      $mediacolorspace = array();
      $mediaiccprofile = array();
      $mediapathtype = array();
      $language_info = array();

      foreach ($searchtag_array as $searchtag)
      {
        // get all hyperCMS tags
        $hypertag_array = gethypertag ($viewstore, $searchtag, 0);

        if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
        {
          $id_array = array ();
          $tagid = 0;

          reset ($hypertag_array);

          // loop for each hyperCMS tag found in template
          foreach ($hypertag_array as $key => $hypertag)
          {
            $tagid++;

            // get tag name
            $hypertagname = gethypertagname ($hypertag);

            // get tag id
            $id = getattribute ($hypertag, "id");

            // get watermark image if id=Watermark
            if ($id == "Watermark" && !empty ($mediafile) && (is_image ($mediafile) || is_video ($mediafile))) $is_watermark = true;
            else $is_watermark = false;

            // if id uses special characters
            if (trim ($id) != "" && specialchr ($id, ":-_") == true)
            {
              $result['view'] = "<!DOCTYPE html>
<html>
  <head>
  <title>hyperCMS</title>
  <meta charset=\"".getcodepage ($lang)."\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />
  </head>
  <body class=\"hcmsWorkplaceGeneric\">
    <p class=hcmsHeadline>".getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters-in-the-content-identification-name'][$lang], $charset, $lang)." '".$id."':<br/>[\]{}()*+?.,\\^$</p>
  </body>
</html>";
              $result['release'] = 0;
              $result['container'] = $contentfile;
              $result['containerdata'] = $contentdata;
              $result['template'] = $templatefile;
              $result['templatedata'] = $templatedata;
              $result['templateext'] = $templateext;
              $result['name'] = $name_orig;
              $result['objecttype'] = $filetype;

              return $result;
            }

            // set default values if not set
            if (!isset ($hypertagname_align[$id])) $hypertagname_align[$id] = "";
            if (!isset ($hypertagname_text[$id])) $hypertagname_text[$id] = "";
            if (!isset ($hypertagname_width[$id])) $hypertagname_width[$id] = "";
            if (!isset ($hypertagname_height[$id])) $hypertagname_height[$id] = "";
            if (!isset ($hypertag_file[$id][$tagid])) $hypertag_file[$id][$tagid] = "";
            if (!isset ($hypertag_text[$id][$tagid])) $hypertag_text[$id][$tagid] = "";
            if (!isset ($hypertag_align[$id][$tagid])) $hypertag_align[$id][$tagid] = "";
            if (!isset ($hypertag_width[$id][$tagid])) $hypertag_width[$id][$tagid] = "";
            if (!isset ($hypertag_height[$id][$tagid])) $hypertag_height[$id][$tagid] = "";
            if (!isset ($onpublish_file[$id][$tagid])) $onpublish_file[$id][$tagid] = "";
            if (!isset ($onpublish_text[$id][$tagid])) $onpublish_text[$id][$tagid] = "";
            if (!isset ($onpublish_align[$id][$tagid])) $onpublish_align[$id][$tagid] = "";
            if (!isset ($onpublish_width[$id][$tagid])) $onpublish_width[$id][$tagid] = "";
            if (!isset ($onpublish_height[$id][$tagid])) $onpublish_height[$id][$tagid] = "";
            if (!isset ($onedit_file[$id][$tagid])) $onedit_file[$id][$tagid] = "";
            if (!isset ($language_info[$id])) $language_info[$id] = "";

            // collect unique id's and set position/key of hypertag
            if (!in_array ($id, $id_array))
            {
              $id_array[] = $id;

              // get key (position) of array item
              $position[$id] = $key;
            }

            // get media content
            if ($buildview != "template")
            {
              // set flag for each found media tag
              if (($hypertagname == $searchtag."file") && empty ($file_found[$id]))
              {
                $file_found[$id] = true;
              }
              elseif ($hypertagname == $searchtag."alttext" && empty ($text_found[$id]))
              {
                $text_found[$id] = true;
              }
              elseif ($hypertagname == $searchtag."align" && empty ($align_found[$id]))
              {
                $align_found[$id] = true;
              }
              elseif ($hypertagname == $searchtag."width" && empty ($width_found[$id]))
              {
                $width_found[$id] = true;
              }
              elseif ($hypertagname == $searchtag."height" && empty ($height_found[$id]))
              {
                $height_found[$id] = true;
              }

              // check if media content for id is already set using db_connect
              $db_connect_data = false;

              if (empty ($mediabot[$id]) && !empty ($db_connect))
              {
                // read content using db_connect
                $db_connect_data = db_read_media ($site, $contentfile, $contentdata, $id, $artid, $user);

                if ($db_connect_data != false)
                {
                  $mediafilebot[$id][$tagid] = $db_connect_data['file'];
                  $mediaobjectbot[$id] = $db_connect_data['object'];
                  $mediaalttextbot[$id] = $db_connect_data['alttext'];
                  $mediaalignbot[$id] = $db_connect_data['align'];
                  $mediawidthbot[$id] = $db_connect_data['width'];
                  $mediaheightbot[$id] = $db_connect_data['height'];

                  // set true
                  $db_connect_data = true;
                }
              }

              // read content from content container
              if ($db_connect_data == false)
              {
                // get the whole media object information of the content container
                if (empty ($mediabot[$id]))
                {
                  $bufferarray = selectcontent ($contentdata, "<media>", "<media_id>", $id);
                  if (!empty ($bufferarray[0])) $mediabot[$id] = $bufferarray[0];
                }

                if (!empty ($mediabot[$id]))
                {
                  // get the media file name and object link from mediabot
                  if ($hypertagname == $searchtag."file" && !isset ($mediafilebot[$id][$tagid]))
                  {
                    $bufferarray = getcontent ($mediabot[$id], "<mediafile>");
                    if (!empty ($bufferarray[0])) $mediafilebot[$id][$tagid] = $bufferarray[0];

                    $bufferarray = getcontent ($mediabot[$id], "<mediaobject>");
                    if (!empty ($bufferarray[0])) $mediaobjectbot[$id] = $bufferarray[0];

                    // check if linked multimedia component exists
                    if (empty ($mediafilebot[$id][$tagid]) && !empty ($mediaobjectbot[$id]))
                    {
                      $mediaobjectpath = deconvertpath ($mediaobjectbot[$id], "file");
                      $media_data = loadfile (getlocation ($mediaobjectpath), getobject ($mediaobjectpath));

                      if ($media_data != "")
                      {
                        $mediafilebot[$id][$tagid] = $mediasite."/".getfilename ($media_data, "media");
                        $contentdata = setcontent ($contentdata, "<media>", "<mediafile>", $mediafilebot[$id][$tagid], "<media_id>", $id);
                      }
                    } 
                  }

                  // get the media alttext name from mediabot
                  if ($hypertagname == $searchtag."alttext" && !isset ($mediaalttextbot[$id]))
                  {
                    $bufferarray = getcontent ($mediabot[$id], "<mediaalttext>");

                    if (!empty ($bufferarray[0]))
                    {
                      $mediaalttextbot[$id] = $bufferarray[0];
                      // escape special characters
                      $mediaalttextbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $mediaalttextbot[$id]);
                    }
                  }
                  // get the media alignment name from mediabot
                  elseif (($hypertagname == $searchtag."align" || $is_watermark) && !isset ($mediaalignbot[$id]))
                  {
                    $bufferarray = getcontent ($mediabot[$id], "<mediaalign>");

                    if (!empty ($bufferarray[0]))
                    {
                      $mediaalignbot[$id] = $bufferarray[0];
                      // escape special characters
                      $mediaalignbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $mediaalignbot[$id]);
                    }
                  }
                  // get the media width name from mediabot
                  elseif ($hypertagname == $searchtag."width" && !isset ($mediawidthbot[$id]))
                  {
                    $bufferarray = getcontent ($mediabot[$id], "<mediawidth>");

                    if (!empty ($bufferarray[0]))
                    {
                      $mediawidthbot[$id] = $bufferarray[0];
                      // escape special characters
                      $mediawidthbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $mediawidthbot[$id]);
                    }
                  }
                  // get the media height name from mediabot
                  elseif ($hypertagname == $searchtag."height" && !isset ($mediaheightbot[$id]))
                  {
                    $bufferarray = getcontent ($mediabot[$id], "<mediaheight>");

                    if (!empty ($bufferarray[0]))
                    {
                      $mediaheightbot[$id] = $bufferarray[0];
                      // escape special characters
                      $mediaheightbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $mediaheightbot[$id]);
                    }
                  }
                }
              }

              // get hyperCMS tags attributes (specific for each tag found in template)
              if ($hypertagname == $searchtag."file" || $is_watermark)
              {
                $hypertag_file[$id][$tagid] = $hypertag;
                $hypertagname_file[$id] = $hypertagname;
                // get label text
                if (empty ($label[$id])) $label[$id] = getattribute ($hypertag, "label");
                // get onpublish event
                $onpublish_file[$id][$tagid] = getattribute (strtolower ($hypertag), "onpublish");
                // get onedit event
                $onedit_file[$id][$tagid] = getattribute (strtolower ($hypertag), "onedit");
                // get mediatype
                $mediatype[$id] = getattribute ($hypertag, "mediatype");
                // get thumbnail view
                $thumbnail[$id][$tagid] = getattribute ($hypertag, "thumbnail");
                // get infotype
                if (!isset ($infotype[$id]) || $infotype[$id] != "meta")
                {
                  $infotype[$id] = getattribute (strtolower ($hypertag), "infotype");
                  if ($infotype[$id] == "meta") $show_meta = true;
                }
                // get dpi for scaling
                $mediadpi[$id] = getattribute ($hypertag, "dpi");

                // get colorspace and ICC profile
                $mediacolorspace[$id][$tagid] = getattribute ($hypertag, "colorspace");
                $mediaiccprofile[$id][$tagid] = getattribute ($hypertag, "iccprofile");

                // get path type [file,url,abs,wrapper,download]
                $mediapathtype[$id][$tagid] = getattribute ($hypertag, "pathtype");
              }
              elseif ($hypertagname == $searchtag."alttext")
              {
                $hypertag_text[$id][$tagid] = $hypertag;
                $hypertagname_text[$id] = $hypertagname;
                $onpublish_text[$id][$tagid] = getattribute (strtolower ($hypertag), "onpublish");
              }
              elseif ($hypertagname == $searchtag."align")
              {
                $hypertag_align[$id][$tagid] = $hypertag;
                $hypertagname_align[$id] = $hypertagname;
                $onpublish_align[$id][$tagid] = getattribute (strtolower ($hypertag), "onpublish");
              }
              elseif ($hypertagname == $searchtag."width")
              {
                $hypertag_width[$id][$tagid] = $hypertag;
                $hypertagname_width[$id] = $hypertagname;
                $onpublish_width[$id][$tagid] = getattribute (strtolower ($hypertag), "onpublish");
              }
              elseif ($hypertagname == $searchtag."height")
              {
                $hypertag_height[$id][$tagid] = $hypertag;
                $hypertagname_height[$id] = $hypertagname;
                $onpublish_height[$id][$tagid] = getattribute (strtolower ($hypertag), "onpublish");
              }

              // get language attribute
              if (empty ($language_info[$id])) $language_info[$id] = getattribute ($hypertag, "language");

              // replace image with reference to the newly generated image if a colorspace or ICC profile is given
              if (!empty ($mediafilebot[$id][$tagid]) && $buildview == "publish" && $hypertagname == $searchtag."file" && (!empty ($mediacolorspace[$id][$tagid]) || !empty ($mediaiccprofile[$id][$tagid])))
              {
                // get abs location of the image
                $imgdir = getmedialocation ($site, $mediafilebot[$id][$tagid], "abs_path_media");

                // convert image to PNG in the requested colorspace or ICC profile
                $mediafilebot_new = convertimage ($site, $imgdir.$mediafilebot[$id][$tagid], $mgmt_config['abs_path_view'], "png", $mediacolorspace[$id][$tagid], $mediaiccprofile[$id][$tagid]);

                // check converted image
                if ($mediafilebot_new != false) $mediafilebot[$id][$tagid] = $mediafilebot_new;
              }
            }
            // if buildview = template
            else
            {
              // get the media file tags
              if ($hypertagname == $searchtag."file")
              {
                $hypertag_file[$id][$tagid] = $hypertag;
              }
              // get the media alttext tag
              elseif ($hypertagname == $searchtag."alttext")
              {
                $hypertag_text[$id][$tagid] = $hypertag; 
              }
              // get the media alignment tag
              elseif ($hypertagname == $searchtag."align")
              {
                $hypertag_align[$id][$tagid] = $hypertag;
              }
              // get the media width tag
              elseif ($hypertagname == $searchtag."width")
              {
                $hypertag_width[$id][$tagid] = $hypertag;
              }
              // get the media height tag
              elseif ($hypertagname == $searchtag."height")
              {
                $hypertag_height[$id][$tagid] = $hypertag; 
              }
            }
          }

          $tagid_max = $tagid;

          // loop for each tag ID
          foreach ($id_array as $id)
          { 
            // set position for form item
            $key = $position[$id];

            $labelname = "";
            $artid = "";
            $elementid = "";

            if ($searchtag == "artmedia")
            {
              // get article id
              $artid = getartid ($id);

              // element id
              $elementid = getelementid ($id);

              // define label
              if (empty ($label[$id])) $labelname = $artid." - ".$elementid;
              else $labelname = $artid." - ".$label[$id];
            }
            else
            {
              // define label
              if (empty ($label[$id])) $labelname = $id;
              else $labelname = $label[$id];
            }

            // set flag for edit button or text field
            if (empty ($foundimg[$id]) && $onedit != "hidden") $foundimg[$id] = true;
            elseif (!empty ($foundtimg[$id])) $foundimg[$id] = false;


            // loop for each unique tag
            for ($tagid = 1; $tagid <= $tagid_max; $tagid++)
            {
              if (isset ($hypertag_file[$id][$tagid]) || isset ($hypertag_text[$id][$tagid]) || isset ($hypertag_align[$id][$tagid]) || isset ($hypertag_width[$id][$tagid]) || isset ($hypertag_height[$id][$tagid]))
              {
                // set default values if the tags and therefore their content was not found
                if (!isset ($mediafilebot[$id][$tagid])) $mediafilebot[$id][$tagid] = "";
                if (!isset ($mediaobjectbot[$id])) $mediaobjectbot[$id] = "";
                if (!isset ($label[$id])) $label[$id] = "";
                if (!isset ($mediatype[$id])) $mediatype[$id] = "";
                if (!isset ($mediaalttextbot[$id])) $mediaalttextbot[$id] = "";
                if (!isset ($mediaalignbot[$id])) $mediaalignbot[$id] = "";
                if (!isset ($mediawidthbot[$id])) $mediawidthbot[$id] = "";
                if (!isset ($mediaheightbot[$id])) $mediaheightbot[$id] = "";

                // set media bots for non existing hyperCMS tags
                if ($buildview != "template")
                {
                  if (empty ($file_found[$id])) $mediafilebot[$id][$tagid] = "*Null*";
                  if (empty ($text_found[$id])) $mediaalttextbot[$id] = "*Null*";
                  if (empty ($align_found[$id]) && !$is_watermark) $mediaalignbot[$id] = "*Null*";
                  if (empty ($width_found[$id])) $mediawidthbot[$id] = "*Null*";
                  if (empty ($height_found[$id])) $mediaheightbot[$id] = "*Null*";
                }

                if (!empty ($hypertag_file[$id][$tagid]))
                {
                  // get readonly attribute
                  $readonly = getattribute ($hypertag_file[$id][$tagid], "readonly");

                  if ($buildview != "formlock")
                  {
                    if ($readonly != false) $disabled = " disabled=\"disabled\"";
                    else $disabled = "";
                  }

                  // get group access
                  $groupaccess = getattribute ($hypertag_file[$id][$tagid], "groups");
                  $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);
                }
                else $groupaccess = true;

                // ------------------------- cmsview ---------------------------

                // in order to access the content via JS
                if ($groupaccess != true && ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock"))
                {
                  // for articles
                  $id = str_replace (":", "_", $id);

                  $formitem[$key] = "
                  <input type=\"hidden\" id=\"".$hypertagname_file[$id]."_".$id."\" value=\"".$mediaobjectbot[$id]."\" />
                  <input type=\"hidden\" id=\"".$hypertagname_align[$id]."_".$id."\" value=\"".$mediaalignbot[$id]."\" />
                  <input type=\"hidden\" id=\"".$hypertagname_text[$id]."_".$id."\" value=\"".$mediaalttextbot[$id]."\" />
                  <input type=\"hidden\" id=\"".$hypertagname_width[$id]."_".$id."\" value=\"".$mediawidthbot[$id]."\" />
                  <input type=\"hidden\" id=\"".$hypertagname_height[$id]."_".$id."\" value=\"".$mediaheightbot[$id]."\" />";
                }

                if (
                     checklanguage ($language_sessionvalues_array, $language_info[$id]) && $groupaccess == true &&
                     !empty ($hypertag_file[$id][$tagid]) && $onedit_file[$id][$tagid] != "hidden" &&
                     (
                       (
                        (
                          $buildview == "cmsview" ||
                          $buildview == "inlineview"
                        ) &&
                        @$infotype[$id] != "meta"
                       ) ||
                       $buildview == "formedit" ||
                       ($buildview == "formmeta" && @$infotype[$id] == "meta") ||
                       $buildview == "formlock" ||
                       $buildview == "template"
                     )
                   )
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;

                  while (!empty ($hypertag_file[$id][$tagid]) && substr_count ($viewstore_offset, $hypertag_file[$id][$tagid]) > 0)
                  {
                    if ($searchtag == "media")
                    {
                      // initialize scaling factor
                      // check if dpi is valid and than calculate scalingfactor
                      if (!empty ($mediadpi[$id]) && $mediadpi[$id] > 0 && $mediadpi[$id] < 1000)
                      {
                        $scalingfactor = round ((72 / $mediadpi[$id]), 2);
                      }
                      else $scalingfactor = "";

                      // create tag link
                      if ($buildview == "cmsview" || $buildview == 'inlineview')
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_media.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_media.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=media&mediacat=comp&scaling=".url_encode($scalingfactor)."&mediatype=".url_encode($mediatype[$id])."&contenttype=".url_encode($contenttype)."&token=".$token."&mediafile=".url_encode($mediafilebot[$id][$tagid])."&mediaobject=".url_encode($mediaobjectbot[$id])."&mediaalttext=".url_encode($mediaalttextbot[$id])."&mediaalign=".url_encode($mediaalignbot[$id])."&mediawidth=".url_encode($mediawidthbot[$id])."&mediaheight=".url_encode($mediaheightbot[$id])."';\"  alt=\"".$labelname.": ".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />\n";
                      }
                      elseif ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                      {
                        if ($buildview == "formedit" || $buildview == "formmeta")
                        {
                          $taglink = "
                          <div id=\"".$hypertagname_file[$id]."_".$id."_controls\" style=\"display:inline-block;\">
                            <img onClick=\"openBrWindowComp(document.forms['hcms_formview'].elements['mediaobject[".$id."]'],'','location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".$hcms_lang['edit'][$lang]."\" title=\"".$hcms_lang['edit'][$lang]."\" />
                            <img onClick=\"deleteEntry(document.forms['hcms_formview'].elements['".$hypertagname_file[$id]."[".$id."]']); deleteEntry(document.forms['hcms_formview'].elements['mediaobject[".$id."]']);\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.png\" alt=\"".$hcms_lang['delete'][$lang]."\" title=\"".$hcms_lang['delete'][$lang]."\" />
                            <img onClick=\"setSaveType('form_so', '".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_media.php?view=".url_encode($buildview)."&savetype=form_so&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&contentfile=".url_encode($contentfile)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".urlencode($label[$id])."&tagname=media&mediacat=comp&scaling=".url_encode($scalingfactor)."&mediatype=".url_encode($mediatype[$id])."&contenttype=".url_encode($contenttype)."&mediafile=".url_encode($mediafilebot[$id][$tagid])."&mediaobject=".url_encode("%comp%")."' + getValue('".$hypertagname_file[$id]."[".$id."]','') + '&mediaalttext=' + getValue('".$hypertagname_text[$id]."[".$id."]','*Null*') + '&mediaalign=' + getSelectedOption('".$hypertagname_align[$id]."[".$id."]','*Null*') + '&mediawidth=' + getValue('".$hypertagname_width[$id]."[".$id."]','*Null*') + '&mediaheight=' + getValue('".$hypertagname_height[$id]."[".$id."]','*Null*'), 'post');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_media.png\" alt=\"".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" />
                          </div>";
                        }
                        else $taglink = "";

                        $formitem[$key] = "
                        <div class=\"hcmsFormRowLabel ".$hypertagname_file[$id]."_".$id."\">
                          <b>".$labelname."</b>
                        </div>
                        <div class=\"hcmsFormRowContent ".$hypertagname_file[$id]."_".$id."\">
                          <table>";

                        if (isset ($mediafilebot[$id][$tagid]) && $mediafilebot[$id][$tagid] != "*Null*")
                        {
                          if (substr_count ($mediafilebot[$id][$tagid], "Null_media.gif") == 1)
                          {
                            $mediaobjectname = "";
                          } 
                          elseif (!empty ($mediaobjectbot[$id]))
                          {
                            $mediaobjectname = getlocationname ($site, $mediaobjectbot[$id], "comp");
                          }
                          else $mediaobjectname = $mediafilebot[$id][$tagid];

                          if ($mediatype[$id] != "") $constraint_array[$key] = "'".$hypertagname_file[$id]."[".$id."]', '".$labelname." (".$hcms_lang['multimedia-file'][$lang].")', '".$mediatype[$id]."'";

                          $formitem[$key] .= "
                          <tr>
                            <td colspan=\"2\">".showmedia ($mediafilebot[$id][$tagid], convertchars ($mediaobjectname, $hcms_lang_codepage[$lang], $charset), "preview_no_rendering", "hcms_mediaplayer_".$id)."</td>
                          </tr>
                          <tr>
                            <td style=\"width:150px;\">".getescapedtext ($hcms_lang['multimedia-file'][$lang], $charset, $lang)." </td>
                            <td style=\"white-space:nowrap;\">
                              <input type=\"hidden\" name=\"mediaobject[".$id."]\" value=\"".$mediaobjectbot[$id]."\" />
                              <input type=\"text\" id=\"".$hypertagname_file[$id]."_".$id."\" name=\"".$hypertagname_file[$id]."[".$id."]\" value=\"".convertchars ($mediaobjectname, $hcms_lang_codepage[$lang], $charset)."\" style=\"width:".$fieldwidth."px;\" ".$disabled." />".$taglink."
                            </td>
                          </tr>";
                        }

                        if ($mediaalttextbot[$id] != "*Null*") $formitem[$key] .= "
                          <tr>
                            <td style=\"width:150px;\">".getescapedtext ($hcms_lang['alternative-text'][$lang], $charset, $lang)." </td>
                            <td><input type=\"text\" id=\"".$hypertagname_text[$id]."_".$id."\" name=\"".$hypertagname_text[$id]."[".$id."]\" value=\"".$mediaalttextbot[$id]."\" style=\"width:".$fieldwidth."px;\" ".$disabled." /></td>
                          </tr>";

                        if ($is_watermark)
                        {
                          $formitem[$key] .= "
                          <tr>
                            <td style=\"width:150px;\">".getescapedtext ($hcms_lang['alignment'][$lang], $charset, $lang)." </td>
                            <td>
                            <select id=\"mediaalign_".$id."\" name=\"mediaalign[".$id."]\" style=\"width:".$fieldwidth."px;\" ".$disabled.">
                              <option value=\"topleft\"".($mediaalignbot[$id] == "topleft" ? " selected" : "").">".getescapedtext ($hcms_lang['top'][$lang]." ".$hcms_lang['left'][$lang], $charset, $lang)."</option>
                              <option value=\"topright\"".($mediaalignbot[$id] == "topright" ? " selected" : "").">".getescapedtext ($hcms_lang['top'][$lang]." ".$hcms_lang['right'][$lang], $charset, $lang)."</option>
                              <option value=\"bottomleft\"".($mediaalignbot[$id] == "bottomleft" ? " selected" : "").">".getescapedtext ($hcms_lang['bottom'][$lang]." ".$hcms_lang['left'][$lang], $charset, $lang)."</option>
                              <option value=\"bottomright\"".($mediaalignbot[$id] == "bottomright" ? " selected" : "").">".getescapedtext ($hcms_lang['bottom'][$lang]." ".$hcms_lang['right'][$lang], $charset, $lang)."</option>
                              <option value=\"center\"".($mediaalignbot[$id] == "center" ? " selected" : "").">".getescapedtext ($hcms_lang['middle'][$lang], $charset, $lang)."</option>
                            </select>
                            </td>
                          </tr>";
                        }
                        elseif ($mediaalignbot[$id] != "*Null*")
                        {
                          $formitem[$key] .= "
                          <tr>
                            <td style=\"width:150px;\">".getescapedtext ($hcms_lang['alignment'][$lang], $charset, $lang)." </td>
                            <td>
                            <select id=\"".$hypertagname_align[$id]."_".$id."\" name=\"".$hypertagname_align[$id]."[".$id."]\" style=\"width:".$fieldwidth."px;\" ".$disabled.">
                              <option value=\"\"".($mediaalignbot[$id] == "" ? " selected" : "").">".getescapedtext ($hcms_lang['standard'][$lang], $charset, $lang)."</option>
                              <option value=\"top\"".($mediaalignbot[$id] == "top" ? " selected" : "").">".getescapedtext ($hcms_lang['top'][$lang], $charset, $lang)."</option>
                              <option value=\"middle\"".($mediaalignbot[$id] == "middle" ? " selected" : "").">".getescapedtext ($hcms_lang['middle'][$lang], $charset, $lang)."</option>
                              <option value=\"absmiddle\"".($mediaalignbot[$id] == "absmiddle" ? " selected" : "").">".getescapedtext ($hcms_lang['absolute-middle'][$lang], $charset, $lang)."</option>
                              <option value=\"bottom\"".($mediaalignbot[$id] == "bottom" ? " selected" : "").">".getescapedtext ($hcms_lang['bottom'][$lang], $charset, $lang)."</option>
                              <option value=\"left\"".($mediaalignbot[$id] == "left" ? " selected" : "").">".getescapedtext ($hcms_lang['left'][$lang], $charset, $lang)."</option>
                              <option value=\"right\"".($mediaalignbot[$id] == "right" ? " selected" : "").">".getescapedtext ($hcms_lang['right'][$lang], $charset, $lang)."</option>
                            </select>
                            </td>
                          </tr>";
                        }

                        if ($mediawidthbot[$id] != "*Null*")
                        {
                          $constraint_array[$key] = "'".$hypertagname_width[$id]."[".$id."]', '".$labelname." (".$hcms_lang['width'][$lang].")', 'NisNum'";
                          $formitem[$key] .= "
                          <tr>
                            <td style=\"width:150px;\">".getescapedtext ($hcms_lang['width'][$lang], $charset, $lang)." </td>
                            <td><input type=\"number\" name=\"".$hypertagname_width[$id]."[".$id."]\" value=\"".$mediawidthbot[$id]."\" style=\"width:60px;\" ".$disabled." /></td>
                          </tr>";
                        }

                        if ($mediaheightbot[$id] != "*Null*")
                        {
                          $constraint_array[$key] = "'".$hypertagname_height[$id]."[".$id."]', '".$labelname." (".$hcms_lang['height'][$lang].")', 'NisNum'";
                          $formitem[$key] .= "
                          <tr>
                            <td style=\"width:150px;\">".getescapedtext ($hcms_lang['height'][$lang], $charset, $lang)." </td>
                            <td><input type=\"number\" name=\"".$hypertagname_height[$id]."[".$id."]\" value=\"".$mediaheightbot[$id]."\" style=\"width:60px;\" ".$disabled." /></td>
                          </tr>";
                        }

                        $formitem[$key] .= "
                          </table>
                        </div>";
                      }
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-media-image'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    elseif ($searchtag == "artmedia")
                    {
                      // create tag link
                      if ($buildview == "cmsview" || $buildview == 'inlineview')
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_media.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_media.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=artmedia&mediacat=comp&mediatype=".url_encode($mediatype[$id])."&contenttype=".url_encode($contenttype)."&token=".$token."&mediafile=".url_encode($mediafilebot[$id][$tagid])."&mediaobject=".url_encode($mediaobjectbot[$id])."&mediaalttext=".urlencode($mediaalttextbot[$id])."&mediaalign=".urlencode($mediaalignbot[$id])."&mediawidth=".url_encode($mediawidthbot[$id])."&mediaheight=".url_encode($mediaheightbot[$id])."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />".$arttaglink[$artid]."\n";
                      }
                      elseif ($buildview == "formedit" || ($buildview == "formmeta" && @$infotype[$id] == "meta") || $buildview == "formlock")
                      {
                        if ($buildview == "formedit" || $buildview == "formmeta")
                        {
                          $taglink = "
                          <div id=\"".$hypertagname_file[$id]."_".$artid."_".$elementid."_controls\" style=\"display:inline-block;\">
                            <img onClick=\"openBrWindowComp(document.forms['hcms_formview'].elements['artmediaobject[".$id."]'],'','location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" />
                            <img onClick=\"deleteEntry(document.forms['hcms_formview'].elements['".$hypertagname_file[$id]."[".$id."]']); deleteEntry(document.forms['hcms_formview'].elements['artmediaobject[".$id."]']);\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" />
                            <img onClick=\"setSaveType('form_so', '".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_media.php?view=".url_encode($buildview)."&savetype=form_so&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=media&mediacat=comp&mediatype=".$mediatype[$id]."&contenttype=".$contenttype."&mediafile=".url_encode($mediafilebot[$id][$tagid])."&mediaobject=' + getValue('".$hypertagname_file[$id]."[".$id."]','') + '&mediaalttext=' + getValue('".$hypertagname_text[$id]."[".$id."]','*Null*') + '&mediaalign=' + getSelectedOption('".$hypertagname_align[$id]."[".$id."]','*Null*') + '&mediawidth=' + getValue('".$hypertagname_width[$id]."[".$id."]','*Null*') + '&mediaheight=' + getValue('".$hypertagname_height[$id]."[".$id."]','*Null*'), 'post');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_media.png\" alt=\"".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" />
                          </div>";
                        }
                        else $taglink = "";
 
                        $formitem[$key] = "
                        <div class=\"hcmsFormRowLabel ".$hypertagname_file[$id]."_".$artid."_".$elementid."\">
                          <b>".$labelname."</b> ".$arttaglink[$artid]."
                        </div>
                        <div class=\"hcmsFormRowContent ".$hypertagname_file[$id]."_".$artid."_".$elementid."\">
                          <table>";

                        if ($mediafilebot[$id][$tagid] != "*Null*")
                        {
                          if (substr_count ($mediafilebot[$id][$tagid], "Null_media.gif") == 1)
                          {
                            $mediaobjectname = "";
                          } 
                          elseif ($mediaobjectbot[$id] != "")
                          {
                            $mediaobjectname = getlocationname ($site, $mediaobjectbot[$id], "comp");
                          }
                          else $mediaobjectname = $mediafilebot[$id][$tagid];

                          if ($mediatype[$id] != "") $constraint_array[$key] = "'".$hypertagname_file[$id]."[".$id."]', '".$labelname." (".$hcms_lang['multimedia-file'][$lang].")', '".$mediatype[$id]."'";

                          $formitem[$key] .= "
                          <tr>
                            <td colspan=\"2\">".showmedia ($mediafilebot[$id][$tagid], convertchars ($mediaobjectname, $hcms_lang_codepage[$lang], $charset), "preview_no_rendering", "hcms_mediaplayer_".$id)."</td>
                          </tr>
                          <tr>
                            <td style=\"width:150px;\">".getescapedtext ($hcms_lang['multimedia-file'][$lang], $charset, $lang)." </td>
                            <td style=\"white-space:nowrap;\"> 
                              <input type=\"hidden\" name=\"artmediaobject[".$id."]\" value=\"".$mediaobjectbot[$id]."\" />
                              <input type=\"text\" id=\"".$hypertagname_file[$id]."_".$artid."_".$elementid."\" name=\"".$hypertagname_file[$id]."[".$id."]\" value=\"".convertchars ($mediaobjectname, $hcms_lang_codepage[$lang], $charset)."\" style=\"width:".$fieldwidth."px;\" ".$disabled." />".$taglink."
                            </td>
                          </tr>";
                        }

                        if ($mediaalttextbot[$id] != "*Null*") $formitem[$key] .= "
                          <tr>
                            <td style=\"width:150px;\">".getescapedtext ($hcms_lang['alternative-text'][$lang], $charset, $lang)." </td>
                            <td>
                              <input type=\"hidden\" name=\"".$hypertagname_text[$id]."[".$id."]\" />
                              <input type=\"text\" name=\"".$hypertagname_text[$id]."_".$artid."_".$elementid."\" value=\"".$mediaalttextbot[$id]."\" style=\"width:".$fieldwidth."px;\" ".$disabled." />
                            </td>
                          </tr>";

                        if ($mediaalignbot[$id] != "*Null*")
                        {
                          $formitem[$key] .= "
                          <tr>
                            <td style=\"width:150px;\">".getescapedtext ($hcms_lang['alignment'][$lang], $charset, $lang)." </td>
                            <td>
                            <select id=\"".$hypertagname_align[$id]."_".$artid."_".$elementid."\" name=\"".$hypertagname_align[$id]."[".$id."]\" style=\"width:".$fieldwidth."px;\" ".$disabled.">
                              <option value=\"\" ".($mediaalignbot[$id] == "" ? " selected\"selected\"" : "").">".getescapedtext ($hcms_lang['standard'][$lang], $charset, $lang)."</option>
                              <option value=\"top\" ".($mediaalignbot[$id] == "top" ? " selected\"selected\"" : "").">".getescapedtext ($hcms_lang['top'][$lang], $charset, $lang)."</option>
                              <option value=\"middle\" ".($mediaalignbot[$id] == "middle" ? " selected\"selected\"" : "").">".getescapedtext ($hcms_lang['middle'][$lang], $charset, $lang)."</option>
                              <option value=\"absmiddle\" ".($mediaalignbot[$id] == "absmiddle" ? " selected\"selected\"" : "").">".getescapedtext ($hcms_lang['absolute-middle'][$lang], $charset, $lang)."</option>
                              <option value=\"bottom\" ".($mediaalignbot[$id] == "bottom" ? " selected\"selected\"" : "").">".getescapedtext ($hcms_lang['bottom'][$lang], $charset, $lang)."</option>
                              <option value=\"left\" ".($mediaalignbot[$id] == "left" ? " selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['left'][$lang], $charset, $lang)."</option>
                              <option value=\"right\" ".($mediaalignbot[$id] == "right" ? " selected\"selected\"" : "").">".getescapedtext ($hcms_lang['right'][$lang], $charset, $lang)."</option>
                            </select>
                            </td>
                          </tr>";
                        }

                        if ($mediawidthbot[$id] != "*Null*")
                        {
                          $constraint_array[$key] = "'".$hypertagname_width[$id]."[".$id."]', '".$labelname." (".$hcms_lang['width'][$lang].")', 'NisNum'";
                          $formitem[$key] .= "
                          <tr>
                            <td style=\"width:150px;\">".getescapedtext ($hcms_lang['width'][$lang], $charset, $lang)." </td>
                            <td><input type=\"number\" id=\"".$hypertagname_width[$id]."_".$artid."_".$elementid."\" name=\"".$hypertagname_width[$id]."[".$id."]\" value=\"".$mediawidthbot[$id]."\" style=\"width:60px;\" ".$disabled." /></td>
                          </tr>";
                        }

                        if ($mediaheightbot[$id] != "*Null*")
                        {
                          $constraint_array[$key] = "'".$hypertagname_height[$id]."[".$id."]', '".$labelname." (".$hcms_lang['height'][$lang].")', 'NisNum'";
                          $formitem[$key] .= "
                          <tr>
                            <td style=\"width:150px;\">".getescapedtext ($hcms_lang['height'][$lang], $charset, $lang)." </td>
                            <td><input type=\"number\" id=\"".$hypertagname_height[$id]."_".$artid."_".$elementid."\" name=\"".$hypertagname_height[$id]."[".$id."]\" value=\"".$mediaheightbot[$id]."\" style=\"width:60px;\" ".$disabled."></td>
                          </tr>";
                        }

                        $formitem[$key] .= "
                        </table>
                      </div>";
                      }
                      elseif ($buildview == "template" && $onedit != "hidden" && ($infotype != "meta" || strpos ($templatefile, ".meta.tpl") > 0))
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>article ".$artid." </b><br />
                              <b>Element: ".$elementid."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-media-image'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    else $taglink = "";

                    if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                    {
                      break;
                    }
                    else
                    {
                      // get full HTML media-tag
                      $imgtag = gethtmltag ($viewstore_offset, $hypertag_file[$id][$tagid]);
                      if ($imgtag == false) $imgtag = $hypertag_file[$id][$tagid];

                      $repl_start = strpos ($viewstore, $imgtag, $repl_offset);

                      if ($buildview == "cmsview" || $buildview == 'inlineview')
                      {
                        // define offset
                        $repl_offset = $repl_start + strlen ($taglink.$imgtag);
                      }
                      elseif ($buildview == "template")
                      {
                        // define offset
                        $imgtag_new = str_ireplace ("<img", "<lockimg", $imgtag);
                        $repl_offset = $repl_start + strlen ($taglink.$imgtag_new);
                        $repl_len = strlen ($imgtag);

                        // replace tag
                        $viewstore = substr_replace ($viewstore, "", $repl_start, $repl_len);
                      }

                      // insert taglink
                      $viewstore = substr_replace ($viewstore, $taglink, $repl_start, 0);
                      $viewstore_offset = substr ($viewstore, $repl_offset);

                      // if ($buildview == "cmsview" || $buildview == 'inlineview') break;
                    }
                  }
                }
                // --------------------------- publish / insert content ----------------------------
                // insert article time management code
                elseif ($buildview == "publish" && $searchtag == "artmedia" && !empty ($hypertag_file[$id][$tagid]) && $onpublish_file[$id][$tagid] != "hidden")
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;

                  while (!empty ($hypertag_file[$id][$tagid]) && substr_count ($viewstore_offset, $hypertag_file[$id][$tagid]) > 0)
                  {
                    // get full HTML media-tag
                    $imgtag = gethtmltag ($viewstore_offset, $hypertag_file[$id][$tagid]);
                    if ($imgtag == false) $imgtag = $hypertag_file[$id][$tagid];

                    // define html-tag position
                    $repl_start = strpos ($viewstore, $imgtag, $repl_offset);
                    $repl_len = strlen ($imgtag);

                    // define new hreftag for link management
                    if ($artstatus[$artid] == "timeswitched")
                    {
                      $imgtag = tpl_tselement ($application, $artdatefrom[$artid], $artdateto[$artid], $imgtag);
                    }
                    elseif ($artstatus[$artid] == "inactive")
                    {
                      // imgtag is empty, in fact must be freespace!
                      $imgtag = " ";
                    }

                    // insert new image tag
                    $viewstore = substr_replace ($viewstore, $imgtag, $repl_start, $repl_len); 

                    // define offset
                    $repl_offset = $repl_start + strlen ($imgtag); 
                    $viewstore_offset = substr ($viewstore_offset, $repl_offset);
                  }
                }

                // define Null media if mediafilebot is empty and set URL to content media repository
                if (empty ($mediafilebot[$id][$tagid]))
                {
                  // copy Null media to template media directory
                  if (!is_file ($mgmt_config['abs_path_tplmedia'].$templatesite."/Null_media.gif"))
                  {
                    copy ($mgmt_config['abs_path_cms']."theme/standard/img/Null_media.gif", $mgmt_config['abs_path_tplmedia'].$templatesite."/Null_media.gif");
                  }

                  $file_media = "Null_media.gif";

                  if ($buildview == "publish") $url_media = $publ_config['url_publ_tplmedia'].$templatesite."/";
                  else $url_media = $mgmt_config['url_path_tplmedia'].$templatesite."/";
                }
                // define media file
                else
                {
                  // if pathytpe == file (absolute path in filesystem)
                  if (!empty ($mediapathtype[$id][$tagid]) && $mediapathtype[$id][$tagid] == "file") $path_prefix = "abs";
                  else $path_prefix = "url";

                  // media path settings (overwrite media pathes with the ones of the publication target)
                  if ($buildview == "publish")
                  { 
                    // use generated image and temp view directory
                    if (!empty ($mediacolorspace[$id][$tagid]) || !empty ($mediaiccprofile[$id][$tagid]) && is_file ($mgmt_config['abs_path_view'].$mediafilebot[$id][$tagid]))
                    {
                      $url_media = $mgmt_config[$path_prefix.'_path_view'];
                    }
                    else
                    {
                      $url_media = $publ_config[$path_prefix.'_publ_media'];
                    }

                    $url_tplmedia = $publ_config[$path_prefix.'_publ_tplmedia'];
                  }
                  else
                  {
                    $url_media = getmedialocation ($site, $mediafilebot[$id][$tagid], $path_prefix."_path_media");
                    $url_tplmedia = $mgmt_config[$path_prefix.'_path_tplmedia'];
                  }

                  // if pathytpe == uri (deprecated value: abs) (URI = URL w/o protocol and domain)
                  if (!empty ($mediapathtype[$id][$tagid]) && ($mediapathtype[$id][$tagid] == "uri" || $mediapathtype[$id][$tagid] == "abs"))
                  {
                    $url_media = cleandomain ($url_media);
                    $url_tplmedia = cleandomain ($url_tplmedia);
                  }

                  // if thumbnail presentation is requested
                  if (!empty ($thumbnail[$id][$tagid]) && ($thumbnail[$id][$tagid] == "1" || strtolower ($thumbnail[$id][$tagid]) == "yes"))
                  {
                    $file_info = getfileinfo ($site, $mediafilebot[$id][$tagid], "");

                    if (is_file (getmedialocation ($site, $file_info['filename'].".thumb.jpg", "abs_path_media").$site."/".$file_info['filename'].".thumb.jpg")) $file_media = $site."/".$file_info['filename'].".thumb.jpg";
                    // mp4 original thumbnail video file
                    elseif (is_file (getmedialocation ($site, $file_info['filename'].".orig.mp4", "abs_path_media").$site."/".$file_info['filename'].".orig.mp4")) $file_media = $site."/".$file_info['filename'].".thumb.mp4";
                    // flv original thumbnail video file
                    elseif (is_file (getmedialocation ($site, $file_info['filename'].".orig.flv", "abs_path_media").$site."/".$file_info['filename'].".orig.flv")) $file_media = $site."/".$file_info['filename'].".orig.flv";
                    // for older versions
                    elseif (is_file (getmedialocation ($site, $file_info['filename'].".thumb.flv", "abs_path_media").$site."/".$file_info['filename'].".thumb.flv")) $file_media = $site."/".$file_info['filename'].".thumb.flv";
                    // use original file
                    else $file_media = $mediafilebot[$id][$tagid];
                  }
                  // use original file
                  else $file_media = $mediafilebot[$id][$tagid];

                  // if media file is already an URL
                  if (!empty ($mediafilebot[$id][$tagid]) && strpos ($mediafilebot[$id][$tagid], "://") > 0)
                  {
                    $url_media = "";
                    $url_tplmedia = "";
                  }
                  // if pathytpe == wrapper (wrapper link)
                  elseif (!empty ($mediapathtype[$id][$tagid]) && $mediapathtype[$id][$tagid] == "wrapper" && getmediacontainerid ($file_media))
                  {
                    $url_media = "";
                    $file_media = createwrapperlink ("", "", "", "", "", getmediacontainerid ($file_media));
                  }
                  // if pathytpe == download (download link)
                  elseif (!empty ($mediapathtype[$id][$tagid]) && $mediapathtype[$id][$tagid] == "download" && getmediacontainerid ($file_media))
                  {
                    $url_media = "";
                    $file_media = createdownloadlink ("", "", "", "", "", getmediacontainerid ($file_media));
                  }
                  // if pathytpe == location (converted location path)
                  elseif (!empty ($mediapathtype[$id][$tagid]) && $mediapathtype[$id][$tagid] == "location")
                  {
                    $url_media = "";
                    $file_media = convertpath ($site, $mediaobjectbot[$id], "comp");
                  }
                }

                if ($buildview != "formedit" && $buildview != "formmeta" && $buildview != "formlock")
                {
                  // escape variable and add slashes if onedit=hidden
                  if ($onedit_file[$id][$tagid] == "hidden")
                  {
                    $mediaalttextbot[$id] = addslashes ($mediaalttextbot[$id]);
                    $mediaalttextbot[$id] = str_replace ("\$", "\\\$", $mediaalttextbot[$id]);
                  }

                  // replace hyperCMS media-tag with contentbot
                  if (!empty ($hypertag_file[$id][$tagid]))
                  {
                    if  ($onpublish_file[$id][$tagid] != "hidden") $viewstore = str_replace ($hypertag_file[$id][$tagid], $url_media.$file_media, $viewstore);
                    elseif ($onpublish_file[$id][$tagid] == "hidden") $viewstore = str_replace ($hypertag_file[$id][$tagid], "", $viewstore);
                  }

                  if (!empty ($hypertag_text[$id][$tagid]))
                  {
                    if ($onpublish_text[$id][$tagid] != "hidden") $viewstore = str_replace ($hypertag_text[$id][$tagid], $mediaalttextbot[$id], $viewstore);
                    elseif ($onpublish_text[$id][$tagid] == "hidden") $viewstore = str_replace ($hypertag_text[$id][$tagid], "", $viewstore);
                  }

                  if (!empty ($hypertag_align[$id][$tagid]))
                  {
                    if ($onpublish_align[$id][$tagid] != "hidden") $viewstore = str_replace ($hypertag_align[$id][$tagid], $mediaalignbot[$id], $viewstore);
                    elseif ($onpublish_align[$id][$tagid] == "hidden") $viewstore = str_replace ($hypertag_align[$id][$tagid], "", $viewstore);
                  }

                  if (!empty ($hypertag_width[$id][$tagid]))
                  {
                    if ($onpublish_width[$id][$tagid] != "hidden") $viewstore = str_replace ($hypertag_width[$id][$tagid], $mediawidthbot[$id], $viewstore);
                    elseif ($onpublish_width[$id][$tagid] == "hidden") $viewstore = str_replace ($hypertag_width[$id][$tagid], "", $viewstore);
                  }

                  if (!empty ($hypertag_height[$id][$tagid]))
                  {
                    if ($onpublish_height[$id][$tagid] != "hidden") $viewstore = str_replace ($hypertag_height[$id][$tagid], $mediaheightbot[$id], $viewstore);
                    elseif ($onpublish_height[$id][$tagid] == "hidden") $viewstore = str_replace ($hypertag_height[$id][$tagid], "", $viewstore);
                  }
                }
              }
            } // end for unique tag loop
          } // end for unique content ID loop
        }
      }


      // =================================================== link content ===================================================

      // create view for link content
      $searchtag_array = array();
      $searchtag_array[0] = "artlink";
      $searchtag_array[1] = "link";
      $hypertagname = array();
      $infotype = array();
      $position = array();
      $onpublish_href = array();
      $onpublish_target = array();
      $onpublish_text = array();
      $onedit_href = array();
      $label = array();
      $disable_href = array();
      $language_info = array();
      $add_submitlink = "";
      $targetlist = array();
      $linkbot = array();

      foreach ($searchtag_array as $searchtag)
      {
        // get all hyperCMS tags
        $hypertag_array = gethypertag ($viewstore, $searchtag, 0);

        if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
        {
          $id_array = array();
          $tagid = 0;

          reset ($hypertag_array);

          // loop for each hyperCMS tag found in template
          foreach ($hypertag_array as $key => $hypertag)
          {
            $tagid++;

            // get tag id
            $id = getattribute ($hypertag, "id");

            // if id uses special characters
            if (trim ($id) != "" && specialchr ($id, ":-_") == true)
            {
              $result['view'] = "<!DOCTYPE html>
  <html>
  <head>
  <title>hyperCMS</title>
  <meta charset=\"".getcodepage ($lang)."\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />
  </head>
  <body class=\"hcmsWorkplaceGeneric\">
    <p class=hcmsHeadline>".getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters-in-the-content-identification-name'][$lang], $charset, $lang)." '".$id."':<br/>[\]{}()*+?.,\\^$</p>
  </body>
  </html>";

              $result['release'] = 0;
              $result['container'] = $contentfile;
              $result['containerdata'] = $contentdata;
              $result['template'] = $templatefile;
              $result['templatedata'] = $templatedata;
              $result['templateext'] = $templateext;
              $result['name'] = $name_orig;
              $result['objecttype'] = $filetype;

              return $result;
            }

            // set default values if not set
            if (!isset ($hypertagname_href[$id])) $hypertagname_href[$id] = "";
            if (!isset ($hypertagname_text[$id])) $hypertagname_text[$id] = "";
            if (!isset ($hypertagname_target[$id])) $hypertagname_target[$id] = "";
            if (!isset ($hypertag_href[$id][$tagid])) $hypertag_href[$id][$tagid] = "";
            if (!isset ($hypertag_target[$id][$tagid])) $hypertag_target[$id][$tagid] = "";
            if (!isset ($hypertag_text[$id][$tagid])) $hypertag_text[$id][$tagid] = "";
            if (!isset ($onpublish_href[$id][$tagid])) $onpublish_href[$id][$tagid] = "";
            if (!isset ($onpublish_target[$id][$tagid])) $onpublish_target[$id][$tagid] = "";
            if (!isset ($onpublish_text[$id][$tagid])) $onpublish_text[$id][$tagid] = "";
            if (!isset ($onedit_href[$id][$tagid])) $onedit_href[$id][$tagid] = "";
            if (!isset ($language_info[$id])) $language_info[$id] = "";
            if (!isset ($targetlist[$id])) $targetlist[$id] = "";

            // collect unique id's and set position/key of hypertag
            if (!in_array ($id, $id_array))
            {
              $id_array[] = $id;

              // get key (position) of array item
              $position[$id] = $key;
            }

            // get tag name
            $hypertagname = gethypertagname ($hypertag);

            // collect unique id's
            if (!in_array ($id, $id_array)) $id_array[] = $id;

            // get link content
            if ($buildview != "template")
            {
              // set flag for each found link tag
              if ($hypertagname == $searchtag."href" && empty ($href_found[$id]))
              {
                $href_found[$id] = true;
              }
              elseif ($hypertagname == $searchtag."target" && empty ($target_found[$id]))
              {
                $target_found[$id] = true;
              }
              elseif ($hypertagname == $searchtag."text" && empty ($text_found[$id]))
              {
                $text_found[$id] = true;
              }

              // check if link content for id is already set using db_connect
              $db_connect_data = false;

              if (empty ($linkbot[$id]) && !empty ($db_connect))
              {
                // read content using db_connect
                $db_connect_data = db_read_link ($site, $contentfile, $contentdata, $id, $artid, $user);

                if ($db_connect_data != false)
                {
                  $linkhrefbot[$id] = $db_connect_data['href'];
                  $linktargetbot[$id] = $db_connect_data['target'];
                  $linktextbot[$id] = $db_connect_data['text'];

                  // set true
                  $db_connect_data = true;
                }
              }

              // read content from content container
              if ($db_connect_data == false)
              {
                // get the whole link information of the content container
                if (empty ($linkbot[$id]))
                {
                  $bufferarray = selectcontent ($contentdata, "<link>", "<link_id>", $id);
                  if (!empty ($bufferarray[0])) $linkbot[$id] = $bufferarray[0];
                }

                if (!empty ($linkbot[$id]))
                {
                  // get the link file name from linkbot
                  if ($hypertagname == $searchtag."href" && !isset ($linkhrefbot[$id]))
                  {
                    $bufferarray = getcontent ($linkbot[$id], "<linkhref>");
                    if (!empty ($bufferarray[0])) $linkhrefbot[$id] = $bufferarray[0];

                    // escape special characters
                    if (!empty ($linkhrefbot[0])) $linkhrefbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $linkhrefbot[$id]); 
                  }
                  // get the link alttext name from linkbot
                  elseif ($hypertagname == $searchtag."target" && !isset ($linktargetbot[$id]))
                  {
                    $bufferarray = getcontent ($linkbot[$id], "<linktarget>");
                    if (!empty ($bufferarray[0])) $linktargetbot[$id] = $bufferarray[0];

                    // get link targets defined in template
                    $targetlist[$id] = getattribute ($hypertag, "list");

                    // escape special characters
                    if (!empty ($linktargetbot[0])) $linktargetbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $linktargetbot[$id]); 
                  }
                  // get the link alignment name from linkbot
                  elseif ($hypertagname == $searchtag."text" && !isset ($linktextbot[$id]))
                  {
                    $bufferarray = getcontent ($linkbot[$id], "<linktext>");
                    if (!empty ($bufferarray[0])) $linktextbot[$id] = $bufferarray[0];

                    // escape special characters
                    if (!empty ($linktextbot[0])) $linktextbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $linktextbot[$id]);
                  }
                }
              }

              // get hyperCMS tag attributes (specific for each tag found in template)
              if ($hypertagname == $searchtag."href")
              {
                $hypertag_href[$id][$tagid] = $hypertag;
                $hypertagname_href[$id] = $hypertagname;

                // get label text
                if (empty ($label[$id])) $label[$id] = getattribute ($hypertag, "label");

                // get onpublish event
                $onpublish_href[$id][$tagid] = getattribute (strtolower ($hypertag), "onpublish");

                // get onedit event
                $onedit_href[$id][$tagid] = getattribute (strtolower ($hypertag), "onedit");

                // get infotype
                if (!isset ($infotype[$id]) || $infotype[$id] != "meta")
                {
                  $infotype[$id] = getattribute (strtolower ($hypertag), "infotype");
                  if ($infotype[$id] == "meta") $show_meta = true;
                }

                // get disable
                $disable_href[$id][$tagid] = getattribute (strtolower ($hypertag), "disable");
              } 
              elseif ($hypertagname == $searchtag."target")
              {
                $hypertag_target[$id][$tagid] = $hypertag;
                $hypertagname_target[$id] = $hypertagname;
                $onpublish_target[$id][$tagid] = getattribute (strtolower ($hypertag), "onpublish");
              }
              elseif ($hypertagname == $searchtag."text")
              {
                $hypertag_text[$id][$tagid] = $hypertag;
                $hypertagname_text[$id] = $hypertagname;
                $onpublish_text[$id][$tagid] = getattribute (strtolower ($hypertag), "onpublish");
              }

              // get language attribute
              if (empty ($language_info[$id])) $language_info[$id] = getattribute ($hypertag, "language");                           
            }
            // if buildview = template
            else
            {
              // get the link file tag
              if ($hypertagname == $searchtag."href")
              {
                $hypertag_href[$id][$tagid] = $hypertag;
              }
              // get the link alttext tag
              elseif ($hypertagname == $searchtag."target")
              {
                $hypertag_target[$id][$tagid] = $hypertag;
              }
              // get the link alignment tag
              elseif ($hypertagname == $searchtag."text")
              {
                $hypertag_text[$id][$tagid] = $hypertag;
              }
            }
          }

          $tagid_max = $tagid;

          // loop for each tag ID
          foreach ($id_array as $id)
          {
            // set default values if the tags and therefore their content was not found
            if (!isset ($linkhrefbot[$id])) $linkhrefbot[$id] = "";
            if (!isset ($linktargetbot[$id])) $linktargetbot[$id] = "";
            if (!isset ($linktextbot[$id])) $linktextbot[$id] = "";

            // set position for form item
            $key = $position[$id];

            $labelname = "";
            $artid = "";
            $elementid = "";

            if ($searchtag == "artlink")
            {
              // get article id
              $artid = getartid ($id);

              // element id
              $elementid = getelementid ($id);

              // define label
              if (empty ($label[$id])) $labelname = $artid." - ".$elementid;
              else $labelname = $artid." - ".$label[$id];
            }
            else
            {
              // define label
              if (empty ($label[$id])) $labelname = $id;
              else $labelname = $label[$id];
            }

            // set link bots for non existing hyperCMS tags
            if ($buildview != "template")
            {
              if (empty ($href_found[$id])) $linkhrefbot[$id] = "*Null*";
              if (empty ($target_found[$id])) $linktargetbot[$id] = "*Null*";
              if (empty ($text_found[$id])) $linktextbot[$id] = "*Null*";
            }
            // for buildview = template set
            else
            {
              $linkhrefbot[$id] = "";
              $linktargetbot[$id] = "";
              $linktextbot[$id] = "";
            } 

            // loop for each unique tag
            for ($tagid = 1; $tagid <= $tagid_max; $tagid++)
            {
              if (isset ($hypertag_href[$id][$tagid]) || isset ($hypertag_text[$id][$tagid]) || isset ($hypertag_target[$id][$tagid]))
              {
                $groupaccess = false;

                if (isset ($hypertag_href[$id][$tagid]))
                {
                  // get readonly attribute
                  $readonly = getattribute ($hypertag_href[$id][$tagid], "readonly");

                  if ($buildview != "formlock")
                  {
                    if ($readonly != false) $disabled = " disabled=\"disabled\"";
                    else $disabled = "";
                  }

                  // get group access
                  $groupaccess = getattribute ($hypertag_href[$id][$tagid], "groups");
                  $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);
                }

                // ------------------------- cmsview and template ---------------------------

                // in order to access the content via JS
                if ($groupaccess != true && ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock"))
                {
                  // for articles
                  $id = str_replace (":", "_", $id);

                  $formitem[$key] = "
                  <input type=\"hidden\" id=\"".$hypertagname_href[$id]."_".$id."\" value=\"".$linkhrefbot[$id]."\" />
                  <input type=\"hidden\" id=\"".$hypertagname_target[$id]."_".$id."\" value=\"".$linktargetbot[$id]."\" />
                  <input type=\"hidden\" id=\"".$hypertagname_text[$id]."_".$id."\" value=\"".$linktextbot[$id]."\" />";
                }

                if (
                     checklanguage ($language_sessionvalues_array, $language_info[$id]) && $groupaccess == true &&
                     isset ($hypertag_href[$id][$tagid]) && $onedit_href[$id][$tagid] != "hidden" &&
                     (
                       (
                        ($buildview == "cmsview" || $buildview == "inlineview")
                        && @$infotype[$id] != "meta"
                       ) ||
                       $buildview == "formedit" ||
                       ($buildview == "formmeta" && @$infotype[$id] == "meta") ||
                       $buildview == "formlock" || $buildview == "template"
                     )
                   )
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;

                  while (!empty ($hypertag_href[$id][$tagid]) && substr_count ($viewstore_offset, $hypertag_href[$id][$tagid]) > 0)
                  {
                    if ($searchtag == "link")
                    {
                      // create tag link
                      if ($buildview == "cmsview" || $buildview == 'inlineview')
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_link.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_link.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=link&targetlist=".url_encode($targetlist[$id])."&linkhref=".url_encode($linkhrefbot[$id])."&linktarget=".url_encode($linktargetbot[$id])."&linktext=".url_encode($linktextbot[$id])."&contenttype=".url_encode($contenttype)."&token=".$token."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />\n";
                      }
                      elseif ($buildview == "formedit" || ($buildview == "formmeta" && @$infotype[$id] == "meta") || $buildview == "formlock")
                      {
                        if ($linkhrefbot[$id] != "*Null*") $add_submitlink .= "
                        submitLink ('temp_".$hypertagname_href[$id]."[".$id."]', '".$hypertagname_href[$id]."[".$id."]');";

                        if ($buildview == "formedit" || ($buildview == "formmeta" && @$infotype[$id] == "meta")) $taglink = "
                        <div id=\"".$hypertagname_href[$id]."_".$id."_controls\" style=\"display:inline-block;\">
                          <img onClick=\"openBrWindowLink(document.forms['hcms_formview'].elements['".$hypertagname_href[$id]."[".$id."]'],'preview','location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', 'preview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonView\" src=\"".getthemelocation()."img/button_file_liveview.png\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang)."\" />
                          <img onClick=\"openBrWindowLink(document.forms['hcms_formview'].elements['".$hypertagname_href[$id]."[".$id."]'],'','location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" /> 
                          <img onClick=\"deleteEntry(document.forms['hcms_formview'].elements['temp_".$hypertagname_href[$id]."[".$id."]']);\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" />
                          <img onClick=\"setSaveType('form_so', '".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_link.php?view=".url_encode($buildview)."&savetype=form_so&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&contenttype=".url_encode($contenttype)."&token=".$token."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=link&targetlist=".url_encode($targetlist[$id])."&linkhref=' + getValue('temp_".$hypertagname_href[$id]."[".$id."]','') + '&linktarget=' + getSelectedOption('".$hypertagname_target[$id]."[".$id."]','*Null*') + '&linktext=' + getValue('".$hypertagname_text[$id]."[".$id."]','*Null*'), 'post');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_link.png\" alt=\"".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" />
                        </div>";
                        else $taglink = "";

                        $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname_href[$id]."_".$id."\">
                            <b>".$labelname."</b>
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname_href[$id]."_".$id."\">
                            <table>";

                        if ($linkhrefbot[$id] != "*Null*") $formitem[$key] .= "
                              <tr>
                                <td style=\"width:150px;\">".getescapedtext ($hcms_lang['link'][$lang], $charset, $lang)." </td>
                                <td style=\"white-space:nowrap;\">
                                  <input type=\"hidden\" name=\"".$hypertagname_href[$id]."[".$id."]\" value=\"".$linkhrefbot[$id]."\" />
                                  <input type=\"text\" id=\"".$hypertagname_href[$id]."_".$id."\" name=\"temp_".$hypertagname_href[$id]."[".$id."]\" value=\"".convertchars (getlocationname ($site, $linkhrefbot[$id], "page"), $hcms_lang_codepage[$lang], $charset)."\" style=\"width:".$fieldwidth."px;\" ".$disabled." /> ".$taglink."
                                </td>
                              </tr>\n";

                        if ($linktargetbot[$id] != "*Null*")
                        {
                          $formitem[$key] .= "
                              <tr>
                                <td style=\"width:150px;\">
                                  ".getescapedtext ($hcms_lang['link-target'][$lang], $charset, $lang)."
                                </td>
                                <td>
                                  <select id=\"".$hypertagname_target[$id]."_".$id."\" name=\"".$hypertagname_target[$id]."[".$id."]\" style=\"width:".$fieldwidth."px;\" ".$disabled.">";

                          $list_array = null;
                          if (substr_count ($targetlist[$id], "|") >= 1) $list_array = explode ("|", $targetlist[$id]);
                          elseif ($targetlist[$id] != "") $list_array[] = $targetlist[$id];

                          if (is_array ($list_array) && sizeof ($list_array) > 0)
                          {
                            foreach ($list_array as $target)
                            {
                              $formitem[$key] .= "
                                    <option value=\"".$target."\"".($linktargetbot[$id] == $target ? " selected" : "").">".$target."</option>\n";
                            }
                          }

                          $formitem[$key] .= "
                                    <option value=\"_self\"".($linktargetbot[$id] == "_self" ? " selected" : "").">".getescapedtext ($hcms_lang['in-same-frame'][$lang], $charset, $lang)."</option>
                                    <option value=\"_parent\"".($linktargetbot[$id] == "_parent" ? " selected" : "").">".getescapedtext ($hcms_lang['in-parent-frame'][$lang], $charset, $lang)."</option>;
                                    <option value=\"_top\"".($linktargetbot[$id] == "_top" ? " selected" : "").">".getescapedtext ($hcms_lang['in-same-browser-window'][$lang], $charset, $lang)."</option>
                                    <option value=\"_blank\"".($linktargetbot[$id] == "_blank" ? " selected" : "").">".getescapedtext ($hcms_lang['in-new-browser-window'][$lang], $charset, $lang)."</option>
                                  </select>
                                </td>
                              </tr>";
                        }

                        if ($linktextbot[$id] != "*Null*")
                        {
                          $formitem[$key] .= "
                              <tr>
                                <td style=\"width:150px;\">
                                  ".getescapedtext ($hcms_lang['link-text'][$lang], $charset, $lang)."
                                </td>
                                <td>
                                  <input type=\"text\" name=\"".$hypertagname_text[$id]."[".$id."]\" value=\"".$linktextbot[$id]."\" style=\"width:".$fieldwidth."px;\" ".$disabled." />
                                </td>
                              </tr>";
                        }

                        $formitem[$key] .= "
                            </table>
                          </div>";
                      } 
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                          <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                            <tr>
                              <td>
                                <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".$id."</b><br />
                                ".getescapedtext ($hcms_lang['here-you-can-add-a-link'][$lang], $charset, $lang)."</span>
                              </td>
                            </tr>
                          </table>";
                      }
                      else $taglink = "";
                    }
                    elseif ($searchtag == "artlink")
                    {
                     // create tag link
                      if ($buildview == "cmsview" || $buildview == 'inlineview')
                      {
                        $taglink = "<img src=\"".getthemelocation()."img/edit_link.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_link.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&contenttype=".url_encode($contenttype)."&token=".$token."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=artlink&targetlist=".url_encode($targetlist[$id])."&linkhref=".url_encode($linkhrefbot[$id])."&linktarget=".url_encode($linktargetbot[$id])."&linktext=".url_encode($linktextbot[$id])."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />".$arttaglink[$artid]."\n";
                      }
                      elseif ($buildview == "formedit" || ($buildview == "formmeta" && @$infotype[$id] == "meta") || $buildview == "formlock")
                      {
                        if ($linkhrefbot[$id] != "*Null*") $add_submitlink .= "
                        submitLink ('temp_".$hypertagname_href[$id]."[".$id."]', '".$hypertagname_href[$id]."[".$id."]');";

                        if ($buildview == "formedit" || ($buildview == "formmeta" && $infotype == "meta")) $taglink = "
                        <div id=\"".$hypertagname_href[$id]."_".$artid."_".$elementid."_controls\" style=\"display:inline-block;\">
                          <img onClick=\"openBrWindowLink(document.forms['hcms_formview'].elements['".$hypertagname_href[$id]."[".$id."]'],'preview','location=no,menubar=no,toolbar=no,titlebar=no,crollbars=yes,resizable=yes,status=no', 'preview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonView\" src=\"".getthemelocation()."img/button_file_liveview.png\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang)."\" />
                          <img onClick=\"openBrWindowLink(document.forms['hcms_formview'].elements['".$hypertagname_href[$id]."[".$id."]'],'','location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" /> 
                          <img onClick=\"deleteEntry(document.forms['hcms_formview'].elements['temp_".$hypertagname_href[$id]."[".$id."]']);\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" />
                          <img onClick=\"setSaveType('form_so', '".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_link.php?view=".url_encode($buildview)."&savetype=form_so&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&contenttype=".url_encode($contenttype)."&token=".$token."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=artlink&targetlist=".url_encode($targetlist[$id])."&linkhref=' + getValue('temp_".$hypertagname_href[$id]."[".$id."]','') + '&linktarget=' + getSelectedOption('".$hypertagname_target[$id]."[".$id."]','*Null*') + '&linktext=' + getValue('".$hypertagname_text[$id]."[".$id."]','*Null*'), 'post');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_link.png\" border=\"0\" alt=\"".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" />
                        </div>";
                        else $taglink = "";

                        $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname_href[$id]."_".$artid."_".$elementid."\">
                            <b>".$labelname."</b> ".$arttaglink[$artid]."
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname_href[$id]."_".$artid."_".$elementid."\">
                            <table>";

                        if ($linkhrefbot[$id] != "*Null*") $formitem[$key] .= "
                              <tr>
                                <td style=\"width:150px;\">".getescapedtext ($hcms_lang['link'][$lang], $charset, $lang)." </td>
                                <td style=\"white-space:nowrap;\">
                                  <input type=\"hidden\" name=\"".$hypertagname_href[$id]."[".$id."]\" value=\"".$linkhrefbot[$id]."\" />
                                  <input type=\"text\" id=\"".$hypertagname_href[$id]."_".$artid."_".$elementid."\" name=\"temp_".$hypertagname_href[$id]."[".$id."]\" value=\"".convertchars (getlocationname ($site, $linkhrefbot[$id], "page", "path"), $hcms_lang_codepage[$lang], $charset)."\" style=\"width:".$fieldwidth."px;\"".$disabled." /> ".$taglink."
                                </td>
                              </tr>\n";

                        if ($linktargetbot[$id] != "*Null*")
                        {
                          $formitem[$key] .= "
                              <tr>
                                <td style=\"width:150px;\">".getescapedtext ($hcms_lang['link-target'][$lang], $charset, $lang)."  </td>
                                <td>
                                  <select id=\"".$hypertagname_target[$id]."_".$artid."_".$elementid."\" name=\"".$hypertagname_target[$id]."[".$id."]\" style=\"width:".$fieldwidth."px;\" ".$disabled.">";

                          $list_array = null;
                          if (substr_count ($targetlist[$id], "|") >= 1) $list_array = explode ("|", $targetlist[$id]);
                          elseif ($targetlist[$id] != "") $list_array[] = $targetlist[$id];

                          if (is_array ($list_array) && sizeof ($list_array) > 0)
                          {
                            foreach ($list_array as $target)
                            {
                              $formitem[$key] .= "
                                    <option value=\"".$target."\"".($linktargetbot[$id] == $target ? " selected" : "").">".$target."</option>";
                            }
                          }

                          $formitem[$key] .= "
                                    <option value=\"_self\"".($linktargetbot[$id] == "_self" ? " selected" : "").">".getescapedtext ($hcms_lang['in-same-frame'][$lang], $charset, $lang)."</option>
                                    <option value=\"_parent\"".($linktargetbot[$id] == "_parent" ? " selected" : "").">".getescapedtext ($hcms_lang['in-parent-frame'][$lang], $charset, $lang)."</option>;
                                    <option value=\"_top\"".($linktargetbot[$id] == "_top" ? " selected" : "").">".getescapedtext ($hcms_lang['in-same-browser-window'][$lang], $charset, $lang)."</option>
                                    <option value=\"_blank\"".($linktargetbot[$id] == "_blank" ? " selected" : "").">".getescapedtext ($hcms_lang['in-new-browser-window'][$lang], $charset, $lang)."</option>
                                  </select>
                                </td>
                              </tr>";
                        }

                        if ($linktextbot[$id] != "*Null*")
                        {
                          $formitem[$key] .= "
                              <tr>
                                <td style=\"width:150px;\">".getescapedtext ($hcms_lang['link-text'][$lang], $charset, $lang)." </td>
                                <td>
                                  <input type=\"text\" id=\"".$hypertagname_text[$id]."_".$id."\" name=\"".$hypertagname_text[$id]."[".$id."]\" value=\"".$linktextbot[$id]."\" style=\"width:".$fieldwidth."px;\" ".$disabled." />
                                </td>
                              </tr>";
                        }

                        $formitem[$key] .= "
                          </table>
                        </div>";
                      } 
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Article: ".$artid."</b><br />
                              <b>Element: ".$elementid."</b><br />
                              </b>".getescapedtext ($hcms_lang['here-you-can-add-a-link'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    else $taglink = "";

                    if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                    {
                      break;
                    }
                    else
                    {
                      // get full HTML link-tag
                      $hreftag = gethtmltag ($viewstore_offset, $hypertag_href[$id][$tagid]);
                      if ($hreftag == false) $hreftag = $hypertag_href[$id][$tagid];

                      $repl_start = strpos ($viewstore, $hreftag, $repl_offset);

                      if ($buildview == "cmsview" || $buildview == "inlineview")
                      {
                        // define offset
                        $repl_offset = $repl_start + strlen ($taglink.$hreftag);
                      }
                      elseif ($buildview == "template")
                      {
                        // define offset
                        $repl_offset = $repl_start + strlen ($taglink.$hreftag);
                        $repl_len = strlen ($hreftag);

                        // replace tag
                        $viewstore = substr_replace ($viewstore, $hreftag, $repl_start, $repl_len);
                      }

                      // insert taglink
                      $viewstore = substr_replace ($viewstore, $taglink, $repl_start, 0);
                      $viewstore_offset = substr ($viewstore, $repl_offset);
 
                      if ($buildview == "cmsview" || $buildview == "inlineview") break;
                    }
                  }

                  // deconvert path
                  $linkhrefbot_insert = deconvertpath ($linkhrefbot[$id], "url");
                }
                // --------------------------- publish ----------------------------
                // include code for link management, only when published
                // please note: if linkhrefbot is onEdit='hidden' then href should be inserted without active link management
                elseif ($buildview == "publish")
                {
                  // link engine is on
                  if ($mgmt_config[$site]['linkengine'] == true && ($application != "htm" || $application != "xml"))
                  {
                    // include code for link management when timeswitched or non-article
                    if ($searchtag == "link" || ($searchtag == "artlink" && $artstatus[$artid] == "active"))
                    {
                      if ((!isset ($onedit_href[$id][$tagid]) || $onedit_href[$id][$tagid] != "hidden") && (!isset ($onpublish_href[$id][$tagid]) || $onpublish_href[$id][$tagid] != "hidden"))
                      {
                        $linkhrefbot_insert = tpl_insertlink ($application, "hypercms_".$container_id, $id);
                      }
                      elseif ((isset ($onedit_href[$id][$tagid]) && $onedit_href[$id][$tagid] == "hidden") && (!isset ($onpublish_href[$id][$tagid]) || $onpublish_href[$id][$tagid] != "hidden"))
                      {
                        $linkhrefbot_insert = str_replace ("%page%/".$site."/", $publ_config['url_publ_page'], $linkhrefbot[$id]);
                      }
                      else $linkhrefbot_insert = "";
                    }
                    // include code for link management and build article time management code including content
                    elseif ($searchtag == "artlink" && $artstatus[$artid] == "timeswitched")
                    {
                      $linkhrefbot_insert = tpl_tsinsertlink ($application, $artdatefrom[$artid], $artdateto[$artid], "hypercms_".$container_id, $id);
                      if ($hypertag_target[$id][$tagid] != "") $linktargetbot[$id] = tpl_tselement ($application, $artdatefrom[$artid], $artdateto[$artid], $linktargetbot[$id]);
                      if ($hypertag_text[$id][$tagid] != "") $linktextbot[$id] = tpl_tselement ($application, $artdatefrom[$artid], $artdateto[$artid], $linktextbot[$id]);
                    }
                    // build article time management code when link inactive
                    elseif ($searchtag == "artlink" && $artstatus[$artid] == "inactive")
                    {
                      $linkhrefbot_insert = "#";
                      if ($hypertag_target[$id][$tagid] != "") $linktargetbot[$id] = "";
                      if ($hypertag_text[$id][$tagid] != "") $linktextbot[$id] = "";
                    }
                  }
                  // link engine is off
                  elseif ($mgmt_config[$site]['linkengine'] == false || ($application == "htm" || $application == "xml"))
                  {
                    // build article time management code when timeswitched or non-article
                    if ($searchtag == "link" || ($searchtag == "artlink" && $artstatus[$artid] == "active"))
                    {
                      // $linkhrefbot[$id] must use the publication target settings
                      $linkhrefbot_insert = deconvertlink ($linkhrefbot[$id]);
                    }
                    // build article time management code if link is timeswitched
                    elseif ($searchtag == "artlink" && $artstatus[$artid] == "timeswitched")
                    {
                      $linkhrefbot_insert = tpl_tslink ($application, $artdatefrom[$artid], $artdateto[$artid], deconvertlink ($linkhrefbot[$id]));
                      if ($hypertag_target[$id][$tagid] != "") $linktargetbot[$id] = tpl_tselement ($application, $artdatefrom[$artid], $artdateto[$artid], $linktargetbot[$id]);
                      if ($hypertag_text[$id][$tagid] != "") $linktextbot[$id] = tpl_tselement ($application, $artdatefrom[$artid], $artdateto[$artid], $linktextbot[$id]);
                    }
                    // build article time management code if link is inactive
                    elseif ($searchtag == "artlink" && $artstatus[$artid] == "inactive")
                    {
                      $linkhrefbot_insert = "#";
                      if ($hypertag_target[$id][$tagid] != "") $linktargetbot[$id] = "";
                      if ($hypertag_text[$id][$tagid] != "") $linktextbot[$id] = "";
                    } 
                  } 
                }
                // --------------------------- preview ----------------------------
                else
                {
                  // deconvert path
                  $linkhrefbot_insert = deconvertpath ($linkhrefbot[$id], "url");
                }

                if ($buildview != "formedit" && $buildview != "formmeta" && $buildview != "formlock")
                {
                  // escape variable and add slashes if onedit=hidden
                  if ($onedit_href[$id][$tagid] == "hidden")
                  {
                    $linktextbot[$id] = addslashes ($linktextbot[$id]);
                    $linktextbot[$id] = str_replace ("\$", "\\\$", $linktextbot[$id]);
                  }

                  // disable link-href click event
                  if (($buildview == "cmsview" || $buildview == "inlineview") && !empty ($disable_href[$id][$tagid]))
                  {
                    $linkhrefbot_insert = "javascript:void(0);";
                  }

                  // ----------------------------- insert hyperreferenes ------------------------------
                  // replace hyperCMS link-tag with contentbot for the page view -> <a href="$linkhref" target="...">
                  if (isset ($hypertag_href[$id][$tagid]) && $hypertag_href[$id][$tagid] != "")
                  {
                    if ($onpublish_href[$id][$tagid] != "hidden") $viewstore = str_replace ($hypertag_href[$id][$tagid], $linkhrefbot_insert, $viewstore);
                    elseif ($onpublish_href[$id][$tagid] == "hidden") $viewstore = str_replace ($hypertag_href[$id][$tagid], "", $viewstore);
                  }

                  if (isset ($hypertag_target[$id][$tagid]) && $hypertag_target[$id][$tagid] != "")
                  {
                    // to avoid opening of a new browser window for all views except publish
                    if ($onpublish_target[$id][$tagid] != "hidden") $viewstore = str_replace ($hypertag_target[$id][$tagid], $linktargetbot[$id], $viewstore);
                    elseif ($onpublish_target[$id][$tagid] == "hidden") $viewstore = str_replace ($hypertag_target[$id][$tagid], "", $viewstore);
                  }

                  if (isset ($hypertag_text[$id][$tagid]) && $hypertag_text[$id][$tagid] != "")
                  {
                    if ($onpublish_text[$id][$tagid] != "hidden") $viewstore = str_replace ($hypertag_text[$id][$tagid], $linktextbot[$id], $viewstore);
                    elseif ($onpublish_text[$id][$tagid] == "hidden") $viewstore = str_replace ($hypertag_text[$id][$tagid], "", $viewstore);
                  }
                }
              }
            } // end for each unique tag loop
          } // end for each unique content ID loop
        }
      }


      // ================================================ component content ===================================================

      // create view for component content
      $searchtag_array = array();
      $searchtag_array[0] = "component";
      $searchtag_array[1] = "artcomponent";
      $id_array = array();
      $infotype = array();
      $position = array();
      $onpublish = "";
      $onedit = "";
      $include = "";
      $comppathtype = "";
      $icon = "";
      $add_submitcomp = "";

      foreach ($searchtag_array as $searchtag)
      {
        // get all hyperCMS tags
        $hypertag_array = gethypertag ($viewstore, $searchtag, 0);

        if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
        {
          reset ($hypertag_array);

          // loop for each hyperCMS tag found in template
          foreach ($hypertag_array as $key => $hypertag)
          {
            // get mediatype
            $mediatype = getattribute ($hypertag, "mediatype");

            // verify mediatype for assets only
            if (!empty ($mediatype) && !empty ($mediafile))
            {
              $continue = true;

              if (strpos (strtolower ("_".$mediatype), "audio") > 0 && is_audio ($mediafile)) $continue = false;
              elseif (strpos (strtolower ("_".$mediatype), "image") > 0 && is_image ($mediafile)) $continue = false;
              elseif ((strpos (strtolower ("_".$mediatype), "document") > 0 || strpos (strtolower ("_".$mediatype), "text") > 0) && is_document ($mediafile)) $continue = false;
              elseif (strpos (strtolower ("_".$mediatype),"video") > 0 && is_video ($mediafile)) $continue = false;
              elseif (strpos (strtolower ("_".$mediatype), "compressed") > 0 && is_compressed ($mediafile)) $continue = false;

              if ($continue == true) continue;
            }

            // get tag name
            $hypertagname = gethypertagname ($hypertag);

            // get tag id
            $id = getattribute ($hypertag, "id");

            // if id uses special characters
            if (trim ($id) != "" && specialchr ($id, ":-_") == true)
            {
              $result['view'] = "<!DOCTYPE html>
  <html>
  <head>
  <title>hyperCMS</title>
  <meta charset=\"".getcodepage ($lang)."\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />
  </head>
  <body class=\"hcmsWorkplaceGeneric\">
    <p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters-in-the-content-identification-name'][$lang], $charset, $lang)." '".$id."':<br/>[\]{}()*+?.,\\^$</p>
  </body>
  </html>";

              $result['release'] = 0;
              $result['container'] = $contentfile;
              $result['containerdata'] = $contentdata;
              $result['template'] = $templatefile;
              $result['templatedata'] = $templatedata;
              $result['templateext'] = $templateext;
              $result['name'] = $name_orig;
              $result['objecttype'] = $filetype;

              return $result;
            }

            // get label text
            $label = getattribute ($hypertag, "label");
            $labelname = "";
            $artid = "";
            $elementid = "";

            if ($searchtag == "artcomponent")
            {
              // get article id
              $artid = getartid ($id);

              // element id
              $elementid = getelementid ($id);

              // define label
              if ($label == false || $label == "") $labelname = $artid." - ".$elementid;
              else $labelname = $artid." - ".$label;
            }
            else
            {
              // define label
              if ($label == false || $label == "") $labelname = $id;
              else $labelname = $label;
            }

            // get tag visibility on publish
            $onpublish = getattribute (strtolower ($hypertag), "onpublish");

            // get tag visibility on edit
            $onedit = getattribute (strtolower ($hypertag), "onedit");

            // get type of content
            $infotype = getattribute (strtolower ($hypertag), "infotype");
            if ($infotype == "meta") $show_meta = true;

            // get tag visibility on edit
            $icon = getattribute (strtolower ($hypertag), "icon");

            // get inclusion type (dynamic or static)
            // if no attribute is set, default value will be "dynamic"
            $include = getattribute (strtolower ($hypertag), "include");

            // get value of tag
            $defaultvalue = getattribute ($hypertag, "default");

            // get readonly attribute
            $readonly = getattribute ($hypertag, "readonly");

            // get path type [file,url,abs]
            $comppathtype = getattribute ($hypertag, "pathtype");

            if ($buildview != "formlock")
            {
              if ($readonly != false) $disabled = " disabled=\"disabled\"";
              else $disabled = "";
            }

            // get group access
            $groupaccess = getattribute ($hypertag, "groups");
            $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);

            if ($defaultvalue != "")
            {
              // convert path that was previously deconverted after template loaded
              $defaultvalue = convertpath ($site, $defaultvalue, "comp");

              // replace the publication varibale
              $defaultvalue = str_replace ("%publication%", $site, $defaultvalue);

              // replace seperator
              if ($hypertagname == $searchtag."m")
              {
                if (substr_count ($defaultvalue, ";") > 0) $defaultvalue = str_replace (";", "|", $defaultvalue);
                if ($defaultvalue[strlen ($defaultvalue) - 1] != "|") $defaultvalue = $defaultvalue."|";
              }
            }

            // get language attribute
            $language_info = getattribute ($hypertag, "language");

            // set flag for edit button
            if (empty ($foundcomp[$id])&& $onedit != "hidden") $foundcomp[$id] = true;
            elseif (!empty ($foundcomp[$id])) $foundcomp[$id] = false;

            // collect unique id's and set position/key of hypertag
            if (!in_array ($id, $id_array) && $onedit != "hidden")
            {
              $id_array[] = $id;

              // get key (position) of array item
              $position[$id] = $key;
            }

            // set position for form item
            if (!empty ($position[$id])) $key = $position[$id];

            // check uniqueness
            $tags = "hyperCMS:".$searchtag."s id='".$id."'";
            $tagm = "hyperCMS:".$searchtag."m id='".$id."'";

            $tagscount = substr_count ($viewstore, $tags);
            $tagmcount = substr_count ($viewstore, $tagm);

            $control_sum = 0;

            if ($tagscount >= 1) $control_sum = $control_sum + 1;
            if ($tagmcount >= 1) $control_sum = $control_sum + 1;

            // if textu, textf or textl tag have the same id => error
            if ($control_sum >= 2)
            {
              $result['view'] = "<!DOCTYPE html>
  <html>
  <head>
  <title>hyperCMS</title>
  <meta charset=\"".$hcms_lang_codepage[$lang]."\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />
  </head>
  <body class=\"hcmsWorkplaceGeneric\">
    <p class=\"hcmsHeadline\">".$hcms_lang['the-tags'][$lang]." [$tags] ".$hcms_lang['and-or'][$lang]." [$tagm] ".$hcms_lang['have-the-same-identification-id'][$lang]."</p>
    ".$hcms_lang['please-note-the-tag-identification-must-be-unique-for-different-tag-types-of-the-same-tag-set'][$lang]."
  </body>
  </html>";

              $result['release'] = 0;
              $result['container'] = $contentfile;
              $result['containerdata'] = $contentdata;
              $result['template'] = $templatefile;
              $result['templatedata'] = $templatedata;
              $result['templateext'] = $templateext;
              $result['name'] = $name_orig;
              $result['objecttype'] = $filetype;

              return $result;
            }
            else
            {
              $condbot = "";
              $contentbot = "";
              $db_connect_data = false;

              if ($buildview != "template")
              {
                // read content using db_connect
                if (!empty ($db_connect))
                {
                  $db_connect_data = db_read_component ($site, $contentfile, $contentdata, $id, $artid, $user);

                  if ($db_connect_data != false)
                  {
                    $contentbot = $db_connect_data['file'];
                    $condbot = $db_connect_data['condition'];

                    // set true
                    $db_connect_data = true;
                  }
                }

                // read content from content container
                if ($db_connect_data == false && !empty ($contentdata))
                {
                  // get content
                  $contentarray = selectcontent ($contentdata, "<component>", "<component_id>", $id);

                  if (!empty ($contentarray[0])) $condarray = getcontent ($contentarray[0], "<componentcond>");
                  if (!empty ($condarray[0])) $condbot = trim ($condarray[0]);

                  if (!empty ($contentarray[0])) $contentarray = getcontent ($contentarray[0], "<componentfiles>");
                  if (!empty ($contentarray[0])) $contentbot = $contentarray[0];
                }

                // set default value eventually given by tag
                if ($contentbot == "" && $defaultvalue != "") $contentbot = $defaultvalue;

                // convert object ID to object path
                $contentbot = getobjectlink ($contentbot);
              }

              // -------------------------------------- cmsview --------------------------------------------

              // in order to access the content via JS
              if ($groupaccess != true && ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock"))
              {
                // for articles
                $id = str_replace (":", "_", $id);

                $formitem[$key] = "
                <input type=\"hidden\" id=\"".$hypertagname."_".$id."\" value=\"".$contentbot."\" />";
              }

              if (
                   checklanguage ($language_sessionvalues_array, $language_info) && $groupaccess == true &&
                   $onedit != "hidden" &&
                   $buildview == "template" ||
                   (
                     isset ($foundcomp[$id]) && $foundcomp[$id] == true &&
                     (
                       (($buildview == "cmsview" || $buildview == 'inlineview') && $infotype != "meta") ||
                       $buildview == "formedit" ||
                       ($buildview == "formmeta" && $infotype == "meta") ||
                       $buildview == "formlock"
                     )
                   )
                 )
              {
                $taglink = "";

                // load customer profiles
                if (!isset ($profile_array) && ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock"))
                {
                  $scandir = scandir ($mgmt_config['abs_path_data']."customer/".$site."/");

                  if ($scandir)
                  {
                    $profile_array = array();

                    foreach ($scandir as $entry)
                    {
                      if ($entry != "." && $entry != ".." && !is_dir ($entry))
                      {
                        if (strpos ($entry, ".prof.dat") > 0)
                        {
                          $entry = substr ($entry, 0, strpos ($entry, ".prof.dat"));
                          $profile_array[] = $entry;
                        }
                      }
                    }

                    if (sizeof ($profile_array) > 0) sort ($profile_array);
                  }
                }

                // ------------ single component --------------
                // replace hyperCMS tag with content
                if ($hypertagname == $searchtag."s")
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;

                  // get the first objectpath from the single component string
                  if (!empty ($contentbot))
                  {
                    $temp = link_db_getobject ($contentbot);
                    if (!empty ($temp[0])) $contentbot = $temp[0];
                  }

                  while (!empty ($hypertag) && substr_count ($viewstore_offset, $hypertag) > 0)  // necessary loop for unique media names for rollover effect
                  {
                    if ($searchtag == "component")
                    {
                      // create tag link for editor
                      if (!empty ($contentbot)) $compeditlink = "<img onclick=\"hcms_openWindowComp('', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=yes', '".str_replace ("%comp%", "", $contentbot)."');\" src=\"".getthemelocation()."img/edit_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\"  style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" /><img src=\"".getthemelocation()."img/edit_delete.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."service/savecontent.php?site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=single&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&component[".$id."]=&condition[".$id."]=".url_encode($condbot)."&token=".$token."';\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\" />";
                      else $compeditlink = "";
 
                      if ($buildview == "cmsview" || $buildview == "inlineview")
                      {
                        $taglink = "<div style=\"all:unset; display:inline-block !important;\"><img src=\"".getthemelocation()."img/edit_compsingle.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_component.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=single&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&component=".url_encode($contentbot)."&condition=".url_encode($condbot)."&mediatype=".url_encode($mediatype)."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; margin:0; padding:0; cursor:pointer;\" />".$compeditlink."</div>\n";
                      }
                      elseif ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                      {
                        $comp_entry_name = getlocationname ($site, $contentbot, "comp", "path"); 
                        if (strlen ($comp_entry_name) > 50) $comp_entry_name = "...".substr (substr ($comp_entry_name, -50), strpos (substr ($comp_entry_name, -50), "/")); 

                        $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                            <b>".$labelname."</b>
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                            <table  class=\"hcmsTableNarrow\">
                              <tr>";

                        // only if not DAM
                        if (!$mgmt_config[$site]['dam']) $formitem[$key] .= "
                                <td width=\"150\">".getescapedtext ($hcms_lang['single-component'][$lang], $charset, $lang)." </td>";

                        $formitem[$key] .= "
                                <td style=\"white-space:nowrap;\">
                                  <input type=\"hidden\" id=\"".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" />
                                  <input type=\"text\" name=\"temp_".$hypertagname."_".$id."\" value=\"".convertchars ($comp_entry_name, $hcms_lang_codepage[$lang], $charset)."\" style=\"width:".$fieldwidth."px;\" readonly=\"readonly\" ".$disabled." />";
 
                        if (($buildview == "formedit" || ($buildview == "formmeta" && $infotype == "meta")) && empty ($disabled)) $formitem[$key] .= "
                                  <div id=\"".$hypertagname."_".$id."_controls\" style=\"display:inline-block;\">
                                    <img onClick=\"openBrWindowComp(document.forms['hcms_formview'].elements['".$hypertagname."[".$id."]'],'','location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\"> 
                                    <img onClick=\"deleteEntry(document.forms['hcms_formview'].elements['".$hypertagname."[".$id."]']); deleteEntry(document.forms['hcms_formview'].elements['temp_".$hypertagname."_".$id."']);\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" />
                                    <img onClick=\"setSaveType('form_so', '".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_component.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=single&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&component=".url_encode($contentbot)."&condition=".url_encode($condbot)."&mediatype=".url_encode($mediatype)."', 'post');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_compsingle.png\" alt=\"".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" />
                                  </div>";

                        $formitem[$key] .= "
                                </td>
                              </tr>";

                        // personalization/customers profiles only if not DAM
                        if (empty ($mgmt_config[$site]['dam']))
                        {
                          $formitem[$key] .= "
                              <tr>
                                <td style=\"width:150px;\">".getescapedtext ($hcms_lang['customer-profile'][$lang], $charset, $lang)." </td>
                                <td style=\"padding-top:3px;\">
                                  <select name=\"condition[".$id."]\" style=\"width:".$fieldwidth."px;\" ".$disabled.">
                                    <option value=\"\">".getescapedtext ($hcms_lang['select'][$lang], $charset, $lang)."</option>";

                          if (sizeof ($profile_array) >= 1)
                          {
                            reset ($profile_array);

                            foreach ($profile_array as $profile)
                            {
                              $formitem[$key] .= "
                                    <option value=\"".$profile."\"".($profile == $condbot ? " selected" : "").">".$profile."</option>";
                            }
                          }

                          $formitem[$key] .= "
                                  </select>
                                </td>
                              </tr>";
                        }
 
                        $formitem[$key] .= "
                            </table>
                          </div>";
                      } 
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['here-you-can-insert-a-single-component'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>\n";
                      }
                      else $taglink = "";
                    }
                    elseif ($searchtag == "artcomponent")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview" || $buildview == 'inlineview')
                      {
                        $taglink = "<div style=\"all:unset; display:inline-block !important;\"><img src=\"".getthemelocation()."img/edit_compsingle.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_component.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=single&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".$db_connect."&id=".$id."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&component[".$id."]=".url_encode($contentbot)."&condition[".$id."]=".url_encode($condbot)."&mediatype[".$id."]=".url_encode($mediatype)."';\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; margin:0; padding:0; cursor:pointer;\" />".$arttaglink[$artid]."</div>\n";
                      }
                      elseif ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                      {
                        $comp_entry_name = getlocationname ($site, $contentbot, "comp", "path"); 
                        if (strlen ($comp_entry_name) > 50) $comp_entry_name = "...".substr (substr ($comp_entry_name, -50), strpos (substr ($comp_entry_name, -50), "/")); 

                        $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname."_".$artid."_".$elementid."\">
                            <b>".$labelname."</b> ".$arttaglink[$artid]."
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$artid."_".$elementid."\">
                            <table class=\"hcmsTableNarrow\">
                              <tr>
                                <td style=\"width:150px;\">".getescapedtext ($hcms_lang['single-component'][$lang], $charset, $lang)." </td>
                                <td style=\"white-space:nowrap;\">
                                  <input type=\"hidden\" id=\"".$hypertagname."_".$artid."_".$elementid."\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" />
                                  <input type=\"text\" name=\"temp_".$hypertagname."_".$artid."_".$elementid."\" value=\"".convertchars ($comp_entry_name, $hcms_lang_codepage[$lang], $charset)."\" style=\"width:".$fieldwidth."px;\" readonly=\"readonly\" ".$disabled." />";

                        if (($buildview == "formedit" || ($buildview == "formmeta" && $infotype == "meta")) && empty ($disabled)) $formitem[$key] .= "
                                  <div id=\"".$hypertagname."_".$artid."_".$elementid."_controls\" style=\"display:inline-block;\">
                                    <img onClick=\"openBrWindowComp(document.forms['hcms_formview'].elements['".$hypertagname."[".$id."]'], '', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" /> 
                                    <img onClick=\"deleteEntry(document.forms['hcms_formview'].elements['".$hypertagname."[".$id."]']); deleteEntry(document.forms['hcms_formview'].elements['temp_".$hypertagname."_".$artid."_".$elementid."']);\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" />
                                    <img onClick=\"setSaveType('form_so', '".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_component.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=single&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&component=".url_encode($contentbot)."&condition=".url_encode($condbot)."&mediatype=".url_encode($mediatype)."', 'post');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_compsingle.png\" alt=\"".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" />
                                  </div>";

                        // personalization/customers profiles only if not DAM
                        if (empty ($mgmt_config[$site]['dam']))
                        {
                          $formitem[$key] .= "
                                  </td>
                                </tr>
                                <tr>
                                  <td style=\"width:150px;\">".getescapedtext ($hcms_lang['customer-profile'][$lang], $charset, $lang)." </td>
                                  <td style=\"padding-top:3px;\">
                                    <select name=\"condition[".$id."]\" style=\"width:".$fieldwidth."px;\" ".$disabled.">
                                      <option value=\"\">".getescapedtext ($hcms_lang['select'][$lang], $charset, $lang)."</option>";

                          if (sizeof ($profile_array) >= 1)
                          {
                            reset ($profile_array);

                            foreach ($profile_array as $profile)
                            {
                              $formitem[$key] .= "
                                      <option value=\"".$profile."\"".($profile == $condbot ? " selected" : "").">".$profile."</option>\n";
                            }
                          }

                          $formitem[$key] .= "
                                    </select>
                                  </td>
                                </tr>";
                        }

                        $formitem[$key] .= "
                            </table>
                          </div>";
                      } 
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Article: ".$artid."</b><br />
                              <b>Element: ".$elementid."</b><br />
                              ".getescapedtext ($hcms_lang['here-you-can-insert-a-single-component'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>\n";
                      }
                      else $taglink = "";
                    }
                    else $taglink = "";

                    // insert taglink
                    $repl_start = strpos ($viewstore, $hypertag, $repl_offset);
                    $repl_offset = $repl_start + strlen ($taglink.$hypertag);
                    $viewstore = substr_replace ($viewstore, $taglink, $repl_start, 0);
                    $viewstore_offset = substr ($viewstore, $repl_offset);

                    break;
                  }
                }
                // ------------ multi component --------------
                elseif ($hypertagname == $searchtag."m")
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;
                  $i = 0;

                  while (!empty ($hypertag) && substr_count ($viewstore_offset, $hypertag) > 0)
                  {
                    if ($searchtag == "component")
                    {
                      // create tag link for editor
                      if (($buildview == "cmsview" || $buildview == 'inlineview') && $onedit != "hidden")
                      {
                        $taglink = "<img onClick=\"hcms_selectComponents('".$hypertagname."','".$id."','".$condbot."','".$mediatype."','".$i."','send');\" src=\"".getthemelocation()."img/edit_compmulti.png\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; cursor:pointer; z-index:9999999;\" /><br />\n";
                      }
                      elseif ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                      {
                        $add_submitcomp .= "
                        submitMultiComp ('".$hypertagname."_".$id."', '".$hypertagname."[".$id."]');";

                        $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname."_".$id."\">
                            <b>".$labelname."</b>
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$id."\">
                            <table class=\"hcmsTableNarrow\">
                              <tr>";

                        // only if not DAM
                        if (!$mgmt_config[$site]['dam']) $formitem[$key] .= "
                                <td style=\"width:150px; vertical-align:top;\">".getescapedtext ($hcms_lang['multiple-component'][$lang], $charset, $lang)." </td>";
 
                        $formitem[$key] .= "
                                <td style=\"white-space:nowrap;\">
                                  <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" />
                                  <select id=\"".$hypertagname."_".$id."\" name=\"".$hypertagname."_".$id."\" size=\"10\" style=\"width:".$fieldwidth."px; height:160px;\" ".$disabled.">";

                        if (!empty ($contentbot) && $contentbot != false)
                        {
                          // cut off last delimiter
                          $component = trim ($contentbot, "|");

                          // split component string into array
                          $component_array = array();

                          if (substr_count ($component, "|") > 0) $component_array = explode ("|", $component);
                          else $component_array[0] = $component;

                          foreach ($component_array as $comp_entry)
                          {
                            $comp_entry_name = getlocationname ($site, $comp_entry, "comp", "path"); 
                            if (strlen ($comp_entry_name) > 50) $comp_entry_name = "...".substr (substr ($comp_entry_name, -50), strpos (substr ($comp_entry_name, -50), "/"));
          
                            $formitem[$key] .= "
                                    <option value=\"".$comp_entry."\">".convertchars ($comp_entry_name, $hcms_lang_codepage[$lang], $charset)."</option>";
                          }
                        }

                        $formitem[$key] .= "
                                  </select>
                                </td>";

                        if (($buildview == "formedit" || ($buildview == "formmeta" && $infotype == "meta")) && empty ($disabled)) $formitem[$key] .= "
                                <td style=\"padding:2px;\">
                                  <div id=\"".$hypertagname."_".$id."_controls\">
                                    <img onClick=\"moveSelected(document.forms['hcms_formview'].elements['".$hypertagname."_".$id."'], false)\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonUp\" src=\"".getthemelocation()."img/button_moveup.png\" alt=\"".getescapedtext ($hcms_lang['move-component-up'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['move-component-up'][$lang], $charset, $lang)."\" /><br />
                                    <img onClick=\"openBrWindowComp(document.forms['hcms_formview'].elements['".$hypertagname."_".$id."'], '', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" /><br /> 
                                    <img onClick=\"deleteSelected(document.forms['hcms_formview'].elements['".$hypertagname."_".$id."'])\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.png\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" /><br />
                                    <img onClick=\"setSaveType('form_so', '".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_component.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=multi&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&condition=".url_encode($condbot)."&mediatype=".url_encode($mediatype)."', 'post');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_compmulti.png\" alt=\"".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" /><br />
                                    <img onClick=\"moveSelected(document.forms['hcms_formview'].elements['".$hypertagname."_".$id."'], true)\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDown\" src=\"".getthemelocation()."img/button_movedown.png\" alt=\"".getescapedtext ($hcms_lang['move-component-down'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['move-component-down'][$lang], $charset, $lang)."\" />
                                  </div>
                                </td>";
 
                        $formitem[$key] .= "
                              </tr>";
 
                        // personalization/customers profiles only if not DAM
                        if (empty ($mgmt_config[$site]['dam']))
                        {
                          $formitem[$key] .= "
                          <tr>
                            <td style=\"width:150px; vertical-align:top;\">".getescapedtext ($hcms_lang['customer-profile'][$lang], $charset, $lang)." </td>
                            <td style=\"padding-top:3px;\">
                              <select name=\"condition[".$id."]\" style=\"width:".$fieldwidth."px;\" ".$disabled.">
                                <option value=\"\">".getescapedtext ($hcms_lang['select'][$lang], $charset, $lang)."</option>";

                          if (sizeof ($profile_array) >= 1)
                          {
                            reset ($profile_array);

                            foreach ($profile_array as $profile)
                            {
                              $formitem[$key] .= "
                                <option value=\"".$profile."\"".($profile == $condbot ? " selected" : "").">".$profile."</option>";
                            }
                          }

                          $formitem[$key] .= "
                              </select>
                            </td>
                          </tr>";
                        }
 
                        $formitem[$key] .= "
                            </table>
                          </div>"; 
                      } 
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['here-you-can-insert-multiple-components'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    elseif ($searchtag == "artcomponent")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview" || $buildview == "inlineview")
                      {
                        $taglink = "<div style=\"all:unset; display:inline-block !important;\"><img onClick=\"hcms_selectComponents('".$hypertagname."','".$id."','".$condbot."','".$mediatype."','".$i."','send');\" src=\"".getthemelocation()."img/edit_compmulti.png\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; margin:0; padding:0; cursor:pointer;\" />".$arttaglink[$artid]."</div>";
                      }
                      elseif ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                      {
                        $add_submitcomp .= "
                        submitMultiComp ('".$hypertagname."_".$artid."_".$elementid."', '".$hypertagname."[".$id."]');";
 
                        $formitem[$key] = "
                          <div class=\"hcmsFormRowLabel ".$hypertagname."_".$artid."_".$elementid."\">
                            <b>".$labelname."</b> ".$arttaglink[$artid]."
                          </div>
                          <div class=\"hcmsFormRowContent ".$hypertagname."_".$artid."_".$elementid."\">
                            <table class=\"hcmsTableNarrow\">
                              <tr>
                                <td style=\"width:150px; vertical-align:top;\">".getescapedtext ($hcms_lang['multiple-component'][$lang], $charset, $lang)." </td>
                                <td>
                                  <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" />
                                  <select id=\"".$hypertagname."_".$artid."_".$elementid."\" name=\"".$hypertagname."_".$artid."_".$elementid."\" size=\"10\" style=\"width:".$fieldwidth."px; height:160px;\" ".$disabled.">";
 
                        if (!empty ($contentbot) && $contentbot != false)
                        {
                          // cut off last delimiter
                          $component = trim ($contentbot, "|");

                          // split component string into array
                          $component_array = array();
 
                          if (substr_count ($component, "|") > 0) $component_array = explode ("|", $component);
                          else $component_array[0] = $component;

                          foreach ($component_array as $comp_entry)
                          {
                            $comp_entry_name = getlocationname ($site, $comp_entry, "comp", "path"); 
                            if (strlen ($comp_entry_name) > 50) $comp_entry_name = "...".substr (substr ($comp_entry_name, -50), strpos (substr ($comp_entry_name, -50), "/"));
                                      
                            $formitem[$key] .= "
                                    <option value=\"".$comp_entry."\">".convertchars ($comp_entry_name, $hcms_lang_codepage[$lang], $charset)."</option>";
                          }
                        }
 
                        $formitem[$key] .= "
                                  </select>
                                </td>";
 
                        if (($buildview == "formedit" || ($buildview == "formmeta" && $infotype == "meta")) && empty ($disabled)) $formitem[$key] .= "
                                <td style=\"padding:2px;\">
                                  <div id=\"".$hypertagname."_".$id."_controls\">
                                    <img onClick=\"moveSelected(document.forms['hcms_formview'].elements['".$hypertagname."_".$artid."_".$elementid."'], false)\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonUp\" src=\"".getthemelocation()."img/button_moveup.png\" alt=\"".getescapedtext ($hcms_lang['move-component-up'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['move-component-up'][$lang], $charset, $lang)."\" /><br />
                                    <img onClick=\"openBrWindowComp(document.forms['hcms_formview'].elements['".$hypertagname."_".$artid."_".$elementid."'], '', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" /><br /> 
                                    <img onClick=\"deleteSelected(document.forms['hcms_formview'].elements['".$hypertagname."_".$artid."_".$elementid."'])\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.png\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" /><br />
                                    <img onClick=\"setSaveType('form_so', '".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_component.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=multi&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&condition=".url_encode($condbot)."&mediatype=".url_encode($mediatype)."', 'post');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_compmulti.png\" alt=\"".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" /><br />
                                    <img onClick=\"moveSelected(document.forms['hcms_formview'].elements['".$hypertagname."_".$artid."_".$elementid."'], true)\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDown\" src=\"".getthemelocation()."img/button_movedown.png\" alt=\"".getescapedtext ($hcms_lang['move-component-down'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['move-component-down'][$lang], $charset, $lang)."\" />
                                  </div>
                                </td>";

                        // personalization/customers profiles only if not DAM
                        if (empty ($mgmt_config[$site]['dam']))
                        {
                          $formitem[$key] .= "
                                </tr>
                                <tr>
                                  <td style=\"width:150px; vertical-align:top;\">".getescapedtext ($hcms_lang['customer-profile'][$lang], $charset, $lang)." </td>
                                  <td style=\"padding-top:3px;\">
                                    <select name=\"condition[".$id."]\" style=\"width:".$fieldwidth."px;\" ".$disabled.">
                                      <option value=\"\">".getescapedtext ($hcms_lang['select'][$lang], $charset, $lang)."</option>";

                          if (sizeof ($profile_array) >= 1)
                          {
                            reset ($profile_array);

                            foreach ($profile_array as $profile)
                            {
                              $formitem[$key] .= "
                                      <option value=\"".$profile."\" ".($profile == $condbot ? "selected=\"selected\"" : "").">".$profile."</option>";
                            }
                          }

                          $formitem[$key] .= "
                                  </select>
                                </td>
                              </tr>";
                        }
 
                        $formitem[$key] .= "
                          </table>
                        </div>";
                      } 
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Article: ".$artid."</b><br />
                              <b>Element: ".$elementid."</b><br />
                              ".getescapedtext ($hcms_lang['here-you-can-insert-multiple-components'][$lang], $charset, $lang)."</span>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    else $taglink = "";
 
                    // insert taglink
                    $repl_start = strpos ($viewstore, $hypertag, $repl_offset);
                    $repl_offset = $repl_start + strlen ($taglink.$hypertag);
                    $viewstore = substr_replace ($viewstore, $taglink, $repl_start, 0);
                    $viewstore_offset = substr ($viewstore, $repl_offset);

                    break;
                  }
                }
              }

              // ------------------------------------- publish ---------------------------------------
              elseif ($buildview == "publish" && $application != "generator")
              {
                if ($onedit != "hidden" && $onpublish != "hidden")
                {
                  // load condition from customer profile
                  if ($condbot != "")
                  {
                    $condition = loadfile ($mgmt_config['abs_path_data']."customer/".$site."/", $condbot.".prof.dat");

                    if ($condition == false)
                    {
                      $errcode = "10102";
                      $error[] = $mgmt_config['today']."|hypercms_tplengine.inc.php|error|".$errcode."|loadfile failed for ".$mgmt_config['abs_path_data']."customer/".$site."/".$condbot.".prof.dat";
                    }
                  }

                  // ------------ single and multicomponent --------------
                  if ($hypertagname == $searchtag."s" || $hypertagname == $searchtag."m")
                  {
                    // dynamic include of components (ASP, JSP, PHP)
                    if ($application != "htm" && $application != "xml" && strtolower ($include) != "static")
                    {
                      if (!isset ($artstatus[$artid]) && (!isset ($artstatus[$artid]) || $artstatus[$artid] == "active" || $artstatus[$artid] == ""))
                      {
                        if ($mgmt_config[$site]['linkengine'] == true)
                        {
                          if (empty ($condition))
                            $component = tpl_insertcomponent ($application, "hypercms_".$container_id, $id);
                          else
                            $component = tpl_persinsertcomponent ($application, $condition, "hypercms_".$container_id, $id);
                        }
                        else
                        {
                          if (empty ($condition))
                            $component = tpl_insertcomponent ($application, $contentbot, "");
                          else
                            $component = tpl_persinsertcomponent ($application, $condition, $contentbot, "");
                        }
                      }
                      elseif ($artstatus[$artid] == "timeswitched")
                      {
                        if ($mgmt_config[$site]['linkengine'] == true)
                        {
                          if (empty ($condition))
                            $component = tpl_tsinsertcomponent ($application, $artdatefrom[$artid], $artdateto[$artid], "hypercms_".$container_id, $id);
                          else
                            $component = tpl_tspersinsertcomponent ($application, $artdatefrom[$artid], $artdateto[$artid], $condition, "hypercms_".$container_id, $id);
                        }
                        else
                        {
                          if (empty ($condition))
                            $component = tpl_tsinsertcomponent ($application, $artdatefrom[$artid], $artdateto[$artid], $contentbot, "");
                          else
                            $component = tpl_tspersinsertcomponent ($application, $artdatefrom[$artid], $artdateto[$artid], $condition, $contentbot, "");
                        }
                      }
                      elseif ($artstatus[$artid] == "inactive")
                      {
                        $component = "";
                      }
                    }
                    // static include of components (standard for HTML, XML, TXT, Generator)
                    else
                    {
                      $contentbot_array = array();
                      $component = "";
 
                      if (!empty ($contentbot))
                      {
                        if (substr_count ($contentbot, "|") > 0)
                        {
                          $contentbot_array = explode ("|", substr ($contentbot, 0, strlen ($contentbot)-1));
                        }
                        else $contentbot_array[0] = $contentbot;
 
                        foreach ($contentbot_array as $complink)
                        {
                          if (strtolower ($include) == "static")
                          {
                            // deconvert path
                            $complink = deconvertpath ($complink, "file"); 
                            $component .= @file_get_contents ($complink); 
                          }
                          else
                          {
                            // deconvert path
                            $complink = deconvertpath ($complink, "url");     
                            $component .= @file_get_contents ($complink);
                          }
                        }
                      }
                    }

                    // insert component-code
                    $viewstore = str_replace ($hypertag, $component, $viewstore);
                  }
                }
                // for use in applications, insert component links
                elseif ($onedit == "hidden" && $onpublish != "hidden")
                {
                  // path type (absolute file path)
                  if ($comppathtype == "file" || $comppathtype == "abs")
                  {
                    // use publish settings
                    if ($buildview == "publish") $temp_root = substr ($publ_config['abs_publ_comp'], 0, strlen ($publ_config['abs_publ_comp']) - 1);
                    // use management settings
                    else $temp_root = substr ($mgmt_config['abs_path_comp'], 0, strlen ($mgmt_config['abs_path_comp']) - 1);

                    // replace the comp variables with the component root
                    $contentbot = str_replace ("%comp%", $temp_root, $contentbot);
                  }
                  // path type (converted location path)
                  elseif ($comppathtype == "location")
                  {
                    $contentbot = convertpath ($site, $contentbot, "comp");
                  }
                  // path type (URL)
                  else
                  {
                    // use publish settings
                    if ($buildview == "publish") $temp_root = substr ($publ_config['url_publ_comp'], 0, strlen ($publ_config['url_publ_comp']) - 1);
                    // use management settings
                    else $temp_root = substr ($mgmt_config['url_path_comp'], 0, strlen ($mgmt_config['url_path_comp']) - 1);

                    // replace the comp variables with the component root
                    $contentbot = str_replace ("%comp%", $temp_root, $contentbot);
                  }

                  // replace template tag with path
                  $viewstore = str_replace ($hypertag, $contentbot, $viewstore);
                }
                else
                {
                  $viewstore = str_replace ($hypertag, "", $viewstore);
                }
              }

              // -------------------------------- include components -------------------------------
              if ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview" || ($buildview == "publish" && $application == "generator"))
              {
                if ($onedit != "hidden")
                {
                  // ------------ single component --------------
                  if ($hypertagname == $searchtag."s")
                  {
                    $component_link = $contentbot;

                    if ($onpublish != "hidden" && $component_link != "")
                    {
                      //$component_link = deconvertpath ($component_link, "file");
                      $component_file = getobject ($component_link);
                      $component_location = getlocation ($component_link);
                      $container_collection = "live";
                      $container_buffer = $container;
                      $container = null;
 
                      if ($buildview == "publish" && $application == "generator") $component_view = buildview ($site, $component_location, $component_file, $user, "publish", "no", "", "", "", false);
                      else $component_view = buildview ($site, $component_location, $component_file, $user, "preview", "no");
 
                      $container = $container_buffer;
                      $component = $component_view['view'];
 
                      // if template is a XML-document escape all < and > and add <br />
                      if ($application == "xml" && ($buildview == "template" || $buildview == "cmsview" || $buildview == 'inlineview'))
                      {
                        $component = str_replace ("<![CDATA[", "&lt;![CDATA[", $component);
                        $component = str_replace ("]]>", "]]&gt;", $component);
                        $component = str_replace ("<", "<span style=\"color:#0000FF; font-size:11px; font-family:Arial, Helvetica, sans-serif;\">&lt;", $component);
                        $component = str_replace (">", "&gt;</span>", $component);
                        $component = str_replace ("\n", "<br />", $component);
                      }
                    }
                    else $component = "";
 
                    $viewstore = str_replace ($hypertag, $component, $viewstore);
                  }
                  // ------------ multi component --------------
                  elseif ($hypertagname == $searchtag."m")
                  {
                    if (!empty ($contentbot))
                    {
                      if ($contentbot[strlen ($contentbot) - 1] == "|") $contentbot = substr ($contentbot, 0, strlen ($contentbot) - 1);
                      $contentbot_array = explode ("|", $contentbot);

                      $multicomponent = "";
                      $scriptarray .= "item['".$id."'] = new Array();\n";
                      $i = 0;

                      foreach ($contentbot_array as $component_link)
                      {
                        if ($component_link != "")
                        {
                          if (($buildview == "cmsview" || $buildview == "inlineview") && $onedit != "hidden" && $icon != "hidden")
                          {
                            $taglink = "<div style=\"all:unset; display:inline-block !important;\"><img onClick=\"hcms_openWindowComp('', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=yes', '".str_replace ("%comp%", "", $component_link)."');\"  src=\"".getthemelocation()."img/edit_edit.png\" alt=\"".$hcms_lang['edit'][$lang]."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; margin:0; padding:0; cursor:pointer;\" /><img onClick=\"hcms_selectComponents('".$hypertagname."','".$id."','".$condbot."','".$mediatype."','".$i."','delete');\" src=\"".getthemelocation()."img/edit_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; margin:0; padding:0; cursor:pointer;\" /><img onClick=\"hcms_selectComponents('".$hypertagname."','".$id."','".$condbot."','".$mediatype."','".$i."','moveup');\"  src=\"".getthemelocation()."img/edit_moveup.png\" alt=\"".getescapedtext ($hcms_lang['move-component-up'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['move-component-up'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; margin:0; padding:0; cursor:pointer;\" /><img onClick=\"hcms_selectComponents('".$hypertagname."','".$id."','".$condbot."','".$mediatype."','".$i."','movedown');\" src=\"".getthemelocation()."img/edit_movedown.png\" alt=\"".getescapedtext ($hcms_lang['move-component-down'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['move-component-down'][$lang], $charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; margin:0; padding:0; cursor:pointer;\" /></div>\n";

                            $scriptarray .= "item['".$id."'][".$i."] = '".$component_link."';\n";
                            $i++;
                          }
                          else $taglink = "";
 
                          if ($onpublish != "hidden" && $component_link != "")
                          {
                            // $component_link = deconvertpath ($component_link, "file");
                            $component_file = getobject ($component_link);
                            $component_location = getlocation ($component_link);
                            $container_collection = "live";
                            $container_buffer = $container;
                            $container = null;
 
                            // render component
                            if ($buildview == "publish" && $application == "generator") $component_view = buildview ($site, $component_location, $component_file, $user, "publish", "no", "", "", "", false);
                            else $component_view = buildview ($site, $component_location, $component_file, $user, "preview", "no");
 
                            $container = $container_buffer;
                            $component = $component_view['view'];

                            // if template is a XML-document escape all < and > and add <br />
                            if ($application == "xml" && ($buildview == "template" || $buildview == "cmsview" || $buildview == 'inlineview'))
                            {
                              $component = str_replace ("<![CDATA[", "&lt;![CDATA[", $component);
                              $component = str_replace ("]]>", "]]&gt;", $component);
                              $component = str_replace ("<", "<span style=\"color:#0000FF; font-size:11px; font-family:Arial, Helvetica, sans-serif;\">&lt;", $component);
                              $component = str_replace (">", "&gt;</span>", $component);
                              $component = str_replace ("\n", "<br />", $component);
                            }
 
                            // add buttons
                            $multicomponent .= $taglink.$component;
                          } 
                        }
                      }
                    }
                    else $multicomponent = "";
 
                    // insert component into view store
                    $viewstore = str_replace ($hypertag, $multicomponent, $viewstore);
                  }
                }
                elseif ($onedit == "hidden" && $onpublish != "hidden")
                {
                  // path type (absolute file path)
                  if ($comppathtype == "file" || $comppathtype == "abs")
                  {
                     // use publish settings
                    if ($buildview == "publish") $temp_root = substr ($publ_config['abs_publ_comp'], 0, strlen ($publ_config['abs_publ_comp']) - 1);
                    // use management settings
                    else $temp_root = substr ($mgmt_config['abs_path_comp'], 0, strlen ($mgmt_config['abs_path_comp']) - 1);

                    // replace the comp variables with the component root
                    $contentbot = str_replace ("%comp%", $temp_root, $contentbot);
                  }
                  // path type (converted location path)
                  elseif ($comppathtype == "location")
                  {
                    $contentbot = convertpath ($site, $contentbot, "comp");
                  }
                  // path type (URL)
                  else
                  {
                     // use publish settings
                     if ($buildview == "publish") $temp_root = substr ($publ_config['url_publ_comp'], 0, strlen ($publ_config['url_publ_comp']) - 1);
                     // use management settings
                     else $temp_root = substr ($mgmt_config['url_path_comp'], 0, strlen ($mgmt_config['url_path_comp']) - 1);

                    // replace the comp variables with the component root
                    $contentbot = str_replace ("%comp%", $temp_root, $contentbot);
                  }

                  // replace template tag with path
                  $viewstore = str_replace ($hypertag, $contentbot, $viewstore);
                }
                else
                {
                  $viewstore = str_replace ($hypertag, "", $viewstore);
                }
              }
              elseif ($buildview == "template")
              {
                // ------------ single component --------------
                if ($hypertagname == $searchtag."s")
                {
                  $viewstore = str_replace ($hypertag, "", $viewstore);
                }
                // ------------ multi component --------------
                elseif ($hypertagname == $searchtag."m")
                {
                  $viewstore = str_replace ($hypertag, "", $viewstore);
                }
              }
            }
          }
        }
      }

      // =================================================== geo location ===================================================

      // create view for link content
      $searchtag = "geolocation";
      $hypertagname = "";
      $infotype = "";
      $label = "";
      $onpublish = "";
      $onedit = "";

      // get all hyperCMS tags
      $hypertag_array = gethypertag ($viewstore, $searchtag, 0);

      if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
      {
        $tagid = 0;

        reset ($hypertag_array);

        // only the first hyperCMS tag found in template is valid
        foreach ($hypertag_array as $key => $hypertag)
        {
          // get tag name
          $hypertagname = gethypertagname ($hypertag);

          // get tag visibility on publish
          $onpublish = getattribute (strtolower ($hypertag), "onpublish");

          // get tag visibility on edit
          $onedit = getattribute (strtolower ($hypertag), "onedit");

          // get type of content
          $infotype = getattribute (strtolower ($hypertag), "infotype");
          if ($infotype == "meta") $show_meta = true;

          // get label
          $label = getattribute ($hypertag, "label");
 
          if ($label == "") $label = getescapedtext ($hcms_lang['geo-location'][$lang], $charset, $lang);

          // get readonly attribute
          $readonly = getattribute (strtolower ($hypertag), "readonly");

          if ($buildview != "formlock")
          {
            if ($readonly != false) $disabled = " disabled=\"disabled\"";
            else $disabled = "";
          }

          // get group access
          $groupaccess = getattribute ($hypertag, "groups");
          $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);

          // get content
          if ($buildview != "template" && $groupaccess == true && $onpublish != "hidden")
          {
            $temp = rdbms_getmedia ($container_id);

            if (!empty ($temp['latitude']) && !empty ($temp['longitude'])) $contentbot = $temp['latitude'].", ".$temp['longitude'];
            else $contentbot = "";
          }

          // in order to access the content via JS
          if ($groupaccess != true && ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock"))
          {
            $formitem[$key] = "
            <input type=\"hidden\" id=\"".$hypertagname_href[$id]."\" value=\"".$contentbot."\" />";
          }

          // get content
          if ($buildview != "template" && $groupaccess == true && $onedit != "hidden")
          {
            $taglink = "";

            // init map and place marker on map
            if ($contentbot != "") $add_onload .= "
    initMap('".$contentbot."');";
            else $add_onload .= "
    initMap();";

            if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
            {
              // correct map width for mobile devices
              if ($maxwidth > 0 && $maxwidth < 600) $mapwidth = $maxwidth;
              else $mapwidth = 600;

              // form map element
              $formitem[$key] = "
                <div class=\"hcmsFormRowLabel ".$hypertagname."\">
                  <b>".$label."</b>
                </div>
                <div class=\"hcmsFormRowContent ".$hypertagname."\">
                  <input type=\"text\" id=\"pac-input\" class=\"hcmsMapsControls\" placeholder=\"".getescapedtext ($hcms_lang['search'][$lang], $charset, $lang)."\" ".$disabled.">
                  <div id=\"map\" style=\"box-sizing:border-box; width:".$mapwidth."px; height:360px; margin:0; border:1px solid grey;\"></div>
                  <input type=\"text\" id=\"".$hypertagname."\" name=\"".$hypertagname."\" style=\"box-sizing:border-box; width:".$mapwidth."px; margin:0;\" value=\"".$contentbot."\" ".$disabled." />
                </div>";
            }
          }
          elseif ($buildview == "template" && $onedit != "hidden")
          {
            $taglink = "
            <table style=\"width:200px; padding:4px; border:1px solid #000000; background-color:#FFFFFF;\">
              <tr>
                <td>
                  <span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>Element: ".getescapedtext ($hcms_lang['geo-location'][$lang], $charset, $lang)."</b></span>
                </td>
              </tr>
            </table>";
          }
          else $taglink = "";

          // publish content
          if (($buildview == "publish" || $buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview") && $onpublish != "hidden")
          {
            $viewstore = str_replace ($hypertag, $contentbot, $viewstore);
          }
          else
          {
            $viewstore = str_replace ($hypertag, $taglink, $viewstore);
          }
        }
      }

      // =================================================== face detection ===================================================

      $faces_json = "''";

      if (is_facerecognition ("sys") || is_annotation ())
      {
        // get content
        $bufferarray = selectcontent ($contentdata, "<text>", "<text_id>", "Faces-JSON");

        if (!empty ($bufferarray[0]))
        {
          $bufferarray = getcontent ($bufferarray[0], "<textcontent>", true);
          if (!empty ($bufferarray[0])) $faces_json = $bufferarray[0];

          // encode script code
          $faces_json = scriptcode_encode ($faces_json);

          // set empty string instead of JSON string
          if (trim ($faces_json) == "") $faces_json = "''";
        }
      }

      // =========================================== JavaScript code ============================================

      $js_tpl_code = "";

      // only for form views
      if (preg_match ("/\[JavaScript:scriptbegin/i", $viewstore))
      {
        // replace hyperCMS script code
        while (substr_count (strtolower($viewstore), "[javascript:scriptbegin") > 0)
        {
          $jstagstart = strpos (strtolower($viewstore), "[javascript:scriptbegin");
          $jstagend = strpos (strtolower($viewstore), "scriptend]", $jstagstart + strlen ("[javascript:scriptbegin")) + strlen ("scriptend]");
          $jstag = substr ($viewstore, $jstagstart, $jstagend - $jstagstart);

          // remove JS code
          $viewstore = str_replace ($jstag, "", $viewstore);

          // assign code
          if (trim ($jstag) != "" && ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock"))
          {
            // remove tags
            $jstag = str_ireplace ("[javascript:scriptbegin", "", $jstag);
            $jstag = str_ireplace ("scriptend]", "", $jstag);
            $js_tpl_code .= "\n".$jstag;
          }
        }
      }

      // ================================ WYSIWYG Views ================================

      if ($buildview != "formedit" && $buildview != "formmeta" && $buildview != "formlock")
      {
        // ================================ javascript for control frame call ================================
        if ($ctrlreload == "yes")
        {
          $bodytag_controlreload = "if (parent.frames['controlFrame']) parent.frames['controlFrame'].location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."control_content_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."'; ";
        }
        else $bodytag_controlreload = "";

        // ==================================== scripts in template view =====================================
        if ($buildview == "template" && !empty ($application) && tpl_tagbegin ($application) != "")
        {
          // replace hyperCMS script code
          while (substr_count (strtolower($viewstore), "[hypercms:scriptbegin") > 0)
          {
            $apptagstart = strpos (strtolower($viewstore), "[hypercms:scriptbegin");
            $apptagend = strpos (strtolower($viewstore), "scriptend]", $apptagstart + strlen ("[hypercms:scriptbegin")) + strlen ("scriptend]");
            $apptag = substr ($viewstore, $apptagstart, $apptagend - $apptagstart);
            $viewstore = str_replace ($apptag, "", $viewstore);

            /* old version
            $htmltag = gethtmltag ($viewstore, $apptag);

            if ($htmltag == false) $viewstore = str_replace ($apptag, "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>".$hcms_lang['onhcms_lang'][$lang]."</b></span>\n</td>\n  </tr>\n</table>\n", $viewstore);
            else $viewstore = str_replace ($htmltag, "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>".$hcms_lang['server-side-script-code-included'][$lang]."</b></span>\n</td>\n  </tr>\n</table>\n".str_replace ($apptag, "", $htmltag), $viewstore);
            */
          }

          // replace application script code
          while (substr_count ($viewstore, tpl_tagbegin($application)) > 0)
          {
            $apptagstart = strpos ($viewstore, tpl_tagbegin($application));
            $apptagend = strpos ($viewstore, tpl_tagend($application), $apptagstart + strlen (tpl_tagbegin($application))) + strlen (tpl_tagend($application));
            $apptag = substr ($viewstore, $apptagstart, $apptagend - $apptagstart);
            $viewstore = str_replace ($apptag, "", $viewstore);

            /* old version
            $htmltag = gethtmltag ($viewstore, $apptag);

            if ($htmltag == false) $viewstore = str_replace ($apptag, "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>".$hcms_lang['onhcms_lang'][$lang]."</b></span>\n</td>\n  </tr>\n</table>\n", $viewstore);
            else $viewstore = str_replace ($htmltag, "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<span style=\"font-family:Arial, Helvetica, sans-serif; font-size:10px; color:#000000;\"><b>".$hcms_lang['server-side-script-code-included'][$lang]."</b></span>\n</td>\n  </tr>\n</table>\n".str_replace ($apptag, "", $htmltag), $viewstore);
            */
          }
        }

        // remove line breaks
        $viewstore = trim ($viewstore);

        // ======================================== execute php script code =========================================

        $tpl_livelink = "";
        $tpl_linkindex = "";

        if ($execute_code == true)
        {
          // escape xml-declaration in templates using XML-code
          // only if part of the source code and not included as a string in a script
          // $regex = "/\"\\s".preg_quote ("<?xml")."\\s/";
          // if (!preg_match ($regex, $viewstore))

          if (!stripos ($viewstore, "\"<?xml ") && !stripos ($viewstore, "'<?xml "))
          {
            $xmldeclaration = gethtmltag ($viewstore, "?xml");

            if (!empty ($xmldeclaration))
            {
              $xmldeclaration_esc = str_replace ("<?", "[hypercms:xmlbegin", $xmldeclaration);
              $xmldeclaration_esc = str_replace ("?>", "xmlend]", $xmldeclaration_esc);
              $viewstore = str_replace ($xmldeclaration, $xmldeclaration_esc, $viewstore);
            }
          }

          if ($application != "htm" && $application != "xml")
          {
            // on Win32 OS the components will be loaded using file system access (not remotely via HTTP)
            // on UNIX all pages and components will load the livelink function
            // components will be executed remotely and only the output will be inlcuded in the page

            // set livelink function
            $tpl_livelink = tpl_livelink ($application, $publ_config['abs_publ_config'], $site);

            // set configuration and link index
            if ($mgmt_config[$site]['linkengine'] == true && isset ($container_id))
            {
              $tpl_linkindex = tpl_linkindex ($application, $publ_config['abs_publ_config'], $site, $container_id);
            }
          }

          // CMS/Inline/Preview
          if ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview")
          {
            // execute hyperCMS scripts or PHP code
            if ($mgmt_config['application']['php'] == true && ($application == "php" || preg_match ("/\[hypercms:scriptbegin/i", $viewstore) || strtoupper ($mgmt_config['os_cms']) == "WIN"))
            {
              // transform
              $viewstore = str_ireplace ("[hypercms:scriptbegin", "<?php", $viewstore);
              $viewstore = str_ireplace ("scriptend]", "?>", $viewstore);

              // change directory to location to have correct hrefs
              $viewstore =  tpl_globals_extended ("php", $mgmt_config['abs_path_cms'], $mgmt_config['abs_path_rep'], $site, $location).$viewstore;

              // save pageview in temp
              $test = savefile ($mgmt_config['abs_path_view'], $unique_id.".pageview.php", $viewstore);

              $viewstore_buffer = $viewstore;

              // execute code
              if ($test == true)
              {
                // add session parameter
                if (session_id() != "") $pageview_parameter = "?PHPSESSID=".session_id();
                else $pageview_parameter = "?hcms_session['hcms']=void";

                // add language setting from session
                if (!empty ($_SESSION[$language_sessionvar])) $pageview_parameter .= "&hcms_session[".$language_sessionvar."]=".url_encode ($_SESSION[$language_sessionvar]);

                // close session file
                session_write_close();

                // execute code
                $viewstore = file_get_contents ($mgmt_config['url_path_view'].$unique_id.".pageview.php".$pageview_parameter);

                // reopen session file
                session_start();

                // error handling
                $viewstore = errorhandler ($viewstore_buffer, $viewstore, $unique_id.".pageview.php");

                deletefile ($mgmt_config['abs_path_view'], $unique_id.".pageview.php", 0);
              }
            }

            // execute application code (non PHP)
            if (!empty ($application) && $application != "php" && !empty ($mgmt_config['application'][$application]) && tpl_tagbegin ($application) != "" && @substr_count ($viewstore, tpl_tagbegin ($application)) > 0)
            {
              // change directory to location to have correct hrefs
              $viewstore =  tpl_globals_extended ($application, $mgmt_config['abs_path_cms'], $mgmt_config['abs_path_rep'], $site, $location).$viewstore;

              // save pageview in temp
              $test = savefile ($mgmt_config['abs_path_view'], $unique_id.".pageview.".$templateext, $viewstore);

              $viewstore_buffer = $viewstore;

              // execute code
              if ($test == true)
              {
                // add session parameter
                if (session_id() != "") $pageview_parameter = "?PHPSESSID=".session_id();
                else $pageview_parameter = "?hcms_session['hcms']=void";

                // add language setting from session
                if (!empty ($_SESSION[$language_sessionvar])) $pageview_parameter .= "&hcms_session[".$language_sessionvar."]=".url_encode ($_SESSION[$language_sessionvar]);
 
                // close session file
                session_write_close();

                $viewstore = @file_get_contents ($mgmt_config['url_path_view'].$unique_id.".pageview.".$templateext.$pageview_parameter);

                // reopen session file
                session_start();

                // error handling
                $viewstore = errorhandler ($viewstore_buffer, $viewstore, $unique_id.".pageview.".$templateext);

                deletefile ($mgmt_config['abs_path_view'], $unique_id.".pageview.".$templateext, 0);
              }
            }
          }
          // Publish
          elseif (!empty ($mgmt_config['application']['php']) && ($buildview == "publish" || $buildview == "unpublish") && (preg_match ("/\[hypercms:scriptbegin/i", $viewstore) || strtoupper ($mgmt_config['os_cms']) == "WIN"))
          {
            // execute hyperCMS scripts for preprocessing
            // transform
            $viewstore = str_ireplace (tpl_tagbegin ($application), "[hyperCMS:skip", $viewstore);
            $viewstore = str_ireplace (tpl_tagend ($application), "skip]", $viewstore);

            $viewstore = str_ireplace ("[hyperCMS:scriptbegin", "<?php", $viewstore);
            $viewstore = str_ireplace ("scriptend]", "?>", $viewstore);

            // in Generator mode we are saving the viewstore, executing it and saving the output to the generated file
            if ($application == "generator")
            {
              // when we are using the generator the generated viewstore will be saved into a file
              if (empty ($mediafile))
              {
                $mediafile_info = getfileinfo ($site, $page, "comp");
                $mediafile = $mediafile_info['filename']."_hcm".$container_id.$mediafile_info['ext'];
              }

              $viewstore = tpl_globals_extended ("php", $mgmt_config['abs_path_cms'], $mgmt_config['abs_path_rep'], $site, $location).$viewstore;

              // save pageview in temp
              $result_save = savefile ($mgmt_config['abs_path_view'], $unique_id.".generate.php", $viewstore);

              $viewstore_buffer = $viewstore;

              if ($result_save == true)
              {
                // add user name from session
                if (session_id() != "") $pageview_parameter = "?PHPSESSID=".session_id();
                else $pageview_parameter = "?hcms_session['hcms']=void";

                // add language setting from session
                if (!empty ($_SESSION[$language_sessionvar])) $pageview_parameter .= "&hcms_session[".$language_sessionvar."]=".url_encode ($_SESSION[$language_sessionvar]);

                // close session file
                session_write_close();

                // execute code of generator (e.g. create a PDF file)
                $viewstore_save = @file_get_contents ($mgmt_config['url_path_view'].$unique_id.".generate.php".$pageview_parameter);

                // reopen session file
                session_start();

                // error handling
                $viewstore = errorhandler ($viewstore_buffer, $viewstore_save, $unique_id.".generate.php");

                deletefile ($mgmt_config['abs_path_view'], $unique_id.".generate.php", 0);

                // creation of the file was successful, save it to the media repository
                if ($viewstore == $viewstore_save)
                {
                  $mediadir = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";

                  // save media file
                  $result_save = savefile ($mediadir, $mediafile, $viewstore);

                  // create thumbnail
                  createmedia ($site, $mediadir, $mediadir, $mediafile, "", "thumbnail", true, true);

                  // remote client
                  remoteclient ("save", "abs_path_media", $site, getmedialocation ($site, $mediafile, "abs_path_media").$site."/", "", $mediafile, "");

                  $errorview = "";
                }
                // on error
                else
                {
                  $errorview = "\n\n".$viewstore;

                  // clean view for event log
                  $errview = strip_tags ($viewstore);
                  $errview = substr ($errview, 0, strpos (trim ($errview, "\n"), "\n"));

                  $errcode = "10201";
                  $error[] = $mgmt_config['today']."|hypercms_tplengine.inc.php|error|".$errcode."|Generator failed to render file ".$mediafile." with error: ".$errview;
                }
              }

              // set viewstore
              $viewstore = $errorview;
            }
            // standard publishing of page and component objects
            else
            {
              // change directory to location to have correct hrefs
              $viewstore = tpl_globals_extended ("php", $mgmt_config['abs_path_cms'], $mgmt_config['abs_path_rep'], $site, $location).$viewstore;

              // save pageview in temp
              $result_save = savefile ($mgmt_config['abs_path_view'], $unique_id.".pageview.php", $viewstore);

              $viewstore_buffer = $viewstore;

              // execute php code
              if ($result_save == true)
              {
                // add user name from session
                if (session_id() != "") $pageview_parameter = "?PHPSESSID=".session_id();
                else $pageview_parameter = "?hcms_session['hcms']=void";

                // add language setting from session
                if (!empty ($_SESSION[$language_sessionvar])) $pageview_parameter .= "&hcms_session[".$language_sessionvar."]=".url_encode ($_SESSION[$language_sessionvar]);

                // close session file
                session_write_close();

                // execute code
                $viewstore = @file_get_contents ($mgmt_config['url_path_view'].$unique_id.".pageview.php".$pageview_parameter);

                // reopen session file
                session_start();

                // error handling
                $viewstore = errorhandler ($viewstore_buffer, $viewstore, $unique_id.".pageview.php");

                deletefile ($mgmt_config['abs_path_view'], $unique_id.".pageview.php", 0);
              }
            }

            // transform back
            $viewstore = str_replace ("[hyperCMS:skip", tpl_tagbegin ($application), $viewstore);
            $viewstore = str_replace ("skip]", tpl_tagend ($application), $viewstore);
          }

          // unescape xml-declaration
          if (!empty ($xmldeclaration))
          {
            $viewstore = str_replace ("[hypercms:xmlbegin", "<?", $viewstore);
            $viewstore = str_replace ("xmlend]", "?>", $viewstore);
          }
        }

        // if no error occured
        if (strpos ("_".$viewstore, "<!-- hyperCMS:ErrorCodeBegin -->") < 1)
        {
          // reload content container in case it has been manipulated by the template script
          if ($container_collection != "live" && ($buildview == "publish" || $buildview == "unpublish" || $buildview == "cmsview" || $buildview == "inlineview"))
          {
            $contentdata = loadcontainer ($contentfile, "work", $user);
          }

          // ======================================== define CSS for components =========================================
          $line_css = "";

          $hypertag_array = gethypertag ($viewstore, "compstylesheet", 0);

          if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
          {
            foreach ($hypertag_array as $hypertag)
            {
              $css_array[] = getattribute ($hypertag, "file");
              $viewstore = str_replace ($hypertag, "", $viewstore);
            }

            if ($css_array != false && sizeof ($css_array) > 0)
            {
              foreach ($css_array as $css)
              {
                if ($css != "") $line_css .= "<link rel=\"stylesheet\" hypercms_href=\"".$css."\" />\n";
              }
            }
          }

          // if no CSS has been defined use system CSS
          if (empty ($line_css)) $line_css .= "
    <link rel=\"stylesheet\" hypercms_href=\"".getthemelocation()."css/main.css\" />
    <link rel=\"stylesheet\" hypercms_href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />";

          // ======================================== insert code for component templates =======================================
          if ($buildview == "template")
          {
            // get HTML body-tag
            if (@substr_count (strtolower ($viewstore), "<body") == 0 && @substr_count (strtolower ($viewstore), ":body") == 0)
            {
              $viewstore = "<!DOCTYPE html>
  <html>
  <head>
  <title>hyperCMS</title>
  <meta charset=\"".$charset."\" />
  ".$line_css."
  </head>
  <body class=\"hcmsWorkplaceGeneric\">
  <table class=\"hcmsTemplateField\">
    <tr>
      <td>".$viewstore."</td>
    </tr>
  </table>
  </body>
  </html>";
            }
          }

          // ============================ insert buttons after body tag and add body tag preload =================================

          if ($buildview == "cmsview" || $buildview == "inlineview" || ($buildview == "preview" && $ctrlreload == "yes"))
          {
            // define buttons for formedit in cmsview or inlineview
            if ($objectview != "cmsview" && $objectview != "inlineview") $headstoreform = "<img src=\"".getthemelocation()."img/edit_form.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."page_view.php?view=formedit&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."';\" style=\"all:unset; display:inline !important; height:32px; padding:0; margin:0; border:0; vertical-align:top; text-align:left;\" alt=\"".getescapedtext ($hcms_lang['form-view'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['form-view'][$lang], $charset, $lang)."\" />";
            else $headstoreform = "";

            // check if html tag exists in viewstore (if not it will be treated as a component)
            if (($buildview == "cmsview" || $buildview == "inlineview") && !empty ($show_meta))
            {
              $headstoremeta = "<img src=\"".getthemelocation()."img/edit_head.png\" onclick=\"location.hypercms_href='".cleandomain ($mgmt_config['url_path_cms'])."page_view.php?view=formmeta&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&contenttype=".url_encode($contenttype)."';\" style=\"all:unset; display:inline !important; height:32px; padding:0; margin:0; border:0; vertical-align:top; text-align:left; cursor:pointer;\" alt=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" />";
            }

            // scriptcode
            $scriptcode = "
    <script src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/main.min.js\" type=\"text/javascript\"></script>
    <script type=\"text/javascript\">
    ".@$bodytag_controlreload."
    ".@$bodytag_popup."
    ".@$bodytag_selectlang."
    </script>\n";

            // get body-tag
            if (@substr_count (strtolower ($viewstore), "<body") > 0 || @substr_count (strtolower ($viewstore), ":body") > 0)
            {
              if (@substr_count (strtolower ($viewstore), "<body") > 0) $bodytagold = gethtmltag ($viewstore, "<body");
              elseif (@substr_count (strtolower ($viewstore), ":body") > 0) $bodytagold = gethtmltag ($viewstore, ":body");

              // include JS/CSS code for rich text editor and date picker
              if ($buildview == "inlineview")
              {
                $scriptcode .= showinlineeditor_head ($lang).showinlinedatepicker_head ();
              }

              // drag button
              if ($buildview != "preview" && ($headstoremeta != "" || $headstoreform != "" || $headstoreview != "" || $headstorelang != ""))
              {
                $headstore = "<div id=\"meta_info\" style=\"all:unset; position:fixed; padding:0; margin:0; z-index:99999; left:4px; top:4px; border:0; background:none; visibility:visible;\"><img src=\"".getthemelocation()."img/edit_drag.png\" style=\"all:unset; display:inline !important; width:32px; height:32px; padding:0; margin:0; border:0; vertical-align:top; text-align:left; cursor:pointer;\" alt=\"".getescapedtext ($hcms_lang['drag'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['drag'][$lang], $charset, $lang)."\" id=\"meta_mover\"/>".$headstoremeta.$headstoreform.$headstoreview.$headstorelang."<script type=\"text/javascript\">hcms_dragLayers(document.getElementById('meta_mover'), document.getElementById('meta_info'));</script></div>";
              }
            }
            // no body-tag available
            else
            {
              $viewstore_new = "<!DOCTYPE html>
  <html>
  <head>
  <title>hyperCMS</title>
  <meta charset=\"".$charset."\" />
              ".$line_css;

              // include JS/CSS code for rich text editor and date picker
              if ($buildview == "inlineview")
              {
                $viewstore_new .= showinlineeditor_head ($lang).showinlinedatepicker_head ();
              }

              $viewstore_new .= "
  </head>
  <body class=\"hcmsWorkplaceGeneric\">\n";

              if ($buildview != "preview") $viewstore_new .= "<div id=\"meta_info\" style=\"position:fixed; padding:0; margin:0; z-index:99999; left:4px; top:4px; border:0; background:none; visibility:visible;\"><img src=\"".getthemelocation()."img/edit_drag.png\" style=\"all:unset; display:inline !important; width:32px; height:32px; padding:0; margin:0; border:0; vertical-align:top; text-align:left;\" alt=\"".getescapedtext ($hcms_lang['drag'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['drag'][$lang], $charset, $lang)."\" id=\"meta_mover\"/>".$headstoremeta.$headstoreform.$headstoreview.$headstorelang."<script type=\"text/javascript\">hcms_dragLayers(document.getElementById('meta_mover'), document.getElementById('meta_info'));</script></div><div style=\"width:100%; height:38px; display:block;\">&nbsp;</div>";

              $viewstore_new .= $viewstore."\n</body>\n</html>";

              $viewstore = $viewstore_new;
              unset ($viewstore_new);

              // define body tag
              $bodytagold = "<body class=\"hcmsWorkplaceGeneric\">"; 
            }

            // deprecated since version 9.0.2: $bodytagnew = str_ireplace ("onload", "lockonload", $bodytagold);
            $bodytagnew = $bodytagold;

            // insert onload commands
            if (!empty ($add_onload))
            {
              $onload_attr = getattribute ($bodytagnew, "onload");

              if (empty ($onload_attr))
              {
                $bodytagnew = str_replace (">", " onload=\"".$add_onload."\">", $bodytagnew);
              }
              else
              {
                $onload_attr_new = substr ($onload_attr, 0, -1)." ".$add_onload.substr ($onload_attr, -1);
                $bodytagnew = str_replace ($onload_attr, $onload_attr_new, $bodytagnew);
              }
            }

            // form for multiple component manipulation in cmsview
            if ($buildview != "preview") $bodytagnew = $bodytagnew."
    <div style=\"display:none;\">
    <form name=\"hcms_result\" action=\"".cleandomain ($mgmt_config['url_path_cms'])."service/savecontent.php\" method=\"post\">
      <input type=\"hidden\" name=\"site\" value=\"".$site."\" />
      <input type=\"hidden\" name=\"cat\" value=\"".$cat."\" />
      <input type=\"hidden\" name=\"compcat\" value=\"multi\" />
      <input type=\"hidden\" name=\"location\" value=\"".$location_esc."\" />
      <input type=\"hidden\" name=\"page\" value=\"".$page."\" />
      <input type=\"hidden\" name=\"contentfile\" value=\"".$contentfile."\" />
      <input type=\"hidden\" name=\"db_connect\" value=\"".$db_connect."\" />
      <input type=\"hidden\" name=\"id\" value=\"\" />
      <input type=\"hidden\" name=\"tagname\" value=\"\" />
      <input type=\"hidden\" name=\"component\" value=\"\" />
      <input type=\"hidden\" name=\"condition\" value=\"\" />
      <input type=\"hidden\" name=\"mediatype\" value=\"\" />
      <input type=\"hidden\" name=\"token\" value=\"".$token."\" />
    </form>
    </div>
    <div style=\"margin:4px; padding:0; border:0; background:none; visibility:visible;\">".$headstore."</div>\n";

            // javascript code
            if ($buildview != "preview") $scriptcode .= "<script src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/main.min.js\" type=\"text/javascript\"></script>
    <script type=\"text/javascript\">
    function hcms_openWindowComp (winName, features, theURL)
    {
      if (theURL != '')
      {
        if (theURL.indexOf('://') == -1)
        {
          var position1 = theURL.indexOf('/');
          var position2 = theURL.lastIndexOf('/');
          var location_comp = \"%comp%/\" + theURL.substring (position1+1, position2+1);

          var location_site = theURL.substring (position1+1, theURL.length-position1);
          location_site = location_site.substring(0, location_site.indexOf('/'));

          var page_comp = theURL.substr (position2+1, theURL.length);
          theURL = '".cleandomain ($mgmt_config['url_path_cms'])."frameset_content.php?ctrlreload=yes&cat=comp&site=' + encodeURIComponent(location_site) + '&location=' + encodeURIComponent(location_comp) + '&page=' + encodeURIComponent(page_comp) + '&user=".url_encode($user)."';

          hcms_openWindow (theURL, winName, features, ".windowwidth ("object").", ".windowheight ("object").");
        }
      }
      else alert (hcms_entity_decode('".getescapedtext ($hcms_lang['no-component-selected'][$lang], $charset, $lang)."'));
    }

    var item = new Array();
    ".$scriptarray."

    function hcms_selectComponents (tagname, id, condition, mediatype, pos, type)
    {
      var component_serialized = '';
      var component_current = '';
      var changes = false;
      var i = 0;

      if (tagname.indexOf('art') == 0) art = 'art';
      else art = '';

      if (item[id])
      {
        while (i < item[id].length)
        {
          component_current = component_current + item[id][i] + '|';
          i++;
        }
      }

      i = 0;

      if (type != 'send')
      {
        if (item[id])
        {
          while (i < item[id].length)
          {
            if (type == 'delete' && i == pos)
            {
              changes = true;
            }
            else if (pos > 0 && type == 'moveup' && i+1 == pos)
            {
              component_serialized = component_serialized + item[id][pos] + '|' + item[id][i] + '|';
              changes = true;
              i++;
            }
            else if (pos < item[id].length-1 && type == 'movedown' && i == pos)
            {
              component_serialized = component_serialized + item[id][i+1] + '|' + item[id][pos] + '|';
              changes = true;
              i++;
            }	
            else
            {
              component_serialized = component_serialized + item[id][i] + '|';
            }

            i++;
          }
        }

        document.forms['hcms_result'].attributes['action'].value = 'service/savecontent.php';
      }
      else if (type == 'send')
      {
        component_serialized = component_current;
        document.forms['hcms_result'].attributes['action'].value = 'frameset_edit_component.php';
        changes = true;
      }

      if (changes == true)
      {
        document.forms['hcms_result'].elements['id'].value = id;
        document.forms['hcms_result'].elements['tagname'].value = tagname;
        document.forms['hcms_result'].elements['component'].value = component_serialized;
        document.forms['hcms_result'].elements['condition'].value = condition;
        document.forms['hcms_result'].elements['mediatype'].value = mediatype;

        if (type != 'send')
        {
          document.forms['hcms_result'].elements['component'].name = art + 'component[' + id + ']';
          document.forms['hcms_result'].elements['condition'].name = art + 'condition[' + id + ']';
        }

        document.forms['hcms_result'].submit();
      }

      return true;
    }
    </script>
    ";
          }

          // =========================================== inject script code ==============================================
          if (isset ($scriptcode) && $scriptcode != "")
          {
            // include javascript
            $vendor = "
    <meta name=\"keyword\" content=\"hyper Content & Digital Asset Management Server (http://www.hypercms.com/)\" />
            ";

            if (preg_match ("/<head/i", $viewstore))
            {
              $viewstore = str_ireplace ("<head>", "<head>".$vendor.$scriptcode, $viewstore);
              // deprecated sinde version 9.0.4: 
              // $viewstore = str_ireplace ("</head>", $scriptcode."</head>", $viewstore);
            }
            else $bodytagnew = $bodytagnew."\n".$scriptcode;

            // insert head information into viewstore
            $viewstore = str_replace ($bodytagold, $bodytagnew, $viewstore);
          }

          // ====================================== transform hyperreferences ============================================
          // for EasyEdit mode excluding component preview inclusions
          if ($buildview == "cmsview" || $buildview == "inlineview" || ($buildview == "preview" && $ctrlreload == "yes"))
          {
            $viewstore = transformlink ($viewstore);
            $viewstore = str_replace ("hypercms_href=", "href=", $viewstore);
          }
          // for all other views excluding component preview inclusions (transform protected hyper references)
          elseif ($buildview == "publish"  || $buildview == "unpublish" || $buildview == "template" || $buildview == "preview")
          {
            $viewstore = str_replace ("hypercms_href=", "href=", $viewstore);
          }

          // ======================================== add header information =============================================
          if (($buildview == "publish" || $buildview == "unpublish") && $application != "media")
          {
            // define template and content file pointer
            $sourcefiles = "\n<!-- hyperCMS:template file=\"".$templatefile."\" -->\n<!-- hyperCMS:content file=\"".$contentfile."\" -->\n";

            // preserve the name of the file
            if ($namefile != "") $sourcefiles .= "<!-- hyperCMS:name file=\"".$namefile."\" -->\n";

            // if a file was generated
            if ($mediafile != "") $sourcefiles .= "<!-- hyperCMS:media file=\"".$mediafile."\" -->\n";

            // insert hypercms file pointer comment tags for reference
            if ($application != "xml")
            {
              if (@substr_count (strtolower ($viewstore), "<body") > 0) $bodytagold = gethtmltag ($viewstore, "<body");
              elseif (@substr_count (strtolower ($viewstore), ":body") > 0) $bodytagold = gethtmltag ($viewstore, ":body");
              else $bodytagold = false;
            }
            else $bodytagold = gethtmltag ($viewstore, "<?xml");

            if ($bodytagold != false) $viewstore = str_replace ($bodytagold, $bodytagold.$sourcefiles, $viewstore);
            else $viewstore = $sourcefiles.$viewstore;

            // add livelink function for active link management
            if ($application != "generator")
            {
              // set variables for API programming
              $tpl_globals = tpl_globals ($application, $contentfile, $charset);

              // insert code
              $viewstore = $pagetracking.$tpl_globals.$tpl_livelink.$tpl_linkindex.trim ($viewstore);
            }
          }
        }
        // if an error occured in the view
        else
        {
          // insert form button if error occured in direct view (not included component view)
          if ($ctrlreload == "yes")
          {
            $button_formview = "<a href=\"".cleandomain ($mgmt_config['url_path_cms'])."page_view.php?view=formedit&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."\"><img src=\"".getthemelocation()."img/edit_form.png\" style=\"display:inline; height:32px; padding:0; margin:0; border:0; vertical-align:top; text-align:left;\" alt=\"".getescapedtext ($hcms_lang['form-view'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['form-view'][$lang], $charset, $lang)."\" /></a>";
            $viewstore = str_replace ("<!-- hyperCMS:ErrorCodeBegin -->", $button_formview, $viewstore);

            // add html and body tags if missing
            if (strpos (strtolower ("_".$viewstore), "<html") < 1)
            {
              $viewstore = "<!DOCTYPE html>
  <html>
  <head>
  <title>hyperCMS</title>
  <meta charset=\"UTF-8\" />
  <body>
  ".$viewstore."
  </body>
  </html>";
            }
          }
          else
          {
            $viewstore = str_replace ("<!-- hyperCMS:ErrorCodeBegin -->", "", $viewstore);
          }
        }
      }
      // =========================================== FORM views ==============================================
      elseif ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
      {
        // ================================ javascript for control frame call ================================
        if ($ctrlreload == "yes")
        {
          $bodytag_controlreload = "if (parent.frames['controlFrame']) parent.frames['controlFrame'].location.href='".cleandomain ($mgmt_config['url_path_cms'])."control_content_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."'; ";
        }
        else $bodytag_controlreload = "";

        // set the default width for different media types for the preview and annotations
        // keep in mind that the video width must match with the rendering setting in the main config
        $default_width = getpreviewwidth ($site, $name_orig);

        // correct media width for mobile devices (if viewport width is smaller than the default width)
        if ($maxwidth > 0 && $maxwidth < $default_width) $mediawidth = $maxwidth;
        else $mediawidth = $default_width;

        // form function call for unformated text constraints
        if (sizeof ($constraint_array) > 0)
        {
          $i = 1;

          foreach ($constraint_array as $constraint)
          {
            if ($i == 1) $add_constraint = $constraint;
            else $add_constraint .= ", ".$constraint;

            $i++;
          }

          $add_constraint = "checkcontent = validateForm(".$add_constraint.");";
        }

        $viewstore = "<!DOCTYPE html>
<html>
<head>
  <title>hyperCMS</title>
  <base href=\"".cleandomain ($mgmt_config['url_path_cms'])."\" />
  <meta charset=\"".$charset."\" />
  <meta name=\"robots\" content=\"noindex, nofollow\" />
  <!-- hyperCMS -->
  <link rel=\"stylesheet\" type=\"text/css\" href=\"".getthemelocation()."css/main.css\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />
  <style>
  .hcmsMapsControls
  {
    margin-top: 10px;
    border: 1px solid transparent;
    border-radius: 2px;
    box-sizing: border-box;
    -moz-box-sizing: border-box;
    height: 30px;
    outline: none;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.3);
  }

  #pac-input
  {
    background-color: #fff;
    font-family: Roboto;
    font-size: 12px;
    font-weight: 300;
    margin-left: 12px;
    padding: 0 11px 0 13px;
    text-overflow: ellipsis;
    width: 280px;
  }

  #pac-input:focus
  {
    border-color: #4d90fe;
  }

  .pac-container
  {
    font-family: Roboto;
  }

  #type-selector
  {
    color: #fff;
    background-color: #4d90fe;
    padding: 5px 11px 0px 11px;
  }

  #type-selector label
  {
    font-family: Roboto;
    font-size: 12px;
    font-weight: 300;
  }
  
  #target
  {
    width: 345px;
  }

  #preview
  {
    padding: 0px 20px 10px 0px;
    min-width: 600px;
    float: left;
  }

  #settings
  {
    padding :0px 20px 10px 0px;
    scrolling: auto;
    min-width: 620px;
    float: left;
  }

  @media screen and (max-width: 1380px)
  {
    #preview
    {
      padding: 0;
      width: 100%;

    }

    #settings
    {
      padding: 0;
      width: 100%;
    }
  }
  </style>
  ";

  if (!empty ($recognizefaces_service)) $viewstore .= "
  <script type=\"text/javascript\">
  // initialize (important for cross-domain service)
  var hcms_service = true;
  </script>
  ";

  $viewstore .= "
  <script src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/main.min.js\" type=\"text/javascript\"></script>
  <!-- JQuery and JQuery UI -->
  <script src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/jquery/jquery-3.5.1.min.js\" type=\"text/javascript\"></script>
  <script src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/jquery-ui/jquery-ui-1.12.1.min.js\" type=\"text/javascript\"></script>
  <link  rel=\"stylesheet\" href=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/jquery-ui/jquery-ui-1.12.1.min.css\" type=\"text/css\" />
  ";

  if (empty ($recognizefaces_service)) $viewstore .= "
  <!-- Editor -->
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/ckeditor/ckeditor/ckeditor.js\"></script>
  <script type=\"text/javascript\">CKEDITOR.disableAutoInline = true;</script>
  <!-- Calendar -->
  <link  rel=\"stylesheet\" type=\"text/css\" href=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rich_calendar.css\" />
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rich_calendar.min.js\"></script>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rc_lang_en.js\"></script>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rc_lang_de.js\"></script>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rc_lang_fr.js\"></script>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rc_lang_pt.js\"></script>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rc_lang_ru.js\"></script>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/domready.js\"></script>
  <!-- Tagging -->
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/tag-it/tag-it.min.js\"></script>
  <link rel=\"stylesheet\" type=\"text/css\" href=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/tag-it/jquery.tagit.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/tag-it/tagit.ui-zendesk.css\" />
  <!-- JSignature -->
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/signature/jSignature.min.js\"></script>
  <!--[if lt IE 9]>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/signature/flashcanvas.js\"></script>
  <![endif]-->
  ";

  // only include if annotations library is available
  if (is_file ($mgmt_config['abs_path_cms']."javascript/annotate/annotate.css")) $viewstore .= "
  <!-- Annotations -->
  <link rel=\"stylesheet\" type=\"text/css\" href=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/annotate/annotate.css\">";

  if (empty ($recognizefaces_service)) $viewstore .= "
  <!-- Google Maps -->
  <script src=\"https://maps.googleapis.com/maps/api/js?v=3&key=".$mgmt_config['googlemaps_appkey']."&libraries=places\"></script>
  ";

  $viewstore .= "
  <script type=\"text/javascript\">
  // initialize
  var hcms_consolelog = true;
  ";

  if ($buildview != "formlock") $viewstore .= "
  ".$bodytag_popup."

  // ----- Validation -----

  function validateForm ()
  {
    var i,p,q,nm,contentname,test,num,min,max,errors='',args=validateForm.arguments;

    for (i=0; i<(args.length-2); i+=3)
    {
      val = hcms_findObj(args[i]); // 1st argument
      contentname = args[i+1]; // 2nd argument
      test = args[i+2]; // 3rd argument

      if (val)
      {
        if (contentname != '')
        {
          nm = contentname;
        }
        else
        {
          nm = val.name;
          nm = nm.substring(nm.indexOf('_')+1, nm.length);
        }

        // value is required
        if (test.charAt(0) == 'R' && val.value.trim() == '' && val.type && val.type.toLowerCase() != 'checkbox' && val.type.toLowerCase() != 'radio')
        {
          errors += nm+' - ".getescapedtext ($hcms_lang['a-value-is-required'][$lang], $charset, $lang).".\\n';
        }
        // test value
        else if ((val=val.value) != '' && test != '')
        {
          if (test == 'audio' || test == 'compressed' || test == 'flash' || test == 'image' || test == 'text' || test == 'video' || test == 'watermark')
          {
            errors += checkMediaType(val, contentname, test);
          }
          else if (test.indexOf('isEmail') != -1)
          {
            p = val.indexOf('@');
            if (p<1 || p==(val.length-1)) errors += nm+' - ".getescapedtext ($hcms_lang['value-must-contain-an-e-mail-address'][$lang], $charset, $lang).".\\n';
          }
          else if (test != 'R')
          {
            num = parseFloat(val);
            if (isNaN(val)) errors += nm+' - ".getescapedtext ($hcms_lang['value-must-contain-a-number'][$lang], $charset, $lang).".\\n';

            if (test.indexOf('inRange') != -1)
            {
              p = test.indexOf(':');

              if (test.substring(0,1) == 'R')
              {
                min = test.substring(8,p);
              }
              else
              {
                min = test.substring(7,p);
              }

              max = test.substring(p+1);
              if (num<min || max<num) errors += nm+' - ".getescapedtext ($hcms_lang['value-must-contain-a-number-between'][$lang], $charset, $lang)." '+min+' - '+max+'.\\n';
            }
          }
        }

      }
    }

    if (errors)
    {
      alert (hcms_entity_decode ('".getescapedtext ($hcms_lang['the-input-is-not-valid'][$lang], $charset, $lang).":\\n'+errors));
      return false;
    }
    else return true;
  }

  function checkMediaType (mediafile, medianame, mediatype)
  {
    if (mediafile != '' && mediatype != '')
    {
      var mediaext = mediafile.substring (mediafile.lastIndexOf('.'), mediafile.length);
      mediaext = mediaext.toLowerCase();

      if (mediaext.length > 2)
      {
        if (mediatype == 'watermark') allowedext = '.jpg.jpeg.png.gif';
        else if (mediatype == 'audio') allowedext = '".strtolower ($hcms_ext['audio'])."';
        else if (mediatype == 'compressed') allowedext = '".strtolower ($hcms_ext['compressed'])."';
        else if (mediatype == 'flash') allowedext = '".strtolower ($hcms_ext['flash'])."';
        else if (mediatype == 'image') allowedext = '".strtolower ($hcms_ext['image'])."';
        else if (mediatype == 'text') allowedext = '".strtolower ($hcms_ext['cms'].$hcms_ext['bintxt'])."';
        else if (mediatype == 'video') allowedext = '".strtolower ($hcms_ext['video'])."';

        if (allowedext.indexOf(mediaext) < 0)
        {
          var error = medianame + ' - ".getescapedtext ($hcms_lang['has-wrong-media-type-required-type'][$lang], $charset, $lang)." ' + mediatype + '.\\n';
          return error;
        }
        else return '';
      }
      else return '';
    }
    else return '';
  }

  // ----- Get, move and delete values -----

  function getValue (selectname, defaultvalue)
  {
    if (document.forms['hcms_formview'].elements[selectname] && document.forms['hcms_formview'].elements[selectname].value)
    {
      return encodeURIComponent (document.forms['hcms_formview'].elements[selectname].value);
    }
    else return defaultvalue;
  }

  function getSelectedOption (selectname, defaultvalue)
  {
    if (document.forms['hcms_formview'].elements[selectname] && document.forms['hcms_formview'].elements[selectname].options)
    {
      var selectbox = document.forms['hcms_formview'].elements[selectname];
      return encodeURIComponent (selectbox.options[selectbox.selectedIndex].value);
    }
    else return defaultvalue;
  }

  function moveSelected (select, down)
  {
    if (select.selectedIndex != -1) {
      if (down)
      {
        if (select.selectedIndex != select.options.length - 1)
          var i = select.selectedIndex + 1;
        else
          return;
      }
      else
      {
        if (select.selectedIndex != 0)
          var i = select.selectedIndex - 1;
        else
          return;
      }

      var swapOption = new Object();

      swapOption.text = select.options[select.selectedIndex].text;
      swapOption.value = select.options[select.selectedIndex].value;
      swapOption.selected = select.options[select.selectedIndex].selected;

      for (var property in swapOption) select.options[select.selectedIndex][property] = select.options[i][property];
      for (var property in swapOption) select.options[i][property] = swapOption[property];
    }
  }

  function moveBoxEntry (fbox, tbox)
  {
    var arrFbox = new Array();
    var arrTbox = new Array();
    var arrLookup = new Array();
    var i;

    for (i = 0; i < tbox.options.length; i++)
    {
      arrLookup[tbox.options[i].text] = tbox.options[i].value;
      arrTbox[i] = tbox.options[i].text;
    }

    var fLength = 0;
    var tLength = arrTbox.length;

    for(i = 0; i < fbox.options.length; i++)
    {
      arrLookup[fbox.options[i].text] = fbox.options[i].value;
      if (fbox.options[i].selected && fbox.options[i].value != '')
      {
        arrTbox[tLength] = fbox.options[i].text;
        tLength++;
      }
      else
      {
        arrFbox[fLength] = fbox.options[i].text;
        fLength++;
      }
    }

    arrFbox.sort();
    arrTbox.sort();
    fbox.length = 0;
    tbox.length = 0;
    var c;

    for(c = 0; c < arrFbox.length; c++)
    {
      var no = new Option();
      no.value = arrLookup[arrFbox[c]];
      no.text = arrFbox[c];
      fbox[c] = no;
    }

    for(c = 0; c < arrTbox.length; c++)
    {
      var no = new Option();
      no.value = arrLookup[arrTbox[c]];
      no.text = arrTbox[c];
      tbox[c] = no;
    }
  }

  function deleteEntry (select)
  {
    select.value = '';
  }

  function deleteSelected (select)
  {
    if (select.length > 0)
    {
      for(var i=0; i<select.length; i++)
      {
        if (select.options[i].selected == true) select.remove(i);
      }
    }
  }

  function replace (string,text,by)
  {
    // Replaces text with by in string
    var strLength = string.length, txtLength = text.length;
    if ((strLength == 0) || (txtLength == 0)) return string;

    var i = string.indexOf(text);
    if ((!i) && (text != string.substring(0,txtLength))) return string;
    if (i == -1) return string;

    var newstr = string.substring(0,i) + by;

    if (i+txtLength < strLength)
        newstr += replace(string.substring(i+txtLength,strLength),text,by);

    return newstr;
  }

  // ----- Open window -----

  function openBrWindowLink (select, winName, features, type)
  {
    var select_temp = document.forms['hcms_formview'].elements['temp_' + select.name];

    if (select && select_temp && select_temp.value != '')
    {
      var theURL = '';

      if (type == 'preview')
      {
        if (select_temp.value.indexOf('://') > 0)
        {
          theURL = select_temp.value;
        }
        else
        {
          if (select.value.indexOf('%page%/".$site."/') != -1) theURL = replace (select.value, '%page%/".$site."/', '".$publ_config['url_publ_page']."');
          else theURL = '".$publ_config['url_publ_page']."' + select.value;
        }
        alert (theURL);
        hcms_openWindow (theURL, winName, features, ".windowwidth ("object").", ".windowheight ("object").");
      }
      else if (type == 'cmsview' || type == 'inlineview')
      {
        theURL = select.value;

        if (theURL.indexOf('://') == -1)
        {
          var position1 = theURL.indexOf('/');
          position2 = theURL.lastIndexOf('/');

          var location_page = theURL.substring (position1, position2 + 1);
          if (location_page.indexOf('/".$site."/') != -1) location_page = replace (location_page, '/".$site."/', '%page%/".$site."/');

          // link must include publication root
          if (location_page.indexOf('/%page%/".$site."') != -1)
          {
            var location_site = theURL.substring (position1+1, theURL.length);
            location_site = location_site.substring(0, location_site.indexOf('/'));

            var page = theURL.substring (position2 + 1, theURL.length);

            // remove parameters
            if (page.indexOf('?') > 0) page = page.substring (0, page.indexOf('?'));
            if (page.indexOf('#') > 0) page = page.substring (0, page.indexOf('#'));

            theURL = '".cleandomain ($mgmt_config['url_path_cms'])."frameset_content.php?ctrlreload=yes&cat=page&site=' + encodeURIComponent(location_site) + '&location=' + encodeURIComponent(location_page) + '&page=' + encodeURIComponent(page) + '&user=".url_encode($user)."';

            hcms_openWindow (theURL, winName, features, ".windowwidth ("object").", ".windowheight ("object").");
          }
          else alert (hcms_entity_decode('".getescapedtext ($hcms_lang['this-is-an-external-page-link'][$lang], $charset, $lang)."'));
        }
        else alert (hcms_entity_decode('".getescapedtext ($hcms_lang['this-is-an-external-page-link'][$lang], $charset, $lang)."'));
      }
    }
    else alert (hcms_entity_decode('".getescapedtext ($hcms_lang['no-link-selected'][$lang], $charset, $lang)."'));
  }

  function openBrWindowComp (select, winName, features, type)
  {
    var theURL = select.value;

    if (theURL != '')
    {
      if (type == 'preview')
      {
        if (theURL.indexOf('://') == -1)
        {
          var position1 = theURL.indexOf('/');
          theURL = '".cleandomain ($publ_config['url_publ_comp'])."' + theURL.substring (position1 + 1, theURL.length);
        }

        hcms_openWindow (theURL, winName, features, ".windowwidth ("object").", ".windowheight ("object").");
      }
      else if (type == 'cmsview' || type == 'inlineview')
      {
        if (theURL.indexOf('://') == -1)
        {
          // remove parameters
          if (theURL.indexOf('?') != -1) theURL = substr (0, theURL.indexOf('?'));

          var position1 = theURL.indexOf('/');
          var position2 = theURL.lastIndexOf('/');
          var location_comp = '%comp%/' + theURL.substring (position1 + 1, position2 + 1);

          // link must include publication root
          if (location_comp.indexOf('%comp%/".$site."/') != -1)
          {
            var location_site = theURL.substring (position1 + 1, theURL.length);
            location_site = location_site.substring(0, location_site.indexOf('/'));

            var comp = theURL.substr (position2 + 1, theURL.length);

            // remove parameters
            if (comp.indexOf('?') > 0) comp = comp.substring (0, page.indexOf('?'));
            if (comp.indexOf('#') > 0) comp = comp.substring (0, page.indexOf('#'));

            theURL = '".cleandomain ($mgmt_config['url_path_cms'])."frameset_content.php?ctrlreload=yes&cat=comp&site=' + encodeURIComponent(location_site) + '&location=' + encodeURIComponent(location_comp) + '&page=' + encodeURIComponent(comp) + '&user=".url_encode($user)."';

            hcms_openWindow (theURL, winName, features, ".windowwidth ("object").", ".windowheight ("object").");
          }
          else alert (hcms_entity_decode('".getescapedtext ($hcms_lang['this-is-an-external-component-link'][$lang], $charset, $lang)."'));
        }
        else alert (hcms_entity_decode('".getescapedtext ($hcms_lang['this-is-an-external-component-link'][$lang], $charset, $lang)."'));
      }
    }
    else alert (hcms_entity_decode('".getescapedtext ($hcms_lang['no-component-selected'][$lang], $charset, $lang)."'));
  }

  // ----- Prepare form fields for submit -----
  function submitText (selectname, targetname)
  {
    if (document.forms['hcms_formview'].elements[targetname] && document.forms['hcms_formview'].elements[selectname])
    {
      document.forms['hcms_formview'].elements[targetname].value = document.forms['hcms_formview'].elements[selectname].value;
    }
  }

  function submitLink (selectname, targetname)
  {
    var select = document.forms['hcms_formview'].elements[selectname];
    var target = document.forms['hcms_formview'].elements[targetname];

    if (select && target)
    {
      if (select.value != '')
      {
        // manually entered hyperlink (http://)
        if (select.value.indexOf('://') > 0)
        {
          target.value = select.value;
        }
        // manually entered relative hyperlink
        else if (select.value.indexOf('/') > 0)
        {
          target.value = select.value;
        }
        // or set via navigation tree
        else if (select.value.indexOf('/') == 0 && select.value.indexOf('%page%') == -1 && select.value.indexOf('%comp%') == -1)
        {
          var link_add = '';

          //  manually added anchor (#anchor)
          if (select.value.indexOf('#') > 0)
          {
            link_add = select.value.substring(select.value.indexOf('#'), select.value.length);

            if (target.value.indexOf('#') > 0) target.value = target.value.substring(0, target.value.indexOf('#')) + link_add;
            else target.value = target.value + link_add;
          }
          // manually added parameters (?variable=name)
          else if (select.value.indexOf('?') > 0)
          {
            link_add = select.value.substring(select.value.indexOf('?'), select.value.length);

            if (target.value.indexOf('?') > 0) target.value = target.value.substring(0, target.value.indexOf('?')) + link_add;
            else target.value = target.value + link_add;
          }
          // selected link by explorer
          else target.value = target.value + link_add;
        }
        // all other cases
        else
        {
          // use link_name
          target.value = select.value;
        }
      }
      else
      {
        target.value = '';
      }
    }

    return true;
  }

  function submitMultiComp (selectname, targetname)
  {
    var component = '';
    var select = document.forms['hcms_formview'].elements[selectname];
    var target = document.forms['hcms_formview'].elements[targetname];

    if (select.options.length > 0)
    {
      for(var i=0; i<select.options.length; i++)
      {
        component = component + select.options[i].value + '|' ;
      }
    }
    else
    {
      component = '';
    }

    target.value = component;
    return true;
  }

  function submitLanguage (selectname, targetname)
  {
    var content = '' ;
    var select = document.forms['hcms_formview'].elements[selectname];
    var target = document.forms['hcms_formview'].elements[targetname];

    if (select.options.length > 0)
    {
      for(var i=0; i<select.options.length; i++)
      {
        content = content + select.options[i].value + ',' ;
      }
    }
    else
    {
      content = '';
    }

    target.value = content;
    return true;
  }

  // ----- Field controls for form views -----

  // Alias for checkFieldValue
  function checkValue (id, value)
  {
    return checkFieldValue (id, value);
  }

  function checkFieldValue (id, value)
  {
    if (document.getElementById(id))
    {
      if (document.getElementById(id).type === 'checkbox')
      {
        if (document.getElementById(id).checked == true && document.getElementById(id).value == value) return true;
      }
      else if (document.getElementById(id).value)
      {
        if (document.getElementById(id).value == value || document.getElementById(id).value.indexOf(value) > -1) return true;
      }
    }

    return false;
  }

  // Alias for lockField
  function lockEdit (id)
  {
    lockField (id);
  }

  function lockField (id)
  {
    if (document.getElementById(id))
    {
      document.getElementById(id).disabled = true;
    }

    if (document.getElementById(id+'_controls'))
    {
      document.getElementById(id+'_controls').style.display = 'none';
    }

    var elements = document.getElementsByClassName(id);

    if (elements.length > 0)
    {
      for (var i = 0; i < elements.length; i++)
      {
        elements[i].disabled = true;
      }
    }

    if (document.getElementById(id+'_protect'))
    {
      document.getElementById(id+'_protect').style.display = 'inline';
    }
  }

  // Alias for unlockField
  function unlockEdit (id)
  {
    unlockField (id);
  }

  function unlockField (id)
  {
    if (document.getElementById(id))
    {
      document.getElementById(id).disabled = false;
    }

    if (document.getElementById(id+'_controls'))
    {
      document.getElementById(id+'_controls').style.display = 'inline-block';
    }

    var elements = document.getElementsByClassName(id);

    if (elements.length > 0)
    {
      for (var i = 0; i < elements.length; i++)
      {
        elements[i].disabled = false;
      }
    }

    if (document.getElementById(id+'_protect'))
    {
      document.getElementById(id+'_protect').style.display = 'none';
    }
  }

  function hideField (id)
  {
    var elements = document.getElementsByClassName(id);

    if (elements.length > 0)
    {
      for (var i = 0; i < elements.length; i++)
      {
        elements[i].style.display = 'none';
      }
    }

    lockEdit (id);
  }

  function showField (id)
  {
    var elements = document.getElementsByClassName(id);

    if (elements.length > 0)
    {
      for (var i = 0; i < elements.length; i++)
      {
        elements[i].style.display = '';
      }
    }

    unlockEdit (id);
  }

  // ----- Rich Calendar -----

  var cal_obj = null;
  var cal_format = null;
  var cal_field = null;

  function show_cal (el, field_id, format)
  {
    if (cal_obj) return;

    cal_field = field_id;
    cal_format = format;
    var datefield = document.getElementById(field_id);

    cal_obj = new RichCalendar();
    cal_obj.start_week_day = 1;
    cal_obj.show_time = false;
    cal_obj.language = '".getcalendarlang ($lang)."';
    cal_obj.user_onchange_handler = cal_on_change;
    cal_obj.user_onclose_handler = cal_on_close;
    cal_obj.user_onautoclose_handler = cal_on_autoclose;
    cal_obj.parse_date(datefield.value, cal_format);
    cal_obj.show_at_element(datefield, 'adj_left-bottom');
  }

  // onchange handler
  function cal_on_change (cal, object_code)
  {
    if (object_code == 'day')
    {
      document.getElementById(cal_field).value = cal.get_formatted_date(cal_format);
      cal.hide();
      cal_obj = null;
    }
  }

  // user defined onclose handler (used in pop-up mode - when auto_close is true)
  function cal_on_close (cal)
  {
  	cal.hide();
  	cal_obj = null;
  }

  // user defined onautoclose handler
  function cal_on_autoclose (cal)
  {
  	cal_obj = null;
  }

  // ----- Comment -----

  function deleteComment(element, value)
  {
    element.disabled = value;
  }

  function isNewComment ()
  {
    var comments = document.getElementsByClassName('is_comment');

    if (comments.length > 0)
    {
      // write content from ckeditor to textarea
      $('textarea.is_comment').each(function () {
         var \$textarea = $(this);
         if (CKEDITOR.instances[\$textarea.attr('id')]) \$textarea.val(CKEDITOR.instances[\$textarea.attr('id')].getData());
      });

      // look for new content or checked delete boxes
      for (var i = 0; i < comments.length; i++)
      {
        if (comments[i].checked == true) return true;
        else if (comments[i].name != '' && comments[i].value != '') return true;
      }
    }

    return false;
  }

  // ----- Signature -----

  function initializeSignature (id)
  {
    // initialize the jSignature widget with options
    $('#signature_' + id).jSignature({ 'lineWidth': 2, 'decor-color': 'transparent' });

    // on change
    $('#signature_' + id).bind('change', function(e) {
      // create image (image = PNG, svgbase64 = SVG)
      if ($('#signature_' + id).jSignature('getData', 'native').length > 0) 
      {
        var imagedata = $('#signature_' + id).jSignature('getData', 'image');
        // set image data string
        $('#' + id).val(imagedata);
      }
      else if ($('#' + id).val() == '')
      {
        $('#' + id).val('');
      }
    });

    // show existing signature image and hide signature field
    if ($('#signatureimage_' + id).length)
    {
      $('#signatureimage_' + id).show();
      $('#signaturefield_' + id).hide();
    }
    else
    {
      $('#signaturefield_' + id).show();
    }
  }

  function resetSignature (id)
  {
    // clears the canvas and rerenders the decor on it
    $('#signature_'+id).jSignature('reset');
    // empty hidden field
    $('#signature_'+id).val('');

    return false;
  }

  // ----- Save only -----

  function saveContent ()
  {
    if (document.forms['hcms_formview'])
    {
      // write annotation image to hidden input
      if (document.getElementById('annotation') && typeof $('#annotation').annotate !== 'undefined')
      {
        $('#annotation').annotate('flatten');
      }

      document.forms['hcms_formview'].submit();
    }
  }

  // ----- Set save type and save -----

  function setSaveType (type, url, method)
  {
    if (typeof (method) === 'undefined') method = 'post';
    var checkcontent = true;
    var save = false;

    if (method != 'ajax_no_constraint')
    {
      ".$add_constraint."
    }

    if (checkcontent == true)
    {
      document.forms['hcms_formview'].elements['savetype'].value = type;
      document.forms['hcms_formview'].elements['forward'].value = url;
      ".$add_submittext."
      ".$add_submitlanguage."
      ".$add_submitlink."
      ".$add_submitcomp."
      hcms_stringifyVTTrecords();
      if (typeof collectFaces === 'function') collectFaces();
      if (typeof initFaceOnVideo === 'function') initFaceOnVideo();

      // save content using form POST method
      if (method == 'post' || isNewComment()) saveContent();
      // save content using AJAX in all other cases
      else save = autoSave(true);

      // for file upload and meta data editing
      if (save == true && typeof parent.nextEditWindow === 'function')
      {
        parent.nextEditWindow();
      }
      else if (save == false)
      {
        saveContent();
      }

      return true;
    }
    else return false;
  }

  function hcms_saveEvent ()
  {
    setSaveType('form_so', '', 'ajax');
  }
  ";

  // autosave code
  if (intval ($mgmt_config['autosave']) > 0)
  {
    $autosave_active = "var active = $(\"#autosave\").is(\":checked\");";
    $autosave_timer = "setTimeout ('autoSave(false)', ".(intval ($mgmt_config['autosave']) * 1000).");";
  }
  else
  {
    $autosave_active = "var active = true;";
    $autosave_timer = "";
  }

  if ($buildview != "formlock") $viewstore .= "
  // ----- Autosave -----

  function autoSave (override)
  {
    ".$autosave_active."

    if (active == true || override == true)
    {
      var checkcontent = true;

      ".$add_constraint."

      if (checkcontent == true)
      {
        ".$add_submittext."
        ".$add_submitlanguage."
        ".$add_submitlink."
        ".$add_submitcomp."
        hcms_stringifyVTTrecords();
        if (typeof collectFaces === 'function') collectFaces();

        // write content to textareas
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances)
        {
          for (var i in CKEDITOR.instances)
          {
            CKEDITOR.instances[i].updateElement();
          }
        }

        // present saving overlay
        hcms_showFormLayer ('saveLayer', 0);
        $(\"#savetype\").val('auto');

        // write annotation image to hidden input
        if (document.getElementById('annotation') && typeof $('#annotation').annotate !== 'undefined')
        {
          $('#annotation').annotate('flatten');
        }

        $.ajax({
          type: 'POST',
          url: \"".cleandomain ($mgmt_config['url_path_cms'])."service/savecontent.php\",
          data: $(\"#hcms_formview\").serialize(),
          success: function (data)
          {
            if (data.message.length !== 0)
            {
              alert (hcms_entity_decode(data.message));
            }				
            setTimeout (\"hcms_hideFormLayer('saveLayer')\", 500);
          },
          dataType: \"json\",
          async: false
        });

        return true;
      }
    }

    ".$autosave_timer."

    return false;
  }";

  $viewstore .= "
  // ----- Geo tagging with Google maps -----

  var map;
  var markers = [];

  function initMap (location)
  {
    if (typeof (location) === 'undefined') location = '';

    // use provided geolocation
    if (location != '')
    {
      var position = location.split(',');

      if (position[0] != '' && position[1] != '')
      {
        var lat = parseFloat(position[0]);
        var lng = parseFloat(position[1]);
      }
    }
    else
    {
      var lat = 0;
      var lng = 0;
    }

    // set center of map
    var latlng = new google.maps.LatLng(lat, lng);

    var myOptions = {
      zoom: 2,
      scrollwheel: true,
      center: latlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };

    // create map
    map = new google.maps.Map(document.getElementById('map'), myOptions);

    // set marker if location has been provided
    if (location != '')
    {
      placeMarker (latlng);
    }

    // add a click event handler to the map object
    google.maps.event.addListener(map, 'click', function(event)
    {
      // place a marker
      placeMarker(event.latLng);

      // display the lat/lng in geo location field
      document.getElementById('geolocation').value = event.latLng.lat() + ', '+ event.latLng.lng();
    });

    // Requires the Places library. Include the libraries=places parameter when you first load the API. For example:
    // <script src='https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places'>

    // Create the search box and link it to the UI element.
    var input = document.getElementById('pac-input');
    var searchBox = new google.maps.places.SearchBox(input);
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

    // Bias the SearchBox results towards current map's viewport.
    map.addListener('bounds_changed', function() {
      searchBox.setBounds(map.getBounds());
    });

    // Listen for the event fired when the user selects a prediction and retrieve
    // more details for that place.
    searchBox.addListener('places_changed', function() {
      var places = searchBox.getPlaces();

      if (places.length == 0) return;

      // Clear out the old markers
      markers.forEach(function(marker) {
        marker.setMap(null);
      });

      // For each place, get the icon, name and location.
      var bounds = new google.maps.LatLngBounds();

      places.forEach(function(place) {
        if (!place.geometry)
        {
          if (hcms_consolelog) console.log('Returned place contains no geometry');
          return;
        }

        // Create a marker for each place
        markers.push(new google.maps.Marker({
          map: map,
          title: place.name,
          position: place.geometry.location
        }));

        // display the lat/lng in geo location field
        document.getElementById('geolocation').value = place.geometry.location.lat() + ', '+ place.geometry.location.lng();

        if (place.geometry.viewport)
        {
          // Only geocodes have viewport.
          bounds.union(place.geometry.viewport);
        }
        else
        {
          bounds.extend(place.geometry.location);
        }
      });
      map.fitBounds(bounds);
    });
  }

  function placeMarker (location)
  {
    // first remove all markers if there are any
    deleteMarkers();

    var marker = new google.maps.Marker({
      position: location,
      map: map
    });

    // add marker in markers array
    markers.push(marker);

    map.setCenter(location);
  }

  // Deletes all markers in the array by removing references to them
  function deleteMarkers ()
  {
    if (markers)
    {
      for (i in markers)
      {
        markers[i].setMap(null);
      }

      markers.length = 0;
    }
  }

  // ----- Face recognition / detection -----

  // stored face definitions
  var faces_json = ".$faces_json.";

  // memory for all face IDs
  var videoface_id = [];
  var imageface_id = [];

  // click event memory to prevent other events from firing
  var clickevent = 'init';

  // has detectFaceOnImage or detectFaceOnVideo been executed
  var detectface = false;

  // has deleteFace been executed
  var deleteface = false;
  ";

  if (empty ($user_client['msie']))
  {
    $viewstore .= "
  // ------------------------------ Face marker --------------------------------

  // create face marker on image
  function createFaceMarkerOnImage (image_id, face_id, x, y, w, h, name, check_names, dragresize)
  {
    // create the new face marker only if the same face does not exist already (compare face name and coordinates)
    if (x >= 0 && w > 0 && y >= 0 && h > 0)
    {
      // collect all existing faces
      var faces = collectFaces('all');

      if (faces)
      {
        if (typeof faces === 'string') var faces = JSON.parse (faces);

        for (var i = 0; i < faces.length; i++)
        {
          // name exists already
          if (check_names == true && name != '' && faces[i].name.toLowerCase() == name.toLowerCase()) return false;
          // coordinates exists already
          else if (x > (faces[i].x - 3) && x < (faces[i].x + 3) && y > (faces[i].y - 3) && y < (faces[i].y + 3)) return false;
        }
      }
    }

    // draggable and resizable (face is considered not to be verified)
    if (dragresize == true)
    {
      var dragresizable = '$(\"#hcmsFace' + face_id + '\").draggable().resizable();';
      var verifiedface = '0';
    }
    else
    {
      var dragresizable = '';
      var verifiedface = '1';
    }

    if (image_id !== '' && face_id >= 0 && (name == '' || document.getElementById('hcmsFace' + face_id) === null) && x >= 0 && w > 0 && y >= 0 && h > 0)
    {
      $('<div>', {
        'id': 'hcmsFace' + face_id,
        'class': 'hcmsFace',
        'onclick': dragresizable + ' switchFaceName(\"hcmsFaceName' + face_id + '\")',
        'onresize': 'resizeFaceName(\"' + face_id + '\");',
        'ondrag': 'resizeFaceName(\"' + face_id + '\");',
        'css': {
          'position': 'absolute',
          'left': x + 'px',
          'top': y + 'px',
          'width': w + 'px',
          'height': h + 'px'
        }
      })
      .insertAfter('#' + image_id);

      imageface_id.push(face_id);
      var offset = (216 - w) / 2;

      // label and form
      $(\"<div id='hcmsFaceName\" + face_id + \"' onclick='clickFaceName();' class='hcmsInfoBox hcmsFaceName' style='visibility:visible; white-space:nowrap; position:absolute; top:\" + (y + h + 6) +\"px; left:\" + (x - offset) + \"px;'><input type='hidden' id='facedetails\" + face_id + \"' value='\\\"face\\\":\" + verifiedface + \"' /><textarea type='text' id='facename\" + face_id + \"' placeholder='".getescapedtext ($hcms_lang['name'][$lang], $charset, $lang)."' class='hcmsTextArea' style='width:200px; height:32px;'>\" +  name + \"</textarea> <img src='".getthemelocation()."img/button_delete.png' class='hcmsButtonTiny hcmsButtonSizeSquare' align='absmiddle' onmousedown=\\\"deleteFace('\" + face_id + \"');\\\" title='".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."' /></div>\").insertAfter($('#hcmsFace' + face_id));

      return true;
    }

    return false;
  }

  // create face marker on video
  function createFaceMarkerOnVideo (video_id, face_id, time, x, y, w, h, name, link, visibility, check_names, dragresize)
  {
    // create the new face marker only if the same face does not exist already
    if (time >= 0 && x >= 0 && w > 0 && y >= 0 && h > 0)
    {
      // collect all existing faces
      var faces = collectFaces('all');

      if (faces)
      {
        if (typeof faces === 'string') var faces = JSON.parse (faces);

        for (var i = 0; i < faces.length; i++)
        {
          // name exists already
          if (check_names == true && name != '' && parseInt(faces[i].time) > (time - 2) && parseInt(faces[i].time) < (time + 2) && faces[i].name.toLowerCase() == name.toLowerCase()) return false;
          // coordinates exists already
          else if (Math.round(faces[i].time) == Math.round(time) && x > (faces[i].x - 3) && x < (faces[i].x + 3) && y > (faces[i].y - 3) && y < (faces[i].y + 3)) return false;
        }
      }
    }

    // get video time id and create face id
    if (face_id == '' && time >= 0)
    {
      var time_id = time.toString().replace ('.', '_');
      face_id = Math.floor(Math.random() * 90000) + '_' + time_id;
    }

    // draggable and resizable (face is considered not to be verified)
    if (dragresize == true)
    {
      var dragresizable = '$(\"#hcmsFace' + face_id + '\").draggable().resizable();';
      var verifiedface = '0';
    }
    else
    {
      var dragresizable = '';
      var verifiedface = '1';
    }

    if (visibility == 'show') visibility = 'visible';
    else if (visibility == 'hide') visibility = 'hidden';
    else visibility = 'hidden';
    
    if (video_id != '' && time >= 0 && x >= 0 && w > 0 && y >= 0 && h > 0)
    {
      // add face id to array
      videoface_id.push(face_id);

      $('<div>', {
        'id': 'hcmsFace' + face_id,
        'class': 'hcmsFace',
        'onclick': dragresizable + ' switchFaceName(\"hcmsFaceName' + face_id + '\")',
        'onresize': 'resizeFaceName(\"' + face_id + '\");',
        'ondrag': 'resizeFaceName(\"' + face_id + '\");',
        'css': {
          'visibility': visibility,
          'position': 'absolute',
          'left': x + 'px',
          'top': y + 'px',
          'width': w + 'px',
          'height': h + 'px'
        }
      })
      .insertAfter('#' + video_id);

      var offset = (216 - w) / 2;

      // label and form
      $(\"<div id='hcmsFaceName\" + face_id + \"' onclick='clickFaceName();' class='hcmsInfoBox hcmsFaceName' style='visibility:\" + visibility+ \"; white-space:nowrap; position:absolute; top:\" + (y + h + 6) +\"px; left:\" + (x - offset) + \"px;'><input type='hidden' id='facedetails\" + face_id + \"' value='\\\"time\\\":\" + time + \", \\\"face\\\":\" + verifiedface + \"' /><textarea type='text' id='facename\" + face_id + \"' onblur='collectFaces(); initFaceOnVideo();' placeholder='".getescapedtext ($hcms_lang['name'][$lang], $charset, $lang)."' class='hcmsTextArea' style='width:200px; height:32px;'>\" + name + \"</textarea> <img src='".getthemelocation()."img/button_delete.png' class='hcmsButtonTiny hcmsButtonSizeSquare' align='absmiddle' onmousedown=\\\"deleteFace('\" + face_id + \"');\\\" title='".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."' /><br/><textarea type='text' id='facelink\" + face_id + \"' onblur='collectFaces(); initFaceOnVideo();' placeholder='".getescapedtext ($hcms_lang['link'][$lang], $charset, $lang)."' class='hcmsTextArea' style='width:200px; height:32px;'>\" +  link + \"</textarea></div>\").insertAfter($('#hcmsFace' + face_id));

      return true;
    }

    return false;
  }

  // delete face
  function deleteFace (id)
  {
    // keeps the rest of the handlers from being executed
    clickevent = 'deleteFace';

    deleteface = true;
    
    $('#facename' + id).remove();
    $('#hcmsFace' + id).remove();
    $('#hcmsFaceName' + id).remove();

    if (typeof collectFaces === 'function') collectFaces();
    if (typeof initFaceOnVideo === 'function') initFaceOnVideo();
    if (typeof detectFaceOnImage === 'function') detectFaceOnImage();

    if (videoface_id.length > 0) videoface_id = hcms_arrayRemoveValue (videoface_id, id);
    else if (imageface_id.length > 0) imageface_id = hcms_arrayRemoveValue (imageface_id, id);

    setTimeout(function() { clickevent = ''; }, 500);
  }

  function captureImageFromVideo (video_id)
  {
    if (video_id != '' && document.getElementById(video_id))
    { 
      var video = document.getElementById(video_id);
      video.pause();

      var canvas = document.createElement('canvas'); 
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      canvas.getContext('2d').drawImage(video, 0, 0, video.videoWidth, video.videoHeight);

      // create base64 encoded imaga data string
      var imagedata = canvas.toDataURL('image/jpeg');

      // remove canvas
      canvas.remove();

      return imagedata;
    }

    return false;
  }
  
  async function recognizeFacesOnImage (element)
  {
    if (element && element.src && deleteface == false)
    {
      // get image attributes
      var src = element.src;
      var width = element.naturalWidth;
      var height = element.naturalHeight;

      // call function in main frame
      // used for service
      if (hcms_service == true && parent.recognizeFaces)
      {
        return await parent.recognizeFaces (src, width, height);
      }
      // used for user
      else if (hcms_service == false)
      {
        if (parent.window.opener) return await parent.window.opener.top.recognizeFaces (src, width, height, 0);
        else if (window.opener) return await window.opener.top.recognizeFaces (src, width, height, 0);
        else if (typeof top.recognizeFaces !== 'undefined') return await top.recognizeFaces (src, width, height, 0);
      }
    }

    return false;
  }

  async function recognizeFacesOnVideo (element)
  {
    if (element)
    {
      // get video attributes
      var width = element.videoWidth;
      var height = element.videoWidth;
      var src = captureImageFromVideo (element.id);
      var time = element.currentTime;

      // call function in main frame
      if (src != '')
      {
        // used for service
        if (hcms_service == true && parent.recognizeFaces)
        {
          return await parent.recognizeFaces (src, width, height, time);
        }
        // used for user
        else if (hcms_service == false)
        {
          if (parent.window.opener) return await parent.window.opener.top.recognizeFaces (src, width, height, time);
          else if (window.opener) return await window.opener.top.recognizeFaces (src, width, height, time);
          else if (typeof top.recognizeFaces !== 'undefined') return await top.recognizeFaces (src, width, height, time);
        }
      }
    }

    return false;
  }

  // call recognize faces service
  function recognizeFacesService ()
  {
    setTimeout (function () { document.location.href='".cleandomain($mgmt_config['url_path_cms'])."service/recognizefaces.php".((!empty ($recognizefaces_service) && substr ($user, 0, 4) == "sys:") ? "?PHPSESSID=".session_id() : "")."'; }, 2000);
  }

  // collect frames from video and recognize faces
  function recognizeFacesOnVideoService ()
  {
    // take a video frame snapshot each elapsed time in seconds
    var frame_sec = 0.7;
    var savemarker = false;

    // get video tag id
    if (document.getElementById('hcms_mediaplayer_asset_html5_api'))
    {
      var video_id = 'hcms_mediaplayer_asset_html5_api';
    }
    else if (document.getElementById('hcms_mediaplayer_asset_flash_api'))
    {
      var video_id = 'hcms_mediaplayer_asset_flash_api';
    }
    else return false;

    if (hcms_consolelog) console.log('video face recognition service start ...');

    var video = document.getElementById(video_id);
    var progress = '0 %';
    var snapshottime = 0;

    async function drawFrame()
    {
      if (snapshottime > (video.currentTime - frame_sec) && snapshottime < (video.duration - frame_sec)) return false;

      video.pause();
      snapshottime = video.currentTime;

      // call function in main frame
      var faces = await recognizeFacesOnVideo (video);

      // progress
      progress = ((video.currentTime / video.duration) * 100).toFixed(2) + ' %';
      if (hcms_consolelog) console.log('video progress (time: ' + video.currentTime + ' s): ' + progress);

      if (faces && faces.length > 0)
      {
        // scaling if the video size has been changed (display size / original)
        ".(!empty ($mediawidth) ? "if (video.videoWidth) var scale = parseFloat(".$mediawidth.") / parseFloat(video.videoWidth);" : "var scale = 1;")."

        if (hcms_consolelog) console.log('scale = ' + parseInt(".$mediawidth.") + '/' + parseInt(video.videoWidth));

        var faces = hcms_arrayUnique (faces);

        for (var i = 0; i < faces.length; i++)
        {
          // create face id
          var time = faces[i].time;
          var time_id = time.toString().replace ('.', '_');
          var id = i + '_' + time_id;

          var marker = createFaceMarkerOnVideo (video_id, id, faces[i].time, (faces[i].x * scale), (faces[i].y * scale), (faces[i].width * scale), (faces[i].height * scale), faces[i].label, '', 'show', true, false);
          if (marker == true && faces[i].label != '') savemarker = true;

          if (hcms_consolelog) console.log('recognized \"' + faces[i].label + '\" (ID:' + id + ') with a distance of ' + faces[i].distance + ' on video with coordinates x:' + (faces[i].x * scale) + ' y:' + (faces[i].y * scale));
        }
      }

      // continue
      if (video.currentTime < video.duration)
      {
        video.play();
      }

      // fallback for ended video event
      if (snapshottime > (video.duration - frame_sec)) onend();
    }

    function onend ()
    {
      // save face markers without input verification
      if (savemarker == true) 
      {
        setTimeout (function() { setSaveType('form_so', '', 'ajax_no_constraint'); }, 500);
        if (hcms_consolelog) console.log('saving face markers ...');
      }

      if (hcms_consolelog) console.log('... video face recognition service finished');

      // call face recognition service for next asset
      setTimeout (recognizeFacesService, 1000);
    }
    
    video.addEventListener('timeupdate', drawFrame, false);
    video.addEventListener('ended', onend, false);
    video.addEventListener('error', onend, false);
    video.muted = true;
    video.play();
  }
  ";

  if (!empty ($recognizefaces_service) && is_video ($mediafile)) $viewstore .= "
  // call video face recognition service after page has been loaded
  window.onload = recognizeFacesOnVideoService;
  ";

  $viewstore .= "
  // recognize or detect faces on image
  async function detectFaceOnImage ()
  {
    var count = 0;
    detectface = true;

    // if not an img tag
    // tag id hcms_mediaplayer_asset has the original media source
    if (document.getElementById('hcms_mediaplayer_asset').tagName != 'IMG') return false;

    if (hcms_consolelog) console.log('detecting faces on image ...');

    // use existing face defintions
    if (faces_json != '' || faces_json.length > 0)
    {
      // remove existing face markers
      if ($('#hcms_mediaplayer_asset').length)
      {
        $('.hcmsFace').remove();
        $('.hcmsFaceName').remove();
      }  

      if (typeof faces_json === 'string') var faces = JSON.parse (faces_json);
      else var faces = faces_json;

      // width of displayed annotation image
      if (document.getElementById('annotation') && document.getElementById('annotation').style.width)
      {
        var mediawidth = document.getElementById('annotation').style.width;
      }
      // width of displayed preview image
      else if (document.getElementById('hcms_mediaplayer_asset').style.width)
      {
        var mediawidth = document.getElementById('hcms_mediaplayer_asset').style.width;
      }
      else var mediawidth = 0;

      for (var i = 0; i < faces.length; i++)
      {
        if (faces[i].name != '')
        {
          // calculate scaling ratio if the image size has been changed (displayed image width / image width from JSON faces)
          if (parseFloat(mediawidth) > 0 && parseFloat(faces[i].imagewidth) > 0)
          {
            var scale = parseFloat(mediawidth) / parseFloat(faces[i].imagewidth);
          }
          else var scale = 1;
          
          // log scaling ratio
          if (hcms_consolelog) console.log('scale of existing face = ' + parseFloat(mediawidth) + ' / ' + parseFloat(faces[i].imagewidth) + ' = ' + scale);

          // verified face
          var dragresize = true;
          if (faces[i].face && faces[i].face == 1) var dragresize = false;

          createFaceMarkerOnImage ('hcms_mediaplayer_asset', i, (faces[i].x * scale), (faces[i].y * scale), (faces[i].width * scale), (faces[i].height * scale), faces[i].name, false, dragresize);

          count = i;
        }
      }
    }

    // recognize or detect faces automatically
    if (document.getElementById('hcms_mediaplayer_asset'))
    {
      // width of displayed annotation image
      if (document.getElementById('annotation') && document.getElementById('annotation').style.width)
      {
        var mediawidth = document.getElementById('annotation').style.width;
      }
      // width of displayed preview image
      else if (document.getElementById('hcms_mediaplayer_asset').style.width)
      {
        var mediawidth = document.getElementById('hcms_mediaplayer_asset').style.width;
      }
      else var mediawidth = 0;

      // calculate scaling ratio if the image size has been changed (display image width / original image width)
      if (parseFloat(mediawidth) > 0 && parseFloat(document.getElementById('hcms_mediaplayer_asset').naturalWidth) > 0)
      {
        var scale = parseFloat(mediawidth) / parseFloat(document.getElementById('hcms_mediaplayer_asset').naturalWidth);
      }
      else var scale = 1;

      // log scaling ratio
      if (hcms_consolelog) console.log('scale of detected face = ' + parseInt(mediawidth) + ' / ' + parseInt(document.getElementById('hcms_mediaplayer_asset').naturalWidth) + ' = ' + scale);
      ";

      if (is_facerecognition ("sys")) $viewstore .= "
      // recognize faces
      var element = document.getElementById('hcms_mediaplayer_asset');

      if (element)
      {
        var faces = await recognizeFacesOnImage (element);

        if (faces && faces.length > 0)
        {
          var savemarker = false;

          for (var i = 0; i < faces.length; i++)
          {
            // continue counter for the tag id
            var j = count + 1 + i;

            if (document.getElementById('Face' + j)) continue;

            var marker = createFaceMarkerOnImage ('hcms_mediaplayer_asset', j, (faces[i].x * scale), (faces[i].y * scale), (faces[i].width * scale), (faces[i].height * scale), faces[i].label, true, false);
            if (marker == true && faces[i].label != '') savemarker = true;

            if (hcms_consolelog) console.log('recognized \"' + faces[i].label + '\" (ID:' + j + ') with a distance of ' + faces[i].distance + ' on image with coordinates x:' + (faces[i].x * scale) + ' y:' + (faces[i].y * scale));
          }

          // save without input verification
          if (savemarker == true) 
          {
            setSaveType('form_so', '', 'ajax_no_constraint');
          }
        }
      }
      ";

    $viewstore .= "
    }

    // call service
    ".(!empty ($recognizefaces_service) ? "recognizeFacesService();" : "")."
  }

  // initialize faces on video
  function initFaceOnVideo (type)
  {
    if (typeof (type) === 'undefined') type = '';

    // if not a video tag (same id can be used for img tag)
    if ($('#hcms_mediaplayer_asset').is('img')) return false;

    // remove existing face selectors
    $('#hcmsFaceSelector').remove();

    // remove existing face markers
    if (type == 'all' && ($('#hcms_mediaplayer_asset_html5_api').length || $('#hcms_mediaplayer_asset_flash_api').length))
    {
      $('.hcmsFace').css('visibility', 'hidden');
      $('.hcmsFaceName').css('visibility', 'hidden');
    }

    // use existing face defintions
    if (faces_json != '' || faces_json.length > 0)
    {
      if (typeof faces_json === 'string') var faces = JSON.parse (faces_json);
      else var faces = faces_json;

      // sort by name
      faces = sortObjectValue (faces, 'name', true);

      // get video width
      if ($('#hcms_mediaplayer_asset_html5_api').length > 0)
      {
        var videowidth = $('#hcms_mediaplayer_asset_html5_api').innerWidth();
        var videotag_id = 'hcms_mediaplayer_asset_html5_api';
      }
      else if ($('#hcms_mediaplayer_asset_flash_api').length > 0)
      {
        var videowidth = $('#hcms_mediaplayer_asset_flash_api').innerWidth();
        var videotag_id = 'hcms_mediaplayer_asset_flash_api';
      }

      if (videotag_id != '' && faces.length > 0)
      {
        // display face name selector
        var html = '<div id=\"hcmsFaceSelector\" style=\"width:' + videowidth + 'px; max-height:100px; margin-bottom:4px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\"><div style=\"float:left; padding:2px;\">".getescapedtext ($hcms_lang['search'][$lang], $charset, $lang)." <img src=\"".getthemelocation()."img/button_history_forward.png\" class=\"hcmsIconList\" /></div>';

        for (var i = 0; i < faces.length; i++)
        {
          if (faces[i].time != '' && faces[i].name != '' && faces[i].name !== undefined)
          {
            html += '<div class=\"hcmsButton\" onclick=\"jumpToFaceOnVideo(' + faces[i].time + ');\">' + faces[i].name;
            if (i+1 < faces.length) html += ', ';
            html += '</div>';
          }
        }

        html += '</div>';

        $(html).insertBefore('#videoplayer_container');

        if (type == 'all')
        {
          // existing face definitions
          for (var i = 0; i < faces.length; i++)
          {
            if (faces[i].time != '' && faces[i].name != '')
            {
              var time_id = faces[i].time.toString().replace ('.', '_');
              var id = i + '_' + time_id;

              if (typeof faces[i].link == 'undefined') faces[i].link = '';

              // calculate scaling ratio if the video size has been changed (original width / video width from JSON faces)
              ".(!empty ($mediawidth) ? "if (faces[i].videowidth) var scale = parseFloat(".$mediawidth.") / parseFloat(faces[i].videowidth);" : "var scale = 1;")."

              // verified face
              var dragresize = true;
              if (faces[i].face && faces[i].face == 1) var dragresize = false;

              if (hcms_consolelog) console.log('scale = ' + parseInt(".$mediawidth.") + '/' + parseInt(faces[i].videowidth));

              createFaceMarkerOnVideo (videotag_id, id, faces[i].time, (faces[i].x * scale), (faces[i].y * scale), (faces[i].width * scale), (faces[i].height * scale), faces[i].name, faces[i].link, 'hide', false, dragresize);
            }
          }
        }
      }
    }
  }

  // detect face on video
  async function detectFaceOnVideo ()
  {
    // find video ID (video in it's original size)
    if ($('#hcms_mediaplayer_asset_html5_api').length > 0)
    {
      var video = $('#hcms_mediaplayer_asset_html5_api');
      var videotag_id = 'hcms_mediaplayer_asset_html5_api';
    }
    else if ($('#hcms_mediaplayer_asset_flash_api').length > 0)
    {
      var video = $('#hcms_mediaplayer_asset_flash_api');
      var videotag_id = 'hcms_mediaplayer_asset_flash_api';
    }
    else return false;

    // video frame (display size)
    if ($('#hcms_mediaplayer_asset').length > 0) var videoframe = $('#hcms_mediaplayer_asset');
    else var videoframe = false;

    if (hcms_consolelog) console.log('detecting faces on video ...');

    // detect faces automatically
    if (video)
    {
      if (video[0].paused)
      {
        video[0].play();
        $('.hcmsFace').css('visibility', 'hidden');
        $('.hcmsFaceName').css('visibility', 'hidden');
        return;
      }
      else
      {
        video[0].pause();
      }

      // scaling if the video size has been changed (display size / original)
      ".(!empty ($mediawidth) ? "if (video[0].videoWidth) var scale = parseFloat(".$mediawidth.") / parseFloat(video[0].videoWidth);" : "var scale = 1;")."

      if (hcms_consolelog) console.log('scale = ' + parseFloat(".$mediawidth.") + '/' + parseFloat(video[0].videoWidth));

      // create face id
      var time = setPlayerTime('');
      var time_id = time.toString().replace ('.', '_');
      ";

      if (is_facerecognition ("sys")) $viewstore .= "
      // recognize faces
      var element = document.getElementById(videotag_id);

      if (element)
      {
        if (hcms_consolelog) console.log('analyzing video frame ...');
        var faces = await recognizeFacesOnVideo (element);
        if (hcms_consolelog) console.log('... done');

        if (faces && faces.length > 0)
        {
          var savemarker = false;

          for (var i = 0; i < faces.length; i++)
          {
            var id = i + '_' + time_id;

            var marker = createFaceMarkerOnVideo (videotag_id, id, time, (faces[i].x * scale), (faces[i].y * scale), (faces[i].width * scale), (faces[i].height * scale), faces[i].label, '', 'show', false, false);
            if (marker == true && faces[i].label != '') savemarker = true;

            if (hcms_consolelog) console.log('recognized \"' + faces[i].label + '\" (ID:' + id + ') with a distance of ' + faces[i].distance + ' on video with coordinates x:' + (faces[i].x * scale) + ' y:' + (faces[i].y * scale));
          }

          // save without input verification
          if (savemarker == true) 
          {
            if (hcms_consolelog) console.log('saving ...');
            setSaveType('form_so', '', 'ajax_no_constraint');
          }
        }
      }
      ";

    $viewstore .= "
    }

    // call service
    ".((!empty ($recognizefaces_service)) ? "recognizeFacesService();" : "")."

    detectface = true;
  }

  // jump to frame in video
  function jumpToFaceOnVideo (time)
  {
    if (typeof faces_json === 'string') var faces = JSON.parse (faces_json);
    else var faces = faces_json;

    // hide all faces
    $('.hcmsFace').css('visibility', 'hidden');
    $('.hcmsFaceName').css('visibility', 'hidden');

    // find video ID
    if ($('#hcms_mediaplayer_asset_html5_api').length > 0) var video = $('#hcms_mediaplayer_asset_html5_api');
    else if ($('#hcms_mediaplayer_asset_flash_api').length > 0) var video = $('#hcms_mediaplayer_asset_flash_api');
    else var video = false;

    video[0].play();

    for (var i = 0; i < videoface_id.length; i++)
    {
      if (videoface_id[i] != '' && videoface_id[i].indexOf('_') > 0)
      {
        var videotime_id = time.toString().replace ('.', '_');
        var start = videoface_id[i].indexOf('_') + 1;
        var facetime_id = videoface_id[i].substring(start);

        if (videotime_id == facetime_id)
        {
          if (video[0].paused)
          {
            // video is already paused
          }
          else
          {
            // pause video
            video[0].pause();

            // set video time
            setTimeout(function() { video[0].currentTime = time; }, 300);
          }

          // show face
          $('#hcmsFace' + videoface_id[i]).css ('visibility', 'visible');
        }
      }
    }
  }

  function hideFaceOnImage ()
  {
    $('.hcmsFace').css('visibility', 'hidden');
    $('.hcmsFaceName').css('visibility', 'hidden');
  }

  function showFaceOnImage ()
  {
    $('.hcmsFace').css('visibility', 'visible');
  }

  function hideFaceOnVideo ()
  {
    // find video ID
    if ($('#hcms_mediaplayer_asset_html5_api').length > 0) var video = $('#hcms_mediaplayer_asset_html5_api');
    else if ($('#hcms_mediaplayer_asset_flash_api').length > 0) var video = $('#hcms_mediaplayer_asset_flash_api');
    else var video = false;

    // hide all visible face markers
    if (!video[0].paused)
    {
      $('.hcmsFace').css('visibility', 'hidden');
      $('.hcmsFaceName').css('visibility', 'hidden');
    }
  }

  // create face marker on image by mouse click or touch
  function createFaceOnImage (event, tag_id)
  {
    var markerwidth = 70;
    var markerheight = 70;

    if (imageface_id.length > 0) var id = imageface_id[imageface_id.length - 1] + 1;
    else var id = 0;

    if (tag_id != '' && clickevent == '')
    {
      // get image width and height
      if (document.getElementById('annotation'))
      {
        var image = document.getElementById('annotation');
        var imagetag_id = 'annotation';
      }
      else
      {
        var image = document.getElementById('hcms_mediaplayer_asset');
        var imagetag_id = 'hcms_mediaplayer_asset';
      }

      var imagewidth = image.offsetWidth;
      var imageheight = image.offsetHeight;

      // get mouse position
      var pos_x = event.offsetX?(event.offsetX):event.pageX-document.getElementById(tag_id).offsetLeft;
      var pos_y = event.offsetY?(event.offsetY):event.pageY-document.getElementById(tag_id).offsetTop;

      // verify limits for click on annotion toolbar buttons
      if (pos_x > 35 && pos_y > 35)
      {
        // verify borders
        if (pos_x < (markerwidth / 2)) pos_x = markerwidth / 2;
        if (pos_y < (markerheight / 2)) pos_y = markerheight / 2;
        if (pos_x > (imagewidth - (markerwidth / 2))) pos_x = imagewidth - (markerwidth / 2);
        if (pos_y > (imageheight - (markerheight / 2))) pos_y = imageheight - (markerheight / 2);

        // correct x/y coordinates for rectangle
        pos_x = pos_x - (markerwidth / 2);
        pos_y = pos_y - (markerheight / 2);

        if (pos_x >= 0 && pos_x <= (imagewidth - markerwidth) && pos_y >= 0 && pos_y <= (imageheight - markerheight))
        {
          $('<div>', {
            'id': 'hcmsFace' + id,
            'class': 'hcmsFace',
            'onclick': '$(\"#hcmsFace' + id + '\").draggable().resizable(); switchFaceName(\"hcmsFaceName' + id + '\")',
            'onresize': 'resizeFaceName(\"' + id + '\");',
            'ondrag': 'resizeFaceName(\"' + id + '\");',
            'css': {
              'position': 'absolute',
              'left': pos_x + 'px',
              'top': pos_y + 'px',
              'width': markerwidth + 'px',
              'height': markerheight + 'px'
            }
          })
          .insertAfter('#hcms_mediaplayer_asset');

          imageface_id.push(id);
          var offset = (216 - markerwidth) / 2;

          // label and form
          $(\"<div id='hcmsFaceName\" + id + \"' onclick='clickFaceName();' class='hcmsInfoBox hcmsFaceName' style='visibility:visible; white-space:nowrap; position:absolute; top:\" + (pos_y + markerheight + 6) +\"px; left:\" + (pos_x - offset) + \"px;'><input type='hidden' id='facedetails\" + id + \"' value='\\\"face\\\":0' /><textarea type='text' id='facename\" + id + \"' placeholder='".getescapedtext ($hcms_lang['name'][$lang], $charset, $lang)."' class='hcmsTextArea' style='width:200px; height:32px;'></textarea> <img src='".getthemelocation()."img/button_delete.png' class='hcmsButtonTiny hcmsButtonSizeSquare' align='absmiddle' onmousedown=\\\"deleteFace('\" + id + \"');\\\" title='".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."' /></div>\").insertAfter($('#hcmsFace' + id));
        }
      }
    }
  }

  // create face marker on video by mouse click or touch
  function createFaceOnVideo (event)
  {
    var markerwidth = 70;
    var markerheight = 70;

    // get video width, height and tag ID
    if ($('#hcms_mediaplayer_asset_html5_api').length > 0)
    {
      var videowidth = $('#hcms_mediaplayer_asset_html5_api').innerWidth();
      var videoheight = $('#hcms_mediaplayer_asset_html5_api').innerHeight();
      var videotag_id = 'hcms_mediaplayer_asset_html5_api';
    }
    else if ($('#hcms_mediaplayer_asset_flash_api').length > 0)
    {
      var videowidth = $('#hcms_mediaplayer_asset_flash_api').innerWidth();
      var videoheight = $('#hcms_mediaplayer_asset_html5_api').innerHeight();
      var videotag_id = 'hcms_mediaplayer_asset_flash_api';
    }

    // get video time
    var time = setPlayerTime('');

    if (videotag_id != '' && clickevent == '' && time > 0)
    {
      // pause video
      $('#' + videotag_id)[0].pause();

      // get mouse position
      var pos_x = event.offsetX?(event.offsetX):event.pageX-document.getElementById(videotag_id).offsetLeft;
      var pos_y = event.offsetY?(event.offsetY):event.pageY-document.getElementById(videotag_id).offsetTop;

      // verify limits for click on annotion toolbar buttons
      if (pos_x > 35 && pos_y > 35)
      {
        // verify borders
        if (pos_x < (markerwidth / 2)) pos_x = markerwidth / 2;
        if (pos_y < (markerheight / 2)) pos_y = markerheight / 2;
        if (pos_x > (videowidth - (markerwidth / 2))) pos_x = videowidth - (markerwidth / 2);
        if (pos_y > (videoheight - (markerheight / 2))) pos_y = videoheight - (markerheight / 2);

        // correct x/y coordinates for rectangle
        pos_x = pos_x - (markerwidth / 2);
        pos_y = pos_y - (markerheight / 2);

        if (pos_x >= 0 && pos_x <= (videowidth - markerwidth) && pos_y >= 0 && pos_y <= (videoheight - markerheight))
        {
          createFaceMarkerOnVideo (videotag_id, '', time, pos_x, pos_y, markerwidth, markerheight, '', '', 'show', false, true);
        }
      }
    }
  }
  
  // resize/reposition face name
  function resizeFaceName (id)
  {
    if (typeof zoommemory !== 'undefined' && zoommemory > 0) var scale = 1 / zoommemory;
    else var scale = 1;

    var faceframe = document.getElementById('hcmsFace' + id);
    var facename = document.getElementById('hcmsFaceName' + id);

    if (faceframe && facename)
    {
      var x = faceframe.offsetLeft * scale;
      var y = faceframe.offsetTop * scale;
      var markerwidth = faceframe.clientWidth * scale;
      var markerheight = faceframe.clientHeight * scale;
      var offset = (216 - markerwidth) / 2;

      facename.style.top = y + markerheight + 6 + 'px';
      facename.style.left = x - offset + 'px';
    }
  }

  // display or hide face name
  function switchFaceName (id)
  {
    // uses visibilty
    var selector = document.getElementById(id);

    // get video tag id
    if ($('#hcms_mediaplayer_asset_html5_api').length > 0) var videotag_id = '#hcms_mediaplayer_asset_html5_api';
    else if ($('#hcms_mediaplayer_asset_flash_api').length > 0) var videotag_id = '#hcms_mediaplayer_asset_flash_api';
    else var videotag_id = '';

    // pause video
    if (videotag_id != '') $(videotag_id)[0].pause();

    // keeps the rest of the handlers from being executed
    clickevent = 'switchFaceName';

    if (selector)
    {
      if (selector.style.visibility == 'hidden') selector.style.visibility = 'visible';
      else selector.style.visibility = 'hidden';
    }

    setTimeout(function() { clickevent = ''; }, 500);
  }

  // click on face action
  function clickFaceName ()
  {
    // get video tag id
    if ($('#hcms_mediaplayer_asset_html5_api').length > 0) var videotag_id = '#hcms_mediaplayer_asset_html5_api';
    else if ($('#hcms_mediaplayer_asset_flash_api').length > 0) var videotag_id = '#hcms_mediaplayer_asset_flash_api';
    else var videotag_id = '';

    // pause video
    if (videotag_id != '') $(videotag_id)[0].pause();

    // keeps the rest of the handlers from being executed
    clickevent = 'clickFaceName';

    setTimeout(function() { clickevent = ''; }, 500);
  }

  // resize all existing faces
  function resizeFaces (scale)
  {
    if (typeof (scale) === 'undefined') scale = 1;
    else if (scale <= 0) scale = 1;

    var elements = document.getElementsByClassName('hcmsFaceName');

    if (elements.length > 0)
    {
      // faces on video
      if ($('#hcms_mediaplayer_asset_html5_api').length > 0 || $('#hcms_mediaplayer_asset_flash_api').length > 0)
      {
        videoface_id = hcms_arrayUnique (videoface_id);

        for (var i = 0; i < videoface_id.length; i++)
        {
          if (document.getElementById('hcmsFace' + videoface_id[i]))
          {
            var faceframe = document.getElementById('hcmsFace' + videoface_id[i]);
            var facename = document.getElementById('hcmsFaceName' + videoface_id[i]);

            // set face dimensions and position
            if (faceframe && (faceframe.offsetTop * scale) > 0 && (faceframe.offsetLeft * scale) > 0)
            {
              faceframe.style.top = (faceframe.offsetTop * scale) + 'px';
              faceframe.style.left = (faceframe.offsetLeft * scale) + 'px';
              faceframe.style.width = (faceframe.clientWidth * scale) + 'px';
              faceframe.style.height = (faceframe.clientHeight * scale) + 'px';

              facename.style.top = (facename.offsetTop * scale) + 'px';
              facename.style.left = (facename.offsetLeft * scale) + 'px';

              if (hcms_consolelog) console.log('repositioned and resized face marker to x = ' + faceframe.style.left + ', y = ' + faceframe.style.top + ', w = ' + faceframe.style.width + ', h = ' + faceframe.style.height + ' using scale = ' + scale);
            }
          }
        }
      }
      // faces on image
      else if ($('#hcms_mediaplayer_asset').length > 0)
      {
        imageface_id = hcms_arrayUnique (imageface_id);

        for (var i = 0; i < imageface_id.length; i++)
        {
          if (document.getElementById('hcmsFace' + imageface_id[i]))
          {
            var faceframe = document.getElementById('hcmsFace' + imageface_id[i]);
            var facename = document.getElementById('hcmsFaceName' + imageface_id[i]);

            // set face dimensions and position
            if (faceframe && (faceframe.offsetTop * scale) > 0 && (faceframe.offsetLeft * scale) > 0)
            {
              faceframe.style.top = (faceframe.offsetTop * scale) + 'px';
              faceframe.style.left = (faceframe.offsetLeft * scale) + 'px';
              faceframe.style.width = (faceframe.clientWidth * scale) + 'px';
              faceframe.style.height = (faceframe.clientHeight * scale) + 'px';

              facename.style.top = (facename.offsetTop * scale) + 'px';
              facename.style.left = (facename.offsetLeft * scale) + 'px';

              if (hcms_consolelog) console.log('repositioned and resized face marker to x = ' + faceframe.style.left + ', y = ' + faceframe.style.top + ', w = ' + faceframe.style.width + ', h = ' + faceframe.style.height + ' using scale = ' + scale);
            }
          }
        }
      }
    }
  }

  // collect all faces for saving
  function collectFaces (type)
  {
    if (typeof (type) === 'undefined') type = '';

    var elements = document.getElementsByClassName('hcmsFaceName');

    if (typeof zoommemory !== 'undefined' && zoommemory > 0) var scale = 1 / zoommemory;
    else var scale = 1;

    if (elements.length > 0)
    {
      // faces on video
      if ($('#hcms_mediaplayer_asset_html5_api').length > 0 || $('#hcms_mediaplayer_asset_flash_api').length > 0)
      {
        if ($('#hcms_mediaplayer_asset_html5_api').length > 0) var video = $('#hcms_mediaplayer_asset_html5_api');
        else if ($('#hcms_mediaplayer_asset_flash_api').length > 0) var video = $('#hcms_mediaplayer_asset_flash_api');

        // display width and height
        var videowidth = video.innerWidth() * scale;
        var videoheight = video.innerHeight() * scale;

        var faces = [];
        var j = 0;

        videoface_id = hcms_arrayUnique (videoface_id);

        for (var i = 0; i < videoface_id.length; i++)
        {
          if ($('#facedetails' + videoface_id[i]).length > 0 && ($('#facename' + videoface_id[i]).val() != '' || type == 'all'))
          {
            // get face frame dimensions and position
            var faceframe = document.getElementById('hcmsFace' + videoface_id[i]);

            if (faceframe)
            {
              var facedetails = $('#facedetails' + videoface_id[i]).val() + ', \"x\":' + (faceframe.offsetLeft * scale) + ', \"y\":' + faceframe.offsetTop + ', \"width\":' + (faceframe.clientWidth * scale) + ', \"height\":' + (faceframe.clientHeight * scale);
              var facename = $('#facename' + videoface_id[i]).val().trim();
              var facelink = $('#facelink' + videoface_id[i]).val().trim();
              faces[j] = '{\"videowidth\":' + videowidth + ', \"videoheight\":' + videoheight + ', ' + facedetails + ', \"name\":' + JSON.stringify(facename) + ', \"link\":' + JSON.stringify(facelink) + '}';

              j++;
            }
          }
        }
      }
      // faces on image
      else if ($('#hcms_mediaplayer_asset').length > 0)
      {
        // displayed annotation image
        if (document.getElementById('annotation') && document.getElementById('annotation').offsetWidth)
        {
          var image = document.getElementById('annotation');
        }
        // displayed preview image
        else if (document.getElementById('hcms_mediaplayer_asset').offsetWidth)
        {
          var image = document.getElementById('hcms_mediaplayer_asset');
        }

        var imagewidth = image.offsetWidth * scale;
        var imageheight = image.offsetHeight * scale;

        var faces = [];
        var j = 0;

        imageface_id = hcms_arrayUnique (imageface_id);

        for (var i = 0; i < imageface_id.length; i++)
        {
          if ($('#facename' + imageface_id[i]).val() != '' || type == 'all')
          {
            // get face frame dimensions and position
            var faceframe = document.getElementById('hcmsFace' + imageface_id[i]);

            if (faceframe)
            {
              var facedetails = $('#facedetails' + imageface_id[i]).val() + ', \"x\":' + (faceframe.offsetLeft * scale) + ', \"y\":' + (faceframe.offsetTop * scale) + ', \"width\":' + (faceframe.clientWidth * scale) + ', \"height\":' + (faceframe.clientHeight * scale);
              var facename = $('#facename' + imageface_id[i]).val().trim();
              faces[j] = '{\"imagewidth\":' + imagewidth + ', \"imageheight\":' + imageheight + ', ' + facedetails + ', \"name\":' + JSON.stringify(facename) + '}';

              j++;
            }
          }
        }
      }

      if (faces.length > 0)
      {
        var unique = hcms_arrayUnique (faces);

        if (type == 'all') return '[' + unique.join(', ') + ']';
        else faces_json = '[' + unique.join(', ') + ']';
      }
      else
      {
        if (type == 'all') return [];
        else faces_json = [];
      }

      // save faces in hidden field
      if (type != 'all') $('#faces').val(faces_json);
    }
    // remove face definitions
    else
    {
      faces_json = [];
      $('#faces').val(faces_json);
    }
  }";
  }

  if ($buildview != "formlock") $viewstore .= "
  ".$autosave_timer."
  ";

  // define VTT records object for JS
  $vtt_records = "{}";
  $vtt_array = array();

  if (!empty ($contentdata))
  {
    $vtt_textnodes = selectcontent ($contentdata, "<text>", "<text_id>", "VTT-*");

    if (is_array ($vtt_textnodes))
    {
      foreach ($vtt_textnodes as $vtt_textnode)
      {
        if (!empty ($vtt_textnode))
        {
          $vtt_id = getcontent ($vtt_textnode, "<text_id>");
          if (!empty ($vtt_id[0])) list ($vtt, $vtt_langcode) = explode ("-", $vtt_id[0]);

          $vtt_string = getcontent ($vtt_textnode, "<textcontent>", true);
        }

        if (!empty ($vtt_string[0]) && !empty ($vtt_langcode))
        {
          $vtt_array[$vtt_langcode] = vtt2array ($vtt_string[0]);
        }
      }
    }
  }

  // json encode array
  if (is_array ($vtt_array) && sizeof ($vtt_array) > 0) $vtt_records = json_encode ($vtt_array);

  // face detection for images (no mobile support, no support of MS IE)
  $add_facedetection = "";

  if (!empty ($mediafile) && (is_facerecognition ("sys") || is_annotation ()))
  {
    // delay for image to load and be displayed
    if (is_image ($mediafile)) $add_facedetection .= "
    if (typeof detectFaceOnImage === 'function') setTimeout (detectFaceOnImage, 800);";
    elseif (is_video ($mediafile)) $add_facedetection .= "
    if (typeof initFaceOnVideo === 'function') setTimeout (\"initFaceOnVideo('all')\", 500);";
  }

  // onload event / document ready
  $viewstore .= "
  ".$bodytag_controlreload."
  
  $(document).ready(function() {

    // JQuery UI tooltip
    $(document).tooltip({
      items: '#annotationHelp'
    });

    // Protect images
    $('#annotation').bind('contextmenu', function(e){
        return false;
    });
    $('img').bind('contextmenu', function(e){
        return false;
    });

    // Execute onload events
    ".$add_onload."

    // Execute code from template
    ".$js_tpl_code."

    // Face detection (after the image has been loaded)
    $('#hcms_mediaplayer_asset').on('load', function(){ ".$add_facedetection." });

    // fallback if detectface function has not been executed (due to issues with MS Edge)
    if (detectface == false) ".$add_facedetection."

    // clear clickevent
    setTimeout(function() { clickevent = ''; }, 500);
  });

  // global object for VTT records
  var vtt_object = ".$vtt_records.";
  ";

  $viewstore .= "
  </script>";

  $viewstore .= "
</head>

<body class=\"hcmsWorkplaceGeneric\">

<!-- saving -->
<div id=\"saveLayer\" class=\"hcmsLoadScreen\"></div>
";

  if ($buildview != "formlock") $viewstore .= "
  <form action=\"".cleandomain ($mgmt_config['url_path_cms'])."service/savecontent.php\" method=\"post\" name=\"hcms_formview\" id=\"hcms_formview\" accept-charset=\"".$charset."\" enctype=\"application/x-www-form-urlencoded\">
    <input type=\"hidden\" name=\"view\" value=\"".$buildview."\" />
    <input type=\"hidden\" name=\"contenttype\" value=\"".$contenttype."\" />
    <input type=\"hidden\" name=\"site\" value=\"".$site."\" />
    <input type=\"hidden\" name=\"cat\" value=\"".$cat."\" />
    <input type=\"hidden\" name=\"location\" value=\"".$location_esc."\" />
    <input type=\"hidden\" name=\"page\" value=\"".$page."\" />
    <input type=\"hidden\" name=\"contentfile\" value=\"".$contentfile."\" />
    <input type=\"hidden\" name=\"db_connect\" value=\"".$db_connect."\" />
    <input type=\"hidden\" id=\"savetype\" name=\"savetype\" value=\"\" />
    <input type=\"hidden\" name=\"forward\" value=\"\" />
    <input type=\"hidden\" name=\"wf_token\" value=\"".$wf_token."\" />
    <input type=\"hidden\" name=\"token\" value=\"".$token."\" />
    <input type=\"hidden\" name=\"service\" value=\"".(!empty ($recognizefaces_service) ? "recognizefaces" : "savecontent")."\" />
    ".((!empty ($recognizefaces_service) && substr ($user, 0, 4) == "sys:") ? "<input type=\"hidden\" name=\"PHPSESSID\" value=\"".session_id()."\" />" : "")."
    <input type=\"hidden\" name=\"medianame\" id=\"medianame\" value=\"\" />
    <input type=\"hidden\" name=\"mediadata\" id=\"mediadata\" value=\"\" />
    <input type=\"hidden\" name=\"faces\" id=\"faces\" value=\"\" />
    ";

    $viewstore .= "
    <!-- top bar -->
    <div id=\"bar\" class=\"hcmsWorkplaceBar\">
      <table style=\"width:100%; height:100%; padding:0; border-spacing:0; border-collapse:collapse;\">
        <tr>
          <td class=\"hcmsHeadline\" style=\"text-align:left; vertical-align:middle; padding:0px 1px 0px 2px\">\n";

        // save buttons
        if ($buildview == "formlock")
        {
          $viewstore .= "<img name=\"Button_so\" src=\"".getthemelocation()."img/button_save.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
          if (($mediafile == false || $mediafile == "") && $page != ".folder" && $objectview != "formedit" && $objectview != "formmeta" && $objectview != "formlock") $viewstore .= "<img name=\"Button_sc\" src=\"".getthemelocation()."img/button_saveclose.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
        }
        else
        {
          $viewstore .= "<img name=\"Button_so\" src=\"".getthemelocation()."img/button_save.png\" class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"setSaveType('form_so', '', 'ajax');\" alt=\"".getescapedtext ($hcms_lang['save'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['save'][$lang], $charset, $lang)."\" />\n";
          if (($mediafile == false || $mediafile == "") && $page != ".folder" && $objectview != "formedit" && $objectview != "formmeta" && $objectview != "formlock") $viewstore .= "<img name=\"Button_sc\" src=\"".getthemelocation()."img/button_saveclose.png\" class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"setSaveType('form_sc', '', 'post');\" alt=\"".getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang)."\" />\n";
        }

        // print button
        $viewstore .= "<img src=\"".getthemelocation()."img/button_print.png\" class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"window.print();\" alt=\"".getescapedtext ($hcms_lang['print'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['print'][$lang], $charset, $lang)."\" />\n";

        // autosave checkbox
        if (intval ($mgmt_config['autosave']) > 0 && ($buildview == "formedit" || $buildview == "formmeta")) $viewstore .= "<label for=\"autosave\"><div class=\"hcmsButton hcmsButtonSizeHeight\" style=\"line-height:32px;\">&nbsp;<input type=\"checkbox\" id=\"autosave\" name=\"autosave\" value=\"yes\" checked=\"checked\" />&nbsp;".getescapedtext ($hcms_lang['autosave'][$lang], $charset, $lang)."&nbsp;</div></label>\n";
        else $viewstore .= "<div class=\"hcmsButtonOff hcmsButtonSizeHeight\" style=\"line-height:32px;\">&nbsp;<input type=\"checkbox\" id=\"autosave\" name=\"autosave\" value=\"\" disabled=\"disabled\" />&nbsp;".getescapedtext ($hcms_lang['autosave'][$lang], $charset, $lang)."&nbsp;</div>\n";

        $viewstore .= "</td>\n";

        // close button
        if (($mediafile == false || $mediafile == "" || $application == "generator") && $page != ".folder" && $objectview != "formedit" && $objectview != "formmeta" && $objectview != "formlock" || $buildview != "formedit")
        {
          if ($buildview == "formlock") $viewstore .= "<td style=\"width:32px; text-align:right; vertical-align:middle; padding-right:2px;\"><img name=\"mediaClose\" src=\"".getthemelocation()."img/button_close.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" /></td>\n";
          else $viewstore .= "<td style=\"width:32px; text-align:right; vertical-align:middle; padding-right:2px;\"><a href=\"".cleandomain ($mgmt_config['url_path_cms'])."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."\" target=\"objFrame\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('mediaClose','','".getthemelocation()."img/button_close_over.png',1);\"><img name=\"mediaClose\" src=\"".getthemelocation()."img/button_close.png\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['close'][$lang], $charset, $lang)."\" /></a></td>\n";
        }

        $viewstore .= "
        </tr>
      </table>
    </div>
    <div style=\"width:100%; height:32px;\">&nbsp;</div>\n";

        // include share links for image and video files
        if (is_dir ($mgmt_config['abs_path_cms']."connector/") && !empty ($mgmt_config[$site]['sharesociallink']) && empty ($recognizefaces_service) && $mediafile != "" && (is_image ($mediafile) || is_video ($mediafile) || is_audio ($mediafile)) && $buildview != "formlock")
        {
          $sharelink = createwrapperlink ($site, $location, $page, "comp");

          $viewstore .= showsharelinks ($sharelink, $mediafile, $lang, "position:fixed; top:50px; right:12px; width:46px;");
        }

        // table for form
        $viewstore .= "
      <!-- form for content -->
      <div class=\"hcmsWorkplaceFrame\">";

        // add preview of media file (for media view the characters set is always UTF-8)
        if ($mediafile != false && $mediafile != "")
        {
          if (($buildview == "formedit" || $buildview == "formmeta") && empty ($recognizefaces_service)) $mediaview = "preview";
          else $mediaview = "preview_no_rendering";

          $views = rdbms_externalquery ('SELECT SUM(dailystat.count) AS count FROM dailystat WHERE id='.intval($container_id).' AND activity="view"');
          $downloads = rdbms_externalquery ('SELECT SUM(dailystat.count) AS count FROM dailystat WHERE id='.intval($container_id).' AND activity="download"');
          $uploads = rdbms_externalquery ('SELECT SUM(dailystat.count) AS count FROM dailystat WHERE id='.intval($container_id).' AND activity="upload"');

          if (empty ($views[0]['count'])) $views[0]['count'] = 0;
          if (empty ($downloads[0]['count'])) $downloads[0]['count'] = 0;
          if (empty ($uploads[0]['count'])) $uploads[0]['count'] = 0;

          $viewstore .= "
        <!-- preview -->
        <div id=\"preview\">
          <div style=\"clear:both;\"></div>
          <div class=\"hcmsFormRowLabel\">
            <b>".getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang)."&nbsp;&nbsp;</b>
            <span class=\"hcmsTextSmall\" style=\"white-space:nowrap;\">
              <img src=\"".getthemelocation()."img/button_file_liveview.png\" class=\"hcmsIconList\" /> ".$views[0]['count']." ".getescapedtext ($hcms_lang['views'][$lang], $charset, $lang)."
              &nbsp;&nbsp;<img src=\"".getthemelocation()."img/button_file_download.png\" class=\"hcmsIconList\" /> ".$downloads[0]['count']." ".getescapedtext ($hcms_lang['downloads'][$lang], $charset, $lang)."
              &nbsp;&nbsp;<img src=\"".getthemelocation()."img/button_file_upload.png\" class=\"hcmsIconList\" /> ".$uploads[0]['count']." ".getescapedtext ($hcms_lang['uploads'][$lang], $charset, $lang)."
            </span>
          </div>
          <div class=\"hcmsFormRowContent\">
            ".showmedia ($site."/".$mediafile, convertchars ($name_orig, $hcms_lang_codepage[$lang], $charset), $mediaview, "hcms_mediaplayer_asset", $mediawidth, "", "hcmsImageItem", $recognizefaces_service)."
          </div>
        </div>";
        }

        $viewstore .= "
      <!-- form  -->
      <div id=\"settings\">
        <div style=\"clear:both;\"></div>";

        if (isset ($formitem) && is_array ($formitem))
        {
          // sort form items by its position/key
          ksort ($formitem);
          reset ($formitem);

          foreach ($formitem as $buffer)
          {
            $viewstore .= $buffer;
          }
        }

        $viewstore .= "
        <div style=\"display:block; clear:both; height:10px;\"></div>
      </div>
    </div>";

        if ($buildview != "formlock") $viewstore .= "
  </form>";

  $viewstore .= "
</body>
</html>";
      }

    // ==================================== remove hyperCMS stylesheet tags in template ==================================

    if ($buildview == "publish" || $buildview == "unpublish" || $buildview == "template" || ($buildview == "preview" && $ctrlreload != "yes"))
    {
      $hypertag_array = gethypertag ($viewstore, "compstylesheet", 0);

      if (empty ($recognizefaces_service) && is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
      {
        foreach ($hypertag_array as $hypertag)
        {
          $viewstore = str_replace ($hypertag, "", $viewstore);
        }
      }
    }

      // ====================================== Adding Headers for Video Player ============================================

      // for all views except template view
      if ($buildview != "template")
      {
        // We only add the video player library files if a video is used (Projekktor or VIDEO.JS Player)
        if (preg_match('/\<video.*?id=[\"\\\']hcms_mediaplayer_/i', $viewstore) || preg_match('/\<video.*?id=[\"\\\']hcms_projekktor_/i', $viewstore) || preg_match('/\<video.*?id=[\"\\\']hcms_videojs_/i', $viewstore))
        {
          if (substr_count (strtolower ($viewstore), "</head>") > 0)
          {
            $viewstore = preg_replace ("/\<\/head\>/i", showvideoplayer_head (false, false, true)."</head>", $viewstore);
          }
          elseif (substr_count (strtolower ($viewstore), "<body") > 0)
          {
            $bodytagold = gethtmltag ($viewstore, "<body");
            $viewstore = str_replace ($bodytagold, $bodytagold."\n".showvideoplayer_head (false, false, true), $viewstore);
          }
          elseif (substr_count (strtolower ($viewstore), ":body") > 0)
          {
            $bodytagold = gethtmltag ($viewstore, ":body");
            $viewstore = str_replace ($bodytagold, $bodytagold."\n".showvideoplayer_head (false, false, true), $viewstore);
          }
        }

        // We only add if a audio is used (audio.js Player) and VIDEO.JS has not been integrated already
        if (preg_match('/\<audio.*?id=[\"\\\']hcms_mediaplayer_/i', $viewstore) && substr_count ($viewstore, "javascript/video-js/video.min.js") == 0)
        {
           if (substr_count (strtolower ($viewstore), "</head>") > 0)
          {
            $viewstore = preg_replace ("/\<\/head\>/i", showaudioplayer_head (false)."</head>", $viewstore);
          }
          elseif (substr_count (strtolower ($viewstore), "<body") > 0)
          {
            $bodytagold = gethtmltag ($viewstore, "<body");
            $viewstore = str_replace ($bodytagold, $bodytagold."\n".showaudioplayer_head (false), $viewstore);
          }
          elseif (substr_count (strtolower ($viewstore), ":body") > 0)
          {
            $bodytagold = gethtmltag ($viewstore, ":body");
            $viewstore = str_replace ($bodytagold, $bodytagold."\n".showaudioplayer_head (false), $viewstore);
          }
        }
      }

      // save log
      savelog (@$error);
    }
    // if view is not allowed due to workflow
    else
    {
      return false;
    }
  }
  // if contentfile or template file is missing
  else
  {
    return false;
  }

  // eventsystem
  if (!empty ($eventsystem['oneditobject_post']) && $eventsystem['hide'] == 0 && ($buildview == "cmsview" || $buildview == 'inlineview'))
  {
    // include hyperCMS Event System
    @include_once ($mgmt_config['abs_path_data']."eventsystem/hypercms_eventsys.inc.php");
    oneditobject_post ($site, $cat, $location, $page, $user);
  }

  // error occured if error comments can be found
  if (isset ($viewstore) && strpos ("_".$viewstore, "<!-- hyperCMS:Error") > 0) $result['result'] = false;
  else $result['result'] = true;

  // return result array
  $result['charset']= $charset;
  $result['view'] = $viewstore;
  $result['release'] = $wf_role;
  $result['workflow_token'] = $wf_token;
  $result['container'] = $contentfile;
  $result['containerdata'] = $contentdata;
  $result['template'] = $templatefile;
  $result['templatedata'] = $templatedata;
  $result['templateext'] = $templateext;
  $result['application'] = $application;
  $result['publication'] = $site;
  $result['location'] = $location;
  $result['object'] = $page;
  $result['name'] = $name_orig;
  $result['objecttype'] = $filetype;

  return $result;
}

// --------------------------------- unescapeview -------------------------------------------
// function: unescapeview()
// input: code [string], application name [string] (optional)
// output: unescaped code / false on error

function unescapeview ($viewstore, $application="php")
{
  if ($viewstore != "")
  {
    $viewstore = str_replace ("[hypercms:xmlbegin", "<?", $viewstore);
    $viewstore = str_replace ("xmlend]", "?>", $viewstore);
    $viewstore = str_replace ("[hyperCMS:skip", tpl_tagbegin ($application), $viewstore);
    $viewstore = str_replace ("skip]", tpl_tagend ($application), $viewstore);
    $viewstore = str_replace ("[hyperCMS:scriptbegin", "<?php", $viewstore);
    $viewstore = str_replace ("scriptend]", "?>", $viewstore);

    return $viewstore;
  }
  else return false;
}

// --------------------------------- buildsearchform -------------------------------------------
// function: buildsearchform()
// input: publication name [string] (optional for report), template name [string] (optional), or report name [string] (optional), group access [array] (optional), 
//        CSS display value for label tag [string] (optional), CSS field width (optional), allow empty values [boolean] (optional), display title [string] (optional)
// output: form view / false on error

function buildsearchform ($site="", $template="", $report="", $ownergroup="", $css_display="inline-block", $css_width_field="90%", $empty_values=true, $title="")
{
  global $user, $siteaccess, 
         $mgmt_config, $mgmt_lang_shortcut_default, $hcms_charset, $hcms_lang_name, $hcms_lang_shortcut, $hcms_lang_codepage, $hcms_lang_date, $hcms_lang, $lang,
         $is_mobile;

  // ----------------------------------- build view of page -----------------------------------------

  // load template
  if (valid_publicationname ($site) && valid_objectname ($template))
  {
    // load template xml file and read information
    $result = loadtemplate ($site, $template);

    $templatedata = $result['content'];
    $templatesite = $result['publication'];

    $bufferdata = getcontent ($templatedata, "<content>", true);

    // add newline at the begin to correct errors in tag-search
    if (!empty ($bufferdata[0])) $viewstore = "\n".$bufferdata[0];
  }
  // load report
  elseif (valid_objectname ($report))
  {
    // load report file and read information
    $result = loadreport ($report);

    if (!empty ($result['sql'])) $viewstore = $result['sql'];
  }

  if (!empty ($viewstore))
  {
    // =================================================== text content ===================================================
    $searchtag_array = array();
    $searchtag_array[0] = "arttext";
    $searchtag_array[1] = "text";
    $searchtag_array[2] = "linkhref";
    $searchtag_array[3] = "mediafile";
    $id_array = array();

    foreach ($searchtag_array as $searchtag)
    {
      // get all hyperCMS tags
      $hypertag_array = gethypertag ($viewstore, $searchtag, 0);

      if (is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
      {
        reset ($hypertag_array);

        // loop for each hyperCMS tag found in template
        foreach ($hypertag_array as $key => $hypertag)
        {
          // get tag name
          $hypertagname = gethypertagname ($hypertag);

          // get tag id
          $id = getattribute ($hypertag, "id");

          // if id uses special characters
          if (trim ($id) != "" && specialchr ($id, ":-_") == true)
          {
            $result['view'] = "<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset=\"".getcodepage ($lang)."\" />
<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />
<link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />
</head>
<body class=\"hcmsWorkplaceGeneric\">
  <p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters-in-the-content-identification-name'][$lang], $charset, $lang)." '".$id."':<br/>[\]{}()*+?.,\\^$</p>
</body>
</html>";

            $result['release'] = 0;
            $result['container'] = $contentfile;
            $result['containerdata'] = $contentdata;
            $result['template'] = $templatefile;
            $result['templatedata'] = $templatedata;
            $result['templateext'] = $templateext;
            $result['name'] = $name_orig;
            $result['objecttype'] = $filetype;

            return $result;
          }

          // get label
          $label = getattribute ($hypertag, "label");

          // get format (if date)
          $format = getattribute ($hypertag, "format");
          if ($format == "") $format = "%Y-%m-%d";

          // get group access
          $groupaccess = getattribute ($hypertag, "groups");
          $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);

          if ($label == "") $label = $id;

          // id must be unique
          if (!in_array ($id, $id_array) && $groupaccess == true)
          {
            // search field for formatted and unformatted text
            if ($hypertagname == $searchtag."u" || $hypertagname == $searchtag."f" || $hypertagname == $searchtag."k")
            {
              if (@substr_count ($viewstore, $hypertag) > 0)
              {
                $formitem[$key] = "
            <label for=\"text_".$id."\" style=\"display:".$css_display."; width:180px; font-weight:bold;\">".$label." </label>
            <input id=\"text_".$id."\" name=\"search_textnode[".$id."]\" value=\"\" style=\"display:inline !important; width:".$css_width_field."; margin:2px 0px;\" ".(empty ($empty_values) ? "required=\"required\"" : "")." /><br />";
              }
            }
            // search field for text lists (options)
            elseif ($hypertagname == $searchtag."l")
            {
              if (@substr_count ($viewstore, $hypertag) > 0)
              {
                $list = "";

                // extract source file (file path or URL) for text list
                $list_sourcefile = getattribute ($hypertag, "file");

                if ($list_sourcefile != "")
                {
                  $list .= getlistelements ($list_sourcefile);
                  // replace commas by
                  $list = str_replace (",", "|", $list);
                }

                // extract text list
                $list_add = getattribute ($hypertag, "list");

                // add seperator
                if ($list_add != "") $list = $list_add."|".$list;

                // template variable %publication%
                if (@substr_count ($list, "%publication%") > 0 && !empty ($siteaccess) && is_array ($siteaccess) && sizeof ($siteaccess) > 0)
                { 
                  natcasesort ($siteaccess);
                  $list = str_replace ("%publication%", implode ("|", $siteaccess), $list);
                }

                // get list entries
                if (!empty ($list))
                {
                  $list = rtrim ($list, "|");
                  $list_array = explode ("|", $list);

                  $formitem[$key] = "
            <label for=\"textl_".$id."\" style=\"display:".$css_display."; width:180px; font-weight:bold;\">".$label." </label>
            <select id=\"textl_".$id."\" name=\"search_textnode[".$id."]\" style=\"display:inline !important; width:".$css_width_field."; margin:2px 0px\">";
              
                  if (!empty ($empty_values)) $formitem[$key] .= "
              <option value=\"\">&nbsp;</option>";

                  foreach ($list_array as $list_entry)
                  {
                    $list_entry = trim ($list_entry);
                    $end_val = strlen ($list_entry) - 1;
 
                    if (($start_val = strpos ($list_entry, "{")) > 0 && strpos ($list_entry, "}") == $end_val)
                    {
                      $diff_val = $end_val-$start_val-1;
                      $list_value = substr ($list_entry, $start_val+1, $diff_val);
                      $list_text = substr ($list_entry, 0, $start_val);
                    }
                    else $list_value = $list_text = $list_entry;

                    $formitem[$key] .= "
                    <option value=\"".$list_value."\">".$list_text."</option>";
                  }

                  $formitem[$key] .= "
            </select><br />";
                }
              }
            }
            // search field for checked values
            elseif ($hypertagname == $searchtag."c")
            {
              if (@substr_count ($viewstore, $hypertag) > 0)
              {
                // extract text value of checkbox
                $value = getattribute ($hypertag, "value");

                $formitem[$key] = "
            <label for=\"textc_".$id."\" style=\"display:".$css_display."; width:180px; font-weight:bold;\">".$label." </label>
            <input type=\"checkbox\" id=\"textc_".$id."\" name=\"search_textnode[".$id."]\" value=\"".$value."\" style=\"margin:2px 0px 6px 0px;\" /> ".$value."<br />";
              }
            }
            // search field for date
            elseif ($hypertagname == $searchtag."d")
            {
              if (@substr_count ($viewstore, $hypertag) > 0)
              {
                $formitem[$key] = "
            <label for=\"".$hypertagname."_".$id."\" style=\"display:".$css_display."; width:180px; font-weight:bold;\">".$label." </label>
            <input type=\"text\" id=\"".$hypertagname."_".$id."\" name=\"search_textnode[".$id."]\" value=\"\" style=\"display:inline !important; margin:2px 0px\" ".(empty ($empty_values) ? "required=\"required\"" : "")." /><img src=\"".getthemelocation()."img/button_datepicker.png\" onclick=\"show_cal(this, '".$hypertagname."_".$id."', '".$format."');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" style=\"z-index:9999999;\" alt=\"".getescapedtext ($hcms_lang['pick-a-date'][$lang])."\" title=\"".getescapedtext ($hcms_lang['pick-a-date'][$lang])."\" /><br />";
              }
            }
            // search field for media alternative text
            elseif ($hypertagname == "mediaalttext")
            {
              if (@substr_count ($viewstore, $hypertag) > 0)
              {
                $formitem["media:".$key] = "
            <label for=\"media_".$id."\" style=\"display:".$css_display."; width:180px; font-weight:bold;\">".$label." </label>
            <input id=\"media_".$id."\" name=\"search_textnode[media:".$id."]\" value=\"\" style=\"display:inline !important; width:".$css_width_field."; margin:2px 0px\" ".(empty ($empty_values) ? "required=\"required\"" : "")." /><br />";
              }
            }
            // search field for link text
            elseif ($hypertagname == "linktext")
            {
              if (@substr_count ($viewstore, $hypertag) > 0)
              {
                $formitem["link:".$key] = "
            <label for=\"link_".$id."\" style=\"display:".$css_display."; width:180px; font-weight:bold;\">".$label." </label>
            <input id=\"link_".$id."\" name=\"search_textnode[link:".$id."]\" value=\"\" style=\"display:inline !important; width:".$css_width_field."; margin:2px 0px\" ".(empty ($empty_values) ? "required=\"required\"" : "")." /><br />";
              }
            }
          }

          // collect id
          $id_array[] = $id;
        }
      }
    }

    $viewstore = "<!DOCTYPE html>
<html>
<head lang=\"".$lang."\">
  <title>hyperCMS</title>
  <meta charset=\"".getcodepage ($lang)."\" />
  <meta name=\"robots\" content=\"noindex, nofollow\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"".getthemelocation()."css/main.css\" />
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/main.min.js\"></script>
  <link  rel=\"stylesheet\" type=\"text/css\" href=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rich_calendar.css\" />
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rich_calendar.min.js\"></script>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rc_lang_en.js\"></script>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rc_lang_de.js\"></script>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rc_lang_fr.js\"></script>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rc_lang_pt.js\"></script>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rc_lang_ru.js\"></script>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/domready.js\"></script>
  <script type=\"text/javascript\">
  var cal_obj = null;
  var cal_format = null;
  var cal_field = null;

  function show_cal (el, field_id, format)
  {
    if (cal_obj) return;

    cal_field = field_id;
    cal_format = format;
    var datefield = document.getElementById(field_id);

    cal_obj = new RichCalendar();
    cal_obj.start_week_day = 1;
    cal_obj.show_time = false;
    cal_obj.language = '".getcalendarlang ($lang)."';
    cal_obj.user_onchange_handler = cal_on_change;
    cal_obj.user_onautoclose_handler = cal_on_autoclose;
    cal_obj.parse_date(datefield.value, cal_format);
    cal_obj.show_at_element(datefield, 'adj_left-top');
  }

  // onchange handler
  function cal_on_change (cal, object_code)
  {
    if (object_code == 'day')
    {
      document.getElementById(cal_field).value = cal.get_formatted_date(cal_format);
      cal.hide();
      cal_obj = null;
    }
  }

  // onautoclose handler
  function cal_on_autoclose (cal)
  {
    cal_obj = null;
  }
  </script>
</head>
<body id=\"hcms_htmlbody\" class=\"hcmsWorkplaceExplorer\" style=\"height:auto;\" ".($template != "" ? "onload=\"parent.hcms_showPage('contentFrame', 'contentLayer');\"" : "").">

<!-- load screen --> 
<div id=\"hcmsLoadScreen\" class=\"hcmsLoadScreen\"></div>

".(trim ($title) != "" ? "<p class=\"hcmsHeadline\">".$title."</p>" : "")."
".($report != "" ? "<form action=\"".cleandomain ($mgmt_config['url_path_cms'])."report/\" methode=\"post\" style=\"padding:4px;\" onsubmit=\"if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display='inline';\">\n <input type=\"hidden\" name=\"reportname\" value=\"".$report."\" />" : "")."
    ";

    if (isset ($formitem) && is_array ($formitem))
    {
      // sort form items by its position/key
      ksort ($formitem);
      reset ($formitem);

      foreach ($formitem as $buffer)
      {
        $viewstore .= $buffer;
      }
    }

    if ($report != "") $viewstore .= "<br />
    <button class=\"hcmsButtonGreen\">".getescapedtext ($hcms_lang['forward'][$lang])."</button>
  ";

  $viewstore .= "
".($report != "" ? "</form>" : "")."
  <div style=\"display:block; clear:both; height:3px;\"></div>
</body>
</html>";

    return $viewstore;
  }
  else return false;
}

// --------------------------------- buildbarchart -------------------------------------------
// function: buildbarchart()
// input: name/id of paper [string], width of paper in pixel [integer], height of paper in pixel [integer], top space in pixel [integer], left space in pixel [integer], x-axis values with index as 1st key and 'value','text','onclick' as 2nd key [array], y1-axis values [array], y2-axis values [array] (optional), y3-axis values [array] (optional),
//        paper CSS style [string] (optional), 1st bar chart CSS style [string] (optional), 2nd bar chart CSS style [string] (optional), 3rd bar chart CSS style [string] (optional), show y-value in chart bar [boolean] (optional), mininmum y-axis value [integer] (optional)
// output: bar chart view / false on error

// help function to find max value of 2-dimensional array
function getmaxvalue ($array)
{
  if (is_array ($array))
  {
    $max = 0;

    foreach ($array as $value)
    {
      if (is_array ($value) && $value['value'] > $max) $max = $value['value'];
      elseif (!is_array ($value) && $value > $max) $max = $value;
    }

    return $max;
  }
  else return false;
}

function buildbarchart ($paper_name, $paper_width=600, $paper_height=300, $paper_top=10, $paper_left=40, $x_axis, $y1_axis, $y2_axis="", $y3_axis="", $paper_style="", $bar1_style="", $bar2_style="", $bar3_style="", $show_value=false, $y_min_value=8)
{
  global $lang,
         $mgmt_config;

  if ($paper_name != "" && is_numeric ($paper_width) && is_numeric ($paper_height) && is_array ($x_axis) && is_array ($y1_axis))
  {
    // get max height of all y-values
    $temp_maxheight = array ();

    if (is_array ($y1_axis) && sizeof ($y1_axis) > 0) $temp_maxheight[1] = getmaxvalue ($y1_axis);
    if (is_array ($y2_axis) && sizeof ($y2_axis) > 0) $temp_maxheight[2] = getmaxvalue ($y2_axis);
    if (is_array ($y3_axis) && sizeof ($y3_axis) > 0) $temp_maxheight[3] = getmaxvalue ($y3_axis);

    if (is_array ($temp_maxheight) && sizeof ($temp_maxheight) > 0) $bar_maxheight = max ($temp_maxheight);
    else $bar_maxheight = 0;

    // set default max value for y-axis
    if ($bar_maxheight < intval ($y_min_value)) $bar_maxheight = intval ($y_min_value);

    // count bars
    $bar_count = sizeof ($x_axis);
    $x_width = ($paper_width - (1 * $bar_count) - 1) / $bar_count;

    // define bar width
    if (is_array ($y2_axis) && is_array ($y3_axis)) $split = 3;
    elseif (is_array ($y2_axis) || is_array ($y3_axis)) $split = 2;
    else $split = 1;

    $bar_width = $x_width / $split;
    $x_width = floor ($x_width);
    $bar_width = floor ($bar_width);

    // paper div-layer
    $result = "<style>
.hcmsChartXAxis
{
  margin: 0;
  padding: 0;
  border: 0;
  text-align: right;
  vertical-align: top;
  z-index: 100;
  transform-origin: right bottom 0; 
  transform: rotate(-45deg);
  /* Safari */
  -webkit-transform: rotate(-45deg);
  /* Firefox */
  -moz-transform: rotate(-45deg);
  /* IE */
  -ms-transform: rotate(-45deg);
  /* Opera */
  -o-transform: rotate(-45deg);
}
</style>
<div id=\"".$paper_name."\" style=\"position:relative; width:".$paper_width."px; height:".$paper_height."px; top:".$paper_top."px; left:".$paper_left."px; margin:0; padding:0; z-index:100; ".$paper_style."\">\n";

    // y-axis values/rulers
    $result .= "  <div id=\"yval4\" style=\"position:absolute; width:40px; top:-8px; left:-44px; margin:0; padding:0; border:0; text-align:right; vertical-align:top; z-index:1;\">".$bar_maxheight."</div>\n";
    $result .= "  <div id=\"yval3\" style=\"position:absolute; width:40px; top:".(($paper_height / 4) - 8)."px; left:-44px; margin:0; padding:0; border:0; text-align:right; vertical-align:top; z-index:100;\">".round ($bar_maxheight / 4 * 3, 0)."</div>\n";
    $result .= "  <div id=\"yval3_rule\" style=\"position:absolute; width:".$paper_width."px; height:1px; top:".($paper_height / 4)."px; left:0; margin:0; padding:0; border-top:1px solid #666666; text-align:right; vertical-align:top; z-index:100;\"></div>\n";
    $result .= "  <div id=\"yval2\" style=\"position:absolute; width:40px; top:".(($paper_height / 2) - 8)."px; left:-44px; margin:0; padding:0; border:0; text-align:right; vertical-align:top; z-index:100;\">".round ($bar_maxheight / 2, 0)."</div>\n";
    $result .= "  <div id=\"yval2_rule\" style=\"position:absolute; width:".$paper_width."px; height:1px; top:".($paper_height / 2)."px; left:0; margin:0; padding:0; border-top:1px solid #666666; text-align:right; vertical-align:top; z-index:100;\"></div>\n";
    $result .= "  <div id=\"yval1\" style=\"position:absolute; width:40px; top:".(($paper_height / 4 * 3) - 8)."px; left:-44px; margin:0; padding:0; border:0; text-align:right; vertical-align:top; z-index:100;\">".round ($bar_maxheight / 4, 0)."</div>\n";
    $result .= "  <div id=\"yval1_rule\" style=\"position:absolute; width:".$paper_width."px; height:1px; top:".($paper_height / 4 * 3)."px; left:0; margin:0; padding:0; border-top:1px solid #666666; text-align:right; vertical-align:top; z-index:100;\"></div>\n";
    $result .= "  <div id=\"yval0\" style=\"position:absolute; width:40px; top:".($paper_height - 8)."px; left:-44px; margin:0; padding:0; border:0; text-align:right; vertical-align:top; z-index:100;\">0</div>\n";

    // 1st bar chart incl. x-axis values
    $i = 0;

    foreach ($x_axis as $key => $x_value)
    {
      $bar_height = $y1_axis[$key]['value'] / $bar_maxheight * $paper_height;
      $bar_height = round ($bar_height);
      $bar_top = ($paper_height - $bar_height);
      $bar_left = ($i * ($x_width + 1)) + 1;
      $x_left = ($i * ($x_width + 1)) + 1;

      if ($show_value == true) $bar_value = $y1_axis[$key]['value'];
      else $bar_value = "&nbsp;";

      // link
      if (!empty ($y1_axis[$key]['onclick'])) $link = "onclick=\"".$y1_axis[$key]['onclick']."\"";
      else $link = "";

      // 1st bar
      if ($bar_height > 0) $result .= "  <div id=\"bar1_".$i."\" ".$link." title=\"".addslashes($y1_axis[$key]['text'])."\" style=\"position:absolute; width:".$bar_width."px; height:".$bar_height."px; top:".$bar_top."px; left:".$bar_left."px; margin:0; padding:0; border:0; text-align:center;  vertical-align:top; z-index:200; ".$bar1_style."\">".$bar_value."</div>\n";
      
      // x-axis values
      $result .= "  <div id=\"xval".$i."\" class=\"hcmsChartXAxis\" style=\"position:absolute; top:".($paper_height + 4)."px; left:".$x_left."px;\">".$x_axis[$key]."</div>\n";
      $i++;
    }

    // 2nd bar chart
    if (is_array ($y2_axis))
    {
      $i = 0;

      foreach ($x_axis as $key => $x_value)
      {
        $bar_height = $y2_axis[$key]['value'] / $bar_maxheight * $paper_height;
        $bar_height = round ($bar_height);
        $bar_top = ($paper_height - $bar_height);
        $bar_left = ($i * ($x_width + 1)) + $bar_width + 1;

        if ($show_value == true) $bar_value = $y2_axis[$key]['value'];
        else $bar_value = "&nbsp;";

        // link
        if (!empty ($y2_axis[$key]['onclick'])) $link = "onclick=\"".$y2_axis[$key]['onclick']."\"";
        else $link = "";

        // 2nd bar
        if ($bar_height > 0) $result .= "  <div id=\"bar2_".$i."\" ".$link." title=\"".addslashes($y2_axis[$key]['text'])."\" style=\"position:absolute; width:".$bar_width."px; height:".$bar_height."px; top:".$bar_top."px; left:".$bar_left."px; margin:0; padding:0; border:0; text-align:center;  vertical-align:top; z-index:200; ".$bar2_style."\">".$bar_value."</div>\n";
        $i++;
      }
    }

    // 3rd bar chart
    if (is_array ($y3_axis))
    {
      $i = 0;

      foreach ($x_axis as $key => $x_value)
      {
        $bar_height = $y3_axis[$key]['value'] / $bar_maxheight * $paper_height;
        $bar_height = round ($bar_height);
        $bar_top = ($paper_height - $bar_height);
        $bar_left = ($i * ($x_width + 1)) + (2 * $bar_width) + 1;

        if ($show_value == true) $bar_value = $y3_axis[$key]['value'];
        else $bar_value = "&nbsp;";

        // link
        if (!empty ($y3_axis[$key]['onclick'])) $link = "onclick=\"".$y3_axis[$key]['onclick']."\"";
        else $link = "";

        // 3rd bar
        if ($bar_height > 0) $result .= "  <div id=\"bar3_".$i."\" ".$link." title=\"".addslashes($y3_axis[$key]['text'])."\" style=\"position:absolute; width:".$bar_width."px; height:".$bar_height."px; top:".$bar_top."px; left:".$bar_left."px; margin:0; padding:0; border:0; text-align:center;  vertical-align:top; z-index:200; ".$bar3_style."\">".$bar_value."</div>\n";
        $i++;
      }
    }

    $result .= "</div>\n";

    return $result;
  }
  else return false;
}
?>