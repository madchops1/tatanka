<?php 
/**
 * Store module for Tatanka
 */

// include product class, order class, and attribute class
require_once(TATANKA_DIR."/".MOD_DIR."/store/product.class.php");
require_once(TATANKA_DIR."/".MOD_DIR."/store/attribute.class.php");
require_once(TATANKA_DIR."/".MOD_DIR."/store/order.class.php");

class store {

  public      $app;                                    // set $app container
	public      $cart                = array();          // user's shopping cart
  protected   $dependencies        = array('user');    // module dependencies
  public      $taxRate             = 0.0;              // the tax rate, e.g. 0.1 = 10%
  public      $flatShippingRate    = 0.0;              // If greater than zero all orders will use this shipping amount, overrides all other shipping amount settings
  public      $weightShippingRates = true;             // when true the shipping rates will be calculated by product weight and quantity 
  public      $weightUnit          = 'oz';             // oz or lb
  public      $unitPrice           = '2.00';           //
  public      $subTotal            = 0;
  public      $tax                 = 0;
  public      $discount            = 0;
  public      $shipping            = 0;
  public      $total               = 0;
  public      $discountCode        = false;  

	function __construct($app) 
  {
    $this->app = $app;                                          // Set $app first
    $this->app->includeDependencies($this->dependencies);       // include dependencies

    if(!isset($_SESSION['store'])) $_SESSION['store'] = $this;  // create the Store in session 

    // Configure the store
    if(isset($app->storeTaxRate))              $_SESSION['store']->taxRate              = $app->storeTaxRate;
    if(isset($app->storeFlatShippingRate))     $_SESSION['store']->flatShippingRate     = $app->storeFlatShippingRate;
    if(isset($app->storeWeightShippingRates))  $_SESSION['store']->weightShippingRates  = $app->storeWeightShippingRates;
    if(isset($app->storeWeightUnit))           $_SESSION['store']->weightUnit           = $app->storeWeightUnit;
    if(isset($app->storeUnitPrice))            $_SESSION['store']->unitPrice            = $app->storeUnitPrice;
 
    // calculate totals
    $this->calculateTotals();

    // if there is a logged in user then lets add there order history to the user session object
    if($_SESSION['user']->loggedIn()) $orderHistory = $app->getOneToMany('orders',$_SESSION['user']->id,'user_id');

  }

  /**
   * Get Routes
   */
  function getRouter()
  {
    switch(strtolower($this->app->page)) {

      // Admin Orders Route  
      case "orders":
        if(!$this->app->modules['admin']->admin) { break; } // Admin Only
        $orders = $_SESSION['store']->getAllOrders($this->app->paginationLimit,$this->app->paginationPage,$this->app->paginationSort,$this->app->paginationOrder);
        $this->app->pageVars['orders'] = $orders;
        $this->app->paginationTotal = $orders[1];
        break;

      // Admin Ordrs New/Edit
      case "orders-new-edit":
        if(!$this->app->modules['admin']->admin) { break; } // Admin Only
        break;

      // admin products
      case "products": 
        if(!$this->app->modules['admin']->admin) { break; } // Admin Only
        $products = $this->app->getAll('products',$this->app->paginationLimit,$this->app->paginationPage,$this->app->paginationSort,$this->app->paginationOrder);
        $this->app->pageVars['products'] = $products;
        $this->app->paginationTotal = $products[1];
        break;

      // admin new/edit detaion product
      case "products-new-edit":
        if(!$this->app->modules['admin']->admin) { break; } // Admin Only
        $this->app->pageVars['product'] = array();
        $this->app->pageVars['productAttributes'] = $this->app->getAll('product_attributes');
        if($this->app->getVar('id')) $this->app->pageVars['product'] = new product($this->app,$this->app->getVar('id'));
        break;

      // admin product attributes 
      case "product-attributes":
        if(!$this->app->modules['admin']->admin) { break; } // Admin Only
        $attributes = $this->app->getAll('product_attributes',$this->app->paginationLimit,$this->app->paginationPage,$this->app->paginationSort,$this->app->paginationOrder);
        $this->app->pageVars['productAttributes'] = $attributes;
        $this->app->paginationTotal = $attributes[1];
        break;

      // admin new/edit detail product attribute
      case "product-attributes-new-edit":
        if(!$this->app->modules['admin']->admin) { break; } // Admin Only
        $this->app->pageVars['productAttribute'] = array();
        $this->app->pageVars['productAttributeOptions'] = $this->app->getAll('product_attribute_options');
        if($this->app->getVar('id')) $this->app->pageVars['productAttribute'] = new productAttribute($this->app,$this->app->getVar('id'));
        break;

      // admin product attribute options
      case "product-attribute-options":
        if(!$this->app->modules['admin']->admin) { break; } // Admin Only
        $options = $this->app->getAll('product_attribute_options',$this->app->paginationLimit,$this->app->paginationPage,$this->app->paginationSort,$this->app->paginationOrder);
        $this->app->pageVars['productAttributeOptions'] = $options;
        $this->app->paginationTotal = $options[1];
        break;

      // admin new/edit detail product attribute options
      case "product-attribute-options-new-edit":
        if(!$this->app->modules['admin']->admin) { break; } // Admin Only
        $this->app->pageVars['productAttributeOption'] = array();
        if($this->app->getVar('id')) $this->app->pageVars['productAttributeOption'] = new productAttributeOption($this->app,$this->app->getVar('id'));
        break;

      // empty cart
      case "emptycart":
        if($this->emptyCart()) {
          header("LOCATION: /cart");
          die;
        }
        break;

      // website product detail page
      case "product":
        if(!$this->app->getVar('id')) { $_SESSION['alertStatus'] = 'Error'; $_SESSION['alertMsg'] = 'Uh-oh, store error.'; header("LOCATION: /"); die; }
        $this->app->pageVars['product'] = new product($this->app,$this->app->getVar('id'));
        break;

      case "checkout": 
        $this->app->pageVars['user'] = $_SESSION['user'];
        // if no order id
        if(isset($_REQUEST['confirm']) && !isset($_REQUEST['id'])) {
          header("LOCATION: /checkout");
          die;
        }
        break;
    }
    return true;
  }

