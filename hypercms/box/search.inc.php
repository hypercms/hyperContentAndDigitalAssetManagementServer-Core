<?php
// ---------------------- SEARCH ---------------------
if (empty ($is_mobile) && isset ($siteaccess) && is_array ($siteaccess))
{
  // get search history of user
  if (!empty ($user)) $keywords = getsearchhistory ($user);

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

  $(document).ready(function()
  {
    // search history
    var available_expressions = [".(is_array ($keywords) ? implode (",\n", $keywords) : "")."];

    $('#search_expression').autocomplete({
      source: available_expressions
    });
  });
  </script>

  <div id=\"searchbar\" style=\"overflow:auto; margin:60px 0px; width:100%; text-align:center;\" title='".($mgmt_config['search_query_match'] == "match" ? getescapedtext ($hcms_lang['search-wildcard-plus'][$lang]." \r\n".$hcms_lang['search-wildcard-minus'][$lang]." \r\n".$hcms_lang['search-wildcard-none'][$lang]." \r\n".$hcms_lang['search-wildcard-asterisk'][$lang]." \r\n".$hcms_lang['search-wildcard-doublequote'][$lang]) : "")."'>
    <form name=\"searchform\" method=\"post\" action=\"frameset_objectlist.php\" autocomplete=\"off\">
      <input type=\"hidden\" name=\"search_dir\" value=\"\" />
      <input type=\"hidden\" name=\"action\" value=\"base_search\" />
      <input type=\"search\" id=\"search_expression\" name=\"search_expression\" onkeydown=\"if (hcms_enterKeyPressed(event)) startSearch();\" placeholder=\"".getescapedtext ($hcms_lang['search'][$lang])."\" style=\"width:30%; min-width:320px; padding:14px 30px 14px 10px; margin:0px auto;\"\" maxlength=\"2000\" autocomplete=\"off\" />
      <img src=\"".getthemelocation()."img/button_search_dark.png\" style=\"cursor:pointer; width:22px; height:22px; margin-left:-36px;\" onclick=\"startSearch('general');\" title=\"".getescapedtext ($hcms_lang['search'][$lang])."\" alt=\"".getescapedtext ($hcms_lang['search'][$lang])."\" />
      <div class=\"hcmsTextWhite hcmsTextShadow\" style=\"padding:4px 3px;\">
        ".getescapedtext ($hcms_lang['search-restriction'][$lang])." &nbsp;
        <label><input type=\"checkbox\" name=\"search_cat\" id=\"search_cat_text\" value=\"text\" onclick=\"if (this.checked) document.getElementById('search_cat_file').checked=false; else document.getElementById('search_cat_file').checked=true;\" checked /> ".getescapedtext ($hcms_lang['text'][$lang])."</label> &nbsp;
        <label><input type=\"checkbox\" name=\"search_cat\" id=\"search_cat_file\" value=\"file\" onclick=\"if (this.checked) document.getElementById('search_cat_text').checked=false; else document.getElementById('search_cat_text').checked=true;\" /> ".getescapedtext ($hcms_lang['location'][$lang]."/".$hcms_lang['object'][$lang]." ".$hcms_lang['name'][$lang])."</label>
      </div> 
    </form>
  </div>
  ";
}
?>