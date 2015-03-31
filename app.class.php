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
 * To begin read the documentation @ 
 */

error_reporting(-1);                    // Error reporting level
ini_set('display_errors', 1);           // Display errors 1 or 0
ob_start();                             // Output buffer
require 'inc/settings.ignore.php';      // Settings
require 'tatanka/database.class.php';   // The database layer class
require 'tatanka/helper.class.php';     // Now the helper functions, was legacy: utility functions
if (!defined('TATANKA_DIR'))  define('TATANKA_DIR', 'tatanka'); // Tatanka constant
if (!defined('MOD_DIR'))      define('MOD_DIR', 'modules');     // Tatanka constant


function __autoload($className) 
{
  $filename = TATANKA_DIR."/".MOD_DIR."/".$className."/".$className.".class.php";
  if (is_readable($filename)) require $filename;
}

class app 
{

  // settings
  public $domain                    = '';                               // The domain where the app resides no trailing slash, e.g. 'tatanka.io'
  public $docRoot                   = '';                               // The absolute path to the document root for the app on the server
  public $tz                        = '';                               // Timezone
  public $debug                     = false;                            // Debugging info toggle
  protected $tatankaDirectory       = TATANKA_DIR;                      // The tatanka app directory
  public $appName				            = '';    									          // The universal app name
  public $author                    = '';                               // The developer
  public static $maxFileUploadSize  = 5120000;                          // The max file upload size

  // Request
  public $page                      = '';                               // The default page the app loads
  public $pagePart                  = 1;                                // e.g. http://www.domain.com/PAGEPART = 1
  public $urlArray                  = array();                          // urlArray used by the app
  public $origUrlArray              = array();                          // origUrlArray used by the app
  public $origUrl                   = '';

  // Environment
  public $httpHost                  = false;                            // Used for http environment, will be false in arrow
  public $pwd                       = false;                            // Used for arrow environment, pwd is the current directory path
  public $arrow                     = false;                            // Arrow container
  public $environment               = '';                               // Key name of the environment from settings
  
  // Layout 
  public $layout                    = '';                               // The layout of the website. A directory in the layouts directory
  public $layoutDir                 = 'layouts/';                       // The default dir for layouts/skins
  public $excludedIndexPages        = array();                          // Pages/urls that ignore the layout index and are fully custom templates
  public $pageVars                  = array();                          // Can return variables from getRouter to the layout 
  
  // Optimization
  public $scripts                   = false;                            // Scripts array
  public $combineScripts            = false;                            // Combine scripts
  public $minifyScripts             = false;                            // Minify scripts as much as possible
  public $cacheScripts              = false;                            // Cache combined and minified scripts
  public $styles                    = false;                            // Styles array
  public $combineStyles             = false;                            // Combine styles
  public $minifyStyles              = false;                            // Minify combined styles
  public $cacheStyles               = false;                            // Cache combined styles
  
  // Pagination, search, and generics
  public $paginationLimit           = 1000;                             // pagination
  public $paginationPage            = 1;                                // pagination
  public $paginationSort            = '';                               // pagination
  public $paginationOrder           = 'desc';                           // pagination
  public $paginationOpOrder         = false;                            // pagination
  public $paginationTotal           = 0;                                // pagination
  public $searchKeywords            = false;                            // search 
  public $table                     = '';                               // generic new and update
  public $oneToManyFields           = array();                          //
  public $manyToManyFields          = array();                          //

  // Modules  
  protected $moduleDirectory        = MOD_DIR;                          // DO NOT CHANGE
  protected $moduleDirectories      = false;                            // Module directories
  public $includedModules           = false;                            // Optional included modules                                    
  public $modules                   = array();                          // Module autoload continer


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

    // Arrow Client
    if($_SERVER['PHP_SELF'] == 'arrow') {
      $this->arrow = true;
    }

    // Start the session
    session_start(); 
    
    // Set the timezone
    date_default_timezone_set($this->tz);
    