  function postRouter()
  {
    if(!isset($_POST['action'])){ return true; }
    switch(strtolower($_POST['action'])) {
      
      // add to cart
      case "addtocart":
        if($_SESSION['store']->addToCart($_POST)) {
          header("LOCATION: /cart");
          die;
        }
        break;

      // update cart
      case "updatecart":
        if($_SESSION['store']->updateCart($_POST)) {
          header("LOCATION: /cart");
          die;
        }
        break;

      // empty shopping cart
      case "emptycart":
        if($_SESSION['store']->emptyCart($_POST)) {
          header("LOCATION: /cart");
          die;        
        }
        break;   

      // generic new store item, used for new products
      case "newitem":

        // admin only, else redirect
        if(!$this->app->modules['admin']->admin) { 
          $_SESSION['alertMsg'] = 'error';
          $_SESSION['alertStatus'] = 'Admin only functionality.';
          header("LOCATION: /"); 
          die; 
        }

        $this->tableTypes($_POST['tabletype']);
        break;

      // update a store item set the tabletypes
      case "updateitem":
        // admin only, else redirect
        if(!$this->app->modules['admin']->admin) { 
          $_SESSION['alertMsg'] = 'error';
          $_SESSION['alertStatus'] = 'Admin only functionality.';
          header("LOCATION: /"); 
          die; 
        }
        $this->tableTypes($_POST['tabletype']);
        break;

      // update shipping, and create the order
      case "updateshipping":
        if($id = $this->updateShipping($_POST)) {
          $_SESSION['alertStatus'] = 'success';
          $_SESSION['alertMsg'] = 'Shipping information updated.';
          header("LOCATION: /checkout/confirm/order/id/".$id);
          die;
        }
        break;
    }
  }

  // table types for generic item creation and updates, sets $this->table
  function tableTypes($tabletype) 
  {
    // switch case type, product, order, attribute, attribute option
    switch(strtolower($tabletype)) {
      case "product":
        $this->app->table = 'products';
        $this->app->manyToManyFields = product::$manyToManyFields;
        break;

      case "order":
        $this->app->table = 'orders';
        break;

      case "attribute":
        $this->app->table = 'product_attributes';
        $this->app->manyToManyFields = productAttribute::$manyToManyFields;
        break;

      case "attributeoption":
        $this->app->table = 'product_attribute_options';
        break;
    }
    return true;
  }

  /**
   * Add to cart
   */
	function addToCart($post) 
  {

    // validation		
    if(!helper::validation(array('qty','id'),$post)) { return false; }

    // validate attributes
    if(isset($post['attributes'])) {
      foreach($post['attributes'] as $attribute){
        if($attribute == '') {
          $_SESSION['alertStatus'] = 'error';
          $_SESSION['alertMsg'] = 'Please select your options.';
          return false;
        }
      }
    }

    // Check if the cart exists
    if(!$_SESSION['store']->cart) $_SESSION['store']->cart = array();

    // Get the product
    $product = new product($this->app,$this->app->getVar('id'));

    // Check Max Qty
    //if(!$this->lessThanMaxQty($post['qty'],$productData)) {
    //  $_SESSION['alertStatus'] = 'error';
    //  $_SESSION['alertMsg'] = 'Quantity must be less than '.$productData['max_qty'].'.';
    //  return false;
    //}

    // Check if Item is already in cart if it is then incriment
    $inCart = false;
    foreach($_SESSION['store']->cart as $item) {
      if($post['id'] == $item['id']) {
        $inCart = true;
        break;
      }
    }

    // If in cart then update
    if($inCart == true) {

      // Update Cart Qty
      foreach($_SESSION['store']->cart as $key=>$item) {
        if($post['id'] == $item['id']) {
          $currentQty   = $item['qty'];
          $addQty       = $post['qty'];
          $newQty       = $currentQty + $addQty;
          $_SESSION['store']->cart[$key]['qty'] = $newQty;
          break;
        }
      }

      // update attributes
      if(isset($post['attributes'])) {
        $_SESSION['store']->cart[$key]['attributes'] = $post['attributes'];
      }

    } 
    // else add item to cart
    else 
    {
      $newItem = array("qty"=>$post['qty'],"id"=>$post['id'],"attributes"=>$post['attributes']);
      $this->cart[] = $newItem;
    }

    // Success
    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg'] = 'Item added to your cart.';
    return true;
	}

