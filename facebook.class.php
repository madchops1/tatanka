
<?
/**
 * CORE!
 */
class facebook {

    public $appId       = "";
    public $appSecret   = "";
    public $url         = "";       // Facebook page url


    // Constructor
	function __construct() {

	}

    // Facebook Login Button
	public function facebookLoginButton() 
  	{
        $content = '	<fb:login-button scope="public_profile,email" onlogin="checkLoginState();">
                		</fb:login-button>
                		<div id="fb-status">
                		</div>';
		return $content;
	}

    /** 
     * Include the Facebook Javascript
     */
    public function js()
    {
        include 'facebook.js.php';
    }

}
?>