<?php
/* * * * * * * * * * * * * * * *
 *                             *
 *  Google Module for Tatanka  *
 *                             *
 * * * * * * * * * * * * * * * *
 * 
 *  Configurable Settings:
 * 
 *    $googleVerification;
 *    $googleAnalyticsId;
 *
 */
class google {

    public $app;
	public $verification          = false;
    public $analyticsId           = false;
    public $clientId              = false;
    public $clientSecret          = false;
    public $mapsKey               = false;
    public $plusUrl               = false;       
	
    /**
     * Constructor
     */
	function __construct($app) 
    {
        $this->app = $app;
        if(isset($app->googleAnalyticsId))  $this->analyticsId  = $app->googleAnalyticsId;
        if(isset($app->googleClientId))     $this->clientId     = $app->googleClientId;
        if(isset($app->googleClientSecret)) $this->clientSecret = $app->googleClientSecret;
        if(isset($app->googleVerification)) $this->verification = $app->googleVerification;
        if(isset($app->googleMapsKey))      $this->mapsKey      = $app->googleMapsKey;
        if(isset($app->googlePlusUrl))      $this->plusUrl      = $app->googlePlusUrl;
	}

    public function getRouter()
    {

        return true;
    }

    public function postRouter()
    {

        return true;
    }

    /**
     * Google Meta Tags for verification and google api
     */
    function meta() 
    {
        // Include the google verivication code
        if($this->verification) {
            $content =  '
                            <meta name="google-site-verification" content="'.$this->verification.'" />
                        ';
        }

        // If there is a client id dd meta for google signin
        if($this->clientId != "") {
            $content .= ' 
                            <meta name="google-signin-clientid" content="'.$this->clientId.'" />
                            <meta name="google-signin-scope" content="https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/plus.profile.emails.read" />
                            <meta name="google-signin-requestvisibleactions" content="http://schemas.google.com/AddActivity" />
                            <meta name="google-signin-cookiepolicy" content="single_host_origin" />
                            <meta name="google-signin-callback" content="signinCallback" />
                        ';
        }

        return $content;
    }

    /**
     * Google javascript
     */
    public function js() 
    {

        include TATANKA_DIR.'/'.MOD_DIR.'/google/google.js.php';
    }

    /**
     * Get lat and long from Geocoding API
     */
    public function getLatLong($address = false)
    {
        $latlong = '';
        if($address && $address != "") {
            $url = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&key=".$this->mapsKey."";
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $url); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            $output = curl_exec($ch); 
            curl_close($ch);
            $json = json_decode($output);
            //die(var_dump($json));
            if(count($json->results) == 0) {
                return $latlong;
            }
            $latLong = "".$json->results[0]->geometry->location->lat.",".$json->results[0]->geometry->location->lng."";
            //echo "Alpha ".$latLong."<Br>";
            return $latLong;
        }
        return $latlong;
    }
}