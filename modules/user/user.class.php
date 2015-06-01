<?php
/**
 * User module for tatanka
 */
class user {

  // Setup
  public $app;                              // Set app 

  // User Values
  public $email                   = '';     // Email address
  public $fname                   = '';     // First name
  public $lname                   = '';     // Last name
  public $uname                   = '';     // User name, legacy
  public $address1                = '';     // Address
  public $address2                = '';     // Address2
  public $city                    = '';     // City
  public $state                   = '';     // State
  public $country                 = '';     // Country
  public $zip                     = '';     // Zip code
  public $phone_primary           = '';     // Primary phone
  public $phone_secondary         = '';     // Secondary phone
  public $organization            = '';     // User's Organization
  public $type                    = '';     // Type of user
  public $image                   = '';     // User's image
  public $id                      = 0;      // The user id
  public $roles                   = '';     // User's roles in a string

  // Configurable Options
  public $welcomeEmail            = '';       // From email on registration auto-emails
  public $welcomeEmailContent     = '';       // Message content for registration auto-emails
  public $organizationName        = '';       // Website/App Organization name, used in auto-emails
  public $loginRedirect           = false;    // Optional: A default page that the user is redirected to, e.g. 'home'?
  public $requiredRoles           = array();  // Optional: array of required roles to do something

  // Other Values
  public $name                    = '';       // Full name
  public $rolesArray              = array();  // Array of user's roles
  public $domain                  = '';       // Domain


  function __construct($app) 
  {

    // most importantly set the app 
    $this->app = $app;        

    // store the user's session if the session does not exist
    if(!isset($_SESSION['user']->id) || $_SESSION['user']->id == "0") { $_SESSION['user'] = $this; }
    
    // set the options
    if(isset($app->userWelcomeEmail))         $this->welcomeEmail           = $app->userWelcomeEmail;
    if(isset($app->userWelcomeEmailContent))  $this->welcomeEmailContent    = $app->userWelcomeEmailContent;
    if(isset($app->organizationName))         $this->organizationName       = $app->organizationName;
    if(isset($app->loginRedirect))            $this->loginRedirect          = $app->loginRedirect;
    if(isset($app->requiredRoles))            $this->requiredRoles          = $app->requiredRoles;
    
    // set some of the other values
    if(isset($app->domain))                   $this->domain                 = $app->domain;
    
    // Refresh the user's session data if they are logged in only
    if($this->loggedIn()) $this->refreshUser(); 
  
  }

  // Get router
  public function getRouter() 
  {

    // validate roles
    $this->validateRoles();

    // Put these into modules, and build an autoloader...
    switch(strtolower($this->app->page)) {

      // admin/users Route
      case "users":
        if(!$this->app->modules['admin']->admin) { break; } // Admin Only
        $users = $_SESSION['user']->getAllUsers($this->app->paginationLimit,$this->app->paginationPage,$this->app->paginationSort,$this->app->paginationOrder);
        $this->app->pageVars['users'] = $users;
        $this->app->paginationTotal = $users[1];
        break;

      // admin/users-new-edit[/id/*]
      case "users-new-edit":
        if(!$this->app->modules['admin']->admin) { break; } // Admin Only
        $this->app->pageVars['user'] = array();
        $this->app->pageVars['user']['id'] = '';
        if($this->app->getVar('id')) $this->app->pageVars['user'] = $this->getUserData($this->app->getVar('id'));
        break;
  
      // logout
      case "logout":
        $this->logout();
        $_SESSION['alertStatus'] = 'success';
        $_SESSION['alertMsg'] = 'Logged out successfully.';
        header("LOCATION: /");
        die;
        break;

      // account
      case "account":
        $_SESSION['user']->restrictedPage();
        $this->app->pageVars['user'] = $_SESSION['user'];
        break;
    }
    return true;
  }

