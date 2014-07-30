<?php
/**
 * Copyright (C) 2012 Jeff Tanner <jeff00seattle@gmail.com>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * lib/OpenSearch/DescriptionDoc.class.php
 * 
 * PHP class used for generating an OpenSearch discription document.
 * 
 * PHP Version 5
 * 
 */

require_once 'lib/OpenSearch/Support.inc.php';


/**
 * Used for generating an OpenSearch discription document.
 */
class OpenSearch_DescriptionDoc
{
    private $_strContentType='application/opensearchdescription+xml';
    private $_strShortName;
    private $_strDescription;
    private $_strContact=null;
    private $_strTags=null;
    private $_strLongName=null;
    private $_strDeveloper=null;
    private $_strAttribution=null;
    private $_strSyndicationRight=null;
    private $_bHasAdultContent=null;
    private $_bNeedReferExt=false;
    private $_bNeedTimeExt=false;
    private $_arrayUrls=array();
    private $_arrayImages=array();
    private $_arrayLanguages=array();
    private $_arrayInputEncoding=array();
    private $_arrayOutputEncoding=array();
    private $_arrayQuery=array();
    
    /**
      * Constructor
      *
      * @param string $strShortName   Short name of search engine
      * @param string $strDescription Search engine description.
      * 
      * @return VOID
      */
    public function OpenSearch_DescriptionDoc($strShortName, $strDescription)
    {
        $this->_strShortName=$strShortName;
        $this->_strDescription=$strDescription;
    }
    
    /**
      * Need to use referer extension.
      * By enabling this you can use {referrer:source?} in search template.
      * some client dose not support this!!
      * 
      * @param bool $bNeedReferExt need referer extension or not.
      * 
      * @return VOID
      */
    public function addNeedReferrerExtension($bNeedReferExt=true)
    {
        $this->_bNeedReferExt=$bNeedReferExt;
    }
    
    /**
      * addNeedTimeExtension()   need to use time extension .
      * by enabling this you can use {time:start?} and 
      * {time:stop?} in search template.
      * 
      * some clients di not support this!!
      * 
      * @param bool $bNeedTimeExt need time extension or not.
      * 
      * @return VOID
      */
    public function addNeedTimeExtension($bNeedTimeExt=true)
    {
        $this->_bNeedTimeExt=$bNeedTimeExt;
    }

    
    /**
      * Add a url element to this description document.
      *
      * @param string $strTemplate    The URL template to be processed 
      *     according to the OpenSearch URL template syntax.
      * @param string $strType        The MIME type of the resource being 
      *     described. The value must be a valid MIME type.
      * @param string $strResults     The role of the resource being 
      *     described in relation to the description document.
      * @param int    $intIndexOffset The index number of the 
      *     first search result. The value must be an integer.
      *   Default: "1"
      * @param int    $intPageOffset  The page number of the 
      *     first set of search results. The value must be an integer.
      *    Default: "1"
      * 
      * @return VOID
      * 
      * @link http://www.opensearch.org/Specifications/OpenSearch/1.1#The_.22Url.22_element
      * @link http://www.opensearch.org/Specifications/OpenSearch/1.1#Url_rel_values
      */
    public function addUrl(
        $strTemplate, 
        $strType, 
        $strResults="results", 
        $intIndexOffset=1, 
        $intPageOffset=1
    ) {
        $strAppend='';
        if ($strResults!="results") {
            $strAppend.=" rel=\"".$strResults."\"";
        }
        if ($intIndexOffset!=1) {
            $strAppend.=" indexOffset=\"".intval($intIndexOffset)."\"";
        }
        if ($intPageOffset!=1) {
            $strAppend.=" pageOffset=\"".intval($intPageOffset)."\"";
        }
        $this->_arrayUrls[]="<Url type=\"$strType\" template=\"$strTemplate\"$strAppend />";
    }
    
