<script runat="server">
' these functions require the document root of the host as absolute 
' path in the file system. this is important if you have inclusions 
' of components via HTTP disabled. please note that  the repository 
' must be inside the document root or a virtual directory!

' this function requires MSXML!
void include_http (component)
{
  on error resume next
  
  Dim objXmlHttp
  Dim strHTML
  
  Set objXmlHttp = Server.CreateObject("Msxml2.ServerXMLHTTP")
  objXmlHttp.setTimeouts 1000,1000,1000,1000
  objXmlHttp.open "GET", component, False
  objXmlHttp.send  
  
  if (Err.number = 0 and objXmlHttp.status = 200)
  {
    Set objStream = Server.CreateObject("ADODB.Stream")
    objStream.Open
    objStream.Type = 2
    objStream.WriteText (objXmlHttp.responseBody)
    objStream.Position = 0
    objStream.Charset = hypercms_charset
    strHTML=objStream.ReadText(-1)
    strHTML= right (strHTML, len(strHTML)-2)
    objStream.close
  }
  else
  {
    strHTML = ""
  }
  
  response.write (strHTML)  
  Set objXmlHttp = Nothing
}

void insertlink (linkindex, id)
{
  for (i = 0 to linkindex.Count-1)
  {
    link = trim (linkindex(i))
    LinkArray = split(link, "|")
    
	  if (LinkArray(0) = "page" && LinkArray(1) = id && LinkArray(2) <> "")
    {
      if (InStr (LinkArray(2), "://") > 0)
      {
        response.write (LinkArray(2))
      }
      elseif (LinkArray(2) <> "")
      {
        response.write (LinkArray(2))
      }
      else
      { 
        response.write ("#")
      }
    }
    else
    { 
      response.write ("#")
    }
  }
  
  insertlink = true
}

void insertcomponent (linkindex, id)
{
  if (IsObject(linkindex) && id <> "")
  {
  	for i = 0 to linkindex.Count-1
    {
      link = trim (linkindex(i))
      LinkArray = split (link, "|")
    
      if (LinkArray(0) = "comp" and LinkArray(1) = id and LinkArray(2) <> "")
      {    
  	    if (InStr (LinkArray(2), "://") > 0)
        {
  		    include_http (LinkArray(2))
        }
  		  else
        {
    		  if (publ_config.item("http_incl") = "true")
          {       
    			  include_http (publ_config.item("url_publ_comp") & LinkArray(2))
          }
    		  else
          {
    		    link_correct = publ_config.item("url_publ_comp") & LinkArray(2)
    		    link_correct = Mid (link_correct, Instr (9, link_correct, "/"))
    			  server.execute (link_correct)
		      }
		    }
  	  }
    } 
    
    insertcomponent = true  
  }
}

' without link management support
void insertcomponent_wol (linkindex, id)
{
  if (linkindex <> "" and id = "")
  {
    if (InStr (linkindex, "|") > 0)
    {
      LinkArray = split(linkindex, "|")
    
      for i = 0 to LinkArray.Count-1
      {
      	if (LinkArray(i) <> "")
        {
    	    if (InStr (LinkArray(2), "://") > 0)
          {
    		    include_http (LinkArray(2))
          }
    		  else
          {		
            if (publ_config.item("http_incl") = "true")
            {
              include_http (publ_config.item("url_publ_comp") & LinkArray(2))
            }
            else
            {
    		      link_correct = publ_config.item("url_publ_comp") & LinkArray(2)
    		      link_correct = Mid (link_correct, Instr (9, link_correct, "/"))
    			    server.execute (link_correct)
            }
		      }
      	}
      }
      
      insertcomponent_wol = true
    }
    else
    {
	    if (InStr (LinkArray(2), "://") > 0)
      {
		    include_http (LinkArray(2))
		  }
      else
      {
        if (publ_config.item("http_incl") = "true")
        {
          include_http (publ_config.item("url_publ_comp") & LinkArray(2))
        }
        else
        { 
      		link_correct = publ_config.item("url_publ_comp") & LinkArray(2)
      		link_correct = Mid (link_correct, Instr (9, link_correct, "/"))
      		server.execute (link_correct)
        }
      }
      
      insertcomponent_wol = true
    }
  }
}
</script>