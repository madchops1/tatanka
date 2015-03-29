<?php
/**
 * Attribute model/class for Tatanka's store module
 */
class productAttribute 
{

	public $app;
	public $id 							= false;
	public $name 						= false;
	public $description 				= false;
	public $price 						= false;
	public $weight 						= false;	// weight
	public $options 		    		= array(); 	// array of productAttributeOptions objects
	public $updated 					= false;
	public $created 					= false;
	public $active						= false;
	public static $manyToManyFields 	= array('options' => array('product_attribute_options','product_attributes_options_relational','attribute_id','attribute_option_id'));


	function __construct($app, $id)
	{
		$this->app 			= $app;
		$this->id 			= $id;
		$attributeArray		= $this->app->getOne('product_attributes',$this->id);

		// Set other product values
		foreach($attributeArray as $key=>$value) {
			$this->{$key} = $value;
		} 

		// Set options
		$r = $this->app->getManyToMany('product_attribute_options','product_attributes_options_relational','attribute_id','attribute_option_id',$this->id);
		while($option = mysql_fetch_array($r)) {
			$this->options[] = new productAttributeOption($app, $option['id']);
		}

	}
}

/**
 * Attribute option model/class for Tatanka's store module
 */
class productAttributeOption 
{

	public $app;
	public $id 					= false;
	public $name 				= false;
	public $updated 			= false;
	public $created 			= false;
	public $active				= false;

	function __construct($app, $id)
	{
		$this->app = $app;
		$this->id = $id;
		$optionArray = $this->app->getOne('product_attribute_options',$this->id);

		// Set other product values
		foreach($optionArray as $key=>$value) {
			$this->{$key} = $value;
		} 
	}
}