  /**
   * Post router
   */
  public function postRouter()
  {

    

    if(!isset($_POST['action'])) { return true; }
    switch(strtolower($_POST['action'])) {

      case "api": 



        switch(strtolower($_POST['endpoint'])) {
          
          case "fbauth":

            // die("POST ROUTER : User");
            //var_dump($_POST);
            //die;

            // Check if user exists
            $s = "SELECT id FROM users WHERE fbid='".$_POST['fbid']."' LIMIT 1";
            $r = database::dbQuery($s);
            if(!$user = mysql_fetch_array($r)) {
           
              $s = "INSERT INTO users SET 
                    fbid='".$_POST['fbid']."', 
                    fname='".$_POST['fname']."', 
                    lname='".$_POST['lname']."',
                    email='".$_POST['email']."'";
              database::dbQuery($s);
              $user['id'] = database::lastId();
            //  return json_encode($user);
            //  die;
            }

            // Update the user 
            $s = "UPDATE users SET 
                  fname='".$_POST['fname']."', 
                  lname='".$_POST['lname']."',
                  email='".$_POST['email']."' 
                  WHERE fbid='".$_POST['fbid']."' LIMIT 1";
            database::dbQuery($s);

            $s = "SELECT * FROM users WHERE fbid='".$_POST['fbid']."' LIMIT 1";
            $r = database::dbQuery($s);
            $user = mysql_fetch_array($r);
            
            echo json_encode($user);
            die;

            //die("Auth Endpoint");
            break;
          /*
          case "updateuser":
            if(!isset($_POST['email'])) {
              die('$_POST["email"] is required to update a user via the User Module API.');
            }
            if($id = $this->updateAccount($_POST)) {
              die($id);
            }
            die();
            break;
          case "login":
            if(!isset($_POST('fbid'))) {
              die('$_POST["fbid"] is required to login via the User Module API.');
            }
            if($id = $this->fbLogin($_POST)) { 
              die($id);
            }
            break;
          default:
            die();
            break;
            */
        }
        die("You hit the User Api");
        break;

      case "login":
        if($id = $this->login($_POST)) {
          if(isset($_POST['redirect'])) { header("LOCATION: /".$_POST['redirect']); die; } // override
          if($this->app->modules['admin']->admin) { header("LOCATION: /".$this->app->modules['admin']->adminPage); die; } // admin redirect to admin homepage
          if($this->loginRedirect) { header("LOCATION: /".$_SESSION['user']->loginRedirect.""); die; } // or redirect to the specified loginRedirect page
          header("LOCATION: /"); 
          die;
        }
        break;

      case "register":
        if($id = $this->register($_POST)) {
          if(isset($_POST['redirect'])) { header("LOCATION: /".$_POST['redirect']); die; } // override
          if($this->app->modules['admin']->admin) { header("LOCATION: /".$this->app->modules['admin']->adminPage."/".$this->app->page."/id/".$id); die; } // admin redirect
          $this->app->pfr();
        }
        break;

      case "forgotpassword":
        if($it = $this->forgotPassword($_POST)) { $this->app->pfr(); }
        break;

      case "updateaccount":
        if($id = $this->updateAccount($_POST)) {
          //if($this->app->modules['admin']->admin) { header("LOCATION: /".$this->app->modules['admin']->adminPage."/".$this->app->page."/id/".$id); die; } // admin redirect
          $this->app->pfr(); 
        }
        break;

      case "changepassword": 
        if($id = $this->changepassword($_POST)) {
          //if($this->app->modules['admin']->admin) { header("LOCATION: /".$this->app->modules['admin']->adminPage."/".$this->app->page."/id/".$id); die; } // admin redirect
          $this->app->pfr();
        }
        break;
    }
    return true;
  }

  /**
   * Validate roles
   */
  public function validateRoles()
  {
    if(isset($_SESSION['user']->requiredRoles) && count($_SESSION['user']->requiredRoles) > 0) {
      if(count($_SESSION['user']->rolesArray) > 0) {
        foreach($_SESSION['user']->requiredRoles as $role) {
          if(in_array(strtolower($role), $_SESSION['user']->rolesArray)) {
            return true;
          }
        }
      }
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Clearance required.';
      return false;
    }
    return true;
  }

  /**
   * Welcome string 
   */
  function welcome() 
  {
    $part1 = '';
    $part2 = '';

  	$welcomeArray = array(	'Welcome back',
  													'Hey',
                            'Heya',
                            'Howdy',
                            'Hidy Ho',
                            'Hello',
  													'Hi',
  													'Yo',
                            'Holla',
                            'What is up',
                            'How you doin\'',
                            'Whats up',
                            'Hola',
                            'What it is',
                            'Good to see ya');

    $welcomeArray2 = array( 'there' );

    $part1 = $welcomeArray[array_rand($welcomeArray)];

    $includePart2 = rand(0,1);
    if($includePart2 == 1) {
      $part2 = " ".$welcomeArray2[array_rand($welcomeArray2)];
    }

  	return $part1.$part2;
  }

