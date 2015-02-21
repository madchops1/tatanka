<?php
/**
 *                     _______    _______       _   _ _  __          
 *             _,(-)  |__   __|/\|__   __|/\   | \ | | |/ /    /\    
 *           (`  \_(     | |  /  \  | |  /  \  |  \| | ' /    /  \   
 *    _,(-)  //">^\      | | / /\ \ | | / /\ \ | . ` |  <    / /\ \  
 *  (`  \_(              | |/ ____ \| |/ ____ \| |\  | . \  / ____ \ 
 *  //"\^>               |_/_/    \_\_/_/    \_\_| \_|_|\_\/_/    \_\
 *
 * The Core TATANKA App Class
 * Do not edit the core class or any other file in the core "tatanka/" directory.
 * To begin make sure you have your're .htaccess, index.php, uploads/, and settings.ignore.php.
 * Also most importantly make sure you extend this app class with your own "your-app-name.class.php" class.
 * See the documentation for documentation and examples.
 */

// Setup
error_reporting(-1);
ini_set("display_errors", 1);
ob_start();

// Settings
include 'inc/settings.ignore.php';

// CORE Includes
include 'tatanka/database.class.php';
include 'tatanka/utility.class.php';
include 'tatanka/store.class.php';
include 'tatanka/google.class.php';
include 'tatanka/facebook.class.php';
include 'tatanka/user.class.php'; 

echo "GOOFY 2";

class app {

	// Application Settings
  public $domain              = '';                               // The domain where the app resides no trailing slash
  public $docRoot             = '';                               // The absolute path to the document root for the app on the server
  public $tz                  = '';                               // Timezone
  public $debug               = false;                            // Debugging info toggle
  public $down                = false;                            // Put the site down, uses the down.php template
  public $downAccessIps       = array();                          // Array of ips that can access the site when it is down
  public $appName				      = '';    									          // The universal app name
  public $author              = '';                               // The developer
  public $contactEmail        = '';                               // The email the contact form submits to, override with child class
  public $layout              = '';                               // The layout of the website. A directory in the layouts directory
  public $adminLayout         = '';                               // The layout of the admin
  public $layoutDir           = 'layouts/';                       // The default dir for layouts/skins
  public $excludedIndexPages  = array();                          // Pages/urls that ignore the layout index and are fully custom templates
  public $page                = '';                               // The default page the app loads
  public $pagePart            = 1;                                // e.g. http://www.domain.com/PAGEPART = 1
  public $adminPagePart       = false;                            // e.g. http://www.domain.com/admin/PAGEPART = 2
  public $urlArray            = array();                          // urlArray used by the app
  public $welcomeEmail        = '';                               // Meant to be overridden by child class
  public $contactConfEmail    = '';                               // Meant to be overridden by child class
  public $companyName         = '';                               // Meant to be overridden by child class
  public $googleMapsKey       = "";                               // Meant to be overridden by child class
  public $googleAnalyticsId   = "";                               // Meant to be overridden by child class             
  public $googleClientId      = "";                               // Meant to be overridden by child class
  public $googleSecret        = "";                               // Meant to be overridden by child class
  public $googleVerification  = "";                               // Meant to be overridden by child class
  public $faceBookAppId       = "";                               // Meant to be overridden by child class
  public $faceBookAppSecret   = "";                               // Meant to be overridden by child class
  public $faceBookUrl         = "";                               // Meant to be overridden by child class
  public $httpHost            = false;                            // Used for http environment, will be false in arrow
  public $pwd                 = false;                            // Used for arrow environment
  public $arrow               = false; 
  public $environment         = "";                               // Key name of the environment from settings
  /** 
   * Constructor
   */ 
  function __construct() 
  {

    // Set the Http Host
    if(isset($_SERVER['HTTP_HOST'])) {
      $this->httpHost = strtolower($_SERVER['HTTP_HOST']);
    }

    // Set PWD for arrow
    if(isset($_SERVER['PWD'])) {
      $this->pwd = $_SERVER['PWD'];
    }

    // Arrow
    if($_SERVER['PHP_SELF'] == 'arrow') {
      // return false for arrow, arrow requires no session.
      //return false;
      $this->arrow = true;
    }

    // Start the session
    session_start(); 

    // Set the timezone
    date_default_timezone_set($this->tz);
    
    // Handle Environemental Variables
    $envs = unserialize(ENVIRONMENTS);
    foreach($envs as $key => $environment) {
      
      //echo strtolower($this->httpHost)." = ".strtolower($environment['host'])."<br>";

      // Match this environment
      if(strtolower($this->httpHost) == strtolower($environment['host'])) {
        
        // Set environmentals
        $this->environment  = $key; 
        $this->docRoot      = $environment['docroot'];
        $this->domain       = $environment['host'];

      }
    }

    // Connect to the database
    database::connectDatabase();

    // Create the User first in session
    if(!isset($_SESSION['user']->id)) { 
      session_destroy();
      session_start();
      $_SESSION['user'] = new user; 
    }

    $_SESSION['user']->refreshUser();

    // Create the Store in session 
    if(!isset($_SESSION['store'])) { $_SESSION['store'] = new store; }

    // Google Class Setup and Configure
    $this->google = new google;
    $this->google->analyticsId            = $this->googleAnalyticsId;
    $this->google->clientId               = $this->googleClientId;
    $this->google->clientSecret           = $this->googleSecret;
    $this->google->verification           = $this->googleVerification;
    $this->google->mapsKey                = $this->googleMapsKey;

    // Facebook Class Setup and Configure
    $this->facebook = new facebook;
    $this->facebook->appId                = $this->faceBookAppId;
    $this->facebook->appSecret            = $this->faceBookAppSecret;
    $this->facebook->url                  = $this->faceBookUrl;

    // Configure the store
    $_SESSION['store']->taxRate           = $this->taxRate;
    $_SESSION['store']->flatShippingRate  = $this->flatShippingRate;

    // Certain Values need to be available in $_SESSION['user']
    if(isset($_SESSION['user'])) {
      $_SESSION['user']->welcomeEmail   = $this->welcomeEmail; 
      $_SESSION['user']->companyName    = $this->companyName; 
      $_SESSION['user']->domain         = $this->domain;
    }

    
    // Process Request
    $this->processRequest();
  }