  /**
   * Update Shipping
   */
  function updateShipping($post) 
  {
    if(!$post) $post = $_POST; // post check

    // id check, else update current logged in user
    $id = $_SESSION['user']->id;

    // Validation
    if(!helper::validation(array('fname','address1','city','state','zip','country'),$post)) return false;
    
    // Update all fileds but...
    // Ignore action, submit, photo values
    foreach($post as $key => $field) {
      if($key != "photo" && $key != "submit" && $key != "action" && $key != "redirect") {
        $s = "UPDATE users SET `".$key."`='".$field."' WHERE id='".$id."'";
        database::dbQuery($s); 
      }
    }

    // Insert the order into our databse
    $s = "INSERT INTO orders SET 
          user_id = '".$_SESSION['user']->id."',
          created=NOW(),
          ip_address='".utility::getUserIp()."',
          subtotal='".$_SESSION['store']->subTotal."',
          shipping='".$_SESSION['store']->shipping."',
          discount='".$_SESSION['store']->discount."',
          tax='".$_SESSION['store']->tax."',
          total='".$_SESSION['store']->total."',
          name='".$_SESSION['user']->fname." ".$_SESSION['user']->lname."',
          email='".$_SESSION['user']->email."',
          shipping_address1='".$post['address1']."',
          shipping_address2='".$post['address2']."',
          shipping_city='".$post['city']."',
          shipping_state='".$post['state']."',
          shipping_zip='".$post['zip']."',
          shipping_country='".$post['country']."',
          status='Incomplete'";
          //stripe_payment_token=''".$post['stripeToken']."";
    database::dbQuery($s);
    $lastId = mysql_insert_id();

    // Insert the products into the history
    foreach($_SESSION['store']->cart as $key=>$item) {
      $product = new product($this->app,$item['id']);

      $attributeString = "";
      if(is_array($item['attributes'])) { 
        foreach($item['attributes'] as $key=>$attribute) {
          $attr = new productAttribute($_SESSION['store']->app,$key);
          $opt = new productAttributeOption($_SESSION['store']->app,$attribute);
          $attributeString .= "- <small>".$attr->name.": ".$opt->name."</small><br>";
        }
      }  

      $s = "INSERT INTO product_order_history SET 
            id='',
            product_id='".$item['id']."',
            order_id='".$lastId."',
            name='".$product->name."',
            description='".$product->description."',
            image='".$product->image."',
            price='".$product->price."',
            qty='".$item['qty']."',
            max_qty='".$product->max_qty."',
            active='1',
            weight='".$product->weight."',
            flat_shipping='".$product->flat_shipping."',
            attribute_string='".$attributeString."',
            created=NOW()";
      database::dbQuery($s);
    }

    // Return
    return $lastId;
  }

	function updateCart($post) 
  {

    //die(var_dump($_SESSION['store']->cart));
    //die(var_dump($post));


    // Check if we are updating qtys
    $i = 0;
    foreach($_SESSION['store']->cart as $item) {
      $_SESSION['store']->cart[$i]['qty'] = $post['qty'][$i];
      //var_dump($_SESSION['store']->cart[$i]);
      if($post['qty'][$i] == 0) {
        unset($_SESSION['store']->cart[$i]);
      }
      $i++;
    }
    //die;
    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg'] = 'Cart updated.';

    return true;
	}

	function emptyCart() 
  {
    // reset the store and reset the cart
    unset($_SESSION['store']);
    return true;
	}

