<?php
/**
 * Admin module for tatanka
 */
class admin 
{
	// $app container
	public 		$app;
  	protected 	$dependencies       = array('user');    // module dependencies, user required for login

	// Options
	public $admin 					= false; 			// true if we are in the admin
	public $layout					= ''; 				// the layout of the admin
    public $adminPage 				= 'admin'; 			// default admin url
    public $pagePart				= 2;				// default admin page part
    public $loginPage	  			= 'login'; 	    	// default login is equal to admin/login here
    public $restrictedPages 		= array('home');	// restricted admin pages
    public $adminRole				= 'admin';			// default admin role.
    public $defaultPage 			= 'dashboard';		// the default admin page

    /**
     * Constructor
     */
	function __construct($app) 
	{
		// Set the app
	    $this->app = $app;     

	    // include dependencies
    	$this->app->includeDependencies($this->dependencies);       

	    // set the options
    	if(isset($app->adminLayout))         	$this->layout           = $app->adminLayout;
    	if(isset($app->adminPage))           	$this->adminPage     	= $app->adminPage;
    	if(isset($app->adminPagePart))       	$this->pagePart         = $app->adminPagePart;
    	if(isset($app->adminLoginPage))		 	$this->loginPage 	    = $app->adminLoginPage;
    	if(isset($app->adminRestrictedPages))	$this->restrictedPages 	= $app->adminRestrictedPages;
    	if(isset($app->defaultAdminPage))		$this->defaultPage 		= $app->defaultAdminPage;
	}

	/**
	 * processRequest hook
	 */
	function processRequest()
	{
		// Admin Url Handling, yes I put this here for now, will probably make it app specific or a module
	    if($this->app->page == $this->adminPage) {
	    	// Set this admin to true
	    	$this->admin = true; 
	    	// Set the adminPagePart in the urlArray if it does not exist
	    	if(!isset($this->app->urlArray[$this->pagePart]) || $this->app->urlArray[$this->pagePart] == "") { $this->app->urlArray[$this->pagePart] = $this->defaultPage; }
	      	// set the app page to the admin page part
	      	$this->app->page = $this->app->urlArray[$this->pagePart];
	      	// set the layout to the admin layout
	      	$this->app->layout = $this->layout;
	      	// Unset the admin page part from the url array
	      	unset($this->app->urlArray[$this->pagePart]);
	      	return true;
	    }
	}

	/**
	 * getRouter hook
	 */
	function getRouter()
	{
		if($this->admin) {
			// validate the admin role on pages that are not login
			if($this->app->page != $this->loginPage) {
				$_SESSION['user']->requiredRoles = array($this->adminRole);
				if(!$_SESSION['user']->validateRoles()) {
					// redirect to the admin login page
					header("LOCATION: /".$this->adminPage."/".$this->loginPage."");
					die;
				}
			}
		}
		return true;
	}
}
?>