  /**
   * Destructor
   */
  function __destruct() 
  {

    database::disconnectDatabase();
  }

  /**
   * Debug Function
   */
  public function debug() 
  {
    if($this->debug == true) { 

      // APP
      echo '<br>Page:<Br>';
      echo '<pre>';
      var_dump($this->page);
      echo '</pre>';

      // REQUEST
      echo '<br>$_REQUEST:<Br>';
      echo '<pre>';
      var_dump($_REQUEST);
      echo '</pre>';

      // POST
      echo '<br>$_POST:<Br>';
      echo '<pre>';
      var_dump($_POST);
      echo '</pre>';

      // USER
      echo '<br>USER:<Br>';
      echo '<pre>';
      var_dump($_SESSION['user']);
      echo '</pre>';

      // STORE
      echo '<br>STORE:<Br>';
      echo '<pre>';
      var_dump($_SESSION['store']);
      echo '</pre>';


      // SERVER
      echo '<br>SERVER:<Br>';
      echo '<pre>';
      var_dump($_SERVER);
      echo '</pre>';

      // ALERTS
      echo '<br>ALERTS:<br>';
      echo '<pre>';
      @var_dump($_SESSION['alertStatus']);
      @var_dump($_SESSION['alertMsg']);
      echo '</pre>';

    }
  }