  /**
   * Login
   *
   */
  function login($post) 
  {
    if(!$post) $post = $_POST; // post check
    if(!helper::validation(array('email','password'),$post)) { return false; }
    $s = "SELECT * FROM users WHERE ( `username`='".$post['email']."' OR `email`='".$post['email']."' ) AND `password`='".md5(trim($post['password']))."' LIMIT 1";
    $r = database::dbQuery($s);
    if($user = mysql_fetch_array($r)) {
      $this->refreshUser($user['id']);
      $_SESSION['alertStatus'] = 'success';
      $_SESSION['alertMsg'] = 'Logged in successfully.';
      return $user['id'];
    }
    $_SESSION['alertStatus'] = 'error';
    $_SESSION['alertMsg'] = 'Login information is incorrect.';
    return false;
  }

  /**
   * FB Login
   */
  function fbLogin($post) {
    if(!$post) $post = $_POST; // post check
    $s = "SELECT * FROM users WHERE fbid='".$_POST['fbid']."' AND email='".$_POST['email']."' LIMIT 1";
    $r = database::dbQuery($s);
    if($user = mysql_fetch_array($r)) {
      $this->refreshUser($user['id']);
      $_SESSION['alertStatus'] = 'success';
      $_SESSION['alertMsg'] = 'Logged in successfully.';
      return $user['id'];
    }
    return false;
  }

  /**
   * Logout 
   */
  function logout() 
  {
  	session_unset(); 
    session_destroy(); 
    session_start();
    return true;
  }

  /**
   * Restricted Page function
   */
  function restrictedPage() 
  {
  	if(!$_SESSION['user']->loggedIn()) {
      $_SESSION['alertMsg'] = 'You must be logged in to perform that request';
      $_SESSION['alertStatus'] = 'error';
  		header("Location: /");
  		die();
  	}
  }

  /**
   * Get another users name, not the signed in user
   */
  function username($id,$format = "first last") 
  {
    $s = "SELECT * FROM `users` WHERE `id`='".$id."' OR `username`='".$id."' LIMIT 1";
    $result = database::dbQuery($s);
    $user = null;
    if($user = mysql_fetch_array($result)) {
      switch($format) {
        case "first":
          return $user['fname'];
          break;
        case "last":
          return $user['lname'];
          break;
        case "username":
          return $user['username'];
          break;
        default:
          return $user['fname']." ".$user['lname'];
          break;
      }
    }
    return "John Doe";
  }

  function getDisplayName($id)
  {
    $user = $_SESSION['user']->getUserData($id);
    if($user['fname'] != '' || $user ['lname'] != '') {
      return $user['fname']." ".$user['lname'];
    } else {
      return $user['email'];
    }
  }

  /**
   * Register a user
   * returns the user id or false
   */
  function register($post) 
  {
    if(!$post) $post = $_POST; // post check

    // general validation
    if(!helper::validation(array('email'),$post)) return false;
    if(!isset($post['terms'])) { $post['terms'] = 0; }
    //sdie($_SESSION['alertMsg']);

    // password validation if not admin      
    // make sure password 1 and password 2 are the same
    if(!$this->app->modules['admin']->admin) {
      if(!helper::validation(array('password1','password2','terms'),$post)) { return false; }
      if($post['password1'] != $post['password2']) {
        $_SESSION['alertStatus'] = 'error';
        $_SESSION['alertMsg'] = 'Your passwords do not match.';
        return false;
      }
    }

    // duplicate email check
    $s = "SELECT * FROM users WHERE `email`='".$post['email']."'";
    $r = database::dbQuery($s);
    if($user = mysql_fetch_array($r)) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'There is already an account registered to '.$post['email'].'.';
      return false;
    }

    // if this is an admin creating a new user then generate a password
    $ammendEmail = '';
    if($this->app->modules['admin']->admin) {
      $post['password1'] = $this->genPass();
      $ammendEmail .= ' Your password is '.$post['password1'].'';
    }