	function calculateTotals() 
  {
    $_SESSION['store']->subTotal = number_format(0,2);
    $_SESSION['store']->shipping = number_format(0,2);
    $_SESSION['store']->discount = number_format(0,2);

    // calc subtotal
    if(count($_SESSION['store']->cart)) {
      
      foreach($_SESSION['store']->cart as $key=>$item) {
        $product = new product($this->app,$item['id']);
        $_SESSION['store']->subTotal = number_format($_SESSION['store']->subTotal + ($item['qty']*$product->price),2);
        if($this->weightShippingRates) {
          $_SESSION['store']->shipping = number_format($_SESSION['store']->shipping + ($product->weight*$this->unitPrice),2);
        }
      }
    }

    // calc discount
    if(isset($_SESSION['store']->discountCode)) {
      $_SESSION['store']->discount = number_format(0,2);
    }
    

    // calc tax
    $_SESSION['store']->tax = number_format(($_SESSION['store']->subTotal + $_SESSION['store']->shipping - $_SESSION['store']->discount)*$_SESSION['store']->taxRate,2); 

    // calc total
    $_SESSION['store']->total = number_format($_SESSION['store']->subTotal + $_SESSION['store']->shipping - $_SESSION['store']->discount + $_SESSION['store']->tax,2);
    return true;

	}

  /**
   * getProducts 
   */ 
  function getProducts() 
  {
    $s = "SELECT * FROM `products` 
    		  WHERE `active`='1' 
    		  ORDER BY `weight` DESC";
    $r = database::dbQuery($s);
    return $r;
  }

  /*
  function getProductData($productId) 
  {
    $s = "SELECT * FROM `products` 
          WHERE `id`='".$productId."' 
          LIMIT 1";
    $r = database::dbQuery($s);
    while($product = mysql_fetch_array($r)) {
      return $product;
    }
    return false;
  }
  */

  /**
   * Check max qty of a product
   */
  function lessThanMaxQty($qty,$productData) 
  {
    if($productData['max_qty'] >= 1) {
      if($qty > $productData['max_qty']) {
        return false;
      } else {
        return true;
      }
    }
    // no max qty
    return true;
  }

  // Get All Orders
  function getAllOrders($limit=1000,$page=1,$sort='id',$order='DESC')
  {
    $offset     = 0;
    $searchSql  = "";
    if($page > 1) { $offset = ($page - 1) * $limit; }
    if(isset($_REQUEST['keywords']) && $_REQUEST['keywords'] != "") { $searchSql = " AND `name` LIKE '%".$_REQUEST['keywords']."%' "; }
    
    // Query
    $s = "SELECT * FROM `orders` 
          WHERE `active`='1' 
          ".$searchSql."
          ORDER BY `".$sort."` ".$order." 
          LIMIT ".$offset.",".$limit."";
    $r = database::dbQuery($s);
    $object = $r;

    // Count
    $s = "SELECT count(*) FROM `orders` 
          WHERE `active`='1' 
          ".$searchSql."";
    $r = database::dbQuery($s);
    $a = mysql_fetch_array($r);
    $count = $a[0];

    // Return
    return array($object,$count);
  }


  function getOrderData($id) 
  {
    // Get Order
    $s = "SELECT * FROM orders WHERE id='".$id."' LIMIT 1";
    $r = database::dbQuery($s);
    if($r) {
      $order = mysql_fetch_array($r);

      // Get Products
      $s = "SELECT * FROM product_order_history WHERE order_id='".$id."' LIMIT 1";
      $r = database::dbQuery($s);
      while($product = mysql_fetch_array($r)) {
        $order['items'][] = $product; 
      }

      return $order;
    }
    return $false;
  }

  function getProductData($id=false) 
  {
    if(!$id) { return false; }
    $s = "SELECT * FROM products WHERE id='".$id."' LIMIT 1";
    $r = database::dbQuery($s);
    $a = mysql_fetch_array($r);
    return $a;
  }

  /**
   * Retrun array of field names from products table
   * does not include id and active
   */
  function getProductFields()
  {
    $s = "SELECT * FROM products LIMIT 1";
    $r = database::dbQuery($s);
    $productTemplate = mysql_fetch_array($r);
    $keys = array_keys($productTemplate);
    $stringKeys = array();
    foreach($keys as $key) {
      if(is_string($key) && $key != "id" && $key != "active") {
        $stringKeys[$key] = $key;
      }
    }
    return $stringKeys;
  }

  /**
   * Link products to order
   */
  function linkProductsToOrder($products=false,$orderId=false) 
  {
    // Validate
    if(!$products || !$orderId) { return false; }

    foreach($products as $productId) {

      // Get Product
      $product = $this->getProductData($productId);
      
      // Get Fields
      $fields = $this->getProductFields();

      // Link
      $s = "INSERT INTO product_order_history SET 
            order_id='".$orderId."',
            product_id='".$productId."',
            created=NOW(),";

      // Dynamically add all product field data to product order history
      foreach($fields as $field) {
        $s .= "`".$field."`='".$product[$field]."',";
      }
      $s = rtrim($s,",");
      //die($s);
      database::dbQuery($s);
    }

    return true;
  }
}