  /**
   * Processes all requests:
   * - Triggers the router
   * - Sets the current page
   */
  protected function processRequest() 
  {

    //
    // The View
    // Handle Mod Rewrite
    //
    if(!isset($_REQUEST['request'])){
      $_REQUEST['request'] = "";
    }
    //if(isset($_REQUEST['request'])) {

      $requestArray = explode(" ",$_REQUEST['request']);
      $url = $requestArray[1];
      $urlArray = explode("/",$url);
      $this->urlArray = $urlArray;

      //var_dump($urlArray);

      //
      // www...
      // Set the page to display
      // and the other params into $_REQUEST and $_GET
      //
      if(!isset($urlArray[$this->pagePart])){
        $urlArray[$this->pagePart] = "";
      }
      //if(isset($urlArray[$this->pagePart]) && $urlArray[$this->pagePart] != '') {
        
        // Set Default Page
        $defaultPage = $this->page;

        // Override the page if it is being passed
        if($urlArray[$this->pagePart] != "") {
          $this->page = $urlArray[$this->pagePart];
        }

        // Sanitize page
        if(strstr($this->page,"#")) {
          $pageArray = explode("#",$this->page);
          $this->page = $pageArray[0];
        }

        // Admin Url Handling
        if($this->page == 'admin') {
          if(!isset($urlArray[$this->adminPagePart])) $urlArray[$this->adminPagePart] = "";
          $this->page   = $urlArray[$this->adminPagePart] != "" ? $urlArray[$this->adminPagePart] : $this->page = $defaultPage;
          $this->layout = $this->adminLayout;
          $this->debug  = false;
          unset($urlArray[$this->adminPagePart]);
          if($this->page != '' && $this->page != 'home' && !strstr($_SESSION['user']->roles,"admin")) {
            $_SESSION['alertStatus'] = 'error';
            $_SESSION['alertMsg'] = 'You must be an admin to go there :(';
            header("LOCATION: /admin");
            die;
          }
        }

        // Down Page Handling
        if($this->down == true && $this->page != 'down') {
          $yourIp = utility::getUserIp();
          if(!in_array($yourIp, $this->downAccessIps)){
            //$_SESSION['alertStatus'] = 'error';
            //$_SESSION['alertMsg'] = 'Your ip: '.$yourIp.' must be authenticated if your a developer :(';
            header("Location: /down");
            die;
          }
        } 

        // Required Roles Handling
        // @todo... should handle and expand on admin url handlint

        // Remove 0, the Page, and the dev root if its there
        unset($urlArray[0]);
        unset($urlArray[$this->pagePart]);

        // Reset Array Values
        $urlArray = array_values($urlArray);
        $i=1;
        foreach($urlArray as $part) {
          if ($i % 2 != 0) {
            if(isset($urlArray[$i])) {
              $_REQUEST[$part] = $urlArray[$i]; 
            } else {
              $_REQUEST[$part] = "";
            }
          } else {
            $i++;
            continue;
          }
          $i++;
        }
      //}
    //}

    //
    // The Request Router
    //
    $this->router();
  }

