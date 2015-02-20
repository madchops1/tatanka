<?php
/**
 * CORE! User Class
 * Registration
 * Login
 * Forgot Password
 * Account 
 * Restricted Pages
 * Session Start
 * @todo Facebook Login/Integration
 * @todo Google Plus Login/Integration
 * @todo Twitter Login/Integration
 */
class user {

  // User Information
  public $user 						= false;
  public $name 						= ''; // (DB) 
  public $email           = ''; // (DB) 
  public $fname           = ''; // (DB) 
  public $lname           = ''; // (DB) 
  public $uname           = ''; // (DB) 
  public $address         = ''; // (DB) 
  public $city            = ''; // (DB) 
  public $state           = ''; // (DB) 
  public $country         = ''; // (DB) 
  public $zip             = ''; // (DB) 
  public $phone_primary   = ''; // (DB) 
  public $phone_secondary = ''; // (DB) 
  public $organization    = ''; // (DB)
  public $type            = ''; // (DB) Type of user
  public $image           = ''; // (DB) The user's image
  public $fbid            = 0;  // (DB) Facebook id
  public $gpid            = 0;  // (DB) Google+ id
  public $twid            = 0;  // (DB) Twitter id
  public $id              = 0;  // (DB) The user id
  public $welcomeEmail    = ""; // Email message content for registration emails
  public $companyName     = ""; // For registration emails
  public $domain          = ""; // For registration emails
  public $roles           = ""; // (DB) user's roles
  public $loginRedirect   = false;

  function __construct() {
    $this->name = $this->fname." ".$this->lname;
  }

  /**
   * Welcome string 
   */
  function welcome() 
  {
  	$welcomeArray = array(	'Welcome back',
  													'Hey',
                            'Heya',
                            'Howdy',
                            'Hidy Ho',
  													'Hi',
  													'Yo',
                            'Holla',
                            'What is up',
                            'How you doin\''
  									);
  	return $welcomeArray[array_rand($welcomeArray)];
  }

  /**
   * Login
   *
   */
  function login($post,$admin=false,$redirect=true) 
  {
  	
    // Validation 
    if(!isset($post['email']) || !isset($post['password'])) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Error processing login';
      return false;
    }

    if($post['email'] == "" || $post['password'] == "") {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Email &amp; Password are required.';
      return false;
    }

    // Database Check
    if( 
        isset($post['email']) && 
        isset($post['password']) && 
        ($post['action'] == 'login' || $post['action'] == 'adminlogin')
    ) {
      $s = "SELECT * FROM users WHERE ( `username`='".$post['email']."' OR `email`='".$post['email']."' ) AND `password`='".md5(trim($post['password']))."'  LIMIT 1";
      $r = database::dbQuery($s);
      
      while($user = mysql_fetch_array($r)) {
        // Admin Validation
        if($admin) {
          if(!strstr($user['roles'],'admin')) {
            $_SESSION['alertStatus'] = 'error';
            $_SESSION['alertMsg'] = 'You do not have clearence for this section.';
            return false;
          }
        }

        $this->user = true;
        $post = $user;
      } 

      if(!$this->user) {
        $_SESSION['alertStatus'] = 'error';
        $_SESSION['alertMsg'] = 'Login information is incorrect.';
        return false;
      }
    } else {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Error.';
      return false;
    }

    // Set the user's data
    foreach($post as $key=>$responseItem) {
  		  $this->{$key} = $responseItem;
  	}

    /*
    // Facebook
    if(isset($this->fb)) {
      $this->type = 'facebook';
    } 
    // Google
    elseif(isset($this->gp)) {
      $this->type = 'google';
    }
    // Twitter
    elseif(isset($this->tw)) {
      $this->type = 'twitter';
    }
    // Local Login
    else{
      $this->type = 'local';
      return true;
    }

    // Update social info from connected accounts
    $this->registerSlashUpdate();
    */
    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg'] = 'Logged in successfully.';

    // Redirect if app requires
    if($_SESSION['user']->loginRedirect && $redirect == true) {

      // Admin redirect
      if($admin) { 
        header("LOCATION: /admin");
        die;
      }

      // Standard redirect
      header("LOCATION: /".$_SESSION['user']->loginRedirect."");
      die;
    }

