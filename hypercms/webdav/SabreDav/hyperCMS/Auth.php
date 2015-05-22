<?php
class Sabre_hyperCMS_Auth extends Sabre_DAV_ServerPlugin 
{
	/**
	* Reference to main server object
	*
	* @var Sabre_DAV_Server
	*/
	private $_server;
	
	/**
	 * Folder where the usersessions are stored
	 * 
	 * @var String
	 */
	private $_sessionFolder;
	
	/**
	 * Contains the functions
	 * @var Sabre_hyperCMS_Functions
	 */
	private $_functions;
	
	private $_realm;
	
	public function __construct(Sabre_hyperCMS_Functions &$functions, $realm)
  {
		$this->_functions = &$functions;
		$this->_realm = $realm;
	}
	
	/**
	 * Authenticates the user based on the current request.
	 *
	 * If authentication is succesful, true must be returned.
	 * If authentication fails, an exception must be thrown.
	 *
	 * @throws Sabre_DAV_Exception_NotAuthenticated
	 * @return bool
	 */
	public function authenticate()
  {
		$sess_id = session_id();
		/*if(array_key_exists('user', $_SESSION) && array_key_exists('passwd', $_SESSION) && $this->_getFunctions()->isLoggedInCmsUser($_SESSION['hcms_user'], $_SESSION['hcms_passwd'], $sess_id)) {
			// Setting user globals for our functions
			$this->_getFunctions()->setGlobal('siteaccess', $_SESSION['hcms_siteaccess']);
			$this->_getFunctions()->setGlobal('pageaccess', $_SESSION['hcms_pageaccess']);
			$this->_getFunctions()->setGlobal('compaccess', $_SESSION['hcms_compaccess']);
			$this->_getFunctions()->setGlobal('globalpermission', $_SESSION['hcms_globalpermission']);
			$this->_getFunctions()->setGlobal('hiddenfolder', $_SESSION['hcms_hiddenfolder']);
			$this->_getFunctions()->setGlobal('localpermission', $_SESSION['hcms_localpermission']);
			$this->_getFunctions()->setGlobal('user', $_SESSION['hcms_user']);
			$this->_getFunctions()->setGlobal('eventsystem', $_SESSION['eventsystem']);
			$this->_getFunctions()->setGlobal('lang', $_SESSION['hcms_lang']);
			
			return true;
		}*/
		
		$digest = new Sabre_HTTP_DigestAuth();
	
		// Hooking up request and response objects
		$digest->setHTTPRequest($this->server->httpRequest);
		$digest->setHTTPResponse($this->server->httpResponse);
	
		$digest->setRealm($this->_realm);
		$digest->init();
	
		$username = $digest->getUsername();
		
		// no username was given
		if (!$username)
    {
			$digest->requireLogin();
			throw new Sabre_DAV_Exception_NotAuthenticated('No digest authentication headers were found');
		}

		// user is already logged in, fetch data from file
		if($this->_getFunctions()->isLoggedInHyperdav($username))
    {
			$this->_getFunctions()->readSessionDataFromFile($username);
		}
    // user is not logged in, reading data and saving to file
    else
    {
			$this->_getFunctions()->writeSessionDataToFile($username);
		}
		
		$hash = $this->_getFunctions()->getGlobal('userhash');
		
		// if this was false, the user account didn't exist
		if ($hash===false || is_null($hash))
    {
			$digest->requireLogin();
			throw new Sabre_DAV_Exception_NotAuthenticated('The supplied username has no Hash');
		}
    
		if (!is_string($hash))
    {
			throw new Sabre_DAV_Exception('Error in building Digest Hash');
		}
		// if this was false, the password or part of the hash was incorrect.
		if (!$digest->validateA1($hash))
    {
			//$digest->requireLogin();
			throw new Sabre_DAV_Exception_NotAuthenticated('Incorrect username');
		}
		
		return true;
	}
	
	/**
	 * Initializes the plugin. This function is automatically called by the server
	 *
	 * @param Sabre_DAV_Server $server
	 * @return void
	 */
	public function initialize(Sabre_DAV_Server $server)
  {
		$this->server = $server;
		$this->server->subscribeEvent('beforeMethod',array($this,'beforeMethod'),10);
	}
	
	/**
	 * This method is called before any HTTP method and forces users to be authenticated
	 *
	 * @param string $method
	 * @throws Sabre_DAV_Exception_NotAuthenticated
	 * @return bool
	 */
	public function beforeMethod($method, $uri)
  {
		return $this->authenticate();
	}
	
	/**
	 * Returns a plugin name.
	 *
	 * Using this name other plugins will be able to access other plugins
	 * using Sabre_DAV_Server::getPlugin
	 *
	 * @return string
	 */
	public function getPluginName()
  {
		return 'hyperCMS_auth';
	}
	
	protected function &_getFunctions()
  {
		return $this->_functions;
	}
}
?>