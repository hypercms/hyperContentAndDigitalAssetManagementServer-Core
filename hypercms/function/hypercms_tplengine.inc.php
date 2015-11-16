<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// ===================================== TEMPLATE ENGINE CORE ===========================================
// the following functions are the core of the template engine.
// every view on templates, content objects in preview mode or edit mode and publishing
// is done by these functions.  

// --------------------------------------- template functions -----------------------------------------------
// these functions are used for creating the desired output depending on the presentation technology.
// the functions are used by buildview and viewinclusions.

// inclusions of files (depending on OS)
function tpl_compinclude ($application, $file, $os_cms)
{
  $application = strtolower ($application);
  
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
  elseif ($application == "htm") return "<script language=\"JavaScript\">";
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
  elseif ($application == "htm") return "<script language=\"JavaScript\">\n".$code."\n</script>";
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
  
  if ($application == "php")
  {
    return "<?php \$hypercms_contentcontainer = \"$container\"; \$hypercms_today = date (\"YmdHi\", time()); ?>\n";
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
    // this means you will have to locate livelink.inc.asp to the root of a virtual directory to include it using 'include virtual.
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
    // this means you will have to locate livelink.inc.asp to the root of a virtual directory to include it using 'include virtual.
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
  $application = strtolower ($application);
  
  if ($application == "php")
  {
    return "<?php
if (is_array (\$_GET['hcms_session']))
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
\$site = '".$site."';
include ('".$abs_path_cms."config.inc.php'); 
\$publ_config = parse_ini_file ('".$abs_path_rep."config/".$site.".ini'); 
include ('".$abs_path_cms."function/hypercms_api.inc.php');
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
@chdir ('$location');
?>\n";
  }
  else return "";
}

// --------------------------------------- checklanguage -----------------------------------------------
// function: checklanguage()
// input: language array with all valid values, language value of attribute in hyperCMS tag
// output: true if language array holds the given language value / false if not found

function checklanguage ($language_array, $language_value)
{
  if (is_array ($language_array) && $language_value != "")
  {
    if (in_array ($language_value, $language_array)) return true;
    else return false;
  }
  else return true;
}

// --------------------------------------- checkgroupaccess -----------------------------------------------
// function: checkgroupaccess()
// input: group access string from hyperCMS group-tag attribute, owner groups as array
// output: true if current ownergroup has access or invalid input / false if not

function checkgroupaccess ($groupaccess, $ownergroup)
{
  if ($groupaccess != "" && is_array ($ownergroup))
  {
    $accessgroup = array ();
    
    // replace ; with |
    if (substr_count ($groupaccess, ";") > 0) $groupaccess = str_replace (";", "|", $groupaccess);
    
    if (substr_count ($groupaccess, "|") > 0) $accessgroup = explode ("|", $groupaccess);
    else $accessgroup[] = $groupaccess;
    
    if (is_array ($accessgroup) && sizeof ($accessgroup) > 0)
    {
      foreach ($ownergroup as $group)
      {
        if (in_array ($group, $accessgroup)) return true;
      }
      
      return false;
    }
    else return true;
  }
  else return true;
}

// --------------------------------------- transformlink -----------------------------------------------
// function: transformlink()
// input: view of object
// output: view with transformed links inside publication for easyedit mode

function transformlink ($viewstore)
{
  // define global variables
  global $site, $location_esc, $page, $ctrlreload, $mgmt_config;
  
  // arrays holding the possible expression for a hyperreference  
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
  $link_array[16] = "location.replace = \"";
  $link_array[17] = "location.replace =\"";
  $link_array[18] = "location.replace= \"";
  $link_array[19] = "location.replace=\"";
  $link_array[20] = "location.replace = '";
  $link_array[21] = "location.replace ='";
  $link_array[22] = "location.replace= '";
  $link_array[23] = "location.replace='";
  
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
    if (@substr_count ($follow, "://") > 0)
    {
      if (@substr_count ($follow, $mgmt_config[$site]['url_path_page']) > 0)
      { 
        $follow = str_replace ($mgmt_config[$site]['url_path_page'], "%page%/".$site."/", $follow);
      }
      elseif (@substr_count ($follow, $publ_config['url_publ_page']) > 0) 
      {
        $follow = str_replace ($publ_config['url_publ_page'], "%page%/".$site."/", $follow);
      }
      elseif (@substr_count ($follow, $mgmt_config['url_path_comp']) > 0) 
      {
        $follow = str_replace ($mgmt_config['url_path_comp'], "%comp%/", $follow);
      }
      elseif (@substr_count ($follow, $publ_config['url_publ_comp']) > 0) 
      {
        $follow = str_replace ($publ_config['url_publ_comp'], "%comp%/", $follow);
      }
    }
    // page link
    elseif (substr ($follow, 0, 1) == "/")
    {
      $pos = strpos ($publ_config['url_publ_page'], "/", strpos ($publ_config['url_publ_page'], "://") + 3);
      $rooturl = substr ($publ_config['url_publ_page'], 0, $pos);
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
    
    return "
  <!DOCTYPE html>
  <html>
    <body>
    <font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><font color='red'>".$return_code."</font><br />
    ".$source_code."</font>
    </body>
  </html>";
  }
  else return $return_code;
}

// --------------------------------------- viewinclusions -----------------------------------------------
// function: viewinclusions()
// input: view of object, hypertag to create view of inlcuded objects, view parameter, application, character set used (optional)
//        view-parameter explanation:
//        $view = "template or any other word" -> the standard text (in table) will be included for the view
//        $view = "preview" -> preview of the content of the included file
//        $view = "publish" -> view the content of the included file as ist is (for publishing)
// output: view on the content including the content of included objects
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
  if (@substr_count (strtolower ($viewstore), strtolower ($hypertag)) >= 1)
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
          
          if ($result['result'] == false)
          {
            $includedata = "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>".getescapedtext ($hcms_lang['template'][$lang], $charset, $lang)." '".specialchr_decode (getobject ($include_file))."' ".getescapedtext ($hcms_lang['that-should-be-included-is-missing'][$lang], $charset, $lang)."<br />".getescapedtext ($hcms_lang['please-upload-the-template'][$lang], $charset, $lang)."</b></font></td>\n  </tr>\n</table>\n";
          }
          else
          {
            $bufferdata = getcontent ($result['content'], "<content>");
            $includedata = $bufferdata[0];
          }  
        }
        // file include (via HTTP)
        elseif (@substr_count (strtolower ($hypertag), "hypercms:fileinclude") == 1)
        {
          if (substr_count ($include_file, "://") == 1)
          {
            $includedata = @file_get_contents ($include_file);
          }
          else $includedata = tpl_compinclude ($application, $include_file, $mgmt_config['os_cms']);

          if ($includedata == false)
          {
            $includedata = "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>".getescapedtext ($hcms_lang['component'][$lang], $charset, $lang)." '".specialchr_decode (getobject ($include_file))."' ".getescapedtext ($hcms_lang['will-be-included-here-for-publishing'][$lang], $charset, $lang)."<br />".getescapedtext ($hcms_lang['please-dont-forget-to-publish-the-component'][$lang], $charset, $lang)."</b></font></td>\n  </tr>\n</table>\n";
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
           
          if ($result['result'] != false)
          {
            $bufferdata = getcontent ($result['content'], "<content>");
            $includedata = $bufferdata[0];
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

          if ($result['result'] == false)

          {
            $includedata = "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>".getescapedtext ($hcms_lang['template'][$lang], $charset, $lang)." '".specialchr_decode (getobject ($include_file))."' ".getescapedtext ($hcms_lang['will-be-included-here-for-publishing'][$lang], $charset, $lang)."<br />".getescapedtext ($hcms_lang['please-upload-the-template'][$lang], $charset, $lang)."</b></font>\n</td>\n  </tr>\n</table>\n";
          }
          else
          {
            $includedata = "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>".getescapedtext ($hcms_lang['template'][$lang], $charset, $lang)." '".specialchr_decode (getobject ($include_file))."' ".getescapedtext ($hcms_lang['will-be-included-here-for-publishing'][$lang], $charset, $lang)."</b></font>\n</td>\n  </tr>\n</table>\n";
          }
        }
        // file include
        elseif (@substr_count (strtolower ($hypertag), "hypercms:fileinclude") == 1)
        {
          $includedata = "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>".getescapedtext ($hcms_lang['component'][$lang], $charset, $lang)." '".specialchr_decode (getobject ($include_file))."' ".getescapedtext ($hcms_lang['will-be-included-here-for-publishing'][$lang], $charset, $lang)."<br />".getescapedtext ($hcms_lang['please-dont-forget-to-publish-the-component'][$lang], $charset, $lang)."</b></font>\n</td>\n  </tr>\n</table>\n";
        }
      }

      // ---------------------------------------- build view -----------------------------------------
      if ($includedata != false)
      {
        // include external data
        $viewstore = str_replace ($hypertag, $includedata, $viewstore);
        
        // recursive inclusions of file includes
        if (@substr_count (strtolower ($includedata), "hypercms:fileinclude") > 0)
        {
          $hypertag_array = gethypertag ($includedata, "fileinclude", 0);
          
          if ($hypertag_array != false)
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
          
          if ($hypertag_array != false)
          {
            foreach ($hypertag_array as $hypertag)
            {
              $viewstore = viewinclusions ($site, $viewstore, $hypertag, $view, $application, $charset);
            }
          }
        }
      }
      else $viewstore = false;
    }
    
    return $viewstore;
  }
  else return $viewstore;
}

// --------------------------------- buildview -------------------------------------------
// function: buildview()
// input: publication name, location, object, user, view parameter (optional), reload workplace control frame and add html & body tags if missing [yes,no] (optional), 
//        template name (optional), container name (optional), 
//        force category to use different location path for %url_location% or %abs_location% [page,comp] (optional), execute_code [true/false] (optional
//
//        buildview parameter may have the following values:
//        $buildview = "formedit": use form for content editing
//        $buildview = "formmeta": use form for content viewing only for meta informations (tag-type must be meta)
//        $buildview = "formlock": use form for content viewing
//        $buildview = "cmsview": view of page based on template, includes hyperCMS specific code (buttons)
//        $buildview = "inlineview": view of page based on template, includes hyperCMS specific code (buttons) and inline text editing
//        $buildview = "publish": view of page for publishing based on template without CMS specific code (buttons)
//        $buildview = "preview": view of page based on template for preview (inactive hyperlinks) without CMS specific code (buttons)
//        $buildview = "template": view of template based on template for preview (inactive hyperlinks) without CMS specific code (buttons)
//
// output: result array with view of the content / false on error
// 
// requirements:
// buildview requires the following functions and files: config.inc.php, hypercms_api.inc.php
// these functions must be inluded before you can use buildview.
// to be able to save the content, the secure token mus be provided to this function.

