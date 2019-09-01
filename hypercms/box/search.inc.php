<?php
// ---------------------- REPORTS ---------------------
if (empty ($is_mobile) && isset ($siteaccess) && is_array ($siteaccess))
{
  // language file
  require_once ("language/".getlanguagefile ($lang));

  echo "
  <script type=\"text/javascript\">
  function startSearch ()
  {
    if (document.forms['searchform'])
    {
      var form = document.forms['searchform'];
    
      // search expression
      if (form.elements['search_expression'] && form.elements['search_expression'].value.trim() == \"\")
      {
        alert (hcms_entity_decode(\"".getescapedtext ($hcms_lang['please-insert-a-search-expression'][$lang])."\"));
        form.elements['search_expression'].focus();
        return false;
      }

      // load screen
      if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline';
      
      // submit form
      form.submit();
    }
  }
  </script>
  <div id=\"searchbar\" style=\"overflow:auto; margin:60px 0px; width:100%; text-align:center; float:left;\">
    <form name=\"searchform\" method=\"post\" action=\"frameset_objectlist.php\">
      <input type=\"hidden\" name=\"search_dir\" value=\"\" />
      <input type=\"hidden\" name=\"action\" value=\"base_search\" />
      <input type=\"text\" name=\"search_expression\" onkeydown=\"if (hcms_enterKeyPressed(event)) startSearch();\" placeholder=\"".getescapedtext ($hcms_lang['search'][$lang])."\" style=\"width:30%; min-width:320px; padding:14px 30px 14px 10px; margin:0px auto;\"\" maxlength=\"2000\" />
      <img src=\"".getthemelocation()."img/button_search_dark.png\" style=\"cursor:pointer; width:22px; height:22px; margin-left:-30px;\" onClick=\"startSearch('general');\" title=\"".getescapedtext ($hcms_lang['search'][$lang])."\" alt=\"".getescapedtext ($hcms_lang['search'][$lang])."\" />
  </div>\n";
}
?>