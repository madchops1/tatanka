<?php
/**
 * A static tatanka module for google standard site map
 * designed for the arrow command line.
 */


class siteMap {

	public static $siteMapUrls 		= array();
	public static $siteMapDomain 	= '';

	// Arrow function
	public static function rebuildSiteMap($domain)
	{	
		self::$siteMapDomain = $domain;
		siteMap::writeSiteMap();
		return true;
	}

	// Generate Sitemap
	public static function generateSiteMap()
	{
		siteMap::scan(self::$siteMapDomain);

		$output =  '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

		// Loop through the URLs 
		foreach(self::$siteMapUrls as $url) {
			$output .= '
	<url>
		<loc>http://'.self::$siteMapDomain.'/'.$url.'</loc> 
	</url>';
		}		  

		$output .= '
</urlset>';
		return $output;
	}

	// Write Sitemap
	public static function writeSiteMap()
	{
		// Generate the map
		$map = siteMap::generateSiteMap(self::$siteMapDomain);
		$siteMapFile = fopen("sitemap.xml", "w") or die("Unable to open sitemap file!\n");
		
		// Write the file
		//$map = preg_replace('/\s+/', '', $map);
		fwrite ($siteMapFile,"");
		fwrite ($siteMapFile,$map);

		// Close the file
		fclose($siteMapFile);
	}

	public static function path($p)
	{
	    $a = explode ("/", $p);
	    $len = strlen ($a[count ($a) - 1]);
	    return (substr ($p, 0, strlen ($p) - $len));
	}

	public static function getUrl($url)
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    $data = curl_exec($ch);
	    curl_close($ch);
	    return $data;
	}

	public static function scan($url)
	{

		// Add the domain to the array
	    array_push(self::$siteMapUrls, $url);

	    // Get the initial data
	    $html = siteMap::getUrl($url);

	    // Break it up
	    $a1 = explode ("<a", $html);

	    foreach ($a1 as $key => $val) {
			$parts = explode (">", $val);
			$a = $parts[0];
			$aparts = explode ("href=", $a);
			if(!isset($aparts[1])) { continue; }
			$hrefparts = explode (" ", $aparts[1]);
			$hrefparts2 = explode ("#", $hrefparts[0]);
			$href = str_replace ("\"", "", $hrefparts2[0]);
			
			//var_dump($href);
			if($href == "") { 				continue; }
			if(strstr($href,"Notice:")) { 	continue; }
			if(strstr($href,"'")) { 		continue; }
			$href = str_replace(" ", "", $href);

			// get the href
			if ((substr ($href, 0, 7) != "http://") && 
			   (substr ($href, 0, 8) != "https://") &&
			   (substr ($href, 0, 6) != "ftp://")) {
			    if ($href[0] == '/') {
					$href = self::$siteMapUrls[0].$href;
			    } else {
					$href = siteMap::path($url) . $href;
				}
			}

			// If the href is the domain then do it...
			if (substr($href, 0, strlen (self::$siteMapUrls[0])) == self::$siteMapUrls[0]) {
			    if ((!in_array ($href, self::$siteMapUrls))) {
					echo $href."\n";
					siteMap::scan($href);
			    }
			}
	    }
	}	
}