    // Handle Environemental Variables
    $envs = unserialize(ENVIRONMENTS);
    foreach($envs as $key => $environment) {
      if(strtolower($this->httpHost) == strtolower($environment['host'])) {        
        $this->environment  = $key; 
        $this->docRoot      = $environment['docroot'];
        $this->domain       = $environment['host'];
      }
    }

    // Connect to the database
    database::connectDatabase();

    // Call the autoloader
    $this->autoLoad();      

    // Process Request
    $this->processRequest();
  }

  /**
   * Destructor
   */
  function __destruct() 
  {
    // Disconnect database
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
      echo '<br>SESSION USER:<Br>';
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
   * Processes all requests
   */
  private function processRequest() 
  {

    // Get the request
    if(!isset($_REQUEST['request'])) { $_REQUEST['request'] = ""; }

    $requestArray = explode(" ",$_REQUEST['request']);
    $url = $requestArray[1];
    $urlArray = explode("/",$url);
    $this->urlArray = $urlArray;
    $this->origUrlArray = $urlArray;

    // Build original full url
    foreach($this->origUrlArray as $part) {
      $this->origUrl .= strtolower($part)."/";
    }
    $this->origUrl = rtrim($this->origUrl,"/");
    $this->origUrl = ltrim($this->origUrl,"/");

    // Set the page to display
    // and the other params into $_REQUEST and $_GET
    if(!isset($this->urlArray[$this->pagePart])) {
      $this->urlArray[$this->pagePart] = "";
    }
      
    // Set Default Page
    $defaultPage = $this->page;

    // Override the default page if it is being passed
    if($this->urlArray[$this->pagePart] != "") {
      $this->page = $this->urlArray[$this->pagePart];
    }

    // Sanitize page
    if(strstr($this->page,"#")) {
      $pageArray = explode("#",$this->page);
      $this->page = $pageArray[0];
    }

    // Strtolower page
    $this->page = strtolower($this->page);
    
    // Trim slash
    $this->page = rtrim($this->page,"/");

    // Call modules processRequest() hooks
    foreach($this->moduleDirectories as $module) {
      $moduleArray = explode("/",$module);
      $moduleName = $moduleArray[count($moduleArray)-1];
      if(isset($this->modules[$moduleName]) && method_exists($this->modules[$moduleName],'processRequest')) { $this->modules[$moduleName]->processRequest(); }
    }

    // Unset the empty portion, and the page
    unset($this->urlArray[0]);
    unset($this->urlArray[$this->pagePart]);

    // Reset Array Values
    $this->urlArray = array_values($this->urlArray);

    // Put the request back into request and get
    $i=1;
    foreach($this->urlArray as $part) {
      if ($i % 2 != 0) {
        if(isset($this->urlArray[$i])) {
          $_REQUEST[$part]  = $this->urlArray[$i]; 
          $_GET[$part]      = $this->urlArray[$i];
        } else {
          $_REQUEST[$part]  = "";
          $_GET[$part]      = "";
        }
      } else {
        $i++;
        continue;
      }
      $i++;
    }

    // Call the router
    $this->router();
  }

  /**
   * Autoload
   */
  private function autoload()
  {
    // Autoload the module classes
    $this->moduleDirectories = glob($this->tatankaDirectory.'/'.$this->moduleDirectory.'/*' , GLOB_ONLYDIR);
    foreach($this->moduleDirectories as $module) { 
      $moduleArray = explode("/",$module);
      $moduleName = $moduleArray[count($moduleArray)-1];
      if($this->includedModules && !in_array(strtolower($moduleName), $this->includedModules)) continue;
      if(class_exists($moduleName)) if(!isset($this->modules[$moduleName])) $this->modules[$moduleName] = new $moduleName($this); 
    }
    // If there are defined included modules then make moduleDirectories equal to includeModules
    if($this->includedModules) { $this->moduleDirectories = $this->includedModules; }
  }

  /**
   * Include Dependencies
   */
  function includeDependencies($dependencies)
  {
    foreach($dependencies as $dependency) {
      //var_dump($this->modules);
      //if(!isset($this->modules[$dependency])) { die("Module ".$dependency." required."); }
      if(class_exists($dependency)) if(!isset($this->modules[$dependency])) $this->modules[$dependency] = new $dependency($this); 
    }
    return true;
  }

  /**
   * Request Router
   */
  private function router() 
  {
    $this->getRouter();
    $this->postRouter();
    return true;
  }

  /**
   * Get Router
   */
  private function getRouter() 
  {
    $this->pagination(); // Call pagination
    // Call module getRouters
    foreach($this->moduleDirectories as $module) {
      $moduleArray = explode("/",$module);
      $moduleName = $moduleArray[count($moduleArray)-1];
      if(isset($this->modules[$moduleName]) && method_exists($this->modules[$moduleName],'getRouter')) { $this->modules[$moduleName]->getRouter(); }
    }
    return true;
  }

  /**
   * Post Request Router
   */
  private function postRouter()
  {

    // Sanitize post, keep unsanitized post around...
    $_UNSANITIZED_POST = $_POST;
    $_POST = database::sanitizePost($_POST);

    // Post action
    if(isset($_POST['action'])) {

      // call module postRouters
      foreach($this->moduleDirectories as $module) {
        $moduleArray = explode("/",$module);
        $moduleName = $moduleArray[count($moduleArray)-1];
        if(isset($this->modules[$moduleName]) && method_exists($this->modules[$moduleName],'postRouter')) { $this->modules[$moduleName]->postRouter(); }
      }

      // post action switch case
      switch($_POST['action']) {        

        // updating a generic new tem
        case "updateitem":
          if($id = $this->updateItem($_POST)) { $this->pfr(); }
          break;

        // creating a generic new item
        case "newitem":
          if($id = $this->newItem($_POST)) { 
            // if admin redirect to the edit/update page
            if($this->modules['admin']->admin) { header("LOCATION: /".$this->modules['admin']->adminPage."/".$this->page."/id/".$id); die; }
            $this->pfr(); 
          }
          break;

        // Works dynamically with table data in admin or main website
        case "table":

          // delete ids
          if(isset($_POST['ids']) && count($_POST['ids']) && isset($_POST['delete_ids'])) {
            $this->delete($_POST);
          }

          // change statuses
          if(isset($_POST['ids']) && count($_POST['ids']) && isset($_POST['change_status'])) {
            $this->changeStatus($_POST);
          }

          // pagination page goto
          if(isset($_POST['current_page']) && isset($_POST['goto_page'])) {
            if($_POST['current_page'] != $_POST['goto_page']) {
              $_REQUEST['page'] = $_POST['goto_page'];
            }
          }

          // search
          if($_POST['keywords'] != "") {
            $_REQUEST['page'] = 1;
            $_REQUEST['keywords'] = $_POST['keywords'];
          }

          // redirect
          //header("LOCATION: /".($this->admin ? $this->adminPage : "")."/".$this->page."");
          //die;

          // prevent form re-submission 
          $this->pfr();

          break;

        // Delete an item, mark as active=0
        case "delete":
          $this->delete($_POST);
          //header("LOCATION: /".($this->admin ? $this->adminPage : "")."/".$this->page."");
          //die;          
          // prevent form re-submission 
          $this->pfr();
          break;         
      }
    }
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
   * ISSET check + pageVar check, for new/edit forms
   */
  public function getVarPageVar($var,$namespace=false)
  {
    // check if there is a value in request for the var
    if($this->getVar($var)) {
      return $this->getVar($var);
    }

    // check if we are working with an object in a kind of namespace
    elseif(isset($this->pageVars[$namespace]->{$var})) {
      return htmlentities($this->pageVars[$namespace]->{$var});
    }

    // check if we are working with an array in a kind of namespace
    elseif(isset($this->pageVars[$namespace][$var])) {
      return htmlentities($this->pageVars[$namespace][$var]);
    }

    // check if we are working with a normal paveVar
    elseif(isset($this->pageVars[$var])) {
      return htmlentities($this->pageVars[$var]);
    }

    // nada
    else {
      return "";
    }
  }
  
  /**
   * Set Pagination Variables
   */
  public function pagination()
  {
    // Built-in Pagination, Limit, Page Number, Sort Order, Opposite Order, Searching
    $this->paginationLimit   = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 1000;
    $this->paginationPage    = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    $this->paginationSort    = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'id';
    // Sort Order
    $this->paginationOrder   = isset($_REQUEST['order']) ? $_REQUEST['order'] : 'desc'; 
    // Opposite Order
    $this->paginationOpOrder = "";
    if($this->paginationOrder == 'desc') { $this->paginationOpOrder = "asc"; } else { $this->paginationOpOrder = "desc"; }
    // Search Keywords
    $this->searchKeywords    = (isset($_REQUEST['keywords']) ? $_REQUEST['keywords'] : "");
  }

  /**
   * Pagination Table summary is output as html
   */
  public function tableSummary() 
  {
    $content = "Showing <strong>";
    if($this->paginationPage == 1) {
      $startRow = 1;
    } else { 
      $startRow = ($this->paginationPage-1)*$this->paginationLimit; 
    }
    $content .= $startRow;
    $content .= " to ";                             
    $endRow = $startRow + $this->paginationLimit - 1;
    if($endRow > $this->paginationTotal) { $endRow = $this->paginationTotal; }
    $content .= $endRow;
    $content .= "</strong> of ";
    $content .= $this->paginationTotal;
    $content .= " entries";
    return $content;
  }

  /**
   * pagination is output into an html list
   */
  public function tablePagination($urlPrefix="") 
  {
    $content = "";
    $numberOfPages = ceil($this->paginationTotal/$this->paginationLimit);

    // If not needed
    if($numberOfPages == 1) { return $content; }

    // Previous
    if($this->paginationPage > 1) {
      $content .= '<li class="prev"><a href="'.$urlPrefix.$this->page.'/page/'.($this->paginationPage-1).'/sort/'.$this->paginationSort.'/order/'.$this->paginationOrder.'/limit/'.$this->paginationLimit.'/keywords/'.$this->searchKeywords.'">Previous</a></li>';
    } 

    // Previous pages
    $i = $this->paginationPage - 1;
    $max = 0;
    $contentA = array();
    while($i >= 1 && $max < 5 && $this->paginationPage > 1) {
      $contentA[] = '<li><a href="'.$urlPrefix.$this->page.'/page/'.$i.'/sort/'.$this->paginationSort.'/order/'.$this->paginationOrder.'/limit/'.$this->paginationLimit.'/keywords/'.$this->searchKeywords.'">'.$i.'</a></li>';
      $i--;
      $max++;
    }

    $reversed = array_reverse($contentA);
    $content .= implode("",$reversed);

    // current page
    $content .= '<li class="active"><input type="hidden" name="current_page" class="" value="'.$this->paginationPage.'"/><input type="text" name="goto_page" class="gotopage" value="'.$this->paginationPage.'"/></li>';


    // Next pages
    $i = $this->paginationPage+1;
    $max = 0;
    while($i<=$numberOfPages && $max < 5) {
      $content .= '<li><a href="'.$urlPrefix.$this->page.'/page/'.$i.'/sort/'.$this->paginationSort.'/order/'.$this->paginationOrder.'/limit/'.$this->paginationLimit.'/keywords/'.$this->searchKeywords.'">'.$i.'</a></li>';
      $i++;
      $max++;
    }

    // Next
    if($this->paginationPage < $numberOfPages) {
      $content .= '<li class="next"><a href="'.$urlPrefix.$this->page.'/page/'.($this->paginationPage+1).'/sort/'.$this->paginationSort.'/order/'.$this->paginationOrder.'/limit/'.$this->paginationLimit.'/keywords/'.$this->searchKeywords.'">Next</a></li>';
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
   * Get all items from a table, paginated
   * returns [mysql resource, full count]
   */
  public function getAll($table, $limit=1000, $page=1, $sort='id', $order='desc')
  {
    $offset = 0;
    $searchSql = "";
    if($page > 1) { $offset = ($page - 1) * $limit; }
    
    $s = "SELECT * FROM `".$table."` 
          WHERE `active`='1' 
          ".$searchSql."
          ORDER BY `".$sort."` ".$order."  
          LIMIT ".$offset.",".$limit."";
    $r = database::dbQuery($s);
    $object = $r;

    $s = "SELECT count(*) FROM `".$table."` 
          WHERE `active`='1' 
          ".$searchSql."";
    $r = database::dbQuery($s);
    $a = mysql_fetch_array($r);
    $count = $a[0];

    $return = array($object,$count);

    return array($object,$count);
  }

  /**
   * get one generic item from a table
   */
  public function getOne($table,$id)
  {
    $s = "SELECT * FROM `".$table."` WHERE `id`='".$id."' LIMIT 1";
    if($r = database::dbQuery($s)) {
      return mysql_fetch_array($r);
    } else {
      return false;
    }
  }

  /**
   * get many generic items from a table
   * return mysql resource or false
   */
  public function getOneToMany($table,$id,$xid) 
  {
    if(!$table){ $table = $this->table; }
    $s = "SELECT * FROM `".$table."` WHERE `".$xid."`='".$id."' AND active='1'";
    $r = database::dbQuery($s);
    return $r;
  }

  /**
   * get many from a relational table, many to many
   */
  public function getManyToMany($table,$relationalTable,$xid,$yid,$id) {
    if(!$table) { $table = $this->table; }
    $s = "SELECT * FROM `".$table."` t1 
          LEFT JOIN `".$relationalTable."` t2 ON t1.id=t2.`".$yid."` 
          WHERE t2.`".$xid."`='".$id."'  
          AND t1.active='1'";
    $r = database::dbQuery($s);
    return $r;
  }

  /**
   * return true if the relationship exists
   */
  public function isManyToMany($relationalTable,$xid,$yid,$idA,$idB) {
    $s = "SELECT * FROM `".$relationalTable."` WHERE `".$xid."`='".$idA."' AND `".$yid."`='".$idB."' LIMIT 1";
    $r = database::dbQuery($s);
    if(mysql_num_rows($r)) {
      return true;
    }
    return false;
  }

  public function isOneToMany() {

  }

  public function addOne() {

  }

  public function removeOne() {

  }


  /**
   * Create a new generic item by creating the id then calling updateItem to update
   * the rest of the values into the database
   */
  public function newItem($post)
  {
    if(!$post) { $post = $_POST; }
    $s = "INSERT INTO `".$this->table."` SET `id`='', `created`=NOW()";
    $r = database::dbQuery($s);
    $lastId = database::lastId();
    $post['id'] = $lastId;
    $this->updateItem($post);
    return $post['id'];
  }

  /**
   * Update a new generic item
   */
  public function updateItem($post)
  {
    if(!$post) { $post = $_POST; }
    if(!$post['id']) { return false; }
    foreach($post as $key=>$value) {
      if($key != 'redirect' && $key != 'image' && $key != 'photo' && $key != 'action' && $key != 'id' && $key != 'created' && $key != 'tabletype' && !array_key_exists($key,$this->oneToManyFields) && !array_key_exists($key, $this->manyToManyFields)) {
        $s = "UPDATE `".$this->table."` SET `".$key."`='".$value."' WHERE `id`='".$post['id']."' LIMIT 1";
        database::dbQuery($s);
      }
    }  

    // check for a photo or image
    $imgInputNameArray = array('image','photo');
    foreach($imgInputNameArray as $postee) {
      // Upload photo file $inputname, $field
      if($fileSql = helper::uploadImage($postee,'image')) {
        $s = "UPDATE `".$this->table."` SET ".rtrim($fileSql,', ')." WHERE `id`='".$post['id']."' LIMIT 1";
        database::dbQuery($s);
      }
    }

    // Update One to many
    if(count($this->oneToManyFields)) {

    }

    // Update Many to many
    if(count($this->manyToManyFields)) {
      foreach($this->manyToManyFields as $field=>$data) {
        // data will be tableB, tableRel, xid, yid
        // remove all relationships
        $s = "DELETE FROM `".$data[1]."` WHERE `".$data[2]."`='".$post['id']."'";        
        database::dbQuery($s);
        // handle only one value

        //var_dump($post[$field]);
        //die;

        if(!is_array($post[$field])) { $post[$field] = array($post[$field]); }
        // re-add all relationships
        foreach($post[$field] as $optionValue) { 
          $s = "INSERT INTO `".$data[1]."` SET `".$data[2]."`='".$post['id']."', `".$data[3]."`='".$optionValue."'";
          echo $s."<br>";
          database::dbQuery($s);
        }
      }
    }

    $_SESSION['alertMsg'] = 'success';
    $_SESSION['alertStatus'] = 'Item saved.';
    return true;
  }

  /**
   * Process table form
   */
  public function processTableForm($post)
  {

    return true;
  }

  /**
   * Upload Image
   * legacy function, this now exists in helper.class.php
   */
  public function uploadImage($name='photo',$field='image') 
  {

    return helper::uploadImage($name,$field);
  }
  
  /**
   * We put an empty updateAccount here because it may be called by a module
   * Now we can override this in our custom child class.
   */
  function updateAccount() 
  {

    return true;
  }

  /**
   * Check if page is SSL
   */
  public function isSSL()
  {
      if( !empty( $_SERVER['HTTPS'] ) )
          return true;

      if( !empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
          return true;

      return false;
  }

  /**
   * Arrow Client function 
   * Clear the scripts and css cache
   */
  public static function clearCache()
  {
    $files = glob('cache/*'); // get all file names
    foreach($files as $file){ // iterate files
      if(is_file($file))
        unlink($file); // delete file
    }
    return true;
  }

  /**
   * Combine Scripts
   */
  public function combineJs()
  {
    $output = '';
    foreach($this->scripts as $script) {
      $twoChar = substr($script, 0, 2);
      // if optimize scripts ture
      if($twoChar == '//' || strtolower($twoChar) == 'ht') {
        if($twoChar == '//') { $script = "http:".$script; }
        @$output .= file_get_contents($script,0);
      } else {
        @$output .= file_get_contents("layouts/".$this->layout."/".$script);
      }  
    }
    return $output;
  }

  /**
   * Combine CSS
   */
  public function combineCss()
  {
    $output = '';
    foreach($this->styles as $style) {
      $twoChar = substr($style, 0, 2);
      // if optimize scripts ture
      if($twoChar == '//' || strtolower($twoChar) == 'ht') {
        if($twoChar == '//') { $style = "http:".$style; }
        @$output .= file_get_contents($style,0);
      } else {
        @$output .= file_get_contents("layouts/".$this->layout."/".$style);
      }  
    }
    return $output;
  }

  /**
   * Include Normal Scripts
   */
  public function includeNormalScripts()
  {
    $output = '';
    foreach($this->scripts as $script) {
      $twoChar = substr($script, 0, 2);
      // if optimize scripts ture
      if($twoChar == '//' || strtolower($twoChar) == 'ht') {
        if($twoChar == '//') { $script = "http:".$script; }
        $output .= "
                    <script type='text/javascript' src='".$script."'></script>";
      } else {
        $output .= "
                    <script type='text/javascript' src='/layouts/".$this->layout."/".$script."'></script>";
      }  
    }
    return $output;
  }

  /**
   * Include Normal Styles
   */
  public function includeNormalStyles()
  {
    $output = '';
    foreach($this->styles as $style) {
      $twoChar = substr($style, 0, 2);
      // if optimize scripts ture
      if($twoChar == '//' || strtolower($twoChar) == 'ht') {
        if($twoChar == '//') { $style = "http:".$style; }
        $output .= "
                    <link rel='stylesheet' href='".$style."'>";      
      } else {
        $output .= "
                    <link rel='stylesheet' href='/layouts/".$this->layout."/".$style."'>";      
      }  
    }
    return $output;
  }

  /**
   * Minify the Scripts
   */
  public function minifyJs($output)
  {
    $output = preg_replace('/^\n+|^[\t\s]*\n+/m', '', $output);
    return $output;
  }

  /**
   * Minify the CSS
   */
  public function minifyCss($output)
  {
    $output = preg_replace('/^\n+|^[\t\s]*\n+/m', '', $output);
    return $output;
  }

  /**
   * Inject the scripts handle optimization or not
   */
  public function injectScripts()
  {
    $output = "";
    $cache = false ;

    // Check for a cache
    if($this->cacheScripts && file_exists("cache/".$this->layout.".js")) {
      if($cacheJs = file_get_contents("cache/".$this->layout.".js")) {
        $output  = "<script type='text/javascript' src='/cache/".$this->layout.".js'></script>";
        $cache = true;
        return $output;
      } 
    }

    // Combine Scripts
    if($this->combineScripts) {
      $output = $this->combineJs();
    } 

    // If not combined Return Normal Scripts 
    else {
      return $this->includeNormalScripts();
    }

    // Minify Scripts
    if($this->minifyScripts) {
      $output = $this->minifyJs($output);
    }

    // Write and Return Cache
    if($this->cacheScripts) {
      $cacheFile = fopen("cache/".$this->layout.".js", "w");
      fwrite($cacheFile, $output);
      fclose($cacheFile);
      $output  = "<script type='text/javascript' src='/cache/".$this->layout.".js'></script>";
      return $output;
    }

    // If not caching then return a combined/minified output 
    $output = "<script type='text/javascript'>
                ".$output."
              </script>";
    return $output;
  }

  /**
   * Inject the styles handle optimization or not
   */
  public function injectStyles()
  {
    $output = "";
    $cache = false ;

    // Check for a cache
    if($this->cacheStyles && file_exists("cache/".$this->layout.".css")) {
      if($cacheCss = file_get_contents("cache/".$this->layout.".css")) {
        $output  = "<link rel='stylesheet' href='/cache/".$this->layout.".css'>";
        $cache = true;
        return $output;
      } 
    }

    // Combine Styles
    if($this->combineStyles) {
      $output = $this->combineCss();
    } 

    // If not combined Return Normal Scripts 
    else {
      return $this->includeNormalStyles();
    }

    // Minify Scripts
    if($this->minifyStyles) {
      $output = $this->minifyCss($output);
    }

    // Write and Return Cache
    if($this->cacheStyles) {
      $cacheFile = fopen("cache/".$this->layout.".css", "w");
      fwrite($cacheFile, $output);
      fclose($cacheFile);
      $output  = "<link rel='stylesheet' href='/cache/".$this->layout.".css'>";
      return $output;
    }

    // If not caching then return a combined/minified output 
    $output = "<style>
                ".$output."
               </style>";
    return $output;
  }

  /**
   * PFR
   * Prevent Form Re-submission
   */
  public function pfr()
  {
    $this->page = ""; 
    foreach($this->origUrlArray as $part) {
      $this->page .= strtolower($part)."/";
    }
    $this->page = rtrim($this->page,"/");
    $this->page = ltrim($this->page,"/");

    header("LOCATION: /".$this->page);
    die;
  }
}