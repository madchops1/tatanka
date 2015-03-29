<?php
/**
 * Maintenance module for tatanka
 */
class maintenance 
{
	// $app container
	public $app;

	// Options
	public $down                = false;		// Put the site down, uses the down.php template
  	public $downAccessIps       = array();		// Array of ips that can access the site when it is down
  	public $downPage 			= 'down';


  	function __construct($app) 
  	{
  		// Most importantly, app first...
  		$this->app = $app;

  		// Set options
  		if(isset($app->down)) 				$this->down 			= $app->down;
  		if(isset($app->downAccessIps)) 		$this->downAccessIps 	= $app->downAccessIps;
  		if(isset($app->downPage))	 		$this->downPage 		= $app->downPage;
  	}

  	function processRequest() 
  	{

	    // Down Page Handling, this can become a module too...
	    if($this->down == true && $this->app->page != $this->downPage) {
	    	$yourIp = helper::getUserIp();
	    	if(!in_array($yourIp, $this->downAccessIps)){
	        	header("Location: /".$this->downPage."");
	        	die;
	      	}
    	} 
    	return true;
  	}
}