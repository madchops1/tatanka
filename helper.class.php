<?php
/**
 * Helper / Utility Class for Tatanka
 */
class utility 
{

  /**
   * send email
   */
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

  /**
   * selected
   */
  public static function selected($x,$y) 
  {
    if($x == $y) {
      return " selected=selected ";
    }
    return "";
  }

  /**
   * checked
   */
  public static function checked($x,$y) 
  {
    if($x == $y) {
      return " checked=checked ";
    }
    return "";
  }

  /**
   * active
   */
  public static function active($x,$y,$z='active') 
  {
    if($x == $y) {
      return " ".$z." ";
    }
    return "";
  }

  /**
   * email validation
   */
  public static function validateEmail($email) 
  {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return false;
    }
    return true;
  }

  /**
   * get user's ip
   */
  public static function getUserIp() 
  {
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
   * field validation
   * pass an array of fields
   * array('email','name','address','address2','city')... 
   * $post = $_POST...
   */
  public static function validation($array=false,$post) 
  {
    if(!$array) { $array = array(); }

    foreach($array as $field) {
      if(!isset($post[$field]) || $post[$field] ==  "") {
        $_SESSION['alertStatus'] = 'error';
        $_SESSION['alertMsg'] = ''.ucwords($field).' is required';
        return false;
      }

      // custom validation for email
      if(strstr(strtolower($field),"email")) {
        if(!utility::validateEmail($post[$field])) {
          $_SESSION['alertStatus'] = 'error';
          $_SESSION['alertMsg'] = 'Email: '.$post[$field].' is not valid.';
          return false;
        } 
      }

      // custom validation for terms
      if(strstr(strtolower($field),"terms")) {
        if($post[$field] != '1') {
          $_SESSION['alertStatus'] = 'error';
          $_SESSION['alertMsg'] = 'Terms required.';
          return false;
        }
      }

    }
    
    return true;
  }

  /**
   * upload image
   */
  public static function uploadImage($name='photo', $field='image') 
  {
    // Upload the found photo
    $new_file_name  = '';
    $fileSql        = '';
    if(isset($_FILES[$name]['name']) && $_FILES[$name]['name'] != '') {

      // if there are no errors
      if(!$_FILES[$name]['error']) {
        
        // modify the file name and validate the file
        $extension =  pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);

        // hash filename
        $new_file_name = md5(strtolower($_FILES[$name]['tmp_name'])).".".strtolower($extension); 
        
        // sql code
        $fileSql = " `".$field."`='/uploads/".$new_file_name."', "; 
        
        // filesize check
        if($_FILES[$name]['size'] > (app::$maxFileUploadSize)) {
          $_SESSION['alertStatus'] = 'error';
          $_SESSION['alertMsg'] = 'Oops!  Your file\'s size is to large. Maximum 5MB.';
          return false;
        }    

        // move the file to uploads
        move_uploaded_file($_FILES[$name]['tmp_name'], $_SERVER['DOCUMENT_ROOT'].'/uploads/'.$new_file_name);          
      }
      
      // if there is an error
      else
      {
        //set that to be the returned message
        $_SESSION['alertStatus'] = 'error';
        $_SESSION['alertMsg'] = 'Oops!  File Error.';
        return false;
      }
    }
    if($fileSql !== '') { return $fileSql; }
    return false;
  }

  /**
   * legacy upload photo
   */
  public static function uploadPhoto($name='photo', $field='image') {
    return utility::uploadImage($name,$photo);
  }
}
class_alias('utility','helper');