  /**
   * Request  Router
   */
  protected function router() 
  {

    //
    // Get Router
    //
    switch(strtolower($this->page)) {

      //
      // Logout requests
      //
      case "logout":

        // Destroy the Session
        session_unset();
        session_destroy();
        session_start();

        // Recreate Session classes because we instantiate the app after these
        // ...so they won't exist if we don't
        $_SESSION['user'] = new user;
        $_SESSION['store'] = new store;
        $_SESSION['alertStatus'] = 'success';
        $_SESSION['alertMsg'] = 'Logged out successfully.';
        header("LOCATION: /");
        die;
        break;

      //
      //
      //
      case "account":
        $_SESSION['user']->restrictedPage();
        break;

      //
      // REST API requests
      // As of now there is no core api
      //
      case "api":
        //echo "This is a request to the api... Functionality coming soon";
        //die;
        break;
    }


    //
    // POST Rouer
    //
    if(isset($_POST['action'])) {

      // Sanitize Post
      $_UNSANITEZED_POST = $_POST;
      $_POST = database::sanitizePost($_POST);
      //var_dump($_POST);
      //die;

      switch($_POST['action']) {        
        
        case "adminlogin":
          $_SESSION['user']->login($_POST,true);
          break;

        case "login":
          $_SESSION['user']->login($_POST);
          break;

        case "register":
          $_SESSION['user']->register($_POST);
          break; 

        case "forgotpassword":
          $_SESSION['user']->forgotPassword($_POST);
          break;

        case "updateaccount":
          if($this->updateAccount($_POST)) {
            if($_SESSION['user']->updateAccount($_POST)) {
              header("LOCATION: /".$this->page);
              die;
            }
          }

          break;

        case "changepassword": 
          $_SESSION['user']->changepassword($_POST);
          break;

        case "addtocart":
          $_SESSION['store']->addToCart($_POST);
          break;

        case "updatecart":
          $_SESSION['store']->updateCart($_POST);
          break;

        case "emptycart":
          $_SESSION['store']->emptyCart($_POST);
          break;   
        
        // Works dynamically with administration table data
        case "table":
          //die($this->page);
          // Delete ids
          if(isset($_POST['ids']) && count($_POST['ids']) && isset($_POST['delete_ids'])) {
            $this->delete($_POST);
            header("LOCATION: /admin/".$this->page."");
            die;
          }

          // Change Statuses
          if(isset($_POST['ids']) && count($_POST['ids']) && isset($_POST['change_status'])) {
            $this->changeStatus($_POST);
            header("LOCATION: /admin/".$this->page."");
            die;
          }

          // Page Goto
          if(isset($_POST['current_page']) && isset($_POST['goto_page'])) {
            if($_POST['current_page'] != $_POST['goto_page']) {
              $_REQUEST['page'] = $_POST['goto_page'];
            }
            header("LOCATION: /admin/".$this->page."");
            die;
          }

          // Search
          if($_POST['keywords'] != "") {
            $_REQUEST['page'] = 1;
            $_REQUEST['keywords'] = $_POST['keywords'];
            header("LOCATION: /admin/".$this->page."");
            die;
          }

          //header("LOCATION: /admin/".$this->page."");
          //die;

          break;

        // Delete an item, mark as active=0
        case "delete":
          $this->delete($_POST);          
          break;         

        // Generic Contact Form Processing
        case "contact":
          $this->processContactForm($_POST);
          break;

        // Do nothing by default...
        default:
          break;
      }
    }

    return true;
  }

  /**
   * Start App
   */
  public function startApp() 
  {
    
    $app = $this;

    // Ignore layout index for declared pages
    if(in_array($this->page, $this->excludedIndexPages)) {
      include $this->layoutDir.$this->layout."/".$this->page.".php";
      return true;
    }

    // Include pages inside the layout's index
    if(!(include $this->layoutDir.$this->layout."/index.php")) {
      echo "Cannot find your layout index.php file. Ex: layouts/your-layout/index.php<br>Also make sure to set your layout in your app's extension of the app class.";
    }
    return true;
  }

  /**
   * Displays a www page
   */
  protected function displayPage() 
  {
    
    $app = $this;
    if(!(include $this->layoutDir.$this->layout."/".$this->page.".php")) {
      header("LOCATION: /404");
      exit;
    }
    return true;
  }

  /**
   * Active class for navigation
   */
  public function activePage($thisPage) 
  {
    if($thisPage == $this->page) {
      return "active selected";
    }
  }

  /**
   * Alert Migration is to be overriden by child class if desired. 
   * The purpose is to override alert codes. E.g. success = green, error = red, etc...
   */
  public function alertMigration() 
  {
    
    return true;
  }

  /**
   * Alert Function
   * success
   * error
   * info
   * notice
   */
  public function alert() 
  {
    $content = '';

    if(isset($_SESSION['alertStatus']) && isset($_SESSION['alertMsg'])) {

      // If layout has a custom template
      if(file_exists($_SERVER['DOCUMENT_ROOT'].'/layouts/'.$this->layout."/alert.php")) {
        $this->alertMigration();
        include($_SERVER['DOCUMENT_ROOT'].'/layouts/'.$this->layout."/alert.php");
      } 

      // Default template for alerts
      else 
      {
        $content = '  <div class="alert alert-'.$_SESSION['alertStatus'].' '.$_SESSION['alertStatus'].'">
                      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                      '.$_SESSION['alertMsg'].'
                    </div>';
      }
    }
    unset($_SESSION['alertStatus']);
    unset($_SESSION['alertMsg']);
    return $content;
  }

