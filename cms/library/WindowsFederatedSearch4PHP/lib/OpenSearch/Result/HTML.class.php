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
 * lib/OpenSearch/Result/HTML.class.php
 * 
 * PHP class used for rendering results in HTML format.
 * 
 * PHP Version 5
 * 
 */

require_once 'lib/OpenSearch/Result.class.php';

/**
 * Renders results in HTML format.
 */
class OpenSearch_Result_HTML extends OpenSearch_Result
{
    /**
     * Constructor
     * 
     * @param string $strTitle          Search title
     * @param string $strDescription    Search description
     * @param string $strStylesheetFile CSS StyleSheet file path
     * 
     * @return Class instance
     */
    public function OpenSearch_Result_HTML(
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
     * @see lib/OpenSearch_Result#printHead()
     * 
     * @return VOID
     */
    protected function printHead()
    {
        $strOut = "";
        header("Content-Type:text/html");
        $strOut .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $strOut .= "\n";
        
        $strOut .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" ";
        $strOut .= "\n";
        $strOut .= "\"http://www.w3.org/TR/html4/strict.dtd\" ";
        $strOut .= ">";
        $strOut .= "\n";
        
        $strOut .= "<html ";
        $strOut .= "xmlns=\"http://www.w3.org/1999/xhtml\" ";
        $strOut .= "xml:lang=\"{$this->strLanguage}\" ";
        $strOut .= "lang=\"{$this->strLanguage}\" ";
        $strOut .= ">";
        $strOut .= "\n";
        
        $strOut .= "  <head profile=\"http://a9.com/-/spec/opensearch/1.1/\" >";
        $strOut .= "\n";
        
        $strOut .= "    ";
        $strOut .= "<title>";
        $strOut .= $this->strTitle;
        $strOut .= "</title>";
        $strOut .= "\n";
        
        $strOut .= "    ";
        $strOut .= "<link ";
        $strOut .= "rel=\"shortcut icon\" "; 
        $strOut .= "href=\"favicon.ico\" ";
        $strOut .= ">";
        $strOut .= "\n";
        
        if (!empty($this->strDescription)) {
            $strOut .= "    ";
            $strOut .= "<link ";
            $strOut .= "rel=\"search\" ";
            $strOut .= "type=\"application/opensearchdescription+xml\" ";
            $strOut .= "href=\"";
            $strOut .= $this->strDescription;
            $strOut .= "\" title=\"";
            $strOut .= $this->strTitle;
            $strOut .= "\"/>";
            $strOut .= "\n";
        }
        
        if ($this->intGetStartIndex>0) {
            $strOut .= "    ";
            $strOut .= "<meta ";
            $strOut .= "name=\"startIndex\" ";
            $strOut .= "content=\"" . $this->intGetStartIndex . "\" ";
            $strOut .= "/>";
            $strOut .= "\n";
        }
        
        if ($this->intGetItemsPerPage>0) {
            $strOut .= "    ";
            $strOut .= "<meta ";
            $strOut .= "name=\"itemsPerPage\" ";
            $strOut .= "content=\"" . $this->intGetItemsPerPage . "\"";
            $strOut .= "/>";
            $strOut .= "\n";
        }
        
        if (!empty($this->strStylesheetFile)) {
            $strOut .= "   ";
            $strOut .= "<link ";
            $strOut .= "rel=\"stylesheet\" ";
            $strOut .= "type=\"text/css\" ";
            $strOut .= "href=\"" . $this->strStylesheetFile . "\" ";
            $strOut .= ">";
            $strOut .= "\n";
        }   
        
        $strOut .= "  </head>" . "\n";
        return $strOut;
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
        $strOut = "";
        $strOut .= "  ";
        $strOut .= "<body>";
        $strOut .= "\n";
        
        $strOut .= "    ";
        $strOut .= "<h1>";
        $strOut .= "<a ";
        $strOut .= "href=\"" . htmlentities($this->strOpenSearchQuery) . "\" ";
        $strOut .= "title=\"" . $this->strTitle . "\" ";
        $strOut .= ">";
        $strOut .= $this->strTitle;
        $strOut .= "</a>";
        $strOut .= "</h1>";
        $strOut .= "\n";
        
        $items = $this->getResultsInPage();
        if (!empty($items)) {
            $strOut .= $this->_printItems($items);
        } else {
        	$strOut .= "<b>No results!</b>";
        }
        
        $strOut .= "  </body>\n";
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
        $strOut  = "";
        $strOut .= "</html>";
        return $strOut;
    }
    
    /**
     * Print items in HTML format.
     * 
     * @param array $items Array of items to be rendered.
     * 
     * @return string HTML segment with items ready for rendering.
     */
    private function _printItems($items)
    {
        $strOut  = "";
        $strOut .= "    ";
        $strOut .= "<dl id=\"result_list\">";
        $strOut .= "\n";
        
        foreach ($items as $item) {
            $strOut .= "      ";
            $strOut .= "<dt>";
            $strOut .= "\n";
            
            $strOut .= "        ";
            $strOut .= "<a ";
            $strOut .= "href=\"" . $item['#link'] . "\" ";
            $strOut .= "title=\"" . $item['#title'] . "\" ";
            $strOut .= ">";
            $strOut .= $item['#title'];
            $strOut .= "</a>";
            $strOut .= "</dt>";
            $strOut .= "\n";
            
            $strOut .= "        ";
            $strOut .= "<dd class=\"desc\">";
            $strOut .= $item['#description'];
            $strOut .= "</dd>";
            $strOut .= "\n";
        }
        
        $strOut .= "    ";
        $strOut .= "</dl>";
        $strOut .= "\n";
        
        return $strOut;
    }
}
?>