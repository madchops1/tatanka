<?php
/**
 * A tatanka module for google standard site map
 * 
 *
 *
 * To use this module call the generateSiteMap function to create/update your website's map
 * siteMap->generateSiteMap(); // will save 
 */



class siteMap {

	public $output 		= "";									// Output variable
	public $defaultUrl  = "sitemap.xml"; 						// Your map will be located at www.yourdomain.com/sitemap.xml
	public $urls 		= array("home", "about", "contact" );	// You can override the urls variable in your own app's class

	function __construct()
	{

		return true;
	}

	function __destruct()
	{

		return true;
	}

	function generateSiteMap($url)
	{
		$this->output =  '<?xml version="1.0" encoding="UTF-8"?>
						  <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
						  ';

		// Loop through the URLs 
		$urls = $this->getUrls();
		foreach($this->urls as $url) {
			$this->output .= '<url>
						          <loc>http://www.example.com/'.$url.'</loc> 
						  	  </url>';
		}		  

		$this->output .= '
						  </urlset>';

		return $this->output;
	}

	function writeSiteMap($url)
	{
		$map = $this->generateSiteMap($url);
		$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
		$txt = "John Doe\n";
		fwrite($myfile, $txt);
		$txt = "Jane Doe\n";
		fwrite($myfile, $txt);
		fclose($myfile);
	}

	
}