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
 * WindowsFederatedSearch_DataSource.class.php
 * 
 * Stock Symbol Search and Parse CSV Quotes for OpenSearch feeds.
 * 
 * PHP Version 5
 * 
 */

require_once 'lib/OpenSearch/Support.inc.php';
require_once 'WindowsFederatedSearch_UserConfig.class.php';

/**
 * Stock Symbol Search and Parse CSV Quotes for OpenSearch feeds.
 */
class WindowsFederatedSearch_DataSource
{
    private $_objResultHandler = null;
    
    /**
     * @access private
     * @var Stock Symbols to search for
     */
    private $_strSearchTerms = null;
	
    /**
     * @access private
     * @var array Stock Symbols
     */
    private $_arrayStockSymbols = array();
    
    
    /**
     * @access private
     * @var array Returned stock quotes
     */
    private $_arrayStockQuoteCSVs;
    
    /**
     * @access private
     * @var array Stock results
     */
    private $_arrayStockQuotes;
    
    /**
     * @var boolean Has proxy address settings
     */
    private $_booleanHasProxy = false;
    
    /**
     * @var string Proxy address.
     */
    private $_strProxy = null;
    
    /**
     * @var string Proxy port
     */
    private $_strProxyPort = null;
    

    /**
     * Constructor
     * 
     * @param object $objResultHandler OpenSearch feed instance
     * @param string $strSearchTerms   Search Stock Symbols
     * 
     * @return VOID
     */
    public function WindowsFederatedSearch_DataSource(
        $objResultHandler,
        $strSearchTerms
    ) {
        if (is_null($objResultHandler) 
            || !is_subclass_of($objResultHandler, "OpenSearch_Result")
        ) {
            die("Error: Results generator not valid!");
        }
        
        $this->_objResultHandler = $objResultHandler;
        
        if (is_null($strSearchTerms) 
            || !is_string($strSearchTerms)
            || empty($strSearchTerms)
        ) {
            die("Error: Search terms is undefined!");
        }
        $this->_strSearchTerms = trim($strSearchTerms);
        
        $strProxy = UserConfigConstants::SERVER_ENDPOINT_PROXY;
        if (!is_null($strProxy)
            && is_string($strProxy)
            && !empty($strProxy) 
        ) {
            $this->_strProxy = $strProxy;
            
            $this->_booleanHasProxy = true;
            
            $strProxyPort = UserConfigConstants::SERVER_ENDPOINT_PROXY_PORT;
            if (!is_null($strProxyPort)
                && is_string($strProxyPort)
            ) {
                $intProxyPort = intval($strProxyPort);
                
                if ($intProxyPort > 0) {
                    $this->_strProxyPort = UserConfigConstants::SERVER_ENDPOINT_PROXY_PORT;
                } else {
                    $this->_strProxyPort = '80';
                }
            } else {
                $this->_strProxyPort = '80';
            }
        }

        
        $this->_arrayStockQuoteCSVs=array();
    }

    /**
     * Build result from Data Source and render OpenSearch feeds.
     * 
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function serve() 
    {
        $booleanStatus = false;
        
        try {
            if (!$this->buildResults()) {
                die( "Error: Failed to build results for feed!");
            }

            if (!$this->_objResultHandler->serve()) {
                die( "Error: Failed to render feed!");
            }

            $bStatus = true;
        }
        catch(Exception $ex)
        {
            die( "Error: " . $ex->getMessage() );
        }
        
        return $bStatus;
    }

    /**
     * addStockSymbol() - Adds a stock symbol to OpenSearch feed.
     * 
     * @param string $strStockSymbol Stock symbol to add
     * 
     * @return boolean Returns TRUE on success or FALSE on failure.
     * 
     * @link http://finance.yahoo.com
     */
    private function _addStockSymbol($strStockSymbol)
    {
        $booleanStatus = false;
        
        if (is_null($strStockSymbol) 
            || !is_string($strStockSymbol) 
            || empty($strStockSymbol)
        ) {
            die( "Error: Invalid stock symbol provided \"{$strStockSymbol}\"!");
        }
        
        $add = strtoupper($strStockSymbol);
        
        if (!in_array($add, $this->_arrayStockSymbols)) {
            $this->_arrayStockSymbols[] = $add;
            if (!sort($this->_arrayStockSymbols, SORT_LOCALE_STRING)) {
                die( "Error: Failed to sort stocks!");
            }
        }
        
        if (!in_array($add, $this->_arrayStockSymbols)) {
            die("Error: Failed to add stock \"{$add}\"!");
        }
        
        $booleanStatus = true;
        
        return $booleanStatus;
    }
    