    // insert the User
    $s = "INSERT INTO users SET 
          `email`='".$post['email']."', 
          `password`='".md5(trim($post['password1']))."',
          `terms`='".$post['terms']."',
          `updated`=NOW(),
          `created`=NOW()";
    $r = database::dbQuery($s);
    $lastId = database::lastId();
    
    // upload photo file
    $fileSql = helper::uploadImage('photo','image');

    // ipdate all fileds but...
    // ignore action, submit, photo, and password values
    foreach($post as $key => $field) {
      if($key != "redirect" && $key != "photo" && $key != "submit" && $key != "action" && $key != "password1" && $key != "password2" && $key != 'id') {
        $s = "UPDATE users SET `".$key."`='".$field."' WHERE id='".$lastId."'";
        database::dbQuery($s); 
      }
    }

    // update photo in database
    if($fileSql) {
      $s = "UPDATE users SET ".rtrim($fileSql," ,")." WHERE id='".$lastId."'";
      database::dbQuery($s);
    }

    // If not admin then login the user
    if(!$this->app->modules['admin']->admin) {
      $post['action'] = 'login';
      $post['password'] = trim($post['password1']);
      $this->login($post);
    }
    
    // set Status
    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg'] = 'Registration successfull.';
    
    // registration confirmation email
    $to       = $post['email'];
    $fname    = "";
    $from     = "donotreply@".$this->domain;
    $fromName = $this->companyName;
    $subj     = "Welcome to ".$this->domain;
    $message  = $this->welcomeEmail.$ammendEmail;
    utility::mail($to,$fname,$from,$fromName,$subj,$message);

