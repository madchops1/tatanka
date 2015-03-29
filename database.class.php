<?php
/**
 * CORE!    
 * Database Class
 * @todo.. update to PDO
 * 
 * 
 */
class database 
{
										
  static $dbLink;

  /**
   * Connect!
   * Connect to the database,
   * now handles dynamic environments and Arrow, Tatanka's command line interface!
   */
  static public function connectDatabase() 
  {
    $envs = unserialize(ENVIRONMENTS);
    // Get the server host 
    // and setup connection for application
    if(isset($_SERVER['HTTP_HOST'])) {
      foreach($envs as $key => $environment) {
        if((strtolower($_SERVER['HTTP_HOST']) == strtolower($environment['host']))) {
          @database::$dbLink = mysql_connect($environment['dbhost'], $environment['dbuser'], $environment['dbpass']);
          @mysql_select_db($environment['dbname']);
        }
      }
    }
    // Arrow Database Connection
    elseif($_SERVER['PHP_SELF'] == 'arrow') {
      // Get the current location
      $pwd = $_SERVER['PWD'];
      foreach($envs as $key => $environment) {
        if(strtolower($pwd) == strtolower($environment['docroot'])) {
          @database::$dbLink = mysql_connect($environment['dbhost'], $environment['dbuser'], $environment['dbpass']);
          @mysql_select_db($environment['dbname']);
        }
      }
    }
  }

  /**
   * Disconnect!
   * Disconnect from the database
   */
  static public function disconnectDatabase() 
  {

  	//mysql_close(database::$dbLink);
  }

  /**
   * Query
   * Query the database
   * Upgrade to pdo @todo...
   */
  static public function dbQuery($s=null) 
  {
  	if(isset($s)) {
      $s = database::injectionProtection($s);
  		@$result = mysql_query($s);
  		if(mysql_error()){
        echo $s."<Br>";
        die(mysql_error());
  			return false;
  		}
  		return $result;
  	}
    return true;
  }

  /**
   * Insecure but basic injection protection
   */
  static public function injectionProtection($s)
  {
    // Upgrade to pdo @todo...
    $s = str_replace("1=1","",$s);
    $s = str_replace("1 = 1", "", $s);
    return $s;
  }

  /**
   * Sanitization
   * Sanitize and return array of values
   */
  static public function sanitizePost($array)
  {
    foreach($array as $key => $value) {
      if(is_array($value)){
        foreach($value as $k=>$val) {
          $array[$key][$k] = database::escapeVar($val);
        }
      } else {
        $array[$key] = database::escapeVar($value);
      }
    }
    return $array;
  }

  static public function escapeVar($var) 
  {
    // Stripslashes
    if (get_magic_quotes_gpc()) {
     $var = stripslashes($var);
    }
    // Quote if not integer
    if (!is_numeric($var)) {
      $var = mysql_real_escape_string($var);
    }
    return $var;
  }

  static public function lastId()
  {
    $id = mysql_insert_id();
    return $id;
  }

}
?>