<?php

/**
* PHPMailer Library for Codeigniter
*
*
* @package PHPMailer Wrapper
* @version 1.0
* @author Michael Heap
* @license MIT License
* @copyright 2011 Prime Accounts
* @link http://www.primeaccounts.com
*/
class PHPMailer_Email
{

	private $_config, $_recipients, $_message, $_from, $_reply_to, $_attach, $_phpm;
	

	function __construct($conf = array())
	{

		if ( ! class_exists("PHPMailer"))
		{
			show_error('PHPMailer is not loaded');
		}

		$this->_ci = &get_instance();
		$this->_ci->load->helper("email_helper");

		$this->initialize($conf);
		$this->clear();
	}

	function initialize($conf)
	{
		$this->_config = $conf;

		$this->_phpm = new PHPMailer();
		$this->_phpm->isHTML();
		$this->_phpm->isSMTP();

		$this->_phpm->SMTPAuth = false; 
		$this->_phpm->Timeout = $this->_config['smtp_timeout'];

		$this->_phpm->Host = $this->_config['smtp_host'];
		$this->_phpm->Username = $this->_config['smtp_user'];
		$this->_phpm->Password = $this->_config['smtp_pass'];
		$this->_phpm->Port = $this->_config['smtp_port'];

	}

	function to($recipients)
	{
		$this->_add_recipient("to", $recipients);
	}

	function cc($recipients)
	{
		$this->_add_recipient("cc", $recipients);
	}

	function bcc($recipients)
	{
		$this->_add_recipient("bcc", $recipients);
	}

	function from($email, $name = null)
	{
		if (is_null($name))
		{
			$name = $email;
		}

		$this->_from = array( "name" => $name, "email" => $email);
	}

	function reply_to($email, $name = null)
	{
		if (empty($email))
		{
			show_error("CI PHPMailer: No Reply To Address Provided");
		}

		if (is_null($name))
		{
			$name = $email;
		}

		if (is_array($email))
		{
			foreach ($email as $name => $e)
			{
				if ( ! valid_email($e))
				{
					show_error("CI PHPMailer: Invalid Email");
				}
				$this->_reply_to[] = $name.' <'.$e.'>';
			}
			return;
		}

		if ( ! valid_email($email))
		{
			show_error("CI PHPMailer: Invalid Email");
		}
		$this->_reply_to[] = $name.' <'.$email.'>';
	}

	function subject($sub)
	{
		$this->_message['subject'] = $sub;
	}

	function message($message)
	{
		$this->_message['body_html'] = $message;
	}

	function set_alt_message($message)
	{
		$this->_message['body_text'] = $message;
	}

	function clear( $do_attach = FALSE)
	{
		$this->_recipients = array("to" => array(), "cc" => array(), "bcc" => array());
		$this->_message = array( "subject" => "", "body_html" => "", "body_text" => "");

		$this->_phpm->ClearAllRecipients();
		$this->_phpm->ClearReplyTos();

		if ( $do_attach ){
			$this->_attach = array();
			$this->_phpm->ClearAttachments();
		}
	}

	function send()
	{

		if ($this->_message['subject'] == "")
		{
			show_error("CI PHPMailer: Missing Subject");
		}

		// Fix the data so it's actually fine

		// Message
		if ($this->_message['body_text'] == "")
		{
			$this->_message['body_text'] = strip_tags(  str_replace("<br>", "\n", $this->_message['body_html']) );
		}

		if ($this->_message['body_html'] == "")
		{
			$this->_message['body_html'] = $this->_message['body_text'];
		}

		if ($this->_message['body_text'] == "")
		{
			show_error("CI PHPMailer: Missing Body Content");
		}

		// Recipients
		if (empty($this->_recipients["to"]))
		{
			show_error("CI PHPMailer: No recipient specified in the To field");
		}

		// Additional Options
		$opt = array();

		// Reply to?
		if ( ! empty($this->_reply_to))
		{
			$this->_phpm->AddReplyTo($this->_reply_to);
		}

		// Attachments?
		foreach ($this->_attach as $a)
		{
			$this->_phpm->AddAttachment($a);
		}

		// Actually send via PHPMailer

		// From
		$this->_phpm->SetFrom($this->_from['email'],$this->_from['name']);

		// To
		foreach ($this->_recipients["to"] as $to)
		{
			$this->_phpm->addAddress($to);
		}

		// cc/bcc

		// Content
		$this->_phpm->Body = $this->_message['body_html'];
		$this->_phpm->AltBody = $this->_message['body_text'];

		$response = $this->_phpm->Send();

		if (!$response){
			echo "Mailer Error: " . $this->_phpm->ErrorInfo . "<br />";
		}

		// Success?
		$resp = (object) array(
			"success" => (bool) $response,
		);

		return $resp->success;		
	}

	function attach( $path )
	{
		$this->_attach[] = $path;
	}

	function print_debugger()
	{
		
	}

	// CI Compat
	function set_newline($newline = "\n")
	{
		if ($newline != "\n" AND $newline != "\r\n" AND $newline != "\r")
		{
			$this->newline	= "\n";
			return;
		}

		$this->newline	= $newline;
	}


	// Private

	function _add_recipient($type, $recipients)
	{
		if (empty($recipients))
		{
			show_error("CI PHPMailer: No Recipients Provided");
		}

		if (is_array($recipients))
		{
			foreach($recipients as $recip)
			{
				if ( ! valid_email($recip))
				{
					show_error("CI PHPMailer: Invalid Email");
				}
				$this->_recipients[$type][] = $recip;
			}
			return;
		}

		if ( ! valid_email($recipients))
		{
			show_error("CI PHPMailer: Invalid Email");
		}
		$this->_recipients[$type][] = $recipients;
	}
}
