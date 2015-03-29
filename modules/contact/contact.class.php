<?php
/**
 * Contact module for tatanka
 */
class contact 
{
	public $app;									                           // $app container
	public $orgainzationName               = '';                               // configurable, website/app organization name
	public $confirmationEmail          	   = 'donotreply@donotreply.com';	   // configurable, email address
    public $confirmationEmailContent 	   = '';                               // configurable, html email content             
    public $email                          = '';                               // configurable, the contact form submission email

    /**
     * Constructor
     */
	function __construct($app) 
    {
    	$this->app = $app; // setup $app
        if($app->contactConfirmationEmail)              $this->confirmationEmail            = $app->contactConfirmationEmail;
        if($app->contactConfirmationEmailContent)       $this->confirmationEmailContent     = $app->contactConfirmationEmailContent;
        if($app->contactOrganizationName)               $this->orgainzationName             = $app->contactOrganizationName;
	    if($app->contactEmail)                          $this->email                        = $app->contactEmail; 
    }

	/**
	 * Post router
	 */
	function getRouter()
	{

		return true;
	}

	/**
	 * Post router
	 */
	function postRouter()
	{
		switch(strtolower($_POST['action'])) {
	        case "contact":
    	    	$this->processContactForm($_POST);
				break;

		}
		return true;
	}

	/**
	 * Process Contact Form
	 * A basic and generic contact form for a website
	 */
	public function processContactForm($post) 
	{
		// Validation
		if(!isset($post['name']) || !isset($post['email']) || !isset($post['message'])) {
			$_SESSION['alertStatus'] = 'error';
			$_SESSION['alertMsg'] = 'Please enter required fields.';
			return false;
		}  

		//
		if($post['name'] == "") {
			$_SESSION['alertStatus'] = 'error';
			$_SESSION['alertMsg'] = 'Please enter your name.';
			return false;
		}

		//
		if($post['email'] == "" || !utility::validateEmail($post['email'])) {
			$_SESSION['alertStatus'] = 'error';
			$_SESSION['alertMsg'] = 'Please enter a valid email.';
			return false;
		}

		//
		if($post['message'] == "") {
			$_SESSION['alertStatus'] = 'error';
			$_SESSION['alertMsg'] = 'Please enter a message.';
			return false;
		}

		// Send Emails
		// Email contact form to us
		$to       = $this->contactEmail;
		$fname    = "GoReturnMe";
		$from     = $post['email'];
		$fromName = $post['name'];
		$subj     = "Contact form submission from ".$this->domain;
		$message  = $post['message'];
		utility::mail($to,$fname,$from,$fromName,$subj,$message);

		// Email contact form confirmation to sender
		$to       = $post['email'];
		$fname    = $post['name'];
		$from     = "DoNotReply@".$this->domain;
		$fromName = $this->companyName;
		$subj     = $this->domain." Received Your Contact Submission. Thank You.";
		$message  = $this->contactConfEmail;
		utility::mail($to,$fname,$from,$fromName,$subj,$message);

		// Redirect to prevent form re-submission
		$_SESSION['alertStatus'] = 'success';
		$_SESSION['alertMsg'] = 'We have received your message. Thanks!';
		header("Location: /".$this->app->page);
		die;
	}
}