    /**
      * Add a url element contain suggestion link .
      *
      * @param string $strTemplate    The URL template to be processed 
      * according to the OpenSearch URL template syntax.
      * @param string $strType        The MIME type of the resource being 
      * described. The value must be a valid MIME type.default 
      * ('application/x-suggestions+json')
      * @param int    $intIndexOffset The index number of the first 
      * search result. The value must be an integer.
      *   Default: "1"
      * @param int    $intPageOffset  The page number of the first set 
      * of search results. The value must be an integer.
      *   Default: "1"
      *   
      * @return VOID
      */
    public function addSuggestions(
        $strTemplate,
        $strType='application/x-suggestions+json',
        $intIndexOffset =1,
        $intPageOffset=1
    ) {
        $this->addUrl(
            $strTemplate, 
            $strType, 
            'suggestions', 
            $intIndexOffset, 
            $intPageOffset
        );
    }
    
    /**
      * Add a url element contain request for a set of resources. .
      *
      * @param string $strTemplate    The URL template to be processed 
      * according to the OpenSearch URL template syntax.
      * @param string $strType        The MIME type of the resource being 
      * described. The value must be a valid MIME type.
      * @param int    $intIndexOffset The index number of the first 
      * search result. The value must be an integer.
      *   Default: "1"
      * @param int    $intPageOffset  The page number of the first 
      * set of search results. The value must be an integer.
      *   Default: "1"
      *   
      * @return VOID
      */
    public function addCollection(
        $strTemplate, 
        $strType, 
        $intIndexOffset =1, 
        $intPageOffset=1
    ) {
        $this->addUrl(
            $strTemplate, 
            $strType, 
            'collection', 
            $intIndexOffset, 
            $intPageOffset
        );
    }
    
    /**
      * Add a url element contain self link .
      *
      * @param string $strTemplate The URL template to be processed 
      * according to the OpenSearch URL template syntax.
      * @param string $strType     The MIME type of the resource being 
      * described. The value must be a valid MIME type.default 
      * ('application/opensearchdescription+xml')
      * 
      * @return VOID
      */
    public function addSelf(
        $strTemplate, 
        $strType='application/opensearchdescription+xml'
    ) {
        $this->addUrl($strTemplate, $strType, 'self');
    }
    
    /**
      * Add a image to this OS Description document
      *
      * @param string $strContent Link to image for this OSDD 
      * (By my tests must be complete not relative.).
      * @param string $strType    The MIME type of the resource 
      * being described. The value must be a valid MIME type.default .
      * @param int    $intHeight  The height of image.
      * @param int    $intWidth   The width of image
      * 
      * @return VOID
      * 
      * @link http://www.opensearch.org/Specifications/OpenSearch/1.1#The_.22Image.22_element
      */
    public function addImage(
        $strContent,
        $strType='',
        $intHeight=0,
        $intWidth=0
    ) {
        $strAppend='';
        //$strType must be valid MIME type!
        if ($strType!=='') {
            $strAppend.=" type=\"$strType\"";
        }
        if (intval($intHeight)>0) {
            $strAppend.=" height=\"".intval($intHeight)."\"";
        }
        if (intval($intWidth)>0) {
            $strAppend.=" width=\"".intval($intWidth)."\"";
        }
        
        $this->_arrayImages[]="<Image$strAppend>$strContent</Image>"       ;
    }

    
    /**
      * Defines a search query that can be performed 
      * by search clients. Please see the OpenSearch Query element 
      * specification for more information.
      *
      * @param string $role      One of valid roles : 
      * 'request','example','related','correction','subset','superset'
      * @param array  $optionals Options like 
      * searchTerms="cat", custom:color="blue", title="Sample search" 
      * and more see specification for more information
      * 
      * @return VOID
      * 
      * @link http://www.opensearch.org/Specifications/OpenSearch/1.1#The_.22Query.22_element
      */
    public function addQuery($role, $optionals=array())
    {
        $valid_role 
            = array( 
            'request', 
            'example', 
            'related', 
            'correction', 
            'subset', 
            'superset'
            );
        if (!strpos($role, ':')) {
            if (!in_array($role, $valid_role, true)) {
                return;
            }
        }
        $strAppend='';
        foreach ($optionals as $opt=>$val) {
            $strAppend.=" $opt=\"$val\"";
        }
        $this->_arrayQuery[]="<Query  role=\"$role\"$strAppend></Query>";
    }
        