function buildview ($site, $location, $page, $user, $buildview="template", $ctrlreload="no", $template="", $container="", $force_cat="", $execute_code=true)
{ 
  global $container_collection,
         $eventsystem,
         $db_connect,
         $mgmt_config, 
         $siteaccess, $adminpermission, $setlocalpermission, $token, 
         $mgmt_lang_shortcut_default, $hcms_charset, $hcms_lang_name, $hcms_lang_shortcut, $hcms_lang_codepage, $hcms_lang_date, $hcms_lang, $lang;
  
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
  
  // set default view values
  $valid_views = array ("formedit", "formmeta", "formlock", "cmsview", "inlineview", "publish", "preview", "template");
  
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

  // validate publication access for all views except publish
  if ($buildview != "publish")
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
  if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
  
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
    if (substr ($location, -1) != "/") $location = $location."/";
  
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
  }
   
  // ----------------------------------- build view of page -----------------------------------------
  
  // include publication target settings
  if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 
  
  // eventsystem
  if ($eventsystem['oneditobject_pre'] == 1 && $eventsystem['hide'] == 0 && ($buildview == "cmsview" || $buildview == "inlineview")) 
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
  
  if (in_array ($buildview, array ("formedit", "formmeta", "formlock", "cmsview", "inlineview", "publish", "preview")))
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
      if (file_exists ($mgmt_config['abs_path_temp'].session_id().".dates.php"))
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
          $dir_version = dir ($versiondir);
               
          if ($dir_version)
          {
            while ($entry = $dir_version->read())
            {
              if ($entry != "." && $entry != ".." && @!is_dir ($versiondir.$entry) && (@preg_match ("/".$contentfile.".v_/i", $entry) || @preg_match ("/_hcm".$container_id."/i", $entry)))
              {
                $files_v[] = $entry;           
              }
            }
            
            $dir_version->close();
          }
      
          if (is_array ($files_v) && @sizeof ($files_v) > 0)
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
          
          $dir_version = dir ($versiondir);
      
          while ($entry = $dir_version->read())
          {
            if ($entry != "." && $entry != ".." && @!is_dir ($versiondir.$entry) && @preg_match ("/".$templatefile.".v_/i", $entry))
            {
              $files_v[] = $entry;           
            }
          }
          
          $dir_version->close();
      
          if (@sizeof ($files_v) >= 1)
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
   
    if (is_array ($result))
    {
      $templatedata = $result['content'];
      $templatesite = $result['publication'];
     
      $bufferdata = getcontent ($templatedata, "<extension>");
      $templateext = $bufferdata[0];
      
      $bufferdata = getcontent ($templatedata, "<application>");
      $application = $bufferdata[0];
  
      $bufferdata = getcontent ($templatedata, "<content>");
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
    
    if ($hypertag_array != false && sizeof ($hypertag_array) > 0) 
    {
      foreach ($hypertag_array as $hypertag)
      {
        if ($buildview != "publish" && $buildview != "preview" && $buildview != "template")
        {
          // last objectview entry will set the view option
          $objectview = $buildview = getattribute ($hypertag, "name"); 
        }
        
        // remove tags 
        $viewstore = str_replace ($hypertag, "", $viewstore);
      }
    }
    
    // if object is media file or folder
    if ((!empty ($mediafile) && $application != "generator") || $page == ".folder")
    {
      if ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "formmeta") $buildview = "formedit";
      elseif ($buildview == "preview" || $buildview == "template") $buildview = "formlock";
    }
    
    // disable form fields
    if ($buildview == "formlock") $disabled = " disabled=\"disabled\"";
    else $disabled = "";
    
    // =============================== get content-type and character set ===============================
    
    $result = getcharset ($site, $viewstore);
    
    $contenttype = $result['contenttype'];
    $hcms_charset = $charset = $result['charset'];

    // get content-type from component template, if set
    $hypertag_array = gethypertag ($viewstore, "compcontenttype", 0);
    
    // remove tag
    if ($hypertag_array != false && sizeof ($hypertag_array) > 0) 
    {
      foreach ($hypertag_array as $hypertag)
      {
        $viewstore = str_replace ($hypertag, "", $viewstore);
      }
      
      $compcontenttype = true;
    }
    
    // ==================================== remove hyperCMS stylesheet tags in template ==================================
    
    if ($buildview == "publish" || $buildview == "template" || ($buildview == "preview" && $ctrlreload != "yes"))
    {  
      $hypertag_array = gethypertag ($viewstore, "compstylesheet", 0);

      if ($hypertag_array != false && sizeof ($hypertag_array) > 0) 
      {         
        foreach ($hypertag_array as $hypertag)
        {
          $viewstore = str_replace ($hypertag, "", $viewstore);
        }
      }
    }
    
    // ============================================== meta data templates ==============================================
    
    // remove tags in meta-data templates
    if (strpos ($templatefile, ".meta.tpl") > 0)
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
      
      $viewstore = "<!DOCTYPE html>
      <html>
      <head>
      <title>hyperCMS</title>
      <meta http-equiv=\"Content-Type\" content=\"".$contenttype."\">
      <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">
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
  
    if ($hypertag_array != false) 
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
        else $inclview = "preview";

        $viewstore = viewinclusions ($site, $viewstore, $hypertag, $inclview, $application, $charset);                    
      }
    }

    // ---------------- template include -------------------   
    // create view for included content

    // get all hyperCMS tags
    $hypertag_array = gethypertag ($viewstore, "tplinclude", 0);
  
    if ($hypertag_array != false) 
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
      $viewstore = str_replace ("<", "<font color=\"#0000FF\" size=\"2\" face=\"Verdana, Arial, Helvetica, sans-serif\">&lt;", $viewstore);
      $viewstore = str_replace (">", "&gt;</font>", $viewstore);   
      $viewstore = str_replace ("\n", "<br />", $viewstore);      
    }    

    // ========================================= define database connectivity =============================================    
    // get db_connect
    // no multiple values are allowed, in that case only the first valid db-connect value will be the valid one

    // get all hyperCMS tags
    $hypertag_array = gethypertag ($viewstore, "dbconnect", 0);
    
    if ($hypertag_array != false)
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
    if (isset ($db_connect) && $db_connect != "" && (!isset ($db_connect_incl) || $db_connect_incl != true) && file_exists ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
    {
      @include_once ($mgmt_config['abs_path_data']."db_connect/".$db_connect);      
    }
       
    // =========================================== load content container ==============================================  
    // define container collection
    if ($container_collection != "live") $container_collection = "work";
    
    // load live container from filesystem
    // get user who checked the object out and read associated working content container file
    
    $usedby = "";
    
    if ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview" || $buildview == "publish" || $buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
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
         ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1)
       )
    {
      // user has no permissions to edit the content
      if ($buildview == "cmsview" || $buildview == "inlineview") $buildview = "preview";
      elseif ($buildview == "formedit" || $buildview == "formmeta") $buildview = "formlock";
    }    
 
    // disable form fields if workflow set buildview
    if ($buildview == "formlock") $disabled = " disabled=\"disabled\"";
    else $disabled = "";   

    // check workflow role
    if (
         ($wf_role >= 1 && $wf_role <= 4) || 
         ($wf_role == 5 && $mgmt_config[$site]['dam'] == true && $ownergroup != false || (isset ($setlocalpermission['root']) && $setlocalpermission['root'] == 1)) || 
         ($wf_role == 5 && $mgmt_config[$site]['dam'] != true) || 
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
        
        $headstoreview = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."page_view.php?view=".url_encode($switcher_view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."\"><img src=\"".getthemelocation()."img/edit_easyedit.gif\" style=\"display:inline-block; width:65px; height:18px; padding:0; margin:0; border:0; vertical-align:top; text-align:left; z-index:9999999;\" alt=\"".$switcher_title."\" title=\"".$switcher_title."\"></a>";
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
    
      if ($hypertag_array != false) 
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
          
          // set default value in session if not set already
          if (empty ($_SESSION[$language_sessionvar])) $_SESSION[$language_sessionvar] = $session_defaultvalue;
          
          if ($buildview != "template")
          {
            // include CMS head edit buttons
            $headstorelang = "<select style=\"background:#FFCA85 url('".getthemelocation()."img/edit_language.gif') no-repeat left top; margin:0; padding:0; height:18px; vertical-align:top; border-style:solid; border-color:#FFFFFF; border-width:1px; -moz-border-radius:4px; -webkit-border-radius:4px; border-radius:4px; z-index:90000;\" title=\"".getescapedtext ($hcms_lang['language'][$lang], $charset, $lang)."\" onchange=\"document.location.hypercms_href='".$mgmt_config['url_path_cms']."page_view.php?hcms_session[".$language_sessionvar."]=' + this.options[this.selectedIndex].value + '&view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."';\">\n";

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
                
                $headstorelang .= ">&nbsp;&nbsp;&nbsp;&nbsp;".$label."</option>\n";
                
                $i++;
              }
            }
            
            $headstorelang .= "</select>\n";

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
        
      // =================================================== head content ===================================================
             
      $pagetracking = "";
      $label = "";
      $language_info = "";
      $headstoremeta = "";
      
      // get all hyperCMS tags
      $hypertag_array = gethypertag ($viewstore, "page", 0);
    
      if ($hypertag_array != false) 
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
          
          // get label text
          $label = getattribute ($hypertag, "label");            
          $labelname = "";
          
          // get type of content
          $infotype = getattribute (strtolower ($hypertag), "infotype");                
          
          // get visibility on publish
          $onpublish = getattribute (strtolower ($hypertag), "onpublish");    
            
          // get visibility on edit
          $onedit = getattribute (strtolower ($hypertag), "onedit");
          
          // get height in pixel of text field
          $sizeheight = getattribute ($hypertag, "height");
          
          // get value of tag
          $defaultvalue = getattribute ($hypertag, "default");          
          
          if ($sizeheight == false || $sizeheight <= 0) $sizeheight = "300";
          
          // get width in pixel of text field
          $sizewidth = getattribute ($hypertag, "width"); 
          
          if ($sizewidth == false || $sizewidth <= 0) $sizewidth = "600";
          
          // get language attribute
          $language_info = getattribute ($hypertag, "language");
          
          // get group access
          $groupaccess = getattribute ($hypertag, "groups");          
          $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);
          
          // create head-buttons depending on buildview parameter setting  
          if ($buildview != "template" && (!isset ($editmeta[$hypertagname]) || $editmeta[$hypertagname] != false) && $onedit != "hidden" && $groupaccess == true)
          {
            // set flag for found tag
            $editmeta[$hypertagname] = false;

            // include CMS head edit buttons
            $headstoremeta = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."page_view.php?view=formmeta&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."\"><img src=\"".getthemelocation()."img/edit_head.gif\" style=\"display:inline-block; width:45px; height:18px; padding:0; margin:0; border:0; vertical-align:top; text-align:left; z-index:9999999;\" alt=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\"></a>";
         
            // read content using db_connect
            if (isset ($db_connect) && $db_connect != "") 
            {
              $db_connect_data = db_read_metadata ($site, $contentfile, $contentdata, $hypertagname, $user);     
              
              if ($db_connect_data != false) 
              {
                $contentbot[0] = $db_connect_data['content'];
                  
                // set true
                $db_connect_data = true;                
              }       
            }
            else $db_connect_data = false;
            
            // read content from content container
            if ($db_connect_data == false) $contentbot = getcontent ($contentdata, "<".$hypertagname.">");
          
            // set default value given eventually by tag
            if ($contentbot[0] == "" && $defaultvalue != "") $contentbot[0] = $defaultvalue;
            
            // if language value is in given language scope
            if (checklanguage ($language_sessionvalues_array, $language_info))
            {
              // if page content-type
              if ($hypertagname == "pagecontenttype" || (!isset ($compcontenttype) && !isset ($contenttype)))
              {
                $contenttype = $contentbot[0];
                
                if ($buildview == "formedit" || $buildview == "formlock" || $buildview == "formmeta")
                {
                  // set flag
                  $setcontenttype = "yes";
                  
                  if ($label != "") $labelname = $label;
                  else $labelname = "Content-type";
                  
                  $formitem[$key] = "
                <tr>
                  <td align=left valign=top>
                    <b>".$labelname."</b>
                  </td>
                  <td>
                    <table cellpadding=0 cellspacing=0 border=0>
                      <tr>
                        <td style=\"width:150px;\">
                          ".getescapedtext ($hcms_lang['character-set'][$lang], $charset, $lang).":
                        </td>
                        <td>";
                  
                  //load code page index file
                  $codepage_array = file ($mgmt_config['abs_path_cms']."include/codepage.dat");
          
                  if ($codepage_array != false)
                  {
                    $formitem[$key] .= "
                          <select name=\"".$hypertagname."\" ".$disabled.">";
                    
                    foreach ($codepage_array as $codepage)
                    {
                      list ($code, $description, $language) = explode ("|", $codepage);
          
                      $formitem[$key] .= "
                            <option value=\"text/html; charset=".$code."\""; 
                      if (substr_count ($contenttype, $code) == 1) $formitem[$key] .= " selected"; 
                      $formitem[$key] .= ">".$code." ".$description."</option>";
                    }
                    
                    $formitem[$key] .= "
                          </select>";
                  }
                  else $formitem[$key] = "
                          <span class=hcmsHeadline>".$hcms_lang['could-not-find-code-page-index'][$lang]."</span>";
                  
                  $formitem[$key] .= "
                          <img src=\"".getthemelocation()."img/button_help.gif\" onClick=\"hcms_openWindow('".$mgmt_config['url_path_cms']."head_contenttype.php','help','resizable=yes,scrollbars=yes','800','600')\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['help'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['help'][$lang], $charset, $lang)."\" />
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>\n";              
                }   
              }
              // if page language
              elseif ($hypertagname == "pagelanguage")
              {  
                $content = $contentbot[0];
                
                if ($label != "") $labelname = $label;
                else $labelname = "Language";         
                         
                if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                { 
                  $add_submitlanguage = "submitLanguage ('list2', '".$hypertagname."');\n";
                             
                  $formitem[$key] = "
                <tr>
                  <td align=left valign=top>
                    <b>".$labelname."</b>
                  </td>
                  <td>
                    <input type=\"hidden\" name=\"".$hypertagname."\" value=\"\">
                    <table cellpadding=0 cellspacing=0 border=0>
                      <tr>
                        <td>
                          ".getescapedtext ($hcms_lang['available-languages'][$lang], $charset, $lang).":<br />";

                  // get languages
                  $langcode_array = getlanguageoptions();
          
                  if ($langcode_array != false)
                  {
                    $formitem[$key] .= "
                         <select multiple size=\"10\" name=\"list1\" style=\"width:250px;\"".$disabled.">";

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
                        <span class=hcmsHeadline>".getescapedtext ($hcms_lang['could-not-find-language-code-index'][$lang], $charset, $lang)."</span>";
          
                  $formitem[$key] .= "
                      </td>
                      <td align=\"center\" valign=\"middle\">
                        <br />
                        <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"moveBoxEntry(this.form.elements['list2'],this.form.elements['list1'])\" value=\"&lt;&lt;\"".$disabled." />
                        <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"moveBoxEntry(this.form.elements['list1'],this.form.elements['list2'])\" value=\"&gt;&gt;\"".$disabled." />
                      </td>
                      <td>
                        ".getescapedtext ($hcms_lang['selected-languages'][$lang], $charset, $lang).":<br />
                        <select multiple size=\"10\" name=\"list2\" style=\"width:250px;\"".$disabled.">";
              
                  if (sizeof ($list2_array) >= 1)
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
                </td>
              </tr>\n";              
                }    
              }            
              // if page customer tracking
              elseif ($hypertagname == "pagetracking")
              {
                if ($label == "") $labelname = $metaname;
                else $labelname = $label;
              
                if ($contentbot[0] != "" && $buildview == "publish") 
                {
                  $pagetracking_name = $contentbot[0];
                  $contentbot[0] = loadfile ($mgmt_config['abs_path_data']."customer/".$site."/", $pagetracking_name.".track.dat");
                  
                  if ($contentbot[0] == false)
                  {
                    $errcode = "10101";
                    $error[] = $mgmt_config['today']."|hypercms_tplengine.inc.php|error|$errcode|loadfile failed for ".$mgmt_config['abs_path_data']."customer/".$site."/".$pagetracking_name.".track.dat";
                  }
                  else $contentbot[0] = tpl_pagetracking ($application, $contentbot[0]);
                  
                  // save page tracking to add it later due to the reason that session handling must be added on top of a page
                  $pagetracking = $contentbot[0]."\n";
                }
  
                if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                {
                  $formitem[$key] = "
                <tr>
                  <td align=left valign=top>
                    <b>".$labelname."</b>
                  </td>
                  <td>
                    <table cellpadding=0 cellspacing=0 border=0>
                      <tr>
                        <td style=\"width:150px;\">
                          ".getescapedtext ($hcms_lang['customer-tracking'][$lang], $charset, $lang).":
                        </td>
                        <td>
                          <select name=\"".$hypertagname."\" style=\"width:250px;\"".$disabled.">
                            <option value=\"\">--------- ".getescapedtext ($hcms_lang['select'][$lang], $charset, $lang)." ---------</option>";
        
                  $dir_item = @dir ($mgmt_config['abs_path_data']."customer/".$site."/");
        
                  $i = 0;
        
                  if ($dir_item != false)
                  {
                    while ($entry = $dir_item->read())
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
        
                    $dir_item->close();
        
                    if (sizeof ($item_files) >= 1)
                    {
                      sort ($item_files);
                      reset ($item_files);
        
                      foreach ($item_files as $persfile)
                      {
                        $pers_name = substr ($persfile, 0, strpos ($persfile, ".track.dat"));
                        
                        if ($pers_name == $contentbot[0]) $selected = " selected";
                        else $selected = "";
                        
                        $formitem[$key] .= "
                            <option value=\"".$pers_name."\"".$selected.">".$pers_name."</option>";
                      }
                    }
                  }
                  
                  $formitem[$key] .= "
                          </select>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>\n";              
                }
                
                // empty contentbot for customer tracking since the content won't replace teh tag  
                $contentbot[0] = "";
              }
              // all other cases
              else
              {
                if ($label == "") $labelname = $metaname;
                else $labelname = $label;
                
                if ($buildview == "formedit" || $buildview == "formlock" || $buildview == "formmeta")
                {
                  $formitem[$key] = "
                <tr>
                  <td align=left valign=top>
                    <b>".$labelname."</b>
                  </td>
                  <td align=left valign=top>
                    <textarea name=\"".$hypertagname."\" wrap=\"VIRTUAL\" style=\"width:".$sizewidth."px; height:".$sizeheight."px;\"".$disabled.">".$contentbot[0]."</textarea>
                  </td>
                </tr>\n";            
                }            
              }
            }
       
            // replace CMS tag with contentbot for page view
            if ($onpublish != "hidden") $viewstore = str_replace ($hypertag, $contentbot[0], $viewstore);
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
            <tr>
              <td align=left valign=top>
                <b>Content-type</b>
              </td>
              <td>
                <table cellpadding=0 cellspacing=0 border=0>
                  <tr>
                    <td style=\"width:150px;\">
                      ".getescapedtext ($hcms_lang['character-set'][$lang], $charset, $lang).":
                    </td>
                    <td>";
          
          //load code page index file
          $codepage_array = file ($mgmt_config['abs_path_data']."codepage.dat");
  
          if ($codepage_array != false)
          {
            $formitem['0'] .= "
                    <select name=\"".$hypertagname."\"".$disabled.">";
            
            foreach ($codepage_array as $codepage)
            {
              list ($code, $description, $language) = explode ("|", $codepage);
  
              $formitem['0'] .= "
                       <option value=\"text/html; charset=".$code."\""; 
              if (substr_count ($contenttype, $code) == 1) $formitem['0'] .= " selected"; 
              $formitem['0'] .= ">".$code." ".$description."</option>";
            }
            
            $formitem['0'] .= "
                     </select>";
          }
          else $formitem['0'] = "
                     <span class=hcmsHeadline>".$hcms_lang['could-not-find-code-page-index'][$lang]."</span>";
          
          $formitem['0'] .= "
                     <img border=0 src=\"".getthemelocation()."img/button_help.gif\" onClick=\"hcms_openWindow('".$mgmt_config['url_path_cms']."head_contenttype.php','help','resizable=yes,scrollbars=yes','800','600')\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['help'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['help'][$lang], $charset, $lang)."\" />
                   </td>
                 </tr>
               </table>
             </td>
           </tr>\n";              
        }        
      }

      // =================================================== article settings ===================================================
           
      $hypertag_array = array();
      $artid_array = array();
      $artid_array = array();
      $hypertagname_array = array();
     
      // get all hyperCMS tags
      $hypertag_array = gethypertag ($viewstore, "art", 0);
    
      if ($hypertag_array != false) 
      { 
        // loop for each hyperCMS arttag found in template
        foreach ($hypertag_array as $hypertag)
        {  
          $id = getattribute ($hypertag, "id");
          $artid = getartid ($id);  
          if (!in_array ($artid, $artid_array)) $artid_array[] = $artid; 
          
          // get tag name
          $hypertagname_array[$artid] = gethypertagname ($hypertag);      
        }  
      
        $artid_array = array_unique ($artid_array);
     
        $i = 0;
         
        // loop for each hyperCMS tag found in template
        foreach ($artid_array as $artid)
        {   
          $i++; 
               
          if ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "formedit" || $buildview == "formmeta" || $buildview == "publish")
          {
            // read content using db_connect
            if (isset ($db_connect) && $db_connect != "") 
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
            else $db_connect_data = false;  
             
            // read content from content container
            if ($db_connect_data == false)
            {            
              $artarray = selectcontent ($contentdata, "<article>", "<article_id>", $artid);
              $bufferarray = getcontent ($artarray[0], "<articletitle>");
              $arttitle[$artid] = $bufferarray[0];
             
              $bufferarray = getcontent ($artarray[0], "<articledatefrom>");
              $artdatefrom[$artid] = $bufferarray[0];       
    
              $bufferarray = getcontent ($artarray[0], "<articledateto>");
              $artdateto[$artid] = $bufferarray[0];
    
              $bufferarray = getcontent ($artarray[0], "<articlestatus>");
              $artstatus[$artid] = $bufferarray[0];
            }

            // transform  
            //$artdatefrom[$artid] = str_replace ("-", "", $artdatefrom[$artid]);
            //$artdatefrom[$artid] = str_replace (" ", "", $artdatefrom[$artid]);
            //$artdatefrom[$artid] = str_replace (":", "", $artdatefrom[$artid]);                  
            
            // transform
            //$artdateto[$artid] = str_replace ("-", "", $artdateto[$artid]);
            //$artdateto[$artid] = str_replace (" ", "", $artdateto[$artid]);
            //$artdateto[$artid] = str_replace (":", "", $artdateto[$artid]);          
          }
  
          if ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "formedit" || $buildview == "formmeta")
          {
            $hypertagname = $hypertagname_array[$artid];
            
            // create tag link for editor
            if ($buildview == "cmsview" || $buildview == "inlineview") $arttaglink[$artid] = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."article_edit.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&artid=".url_encode($artid)."&tagname=".url_encode($hypertagname)."&arttitle=".urlencode ($arttitle[$artid])."&artdatefrom=".url_encode($artdatefrom[$artid])."&artdateto=".url_encode($artdateto[$artid])."&artstatus=".url_encode($artstatus[$artid])."&contenttype=".url_encode($contenttype)."\"><img src=\"".getthemelocation()."img/button_article.gif\" alt=\"".$artid.": ".getescapedtext ($hcms_lang['define-settings-for-article'][$lang], $charset, $lang)."\" title=\"".$artid.": ".getescapedtext ($hcms_lang['define-settings-for-article'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>";
            elseif ($buildview == "formedit" || $buildview == "formmeta") $arttaglink[$artid] = "<img onClick=\"self.location.href='".$mgmt_config['url_path_cms']."article_edit.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&artid=".url_encode($artid)."&tagname=".url_encode($hypertagname)."&arttitle=".urlencode ($arttitle[$artid])."&artdatefrom=".url_encode($artdatefrom[$artid])."&artdateto=".url_encode($artdateto[$artid])."&artstatus=".url_encode($artstatus[$artid])."&contenttype=".url_encode($contenttype)."';\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_article.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['define-settings-for-article'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['define-settings-for-article'][$lang], $charset, $lang)."\" />";
            else $arttaglink[$artid] = "";
          }          
        }        
      }      

      // =================================================== text content ===================================================

      $searchtag_array[0] = "arttext";
      $searchtag_array[1] = "text";
      $searchtag_array[2] = "comment";
      $infotype = "";
      $position = "";
      $onpublish = "";
      $onedit = "";
      $constraint = "";
      $toolbar = "";
      $label = "";
      $language_info = "";
      $add_submittext = "";
      
      foreach ($searchtag_array as $searchtag)
      {
        // get all hyperCMS tags
        $hypertag_array = gethypertag ($viewstore, $searchtag, 0);    
      
        if ($hypertag_array != false) 
        {
          $id_array = array();
          
          reset ($hypertag_array);
          
          // loop for each hyperCMS tag found in template
          foreach ($hypertag_array as $key => $hypertag)
          {
            // get tag name
            $hypertagname = gethypertagname ($hypertag);

            // get tag id
            $id = getattribute ($hypertag, "id"); 
            
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
            
            // extract text value of checkbox
            $value = getattribute ($hypertag, "value");  
            
            // get value of tag
            $defaultvalue = getattribute ($hypertag, "default");
            
            // get format (if date)
            $format = getattribute ($hypertag, "format");  

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
            
            // get width in pixel of text field
            $sizewidth = getattribute ($hypertag, "width");
            
            if ($sizewidth == false || $sizewidth <= 0) $sizewidth = "600";
            
            // get language attribute
            $language_info = getattribute ($hypertag, "language");
            
            // get group access
            $groupaccess = getattribute ($hypertag, "groups");
            $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);
            
            // get dpi
            $dpi = getattribute ($hypertag, "dpi");
            
            // get colorspace and ICC profile
            $colorspace = getattribute ($hypertag, "colorspace");
            $iccprofile = getattribute ($hypertag, "iccprofile");
            
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
        
            $tagucount = @substr_count ($viewstore, $tagu);
            $tagfcount = @substr_count ($viewstore, $tagf);
            $taglcount = @substr_count ($viewstore, $tagl);
            $tagccount = @substr_count ($viewstore, $tagc);
            $tagdcount = @substr_count ($viewstore, $tagd);
            $tagkcount = @substr_count ($viewstore, $tagk);
        
            $control_sum = 0;
        
            if ($tagucount >= 1) $control_sum++;
            if ($tagfcount >= 1) $control_sum++;
            if ($taglcount >= 1) $control_sum++;
            if ($tagccount >= 1) $control_sum++;
            if ($tagdcount >= 1) $control_sum++;
            if ($tagkcount >= 1) $control_sum++;
        
            // if textu, textf or textl tag have the same id => error
            if ($control_sum >= 2)
            {
              $result['view'] = "<!DOCTYPE html>
              <html>
              <head>
              <title>hyperCMS</title>
              <meta http-equiv=\"Content-Type\" content=\"text/html; charset=".getcodepage ($lang)."\">
              <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">
              <script src=\"".$mgmt_config['url_path_cms']."javascript/click.js\" type=\"text/javascript\">
              </script>
              </head>
              <body class=\"hcmsWorkplaceGeneric\">
                <p class=hcmsHeadline>".$hcms_lang['the-tags'][$lang]." [".$tagu."], [".$tagf."], [".$tagl."], [".$tagc."], [".$tagd."] ".$hcms_lang['and-or'][$lang]." [".$tagk."] ".$hcms_lang['have-the-same-identification-id'][$lang]."</p>
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
              exit;
            }
            else
            {
              if ($buildview != "template")
              {
                // read content using db_connect
                if (isset ($db_connect) && $db_connect != "") 
                {
                  $db_connect_data = db_read_text ($site, $contentfile, $contentdata, $id, $artid, $user);
                  
                  if ($db_connect_data != false) 
                  {
                    $contentbot = $db_connect_data['text'];
                  
                    // set true
                    $db_connect_data = true;                    
                  }
                }   
                else $db_connect_data = false;  
                     
                // read content from content container
                if ($db_connect_data == false)
                {
                  // for comments    
                  if ($searchtag == "comment") 
                  {
                    $bufferarray = selectcontent ($contentdata, "<text>", "<text_id>", $id.':*');
                    $contentcomment = "";
                    
                    if (is_array ($bufferarray)) 
                    {
                      $contentcomment = "<div>\n";
                      
                      foreach ($bufferarray as $data) 
                      {
                        $tmpid = getcontent ($data, "<text_id>");
                        $tmpuser = getcontent ($data, "<textuser>");
                        $tmpcontent = getcontent ($data, "<textcontent>");
                        
                        if (!empty ($tmpid[0]) && !empty ($tmpcontent[0])) 
                        {
                          // only for form views
                          if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") $contentcomment .= "<div style=\"margin-bottom:2px;\">\n";
                          elseif ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview") $contentcomment .= "<div class=\"hcms_comment\">\n";
                          
                          list ($name, $microtime) = explode (":", $tmpid[0]);
                          
                          $date_format = 'Y-m-d H:i:s';
                          if (is_array ($hcms_lang_date) && $hcms_lang_date[$lang] != false) $date_format = $hcms_lang_date[$lang];
                                                    
                          if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") $contentcomment .= "<div class=\"hcmsWorkplaceExplorer\" style=\"width:100%; padding:3px;\">".getescapedtext (str_replace(array('%date%', '%user%'), array(date($date_format, $microtime), $tmpuser[0]), $hcms_lang['date-by-user'][$lang]), $charset, $lang);
                          elseif ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview") $contentcomment .= "<div class=\"hcms_comment_header\">".getescapedtext (str_replace(array('%date%', '%user%'), array(date($date_format, $microtime), $tmpuser[0]), $hcms_lang['date-by-user'][$lang]), $charset, $lang);
                          
                          // is the current user allowed to delete a comment
                          if ($tmpuser[0] == $user || checkadminpermission () || checkglobalpermission ($site, 'user'))
                          {
                            if ($buildview == "formedit" || $buildview == "formmeta") $contentcomment .= "<span style=\"float:right;\"><input id=\"textf_".$tmpid[0]."\" type=\"hidden\" name=\"textf[".$tmpid[0]."]\" DISABLED/><input id='delete_".$tmpid[0]."' type='checkbox' onclick=\"deleteComment(document.getElementById('textf_".$tmpid[0]."'), !this.checked);\"/><label for=\"delete_".$tmpid[0]."\">".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."</label></span>\n";
                            elseif ($buildview == "cmsview" || $buildview == "inlineview") $contentcomment .= "<span style=\"float:right;\"><a hypercms_href=\"".$mgmt_config['url_path_cms']."service/savecontent.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&textf[".$tmpid[0]."]=&token=".$token."\" /><img src=\"".getthemelocation()."img/button_delete.gif\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" style=\"width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a></span>\n";
                          }

                          $contentcomment .= "</div>\n";
                          
                          if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") $contentcomment .= "<div class=\"hcmsRowData2\" style=\"width:100%; padding:3px;\">".$tmpcontent[0]."</div>\n";
                          elseif ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview") $contentcomment .= "<div class=\"hcms_comment_content\">".$tmpcontent[0]."</div>\n";
                          
                          $contentcomment .= "</div>\n";
                        }
                      }
                      
                      if ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                      {
                        // only for form views
                        $contentcomment .= "</div>\n";
                      }
                    }
                    
                    $contentbot = "";
                  }
                  // all other cases
                  else
                  {
                    // get content between tags
                    $bufferarray = selectcontent ($contentdata, "<text>", "<text_id>", $id);
                    $bufferarray = getcontent ($bufferarray[0], "<textcontent>");
                    $contentbot = $bufferarray[0];
                  }
                }
                
                // set default value given eventually by tag
                if ($contentbot == "" && $defaultvalue != "") $contentbot = $defaultvalue;

                // un-comment html tags (important for formatted texts)
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
                    // target dir
                    $viewdir = $mgmt_config['abs_path_view'];
                    //convert image to PNG in the requested colorspace or ICC profile
                    $destination_file = convertimage ($site, $imgdir.$site."/".$imginfo['file'], $viewdir, "png", $colorspace, $iccprofile);
                    // define url of converted image
                    $imagelocation['destination'][] = $mgmt_config['url_path_view'].$destination_file;
                  }
                  
                  // replace the src attributes in the img tags with
                  if (!empty ($imagelocation['source']) && !empty ($imagelocation['destination']))
                  {
                    $contentbot = str_replace ($imagelocation['source'], $imagelocation['destination'], $contentbot);
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
                
                // add slashes if onedit=hidden
                if ($onedit == "hidden")
                {
                  $contentbot = addslashes ($contentbot);
                }
              }
        
              // -------------------------- cmsview and hcms_formviews ---------------------------
              
              if (
                   checklanguage ($language_sessionvalues_array, $language_info) && $onedit != "hidden" && $groupaccess == true && 
                   (
                     (
                      (
                        $buildview == "cmsview" ||
                        $buildview == 'inlineview' 
                      )
                      && $infotype != "meta"
                     ) || 
                     $buildview == "formedit" || 
                     ($buildview == "formmeta" && $infotype == "meta") || 
                     $buildview == "formlock" || 
                     $buildview == "template"
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
                  while (@substr_count ($viewstore_offset, $hypertag) > 0)
                  {                             
                    if ($searchtag == "text")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {                   
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."editor/editoru.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&constraint=".url_encode($constraint)."&contenttype=".url_encode($contenttype)."&width=".url_encode($sizewidth)."&height=".url_encode($sizeheight)."&default=".url_encode($defaultvalue)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_textu.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-unformatted-text'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-unformatted-text'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        $add_submittext .= "submitText ('".$hypertagname."_".$id."', '".$hypertagname."[".$id."]');\n";
                        
                        // if keyword list
                        if ($hypertagname == $searchtag."k")
                        {
                          $list = "";
                          
                          // extract source file (file path or URL) for text list
                          $list_sourcefile = getattribute ($hypertag, "file");
                          
                          if ($list_sourcefile != "")
                          {
                            $list .= @file_get_contents ($list_sourcefile);
                          }
                          
                          // extract text list
                          $list .= getattribute ($hypertag, "list");

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
                          else $keywords_tagit = "";
                          
                          $add_onload .= "
    $('#".$hypertagname."_".$id."').tagit({".$keywords_tagit."singleField:true, singleFieldDelimiter:',', singleFieldNode:$('#".$hypertagname."_".$id."')});";
                          
                          $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b>
                          </td>
                          <td align=left valign=top>
                            <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" />
                            <input name=\"".$hypertagname."_".$id."\" id=\"".$hypertagname."_".$id."\" style=\"width:".$sizewidth."px;\"".$disabled." value=\"".$contentbot."\" />
                          </td>
                        </tr>";
                        }
                        // if unformatted text
                        else
                        {
                          if ($constraint != "") $constraint_array[$key] = "'".$hypertagname."_".$id."','".$labelname."','".$constraint."'";

                          $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b>
                          </td>
                          <td align=left valign=top>
                            <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" />
                            <textarea name=\"".$hypertagname."_".$id."\" style=\"width:".$sizewidth."px; height:".$sizeheight."px;\"".$disabled.">".$contentbot."</textarea>
                          </td>
                        </tr>";
                        }
                      }                        
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:0px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-text-entries'][$lang], $charset, $lang)."</font>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    elseif ($searchtag == "arttext")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview" && $infotype != "meta")
                      {
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."editor/editoru.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&constraint=".url_encode($constraint)."&contenttype=".url_encode($contenttype)."&width=".url_encode($sizewidth)."&height=".url_encode($sizeheight)."&default=".url_encode($defaultvalue)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_textu.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-unformatted-text'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-unformatted-text'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>".$arttaglink[$artid]."\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        $add_submittext .= "submitText ('".$hypertagname."_".$artid."_".$elementid."', '".$hypertagname."[".$id."]');\n";
                        
                        if ($constraint != "") $constraint_array[$key] = "'".$hypertagname."_".$id."','".$labelname."','".$constraint."'";
                        
                        $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b> ".$arttaglink[$artid]."
                          </td>
                          <td align=left valign=top>
                            <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" />
                            <textarea name=\"".$hypertagname."_".$artid."_".$elementid."\" style=\"width:".$sizewidth."px; height:".$sizeheight."px;\"".$disabled.">".$contentbot."</textarea>
                          </td>
                        </tr>";
                      }
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:0px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>article: ".$artid."<br />element: ".$elementid."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-text-entries'][$lang], $charset, $lang)."</font>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    elseif ($searchtag == "comment")
                    {
                      if (!empty ($contentcomment) && ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview"))
                      {
                        $taglink = $contentcomment;
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        $add_submittext .= "submitText ('".$hypertagname."_".$id."', '".$hypertagname."[".$id."]');\n";
                        
                        $formitem[$key] = "
                      <tr>
                        <td align=left valign=top>
                          <b>".$labelname."</b>
                        </td>";
                        if (!empty ($contentcomment)) $formitem[$key] .= "
                        <td>".$contentcomment."</td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>";
                        $formitem[$key] .= "
                        <td align=left valign=top>
                          <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" />
                          <textarea name=\"".$hypertagname."_".$id."\" style=\"width:".$sizewidth."px; height:".$sizeheight."px;\"".$disabled."></textarea>
                        </td>
                      </tr>";
                      }                        
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:0px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-comments'][$lang], $charset, $lang)."</font>
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

                  while (@substr_count ($viewstore_offset, $hypertag) > 0)
                  {                     
                    if ($searchtag == "text")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."editor/editorf.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&width=".url_encode($sizewidth)."&height=".url_encode($sizeheight)."&dpi=".url_encode($dpi)."&toolbar=".url_encode($toolbar)."&default=".url_encode($defaultvalue)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_textf.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-formatted-text'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-formatted-text'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        // register site for editor
                        $_SESSION['site_editor'] = $site;
                        $_SESSION['contenttype_editor'] = $contenttype;
                        
                        // setting the toolbar
                        if (empty ($toolbar)) $toolbar = 'Default';

                        if ($buildview == "formlock")
                        {
                          $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b>
                          </td>
                          <td align=left valign=top>
                            <table border=0 cellspacing=1 bgcolor=#000000 style=\"width:".$sizewidth."px; height:".$sizeheight."px;\">
                              <tr>
                                <td bgcolor=\"#FFFFFF\" align=left valign=top>
                                  ".$contentbot."
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>";
                        }
                        else
                        {
                          $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b>
                          </td>
                          <td align=left valign=top>
                          ".showeditor ($site, $hypertagname, $id, $contentbot, $sizewidth, $sizeheight, $toolbar, $lang, $dpi)."
                          </td>
                        </tr>";
                        }
                      }                      
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-formatted-text-entries'][$lang], $charset, $lang)."</font>
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
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."editor/editorf.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&contentfile=".url_encode($contentfile)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&width=".url_encode($sizewidth)."&height=".url_encode($sizeheight)."&dpi=".url_encode($dpi)."&toolbar=".url_encode($toolbar)."&default=".url_encode($defaultvalue)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_textf.gif\" alt=\"".$artid.": ".$elementid.": ".getescapedtext ($hcms_lang['edit-formatted-text'][$lang], $charset, $lang)."\" title=\"".$artid.": ".$elementid.": ".getescapedtext ($hcms_lang['edit-formatted-text'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>".$arttaglink[$artid]."\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        // register site for editor
                        $_SESSION['site_editor'] = $site;
                        $_SESSION['contenttype_editor'] = $contenttype;                                              
                        
                        // setting the toolbar
                        if (empty ($toolbar)) $toolbar = 'Default';                        

                        if ($buildview == "formlock")
                        {
                          $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$label."</b>
                          </td>
                          <td align=left valign=top>
                            <table border=0 cellspacing=1 bgcolor=#000000 style=\"width:".$sizewidth."px; height:".$sizeheight."px;\">
                              <tr>
                                <td bgcolor=bgcolor=\"#FFFFFF\" align=left valign=top>
                                ".$contentbot."
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>";
                        }
                        else
                        {
                          $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$label."</b> ".$arttaglink[$artid]."
                          </td>
                          <td align=left valign=top>
                          ".showeditor ($site, $hypertagname, $id, $contentbot, $sizewidth, $sizeheight, $toolbar, $lang, $dpi)."
                          </td>
                        </tr>";
                        }
                      }                      
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=\"#000000\"><b>article: ".$artid."<br />element: ".$elementid."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-formatted-text-entries'][$lang], $charset, $lang)."</font>
                            </td>
                          </tr>
                        </table>";
                      }
                      else $taglink = "";
                    }
                    elseif ($searchtag == "comment")
                    {
                      if (!empty ($contentcomment) && ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "preview"))
                      {
                        $taglink = $contentcomment;
                      }
                      // create tag link for editor
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        // register site for editor
                        $_SESSION['site_editor'] = $site;
                        $_SESSION['contenttype_editor'] = $contenttype;
                        
                        // setting the toolbar
                        if (empty ($toolbar)) $toolbar = 'Default';

                        if ($buildview == "formlock")
                        {
                          $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b>
                          </td>
                          <td>
                            ".$contentcomment."
                          </td>
                        </tr>";
                        }
                        else 
                        {
                          $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b>
                          </td>";
                          if (!empty ($contentcomment)) $formitem[$key] .= "<td>".$contentcomment."</td></tr><tr><td>&nbsp;</td>";
                          $formitem[$key] .= "
                          <td align=left valign=top>
                            ".showeditor ($site, $hypertagname, $id, $contentbot, $sizewidth, $sizeheight, $toolbar, $lang, $dpi)."
                          </td>
                        </tr>";
                        }
                      }                        
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:0px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>element: ".$id."</b><br />
                              ".$hcms_lang['this-place-is-reserved-for-formatted-comments'][$lang]."</font>
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
        
                  while (@substr_count ($viewstore_offset, $hypertag) > 0)
                  {                 
                    // extract text list
                    $list = getattribute ($hypertag, "list");
                    
                    if ($searchtag == "text")
                    { 
                      // create tag link for editor               
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."editor/editorl.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&list=".url_encode($list)."&contenttype=".url_encode($contenttype)."&default=".url_encode($defaultvalue)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_textl.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-text-options'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-text-options'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        // get list entries
                        if ($list != "")
                        {
                          $list_array = explode ("|", $list);
  
                          $formitem[$key] = "
                          <tr>
                            <td align=left valign=top>
                              <b>".$labelname."</b>
                            </td>
                            <td align=left valign=top>
                              <select name=\"".$hypertagname."[".$id."]\"".$disabled.">\n";
                          
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
                                <option value=\"".$list_value."\""; 
                            if ($list_value == $contentbot) $formitem[$key] .= " selected"; 
                            $formitem[$key] .= ">".$list_text."</option>\n";
                          }
                                         
                          $formitem[$key] .= "
                              </select>
                            </td>
                          </tr>";
                        }
                      }
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-text-options'][$lang], $charset, $lang)."</font>
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
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."editor/editorl.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&contentfile=".url_encode($contentfile)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&list=".url_encode($list)."&contenttype=".url_encode($contenttype)."&default=".url_encode($defaultvalue)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_textl.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-text-options'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-text-options'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>".$arttaglink[$artid]."\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        // get list entries
                        $list_array = null;
                        $list_array = explode ("|", $list);

                        $formitem[$key] = "
                      <tr>
                        <td align=left valign=top>
                          <b>".$labelname."</b> ".$arttaglink[$artid]."
                        </td>
                        <td align=left valign=top>
                          <select name=\"".$hypertagname."[".$id."]\"".$disabled.">\n";
                        
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
                            <option value=\"".$list_value."\""; 
                          if ($list_entry == $contentbot) $formitem[$key] .= " selected"; 
                          $formitem[$key] .= ">".$list_text."</option>\n";
                        }
                                       
                        $formitem[$key] .= "
                          </select>
                        </td>
                      </tr>";
                      }                      
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>article: ".$artid."<br />element: ".$elementid."</b><br />
                              ".$hcms_lang['this-place-is-reserved-for-text-options'][$lang]."</font>
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
        
                  while (@substr_count ($viewstore_offset, $hypertag) > 0)
                  {                                
                    if ($searchtag == "text")
                    { 
                      // create tag link for editor               
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."editor/editorc.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&value=".url_encode($value)."&contentbot=".url_encode($contentbot)."&default=".url_encode($defaultvalue)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_textc.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['set-checkbox'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-checkbox'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        if ($value == $contentbot) $checked = " checked";
                        else $checked = "";
                        
                        $formitem[$key] = "
                      <tr>
                        <td align=left valign=top>
                          <b>".$labelname."</b>
                        </td>
                        <td align=left valign=top>
                          <input type=\"hidden\" name=\"".$hypertagname."[".$id."]"."\" value=\"\">
                          <input type=\"checkbox\" name=\"".$hypertagname."[".$id."]\" value=\"".$value."\"".$checked.$disabled."> ".$value."
                        </td>
                      </tr>";
                      }
                      elseif ($buildview == "template" && $onedit != "hidden")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:0px; border:1px solid #000000; background-color:#FFFFFF;\"0>
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-a-checkbox'][$lang], $charset, $lang)."</font>
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
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."editor/editorl.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&value=".url_encode($value)."&contentbot=".url_encode($contentbot)."&default=".url_encode($defaultvalue)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_textc.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['set-checkbox'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-checkbox'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>".$arttaglink[$artid]."\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {
                        if ($value == $contentbot) $checked = " checked";
                        else $checked = "";
                                              
                        $formitem[$key] = "
                      <tr>
                        <td align=left valign=top>
                          <b>".$labelname."</b> ".$arttaglink[$artid]."
                        </td>
                        <td align=left valign=top>
                          <input type=\"hidden\" name=\"".$hypertagname."[".$id."]"."\" value=\"\">
                          <input type=\"checkbox\" name=\"".$hypertagname."[".$id."]\" value=\"".$value."\"".$checked.$disabled."> ".$value."
                        </td>
                      </tr>";
                      }                      
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:0px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>article: ".$artid."<br />
                              element: ".$elementid."</b><br />".$hcms_lang['this-place-is-reserved-for-a-checkbox'][$lang]."</font>
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
                  
                  if ($format == "") $format = "%Y-%m-%d";
        
                  while (@substr_count ($viewstore_offset, $hypertag) > 0)
                  {                                
                    if ($searchtag == "text")
                    { 
                      // create tag link for editor               
                      if ($buildview == "cmsview")
                      {
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."editor/editord.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&format=".url_encode($format)."&contentbot=".url_encode($contentbot)."&default=".url_encode($defaultvalue)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_textd.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {                        
                        if ($disabled == "") $showcalendar = "onclick=\"show_cal_".$id."(this);\"";
                        else $showcalendar = "";
                        
                        $formitem[$key] = "
                      <tr>
                        <td align=left valign=top>
                          <b>".$labelname."</b>
                        </td>
                        <td align=left valign=top>
                          <input type=\"text\" id=\"datefield_".$id."\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" ".$disabled." />
                          <img name=\"datepicker\" src=\"".getthemelocation()."img/button_datepicker.gif\" ".$showcalendar." align=\"absmiddle\" style=\"width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" alt=\"".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" ".$disabled." />
                          <script language=\"JavaScript\" type=\"text/javascript\">
                          <!--
                           var cal_obj_".$id." = null;
                           var format_".$id." = '".$format."';
                           
                           function show_cal_".$id." (el)
                           {
                             if (cal_obj_".$id.") return;
                             var datefield_".$id." = document.getElementById('datefield_".$id."');  
                             
                             cal_obj_".$id." = new RichCalendar();
                             cal_obj_".$id.".start_week_day = 1;
                             cal_obj_".$id.".show_time = false;
                             cal_obj_".$id.".language = '".getcalendarlang ($lang)."';
                             cal_obj_".$id.".user_onchange_handler = cal_on_change_".$id.";
                             cal_obj_".$id.".user_onautoclose_handler = cal_on_autoclose_".$id.";
                             cal_obj_".$id.".parse_date(datefield_".$id.".value, format_".$id.");
                             cal_obj_".$id.".show_at_element(datefield_".$id.", 'adj_left-bottom');
                           }
                           
                           // onchange handler
                           function cal_on_change_".$id." (cal, object_code)
                           {
                             if (object_code == 'day')
                             {
                               document.getElementById('datefield_".$id."').value = cal.get_formatted_date(format_".$id.");
                               document.getElementById('textfield_".$id."').value = cal.get_formatted_date(format_".$id.");
                               cal.hide();
                               cal_obj_".$id." = null;
                             }
                           }
                            
                           // onautoclose handler
                           function cal_on_autoclose_".$id." (cal)
                           {
                             cal_obj_".$id." = null;
                           }
                          -->
                          </script>                                           
                        </td>
                      </tr>";
                      }
                      elseif ($buildview == "template" && $onedit != "hidden")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:0px; border:1px solid #000000; background-color:#FFFFFF;\"0>
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['this-place-is-reserved-for-a-date-field'][$lang], $charset, $lang)."</font>
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
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."editor/editord.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&contenttype=".url_encode($contenttype)."&format=".url_encode($format)."&contentbot=".url_encode($contentbot)."&default=".url_encode($defaultvalue)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_textd.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>".$arttaglink[$artid]."\n";
                      }
                      elseif (($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock") && isset ($foundtxt[$id]) && $foundtxt[$id] == true)
                      {                     
                        if ($disabled == "") $showcalendar = "onclick=\"show_cal_".$artid."_".$elementid."(this);\"";
                        else $showcalendar = "";
                                              
                        $formitem[$key] = "
                      <tr>
                        <td align=left valign=top>
                          <b>".$labelname."</b> ".$arttaglink[$artid]."
                        </td>
                        <td align=left valign=top>
                          <input type=\"hidden\" id=\"datefield_".$artid."_".$elementid."\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" />
                          <input type=\"text\" id=\"textfield_".$artid."_".$elementid."\" value=\"".$contentbot."\" ".$disabled." /><img name=\"datepicker\" src=\"".getthemelocation()."img/button_datepicker.gif\" ".$showcalendar." align=\"absmiddle\" style=\"width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" alt=\"".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang)."\" ".$disabled." />
                          <script language=\"JavaScript\" type=\"text/javascript\">
                          <!--
                           var cal_obj_".$artid."_".$elementid." = null;
                           var format_".$artid."_".$elementid." = '".$format."';
                           
                           function show_cal_".$artid."_".$elementid." (el) {
                             if (cal_obj_".$artid."_".$elementid.") return;
                             var datefield_".$artid."_".$elementid." = document.getElementById('datefield_".$artid."_".$elementid."');  
                             
                             cal_obj_".$artid."_".$elementid." = new RichCalendar();
                             cal_obj_".$artid."_".$elementid.".start_week_day = 1;
                             cal_obj_".$artid."_".$elementid.".show_time = false;
                             cal_obj_".$artid."_".$elementid.".language = '".getcalendarlang ($lang)."';
                             cal_obj_".$artid."_".$elementid.".user_onchange_handler = cal_on_change_".$artid."_".$elementid.";
                             cal_obj_".$artid."_".$elementid.".user_onautoclose_handler = cal_on_autoclose_".$artid."_".$elementid.";
                             cal_obj_".$artid."_".$elementid.".parse_date(datefield_".$artid."_".$elementid.".value, format_".$artid."_".$elementid.");
                             cal_obj_".$artid."_".$elementid.".show_at_element(datefield_".$artid."_".$elementid.", 'adj_left-bottom');
                           }
                           
                           // onchange handler
                           function cal_on_change_".$artid."_".$elementid." (cal, object_code)
                           {
                             if (object_code == 'day')
                             {
                               document.getElementById('datefield_".$artid."_".$elementid."').value = cal.get_formatted_date(format_".$artid."_".$elementid.");
                               document.getElementById('textfield_".$artid."_".$elementid."').value = cal.get_formatted_date(format_".$artid."_".$elementid.");
                               cal.hide();
                               cal_obj_".$artid."_".$elementid." = null;
                             }
                           }
                            
                           // onautoclose handler
                           function cal_on_autoclose_".$artid."_".$elementid." (cal)
                           {
                             cal_obj_".$artid."_".$elementid." = null;
                           }                                           
                          -->
                          </script>                                            
                        </td>
                      </tr>";
                      }                      
                      elseif ($buildview == "template")
                      {
                        $taglink = "<table style=\"width:200px; padding:0px; border:1px solid #000000; background-color:#FFFFFF;\">\n  <tr>\n    <td>\n      <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>article: ".$artid."<br />element: ".$elementid."</b><br />".getescapedtext ($hcms_lang['this-place-is-reserved-for-a-checkbox'][$lang], $charset, $lang)."</font>\n    </td>\n  </tr>\n</table>\n";
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
              }
            }

            // ------------------- publish / insert content -------------------       
            if ($buildview != "template" && $buildview != "formedit" && $buildview != "formmeta" && $buildview != "formlock")
            {
              // include time management code for article
              if ($buildview == "publish" && $searchtag == "arttext")
              {
                if ($artstatus[$artid] == "timeswitched")
                {
                  // escape specific characters
                  $contentbot = str_replace ("'", "\\'", $contentbot);
                  $contentbot = str_replace ("\$", "\\\$'", $contentbot);
                  $contentbot = str_replace ("\\", "\\\\", $contentbot);
      
                  $contentbot = tpl_tselement ($application, $artdatefrom[$artid], $artdateto[$artid], $contentbot);
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
                 
                $contentbot = showinlineeditor ($site, $hypertag, $id, $contentbot, $sizewidth, $sizeheight, $toolbar, $lang, $contenttype, $cat, $location_esc, $page, $contentfile, $db_connect, $token);
                
                // insert content
                $viewstore = str_replace ($hypertag, $contentbot, $viewstore);
              }
              // for all other modes
              else
              {
                // insert content
                if ($onpublish != "hidden") $viewstore = str_replace ($hypertag, $contentbot, $viewstore);
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
      $language_info = array();

      foreach ($searchtag_array as $searchtag)
      {
        // get all hyperCMS tags
        $hypertag_array = gethypertag ($viewstore, $searchtag, 0);
      
        if ($hypertag_array != false) 
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
            if (!isset ($hypertag_file[$id][$tagid])) $hypertag_file[$id][$tagid] = "";
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
              if ($hypertagname == $searchtag."file" && empty ($file_found[$id]))
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
              if (empty ($mediabot[$id]))
              {
                // read content using db_connect
                if (isset ($db_connect) && $db_connect != "") 
                {
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
                else $db_connect_data = false;
              }
                        
              // read content from content container
              if ($db_connect_data == false)
              {                  
                // get the whole media object information of the content container
                if (empty ($mediabot[$id]))
                {
                  $bufferarray = selectcontent ($contentdata, "<media>", "<media_id>", $id);
                  $mediabot[$id] = $bufferarray[0];         
                } 
                
                // get the media file name and object link from mediabot            
                if ($hypertagname == $searchtag."file" && !isset ($mediafilebot[$id][$tagid]))
                {                   
                  $bufferarray = getcontent ($mediabot[$id], "<mediafile>");
                  $mediafilebot[$id][$tagid] = $bufferarray[0];
                  $bufferarray = getcontent ($mediabot[$id], "<mediaobject>");
                  $mediaobjectbot[$id] = $bufferarray[0];    
                  
                  // check if linked multimedia component exists
                  if (empty ($mediafilebot[$id][$tagid]))
                  {
                    $mediaobjectpath = deconvertpath ($mediaobjectbot[$id], "file");
                    $media_data = loadfile (getlocation ($mediaobjectpath), getobject ($mediaobjectpath));
                    
                    if ($media_data != false)
                    {
                      $mediafilebot[$id][$tagid] = $mediasite."/".getfilename ($media_data, "media");
                      $contentdata = setcontent ($contentdata, "<media>", "<mediafile>", $mediafilebot[$id][$tagid], "<media_id>", $id);
                    }
                    else $mediafilebot[$id][$tagid] = "";  
                  }                              
                }                     
                // get the media alttext name from mediabot              
                elseif ($hypertagname == $searchtag."alttext" && !isset ($mediaalttextbot[$id]))
                {
                  $bufferarray = getcontent ($mediabot[$id], "<mediaalttext>");
                  $mediaalttextbot[$id] = $bufferarray[0]; 
                  // escape special characters
                  $mediaalttextbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $mediaalttextbot[$id]);                  
                }
                // get the media alignment name from mediabot  
                elseif ($hypertagname == $searchtag."align" && !isset ($mediaalignbot[$id]))
                {
                  $bufferarray = getcontent ($mediabot[$id], "<mediaalign>");
                  $mediaalignbot[$id] = $bufferarray[0];
                  // escape special characters
                  $mediaalignbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $mediaalignbot[$id]);                    
                }
                // get the media width name from mediabot  
                elseif ($hypertagname == $searchtag."width" && !isset ($mediawidthbot[$id]))
                {
                  $bufferarray = getcontent ($mediabot[$id], "<mediawidth>");
                  $mediawidthbot[$id] = $bufferarray[0];
                  // escape special characters
                  $mediawidthbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $mediawidthbot[$id]);                    
                }
                // get the media height name from mediabot  
                elseif ($hypertagname == $searchtag."height" && !isset ($mediaheightbot[$id]))
                {
                  $bufferarray = getcontent ($mediabot[$id], "<mediaheight>");
                  $mediaheightbot[$id] = $bufferarray[0];
                  // escape special characters
                  $mediaheightbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $mediaheightbot[$id]);                    
                }
              }
              
              // get hyperCMS tags attributes (specific for each tag found in template)            
              if ($hypertagname == $searchtag."file")
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
                if (!isset ($infotype[$id]) || $infotype[$id] != "meta") $infotype[$id] = getattribute ($hypertag, "infotype");
                // get dpi for scaling
                $mediadpi[$id] = getattribute ($hypertag, "dpi");
                // get colorspace and ICC profile
                $mediacolorspace[$id][$tagid] = getattribute ($hypertag, "colorspace");
                $mediaiccprofile[$id][$tagid] = getattribute ($hypertag, "iccprofile");
                // get path type [file,url,abs]
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
                // target dir
                $viewdir = $mgmt_config['abs_path_view'];
                // convert image to PNG in the requested colorspace or ICC profile
                $mediafilebot_new = convertimage ($site, $imgdir.$mediafilebot[$id][$tagid], $viewdir, "png", $mediacolorspace[$id][$tagid], $mediaiccprofile[$id][$tagid]);
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
            // set default values if the tags and therefore their content was not found
            if (!isset ($mediaalttextbot[$id])) $mediaalttextbot[$id] = "";
            if (!isset ($mediaalignbot[$id])) $mediaalignbot[$id] = "";
            if (!isset ($mediawidthbot[$id])) $mediawidthbot[$id] = "";
            if (!isset ($mediaheightbot[$id])) $mediaheightbot[$id] = "";
          
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
            
            // set media bots for non existing hyperCMS tags
            if ($buildview != "template")
            {
              if (empty ($file_found[$id])) $mediafilebot[$id][$tagid] = "*Null*"; 
              if (empty ($text_found[$id])) $mediaalttextbot[$id] = "*Null*"; 
              if (empty ($align_found[$id])) $mediaalignbot[$id] = "*Null*";  
              if (empty ($width_found[$id])) $mediawidthbot[$id] = "*Null*";  
              if (empty ($height_found[$id])) $mediaheightbot[$id] = "*Null*";             
            }
            // for buildview = template
            else
            {
              $mediafilebot[$id][$tagid] = ""; 
              $mediaalttextbot[$id] = ""; 
              $mediaalignbot[$id] = "";  
              $mediawidthbot[$id] = ""; 
              $mediaheightbot[$id] = "";  
            }               
            
            // loop for each unique tag
            for ($tagid = 1; $tagid <= $tagid_max; $tagid++)
            {
              if (isset ($hypertag_file[$id][$tagid]) || isset ($hypertag_text[$id][$tagid]) || isset ($hypertag_align[$id][$tagid]) || isset ($hypertag_width[$id][$tagid]) || isset ($hypertag_height[$id][$tagid]))   
              {
                // get group access
                if (!empty ($hypertag_file[$id][$tagid]))
                {
                  $groupaccess = getattribute ($hypertag_file[$id][$tagid], "groups");
                  $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);
                }
                else $groupaccess = true;
                
                // ------------------------- cmsview ---------------------------
                
                if (
                     checklanguage ($language_sessionvalues_array, $language_info[$id]) && $groupaccess == true && 
                     isset ($hypertag_file[$id][$tagid]) && $onedit_file[$id][$tagid] != "hidden" &&
                     (
                       (
                        (
                          $buildview == "cmsview" || 
                          $buildview == "inlineview"
                        ) && 
                        $infotype[$id] != "meta"
                       ) || 
                       $buildview == "formedit" || 
                       ($buildview == "formmeta" && $infotype[$id] == "meta") || 
                       $buildview == "formlock" || 
                       $buildview == "template"
                     )
                   )
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;
            
                  while (@substr_count ($viewstore_offset, $hypertag_file[$id][$tagid]) > 0)
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
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."frameset_edit_media.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=media&mediacat=comp&mediafile=".url_encode($mediafilebot[$id][$tagid])."&mediaobject_curr=".url_encode($mediaobjectbot[$id])."&mediaobject=".url_encode($mediaobjectbot[$id])."&mediaalttext=".url_encode($mediaalttextbot[$id])."&mediaalign=".url_encode($mediaalignbot[$id])."&mediawidth=".url_encode($mediawidthbot[$id])."&mediaheight=".url_encode($mediaheightbot[$id])."&scaling=".url_encode($scalingfactor)."&mediatype=".url_encode($mediatype[$id])."&contenttype=".url_encode($contenttype)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_media.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>\n";
                      }
                      elseif ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                      {
                        if ($buildview == "formedit" || $buildview == "formmeta")
                        {
                          $taglink = " <img onClick=\"openBrWindowComp(document.forms['hcms_formview'].elements['mediaobject[".$id."]'],'','scrollbars=yes,resizable=yes,width=800,height=600', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.gif\" align=\"absmiddle\" alt=\"".$hcms_lang['edit'][$lang]."\" title=\"".$hcms_lang['edit'][$lang]."\" />
                          <img onClick=\"deleteEntry(document.forms['hcms_formview'].elements['".$hypertagname_file[$id]."[".$id."]']); deleteEntry(document.forms['hcms_formview'].elements['mediaobject[".$id."]']);\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.gif\" align=\"absmiddle\" alt=\"".$hcms_lang['delete'][$lang]."\" title=\"".$hcms_lang['delete'][$lang]."\" />
                          <img onClick=\"setSaveType('form_so', '".$mgmt_config['url_path_cms']."frameset_edit_media.php?view=".url_encode($buildview)."&savetype=form_so&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&contentfile=".url_encode($contentfile)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".urlencode($label[$id])."&tagname=media&mediacat=comp&mediafile=".url_encode($mediafilebot[$id][$tagid])."&mediaobject_curr=".url_encode($mediaobjectbot[$id])."&mediaobject=' + getValue('".$hypertagname_file[$id]."[".$id."]','') + '&mediaalttext=' + getValue('".$hypertagname_text[$id]."[".$id."]','*Null*') + '&mediaalign=' + getSelectedOption('".$hypertagname_align[$id]."[".$id."]','*Null*') + '&mediawidth=' + getValue('".$hypertagname_width[$id]."[".$id."]','*Null*') + '&mediaheight=' + getValue('".$hypertagname_height[$id]."[".$id."]','*Null*') + '&scaling=".url_encode($scalingfactor)."&mediatype=".url_encode($mediatype[$id])."&contenttype=".url_encode($contenttype)."');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_media.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" />\n";
                        }
                        else $taglink = "";
                        
                        $formitem[$key] = "<tr><td align=left valign=top><b>".$labelname."</b></td><td align=left valign=top><table>\n";

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
                          
                          if ($mediatype[$id] != "") $constraint_array[$key] = "'".$hypertagname_file[$id]."[".$id."]', '".$labelname.", ".$hcms_lang['multimedia-file'][$lang]."', '".$mediatype[$id]."'";
                          
                          $formitem[$key] .= "<tr><td colspan=2>".showmedia ($mediafilebot[$id][$tagid], convertchars ($mediaobjectname, $hcms_lang_codepage[$lang], $charset), "preview_no_rendering")."</td>
                          </tr>
                          <tr>
                            <td width=\"150\">".getescapedtext ($hcms_lang['multimedia-file'][$lang], $charset, $lang).":</td>
                            <td>
                              <input type=\"hidden\" name=\"mediaobject_curr[".$id."]\" value=\"".$mediaobjectbot[$id]."\" />
                              <input type=\"hidden\" name=\"mediaobject[".$id."]\" value=\"".$mediaobjectbot[$id]."\" />
                              <input name=\"".$hypertagname_file[$id]."[".$id."]\" value=\"".convertchars ($mediaobjectname, $hcms_lang_codepage[$lang], $charset)."\" style=\"width:350px;\" ".$disabled." />".$taglink."
                            </td>
                          </tr>\n";
                        }
                        
                        if ($mediaalttextbot[$id] != "*Null*") $formitem[$key] .= "<tr><td width=\"150\">".getescapedtext ($hcms_lang['alternative-text'][$lang], $charset, $lang).":</td><td><input name=\"".$hypertagname_text[$id]."[".$id."]\" value=\"".$mediaalttextbot[$id]."\" style=\"width:350px;\"".$disabled." /></td></tr>\n";
                        
                        if ($mediaalignbot[$id] != "*Null*")
                        {
                          $formitem[$key] .= "<tr><td width=\"150\">".getescapedtext ($hcms_lang['alignment'][$lang], $charset, $lang).":</td><td><select name=\"".$hypertagname_align[$id]."[".$id."]\" style=\"width:350px;\"".$disabled.">\n";
                          $formitem[$key] .= "<option value=\"\""; if ($mediaalignbot[$id] == "") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['standard'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "<option value=\"top\""; if ($mediaalignbot[$id] == "top") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['top'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "<option value=\"middle\""; if ($mediaalignbot[$id] == "middle") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['middle'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "<option value=\"absmiddle\""; if ($mediaalignbot[$id] == "absmiddle") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['absolute-middle'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "<option value=\"bottom\""; if ($mediaalignbot[$id] == "bottom") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['bottom'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "<option value=\"left\""; if ($mediaalignbot[$id] == "left") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['left'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "<option value=\"right\""; if ($mediaalignbot[$id] == "right") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['right'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "</select></td></tr>\n";          
                        }
                        
                        if ($mediawidthbot[$id] != "*Null*")
                        {
                          $constraint_array[$key] = "'".$hypertagname_width[$id]."[".$id."]', '".$labelname.", ".$hcms_lang['width'][$lang]."','NisNum'";
                          $formitem[$key] .= "<tr><td width=\"150\">".getescapedtext ($hcms_lang['width'][$lang], $charset, $lang).":</td><td><input name=\"".$hypertagname_width[$id]."[".$id."]\" value=\"".$mediawidthbot[$id]."\" size=4".$disabled." /></td></tr>\n";
                        }
                        
                        if ($mediaheightbot[$id] != "*Null*")
                        {
                          $constraint_array[$key] = "'".$hypertagname_height[$id]."[".$id."]', '".$labelname.", ".$hcms_lang['height'][$lang]."','NisNum'";
                          $formitem[$key] .= "<tr><td width=\"150\">".getescapedtext ($hcms_lang['height'][$lang], $charset, $lang).":</td><td><input name=\"".$hypertagname_height[$id]."[".$id."]\" value=\"".$mediaheightbot[$id]."\" size=4".$disabled." /></td></tr>\n";
                        }
                        
                        $formitem[$key] .= "</table></td></tr>";
                      }
                      elseif ($buildview == "template")
                      {
                        $taglink = "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\"><tr><td><font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>element: ".$id."</b><br />".getescapedtext ($hcms_lang['this-place-is-reserved-for-media-image'][$lang], $charset, $lang)."</font></td></tr></table>\n";
                      }
                      else $taglink = "";
                    }
                    elseif ($searchtag == "artmedia")
                    {
                      // create tag link
                      if ($buildview == "cmsview" || $buildview == 'inlineview')
                      {
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."frameset_edit_media.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=artmedia&mediacat=comp&mediafile=".url_encode($mediafilebot[$id][$tagid])."&mediaobject_curr=".url_encode($mediaobjectbot[$id])."&mediaobject=".url_encode($mediaobjectbot[$id])."&mediaalttext=".urlencode($mediaalttextbot[$id])."&mediaalign=".urlencode($mediaalignbot[$id])."&mediawidth=".url_encode($mediawidthbot[$id])."&mediaheight=".url_encode($mediaheightbot[$id])."&mediatype=".url_encode($mediatype[$id])."&contenttype=".url_encode($contenttype)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_media.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>".$arttaglink[$artid]."\n";
                      }
                      elseif ($buildview == "formedit" || ($buildview == "formmeta" && $infotype[$id] == "meta") || $buildview == "formlock")
                      {
                        if ($buildview == "formedit" || $buildview == "formmeta")
                        {
                          $taglink = " <img onClick=\"openBrWindowComp(document.forms['hcms_formview'].elements['artmediaobject[".$id."]'],'','scrollbars=yes,resizable=yes,width=800,height=600', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" />
                          <img onClick=\"deleteEntry(document.forms['hcms_formview'].elements['".$hypertagname_file[$id]."[".$id."]']); deleteEntry(document.forms['hcms_formview'].elements['artmediaobject[".$id."]']);\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" />
                          <img onClick=\"setSaveType('form_so', '".$mgmt_config['url_path_cms']."frameset_edit_media.php?view=".url_encode($buildview)."&savetype=form_so&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=media&mediacat=comp&mediafile=".url_encode($mediafilebot[$id][$tagid])."&mediaobject_curr=".url_encode($mediaobjectbot[$id])."&mediaobject=' + getValue('".$hypertagname_file[$id]."[".$id."]','') + '&mediaalttext=' + getValue('".$hypertagname_text[$id]."[".$id."]','*Null*') + '&mediaalign=' + getSelectedOption('".$hypertagname_align[$id]."[".$id."]','*Null*') + '&mediawidth=' + getValue('".$hypertagname_width[$id]."[".$id."]','*Null*') + '&mediaheight=' + getValue('".$hypertagname_height[$id]."[".$id."]','*Null*') + '&mediatype=".$mediatype[$id]."&contenttype=".$contenttype."');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_media.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['set-media'][$lang], $charset, $lang)."\" />\n";
                        }
                        else $taglink = "";
                        
                        $formitem[$key] = "<tr><td align=left valign=top><b>".$labelname."</b> ".$arttaglink[$artid]."</td><td align=left valign=top><table>\n";

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
                          
                          if ($mediatype[$id] != "") $constraint_array[$key] = "'".$hypertagname_file[$id]."[".$id."]', '".$labelname.", ".$hcms_lang['multimedia-file'][$lang]."', '".$mediatype[$id]."'";
                                       
                          $formitem[$key] .= "<tr><td colspan=2>".showmedia ($mediafilebot[$id][$tagid], convertchars ($mediaobjectname, $hcms_lang_codepage[$lang], $charset), "preview_no_rendering")."</td>
                          </tr>
                          <tr>
                            <td width=\"150\">".getescapedtext ($hcms_lang['multimedia-file'][$lang], $charset, $lang).":</td>
                            <td>                          
                              <input type=\"hidden\" name=\"artmediaobject_curr[".$id."]\" value=\"".$mediaobjectbot[$id]."\" />
                              <input type=\"hidden\" name=\"artmediaobject[".$id."]\" value=\"".$mediaobjectbot[$id]."\" />
                              <input name=\"".$hypertagname_file[$id]."[".$id."]\" value=\"".convertchars ($mediaobjectname, $hcms_lang_codepage[$lang], $charset)."\" style=\"width:350px;\"".$disabled." />".$taglink."
                            </td>
                          </tr>\n";
                        }
                        
                        if ($mediaalttextbot[$id] != "*Null*") $formitem[$key] .= "<tr><td width=\"150\">".getescapedtext ($hcms_lang['alternative-text'][$lang], $charset, $lang).":</td><td><input type=\"hidden\" name=\"".$hypertagname_text[$id]."[".$id."]\" /><input name=\"".$hypertagname_text[$id]."_".$artid."_".$elementid."\" value=\"".$mediaalttextbot[$id]."\" style=\"width:350px;\"".$disabled."></td></tr>\n";
                        
                        if ($mediaalignbot[$id] != "*Null*")
                        {
                          $formitem[$key] .= "<tr><td width=\"150\">".getescapedtext ($hcms_lang['alignment'][$lang], $charset, $lang).":</td><td><select name=\"".$hypertagname_align[$id]."[".$id."]\" style=\"width:350px;\"".$disabled.">\n";
                          $formitem[$key] .= "<option value=\"\""; if ($mediaalignbot[$id] == "") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['standard'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "<option value=\"top\""; if ($mediaalignbot[$id] == "top") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['top'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "<option value=\"middle\""; if ($mediaalignbot[$id] == "middle") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['middle'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "<option value=\"absmiddle\""; if ($mediaalignbot[$id] == "absmiddle") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['absolute-middle'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "<option value=\"bottom\""; if ($mediaalignbot[$id] == "bottom") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['bottom'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "<option value=\"left\""; if ($mediaalignbot[$id] == "left") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['left'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "<option value=\"right\""; if ($mediaalignbot[$id] == "right") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['right'][$lang], $charset, $lang)."</option>\n";
                          $formitem[$key] .= "</select></td></tr>\n";                       
                        }
                        
                        if ($mediawidthbot[$id] != "*Null*")
                        {
                          $constraint_array[$key] = "'".$hypertagname_width[$id]."[".$id."]', '".$labelname.", ".$hcms_lang['width'][$lang]."','NisNum'";
                          $formitem[$key] .= "<tr><td width=\"150\">".getescapedtext ($hcms_lang['width'][$lang], $charset, $lang).":</td><td><input name=\"".$hypertagname_width[$id]."[".$id."]\" value=\"".$mediawidthbot[$id]."\" size=4".$disabled."></td></tr>\n";
                        }
                        
                        if ($mediaheightbot[$id] != "*Null*")
                        {
                          $constraint_array[$key] = "'".$hypertagname_height[$id]."[".$id."]', '".$labelname.", ".$hcms_lang['height'][$lang]."','NisNum'";
                          $formitem[$key] .= "<tr><td width=\"150\">".getescapedtext ($hcms_lang['height'][$lang], $charset, $lang).":</td><td><input name=\"".$hypertagname_height[$id]."[".$id."]\" value=\"".$mediaheightbot[$id]."\" size=4".$disabled."></td></tr>\n";
                        }
                        
                        $formitem[$key] .= "</table></td></tr>";                  
                      }
                      elseif ($buildview == "template")
                      {
                        $taglink = "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\"><tr><td><font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>article ".$artid.":<br />element: ".$elementid."<br /></b>".getescapedtext ($hcms_lang['this-place-is-reserved-for-media-image'][$lang], $charset, $lang)."</font></td></tr></table>\n";
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
                        $viewstore = substr_replace ($viewstore, $imgtag_new, $repl_start, $repl_len);
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
                elseif ($buildview == "publish" && $searchtag == "artmedia" && isset ($hypertag_file[$id][$tagid]) && $onpublish_file[$id][$tagid] != "hidden")
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;
        
                  while (@substr_count ($viewstore_offset, $hypertag_file[$id][$tagid]) >= 1)
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
                  } // end while loop
                } 
              
                // define Null media if mediafilebot is empty and set URL to content media repository 
                if (empty ($mediafilebot[$id][$tagid]))
                {
                  // copy Null media to template media directory
                  if (!file_exists ($mgmt_config['abs_path_tplmedia'].$templatesite."/Null_media.gif"))
                  {
                    copy ($mgmt_config['abs_path_cms']."theme/standard/img/Null_media.gif", $mgmt_config['abs_path_tplmedia'].$templatesite."/Null_media.gif");
                  }
         
                  $file_media = "Null_media.gif";
                  
                  if ($buildview == "publish") $url_media = $publ_config['url_publ_tplmedia'].$templatesite."/";
                  else $url_media = $mgmt_config['url_path_tplmedia'].$templatesite."/";         
                }
                // define media file to present
                else
                {
                  // if pathytpe == file (absolute path in filesystem)
                  if (!empty ($mediapathtype[$id][$tagid]) && $mediapathtype[$id][$tagid] == "file") $prefix = "abs";
                  else $prefix = "url";
                    
                  // media path settings (overwrite media pathes with the ones of the publication target)
                  if ($buildview == "publish")
                  {                                    
                    // use generated image and temp view directory
                    if (!empty ($mediacolorspace[$id][$tagid]) || !empty ($mediaiccprofile[$id][$tagid]) && is_file ($mgmt_config['abs_path_view'].$mediafilebot[$id][$tagid]))
                    {
                      $url_media = $mgmt_config[$prefix.'_path_view'];
                    }
                    else
                    {
                      $url_media = $publ_config[$prefix.'_publ_media'];
                    }
                    
                    $url_tplmedia = $publ_config[$prefix.'_publ_tplmedia'];
                  }
                  else
                  {
                    $url_media = getmedialocation ($site, $mediafilebot[$id][$tagid], $prefix."_path_media");
                    $url_tplmedia = $mgmt_config[$prefix.'_path_tplmedia'];
                  }
                
                  // if pathytpe == abs (absolute path = URL w/o protocol and domain)
                  if (!empty ($mediapathtype[$id][$tagid]) && $mediapathtype[$id][$tagid] == "abs")
                  {
                    $url_media = cleandomain ($url_media);
                    $url_tplmedia = cleandomain ($url_tplmedia);
                  }
                  
                  // if thumbnail presentation is requested
                  if (!empty ($thumbnail[$id][$tagid]) && ($thumbnail[$id][$tagid] == "1" || strtolower ($thumbnail[$id][$tagid]) == "yes"))
                  {
                    $file_info = getfileinfo ($site, $mediafilebot[$id][$tagid], "");
                    
                    if (file_exists (getmedialocation ($site, $file_info['filename'].".thumb.jpg", "abs_path_media").$site."/".$file_info['filename'].".thumb.jpg")) $file_media = $site."/".$file_info['filename'].".thumb.jpg";
                    // mp4 original thumbnail video file
                    elseif (file_exists (getmedialocation ($site, $file_info['filename'].".orig.mp4", "abs_path_media").$site."/".$file_info['filename'].".orig.mp4")) $file_media = $site."/".$file_info['filename'].".thumb.mp4";
                    // flv original thumbnail video file
                    elseif (file_exists (getmedialocation ($site, $file_info['filename'].".orig.flv", "abs_path_media").$site."/".$file_info['filename'].".orig.flv")) $file_media = $site."/".$file_info['filename'].".orig.flv";
                    // for older versions
                    elseif (file_exists (getmedialocation ($site, $file_info['filename'].".thumb.flv", "abs_path_media").$site."/".$file_info['filename'].".thumb.flv")) $file_media = $site."/".$file_info['filename'].".thumb.flv";
                    // use original file
                    else $file_media = $mediafilebot[$id][$tagid];
                  }
                  // use original file
                  else $file_media = $mediafilebot[$id][$tagid];
                }                 
              
                if ($buildview != "formedit" && $buildview != "formmeta" && $buildview != "formlock")
                {                 
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
      $language_info = array();
      $add_submitlink = "";
      $targetlist = array();
      
      foreach ($searchtag_array as $searchtag)
      {
        // get all hyperCMS tags
        $hypertag_array = gethypertag ($viewstore, $searchtag, 0);
      
        if ($hypertag_array != false) 
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
              if (empty ($linkbot[$id]))
              {            
                // read content using db_connect
                if (isset ($db_connect) && $db_connect != "") 
                {
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
                else $db_connect_data = false;
              }
                      
              // read content from content container
              if ($db_connect_data == false)
              {          
                // get the whole link information of the content container
                if (empty ($linkbot[$id]))
                {
                  $bufferarray = selectcontent ($contentdata, "<link>", "<link_id>", $id);
                  $linkbot[$id] = $bufferarray[0];
                } 
          
                // get the link file name from linkbot            
                if ($hypertagname == $searchtag."href" && !isset ($linkhrefbot[$id]))
                {                   
                  $bufferarray = getcontent ($linkbot[$id], "<linkhref>");
                  $linkhrefbot[$id] = $bufferarray[0];								
                }           
                // get the link alttext name from linkbot              
                elseif ($hypertagname == $searchtag."target" && !isset ($linktargetbot[$id]))
                {
                  $bufferarray = getcontent ($linkbot[$id], "<linktarget>");
                  $linktargetbot[$id] = $bufferarray[0];
                  // get link targets defined in template
                  $targetlist[$id] = getattribute ($hypertag, "list");
                  // escape special characters
                  $linktargetbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $linktargetbot[$id]);                      
                }
                // get the link alignment name from linkbot  
                elseif ($hypertagname == $searchtag."text" && !isset ($linktextbot[$id]))
                {
                  $bufferarray = getcontent ($linkbot[$id], "<linktext>");
                  $linktextbot[$id] = $bufferarray[0];
                  // escape special characters
                  $linktextbot[$id] = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $linktextbot[$id]);                     
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
                if (!isset ($infotype[$id]) || $infotype[$id] != "meta") $infotype[$id] = getattribute (strtolower ($hypertag), "infotype");
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
                       
                // get group access
                if (isset ($hypertag_href[$id][$tagid]))
                {
                  $groupaccess = getattribute ($hypertag_href[$id][$tagid], "groups");
                  $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);   
                }
                                        
                // ------------------------- cmsview and template ---------------------------
                
                if (
                     checklanguage ($language_sessionvalues_array, $language_info[$id]) && $groupaccess == true && 
                     isset ($hypertag_href[$id][$tagid]) && $onedit_href[$id][$tagid] != "hidden" && 
                     (
                       (
                        ($buildview == "cmsview" || $buildview == 'inlineview')
                        && $infotype[$id] != "meta"
                       ) || 
                       $buildview == "formedit" || 
                       ($buildview == "formmeta" && $infotype[$id] == "meta") || 
                       $buildview == "formlock" || $buildview == "template"
                     )
                   )
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;
    
                  while (@substr_count ($viewstore_offset, $hypertag_href[$id][$tagid]) >= 1)
                  {                 
                    if ($searchtag == "link")
                    {           
                      // create tag link
                      if ($buildview == "cmsview" || $buildview == 'inlineview')
                      {
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."frameset_edit_link.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=link&linkhref_curr=".url_encode($linkhrefbot[$id])."&linkhref=".url_encode($linkhrefbot[$id])."&linktarget=".url_encode($linktargetbot[$id])."&targetlist=".url_encode($targetlist[$id])."&linktext=".url_encode($linktextbot[$id])."&contenttype=".url_encode($contenttype)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_link.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>\n";
                      }
                      elseif ($buildview == "formedit" || ($buildview == "formmeta" && $infotype[$id] == "meta") || $buildview == "formlock")
                      {  
                        if ($linkhrefbot[$id] != "*Null*") $add_submitlink .= "submitLink ('temp_".$hypertagname_href[$id]."[".$id."]', '".$hypertagname_href[$id]."[".$id."]');\n";
                        
                        if ($buildview == "formedit" || ($buildview == "formmeta" && $infotype[$id] == "meta")) $taglink = "
                        <img onClick=\"openBrWindowLink(document.forms['hcms_formview'].elements['".$hypertagname_href[$id]."[".$id."]'],'preview','scrollbars=yes,resizable=yes,width=800,height=600', 'preview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonView\" src=\"".getthemelocation()."img/button_file_liveview.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang)."\" /> 
                        <img onClick=\"openBrWindowLink(document.forms['hcms_formview'].elements['".$hypertagname_href[$id]."[".$id."]'],'','scrollbars=yes,resizable=yes,width=800,height=600,status=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" />                          
                        <img onClick=\"deleteEntry(document.forms['hcms_formview'].elements['temp_".$hypertagname_href[$id]."[".$id."]']);\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" />
                        <img onClick=\"setSaveType('form_so', '".$mgmt_config['url_path_cms']."frameset_edit_link.php?view=".url_encode($buildview)."&savetype=form_so&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=link&linkhref_curr=".url_encode($linkhrefbot[$id])."&linkhref=' + getValue('temp_".$hypertagname_href[$id]."[".$id."]','') + '&linktarget=' + getSelectedOption('".$hypertagname_target[$id]."[".$id."]','*Null*') + '&targetlist=".url_encode($targetlist[$id])."&linktext=' + getValue('".$hypertagname_text[$id]."[".$id."]','*Null*') + '&contenttype=".url_encode($contenttype)."&token=".$token."');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_link.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" />\n";
                        else $taglink = "";
                       
                        $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b>
                          </td>
                          <td align=left valign=top>
                            <table>";
                        
                        if ($linkhrefbot[$id] != "*Null*") $formitem[$key] .= "
                              <tr>
                                <td width=\"150\">".getescapedtext ($hcms_lang['link'][$lang], $charset, $lang).":</td>
                                <td>
                                  <input type=\"hidden\" name=\"".$hypertagname_href[$id]."_curr[".$id."]\" value=\"".$linkhrefbot[$id]."\" />
                                  <input type=\"hidden\" name=\"".$hypertagname_href[$id]."[".$id."]\" value=\"".$linkhrefbot[$id]."\" />
                                  <input type=\"text\" name=\"temp_".$hypertagname_href[$id]."[".$id."]\" value=\"".convertchars (getlocationname ($site, $linkhrefbot[$id], "page"), $hcms_lang_codepage[$lang], $charset)."\" style=\"width:350px;\" ".$disabled." /> ".$taglink."
                                </td>
                              </tr>\n";
                        
                        if ($linktargetbot[$id] != "*Null*") 
                        {
                          $formitem[$key] .= "
                              <tr>
                                <td width=\"150\">
                                  ".getescapedtext ($hcms_lang['link-target'][$lang], $charset, $lang).":
                                </td>
                                <td>
                                  <select name=\"".$hypertagname_target[$id]."[".$id."]\" style=\"width:350px;\"".$disabled.">";
                          
                          $list_array = null;  
                          if (substr_count ($targetlist[$id], "|") >= 1) $list_array = explode ("|", $targetlist[$id]);
                          elseif ($targetlist[$id] != "") $list_array[] = $targetlist[$id];
                          
                          if (is_array ($list_array) && sizeof ($list_array) > 0)
                          {
                            foreach ($list_array as $target)
                            {
                              $formitem[$key] .= "
                                    <option value=\"".$target."\"";
                              if ($linktargetbot[$id] == $target) $formitem[$key] .= " selected";
                              $formitem[$key] .= ">".$target."</option>\n";
                            }
                          }
                          
                          $formitem[$key] .= "
                                    <option value=\"_self\""; if ($linktargetbot[$id] == "_self") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['in-same-frame'][$lang], $charset, $lang)."</option>
                                    <option value=\"_parent\""; if ($linktargetbot[$id] == "_parent") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['in-parent-frame'][$lang], $charset, $lang)."</option>;
                                    <option value=\"_top\""; if ($linktargetbot[$id] == "_top") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['in-same-browser-window'][$lang], $charset, $lang)."</option>
                                    <option value=\"_blank\""; if ($linktargetbot[$id] == "_blank") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['in-new-browser-window'][$lang], $charset, $lang)."</option>
                                  </select>
                                </td>
                              </tr>";
                        }   
                                          
                        if ($linktextbot[$id] != "*Null*")
                        {
                          $formitem[$key] .= "
                              <tr>
                                <td width=\"150\">
                                  ".getescapedtext ($hcms_lang['link-text'][$lang], $charset, $lang).":
                                </td>
                                <td>
                                  <input type=\"text\" name=\"".$hypertagname_text[$id]."[".$id."]\" value=\"".$linktextbot[$id]."\" style=\"width:350px;\"".$disabled." />
                                </td>
                              </tr>";
                        }
                        
                        $formitem[$key] .= "
                            </table>
                          </td>
                        </tr>\n";
                      }                        
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                          <table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">
                            <tr>
                              <td>
                                <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>element: ".$id."</b><br />
                                ".getescapedtext ($hcms_lang['here-you-can-add-a-link'][$lang], $charset, $lang)."</font>
                              </td>
                            </tr>
                          </table>\n";
                      }  
                      else $taglink = ""; 
                    }
                    elseif ($searchtag == "artlink")
                    {            
                     // create tag link
                      if ($buildview == "cmsview" || $buildview == 'inlineview')
                      {
                        $taglink = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."frameset_edit_link.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=artlink&linkhref_curr=".url_encode($linkhrefbot[$id])."&linkhref=".url_encode($linkhrefbot[$id])."&linktarget=".url_encode($linktargetbot[$id])."&targetlist=".url_encode($targetlist[$id])."&linktext=".url_encode($linktextbot[$id])."&contenttype=".url_encode($contenttype)."&token=".$token."\"><img src=\"".getthemelocation()."img/button_link.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>".$arttaglink[$artid]."\n";
                      }
                      elseif ($buildview == "formedit" || ($buildview == "formmeta" && $infotype[$id] == "meta") || $buildview == "formlock")
                      {   
                        if ($linkhrefbot[$id] != "*Null*") $add_submitlink .= "submitLink ('temp_".$hypertagname_href[$id]."[".$id."]', '".$hypertagname_href[$id]."[".$id."]');\n";
                                
                        if ($buildview == "formedit" || ($buildview == "formmeta" && $infotype == "meta")) $taglink = "
                        <img onClick=\"openBrWindowLink(document.forms['hcms_formview'].elements['".$hypertagname_href[$id]."[".$id."]'],'preview','scrollbars=yes,resizable=yes,width=800,height=600', 'preview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonView\" src=\"".getthemelocation()."img/button_file_liveview.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang)."\" /> 
                        <img onClick=\"openBrWindowLink(document.forms['hcms_formview'].elements['".$hypertagname_href[$id]."[".$id."]'],'','scrollbars=yes,resizable=yes,width=800,height=600,status=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" />                          
                        <img onClick=\"deleteEntry(document.forms['hcms_formview'].elements['temp_".$hypertagname_href[$id]."[".$id."]']);\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" />
                        <img onClick=\"setSaveType('form_so', '".$mgmt_config['url_path_cms']."frameset_edit_link.php?view=".url_encode($buildview)."&savetype=form_so&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label[$id])."&tagname=artlink&linkhref_curr=".url_encode($linkhrefbot[$id])."&linkhref=' + getValue('temp_".$hypertagname_href[$id]."[".$id."]','') + '&linktarget=' + getSelectedOption('".$hypertagname_target[$id]."[".$id."]','*Null*') + '&targetlist=".url_encode($targetlist[$id])."&linktext=' + getValue('".$hypertagname_text[$id]."[".$id."]','*Null*') + '&contenttype=".url_encode($contenttype)."&token=".$token."');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_link.gif\" border=\"0\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['set-link'][$lang], $charset, $lang)."\" />\n";
                        else $taglink = "";
                        
                        $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b> ".$arttaglink[$artid]."
                          </td>
                          <td align=left valign=top>
                            <table>";
                        
                        if ($linkhrefbot[$id] != "*Null*") $formitem[$key] .= "
                              <tr>
                                <td width=\"150\">".getescapedtext ($hcms_lang['link'][$lang], $charset, $lang).":</td>
                                <td>
                                  <input type=\"hidden\" name=\"".$hypertagname_href[$id]."_curr[".$id."]\" value=\"".$linkhrefbot[$id]."\" />
                                  <input type=\"hidden\" name=\"".$hypertagname_href[$id]."[".$id."]\" value=\"".$linkhrefbot[$id]."\" />
                                  <input type=\"text\" name=\"temp_".$hypertagname_href[$id]."[".$id."]\" value=\"".convertchars (getlocationname ($site, $linkhrefbot[$id], "page", "path"), $hcms_lang_codepage[$lang], $charset)."\" style=\"width:350px;\"".$disabled." /> ".$taglink."
                                </td>
                              </tr>\n";
                        
                        if ($linktargetbot[$id] != "*Null*") 
                        {
                          $formitem[$key] .= "
                              <tr>
                                <td width=\"150\">
                                  ".getescapedtext ($hcms_lang['link-target'][$lang], $charset, $lang).":
                                </td>
                                <td>
                                  <select name=\"".$hypertagname_target[$id]."[".$id."]\" style=\"width:350px;\"".$disabled.">";
  
                          $list_array = null;
                          if (substr_count ($targetlist[$id], "|") >= 1) $list_array = explode ("|", $targetlist[$id]);
                          elseif ($targetlist[$id] != "") $list_array[] = $targetlist[$id];
                          
                          if (is_array ($list_array) && sizeof ($list_array) > 0)
                          {
                            foreach ($list_array as $target)
                            {
                              $formitem[$key] .= "
                                    <option value=\"".$target."\""; if ($linktargetbot[$id] == $target) $formitem[$key] .= " selected"; $formitem[$key] .= ">".$target."</option>";
                            }
                          }
                          
                          $formitem[$key] .= "
                                    <option value=\"_self\""; if ($linktargetbot[$id] == "_self") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['in-same-frame'][$lang], $charset, $lang)."</option>
                                    <option value=\"_parent\""; if ($linktargetbot[$id] == "_parent") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['in-parent-frame'][$lang], $charset, $lang)."</option>;
                                    <option value=\"_top\""; if ($linktargetbot[$id] == "_top") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['in-same-browser-window'][$lang], $charset, $lang)."</option>
                                    <option value=\"_blank\""; if ($linktargetbot[$id] == "_blank") $formitem[$key] .= " selected"; $formitem[$key] .= ">".getescapedtext ($hcms_lang['in-new-browser-window'][$lang], $charset, $lang)."</option>
                                  </select>
                                </td>
                              </tr>\n";
                        }
                         
                        if ($linktextbot[$id] != "*Null*")
                        {
                          $formitem[$key] .= "
                              <tr>
                                <td width=\"150\">
                                  ".getescapedtext ($hcms_lang['link-text'][$lang], $charset, $lang).":
                                </td>
                                <td>
                                  <input type=\"text\" name=\"".$hypertagname_text[$id]."[".$id."]\" value=\"".$linktextbot[$id]."\" style=\"width:350px;\"".$disabled.">
                                </td>
                              </tr>\n";
                        }
                        
                        $formitem[$key] .= "</table></td></tr>\n";
                      }                      
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:0px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>article: ".$artid."<br />element: ".$elementid."<br />
                              </b>".getescapedtext ($hcms_lang['here-you-can-add-a-link'][$lang], $charset, $lang)."</font>
                            </td>
                          </tr>
                        </table>\n";
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
                      if (isset ($onedit_href[$id][$tagid]) && $onedit_href[$id][$tagid] != "hidden" && isset ($onpublish_href[$id][$tagid]) && $onpublish_href[$id][$tagid] != "hidden")
                        $linkhrefbot_insert = tpl_insertlink ($application, "hypercms_".$container_id, $id);
                      elseif (isset ($onedit_href[$id][$tagid]) && $onedit_href[$id][$tagid] == "hidden" && isset ($onpublish_href[$id][$tagid]) && $onpublish_href[$id][$tagid] != "hidden")
                        $linkhrefbot_insert = str_replace ("%page%/".$site."/", $publ_config['url_publ_page'], $linkhrefbot[$id]);
                      else
                        $linkhrefbot_insert = "";
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
      $searchtag_array[0] = "component";
      $searchtag_array[1] = "artcomponent";
      $id_array = array();
      $infotype = "";
      $position = "";
      $onpublish = "";
      $onedit = ""; 
      $include = "";
      $icon = "";
      $add_submitcomp = "";
      
      foreach ($searchtag_array as $searchtag)
      {
        // get all hyperCMS tags
        $hypertag_array = gethypertag ($viewstore, $searchtag, 0);
      
        if ($hypertag_array != false) 
        {
          reset ($hypertag_array);
          
          // loop for each hyperCMS tag found in template
          foreach ($hypertag_array as $key => $hypertag)
          {            
            // get tag name
            $hypertagname = gethypertagname ($hypertag);  
            
            // get tag id
            $id = getattribute ($hypertag, "id"); 
            
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
            
            // get tag visibility on edit
            $icon = getattribute (strtolower ($hypertag), "icon");    
            
            // get inclusion type (dynamic or static)
            // if no attribute is set, default value will be "dynamic"
            $include = getattribute (strtolower ($hypertag), "include");
            
            // get value of tag
            $defaultvalue = getattribute ($hypertag, "default");
            
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
              <meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$hcms_lang_codepage[$lang]."\">
              <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">
              <script src=\"javascript/click.js\" type=\"".$mgmt_config['url_path_cms']."text/javascript\">
              </script>
              </head>
              <body class=\"hcmsWorkplaceGeneric\">
                <p class=hcmsHeadline>".$hcms_lang['the-tags'][$lang]." [$tags] ".$hcms_lang['and-or'][$lang]." [$tagm] ".$hcms_lang['have-the-same-identification-id'][$lang]."</p>
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
              exit;
            }
            else
            {
              if ($buildview != "template")
              {
                $contentbot = "";
                
                // read content using db_connect
                if (isset ($db_connect) && $db_connect != "") 
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
                else $db_connect_data = false;  
                  
                // read content from content container
                if ($db_connect_data == false)
                {    
                  // get content between tags
                  $contentarray = selectcontent ($contentdata, "<component>", "<component_id>", $id);
                  $condarray = getcontent ($contentarray[0], "<componentcond>");
                  $condbot = trim ($condarray[0]);
                  $contentarray = getcontent ($contentarray[0], "<componentfiles>");
                  $contentbot = $contentarray[0];
                }
                
                // set default value eventually given by tag
                if ($contentbot == "" && $defaultvalue != "") $contentbot = $defaultvalue;

                // convert object ID to object path
                $contentbot = getobjectlink ($contentbot);
              }    

              // -------------------------------------- cmsview --------------------------------------------
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
                  $dir_item = @dir ($mgmt_config['abs_path_data']."customer/".$site."/");
  
                  if ($dir_item != false)
                  {
                    $profile_array = array();
                    
                    while ($entry = $dir_item->read())
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
                    $dir_item->close();
                  }
                }
                                        
                // ------------ single component --------------        
                // replace hyperCMS tag with content
                if ($hypertagname == $searchtag."s")
                {
                  $repl_offset = 0;
                  $viewstore_offset = $viewstore;

                  while (@substr_count ($viewstore_offset, $hypertag) >= 1)  // necessary loop for unique media names for rollover effect
                  {
                    if ($searchtag == "component")
                    {                   
                      // create tag link for editor
                      if (!empty ($contentbot)) $compeditlink = "<img onClick=\"hcms_openWindowComp('', 'scrollbars=yes,resizable=yes,width=800,height=600,status=yes', '".str_replace ("%comp%", "", $contentbot)."');\" src=\"".getthemelocation()."img/button_file_edit.gif\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\"  style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /><a hypercms_href=\"".$mgmt_config['url_path_cms']."service/savecontent.php?site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=single&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&component_curr[".$id."]=".url_encode($contentbot)."&component[".$id."]=&condition[".$id."]=".url_encode($condbot)."&token=".$token."\" /><img src=\"".getthemelocation()."img/button_delete.gif\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>";
                      else $compeditlink = "";
                      
                      if ($buildview == "cmsview" || $buildview == "inlineview")
                      {
                        $taglink = "<div><a hypercms_href=\"".$mgmt_config['url_path_cms']."frameset_edit_component.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=single&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&component_curr=".url_encode($contentbot)."&component=".url_encode($contentbot)."&condition=".url_encode($condbot)."\"><img src=\"".getthemelocation()."img/button_compsingle.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>".$compeditlink."</div>\n";
                      }
                      elseif ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                      {
                        $comp_entry_name = getlocationname ($site, $contentbot, "comp", "path");                    
                        if (strlen ($comp_entry_name) > 50) $comp_entry_name = "...".substr (substr ($comp_entry_name, -50), strpos (substr ($comp_entry_name, -50), "/"));                          
                        
                        $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b>
                          </td>
                          <td align=left valign=top>
                            <table cellpadding=0 cellspacing=0 border=0>
                              <tr>";
                              
                        // only if not DAM  
                        if (!$mgmt_config[$site]['dam']) $formitem[$key] .= "
                                <td width=\"150\">
                                  ".getescapedtext ($hcms_lang['single-component'][$lang], $charset, $lang).":
                                </td>";
                                
                        $formitem[$key] .= "
                                <td>
                                  <input type=\"hidden\" name=\"component_curr[".$id."]\" value=\"".$contentbot."\" />
                                  <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" />
                                  <input type=\"text\" name=\"temp_".$hypertagname."_".$id."\" value=\"".convertchars ($comp_entry_name, $hcms_lang_codepage[$lang], $charset)."\" style=\"width:350px;\" disabled=\"disabled\" />\n";
                              
                        if ($buildview == "formedit" || ($buildview == "formmeta" && $infotype == "meta")) $formitem[$key] .= "
                                  <img onClick=\"openBrWindowComp(document.forms['hcms_formview'].elements['".$hypertagname."[".$id."]'],'','scrollbars=yes,resizable=yes,width=800,height=600,status=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\">                          
                                  <img onClick=\"deleteEntry(document.forms['hcms_formview'].elements['".$hypertagname."[".$id."]']); deleteEntry(document.forms['hcms_formview'].elements['temp_".$hypertagname."_".$id."']);\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" />
                                  <img onClick=\"self.location.href='".$mgmt_config['url_path_cms']."frameset_edit_component.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=single&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&component_curr=".url_encode($contentbot)."&component=".url_encode($contentbot)."&condition=".url_encode($condbot)."';\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_compsingle.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" />\n";
                          
                        $formitem[$key] .= "
                                </td>
                              </tr>";
                        
                        // personalization/customers profiles only if not DAM  
                        if (!$mgmt_config[$site]['dam'])
                        {
                          $formitem[$key] .= "
                              <tr>
                                <td width=\"150\">".getescapedtext ($hcms_lang['customer-profile'][$lang], $charset, $lang).":</td>
                                <td style=\"padding-top:3px;\">
                                  <select name=\"condition[".$id."]\" style=\"width:350px;\"".$disabled.">
                                    <option value=\"\">--------- ".getescapedtext ($hcms_lang['select'][$lang], $charset, $lang)." ---------</option>";
                    
                          if (sizeof ($profile_array) >= 1)
                          {
                            reset ($profile_array);
              
                            foreach ($profile_array as $profile)
                            {
                              $formitem[$key] .= "
                                    <option value=\"".$profile."\"";
                              if ($profile == $condbot) $formitem[$key] .= " selected";
                              $formitem[$key] .= ">".$profile."</option>";
                            }
                          }
                  
                          $formitem[$key] .= "
                                  </select>
                                </td>
                              </tr>";
                        }
                        
                        $formitem[$key] .= "  
                            </table>
                          </td>
                        </tr>";   
                      }                      
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['here-you-can-insert-a-single-component'][$lang], $charset, $lang)."</font>
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
                        $taglink = "<div><a hypercms_href=\"".$mgmt_config['url_path_cms']."frameset_edit_component.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=single&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".$db_connect."&id=".$id."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&component_curr[".$id."]=".url_encode($contentbot)."&component[".$id."]=".url_encode($contentbot)."&condition[".$id."]=".url_encode($condbot)."\"><img src=\"".getthemelocation()."img/button_compsingle.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></a>".$arttaglink[$artid]."</div>\n";
                      }
                      elseif ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                      {                     
                        $comp_entry_name = getlocationname ($site, $contentbot, "comp", "path");                    
                        if (strlen ($comp_entry_name) > 50) $comp_entry_name = "...".substr (substr ($comp_entry_name, -50), strpos (substr ($comp_entry_name, -50), "/"));                        
                        
                        $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b> ".$arttaglink[$artid]."
                          </td>
                          <td align=left valign=top>
                            <table cellpadding=0 cellspacing=0 border=0>
                              <tr>
                                <td width=\"150\">
                                  ".getescapedtext ($hcms_lang['single-component'][$lang], $charset, $lang).":
                                </td>
                                <td>
                                  <input type=\"hidden\" name=\"artcomponent_curr[".$id."]\" value=\"".$contentbot."\" />
                                  <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" />
                                  <input type=\"text\" name=\"temp_".$hypertagname."_".$artid."_".$elementid."\" value=\"".convertchars ($comp_entry_name, $hcms_lang_codepage[$lang], $charset)."\" style=\"width:350px;\" disabled />\n";
                             
                        if ($buildview == "formedit" || ($buildview == "formmeta" && $infotype == "meta")) $formitem[$key] .= "
                                  <img onClick=\"openBrWindowComp(document.forms['hcms_formview'].elements['".$hypertagname."[".$id."]'],'','scrollbars=yes,resizable=yes,width=800,height=600,status=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" />                          
                                  <img onClick=\"deleteEntry(document.forms['hcms_formview'].elements['".$hypertagname."[".$id."]']); deleteEntry(document.forms['hcms_formview'].elements['temp_".$hypertagname."_".$artid."_".$elementid."']);\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" />
                                  <img onClick=\"self.location.href='".$mgmt_config['url_path_cms']."frameset_edit_component.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=single&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&component_curr=".url_encode($contentbot)."&component=".url_encode($contentbot)."&condition=".url_encode($condbot)."';\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_compsingle.gif\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['insert-single-component'][$lang], $charset, $lang)."\" />\n";
                              
                        $formitem[$key] .= "
                                </td>
                              </tr>
                              <tr>
                                <td width=\"150\">".getescapedtext ($hcms_lang['customer-profile'][$lang], $charset, $lang).":</td>
                                <td style=\"padding-top:3px;\">
                                  <select name=\"condition[".$id."]\" style=\"width:350px;\"".$disabled.">
                                    <option value=\"\">--------- ".getescapedtext ($hcms_lang['select'][$lang], $charset, $lang)." ---------</option>";
                    
                        if (sizeof ($profile_array) >= 1)
                        {
                          reset ($profile_array);
            
                          foreach ($profile_array as $profile)
                          {
                            $formitem[$key] .= "
                                    <option value=\"".$profile."\"";
                            if ($profile == $condbot) $formitem[$key] .= " selected";
                            $formitem[$key] .= ">".$profile."</option>\n";
                          }
                        }
                
                        $formitem[$key] .= "
                                  </select>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>\n";                     
                      }                        
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>article: ".$artid."<br />element: ".$elementid."</b><br />
                              ".getescapedtext ($hcms_lang['here-you-can-insert-a-single-component'][$lang], $charset, $lang)."</font>
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

                  while (@substr_count ($viewstore_offset, $hypertag) >= 1)
                  {                  
                    if ($searchtag == "component")
                    {                
                      // create tag link for editor
                      if (($buildview == "cmsview" || $buildview == 'inlineview') && $onedit != "hidden")
                      {
                        $taglink = "<img onClick=\"hcms_changeitem('".$hypertagname."','".$id."','".$condbot."','".$i."','send');\" src=\"".getthemelocation()."img/button_compmulti.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; cursor:pointer; z-index:9999999;\" /><br />\n";
                      }
                      elseif ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                      {
                        $add_submitcomp .= "submitMultiComp ('".$hypertagname."_".$id."', '".$hypertagname."[".$id."]');\n";

                        $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b>
                          </td>
                          <td align=left valign=top>
                            <table cellpadding=0 cellspacing=0 border=0>
                              <tr>";
                              
                        // only if not DAM  
                        if (!$mgmt_config[$site]['dam']) $formitem[$key] .= "
                                <td align=left valign=top width=\"150\">
                                  ".getescapedtext ($hcms_lang['multiple-component'][$lang], $charset, $lang).":
                                </td>";
                                
                        $formitem[$key] .= "
                                <td>
                                  <input type=\"hidden\" name=\"component_curr[".$id."]\" value=\"".$contentbot."\" />
                                  <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" />
                                  <select name=\"".$hypertagname."_".$id."\" size=\"10\" style=\"width:350px;\" ".$disabled.">";                     
                                
                        if (!empty ($contentbot) && $contentbot != false)
                        {
                          // cut off last delimiter
                          $component = trim ($contentbot, "|");
                          
                          // split component string into array
                          $component_array = null;
                          
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
                        
                        if ($buildview == "formedit" || ($buildview == "formmeta" && $infotype == "meta")) $formitem[$key] .= "
                                <td style=\"padding:2px;\">
                                  <img onClick=\"moveSelected(document.forms['hcms_formview'].elements['".$hypertagname."_".$id."'], false)\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('ButtonUp','','".getthemelocation()."img/button_moveup_over.gif',1)\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" name=\"ButtonUp\" src=\"".getthemelocation()."img/button_moveup.gif\" alt=\"".getescapedtext ($hcms_lang['move-component-up'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['move-component-up'][$lang], $charset, $lang)."\" /><br />  
                                  <img onClick=\"openBrWindowComp(document.forms['hcms_formview'].elements['".$hypertagname."_".$id."'],'','scrollbars=yes,resizable=yes,width=800,height=600,status=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.gif\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" /><br />                          
                                  <img onClick=\"deleteSelected(document.forms['hcms_formview'].elements['".$hypertagname."_".$id."'])\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.gif\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" /><br />            
                                  <img onClick=\"self.location.href='".$mgmt_config['url_path_cms']."frameset_edit_component.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=multi&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&condition=".url_encode($condbot)."';\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_compmulti.gif\" alt=\"".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" /><br />
                                  <img onClick=\"moveSelected(document.forms['hcms_formview'].elements['".$hypertagname."_".$id."'], true)\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('ButtonDown','','".getthemelocation()."img/button_movedown_over.gif',1)\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" name=\"ButtonDown\" src=\"".getthemelocation()."img/button_movedown.gif\" alt=\"".getescapedtext ($hcms_lang['move-component-down'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['move-component-down'][$lang], $charset, $lang)."\" />
                                </td>";
                              
                        $formitem[$key] .= "
                              </tr>";
                        
                        // personalization/customers profiles only if not DAM  
                        if (!$mgmt_config[$site]['dam'])
                        {
                          $formitem[$key] .= "
                          <tr>
                            <td width=\"150\">
                              ".getescapedtext ($hcms_lang['customer-profile'][$lang], $charset, $lang).":
                            </td>
                            <td style=\"padding-top:3px;\">
                              <select name=\"condition[".$id."]\" style=\"width:350px;\" ".$disabled.">
                                <option value=\"\">--------- ".getescapedtext ($hcms_lang['select'][$lang], $charset, $lang)." ---------</option>\n";
              
                          if (sizeof ($profile_array) >= 1)
                          {
                            reset ($profile_array);
              
                            foreach ($profile_array as $profile)
                            {
                              $formitem[$key] .= "
                                <option value=\"".$profile."\""; if ($profile == $condbot) $formitem[$key] .= " selected"; $formitem[$key] .= ">".$profile."</option>";
                            }
                          }
                  
                          $formitem[$key] .= "
                              </select>
                            </td>
                          </tr>\n";
                        }
                        
                        $formitem[$key] .= "
                            </table>
                          </td>
                        </tr>\n";                    
                      }                        
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:0px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>element: ".$id."</b><br />
                              ".getescapedtext ($hcms_lang['here-you-can-insert-multiple-components'][$lang], $charset, $lang)."</font>
                            </td>
                          </tr>
                        </table>\n";
                      }
                      else $taglink = "";
                    }
                    elseif ($searchtag == "artcomponent")
                    {
                      // create tag link for editor
                      if ($buildview == "cmsview" || $buildview == "inlineview")
                      {
                        $taglink = "<div><img onClick=\"hcms_changeitem('".$hypertagname."','".$id."','".$condbot."','".$i."','send');\" src=\"".getthemelocation()."img/button_compmulti.gif\" alt=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" title=\"".$labelname.": ".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; cursor:pointer; z-index:9999999;\" />".$arttaglink[$artid]."</div>\n";
                      }
                      elseif ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
                      {
                        $add_submitcomp .= "submitMultiComp ('".$hypertagname."_".$artid."_".$elementid."', '".$hypertagname."[".$id."]');\n";
                      
                        $formitem[$key] = "
                        <tr>
                          <td align=left valign=top>
                            <b>".$labelname."</b> ".$arttaglink[$artid]."
                          </td>
                          <td align=left valign=top>
                            <table cellpadding=0 cellspacing=0 border=0>
                              <tr>
                                <td align=left valign=top width=\"150\">
                                  ".getescapedtext ($hcms_lang['multiple-component'][$lang], $charset, $lang).":
                                </td>
                                <td>
                                  <input type=\"hidden\" name=\"artcomponent_curr[".$id."]\" value=\"".$contentbot."\" />
                                  <input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" />
                                  <select name=\"".$hypertagname."_".$artid."_".$elementid."\" size=\"10\" style=\"width:350px;\" ".$disabled.">\n";                     
                                
                        if (!empty ($contentbot) && $contentbot != false)
                        {
                          // cut off last delimiter
                          $component = trim ($contentbot, "|");

                          // split component string into array
                          $component_array = null;
                          
                          if (substr_count ($component, "|") > 0) $component_array = explode ("|", $component);
                          else $component_array[0] = $component;
        
                          foreach ($component_array as $comp_entry)
                          { 
                            $comp_entry_name = getlocationname ($site, $comp_entry, "comp", "path");                    
                            if (strlen ($comp_entry_name) > 50) $comp_entry_name = "...".substr (substr ($comp_entry_name, -50), strpos (substr ($comp_entry_name, -50), "/"));  
                                                                               
                            $formitem[$key] .= "
                                    <option value=\"".$comp_entry."\">".convertchars ($comp_entry_name, $hcms_lang_codepage[$lang], $charset)."</option>\n";
                          }
                        }
                        
                        $formitem[$key] .= "
                                  </select>
                                </td>";
                        
                        if ($buildview == "formedit" || ($buildview == "formmeta" && $infotype == "meta")) $formitem[$key] .= "
                                <td style=\"padding:2px;\">
                                  <img onClick=\"moveSelected(document.forms['hcms_formview'].elements['".$hypertagname."_".$artid."_".$elementid."'], false)\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonUp\" src=\"".getthemelocation()."img/button_moveup.gif\" alt=\"".getescapedtext ($hcms_lang['move-component-up'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['move-component-up'][$lang], $charset, $lang)."\" /><br />
                                  <img onClick=\"openBrWindowComp(document.forms['hcms_formview'].elements['".$hypertagname."_".$artid."_".$elementid."'],'','scrollbars=yes,resizable=yes,width=800,height=600,status=yes', 'cmsview');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonEdit\" src=\"".getthemelocation()."img/button_edit.gif\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" /><br />                          
                                  <img onClick=\"deleteSelected(document.forms['hcms_formview'].elements['".$hypertagname."_".$artid."_".$elementid."'])\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDelete\" src=\"".getthemelocation()."img/button_delete.gif\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" /><br />            
                                  <img onClick=\"self.location.href='".$mgmt_config['url_path_cms']."frameset_edit_component.php?view=".url_encode($buildview)."&site=".url_encode($site)."&cat=".url_encode($cat)."&compcat=multi&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&id=".url_encode($id)."&label=".url_encode($label)."&tagname=".url_encode($hypertagname)."&condition=".url_encode($condbot)."';\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_compmulti.gif\" alt=\"".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['insert-multiple-component'][$lang], $charset, $lang)."\" /><br />
                                  <img onClick=\"moveSelected(document.forms['hcms_formview'].elements['".$hypertagname."_".$artid."_".$elementid."'], true)\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" name=\"ButtonDown\" src=\"".getthemelocation()."img/button_movedown.gif\" alt=\"".getescapedtext ($hcms_lang['move-component-down'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['move-component-down'][$lang], $charset, $lang)."\" />
                                </td>";

                        $formitem[$key] .= "
                              </tr>
                              <tr>
                                <td width=\"150\">".getescapedtext ($hcms_lang['customer-profile'][$lang], $charset, $lang).":</td>
                                <td style=\"padding-top:3px;\">
                                  <select name=\"condition[".$id."]\" style=\"width:350px;\"".$disabled.">
                                    <option value=\"\">--------- ".getescapedtext ($hcms_lang['select'][$lang], $charset, $lang)." ---------</option>\n";
              
                        if (sizeof ($profile_array) >= 1)
                        {
                          reset ($profile_array);
            
                          foreach ($profile_array as $profile)
                          {
                            $formitem[$key] .= "
                                    <option value=\"".$profile."\"";
                            if ($profile == $condbot) $formitem[$key] .= " selected";
                            $formitem[$key] .= ">".$profile."</option>";
                          }
                        }
                
                        $formitem[$key] .= "
                                </select>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>\n";  
                      }                        
                      elseif ($buildview == "template")
                      {
                        $taglink = "
                        <table style=\"width:200px; padding:0px; border:1px solid #000000; background-color:#FFFFFF;\">
                          <tr>
                            <td>
                              <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>article: ".$artid."<br />element: ".$elementid."</b><br />
                              ".getescapedtext ($hcms_lang['here-you-can-insert-multiple-components'][$lang], $charset, $lang)."</font>
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
                      $error[] = $mgmt_config['today']."|hypercms_tplengine.inc.php|error|$errcode|loadfile failed for ".$mgmt_config['abs_path_data']."customer/".$site."/".$condbot.".prof.dat";
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
                      $contentbot_array = null;
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
                  // use publish settings
                  if ($buildview == "publish")
                  {
                    // replace the url_comp variables with the URL of the component root
                    if ($mgmt_config['os_cms'] == "WIN") $contentbot = str_replace ("%comp%", substr ($publ_config['abs_publ_comp'], 0, strlen ($publ_config['abs_publ_comp'])-1), $contentbot);
                    else $contentbot = str_replace ("%comp%", substr ($publ_config['url_publ_comp'], 0, strlen ($publ_config['url_publ_comp'])-1), $contentbot);
                  }
                  // use management settings
                  else
                  {
                    // replace the url_comp variables with the URL of the component root
                    if ($mgmt_config['os_cms'] == "WIN") $contentbot = str_replace ("%comp%", substr ($mgmt_config['abs_path_comp'], 0, strlen ($mgmt_config['abs_path_comp'])-1), $contentbot);
                    else $contentbot = str_replace ("%comp%", substr ($mgmt_config['url_path_comp'], 0, strlen ($mgmt_config['url_path_comp'])-1), $contentbot);
                  }
                            
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
                        $component = str_replace ("<", "<font color=\"#0000FF\" size=\"2\" face=\"Verdana, Arial, Helvetica, sans-serif\">&lt;", $component);
                        $component = str_replace (">", "&gt;</font>", $component);   
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
                            $taglink = "<div><img onClick=\"hcms_openWindowComp('', 'scrollbars=yes,resizable=yes,width=800,height=600,status=yes', '".str_replace ("%comp%", "", $component_link)."');\"  src=\"".getthemelocation()."img/button_edit.gif\" alt=\"".$hcms_lang['edit'][$lang]."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /><img onClick=\"hcms_changeitem('".$hypertagname."','".$id."','".$condbot."','".$i."','delete');\" src=\"".getthemelocation()."img/button_delete.gif\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /><img onClick=\"hcms_changeitem('".$hypertagname."','".$id."','".$condbot."','".$i."','moveup');\"  src=\"".getthemelocation()."img/button_moveup.gif\" alt=\"".getescapedtext ($hcms_lang['move-component-up'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['move-component-up'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /><img onClick=\"hcms_changeitem('".$hypertagname."','".$id."','".$condbot."','".$i."','movedown');\" src=\"".getthemelocation()."img/button_movedown.gif\" alt=\"".getescapedtext ($hcms_lang['move-component-down'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['move-component-down'][$lang], $charset, $lang)."\" style=\"display:inline-block; width:22px; height:22px; border:0; cursor:pointer; z-index:9999999;\" /></div>\n";
  
                            $scriptarray .= "item['".$id."'][".$i."] = '".$component_link."';\n";
                            $i++;
                          }
                          else $taglink = "";                 
                        
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
                              $component = str_replace ("<", "<font color=\"#0000FF\" size=\"2\" face=\"Verdana, Arial, Helvetica, sans-serif\">&lt;", $component);
                              $component = str_replace (">", "&gt;</font>", $component);   
                              $component = str_replace ("\n", "<br />", $component);  
                            }    
                            
                            // add buttons
                            $multicomponent .= $taglink.$component;     
                          }                                     
                        }
                      }
                    }
                    else $multicomponent = "";
          
                    $viewstore = str_replace ($hypertag, $multicomponent, $viewstore);             
                  }
                }
                elseif ($onedit == "hidden" && $onpublish != "hidden")
                {      
                  if ($buildview == "publish")
                  {      
                    $viewstore = str_replace ($hypertag, str_replace ("%comp%", substr ($publ_config['url_publ_comp'], 0, strlen ($publ_config['url_publ_comp'])-1), $contentbot), $viewstore);
                  }
                  else
                  {
                    $viewstore = str_replace ($hypertag, str_replace ("%comp%", substr ($mgmt_config['url_path_comp'], 0, strlen ($mgmt_config['url_path_comp'])-1), $contentbot), $viewstore);
                  }              
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
      
      // WYSIWYG Views
      if ($buildview != "formedit" && $buildview != "formmeta" && $buildview != "formlock")
      {  
        // ================================ javascript for control frame call ================================
        if ($ctrlreload == "yes")
        {
          $bodytag_controlreload = "if (eval (parent.frames['controlFrame'])) parent.frames['controlFrame'].location.hypercms_href='".$mgmt_config['url_path_cms']."control_content_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';";
        }
        else $bodytag_controlreload = "";      
                   
        // ==================================== scripts in template view =====================================
        if ($buildview == "template" && $application != "")
        {
          // replace hyperCMS script code                  
          while (@substr_count (strtolower($viewstore), "[hypercms:scriptbegin") > 0)
          {
            $apptagstart = strpos (strtolower($viewstore), "[hypercms:scriptbegin");
            $apptagend = strpos (strtolower($viewstore), "scriptend]", $apptagstart + strlen ("[hypercms:scriptbegin")) + strlen ("scriptend]");
            $apptag = substr ($viewstore, $apptagstart, $apptagend - $apptagstart);
            $viewstore = str_replace ($apptag, "", $viewstore);
            
            /* old version
            $htmltag = gethtmltag ($viewstore, $apptag);
            
            if ($htmltag == false) $viewstore = str_replace ($apptag, "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>".$hcms_lang['onhcms_lang'][$lang]."</b></font>\n</td>\n  </tr>\n</table>\n", $viewstore);
            else $viewstore = str_replace ($htmltag, "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>".$hcms_lang['server-side-script-code-included'][$lang]."</b></font>\n</td>\n  </tr>\n</table>\n".str_replace ($apptag, "", $htmltag), $viewstore);
            */
          }
          
          // replace application script code
          while (@substr_count ($viewstore, tpl_tagbegin($application)) > 0)
          {
            $apptagstart = strpos ($viewstore, tpl_tagbegin($application));
            $apptagend = strpos ($viewstore, tpl_tagend($application), $apptagstart + strlen (tpl_tagbegin($application))) + strlen (tpl_tagend($application));            
            $apptag = substr ($viewstore, $apptagstart, $apptagend - $apptagstart);            
            $viewstore = str_replace ($apptag, "", $viewstore);

            /* old version
            $htmltag = gethtmltag ($viewstore, $apptag);
            
            if ($htmltag == false) $viewstore = str_replace ($apptag, "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>".$hcms_lang['onhcms_lang'][$lang]."</b></font>\n</td>\n  </tr>\n</table>\n", $viewstore);
            else $viewstore = str_replace ($htmltag, "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>".$hcms_lang['server-side-script-code-included'][$lang]."</b></font>\n</td>\n  </tr>\n</table>\n".str_replace ($apptag, "", $htmltag), $viewstore);
            */
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
        if ($buildview == "publish") 
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
        
        // replace the container variables in the template with container name
        if (isset ($contentfile)) $viewstore = str_replace ("%container%", $contentfile, $viewstore); 
        
        // replace the container variables in the template with container name
        if (isset ($container_id)) $viewstore = str_replace ("%container_id%", $container_id, $viewstore);
        
        // replace the container variables in the template with container name
        if ($mgmt_config['db_connect_rdbms'] != "" && isset ($location_esc) && isset ($page)) $objecthash = rdbms_getobject_hash ($location_esc.$page);
        else $objecthash = "";
        
        $viewstore = str_replace ("%objecthash%", $objecthash, $viewstore); 
        
        // replace the template variables in the template with the used template
        if (isset ($templatefile)) $viewstore = str_replace ("%template%", $templatefile, $viewstore);
        
        // replace the page/comp variables in the template
        if ($buildview == "publish") 
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
                
        // remove line breaks
        $viewstore = trim ($viewstore);                     
             
        // ======================================== execute php script code =========================================
        
        $tpl_livelink = "";
        $tpl_linkindex = "";

        if ($execute_code == true)
        {
          // escape xml-declaration in templates using XML-code
          $xmldeclaration = gethtmltag ($viewstore, "?xml");
          
          if ($xmldeclaration != false) 
          {
            $xmldeclaration_esc = str_replace ("<?", "[hypercms:xmlbegin", $xmldeclaration);
            $xmldeclaration_esc = str_replace ("?>", "xmlend]", $xmldeclaration_esc);
            $viewstore = str_replace ($xmldeclaration, $xmldeclaration_esc, $viewstore);
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
                // add language setting from session
                if (!empty ($_SESSION[$language_sessionvar])) $pageview_parameter = "?hcms_session[".$language_sessionvar."]=".$_SESSION[$language_sessionvar];
                
                $viewstore = @file_get_contents ($mgmt_config['url_path_view'].$unique_id.".pageview.php".$pageview_parameter);
                
                // error handling
                $viewstore = errorhandler ($viewstore_buffer, $viewstore, $unique_id.".pageview.php");            
             
                deletefile ($mgmt_config['abs_path_view'], $unique_id.".pageview.php", 0);
              }
            }
            
            // execute application code (non PHP)
            if ($application != "" && $application != "php" && isset ($mgmt_config['application'][$application]) && $mgmt_config['application'][$application] == true && @substr_count ($viewstore, tpl_tagbegin ($application)) > 0)
            {       
              // change directory to location to have correct hrefs
              $viewstore =  tpl_globals_extended ($application, $mgmt_config['abs_path_cms'], $mgmt_config['abs_path_rep'], $site, $location).$viewstore;
              
              // save pageview in temp
              $test = savefile ($mgmt_config['abs_path_view'], $unique_id.".pageview.".$templateext, $viewstore);   
              
              $viewstore_buffer = $viewstore;
              
              // execute code
              if ($test == true)
              {
                // add language setting from session
                if ($language_sessionvar != "" && $_SESSION[$language_sessionvar] != "") $pageview_parameter = "?hcms_session[".$language_sessionvar."]=".$_SESSION[$language_sessionvar];
                            
                $viewstore = @file_get_contents ($mgmt_config['url_path_view'].$unique_id.".pageview.".$templateext.$pageview_parameter);
                
                // error handling
                $viewstore = errorhandler ($viewstore_buffer, $viewstore, $unique_id.".pageview.".$templateext);                
                
                deletefile ($mgmt_config['abs_path_view'], $unique_id.".pageview.".$templateext, 0);
              }
            }        
          }
          // Publish
          elseif ($mgmt_config['application']['php'] == true && $buildview == "publish" && (preg_match ("/\[hypercms:scriptbegin/i", $viewstore) || strtoupper ($mgmt_config['os_cms']) == "WIN"))
          {
            // execute hyperCMS scripts for preprocessing
            // transform 
            $viewstore = str_replace (tpl_tagbegin ($application), "[hyperCMS:skip", $viewstore);
            $viewstore = str_replace (tpl_tagend ($application), "skip]", $viewstore);
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
                // add language setting from session
                if ($language_sessionvar != "" && $_SESSION[$language_sessionvar] != "") $pageview_parameter = "?hcms_session[".$language_sessionvar."]=".$_SESSION[$language_sessionvar];
                
                // execute code of generator (e.g. create a PDF file)
                $viewstore_save = @file_get_contents ($mgmt_config['url_path_view'].$unique_id.".generate.php".$pageview_parameter);
                
                // error handling
                $viewstore = errorhandler ($viewstore_buffer, $viewstore_save, $unique_id.".generate.php");            

                deletefile ($mgmt_config['abs_path_view'], $unique_id.".generate.php", 0);
                
                // generation of file was successful, save it to the media repository
                if ($viewstore == $viewstore_save)
                {
                  $mediadir = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
                  
                  // save media file
                  $result_save = savefile ($mediadir, $mediafile, $viewstore);
                  
                  // create thumbnail
                  createmedia ($site, $mediadir, $mediadir, $mediafile, "", "thumbnail");
                  
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
                  $error[] = $mgmt_config['today']."|hypercms_tplengine.inc.php|error|$errcode|generator failed to render file ".$mediafile." with error: ".$errview;
                }
              }
              
              // set viewstore    
              $viewstore = $errorview;
            }
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
                // add language setting from session
                if ($language_sessionvar != "" && $_SESSION[$language_sessionvar] != "") $pageview_parameter = "?hcms_session[".$language_sessionvar."]=".$_SESSION[$language_sessionvar];
                       
                $viewstore = @file_get_contents ($mgmt_config['url_path_view'].$unique_id.".pageview.php".$pageview_parameter);
            
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
          if ($xmldeclaration != false) 
          {
            $viewstore = str_replace ("[hypercms:xmlbegin", "<?", $viewstore);
            $viewstore = str_replace ("xmlend]", "?>", $viewstore);
          }
        }
        
        // ======================================== define CSS for components =========================================
        $line_css = "";
        
        $hypertag_array = gethypertag ($viewstore, "compstylesheet", 0);

        if ($hypertag_array != false && sizeof ($hypertag_array) > 0) 
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
              if ($css != "") $line_css .= "<link rel=\"stylesheet\" hypercms_href=\"".$css."\">\n";
            }
          }
        }   
        
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
            <meta http-equiv=\"Content-Type\" content=\"".$contenttype."\" />
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
          if ($objectview != "cmsview" && $objectview != "inlineview") $headstoreform = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."page_view.php?view=formedit&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."\"><img src=\"".getthemelocation()."img/edit_form.gif\" style=\"display:inline-block; width:45px; height:18px; padding:0; margin:0; border:0; vertical-align:top; text-align:left;\" alt=\"".getescapedtext ($hcms_lang['form-view'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['form-view'][$lang], $charset, $lang)."\" /></a>";       
          else $headstoreform = "";
          
          // check if html tag exists in viewstore (if not it will be treated as a component)
          if (($buildview == "cmsview" || $buildview == "inlineview") && !isset ($compcontenttype) && !isset ($contenttype)) 
          {
            $headstoremeta = "<a hypercms_href=\"".$mgmt_config['url_path_cms']."page_view.php?view=formmeta&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&db_connect=".url_encode($db_connect)."&contenttype=".url_encode($contenttype)."\"><img src=\"".getthemelocation()."img/edit_head.gif\" style=\"display:inline-block; width:45px; height:18px; padding:0; margin:0; border:0; vertical-align:top; text-align:left;\" alt=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" /></a>\n";
          }         

          // scriptcode
          $scriptcode = "<script src=\"".$mgmt_config['url_path_cms']."javascript/main.js\" type=\"text/javascript\"></script>
  <script language=\"JavaScript\">
  <!--
  ".$bodytag_controlreload."
  ".$bodytag_popup."
  -->
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
            if ($buildview != "preview" && ($headstoremeta != "" || $headstoreform != "" || $headstoreview != "" || $headstorelang != "")) $headstore = "<div id=\"meta_info\" style=\"position:absolute; padding:0; margin:0; z-index:99999; left:4px; top:4px; border:0; background:none; visibility:visible;\"><img src=\"".getthemelocation()."img/edit_drag.gif\" style=\"display:inline-block; width:18px; height:18px; padding:0; margin:0; border:0; vertical-align:top; text-align:left;\" alt=\"".getescapedtext ($hcms_lang['drag'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['drag'][$lang], $charset, $lang)."\" id=\"meta_mover\"/>".$headstoremeta.$headstoreform.$headstoreview.$headstorelang."<script type=\"text/javascript\">hcms_drag(document.getElementById('meta_mover'), document.getElementById('meta_info'));</script></div>";
          }
          // no body-tag available
          else
          {
            $viewstore_new = "<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv=\"Content-Type\" content=\"".$contenttype."\" />
            ".$line_css;
            
            if ($buildview == "inlineview")
            {
              $viewstore_new .= showinlineeditor_head ($lang).showinlinedatepicker_head ();
            }
            
            $viewstore_new .= "
</head>
<body class=\"hcmsWorkplaceGeneric\">\n";

            if ($buildview != "preview") $viewstore_new .= "<div id=\"meta_info\" style=\"display:block; margin:3px;\">".$headstoremeta.$headstoreform.$headstoreview.$headstorelang."</div>";
            
            $viewstore_new .= "".$viewstore."\n</body>\n</html>";
      
            $viewstore = $viewstore_new;
            unset ($viewstore_new);
            
            // define body tag
            $bodytagold = "<body class=\"hcmsWorkplaceGeneric\">";                      
          }
          
          $bodytagnew = str_ireplace ("onload", "lockonload", $bodytagold);

          // form for multiple component manipulation in cmsview
          if ($buildview != "preview") $bodytagnew = $bodytagnew."        
  <div style=\"display:none;\">
  <form name=\"hcms_result\" action=\"".$mgmt_config['url_path_cms']."service/savecontent.php\" method=\"post\">
    <input type=\"hidden\" name=\"site\" value=\"".$site."\" />
    <input type=\"hidden\" name=\"cat\" value=\"".$cat."\" />
    <input type=\"hidden\" name=\"compcat\" value=\"multi\" />
    <input type=\"hidden\" name=\"location\" value=\"".$location_esc."\" />
    <input type=\"hidden\" name=\"page\" value=\"".$page."\" />
    <input type=\"hidden\" name=\"contentfile\" value=\"".$contentfile."\" />
    <input type=\"hidden\" name=\"db_connect\" value=\"".$db_connect."\" />
    <input type=\"hidden\" name=\"id\" value=\"\" />
    <input type=\"hidden\" name=\"tagname\" value=\"\" />
    <input type=\"hidden\" name=\"component_curr\" value=\"\" />
    <input type=\"hidden\" name=\"component\" value=\"\" />  
    <input type=\"hidden\" name=\"condition\" value=\"\" /> 
    <input type=\"hidden\" name=\"token\" value=\"".$token."\">
  </form>
  </div>
  <div style=\"margin:4px; padding:0; border:0; background:none; visibility:visible;\">".$headstore."</div>\n";
      
          // javascript code
          if ($buildview != "preview") $scriptcode .= "<script src=\"".$mgmt_config['url_path_cms']."javascript/main.js\" type=\"text/javascript\"></script>
  <script language=\"JavaScript\">
  <!--  
  function hcms_openWindowComp (winName, features, theURL)
  { 
    if (theURL != '')
    {
      if (theURL.indexOf('://') == -1)
      {      
        position1 = theURL.indexOf('/');
        position2 = theURL.lastIndexOf('/');
        location_comp = \"%comp%/\" + theURL.substring (position1+1, position2+1);
        
        location_site = theURL.substring (position1+1, theURL.length-position1);              
        location_site = location_site.substring(0, location_site.indexOf('/'));
        
        page_comp = theURL.substr (position2+1, theURL.length);
        theURL = '".$mgmt_config['url_path_cms']."frameset_content.php?ctrlreload=yes&cat=comp&site=' + location_site + '&location=' + location_comp + '&page=' + page_comp + '&user=".$user."';

        popup = window.open(theURL,winName,features);
        popup.moveTo(screen.width/2-800/2, screen.height/2-600/2);
        popup.focus();
      }
    }
    else alert(hcms_entity_decode('".getescapedtext ($hcms_lang['no-component-selected'][$lang], $charset, $lang)."'));  
  }
  
  var item = new Array();
  ".$scriptarray."
  
  function hcms_changeitem (tagname, id, condition, pos, type)
  {
    var component_serialized = '';
    var component_curr = '';
    var changes = false;
    var i = 0;
    
    if (tagname.indexOf('art') == 0) art = 'art';
    else art = '';
    
    if (eval (item[id]))
    {
      while (i < item[id].length)
      {
        component_curr = component_curr + item[id][i] + '|';  
        i++;
      }
    } 
      
    i = 0;
      
    if (type != 'send')
    {
      if (eval (item[id]))
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
      component_serialized = component_curr;
      document.forms['hcms_result'].attributes['action'].value = 'frameset_edit_component.php';
      changes = true;
    }
  
    if (changes == true)
    {
      document.forms['hcms_result'].elements['id'].value = id;
      document.forms['hcms_result'].elements['tagname'].value = tagname;
      document.forms['hcms_result'].elements['component_curr'].value = component_curr;
      document.forms['hcms_result'].elements['component'].value = component_serialized;
      document.forms['hcms_result'].elements['condition'].value = condition;

      if (type != 'send') 
      {
        document.forms['hcms_result'].elements['component_curr'].name = art + 'component_curr[' + id + ']';
        document.forms['hcms_result'].elements['component'].name = art + 'component[' + id + ']';
        document.forms['hcms_result'].elements['condition'].name = art + 'condition[' + id + ']';
      }

      document.forms['hcms_result'].submit();
    }
    
    return true;
  }
  //-->
  </script>\n";
        }
        
        // =========================================== inject script code ==============================================
        if (isset ($scriptcode) && $scriptcode != "")
        {
          // include javascript
          $vendor = "<meta name=\"keyword\" content=\"hyper Content Management Server (http://www.hypercms.com/)\" />";
          
          if (preg_match ("/<head/i", $viewstore))
          {
            $viewstore = str_ireplace ("<head>", "<head>\n".$vendor, $viewstore);
            $viewstore = str_ireplace ("</head>", $scriptcode."</head>", $viewstore);
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
        elseif ($buildview == "publish" || $buildview == "template" || $buildview == "preview")
        {
          $viewstore = str_replace ("hypercms_href=", "href=", $viewstore);
        }           
                
        // ======================================== add header information =============================================
        if ($buildview == "publish" && $application != "media")
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
            $viewstore = $tpl_globals.$tpl_livelink.$tpl_linkindex.$pagetracking.trim ($viewstore);     
          }
        }
      }
      // =========================================== FORM views ==============================================
      elseif ($buildview == "formedit" || $buildview == "formmeta" || $buildview == "formlock")
      {
        // ================================ javascript for control frame call ================================
        if ($ctrlreload == "yes")
        {
          $bodytag_controlreload = "if (eval (parent.frames['controlFrame'])) parent.frames['controlFrame'].location.href='".$mgmt_config['url_path_cms']."control_content_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';";
        }
        else $bodytag_controlreload = "";     
              
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
  <base href=\"".$mgmt_config['url_path_cms']."editor/\" />
  <meta http-equiv=\"Content-Type\" content=\"".$contenttype."\" />
  <meta name=\"robots\" content=\"noindex, nofollow\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"".getthemelocation()."css/main.css\" />
  <script src=\"".$mgmt_config['url_path_cms']."javascript/main.js\" type=\"text/javascript\"></script>
  <script src=\"".$mgmt_config['url_path_cms']."javascript/jquery/jquery-1.10.2.min.js\" type=\"text/javascript\"></script>
  <script src=\"".$mgmt_config['url_path_cms']."javascript/jquery-ui/jquery-ui-1.10.2.min.js\" type=\"text/javascript\"></script>
  <script type=\"text/javascript\" src=\"".$mgmt_config['url_path_cms']."editor/ckeditor/ckeditor.js\"></script>
  <script type=\"text/javascript\">CKEDITOR.disableAutoInline = true;</script>
  <link  rel=\"stylesheet\" type=\"text/css\" href=\"".$mgmt_config['url_path_cms']."javascript/rich_calendar/rich_calendar.css\" />
  <script language=\"JavaScript\" type=\"text/javascript\" src=\"".$mgmt_config['url_path_cms']."javascript/rich_calendar/rich_calendar.js\"></script>
  <script language=\"JavaScript\" type=\"text/javascript\" src=\"".$mgmt_config['url_path_cms']."javascript/rich_calendar/rc_lang_en.js\"></script>
  <script language=\"JavaScript\" type=\"text/javascript\" src=\"".$mgmt_config['url_path_cms']."javascript/rich_calendar/rc_lang_de.js\"></script>
  <script language=\"Javascript\" type=\"text/javascript\" src=\"".$mgmt_config['url_path_cms']."javascript/rich_calendar/domready.js\"></script>
  <script language=\"Javascript\" type=\"text/javascript\" src=\"".$mgmt_config['url_path_cms']."javascript/tag-it/tag-it.min.js\"></script>
  <link rel=\"stylesheet\" type=\"text/css\" href=\"".$mgmt_config['url_path_cms']."javascript/tag-it/jquery.tagit.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"".$mgmt_config['url_path_cms']."javascript/tag-it/tagit.ui-zendesk.css\" />
  <script language=\"JavaScript\" type=\"text/javascript\">
  <!--
  ".$bodytag_controlreload."";
  
  if ($buildview != "formlock") $viewstore .= "
  ".$bodytag_popup."

  function validateForm() 
  {
    var i,p,q,nm,test,num,min,max,errors='',args=validateForm.arguments;
    
    for (i=0; i<(args.length-2); i+=3) 
    { 
      test = args[i+2];
      contentname = args[i+1];
      val = hcms_findObj(args[i]);
      
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
        
        if ((val=val.value) != '' && test != '') 
        {
          if (test == 'audio' || test == 'compressed' || test == 'flash' || test == 'image' || test == 'text' || test == 'video') 
          { 
            errors += checkMediaType(val, contentname, test);
          } 
          else if (test.indexOf('isEmail')!=-1) 
          { 
            p=val.indexOf('@');
            if (p<1 || p==(val.length-1)) errors += nm+' - ".getescapedtext ($hcms_lang['value-must-contain-an-e-mail-address'][$lang], $charset, $lang).".\\n';
          } 
          else if (test!='R') 
          { 
            num = parseFloat(val);
            if (isNaN(val)) errors += nm+' - ".getescapedtext ($hcms_lang['value-must-contain-a-number'][$lang], $charset, $lang).".\\n';
            if (test.indexOf('inRange') != -1) 
            { 
              p=test.indexOf(':');
              if(test.substring(0,1) == 'R')
              {
                min=test.substring(8,p); 
              } else {
                min=test.substring(7,p); 
              }
              max=test.substring(p+1);
              if (num<min || max<num) errors += nm+' - ".getescapedtext ($hcms_lang['value-must-contain-a-number-between'][$lang], $charset, $lang)." '+min+' - '+max+'.\\n';
            } 
          } 
        } 
        else if (test.charAt(0) == 'R') errors += nm+' - ".getescapedtext ($hcms_lang['a-value-is-required'][$lang], $charset, $lang).".\\n'; 
      }
    } 
    
    if (errors) 
    {
      alert (hcms_entity_decode ('".getescapedtext ($hcms_lang['the-input-is-not-valid'][$lang], $charset, $lang).":\\n'+errors));
      return false;
    }  
    else return true;
  } 
  
  function checkMediaType(mediafile, medianame, mediatype)
  {    
    if (mediafile != '' && mediatype != '')
    {
      var mediaext = mediafile.substring (mediafile.lastIndexOf('.'), mediafile.length);
      mediaext = mediaext.toLowerCase();
     
      if (mediaext.length > 2)
      {
        if (mediatype == 'audio') allowedext = '".strtolower ($hcms_ext['audio'])."';
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
  
  function getValue(selectname, defaultvalue)
  {
    if (document.forms['hcms_formview'].elements[selectname] && document.forms['hcms_formview'].elements[selectname].value)
    {
      return encodeURIComponent (document.forms['hcms_formview'].elements[selectname].value);
    }
    else return defaultvalue;
  }
  
  function getSelectedOption(selectname, defaultvalue)
  {
    if (document.forms['hcms_formview'].elements[selectname] && document.forms['hcms_formview'].elements[selectname].options)
    {
      var selectbox = document.forms['hcms_formview'].elements[selectname];
      return encodeURIComponent (selectbox.options[selectbox.selectedIndex].value);
    }
    else return defaultvalue;
  }  
    
  function moveSelected(select, down)
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
      swapOption.defaultSelected = select.options[select.selectedIndex].defaultSelected;
  
      for (var property in swapOption) select.options[select.selectedIndex][property] = select.options[i][property];
      for (var property in swapOption) select.options[i][property] = swapOption[property];
    }
  }
  
  function deleteEntry(select)
  {
    select.value = '';
  }  
  
  function deleteSelected(select)
  {
    if (select.length > 0)
    {
      for(var i=0; i<select.length; i++)
      {
        if (select.options[i].selected == true) select.remove(i);
      }
    }
  }
  
  function replace(string,text,by)
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
  
  function openBrWindowLink(select, winName, features, type)
  {
    var select_temp = eval(\"document.forms['hcms_formview'].elements['temp_\" + select.name + \"']\");

    if (eval (select) && eval (select_temp) && select_temp.value != '')
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
          theURL = replace (select.value, '%page%/".$site."/', '".$publ_config['url_publ_page']."');
        }
    
        popup = window.open(theURL,winName,features);
        popup.moveTo(screen.width/2-800/2, screen.height/2-600/2);
        popup.focus();
      }
      else if (type == 'cmsview' || type == 'inlineview')  
      {
        theURL = select.value;
        
        if (theURL.indexOf('://') == -1)
        {      
          position1 = theURL.indexOf('/');
          position2 = theURL.lastIndexOf('/');
            
          location_page = theURL.substring (position1, position2+1);
          location_page = replace (location_page, '/".$site."/', '%page%/".$site."/');
          location_page = encodeURIComponent (location_page);
            
          location_site = theURL.substring (position1+1, theURL.length);              
          location_site = location_site.substring(0, location_site.indexOf('/'));
          location_site = encodeURIComponent (location_site);
            
          page = theURL.substring (position2 + 1, theURL.length);
          if (page.indexOf('?') > 0) page = page.substring (0, page.indexOf('?'));
          if (page.indexOf('#') > 0) page = page.substring (0, page.indexOf('#'));
          page = encodeURIComponent (page);
          
          theURL = '".$mgmt_config['url_path_cms']."frameset_content.php?ctrlreload=yes&cat=page&site=' + location_site + '&location=' + location_page + '&page=' + page + '&user=".$user."';
  
          popup = window.open(theURL,winName,features);
          popup.moveTo(screen.width/2-800/2, screen.height/2-600/2);
          popup.focus();
        }
        else alert(hcms_entity_decode('".getescapedtext ($hcms_lang['this-is-an-external-page-link'][$lang], $charset, $lang)."'));
      }    
    }
    else alert(hcms_entity_decode('".getescapedtext ($hcms_lang['no-link-selected'][$lang], $charset, $lang)."'));
  }  
  
  function openBrWindowComp(select, winName, features, type)
  {
    theURL = select.value;
    
    if (theURL != '')
    {
      if (type == 'preview')
      {
        if (theURL.indexOf('://') == -1)
        {
          position1 = theURL.indexOf('/');
          theURL = '".$publ_config['url_publ_comp']."' + theURL.substring (position1 + 1, theURL.length);
        }
    
        popup = window.open(theURL,winName,features);
        popup.moveTo(screen.width/2-800/2, screen.height/2-600/2);
        popup.focus();
      }
      else if (type == 'cmsview' || type == 'inlineview')  
      {
        if (theURL.indexOf('://') == -1)
        {      
          position1 = theURL.indexOf('/');
          position2 = theURL.lastIndexOf('/');
          location_comp = '%comp%/' + theURL.substring (position1 + 1, position2 + 1);
          location_comp = encodeURIComponent (location_comp);
            
          location_site = theURL.substring (position1+1, theURL.length);              
          location_site = location_site.substring(0, location_site.indexOf('/'));
          location_site = encodeURIComponent (location_site);

          page_comp = theURL.substr (position2+1, theURL.length);
          page_comp = encodeURIComponent (page_comp);
          
          theURL = '".$mgmt_config['url_path_cms']."frameset_content.php?ctrlreload=yes&cat=comp&site=' + location_site + '&location=' + location_comp + '&page=' + page_comp + '&user=".$user."';
  
          popup = window.open(theURL,winName,features);
          popup.moveTo(screen.width/2-800/2, screen.height/2-600/2);
          popup.focus();
        }
        else alert(hcms_entity_decode('".getescapedtext ($hcms_lang['this-is-an-external-component-link'][$lang], $charset, $lang)."'));
      }
    }
    else alert(hcms_entity_decode('".getescapedtext ($hcms_lang['no-component-selected'][$lang], $charset, $lang)."'));  
  } 
  
  function submitText(selectname, targetname)
  {
    document.forms['hcms_formview'].elements[targetname].value = document.forms['hcms_formview'].elements[selectname].value;
  }
  
  function submitLink(selectname, targetname)
  { 
    var select = document.forms['hcms_formview'].elements[selectname];
    var target = document.forms['hcms_formview'].elements[targetname];
    
    if (eval (select) && eval (target))
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

            if (select_temp.value.indexOf('#') > 0) target.value = target.value.substring(0, target.value.indexOf('#')) + link_add;
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
  
  function submitMultiComp(selectname, targetname)
  {
    var component = '';
    var select = document.forms['hcms_formview'].elements[selectname];
    var target = document.forms['hcms_formview'].elements[targetname];
  
    if(select.options.length > 0)
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
  
  function moveBoxEntry(fbox, tbox)
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
  
  function submitLanguage(selectname, targetname)
  {
    var content = '' ;
    var select = document.forms['hcms_formview'].elements[selectname];
    var target = document.forms['hcms_formview'].elements[targetname]
  
    if(select.options.length > 0)
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
  

  function deleteComment(element, value)
  {
    element.disabled = value;
  }  
  
  function setSaveType(type, url)
  {
    var checkcontent = true;
    
    ".$add_constraint."
    
    if (checkcontent == true)
    { 
      document.forms['hcms_formview'].elements['savetype'].value = type;
      document.forms['hcms_formview'].elements['forward'].value = url;
      ".$add_submittext."
      ".$add_submitlanguage."
      ".$add_submitlink."
      ".$add_submitcomp."
      hcms_stringifyVTTrecords();
      document.forms['hcms_formview'].submit();
      return true;
    }  
    else return false;
  }
  ";
  
  // autosave code
  if (intval ($mgmt_config['autosave']) > 0)
  {
    $autosave_active = "var active = $(\"#autosave\").is(\":checked\");";
    $autosave_timer = "setTimeout ('autosave()', ".(intval ($mgmt_config['autosave']) * 1000).");";
  }
  else
  {
    $autosave_active = "var active = true;";
    $autosave_timer = "";
  }
  
  if ($buildview != "formlock") $viewstore .= "
  function autosave ()
  {
    ".$autosave_active."
      
    if (active == true)
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
            
        for (var i in CKEDITOR.instances)
        {
          CKEDITOR.instances[i].updateElement();
        }
        
        hcms_showHideLayers ('messageLayer','','show');
        $(\"#savetype\").val('auto');
            
        $.post(
          \"".$mgmt_config['url_path_cms']."service/savecontent.php\", 
          $(\"#hcms_formview\").serialize(), 
          function (data)
          {
            if (data.message.length !== 0)
            {
              alert (hcms_entity_decode(data.message));
            }				
            setTimeout (\"hcms_showHideLayers('messageLayer','','hide')\", 1500);
          }, 
          \"json\"
        );
      }
    }
    
    ".$autosave_timer."
  }
  
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
          list ($vtt, $vtt_langcode) = explode ("-", $vtt_id[0]);
          
          $vtt_string = getcontent ($vtt_textnode, "<textcontent>");
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
  
  // onload event / document ready
  if ($add_onload != "") $viewstore .= "
  $(document).ready(function() {".
    $add_onload."
  });
  
  // global object for VTT records
  var vtt_object = ".$vtt_records.";
  ";
  
  $viewstore .= "
  //-->
  </script>";
  
  $viewstore .= "</head>

<body class=\"hcmsWorkplaceGeneric\">
  
  <div id=\"messageLayer\" style=\"position:absolute; width:350px; height:40px; z-index:6; left:150px; top:120px; visibility:hidden;\">
    <table width=\"350\" height=\"40\" border=0 cellspacing=0 cellpadding=3 class=\"hcmsMessage\">
      <tr>
        <td align=\"center\" valign=\"top\">
          <div style=\"width:100%; height:100%; z-index:10; overflow:auto;\">
          ".getescapedtext ($hcms_lang['autosave'][$lang], $charset, $lang)."
          </div>
        </td>
      </tr>
    </table>
  </div>";
  
  if ($buildview != "formlock") $viewstore .= "
  <form action=\"".$mgmt_config['url_path_cms']."service/savecontent.php\" method=\"post\" name=\"hcms_formview\" id=\"hcms_formview\" accept-charset=\"".$charset."\" enctype=\"application/x-www-form-urlencoded\">
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
    <input type=\"hidden\" name=\"wf_token\" value=\"".$wf_token."\">
    <input type=\"hidden\" name=\"token\" value=\"".$token."\">";
    
    $viewstore .= "
    <!-- top bar -->
    <div id=\"bar\" class=\"hcmsWorkplaceBar\">
      <table style=\"width:100%; height:100%; padding:0; border-spacing:0; border-collapse:collapse;\">
        <tr>
          <td class=\"hcmsHeadline\" style=\"text-align:left; vertical-align:middle; padding:0px 1px 0px 2px\">\n";
        
        // save buttons
        if ($buildview == "formlock") 
        {
          $viewstore .= "<img name=\"Button_so\" src=\"".getthemelocation()."img/button_save.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" align=\"absmiddle\" />\n";
          if (($mediafile == false || $mediafile == "") && $page != ".folder" && $objectview != "formedit" && $objectview != "formmeta" && $objectview != "formlock") $viewstore .= "<img name=\"Button_sc\" src=\"".getthemelocation()."img/button_saveclose.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" align=\"absmiddle\" />\n";
        }
        else
        {
          $viewstore .= "<img name=\"Button_so\" src=\"".getthemelocation()."img/button_save.gif\" class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"setSaveType('form_so', '');\" alt=\"".getescapedtext ($hcms_lang['save'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['save'][$lang], $charset, $lang)."\" align=\"absmiddle\" />\n";
          if (($mediafile == false || $mediafile == "") && $page != ".folder" && $objectview != "formedit" && $objectview != "formmeta" && $objectview != "formlock") $viewstore .= "<img name=\"Button_sc\" src=\"".getthemelocation()."img/button_saveclose.gif\" class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"setSaveType('form_sc', '');\" alt=\"".getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang)."\" align=\"absmiddle\" />\n"; 
        }
        
        // print button
        $viewstore .= "<img src=\"".getthemelocation()."img/button_print.gif\" class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"window.print();\" alt=\"".getescapedtext ($hcms_lang['print'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['print'][$lang], $charset, $lang)."\" align=\"absmiddle\" />\n";
        
        // autosave checkbox
        if (intval ($mgmt_config['autosave']) > 0 && ($buildview == "formedit" || $buildview == "formmeta")) $viewstore .= "<div class=\"hcmsButton\" style=\"font-weight:normal; height:22px;\">&nbsp;<input type=\"checkbox\" id=\"autosave\" name=\"autosave\" value=\"yes\" checked=\"checked\" /><label for=\"autosave\">&nbsp;".getescapedtext ($hcms_lang['autosave'][$lang], $charset, $lang)."</label>&nbsp;</div>\n";
        else $viewstore .= "<div class=\"hcmsButtonOff\" style=\"font-weight:normal; height:22px;\">&nbsp;<input type=\"checkbox\" id=\"autosave\" name=\"autosave\" value=\"\" disabled=\"disabled\" />&nbsp;".getescapedtext ($hcms_lang['autosave'][$lang], $charset, $lang)."&nbsp;</div>\n";
        
        $viewstore .= "</td>\n";
        
        // close button
        if (($mediafile == false || $mediafile == "" || $application == "generator") && $page != ".folder" && $objectview != "formedit" && $objectview != "formmeta" && $objectview != "formlock" || $buildview != "formedit")
        {
          if ($buildview == "formlock") $viewstore .= "<td style=\"width:26px; text-align:right; vertical-align:middle;\"><img name=\"mediaClose\" src=\"".getthemelocation()."img/button_close.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" /></td>\n";
          else $viewstore .= "<td style=\"width:26px; text-align:right; vertical-align:middle;\"><a href=\"".$mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."\" target=\"objFrame\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('mediaClose','','".getthemelocation()."img/button_close_over.gif',1);\"><img name=\"mediaClose\" src=\"".getthemelocation()."img/button_close.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['close'][$lang], $charset, $lang)."\" /></a></td>\n";
        }
        
        $viewstore .= "
        </tr>
      </table>
    </div>
    <div style=\"width:100%; height:32px;\">&nbsp;</div>\n";
    
      // include share links for image and video files
      if (is_dir ($mgmt_config['abs_path_cms']."connector/socialmedia/") && !empty ($mgmt_config[$site]['sharesociallink']) && $mediafile != "" && (is_image ($mediafile) || is_video ($mediafile)) && $buildview != "formlock")
      {
        $sharelink = createwrapperlink ($site, $location, $page, "comp");        
        $viewstore .= showsharelinks ($sharelink, $lang, "position:absolute; top:40px; right:12px;");
      }

      // table for form
      $viewstore .= "
    <!-- form for content -->
    <div class=\"hcmsWorkplaceFrame\" style=\"z-index:1;\">
      <table>\n";
        
        // add preview of media file (for media view the characters set is always UTF-8)
        if ($mediafile != false && $mediafile != "")
        {
          if ($buildview == "formedit" || $buildview == "formmeta") $mediaview = "preview";
          else $mediaview = "preview_no_rendering";
          
          $viewstore .= "<tr><td align=left valign=top><b>".getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang)."</b></td><td align=left valign=top>".showmedia ($site."/".$mediafile, convertchars ($name_orig, $hcms_lang_codepage[$lang], $charset), $mediaview)."</td></tr>\n";
        }
      
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
      </table>
    </div>\n";        
 
      $viewstore .= "
  </form>
</body>
</html>";
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
            $viewstore = preg_replace ("/\<\/head\>/i", showvideoplayer_head (false, false)."</head>", $viewstore);
          }
          elseif (substr_count (strtolower ($viewstore), "<body") > 0)
          {
            $bodytagold = gethtmltag ($viewstore, "<body");
            $viewstore = str_replace ($bodytagold, $bodytagold."\n".showvideoplayer_head (false, false), $viewstore);
          }
          elseif (substr_count (strtolower ($viewstore), ":body") > 0)
          {
            $bodytagold = gethtmltag ($viewstore, ":body");
            $viewstore = str_replace ($bodytagold, $bodytagold."\n".showvideoplayer_head (false, false), $viewstore);
          }          
        }
        
        // We only add if a audio is used (audio.js Player) and VIDEO.JS has not been integrated already
        if (preg_match('/\<audio.*?id=[\"\\\']hcms_mediaplayer_/i', $viewstore) && substr_count ($viewstore, "javascript/video-js/video.js") == 0)
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
    else return false;
  }
  // if contentfile or template file is missing
  else return false;
  
  // eventsystem
  if ($eventsystem['oneditobject_post'] == 1 && $eventsystem['hide'] == 0 && ($buildview == "cmsview" || $buildview == 'inlineview')) 
  {
    // include hyperCMS Event System
    @include_once ($mgmt_config['abs_path_data']."eventsystem/hypercms_eventsys.inc.php");
    oneditobject_post ($site, $cat, $location, $page, $user);    
  }

  // return result array
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

// --------------------------------- buildsearchform -------------------------------------------
// function: buildsearchform()
// input: publication name, template name, group access as array
// output: form view

function buildsearchform ($site, $template, $ownergroup="")
{ 
  global $user, $mgmt_config, $mgmt_lang_shortcut_default, $hcms_charset, $hcms_lang_name, $hcms_lang_shortcut, $hcms_lang_codepage, $hcms_lang_date, $hcms_lang, $lang;    
             
  // ----------------------------------- build view of page -----------------------------------------

  // =================================================== load template =================================================  
  // load associated template xml file and read information
  $result = loadtemplate ($site, $template); 
 
  $templatedata = $result['content'];
  $templatesite = $result['publication'];

  $bufferdata = getcontent ($templatedata, "<content>");
  
  // add newline at the begin to correct errors in tag-search
  $viewstore = "\n".$bufferdata[0];

  if ($viewstore != "")
  {
    // =============================== get content-type and character set ===============================
    $result = getcharset ($site, $viewstore);
    
    $contenttype = $result['contenttype'];
    $hcms_charset = $charset = $result['charset'];  
      
    // =================================================== text content ===================================================
    $searchtag_array[0] = "arttext";
    $searchtag_array[1] = "text";
    $infotype = "";
    $value = "";
    $id_array = array();
    
    foreach ($searchtag_array as $searchtag)
    {
      // get all hyperCMS tags
      $hypertag_array = gethypertag ($viewstore, $searchtag, 0);    
    
      if ($hypertag_array != false) 
      {
        reset ($hypertag_array);
        
        // loop for each hyperCMS tag found in template
        foreach ($hypertag_array as $key => $hypertag)
        {
          // get tag name
          $hypertagname = gethypertagname ($hypertag);
          
          // get tag id
          $id = getattribute ($hypertag, "id");

          // get article id
          $artid = getartid ($id);
          
          // element id
          $elementid = getelementid ($id);                     
          
          // get type of content
          $infotype = getattribute (strtolower ($hypertag), "infotype");
          
          // extract text value of checkbox
          $value = getattribute ($hypertag, "value");      
          
          // get label
          $label = getattribute ($hypertag, "label");

          // get group access
          $groupaccess = getattribute ($hypertag, "groups");
          $groupaccess = checkgroupaccess ($groupaccess, $ownergroup);
          
          if ($label == "") $label = $id;
          if (trim ($label) != "") $label = $label.":";                

          // id must be unique
          if (!in_array ($id, $id_array) && $groupaccess == true)
          {
            // search field for formatted and unformatted text
            if ($hypertagname == $searchtag."u" || $hypertagname == $searchtag."f" || $hypertagname == $searchtag."k")
            {
              // loop for unique media names for rollover effect
              if (@substr_count ($viewstore, $hypertag) >= 1)
              {    
                if ($searchtag == "text")
                {
                  $formitem[$key] = "<tr><td align=left valign=top width=180 nowrap>".$label." </td><td align=left valign=top><input name=\"search_textnode[".$id."]\" size=30 /></td></tr>\n";
                }
                elseif ($searchtag == "arttext")
                {                        
                  $formitem[$key] = "<tr><td align=left valign=top width=180 nowrap>".$label." </td><td align=left valign=top><input name=\"search_textnode[".$id."]\" size=30 /></td></tr>\n";
                }           
              }
            }
            // search field for text lists (options)
            elseif ($hypertagname == $searchtag."l")
            {
              if (@substr_count ($viewstore, $hypertag) >= 1)
              {
                // extract text list
                $list = getattribute ($hypertag, "list");
                
                if ($searchtag == "text")
                {
                  // get list entries
                  $list_array = null;
                  $list_array = explode ("|", $list);
  
                  $formitem[$key] = "<tr><td align=left valign=top width=180 nowrap>".$label." </td><td align=left valign=top><select name=\"search_textnode[".$id."]\">\n";
                  
                  foreach ($list_array as $list_entry)
                  {
                    $formitem[$key] .= "<option value=\"".$list_entry."\">".$list_entry."</option>\n";
                  }
                                 
                  $formitem[$key] .= "</select></td></tr>\n";
                }
                elseif ($searchtag == "arttext")
                {
                  // get list entries
                  $list_array = explode ("|", $list);
  
                  $formitem[$key] = "<tr><td align=left valign=top width=180 nowrap>".$label." </td><td align=left valign=top><select name=\"search_textnode[".$id."]\">\n";
                  
                  foreach ($list_array as $list_entry)
                  {
                    $formitem[$key] .= "<option value=\"".$list_entry."\">".$list_entry."</option>\n";
                  }
                                 
                  $formitem[$key] .= "</select></td></tr>\n";
                }            
              }
            }
            // search field for checked values
            elseif ($hypertagname == $searchtag."c")
            {
              if (@substr_count ($viewstore, $hypertag) >= 1)
              {                    
                if ($searchtag == "text")
                {                  
                  $formitem[$key] = "<tr><td align=left valign=top width=180 nowrap>".$label." </td><td align=left valign=top><input type=\"checkbox\" name=\"search_textnode[".$id."]\" value=\"".$value."\"> ".$value."</td></tr>\n";
                }
                elseif ($searchtag == "arttext")
                {
                  $formitem[$key] = "<tr><td align=left valign=top width=180 nowrap>".$label." </td><td align=left valign=top><input type=\"checkbox\" name=\"search_textnode[".$id."]\" value=\"".$value."\"> ".$value."</td></tr>\n";
                }               
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
    <head>
    <title>hyperCMS</title>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=".getcodepage ($lang)."\">
    <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">
    </head>
    <body id=\"hcms_htmlbody\" class=\"hcmsWorkplaceExplorer\" onload=\"parent.hcms_showPage('contentLayer');\" leftmargin=\"3\" topmargin=\"3\" marginwidth=\"0\" marginheight=\"0\">
    <table width=\"100%\" cellspacing=2 cellpadding=0>
      <tr>
        <td align=\"left\" valign=\"middle\">\n";

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
    
    $viewstore .= "</table>\n</body>\n</html>";
    
    return $viewstore;
  }
  else return false;                  
}

// --------------------------------- buildbarchart -------------------------------------------
// function: buildbarchart()
// input: name/id of paper, width of paper in pixel, height of paper in pixel, top space in pixel, left space in pixel, x-axis values as array, y1-axis values as array, y2-axis values as array (optional), y3-axis values as array (optional),
//        paper CSS style, 1st bar chart CSS style, 2nd bar chart CSS style, 3rd bar chart CSS style, show y-value in bar [true,false]
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

function buildbarchart ($paper_name, $paper_width=600, $paper_height=300, $paper_top=10, $paper_left=40, $x_axis, $y1_axis, $y2_axis="", $y3_axis="", $paper_style="", $bar1_style="", $bar2_style="", $bar3_style="", $show_value=false)
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
    if ($bar_maxheight < 100) $bar_maxheight = 100;

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
    $result = "<div id=\"".$paper_name."\" style=\"position:relative; width:".$paper_width."px; height:".$paper_height."px; top:".$paper_top."px; left:".$paper_left."px; margin:0; padding:0; z-index:100; ".$paper_style."\">\n";

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

      // 1st bar 
      if ($bar_height > 0) $result .= "  <div id=\"bar1_".$i."\" title=\"".$y1_axis[$key]['text']."\" style=\"position:absolute; width:".$bar_width."px; height:".$bar_height."px; top:".$bar_top."px; left:".$bar_left."px; margin:0; padding:0; border:0; text-align:center;  vertical-align:top; z-index:200; ".$bar1_style."\">".$bar_value."</div>\n";
      // x-axis values
      $result .= "  <div id=\"xval".$i."\" style=\"position:absolute; width:".$x_width."px; top:".$paper_height."px; left:".$x_left."px; margin:0; padding:0; border:0; text-align:center; vertical-align:top; z-index:100;\">".$x_axis[$key]."</div>\n";
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

        // 2nd bar        
        if ($bar_height > 0) $result .= "  <div id=\"bar2_".$i."\" title=\"".$y2_axis[$key]['text']."\" style=\"position:absolute; width:".$bar_width."px; height:".$bar_height."px; top:".$bar_top."px; left:".$bar_left."px; margin:0; padding:0; border:0; text-align:center;  vertical-align:top; z-index:200; ".$bar2_style."\">".$bar_value."</div>\n";
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
        
        // 3rd bar    
        if ($bar_height > 0) $result .= "  <div id=\"bar3_".$i."\" title=\"".$y3_axis[$key]['text']."\" style=\"position:absolute; width:".$bar_width."px; height:".$bar_height."px; top:".$bar_top."px; left:".$bar_left."px; margin:0; padding:0; border:0; text-align:center;  vertical-align:top; z-index:200; ".$bar3_style."\">".$bar_value."</div>\n";
        $i++;
      }
    }
    
    $result .= "</div>\n";
    
    return $result;
  }
  else return false;  
}
?>