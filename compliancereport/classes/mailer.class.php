<?php

class Mailer 
{
	private $from = "cfops@eclists.intel.com";
	private $from_name = "\"Cfengine Operations\"";
	private $reply_to = "cfops@eclists.intel.com";
	private $to = array();
	private $subject = "";
	private $message = "";
	private $attachment = "";
	private $attachment_filename = "";
	private $headers = "";
	private $uid = "";

	public function __construct( $subject , $message ) 
	{
		$this->subject = $subject;
		$this->message = $message;
		$this->uid = md5(uniqid(time()));
		
		$this->headers = "From: " . $this->from_name . " <" . $this->from . ">\r\n";
		$this->headers .= "Reply-To: " . $this->reply_to . "\r\n";
		$this->headers .= "MIME-Version: 1.0\r\n";
		$this->headers .= "Content-Type: multipart/mixed; boundary=\"" . $this->uid . "\"\r\n\r\n";
		$this->headers .= "This is a multi-part message in MIME format.\r\n";
		$this->headers .= "--" . $this->uid . "\r\n";
		$this->headers .= "Content-type:text/html; charset=iso-8859-1\r\n";
		$this->headers .= "Content-Transfer-Encoding: base64\r\n\r\n";
		$this->headers .= "[MAILCONTENT] \r\n\r\n";	
	}
	
	public function addRecipient( $person )
	{
		$this->to[] = $person;
	}
	
	public function embedPicture( $lookFor , $replaceWith )
	{
		$content = chunk_split( base64_encode( file_get_contents( $replaceWith ) ) );
		$name = basename( $replaceWith );
		
		$this->message = str_replace( $lookFor , "cid:$lookFor" , $this->message );
			
		$this->headers .= "--" . $this->uid . "\r\n";		
		$this->headers .= "Content-Type: image/jpeg; file_name = \"" . $name . "\"\r\n"; 
		$this->headers .= "Content-ID: <$lookFor>\r\n";
		$this->headers .= "Content-Transfer-Encoding: base64\r\n";		
		$this->headers .= "Content-Disposition: inline; filename=\"$name\"\r\n\r\n";
		$this->headers .= $content . "\r\n\r\n";
	}
	
	public function attachFile( $attachment = '' )
	{
		$content = chunk_split( base64_encode( file_get_contents( $attachment ) ) );
		$name = basename( $attachment );
		
		$this->headers .= "--" . $this->uid . "\r\n";
		$this->headers .= "Content-Type: application/octet-stream; name=\"" . $name . "\"\r\n"; 
		$this->headers .= "Content-Transfer-Encoding: base64\r\n";
		$this->headers .= "Content-Disposition: attachment; filename=\"" . $name . "\"\r\n\r\n";
		$this->headers .= $content . "\r\n\r\n";
	}

	public function mail() 
	{		
		$this->headers .= "--" . $this->uid . "--";
		
		$this->headers = str_replace( "[MAILCONTENT]" , chunk_split( base64_encode( $this->message ) ) , $this->headers );
		
		foreach( $this->to as $recipient )
		{
			@mail( $recipient , $this->subject , "" , $this->headers ); 				
		}
	}
}
