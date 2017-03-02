<?php
require_once("RestFulApi.php");
	$request_method = $_SERVER["REQUEST_METHOD"];
	switch($request_method){
		case 'GET':
			if(isset($_GET['method'])){
				$response = new RestFulApi();
				$method   = $_GET['method'];
				if(method_exists($response, $method)) {
				    $response->$method();
				}
				else {
					$response->ErrorMessage($method);
				}
			}
			break;
		case 'POST':
			if(isset($_GET['method'])){
				$response = new RestFulApi();
				$method   = $_GET['method'];
				$method  .= '_post';
				if(method_exists($response, $method)) {
				    $response->$method();
				}
				else {				
					$response->ErrorMessage($method);
				}
			}
			break;
		case 'PUT':
			if(isset($_GET['method'])){ 
				$response = new RestFulApi();
				$method   = $_GET['method'];
				$method  .= '_put';
				if(method_exists($response, $method)) {
				    $response->$method();
				}
				else {
					$response->ErrorMessage($method);	
				}
			}			
			break;
		case 'DELETE':
			if(isset($_GET['method'])){ 
				$response = new RestFulApi();
				$method   = $_GET['method'];
				$method  .= '_delete';
				if(method_exists($response, $method)) {
				    $response->$method();
				}
				else {
					$response->ErrorMessage($method);	
				}
			}
			break;
		default:
			// Invalid Request Method
			header("HTTP/1.1 405 Method Not Allowed");
			break;
	}
?>