  /**
   * ISSET check
   */
  public function getVar($var) 
  {
    if(isset($_POST[$var])) {
      return $_POST[$var];
    }
    if(isset($_REQUEST[$var])) {
      return $_REQUEST[$var];
    }
    return "";
  }

  /**
   * Table summary is output as html
   */
  public function tableSummary($page,$limit,$total) 
  {
    $content = "Showing <strong>";
    if($page == 1) {
      $startRow = 1;
    } else { 
      $startRow = ($page-1)*$limit; 
    }
    $content .= $startRow;
    $content .= " to ";                             
    $endRow = $startRow + $limit - 1;
    if($endRow > $total) { $endRow = $total; }
    $content .= $endRow;
    $content .= "</strong> of ";
    $content .= $total;
    $content .= " entries";
    return $content;
  }

  /**
   * Table pagination is output into an html list
   */
  public function tablePagination($urlPrefix = "",$page = 1,$total = 0,$limit = 1000,$sort = "",$order="DESC") 
  {
    $content = "";
    $numberOfPages = ceil($total/$limit);

    // If not needed
    if($numberOfPages == 1) {
      return $content;
    }

    // Previous
    if($page > 1) {
      $content .= '<li class="prev"><a href="'.$urlPrefix.$this->page.'/page/'.($page-1).'/sort/'.$sort.'/order/'.$order.'/limit/'.$limit.'/keywords/'.$this->getVar('keywords').'">Previous</a></li>';
    } 

    // previous pages
    $i = $page - 1;
    $max = 0;
    $contentA = array();
    while($i >= 1 && $max < 5 && $page > 1) {
      $contentA[] = '<li><a href="'.$urlPrefix.$this->page.'/page/'.$i.'/sort/'.$sort.'/order/'.$order.'/limit/'.$limit.'/keywords/'.$this->getVar('keywords').'">'.$i.'</a></li>';
      $i--;
      $max++;
    }

    $reversed = array_reverse($contentA);
    //var_dump($reversed);
    $content .= implode("",$reversed);

    // current page
    $content .= '<li class="active"><input type="hidden" name="current_page" class="" value="'.$page.'"/><input type="text" name="goto_page" class="gotopage" value="'.$page.'"/></li>';


    // Next pages
    $i = $page+1;
    $max = 0;
    while($i<=$numberOfPages && $max < 5) {
      $content .= '<li><a href="'.$urlPrefix.$this->page.'/page/'.$i.'/sort/'.$sort.'/order/'.$order.'/limit/'.$limit.'/keywords/'.$this->getVar('keywords').'">'.$i.'</a></li>';
      $i++;
      $max++;
    }

    // Next
    if($page < $numberOfPages) {
      $content .= '<li class="next"><a href="'.$urlPrefix.$this->page.'/page/'.($page+1).'/sort/'.$sort.'/order/'.$order.'/limit/'.$limit.'/keywords/'.$this->getVar('keywords').'">Next</a></li>';
    }

    return $content;    
  }

  /**
   * Delete items from the database
   * Nothing ever truly gets deleted, "active" column is set to 0 
   * $post['table']
   * $post['xid']
   * $post['id']
   * $post['ids']
   */
  public function delete($post)
  {
    if(!isset($post['table']) || !isset($post['xid']) && ( !isset($post['id']) || !isset($post['ids']))) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg']    = 'Error';  
      return false;
    }

    if(isset($post['ids'])) {
      foreach($post['ids'] as $id) {
        $s = "UPDATE `".$post['table']."` SET `active`='0' WHERE `".$post['xid']."`='".$id."' LIMIT 1";
        $r = database::dbQuery($s);
      }
      $_SESSION['alertStatus'] = 'success';
      $_SESSION['alertMsg'] = 'Deleted.';  
      return true;
    }

