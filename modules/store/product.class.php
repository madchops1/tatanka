<?php
/**
 * Product model/class for Tatanka's store module
 */
class product {

	public $app;
	public $id 							= false;
	public $name 						= false;
	public $description 				= false;
	public $price 						= false;
	public $weight 						= false;	// weight
	public $attributes 		    		= array(); 	// array of attribute objects
	public $max_qty 					= 0;
	public $flat_shipping 			    = 0;
	public static $manyToManyFields 	= array('attributes' => array('product_attributes','products_attributes_relational','product_id','product_attribute_id'));

	// Construct the product
	function __construct($app, $id)
	{
		$this->app 			= $app;
		$this->id 			= $id;
		$productArray 		= $this->app->getOne('products',$this->id);

		// Set product values
		foreach($productArray as $key=>$value) {
			$this->{$key} = $value;
		} 

		// Set attributes
		$r = $this->app->getManyToMany('product_attributes','products_attributes_relational','product_id','product_attribute_id',$this->id);
		while($attribute = mysql_fetch_array($r)) {
			$this->attributes[] = new productAttribute($app, $attribute['id']);
		}
	}
}