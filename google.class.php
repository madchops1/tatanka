<?
/**
 * CORE! Google Class
 */
class google {

    /**
     * Setup
     */
	public $verification          = false;
    public $analyticsId           = false;
    public $clientId              = false;
    public $clientSecret          = false;
    public $mapsKey               = false;
	
    /**
     * Constructor
     */
	function __construct() {

	}

    // Google Meta Tags for verification and google api
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

        include 'google.js.php';
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
?>