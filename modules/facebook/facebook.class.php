<?php
/* * * * * * * * * * * * * * * * *
 *                               *
 *  Facebook Module for Tatanka  *
 *                               *
 * * * * * * * * * * * * * * * * *
 * 
 *  Configurable Settings:
 * 
 *    $faceBookAppId;
 *    $faceBookAppSecret;
 *    $faceBookUrl
 *
 */

class facebook {

    public $app;                // $app container              
    public $appId       = "";   // facebook app id
    public $appSecret   = "";   // facebook app secret
    public $url         = "";       // main facebook page url
    public $login       = false;    // True = use facebook login

	function __construct($app) 
    {
        $this->app = $app;                                                                      // Set $app first
        if(isset($app->faceBookAppId))          $this->appId       = $app->faceBookAppId;       // Configurable $appId
        if(isset($app->faceBookAppSecret))      $this->appSecret   = $app->faceBookAppSecret;   // Configurable $appSecret
        if(isset($app->faceBookUrl))            $this->url         = $app->faceBookUrl;         // Configurable $url
        if(isset($app->faceBookLogin))          $this->login       = $app->faceBookLogin;       // Facebook login true or false
	}

    public function getRouter()
    {

        return true;
    }

    public function postRouter()
    {

        return true;
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

        include TATANKA_DIR.'/'.MOD_DIR.'/facebook/facebook.js.php';
    }
}