    /**
      * Contains a value that indicates the degree 
      * to which the search results provided by this 
      * search engine can be queried, displayed, 
      * and redistributed.
      *
      * @param string $strValue Valid values: 
      * 	'open','limited','private','closed'
      * 
      * @return VOID
      * 
      * @link http://www.opensearch.org/Specifications/OpenSearch/1.1#The_.22SyndicationRight.22_element
      */
    public function addSyndicationRight($strValue=null)
    {
        $strAcceptedValue=array('open','limited','private','closed');
        if (in_array($strValue, $strAcceptedValue, true)) {
            $this->_strSyndicationRight=$strValue;
        } else {
            $this->_strSyndicationRight=null;
        }
    }
    
    /**
      * Contains a boolean value that should be set 
      * to true if the search results 
      * may contain material intended only for adults.
      *
      * @param bool $bHasAdultContent Has adult content or has not!
      * 
      * @return VOID
      * 
      * @link http://www.opensearch.org/Specifications/OpenSearch/1.1#The_.22AdultContent.22_element
      */
    public function addAdultContent($bHasAdultContent)
    {
        $this->_bHasAdultContent = $bHasAdultContent;
    }
    
    /**
      * addLanguage()   Contains a string that indicates 
      * that the search engine supports search results in 
      * the specified language.
      *
      * @param string $strLang Language code for results 
      * like "en" for English or "*" for all languages
      * 
      * @return VOID
      * 
      * @link http://www.opensearch.org/Specifications/OpenSearch/1.1#The_.22Language.22_element
      */
    public function addLanguage($strLang)
    {
        $this->_arrayLanguages[]=$strLang;
        $this->_arrayLanguages=array_unique($this->_arrayLanguages);
    }
    
    /**
      * Contains a string that indicates 
      * that the search engine supports search requests encoded 
      * with the specified character encoding.
      *
      * @param string $strEncoding String that indicates that the 
      * search engine supports search requests encoded with 
      * the specified character encoding. default UTF-8
      * 
      * @return VOID
      * 
      * @link http://www.opensearch.org/Specifications/OpenSearch/1.1#The_.22InputEncoding.22_element
      */
    public function addInputEncoding($strEncoding)
    {
        $this->_arrayInputEncoding[]=$strEncoding;
        $this->_arrayInputEncoding=array_unique($this->_arrayInputEncoding);
    }
    
    /**
      * Contains a string that indicates 
      * that the search engine supports search responses encoded 
      * with the specified character encoding.
      *
      * @param string $strEncoding String that indicates that the 
      * search engine supports search responses encoded with 
      * the specified character encoding. default UTF-8
      * 
      * @return VOID
      * 
      * @link http://www.opensearch.org/Specifications/OpenSearch/1.1#The_.22OutputEncoding.22_element
      */
    public function addOutputEncoding($strEncoding)
    {
        $this->_arrayOutputEncoding[]=$strEncoding;
        $this->_arrayOutputEncoding=array_unique($this->_arrayOutputEncoding);
    }
    
    /**
      * For internal use, check for all valid fields
      * 
      *  @access public
      *
      * @return VOID
      */
    public function validate()
    {
        if (count($this->_arrayUrls)<1) {
            trigger_error('The required field "Url" not set.');
        }
    }
    
