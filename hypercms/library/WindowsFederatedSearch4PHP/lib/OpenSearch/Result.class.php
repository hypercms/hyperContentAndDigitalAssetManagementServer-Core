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
 * lib/OpenSearch/Result.class.php
 * 
 * Abstract base PHP class used for building 
 * and rendering Open Search results.
 * 
 * PHP Version 5
 * 
 */

require_once 'lib/OpenSearch/Support.inc.php';

/**
 * Base class for handling search requests.
 */
abstract class OpenSearch_Result
{
    protected $strGetQuery;
    protected $strGetFormat;
    protected $strGetSource;
    protected $intGetStartIndex;
    protected $intGetItemsPerPage;
    protected $strTitle;
    protected $strDescription;
    protected $strLanguage              = 'en' ;
    protected $arrayResultsAll          = array();
    protected $strOpenSearchQuery             = "";
    protected $strStylesheetFile        = null;
    protected $strEncoding              = "UTF-8";
    protected $strUrlPath               = "";

    /**
     * Choose a OpenSearch results generator.
     * 
     * @return OpenSearch_Result subclass
     */
    public static function createResultGenerator()
    {
        $objOpenSearchResult = null;
        
        $format = ServiceConstants::FORMAT_HTML;
        if (isset($_GET['format'])) {
            $format=$_GET['format'];
        }
        
        switch ($format) {
        case ServiceConstants::FORMAT_HTML:
        default:
            include_once 'lib/OpenSearch/Result/HTML.class.php';
            
            $objOpenSearchResult 
                = new OpenSearch_Result_HTML (
                    UserConfigConstants::OPEN_SEARCH_SHORT_NAME . ": HTML Output",
                    UserConfigConstants::OPEN_SEARCH_DESCRIPTION,
                    UserConfigConstants::FILE_HTML_STYLESHEET
                );
            break;
            
        case ServiceConstants::FORMAT_RSS:
            include_once 'lib/OpenSearch/Result/RSS.class.php';
            
            $objOpenSearchResult 
                = new OpenSearch_Result_RSS (
                    UserConfigConstants::OPEN_SEARCH_SHORT_NAME . ": RSS-2 Output",
                    UserConfigConstants::OPEN_SEARCH_DESCRIPTION,
                    UserConfigConstants::FILE_RSS_STYLESHEET
                );
            break;
             
        default:
            die("Error: Unexpected format chosen for results: \"{$format}\"!");
        }
        
        if (is_null($objOpenSearchResult) 
            || !is_subclass_of($objOpenSearchResult, "OpenSearch_Result")
        ) {
            die("Error: Results generator not chosen!");
        }
        
        return $objOpenSearchResult;
    }

    /**
     * Constructor
     * 
     * @param string $strTitle          Search title
     * @param string $strDescription    Search description
     * @param string $strStylesheetFile StyleSheet file path
     * 
     * @return
     */
    protected function OpenSearch_Result (
        $strTitle,
        $strDescription,
        $strStylesheetFile = null
    ) {
        $this->strGetQuery = $_GET['query'];

        $this->strGetFormat = ServiceConstants::FORMAT_HTML;
        if (isset($_GET['format'])) {
            $this->strGetFormat=$_GET['format'];
        }

        $this->strGetSource = ServiceConstants::SRC_IE_SEARCHBOX;
        if (isset($_GET['src'])) {
            $this->strGetSource=$_GET['src'];
        }

        $this->intGetStartIndex=0;
        if (isset($_GET['start'])) {
            $this->intGetStartIndex=$_GET['start'];
        }

        $this->intGetItemsPerPage=0;
        if (isset($_GET['cnt'])) {
            $this->intGetItemsPerPage=$_GET['cnt'];
        }
        
        $this->strTitle              = $strTitle;
        $this->strDescription        = $strDescription;
        $this->strStylesheetFile     = $strStylesheetFile;
        
        $strProtocol    = ServiceConstants::PROTOCOL_HTTP;
        
        $strHost        = ServiceConstants::SERVER_NAME;
        if (isset($_SERVER['SERVER_NAME'])) {
            $strHost=$_SERVER['SERVER_NAME'];
        }
        
        if (isset($_SERVER['SERVER_PORT'])) {
            $intPort=$_SERVER['SERVER_PORT'];
        }
        $intPort = ($intPort != 80) ? ":{$intPort}" : "";
        
        if (isset($_SERVER['PHP_SELF'])) {
            $strPath=dirname($_SERVER['PHP_SELF']);
        }
        
        $strUrlPath = $strProtocol.$strHost.$intPort.$strPath."/";
        $this->strUrlPath = $strUrlPath;
        
        $strOpenSearchQuery  = $strUrlPath;
        $strOpenSearchQuery .= "search.php";
        $strOpenSearchQuery .= "?";
        $strOpenSearchQuery .= "query=".implode('+', explode(' ', $this->strGetQuery));
        $strOpenSearchQuery .= "&";
        $strOpenSearchQuery .= "format=".$this->strGetFormat;
        
        $this->strOpenSearchQuery=$strOpenSearchQuery;
    }

