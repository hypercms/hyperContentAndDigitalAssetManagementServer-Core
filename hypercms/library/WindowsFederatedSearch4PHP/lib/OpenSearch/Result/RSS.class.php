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
 * lib/OpenSearch/Result/RSS.class.php
 * 
 * PHP class used for rendering feed in RSS 2.0 format
 * 
 * PHP Version 5
 * 
 */

require_once 'lib/OpenSearch/Result.class.php';
require_once 'lib/OpenSearch/ServiceConstants.class.php';

/**
 * Renders results in RSS XML 2.0 format.
 */
class OpenSearch_Result_RSS extends OpenSearch_Result
{
    
    /**
     * Constructor
     * 
     * @param string $strTitle          Search title
     * @param string $strDescription    Search description
     * @param string $strStylesheetFile XSLT StyleSheet file path
     * 
     * @return VOID
     */
    public function OpenSearch_Result_RSS(
        $strTitle,
        $strDescription,
        $strStylesheetFile=null
    ) {
        parent::OpenSearch_Result(
            $strTitle,
            $strDescription,
            $strStylesheetFile
        );
    }

    /**
     * Render head part of OpenSearch feed.
     * 
     * @see lib/OpenSearch_Result#_printHead()
     * 
     * @return VOID
     */
    protected function printHead()
    {
        $strOut = "";   
        if (!empty($this->strStylesheet) 
            && ($this->strGetSource != ServiceConstants::SRC_WINDOWS_7_EXPLORER)
        ) {
            $strOut .= "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>";
            $strOut .= "\n";
            
            $strOut .= "<?xml-stylesheet href=\"";
            $strOut .= $this->strStylesheet;
            $strOut .= "\" type=\"text/xsl\" ?>";
            $strOut .= "\n";
        }
               
        $strOut .= "<rss version=\"2.0\"";
        $strOut .= " xmlns:content=\"http://purl.org/rss/1.0/modules/content/\"";
        $strOut .= " xmlns:wfw=\"http://wellformedweb.org/CommentAPI/\"";
        $strOut .= " xmlns:media=\"http://search.yahoo.com/mrss/\"";
        $strOut .= " xmlns:exif=\"http://www.exif.org/specifications.html\"";
        $strOut .= ">\n";
              
        return $strOut;
    }
    
    
    /**
     * Render tail part for OpenSearch feed.
     * 
     * @see lib/OpenSearch_Result#printTail()
     * 
     * @return VOID
     */
    protected function printTail()
    {
        return "</channel>\n</rss>\n";
    }
    
    
    /**
     * Render body information (results) for OpenSearch feed.
     * 
     * @see lib/OpenSearch_Result#printBody()
     * 
     * @return VOID
     */
    protected function printBody()
    {               
        $rss_channel = new RSS_Channel();
        $rss_channel->strTitle = $this->strTitle;
        $rss_channel->strLink = htmlentities($this->strOpenSearchQuery);
        $rss_channel->strDescription = $this->strDescription;
        
        $strOut = "";
        $strOut .= $this->_printChannel($rss_channel);
        
        $arrayAllItemResultsInPage = $this->getResultsInPage();
        
        $aRssItems = array();
        foreach ($arrayAllItemResultsInPage as $arrayItemResults) {
            $rss_item = new RSS_Item();
            $rss_item->strTitle = $arrayItemResults['#title'];
            $rss_item->strDescription = $arrayItemResults['#description'];
            $rss_item->strLink = $arrayItemResults['#link'];
            $rss_item->strAuthor = $arrayItemResults['#author'];
            $rss_item->strPubDate = $arrayItemResults['#pub_date'];
            $rss_item->arrayCategories = $arrayItemResults['#categories'];
            
            if (isset($arrayItemResults['#media_content_url'])) {
                $rss_item->strMediaContentUrl 
                    = $arrayItemResults['#media_content_url'];
                $rss_item->intMediaContentFileSize 
                    = $arrayItemResults['#media_content_filesize'];
                $rss_item->strMediaContentType 
                    = $arrayItemResults['#media_content_type'];
                $rss_item->strMediaContentMedium 
                    = $arrayItemResults['#media_content_medium'];
                $rss_item->intMediaContentWidth 
                    = $arrayItemResults['#media_content_width'];
                $rss_item->intMediaContentHeight 
                    = $arrayItemResults['#media_content_eight'];
                
                $rss_item->binaryMediaHash 
                    = $arrayItemResults['#media_hash'];
                $rss_item->strMediaHashAlgo 
                    = $arrayItemResults['#media_hash_algo'];
            }
            
            $aRssItems[] = $rss_item;
        }
        
        $strOut .= $this->_printItems($aRssItems);
        
        return $strOut;
    }
    
    
    /**
     * Put together RSS channel information for rendering within RSS feed.
     * 
     * @param string $channel Channel properties
     * 
     * @return string Ready to be rendered RSS channel
     */
    private function _printChannel($channel)
    {
        $selfUrl = ServiceConstants::PROTOCOL_HTTP;
        $selfUrl .= $_SERVER["HTTP_HOST"];
        $selfUrl .= $_SERVER["PHP_SELF"];
        
        $strOut  = "";
        $strOut .= "  <channel>\n";
        
        $strOut .= "    <title>";
        $strOut .= $channel->strTitle;
        $strOut .= "</title>\n";
        
        $strOut .= "    <link>";
        $strOut .= $channel->strLink;
        $strOut .= "</link>\n";
        
        $strOut .= "    <description>";
        $strOut .= $channel->strDescription;
        $strOut .= "</description>\n";
        
        if (!empty($channel->strLanguage)) {
            $strOut .= "    <language>";
            $strOut .= $channel->strLanguage;
            $strOut .= "</language>\n";
        }
        if (!empty($channel->strCopyright)) {
            $strOut .= "    <copyright>";
            $strOut .= $channel->strCopyright;
            $strOut .= "</copyright>\n";
        }
        if (!empty($channel->strPubDate)) {
            $strOut .= "    <pubDate>";
            $strOut .= $channel->strPubDate;
            $strOut .= "</pubDate>\n";
        }
        if (!empty($channel->strLastBuildDate)) {
            $strOut .= "    ";
            $strOut .= "<lastBuildDate>";
            $strOut .= $channel->strLastBuildDate;
            $strOut .= "</lastBuildDate>";
            $strOut .= "\n";
        }
        foreach ($channel->arrayCategories as $category) {
            $strOut .= "    ";
            $strOut .= "<category";
            if (!empty($category["domain"])) {
                $strOut .= "  domain=\"";
                $strOut .= $category["domain"];
                $strOut .= "\"";
            }
            $strOut .= ">";
            $strOut .= $category["name"];
            $strOut .= "</category>\n";
        }
        if (!empty($channel->strGenerator)) {
            $strOut .= "    <generator>";
            $strOut .= $channel->strGenerator;
            $strOut .= "</generator>\n";
        }
        if (!empty($channel->strImage)) {
            $strImage = $channel->strImage;
            $strOut .= "    <strImage>\n";          
            $strOut .= "      <url>";
            $strOut .= $strImage->strUrl;
            $strOut .= "</url>\n";        
            $strOut .= "      <title>";
            $strOut .= $strImage->strTitle;
            $strOut .= "</title>\n";         
            $strOut .= "      <link>";
            $strOut .= $strImage->strLink;
            $strOut .= "</link>\n";         
            if ($strImage->intWidth) {
                $strOut .= "      <intWidth>";
                $strOut .= $strImage->intWidth;
                $strOut .= "</intWidth>\n";
            }
            if ($strImage->height) {
                $strOut .= "      <height>";
                $strOut .= $strImage->height;
                $strOut .= "</height>\n";
            }
            if (!empty($strImage->strDescription)) {
                $strOut .= "      <description>";
                $strOut .= $strImage->strDescription;
                $strOut .= "</description>\n";
            }
            $strOut .= "    </strImage>\n";
        }
        if (!empty($channel->strTextInput)) {
            $strTextInput = $channel->strTextInput;
            $strOut .= "    <textInput>\n";          
            $strOut .= "      <title>";
            $strOut .= $strTextInput->strTitle;
            $strOut .= "</title>\n";          
            $strOut .= "      <description>";
            $strOut .= $strTextInput->strDescription;
            $strOut .= "</description>\n";         
            $strOut .= "      <name>";
            $strOut .= $strTextInput->name;
            $strOut .= "</name>\n";       
            $strOut .= "      <link>";
            $strOut .= $strTextInput->strLink;
            $strOut .= "</link>\n";           
            $strOut .= "    </textInput>\n";
        }
        return $strOut;
    }
    