    // Return 
    return $lastId;
  }

  /**
   * Update Account
   * returns the user id or false
   */
  function updateAccount($post=false,$valid=false) 
  { 
    if(!$post) $post = $_POST; // post check

    // id check, else update current logged in user
    if(isset($post['id'])) { $id = $post['id']; } else { $id = $_SESSION['user']->id; }

    // Validation
    if(isset($post['email'])) {
      if(!helper::validation(array('email'),$post)) return false;
    }

    // Upload photo file
    $fileSql = helper::uploadImage('photo','image');

    // Update all fileds but...
    // Ignore action, submit, photo values
    foreach($post as $key => $field) {
      if($key != "photo" && $key != "submit" && $key != "action" && $key != "redirect") {
        $s = "UPDATE users SET `".$key."`='".$field."' WHERE id='".$id."'";
        database::dbQuery($s); 
      }
    }

    // Update photo in database
    if($fileSql) {
      $s = "UPDATE users SET ".rtrim($fileSql," ,")." WHERE id='".$id."'";
      database::dbQuery($s);
    }

    // refresh the user
    $this->refreshUser();

    // set the alert
    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg'] = 'Account updated!';

    // return id
    return $id;
  }

  /**
   * Change Password
   * returns the user id or false
   */
  function changePassword($post) 
  {
    if(!$post) $post = $_POST; // post check

    // id check
    if(isset($post['id'])) { $id = $post['id']; } else { $id = $_SESSION['user']->id; }

    // validation
    if(!helper::validation(array('password1','password2','current_password'),$post)) return false;

    // make sure current password matches 
    $s = "SELECT password FROM users WHERE id='".$id."' AND password='".md5($post['current_password'])."'LIMIT 1";
    $r = database::dbQuery($s);
    $pass = mysql_fetch_array($r);
    if(!$pass) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Incorrect current password.';
      return false;
    }

    // make sure password 1 and password 2 are the same
    if($post['password1'] != $post['password2']) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Your passwords do not match.';
      return false;
    }

    // Update the password
    $s = "UPDATE users SET password='".md5($post['password1'])."' WHERE id='".$id."' LIMIT 1";
    $r = database::dbQuery($s);

    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg'] = 'Password has been updated.';
    return true;
  }

  /**
   * Email password to user
   * returns the user id or false
   */
  function forgotPassword($post) 
  {
    if(!$post) $post = $_POST; // post check

    // Get the user
    $s = "SELECT * FROM users WHERE `email`='".$post['email']."' LIMIT 1";
    $r = database::dbQuery($s);
    
    // Generate new password
    $newPass = $this->genPass();

    // If there is a user
    if($user = mysql_fetch_array($r)) {

      

      $s = "UPDATE `users` SET `password`='".md5(trim($newPass))."' WHERE `email`='".$user['email']."'";
      database::dbQuery($s);

      //echo md5($newPass)."<Br>";
      //echo $newPass;
      //die;

      $to       = $user['email'];
      $fname    = "";
      $from     = "DoNotReply@".$this->domain;
      $fromName = $this->companyName;
      $subj     = "You requested a new password @ ".$this->domain;
      $message  = "Your new password is: \n\n".trim($newPass)." \n\n";
      $message .= "Login to <a href='http://".$this->domain."'>".$this->domain."</a>\n\n";
      utility::mail($to,$fname,$from,$fromName,$subj,$message);
      $_SESSION['alertStatus'] = 'success';
      $_SESSION['alertMsg'] = 'A new password has been sent to your email address.';
      return $user['id'];
    }

    $_SESSION['alertStatus'] = 'error';
    $_SESSION['alertMsg'] = 'There was an issue finding that account.';
    return false;
  }

  /**
   * Generate random password
   */
  function genPass($length=8) 
  {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $count = mb_strlen($chars);

    for ($i = 0, $result = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result .= mb_substr($chars, $index, 1);
    }

    return $result;
  }

  /**
   * Return an array of users data
   */
  function getUserData($userId) 
  {
    $s = "SELECT * FROM users WHERE id='".$userId."' LIMIT 1";
    $r = database::dbQuery($s);
    while($user = mysql_fetch_array($r)) {
      //if($user['image'] == "") { $user['image'] = $this->defaultImage; }
      return $user;
    }
    return false;
  }

  /**
   * Get all Users
   */
  function getAllUsers($limit=1000,$page=1,$sort='id',$order='DESC') 
  {
    $offset = 0;
    $searchSql = "";
    if($page > 1) { $offset = ($page - 1) * $limit; }
    if(isset($_REQUEST['keywords']) && $_REQUEST['keywords'] != "") { 
      $searchSql =  " AND ( ";
      $searchSql .= "`fname` LIKE '%".$_REQUEST['keywords']."%' OR "; 
      $searchSql .= "`lname` LIKE '%".$_REQUEST['keywords']."%' OR "; 
      $searchSql .= "`phone_primary` LIKE '%".$_REQUEST['keywords']."%' OR "; 
      $searchSql .= "`phone_secondary` LIKE '%".$_REQUEST['keywords']."%' OR "; 
      $searchSql .= "`email` LIKE '%".$_REQUEST['keywords']."%'"; 
      $searchSql .= " ) "; 
    }

    $s = "SELECT * FROM `users` 
          WHERE `active`='1' 
          ".$searchSql."
          ORDER BY `".$sort."` ".$order."  
          LIMIT ".$offset.",".$limit."";
    $r = database::dbQuery($s);
    //var_dump($s);
    $object = $r;

    $s = "SELECT count(*) FROM `users` 
          WHERE `active`='1' 
          ".$searchSql."";
    $r = database::dbQuery($s);
    $a = mysql_fetch_array($r);
    $count = $a[0];

    $return = array($object,$count);
    //var_dump($return);
    //die;
    return array($object,$count);
  }

  /**
   * Reset all the users data, useful after an update
   */
  function refreshUser($id=false)
  {
    if(!$id) { $id = $_SESSION['user']->id; }
    $s = "SELECT * FROM users WHERE id='".$id."' LIMIT 1";
    $r = database::dbQuery($s);
    $user = mysql_fetch_array($r);
    foreach($user as $key=>$responseItem) {
      $_SESSION['user']->{$key} = $responseItem;
    }      
    // Set some of the constructed variables
    $_SESSION['user']->name = $this->username($_SESSION['user']->id);
    $_SESSION['user']->rolesArray = explode(",",$_SESSION['user']->roles);
    return true;  
  }

  /**
   * Is user logged in?
   */
  public static function loggedIn()
  {
    if(!isset($_SESSION['user'])) {
      return false;
    }
    if(!isset($_SESSION['user']->id)) {
      return false;
    }
    if($_SESSION['user']->id == '') {
      return false;
    }
    return true;
  }

  /**
   * Does user have a role?
   */ 
  public static function hasRole($requiredRole=false) 
  {
    if($this->loggedIn()) {
      if($this->rolesArray) {
        foreach($role as $this->rolesArray) {
          if($role == $requiredRole) {
            return true;
          }
        }
      }
    }
    return false;
  }

}
