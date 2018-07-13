<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 

include_once FCPATH.'/vendor/autoload.php';

class Aws_ses {  

	private $ci;
	private $aws_sdk;
	private $ses;
	 
	public function __construct()
	{
		$this->ci =& get_instance();

		if ( !$this->ci->config->item('aws_access_key') ) {
			$this->ci->config->load('aws_sdk');
		}

		$this->aws_sdk = new Aws\Sdk(
			array(
				'credentials' => array(
					'key'		=> $this->ci->config->item('aws_access_key'),
					'secret'	=> $this->ci->config->item('aws_secret_key'),
				),
					'region'	=> $this->ci->config->item('aws_region_default'),
					'version'	=> 'latest',
				)
			);
		$this->ses = $this->aws_sdk->createSes();
	}

	function send($from, $to, $subject, $message, $reply_to=''){
		try {

			if ( !is_array($to) ) {
				$to = array($to);
			}

			$email = array(
				'Source' => $from,
				'Destination' => array(
					'ToAddresses' => $to,
				),
				'Message' => array(
					'Subject' => array(
						'Data' => $subject,
					),
					'Body' => array(
						'Html' => array(
							'Data' => $message,
						),
					),
				),
			);

			if ($reply_to) {
				if ( !is_array($reply_to) ) {
					$reply_to = array($reply_to);
				}
				
				$email['ReplyToAddresses'] = $reply_to;
			}

			$result = $this->ses->sendEmail($email);

			if(isset($result['MessageId'])){
				return TRUE;
			}
		}
		catch (SesException $e) {
			$this->_logError( __FUNCTION__, $e->getMessage() );
		}
		return FALSE;
	}

	private function _logError($method, $detail='')
	{
		$message = implode('->', array(get_class($this), $method)) . ': ' . $detail;
		
		log_message('error', $message );
	}

}
