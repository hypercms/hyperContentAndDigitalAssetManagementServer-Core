<?php
function insertlink ($hcms_linkindex, $hcms_id)
{ 
  global $publ_config;
  
  if (is_array ($hcms_linkindex) && sizeof ($hcms_linkindex) > 0)
  {
    reset ($hcms_linkindex);

    foreach ($hcms_linkindex as $hcms_link)
    {
      list ($hcms_cat, $hcms_link_id, $hcms_link_href) = explode ("|", trim ($hcms_link));

      if ($hcms_cat == "page" && $hcms_link_id == $hcms_id && $hcms_link_href != "")
      {
        if (@strpos ("://", $hcms_link_href) > 0)
        {
          // check if external href exists, uncomment these two lines and comment the echo $link_href:
          // if (@file ($hcms_link_href)) echo $hcms_link_href;
          // else echo "#";
          echo $hcms_link_href;
        }
        else
        {
          echo $hcms_link_href;
        }

        return true;
        break;
      }
    }
  }
  else
  {
    echo "#";
    return false;
  }
}

function insertcomponent ($hcms_linkindex, $hcms_id)
{
  global $publ_config;

  // with link management
  if (is_array ($hcms_linkindex) && sizeof ($hcms_linkindex) > 0)
  {
    reset ($hcms_linkindex);

    foreach ($hcms_linkindex as $hcms_link)
    {
      list ($hcms_cat, $hcms_comp_id, $hcms_component) = explode ("|", trim ($hcms_link));

      if ($hcms_cat == "comp" && $hcms_comp_id == $hcms_id && $hcms_component != "")
      {
        if ($publ_config['publ_os'] == "UNIX")
        {
          if ($publ_config['http_incl'] == true) @include ($publ_config['url_publ_comp'].$hcms_component);
          else @include ($publ_config['abs_publ_comp'].$hcms_component);
        }
        elseif ($publ_config['publ_os'] == "WIN")
        {
          if ($publ_config['http_incl'] == true) echo @file_get_contents ($publ_config['url_publ_comp'].$hcms_component);
          else @include ($publ_config['abs_publ_comp'].$hcms_component);
        }
      }
    }

    return true;
  }
  // without link management (id is not set)
  elseif ($hcms_linkindex != "" && $hcms_id == "")
  {
    if (substr_count ($hcms_linkindex, "|") >= 1)
    {
      $hcms_compmulti_array = explode ("|", $hcms_linkindex);
      
      if (is_array ($hcms_compmulti_array) && sizeof ($hcms_compmulti_array) > 0)
      {
        foreach ($hcms_compmulti_array as $hcms_component)
        {
          if ($hcms_component != "")
          {
            if ($publ_config['publ_os'] == "UNIX")
            {
              if ($publ_config['http_incl'] == true) @include ($publ_config['url_publ_comp'].$hcms_component);
              else @include ($publ_config['abs_publ_comp'].$hcms_component);
            }
            elseif ($publ_config['publ_os'] == "WIN")
            {
              if ($publ_config['http_incl'] == true) echo @file_get_conetnts ($publ_config['url_publ_comp'].$hcms_component);
              else @include ($publ_config['abs_publ_comp'].$hcms_component);
            }
          }
        }

        return true;
      }
      else return false;
    }
    else
    {
      $hcms_component = $hcms_linkindex;

      if ($publ_config['publ_os'] == "UNIX")
      {
        if ($publ_config['http_incl'] == true) @include ($publ_config['url_publ_comp'].$hcms_component);
        else @include ($publ_config['abs_publ_comp'].$hcms_component);
      }
      elseif ($publ_config['publ_os'] == "WIN")
      {
        if ($publ_config['http_incl'] == true) echo @file_get_contents ($publ_config['url_publ_comp'].$hcms_component);
        else @include ($publ_config['abs_publ_comp'].$hcms_component);
      }

      return true; 
    }
  }
  else return false;
} 
?>