    /**
     * Add item to OpenSearch feed.
     * 
     * @param string $strItemTitle        Title
     * @param string $strItemDesc         Description
     * @param string $strItemLinkURL      Resouce link
     * @param string $strItemAuthor       Author
     * @param string $strItemPubDate      Publish date
     * @param array  $arrayItemCategories Categories<name, domain>
     * @param array  $arrayItemImageInfo  Image
     * 
     * @return VOID
     */
    public function addItem(
        $strItemTitle = "",
        $strItemDesc = "",
        $strItemLinkURL = null,
        $strItemAuthor = null,
        $strItemPubDate = null,
        $arrayItemCategories = null,
        $arrayItemImageInfo = null
    ) {
        $item = array();
        $item['#title'] = $strItemTitle;
        $item['#description'] = $strItemDesc;
        $item['#link'] = $strItemLinkURL;
        $item['#author'] = $strItemAuthor;
        
        date_default_timezone_set('America/Los_Angeles');
        $item['#pub_date'] = $strItemPubDate ? $strItemPubDate : date('r');
        
        $item['#categories'] = $arrayItemCategories;
        

        if ($arrayItemImageInfo) {
            $item['#media_content_url'] 
                = $arrayItemImageInfo['#media_content_url'];
            $item['#media_content_filesize'] 
                = $arrayItemImageInfo['#media_content_filesize'];
            $item['media_content_type'] = "image/jpeg";
            $item['media_content_medium'] = "image";
            $item['#media_content_width'] 
                = $arrayItemImageInfo['#media_content_width'];
            $item['#media_content_height'] 
                = $arrayItemImageInfo['#media_content_height'];
            
            $item['#media_hash'] = $arrayItemImageInfo['#media_hash'];
            $item['#media_hash_algo'] = "md5";
        }
        
        $this->arrayResultsAll[]=$item;
    } 
    
    /**
     * Render results generated.
     * 
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function serve()
    {
        $status = false;
        
        try {
            $strOut = "";
            $strOut .= $this->printHead();
            $strOut .= $this->printBody();
            $strOut .= $this->printTail();

            echo $strOut;
            $status = true;
        }
        catch(Exception $ex)
        {
            die( "Error: Exception: " . $ex->getMessage() );
        }
        
        return $status;
    }
    
    /**
     * Generate results based upon URL provided 
     * {start_index} and {items_per_page}.
     * 
     * @return array
     */
    protected function getResultsInPage()
    {
        $arrayResultsInPage = null;
        if ($this->intGetStartIndex > 0) {
            if ($this->intGetItemsPerPage > 0) {
                
                $intLengthIndexBased  = $this->intGetStartIndex;
                $intLengthIndexBased += $this->intGetItemsPerPage;
                $intLengthIndexBased += -1;
  
                $intLengthAllResults = count($this->arrayResultsAll);
                $max = min($intLengthIndexBased, $intLengthAllResults);
                $intLengthItemsInPage = ($max - $this->intGetStartIndex) + 1;
                
                $arrayResultsInPage = array_splice(
                    $this->arrayResultsAll,
                    $this->intGetStartIndex - 1,
                    $intLengthItemsInPage
                );
            } else {
                $arrayResultsInPage = array_splice(
                    $this->arrayResultsAll,
                    $this->intGetStartIndex - 1
                );
            }
        } else if ($this->intGetItemsPerPage > 0) {
            $length = min($this->intGetItemsPerPage, count($this->arrayResultsAll));
            $arrayResultsInPage = array_splice($this->arrayResultsAll, 0, $length);
        } else {
            $arrayResultsInPage = $this->arrayResultsAll;
        }
        
        return $arrayResultsInPage;
    }
    
    /**
     * Render head part of OpenSearch feed.
     * 
     * @return string Head part
     */
    abstract protected function printHead();
    
    /**
     * Render body information (results) for OpenSearch feed.
     * 
     * @return string Results for OpenSearch feed.
     */
    abstract protected function printBody();
    
    /**
     * Render tail part for OpenSearch feed.
     * 
     * @return string Tail part
     */
    abstract protected function printTail();
    
}
?>