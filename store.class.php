<?
/**
 * CORE! Store
 *
 *
 *
 *
 */
class store {

	public $cart = array();

  // Stripe Details
  public $stripeTestSecret    = ""; // Stripe is for the request free tags functionality...
  public $stripeTestPublish   = ""; 
  public $stripeLiveSecret    = "";
  public $stripeLivePublish   = "";

  public $taxRate             = 0.0;
  public $flatShippingRate    = 0.0;

	function __construct() {

	}

  /**
   * Add to cart
   */
	function addToCart($post) {
		
    // Check if the cart exists
    if(!$this->cart) {
			$cart = array();
		} 

    // Get product data
    $productData = $this->getProductData($post['prodid']);

    // Default Qty 1
    if($post['qty'] == "") { 
      $post['qty'] = 1;
    }

    // Check Max Qty
    if(!$this->lessThanMaxQty($post['qty'],$productData)) {
      $_SESSION['alertStatus'] = 'error';
      $_SESSION['alertMsg'] = 'Quantity must be less than '.$productData['max_qty'].'.';
      return false;
    }

    // Check if Item is already in cart if it is then incriment
    $inCart = false;
    foreach($this->cart as $item) {
      if($post['prodid'] == $item['id']) {
        $inCart = true;
        break;
      }
    }

    // If in cart then update qty
    if($inCart == true) {

      // Update Cart Qty
      foreach($this->cart as $key=>$item) {
        if($post['prodid'] == $item['id']) {
          $currentQty = $item['qty'];
          $addQty = $post['qty'];
          $newQty = $currentQty + $addQty;
          $this->cart[$key]['qty'] = $newQty;
          break;
        }
      }
    } 
    // Else add itemto cart
    else {

      // Add item to Cart
      $newItem = array("qty"=>$post['qty'],"id"=>$post['prodid']);
      $this->cart[] = $newItem;
    }

    

    // Success
    $_SESSION['alertStatus'] = 'success';
    $_SESSION['alertMsg'] = 'Item added to your cart.';
    return true;
	}



  /**
   * Remove from cart
   */
  function removeFromCart($post) {
    // If no cart then return false
    if(!$this->cart) {
      return false;
    }
  }

	function updateCart($post) {

    // check max_qty
	}

	function emptyCart($post) {

	}

	function total($post) {

	}

  /**
   * getProducts 
   */ 
  function getProducts() {
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
?>