    /**
     * Remove a stock symbol to OpenSearch feed.
     * 
     * @param string $strStockSymbol Stock symbol to remove.
     * 
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    private function _removeStockSymbol($strStockSymbol)
    {
        $booleanStatus = false;
        
        $find = strtoupper($strStockSymbol);
        
        /*
         * Find key of given value
         */
        $key = array_search($find, $this->_arrayStockSymbols); 
        if ($key != null || $key !== false) {
            /*
             * Remove key from array
             */
            unset($this->_arrayStockSymbols[$key]);

            if (in_array($find, $this->_arrayStockSymbols)) {
                die("Error: Failed to remove stock \"{$find}\"!");
            }
        }
        
        $booleanStatus = true;
        
        return $booleanStatus;
    }
    
    /**
     * Reset stock symbols by removing all symbols 
     * and creating an empty array.
     * 
     * @return VOID
     */
    private function _resetStockSymbols()
    {
        unset($this->_arrayStockSymbols);
        $this->_arrayStockSymbols = array();
    }

    /**
     * Request info using CURL.
     * 
     * @param string $url URL
     * 
     * @return mixed
     */
    private function _curlRequest($url)
    {
        if (!$this->_isValidURL($url)) {
            die("Error: Invalid URL provided: \"{$url}\"!");
        }
        
        $cH = curl_init();

        curl_setopt($cH, CURLOPT_URL, $url);
        curl_setopt($cH, CURL_HTTP_VERSION_1_1, true);
        curl_setopt($cH, CURLOPT_HTTPGET, true);
        curl_setopt($cH, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($cH, CURLOPT_CONNECTTIMEOUT, 60);
        
        if ($this->_booleanHasProxy) {
            curl_setopt($cH, CURLOPT_PROXY, $this->_strProxy);
            curl_setopt($cH, CURLOPT_PROXYPORT, $this->_strProxyPort);
        }

        $response = curl_exec($cH);
        curl_close($cH);
        
        return $response;
    }
    
    /**
     * Validate URL
     * 
     * @param string $url URL
     * 
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    private function _isValidURL($url) 
    {
        if (is_null($url)) {
            return false;
        }
        if (!is_string($url)) {
            return false;
        }
        if (empty($url)) {
            return false;
        }

        return true;
    }
    
    /**
     * Gathers latest stock information.
     * 
     * @return boolean Returns TRUE on success or FALSE on failure.
     * 
     * @link http://www.gummy-stuff.org/Yahoo-data.htm
     */
    private function _getCurrentStockQuotes() 
    {
        if (empty($this->_arrayStockSymbols)) { 
            return false;
        }
        // Building Yahoo Stock Quotes Query
        unset($this->_arrayStockQuoteCSVs);
        $this->_arrayStockQuoteCSVs = array();
        for ($i=0; $i<count($this->_arrayStockSymbols); $i++) {
            $strStockSymbol = $this->_arrayStockSymbols[$i];
            
            $strUrlQuery  = "";
            $strUrlQuery .= "http://download.finance.yahoo.com/d/quotes.csv";
            $strUrlQuery .= "?";
            $strUrlQuery .= "s=".$strStockSymbol;
            $strUrlQuery .= "&";
            $strUrlQuery .= "f=snl1d1t1c1ohgv";
            $strUrlQuery .= "&";
            $strUrlQuery .= "e=.csv";
            if (!$this->_isValidURL($strUrlQuery)) {
                die("Error: Invalid URL created: \"{$strUrlQuery}\"!");
            }
            
            $response = $this->_curlRequest($strUrlQuery);
            
            if ($response) {
                array_push($this->_arrayStockQuoteCSVs, $response);
            }
        }
        if (empty($this->_arrayStockQuoteCSVs)) { 
            return false;
        }   
        unset($this->_arrayStockQuotes);
        $this->_arrayStockQuotes = array(); 
        foreach ($this->_arrayStockQuoteCSVs as $csvStockQuote) {
            $arrayStockQuoteContent 
                = str_replace("\"", "", $csvStockQuote);
            $arrayStockQuoteContent 
                = explode(",", $arrayStockQuoteContent);
            
            $arrayStockQuoteDetails=array();
            $arrayStockQuoteDetails['#symbol'] 
                = trim($arrayStockQuoteContent[0]);
            $arrayStockQuoteDetails['#name'] 
                = trim($arrayStockQuoteContent[1]);
            $arrayStockQuoteDetails['#last_price'] 
                = trim($arrayStockQuoteContent[2]);
            $arrayStockQuoteDetails['#date'] 
                = trim($arrayStockQuoteContent[3]);
            $arrayStockQuoteDetails['#time'] 
                = trim($arrayStockQuoteContent[4]);
            $arrayStockQuoteDetails['#change'] 
                = trim($arrayStockQuoteContent[5]);
            $arrayStockQuoteDetails['#open'] 
                = trim($arrayStockQuoteContent[6]);
            $arrayStockQuoteDetails['#high'] 
                = trim($arrayStockQuoteContent[7]);
            $arrayStockQuoteDetails['#low'] 
                = trim($arrayStockQuoteContent[8]);
            $arrayStockQuoteDetails['#volume'] 
                = trim($arrayStockQuoteContent[9]);
            
            $this->_arrayStockQuotes[] = $arrayStockQuoteDetails;
        }
        return true;
    }
    
    /**
     * Build up results and provide to OpenSearch endpoint.
     * 
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    protected function buildResults()
    {
        $this->_arrayStockSymbols = array();

        $strStockQuotesRequest = $this->_strSearchTerms;
        if (empty($strStockQuotesRequest)) {
            die( "Error: provided not stock quotes to lookup!");
        }
        
        /*
         * Remove delimiters
         */
        $strStockQuotesRequest = preg_replace('[,|;|\.|\+|\-]', ' ', $strStockQuotesRequest);

        /*
         * Remove extra spaces
         */
        $strStockQuotesRequest = preg_replace('/\s\s+/', ' ', $strStockQuotesRequest);

        /*
         * Gather stock symbols
         */
        $arrayStockQuotesRequest = explode(' ', $strStockQuotesRequest);
        
        foreach ($arrayStockQuotesRequest as $strStockSymbol) {
            $this->_addStockSymbol($strStockSymbol);
        }

        /*
         * add results to the feed.
         */
        if ( $this->_getCurrentStockQuotes() ) {
            if (empty($this->_arrayStockQuotes)) { 
                return false; 
            }
            foreach ($this->_arrayStockQuotes as $arrayQuoteInfo) {
                $strItemTitle  = $arrayQuoteInfo['#name'];
                $strItemTitle .= " ({$arrayQuoteInfo['#symbol']})";

                $strItemLink = "http://finance.yahoo.com/q?s={$arrayQuoteInfo['#symbol']}";

                $arrayQuoteInfoDetails = array_slice($arrayQuoteInfo, 2);

                $tmp = array();
                foreach ($arrayQuoteInfoDetails as $k => $v) { 
                    $k = ltrim($k, "#");
                    $tmp[] = "{$k} = \"{$v}\"";
                }
                $strItemDescription = implode(", ", $tmp);

                $arrayItemCategories = array();
                $category = array();
                $category['name']='stock';
                $arrayItemCategories[] = $category;
                $category = array();
                $category['name']='quote';
                $arrayItemCategories[] = $category;

                $arrayItemImageInfo = null;

                /*
                * Adding the feed here.
                */
                $this->_objResultHandler->addItem(
                    $strItemTitle,
                    $strItemDescription,
                    $strItemLink, 
                    "Windows Federated Search's Stock Quote Service",
                    null,
                    $arrayItemCategories,
                    $arrayItemImageInfo
                );
            }
        } else {
            die( "Error: No stock results returned!");
        }
        return true;
    }
}
?>