  	return true;
  }

  /**
   * Logout 
   */
  function logout() 
  {
  	$this->user = null;
  	session_destroy();
  }

  /**
   * Restricted Page function
   */
  function restrictedPage() 
  {
  	if(!$this->user) {
      $_SESSION['alertMsg'] = 'You must be logged in to perform that request'.
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

  function getDisplayName($id){
    $user = $_SESSION['user']->getUserData($id);
    if($user['fname'] != '' || $user ['lname'] != '') {
      return $user['fname']." ".$user['lname'];
    } else {
      return $user['email'];
    }
  }

  /**
   * Register a user
   */
  function register($post) 
  {

    // Validation
    if(!isset($post['email']) || !isset($post['password1']) || !isset($post['password2'])) {
      return false;
    }

    // Required Fields
    if($post['email'] == "" || $post['password1'] == "" || $post['password2'] == "") {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Please enter all required* fields.';
      return false;
    }

    // Make sure password 1 and password 2 are the same
    if($post['password1'] != $post['password2']) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Your passwords do not match.';
      return false;
    }

    // Terms
    if(!isset($post['terms']) || $post['terms'] != "1") {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Please agree to the terms by checking the box.';
      return false;
    }

    // Duplication Check
    $s = "SELECT * FROM users WHERE `email`='".$post['email']."'";
    $r = database::dbQuery($s);
    if($user = mysql_fetch_array($r)) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'There is an account already associated with '.$post['email'].'.';
      return false;
    }

    // Insert the User
    $s = "INSERT INTO users SET 
          `email`='".$post['email']."', 
          `password`='".md5(trim($post['password1']))."',
          `terms`='".$post['terms']."',
          `updated`=NOW(),
          `created`=NOW()";
    $r = database::dbQuery($s);
    
    // Login the User
    $post['action'] = 'login';
    $post['password'] = trim($post['password1']);
    $this->login($post);
    
    // Set Status
    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg'] = 'You have been registered successfully.';
    
    // Registration Confirmation Email
    $to       = $post['email'];
    $fname    = "";
    $from     = "DoNotReply@".$this->domain;
    $fromName = $this->companyName;
    $subj     = "Welcome to ".$this->domain;
    $message  = $this->welcomeEmail;
    utility::mail($to,$fname,$from,$fromName,$subj,$message);

    if(isset($post['redirect'])) {
      header("LOCATION: /".$post['redirect']);
      die;
    }

    return true;
  }

  /**
   * Update Account
   */
  function updateAccount($post) 
  {

    //die(var_dump($post));
    // First run app update account, which can be overridden by custom class update account
    //$this->app->updateAccount();

    // Validation
    if(!isset($post['email'])) {
      return false;
    }

    // Required Fields
    if($post['email'] == "") {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Please enter all required* fields.';
      return false;
    }

    // Upload Photo
    $new_file_name = '';
    $fileSql = '';
    if(isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != '')
    {
      //if no errors...
      if(!$_FILES['photo']['error'])
      {
        //now is the time to modify the future file name and validate the file
        $extension =  pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_file_name = md5(strtolower($_FILES['photo']['tmp_name'])).".".$extension; //rename file
        $fileSql = "image='/uploads/".$new_file_name."', "; 
        if($_FILES['photo']['size'] > (5120000)) //can't be larger than 1 MB
        {
          $_SESSION['alertStatus'] = 'error';
          $_SESSION['alertMsg'] = 'Oops!  Your file\'s size is to large. Maximum 1MB.';
          return false;
        }    
        //move it to where we want it to be
        move_uploaded_file($_FILES['photo']['tmp_name'], $_SERVER['DOCUMENT_ROOT'].'/uploads/'.$new_file_name);          
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

    //die(var_dump($post));

    // Ignore action, submit, photo values
    foreach($post as $key => $field) {
      if($key != "photo" && $key != "submit" && $key != "action") {
        //echo $key.":".$field."<br>";
        //die("ALPHA: ");
        $s = "UPDATE users SET `".$key."`='".$field."' WHERE id='".$_SESSION['user']->id."'";
        //echo $s."<Br>";
        database::dbQuery($s); 
      }
    }

    //die;

    // Update photo
    if($fileSql != "") {
      //die("BETA: ");
      $s = "UPDATE users SET ".rtrim($fileSql," ,")." WHERE id='".$_SESSION['user']->id."'";
      //echo $s;
      database::dbQuery($s);
    }

    /*
    die;
    $s = "UPDATE users SET 
      fname='".$post['fname']."',
      lname='".$post['lname']."',
      email='".$post['email']."',
      address='".$post['address']."',
      city='".$post['city']."',
      state='".$post['state']."',
      zip='".$post['zip']."',
      country='".$post['country']."',
      ".$fileSql."
      phone_primary='".$post['phone_primary']."',
      phone_secondary='".$post['phone_secondary']."'      
      WHERE 
      id='".$_SESSION['user']->id."'";
    $r = database::dbQuery($s);
    */

    $this->refreshUser();

    /*
    // Reset the user class values
    $s = "SELECT * FROM users WHERE id='".$_SESSION['user']->id."' LIMIT 1";
    $r = database::dbQuery($s);
    $user = mysql_fetch_array($r);
    // $post = $user;
    foreach($user as $key=>$responseItem) {
      //if(isset($this->{$key})) {
        $this->{$key} = $responseItem;
      //}
    }
    */

    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg'] = 'Thank you for updating your account.';
    return true;
  }

  /**
   * Change Password
   */
  function changePassword($post) 
  {

    // Validation
    if(!isset($post['password1']) || !isset($post['password2']) || !isset($post['current_password'])) {
      return false;
    }

    if($post['password1'] == "" || $post['password2'] == "" || $post['current_password'] == "") {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Please complete all required fields.';
      return false;
    }

    // Make sure current password matches 
    $s = "SELECT password FROM users WHERE id='".$_SESSION['user']->id."' AND password='".md5($post['current_password'])."'LIMIT 1";
    $r = database::dbQuery($s);
    $pass = mysql_fetch_array($r);
    if(!$pass) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Incorrect current password.';
      return false;
    }


    // Make sure password 1 and password 2 are the same
    if($post['password1'] != $post['password2']) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Your passwords do not match.';
      return false;
    }

    $s = "UPDATE users SET password='".md5($post['password1'])."' WHERE id='".$_SESSION['user']->id."' LIMIT 1";
    $r = database::dbQuery($s);

    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg'] = 'Your password has been updated.';
    return true;
  }

  /**
   * Email password to user
   */
  function forgotPassword($post) {

    $s = "SELECT * FROM users WHERE `email`='".$post['email']."' LIMIT 1";
    $r = database::dbQuery($s);
    
    // Give new password
    $newPass = $this->genPass();
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

      header("LOCATION: /");
      die;   

      return true;
    }

    $_SESSION['alertStatus'] = 'error';
    $_SESSION['alertMsg'] = 'There was an issue creating your new password.';

    return false;
  }

  /**
   * Register User
   * Only after login for social connected accounts
   */
  function registerSlashUpdate() {

    // Check if user is in system as facebook or g+
    $s = "SELECT * FROM `users` 
          WHERE 
          `fbid`='".$this->fbid."' OR 
          `gpid`='".$this->gpid."' 
          LIMIT 1;";
    $r = database::dbQuery($s);

    // If user exists
    if($user = mysql_fetch_array($r)) {
      // Update
      $emailQuery = $this->email != "" ? "`email`='".$this->email."'" : ""; 
      $s = "UPDATE `users` SET 
            `fname`='".$this->fname."',
            `lname`='".$this->lname."',
            `username`='".$this->uname."',
            ".$emailQuery."
            `type`='".$this->type."', 
            `image`='".$this->image."' 
            WHERE 
            `fbid`='".$this->fbid."' OR 
            `gpid`='".$this->gpid."'";
      database::dbQuery($s);

      //$s = "SELECT * FROM `users` WHERE `username`='".$this->uname."' LIMIT 1";
      //$r = database::dbQuery($s);
      //while($row = mysql_fetch_array($r)) {
      $this->id = $user['id'];
      //}
    }

    // Register
    else {
      $s = "INSERT INTO `users` SET
            `fbid`='".$this->fbid."',
            `gpid`='".$this->gpid."',
            `fname`='".$this->fname."',
            `lname`='".$this->lname."',
            `username`='".$this->uname."',
            `email`='".$this->email."',
            `type`='".$this->type."',
            `image`='".$this->image."',
            `updated`=NOW(),
            `created`=NOW()";
      database::dbQuery($s);
      $this->id = mysql_insert_id();
    }
  }

  /**
   * Generate random password
   */
  function genPass($length=8) {
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
  function getUserData($userId) {
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
  function getAllUsers($limit=1000,$page=1,$sort='id',$order='DESC') {
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



  function refreshUser()
  {
    if(isset($_SESSION['user']->id) && $_SESSION['user']->id != "") {
      $s = "SELECT * FROM users WHERE id='".$_SESSION['user']->id."' LIMIT 1";
      $r = database::dbQuery($s);
      $user = mysql_fetch_array($r);
      foreach($user as $key=>$responseItem) {
          $this->{$key} = $responseItem;
      }
    } else {
      $s = "SELECT * FROM users LIMIT 1";
      $r = database::dbQuery($s);
      $user = mysql_fetch_array($r);
      foreach($user as $key=>$responseItem) {
          $this->{$key} = "";
      }
    }
  }

  /**
   * loggedin
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
   *
   */ 
  public static function hasRole($role=false) 
  {
    $user = $_SESSION['user']->getUserData($_SESSION['user']->id);
    if(strstr($_SESSION['user']->roles,$role)) {
      return true;
    }
    return false;
  }

}

?>