    /**
     * Put together RSS channel items for rendering within RSS feed.
     * 
     * @param string $aRssItems Array of RSS item properties
     * 
     * @return string Ready to be rendered RSS channel items
     */
    private function _printItems($aRssItems)
    {
        $strOut  = "";
        foreach ($aRssItems as $rssItem) {
            $strOut .= "    <item>\n";
            if (!empty($rssItem->strTitle)) {
                $strOut .= "      <title>";
                $strOut .= $rssItem->strTitle;
                $strOut .= "</title>\n";
            }
            if (!empty($rssItem->strDescription)) {
                $strOut .= "      <description>";
                $strOut .= $rssItem->strDescription;
                $strOut .= "</description>\n";
            }
            if (!empty($rssItem->strLink)) {
                $strOut .= "      <link>";
                $strOut .= $rssItem->strLink;
                $strOut .= "</link>\n";
            }           
            if (!empty($rssItem->strPubDate)) {
                $strOut .= "      <pubDate>";
                $strOut .= $rssItem->strPubDate;
                $strOut .= "</pubDate>\n";
            }
            if (!empty($rssItem->strAuthor)) {
                $strOut .= "      <author>";
                $strOut .= $rssItem->strAuthor;
                $strOut .= "</author>\n";
            }
            if (!empty($rssItem->strComments)) {
                $strOut .= "      <comments>";
                $strOut .= $rssItem->strComments;
                $strOut .= "</comments>\n";
            }
            if (!empty($rssItem->guidID)) {
                $strOut .= "      <guid  isPermaLink=\"";
                $strOut .= ($rssItem->booleanGuidIdIsPermaLink ? "true" : "false");
                $strOut .= "\">";
                $strOut .= $rssItem->guidID;
                $strOut .= "</guid>\n";
            }
            if (!empty($rssItem->strSource)) {
                $strOut .= "      <source  url=\"";
                $strOut .= $rssItem->strSourceUrl;
                $strOut .= "\">";
                $strOut .= $rssItem->strSource;
                $strOut .= "</source>\n";
            }
            if (!empty($rssItem->strEnclosureUrl) 
                || !empty($rssItem->strEnclosureType)
            ) {
                $strOut .= "      <enclosure url=\"";
                $strOut .= $rssItem->strEnclosureUrl;
                $strOut .= "\" length=\"";
                $strOut .= $rssItem->intEnclosureLength;
                $strOut .= "\" type=\"";
                $strOut .= $rssItem->strEnclosureType;
                $strOut .= "\" />\n";
            }
            if ($rssItem->arrayCategories
                && is_array($rssItem->arrayCategories)
            ) {
                foreach ($rssItem->arrayCategories as $category) {
                    $strOut .= "      <category";
                    if (!empty($category["domain"])) {
                        $strOut .= "  domain=\"";
                        $strOut .= $category["domain"];
                        $strOut .= "\" ";
                    }
                    $strOut .= ">";
                    $strOut .= $category["name"];
                    $strOut .= "</category>\n";
                }
            }           
            if (!empty($rssItem->strMediaContentUrl)) {
                $strOut .= "      <media:content  url=\"";
                $strOut .= $rssItem->strMediaContentUrl;
                $strOut .= "\"  fileSize=\"";
                $strOut .= $rssItem->intMediaContentFileSize;
                $strOut .= "\"  type=\"";
                $strOut .= $rssItem->strMediaContentType;
                $strOut .= "\"  medium=\"";
                $strOut .= $rssItem->strMediaContentMedium;
                $strOut .= "\"  intWidth=\"";
                $strOut .= $rssItem->intMediaContentWidth;
                $strOut .= "\"  height=\"";
                $strOut .= $rssItem->intMediaContentHeight;
                $strOut .= "\"  >";
                if (!empty($rssItem->binaryMediaHash)) {
                    $strOut .= "        <media:hash algo=\"";
                    $strOut .= $rssItem->strMediaHashAlgo;
                    $strOut .= "\"  >";
                    $strOut .= $rssItem->binaryMediaHash;
                    $strOut .= "</media:hash>\n";
                }
                $strOut .= "</media:content>\n";
            }
            $strOut .= "     </item>\n";
        }  
        return $strOut;
    }
}

