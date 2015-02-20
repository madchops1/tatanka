<?
/**
 * CORE! Utility Class
 */
class utility {


  // Send Email
  public static function mail($to,$fname="",$from,$fromName="",$subj,$message) 
  {

    // message
    $finalMessage = '	<html>
                        <head>
                          <title>'.$subj.'</title>
                        </head>
                        <body>
                          '.$message.'
                        </body>
                      </html>
                      ';

    // To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

    // Additional headers
    $headers .= 'To: '.$fname.' <'.$to.'>' . "\r\n";
    $headers .= 'From: '.$fromName.' <'.$from.'>' . "\r\n";

    // Mail it
    mail($to, $subj, $finalMessage, $headers);

    return true;
  }

  // Selected
  public static function selected($x,$y) {
    if($x == $y) {
      return " selected=selected ";
    }
    return "";
  }

  // Checked
  public static function checked($x,$y) {
    if($x == $y) {
      return " checked=checked ";
    }
    return "";
  }

  // Active
  public static function active($x,$y, $z='active') {
    if($x == $y) {
      return " ".$z." ";
    }
    return "";
  }

  // Email Validation
  public static function validateEmail($email) {
    //$email = "someone@exa mple.com";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return false;
    }
    
    return true;
  }

  public static function getUserIp() {
    // Get user IP address
    if ( isset($_SERVER['HTTP_CLIENT_IP']) && ! empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && ! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    $ip = filter_var($ip, FILTER_VALIDATE_IP);
    $ip = ($ip === false) ? '0.0.0.0' : $ip;
    return $ip;
  }

  /**
   * Field validation
   * pass an array of fields
   * array('email','name','address','address2','city')... 
   * $post = $_POST...
   */
  public static function validation($array,$post) {
    
    foreach($array as $field) {
      if(!isset($post[$field]) || $post[$field] ==  "") {
        return false;
      }

      // Custom Validation
      if(strstr(strtolower($field),"email")) {
        if(!utility::validateEmail($post[$field])) {
          return false;
        } 
      }
    }
    
    return true;
  }

}
?>