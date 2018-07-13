<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 

include_once FCPATH.'/vendor/autoload.php';

class Aws_dynamodb {  

	private $ci;
	private $aws_sdk;
	private $dynamodb;
	private $marshaler;
	 
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
		$this->dynamodb = $this->aws_sdk->createDynamoDb();
		$this->marshaler = new Aws\DynamoDb\Marshaler();
	}

	public function batchGetItem($table, $data, $consistent=TRUE)
	{		
		try {
			$batch_data = array(
				'RequestItems'=>array(
					$table=>array(
						'ConsistentRead' => $consistent,
						'Keys' => array(),
					)
				)
			);

			foreach ( $data as $batch_item ) {
				$batch_data['RequestItems'][$table]['Keys'][] = $this->marshaler->marshalItem($batch_item);
			}

			$result = $this->dynamodb->batchGetItem($batch_data);

			if ( isset($result['Responses'][$table]) ) {
				return $result['Responses'][$table];
			}
			else {
				$this->_logError( __FUNCTION__, json_encode($data) );
			}
		}
		catch ( Exception $e ) {
			$this->_logError( __FUNCTION__, $e->getMessage() );
		}

		return FALSE;
	}

	public function getCount($table)
	{
		try {
			$result = $this->dynamodb->describeTable(
				array(
					'TableName' => $table,
				)
			);

			if( isset($result['Table']['ItemCount']) ) {
				return $result['Table']['ItemCount'];
			}
			else {
				$this->_logError( __FUNCTION__, $table );
			}
		}
		catch ( Exception $e ) {
			$this->_logError( __FUNCTION__, $e->getMessage() );
		}

		return 0;	
	}

	public function getItem($table, $data, $consistent=TRUE)
	{		
		try {
			$result = $this->dynamodb->getItem(
				array(
					'TableName' => $table,
					'ConsistentRead' => $consistent,
					'Key' => $this->marshaler->marshalItem($data),
				)
			);

			if( isset($result['Item']) ){
				return $result['Item'];
			}
			else {
				$this->_logError( __FUNCTION__, json_encode($data) );
			}
		}
		catch ( Exception $e ) {
			$this->_logError( __FUNCTION__, $e->getMessage() );
		}

		return FALSE;
	}

	public function putItem($table, $data)
	{		
		try {
			$result = $this->dynamodb->putItem(array(
				'TableName' => $table,
				'Item' => $this->marshaler->marshalItem($data),
			));
		}
		catch ( Exception $e) {
			$this->_logError( __FUNCTION__, $e->getMessage() );
		}
	}

	private function _logError($method, $detail='')
	{
		$message = implode('->', array(get_class($this), $method)) . ': ' . $detail;
		
		log_message('error', $message );
	}

}