    /**
      * Serve the description doc
      *
      * @param bool $bProvideHeader Use header to serve or not?
      * 
      * @return VOID
      */
    public function serve($bProvideHeader=true)
    {
    	$out = "";
        $this->validate();
        if ($bProvideHeader) {
            header("Content-type: ".$this->_strContentType);
        }
        $out .= "<OpenSearchDescription";
        $out .= "\n";
        $out .= "  ";
        $out .= "xmlns=";
        $out .= "\"http://a9.com/-/spec/opensearch/1.1/\"";
        
        if ($this->_bNeedReferExt) {
            $out .= "\n";
            $out .= "  ";
            $out .= "xmlns:referrer=";
            $out .= "\"http://a9.com/-/opensearch/extensions/referrer/1.0/\"";
        }
        
        if ($this->_bNeedTimeExt) {
            $out .= "\n";
            $out .= "  ";
            $out .= "xmlns:time=";
            $out .= "\"http://a9.com/-/opensearch/extensions/time/1.0/\"";
        }
        
        $out .= ">";
        $out .= "\n";
        
        $out .= "  ";
        $out .= "<ShortName>";
        $out .= $this->_strShortName;
        $out .= "</ShortName>";
        $out .= "\n";

        $out .= "  ";
        $out .= "<Description>";
        $out .= $this->_strDescription;
        $out .= "</Description>";
        $out .= "\n";
        
        foreach ($this->_arrayUrls as $url) {
            $out .= "  ";
            $out .= $url;
            $out .= "\n";
        }
        if (!empty($this->_strContact)) {
            $out .= "  ";
            $out .= "<Contact>";
            $out .= $this->_strContact;
            $out .= "</Contact>";
            $out .= "\n";
        }
        if (!empty($this->_strTags)) {
            $out .= "  ";
            $out .= "<Tags>";
            $out .= $this->_strTags;
            $out .= "</Tags>";
            $out .= "\n";
        }
        if (!empty($this->_strLongName)) {
            $out .= "  ";
            $out .= "<LongName>";
            $out .= $this->_strLongName;
            $out .= "</LongName>";
            $out .= "\n";
        }
        foreach ($this->_arrayImages as $img) {
            $out .= "  ";
            $out .= $img;
            $out .= "\n";
        }
        foreach ($this->_arrayQuery as $query) {
            $out .= "  ";
            $out .= $query;
            $out .= "\n";
        }
        if (!empty($this->_strDeveloper)) {
            $out .= "  ";
            $out .= "<Developer>";
            $out .= $this->_strDeveloper;
            $out .= "</Developer>";
            $out .= "\n";
        }
        if (!empty($this->_strAttribution)) {
            $out .= "  ";
            $out .= "<Attribution>";
            $out .= $this->_strAttribution;
            $out .= "</Attribution>";
            $out .= "\n";
        }
        if (!empty($this->_strSyndicationRight)) {
            $out .= "  ";
            $out .= "<SyndicationRight>";
            $out .= $this->_strSyndicationRight;
            $out .= "</SyndicationRight>";
            $out .= "\n";
        }
        if (!empty($this->_bHasAdultContent) 
            && $this->_bHasAdultContent!==false
        ) {
            $out .= "  ";
            $out .= "<AdultContent>";
            $out .= $this->_bHasAdultContent;
            $out .= "</AdultContent>";
            $out .= "\n";
        }
        
        foreach ($this->_arrayLanguages as $strLang) {
            $out .= "  ";
            $out .= "<Language>";
            $out .= $strLang;
            $out .= "</Language>";
            $out .= "\n";
        }
        
        foreach ($this->_arrayInputEncoding as $ienc) {
            $out .= "  ";
            $out .= "<InputEncoding>";
            $out .= $ienc;
            $out .= "</InputEncoding>";
            $out .= "\n";
        }
        
        foreach ($this->_arrayOutputEncoding as $oenc) {
            $out .= "  ";
            $out .= "<OutputEncoding>";
            $out .= $oenc;
            $out .= "</OutputEncoding>";
            $out .= "\n";
        }
        
        $out .= "</OpenSearchDescription>";
        $out .= "\n";
        
        echo $out;
    }
}
    
?> 