    $s = "UPDATE `".$post['table']."` SET `active`='0' WHERE `".$post['xid']."`='".$post['id']."' LIMIT 1";
    $r = database::dbQuery($s);
    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg'] = 'Deleted.'; 
    return true;
  }

  /**
   * Update item's status in the database
   * Nothing ever truly gets deleted, "active" column is set to 0 
   * $post['table']
   * $post['xid']
   * $post['id']
   * $post['ids']
   */
  public function changeStatus($post)
  {
    if(!isset($post['table']) || !isset($post['xid']) && ( !isset($post['id']) || !isset($post['ids']))) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg']    = 'Error';  
      return false;
    }

    if(isset($post['ids'])) {
      foreach($post['ids'] as $id) {
        $s = "UPDATE `".$post['table']."` SET `status`='".$_POST['status']."' WHERE `".$post['xid']."`='".$id."' LIMIT 1";
        $r = database::dbQuery($s);
      }
      $_SESSION['alertStatus'] = 'success';
      $_SESSION['alertMsg']    = 'Updated.';  
      return true;
    }

    $s = "UPDATE `".$post['table']."` SET `status`='".$_POST['status']."' WHERE `".$post['xid']."`='".$post['id']."' LIMIT 1";
    $r = database::dbQuery($s);
    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg']    = 'Updated.'; 
    return true;
  }

  /**
   * Process Contact Form
   * A basic and generic contact form for a website
   */
  public function processContactForm($post) 
  {
    // Validation
    if(!isset($post['name']) || !isset($post['email']) || !isset($post['message'])) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Please enter required fields.';
      return false;
    }  

    //
    if($post['name'] == "") {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Please enter your name.';
      return false;
    }

    //
    if($post['email'] == "" || !utility::validateEmail($post['email'])) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Please enter a valid email.';
      return false;
    }

    //
    if($post['message'] == "") {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Please enter a message.';
      return false;
    }

    // Send Emails
    // Email contact form to us
    $to       = $this->contactEmail;
    $fname    = "GoReturnMe";
    $from     = $post['email'];
    $fromName = $post['name'];
    $subj     = "Contact form submission from ".$this->domain;
    $message  = $post['message'];
    utility::mail($to,$fname,$from,$fromName,$subj,$message);

    // Email contact form confirmation to sender
    $to       = $post['email'];
    $fname    = $post['name'];
    $from     = "DoNotReply@".$this->domain;
    $fromName = $this->companyName;
    $subj     = $this->domain." Received Your Contact Submission. Thank You.";
    $message  = $this->contactConfEmail;
    utility::mail($to,$fname,$from,$fromName,$subj,$message);

    // Redirect to prevent form re-submission
    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg'] = 'We have received your message. Thanks!';
    header("Location: /contact");
    die;
  }

  public function uploadImage($name='photo',$field='image') 
  {

    // Upload the found photo
    $new_file_name  = '';
    $fileSql        = ' ';
    if(isset($_FILES[$name]['name']) && $_FILES[$name]['name'] != '')
    {
      //if no errors...
      if(!$_FILES[$name]['error'])
      {
        //now is the time to modify the future file name and validate the file
        $extension =  pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);
        $new_file_name = md5(strtolower($_FILES[$name]['tmp_name'])).".".strtolower($extension); //rename file
        $fileSql = "`".$field."`='/uploads/".$new_file_name."', "; 
        if($_FILES[$name]['size'] > (5120000)) //can't be larger than 5 MB
        {
          $_SESSION['alertStatus'] = 'error';
          $_SESSION['alertMsg'] = 'Oops!  Your file\'s size is to large. Maximum 5MB.';
          return false;
        }    
        //move it to where we want it to be
        move_uploaded_file($_FILES[$name]['tmp_name'], $_SERVER['DOCUMENT_ROOT'].'/uploads/'.$new_file_name);          
      }
      //if there is an error...
      else
      {
        //set that to be the returned message
        $_SESSION['alertStatus'] = 'error';
        $_SESSION['alertMsg'] = 'Oops!  File Error.';
        return false;
      }
    }

    return $fileSql;
  }
  
  function updateAccount() {

    return true;
  }

  public function isSSL()
  {
      if( !empty( $_SERVER['HTTPS'] ) )
          return true;

      if( !empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
          return true;

      return false;
  }

}
?>