/**
 * Used for buiding RSS channel.
 */
class RSS_Channel
{
    var $atomLinkHref = "";
    var $strTitle = "";
    var $strLink = "";
    var $strDescription = "";
    var $strLanguage = "";
    var $strCopyright = "";
    var $strPubDate = "";
    var $strLastBuildDate = "";
    var $arrayCategories = array();
    var $strGenerator = "";
    var $strImage = "";
    var $strTextInput = "";
}

/**
 * Used for buiding RSS item.
 */
class RSS_Item
{
    var $strTitle = "";
    var $strDescription = "";
    var $strLink = "";
    var $strAuthor = "";
    var $strPubDate = "";
    var $strComments = "";
    var $guidID = "";
    var $booleanGuidIdIsPermaLink = true;
    var $strSource = "";
    var $strSourceUrl = "";
    var $strEnclosureUrl = "";
    var $intEnclosureLength = "0";
    var $strEnclosureType = "";
    var $arrayCategories = array();
    
    var $strMediaContentUrl = "";
    var $intMediaContentFileSize = "";
    var $strMediaContentType = "";
    var $strMediaContentMedium = "";
    var $intMediaContentWidth = "";
    var $intMediaContentHeight = "";

    var $binaryMediaHash = "";
    var $strMediaHashAlgo = "";
}
?>