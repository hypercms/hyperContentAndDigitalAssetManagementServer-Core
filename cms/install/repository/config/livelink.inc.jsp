<%@ page import="java.io.*" %>
<%@ page import="java.util.*" %>
<%@ page import="java.net.*" %>
<%!  
public boolean urlexists(java.lang.String URLName) 
{
  try 
  {
    HttpURLConnection.setFollowRedirects(true);
    HttpURLConnection con = (HttpURLConnection) new URL(URLName).openConnection();
    con.setRequestMethod("HEAD");
    if (con.getResponseCode() == HttpURLConnection.HTTP_OK) return true;
  } catch (Exception e) {}
  
  return false;
}

public String geturl(String URLName) 
{
  try 
  {
    java.net.URL url = new URL(URLName);
    java.net.HttpURLConnection con = null;
	
    con =(java.net.HttpURLConnection) url.openConnection();
    con.setRequestProperty("Content-Type","text/xml");
    con.setDoOutput(true);
    con.setDoInput(true);
    con.setRequestMethod("GET");
	
	if(con.getResponseCode() != 404){
    
		BufferedReader in = new BufferedReader(new InputStreamReader(con.getInputStream()));
	
		StringBuffer response = new StringBuffer();
		String line;
		
		while ((line = in.readLine()) != null) response.append(line);
		in.close();
		
	    return response.toString();
	}
	else {
	    return "";
	}
	
  } 
  catch(Exception e) {return e.toString();}
}

public String strreplace(String pattern, String replace, String str) 
{
  int s = 0;
  int e = 0;
  StringBuffer result = new StringBuffer();

  while ((e = str.indexOf(pattern, s)) >= 0) 
  {
    result.append(str.substring(s, e));
    result.append(replace);
    s = e+pattern.length();
  }
  
  result.append(str.substring(s));
  return result.toString();
}

public String insertlink (Vector linkindex, String id, Properties properties) 
{
  String output="";
  
  String link;
  int i,j;
  boolean cont=true;
  
  String cat="", link_id="", link_href="", link_correct="";
  String url_publ_page = properties.getProperty("url_publ_page");
  
  if (linkindex.size()>0) 
  {
    for (i=0; i<linkindex.size() && cont; i++) 
    {
      link = (String)linkindex.elementAt(i);
      link.trim();

      StringTokenizer st = new StringTokenizer(link, "|");
      String item="";
      cat = st.nextToken();
      link_id = st.nextToken();
      link_href = st.nextToken();
      
      if (cat.equals("page") && link_id.equals(id) && !link_href.equals("")) 
      {
	  
        if (link_href.indexOf("://")>0) 
        {
          if (urlexists (link_href)) output += link_href;
          else output+= "#";   
        }
        else
        {
          if (!link_href.equals("")) output+= link_href;
          else output+= "#";
        }
        
        cont = false;
      } 
    }
  }
  else
  {
    output+= "#";
  } 
  
  return output;
}

public StringTokenizer insertcomponent (Vector linkindex, String id, Properties properties, javax.servlet.jsp.JspWriter o) 
{
  String path_comp, link, cat,retStr, comp_id, component,rel_comp, incfile,error,http_incl;
  incfile = "";
  int i;
 
  try{
  	  error = "";
	  retStr = "";
	  path_comp = properties.getProperty("url_publ_comp");
	  rel_comp = properties.getProperty("rel_publ_comp");
	  http_incl = properties.getProperty("http_incl");

	  if (linkindex.size()>0) 
	  { 
		for (i=0; i<linkindex.size(); i++) 
		{

		  link = (String)linkindex.elementAt(i);
		  link.trim(); 

		  StringTokenizer st = new StringTokenizer(link, "|");
		  String item="";

		  try{cat = st.nextToken();}catch(NoSuchElementException e){ cat = "";}
 		  try{comp_id = st.nextToken();}catch(NoSuchElementException e){ comp_id = "";}
		  try{component = st.nextToken();}catch(NoSuchElementException e){ component = "";}
		
		  if (cat.equals("comp") && comp_id.equals(id)) 
		  {
		    if (component.indexOf("://", 0) > 0)
			{ 
			  retStr += rel_comp + component + "|";    
			  if(!http_incl.equals("false"))
  			    try { o.print (geturl (component)); } catch (Exception e) {}
			}
			else
			{
			  if(component!= null && component.length() != 0){
			  incfile = path_comp + component.substring(1);      
              retStr += rel_comp + component.substring(1) + "|";
			  if(!http_incl.equals("false"))
  			    try { o.print (geturl (incfile)); } catch (Exception e) {}
				}
			}
		  }
		}

		if(http_incl.equals("false"))
  		  return (new StringTokenizer(retStr,"|"));
	    else
		  return null;  
	  }
	  else return null;
  }
  catch(Exception e){
    error = e.toString();
  }
  try { o.print("Fehler:" + error); } catch (Exception e) {}
  return null;
}

public boolean insertcomponent (String linkindex, String id, Properties properties, javax.servlet.jsp.JspWriter o) 
{
  String path_comp, component, incfile;

  path_comp = properties.getProperty("url_publ_comp");
  
  if (!linkindex.equals("") && id.equals("")) 
  {
    if (linkindex.indexOf("|", 0) > 0) 
    {
      StringTokenizer st = new StringTokenizer(linkindex, "|");
      String item="";
      
      while (st.hasMoreTokens()) 
      {
        component = st.nextToken();
        
        if (component != "") 
        {
          if (component.indexOf("://", 0) > 0)
          {       
            try { o.print (geturl(component)); } catch (Exception e) {}
          }
          else
          {        
            incfile = path_comp + component;
            try { o.print (geturl (incfile)); } catch (Exception e) {}
          }
        }
      }
      
      return true;
    }
    else
    {
      if (linkindex.indexOf("://", 0) > 0)
      {       
        try { o.print (geturl (linkindex)); } catch (Exception e) {}
      }
      else
      {     
        incfile = path_comp + linkindex;
        try { o.print (geturl (incfile)); } catch (Exception e) {}
      }
      
      return true;
    }    
  } 
  else return false;
}
%>