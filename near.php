<?
namespace Near;

Class Near{
	public static function GetNearRpcData($method){

		$message = json_encode(array('jsonrpc' => '2.0', 'id' => "dontcare", 'method' => $method, 'params' => [null]));
		$requestHeaders = ['Content-type: application/json'];

		$ch = curl_init("https://rpc.betanet.nearprotocol.com");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
		
		$json = curl_exec($ch);
		curl_close($ch);

		$data = json_decode($json, true);
		return $data;
	}
}