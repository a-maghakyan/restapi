<?php 

class RestFulApi {

	/**
	 * Host name
	 * @var string
	 */
	private $host;
	
	/**
	 * Database name
	 * @var string
	 */
    private $dbname;
    
    /**
     * Database user name
     * @var string
     */
    private $user;

    /**
     * Database password
     * @var integer
     */
    private $password;

    /**
     * Connection
     * @var resours
     */
    private $dbh;
	
	/**
	 * Accept type $requestContentType
	 * @var string
	 */
	private $requestContentType;

	/**
	 * @var string
	 */
	private $httpVersion;

	/**
	 * @var integer
	 */
	private $ActiveFlag;

    public function __construct() {
    	$this->requestContentType = $_SERVER['HTTP_ACCEPT'];
    	$this->httpVersion        = "HTTP/1.1";
    	$this->host               = 'localhost';
    	$this->dbname             = 'rest_api';
    	$this->user               = 'root';
    	$this->password           = 123456;
    	$this->ActiveFlag		  = 1;
    	
    	/**
    	 * Connect to Database
    	 */
        $this->dbh = new mysqli($this->host, $this->user, $this->password, $this->dbname);
		if ($this->dbh->connect_errno) {
		    echo "Failed to connect to MySQL: (" . $this->dbh->connect_errno . ") " . $this->dbh->connect_error;
		}      
    }

    /**
     * Close Database connection
     */
    public function __destruct() {
       $this->dbh->close();
    }

    /**
     * Create New User
     */
    public function CreateUser(){
		$firstname   = $this->escapeString($_POST['firstname']);
		$lastname    = $this->escapeString($_POST['lastname']);
		$password    = $this->escapeString($_POST['password']);
		$email       = $this->escapeString($_POST['email']);
		$phone 	     = $this->escapeString($_POST['phone']);
		$language    = $this->escapeString($_POST['language']);
		$countryId   = intval($this->escapeString($_POST['countryId']));
		$imagePath   = $this->escapeString($_POST['imagePath']);
		$verifyToken = substr(md5(mt_rand()),0,30);

		if(empty($firstname) or empty($lastname) or empty($password) or empty($email) or empty($phone) or empty($countryId) or empty($imagePath)){
            $response = array('status' => "Fill all fields.");
            $statusCode = 400;
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
		else {
			//Check other inputs
			if(empty($language) or strlen($language) != 2){
				$language = "EN";
			}

			//Check if email or phone already exists
			$result = $this->dbh->query("SELECT * FROM `User` WHERE `Email` = '$email' or `Phone` = '$phone'");
			
			$rowcount = $this->dbh->affected_rows;
			
			if($rowcount > 0){
    			$response = array('status' => "This email or phnoe already exists.");
                $statusCode = 400;
                $this->send_XML_JSON($response, $this->requestContentType, $statusCode);			
			}
			else{   				
				//Hashing password
				$password_hash = password_hash($password, PASSWORD_DEFAULT);
				$addUser = $this->dbh->query("INSERT INTO `User`(`FirstName`, `LastName`,`Password`, `Email`, `Phone`, `ProfileImagePath`, `CountryID`, `Language`,	`VerifyToken`) 
					VALUES ('$firstname', '$lastname', '$password_hash', '$email', '$phone', '$imagePath', $countryId,'$language', '$verifyToken')");

				if($addUser){
					//Get Email
					$userId   = $this->dbh->insert_id;
					$query    = $this->dbh->query("SELECT `Email` FROM `User` WHERE `UserID` = $userId");
					$response = $query->fetch_assoc();
					$email    = $response['Email'];	
    				
    				//Send Email
					$this->sendEmail($email,$verifyToken);
					
					$response   = array('status' => "Please check your email for verification.");
                	$statusCode = 200;
                	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);		    	
				}
			}   						
		}
    }

    /**
     * Activate User
     * @param string $verifyToken
     */
    public function ActivateUser(){
    	$verifyToken = $this->escapeString($_POST['verifyToken']);

		$result = $this->dbh->query("UPDATE `User` SET `ActiveFlag`= $this->ActiveFlag 
									 WHERE `VerifyToken`='$verifyToken'");
		$result = $this->dbh->affected_rows;
		
		if($result > 0){
			//Get UserID
			$query = $this->dbh->query("SELECT `UserID` FROM `User` WHERE `VerifyToken` = '$verifyToken'");
			
			$response = $query->fetch_assoc();
            $statusCode = 200;
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);			
		}
		else {
			$response = array('status' => "Error activation.");
            $statusCode = 400;
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
		}		
    }

    /**
     * Send same random token key to current user’s email for activation 
     */
    public function ResendKeyActivateUser(){
    	$UserID = intval($this->escapeString($_POST['userID']));
  		
  		if($UserID != 0){
  			//Check if UserID exists
  			$query  = $this->dbh->query("SELECT * FROM `User` 
										 WHERE `UserID` = $UserID");
			$result = $this->dbh->affected_rows;
			
			
			if($result > 0){
	    		//Get VerifyToken and Email 
				$query  = $this->dbh->query("SELECT `VerifyToken`, `Email` FROM `User` 
											 WHERE `UserID` = $UserID AND `ActiveFlag` != $this->ActiveFlag");
				$result = $this->dbh->affected_rows;
				
				if($result > 0){
					$result = $query->fetch_all(MYSQLI_ASSOC);

					$verifyToken  = $result[0]['VerifyToken'];
					$email 		  = $result[0]['Email'];
					//Send email
					$this->sendEmail($email,$verifyToken);	

			    	$response = array('status' => "Please check your email for verification.");
                	$statusCode = 200;
                	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
				}
				else{
					$statusCode = 200;
					$response = array('status' => "User is activated arlady.");
		            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
				}
			}	
			else{
				$statusCode = 400;
				$response = array('status' => "UserID do not found.");
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
			}	
    	}	
    	else {
    		$statusCode = 400;
			$response = array('status' => "UserID can not be 0.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
    	}
    }

    /**
     * User Authorization
     */
    public function UserAuth(){
    	$userID   = intval($this->escapeString($_POST['userID']));
    	$password = $this->escapeString($_POST['password']);

    	$query = $this->dbh->query("SELECT `Password` FROM `User` 
									WHERE `UserID` = $userID 
									AND `ActiveFlag` = $this->ActiveFlag");
		$result = $this->dbh->affected_rows;
		
		if($result > 0){
			$response = $query->fetch_assoc();
			$hash = $response['Password'];

			if(password_verify($password, $hash)){
				$statusCode = 200;
				$response = array('status' => "User logged in successfully.");
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
    		}
    		else{
    			$statusCode = 400;
				$response = array('status' => "Login failed.");
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
    		}		
		}
		else {
    		$statusCode = 400;
			$response = array('status' => "Login failed.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
    }

    /**
     * Update User Information 
     */
    public function UpdateUserInfo(){
    	parse_str(file_get_contents("php://input"),$updateData);
    	$userId = intval($this->escapeString($updateData['userinfo']['userID']));
    	
    	if($userId != 0){
    		//Check if empty datas
    		function emptyArray($array){
    			if(empty($array)) return true;
    		}
			$checkArray = array_filter($updateData['userinfo'],'emptyArray');
    		if(count($checkArray) < 6) {
			    	$sql   = "UPDATE `User` SET";
					$comma   = " ";
					$updateList = array(
					    "firstName" => "`FirstName`",
					    "lastName"  => "`LastName`",
					    "phoneNo"   => "`Phone`",
					    "image"     => "`ProfileImagePath`",
					    "country"   => "`CountryID`",
					    "language"  => "`Language`"
					);
					
					foreach($updateData['userinfo'] as $key => $value) {
					    if( ! empty($value) && array_key_exists($key, $updateList)) {
					       $sql .= $comma . $updateList[$key] . " = '" . $this->escapeString($value) . "'";		       
					       $comma = ", ";		        
					    }
					}

					$comma = " ";
					$sql .= $comma."WHERE `UserID` = $userId";
					$query  = $this->dbh->query($sql);
					$result = $this->dbh->affected_rows;
				
					if($result > 0){
						$statusCode = 200;
						$response = array('status' => "Selected fields updated successfully.");
		            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
					} 
					else {
						$statusCode = 400;
						$response = array('status' => "Check UserID, phone number or language format (EN,RU).");
		            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
					}
				}
			else{
				$statusCode = 400;
				$response = array('status' => "Empty data.");
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
			}		
    	}
    	else {
    		$statusCode = 400;
			$response = array('status' => "UserID can not be 0.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
    	}
    } 

    /**
     * Get current user info for menu 
     */
    public function GetUserInfoForMenu(){
		$userId = intval($this->escapeString($_GET['id']));
		$query = $this->dbh->query("SELECT CONCAT(`FirstName`,' ',`LastName`) AS fullName, `ProfileImagePath`, `CountryName` FROM `User`,`Country` WHERE `UserID` = $userId AND `User`.`CountryID` = `Country`.`CountryID`");
		$result = $this->dbh->affected_rows;
		if($result > 0){
			$response = $query->fetch_assoc();
			$statusCode = 200;
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
		else {
			$statusCode = 400;
			$response = array('status' => "Nothing found.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
    }

    /**
     * Get User Detail Information 
     */
    public function GetUserDetailInfo(){
    	$query = $this->dbh->query("SELECT CONCAT(`FirstName`,' ',`LastName`) AS fullName, `ProfileImagePath`, `CountryName`, `Email`, `Phone` FROM `User`,`Country` WHERE `User`.`CountryID` = `Country`.`CountryID`");

		$result = $this->dbh->affected_rows;
		
		if($result > 0){
			$response = $query->fetch_all(MYSQLI_ASSOC);
			$statusCode = 200;
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
		else {
			$statusCode = 400;
			$response = array('status' => "Nothing found.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
    }

    /**
     * Show all brand image
     */
    public function GetAllBrand(){
    	$query = $this->dbh->query("SELECT `BrandImagePath` FROM `Brand`");
		$result = $this->dbh->affected_rows;
		
		if($result > 0){
			$response = $query->fetch_all(MYSQLI_ASSOC);
			$statusCode = 200;
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
		else {
			$statusCode = 400;
			$response = array('status' => "Nothing found.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}  	
    }

    /**
     * Search Products By Text
     */
    public function SearchProductsByText(){
    	$serachText = $this->escapeString($_POST['serachText']);
    	$offset     = intval($this->escapeString($_POST['offset']));
    	$limit      = intval($this->escapeString($_POST['limit'])); 
    	
    	if($serachText != '' or !empty($serachText)){
	    	$condition = "`IsPublished` = 1 AND `IsApproved` = 1 AND `LoyaltyRewardOnly` = 0 AND (`StockCount` > 0 OR `IsStockCount` = 0)";
	    	$comma = " ";
	    	$query = $this->dbh->query("SELECT `ProductName`, `ProductPrice`, `ProductCurrency`, `ProductSummaryDescription`,`FeatureImagePath` FROM `Product` WHERE `ProductName` LIKE '%$serachText%' AND".$comma.$condition.$comma."LIMIT $limit OFFSET $offset");
			$result = $this->dbh->affected_rows;
			
			if($result > 0){
				$response = $query->fetch_all(MYSQLI_ASSOC);
				$statusCode = 200;
	            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
			}
			else {
				$query = $this->dbh->query("SELECT `ProductName`, `ProductPrice`, `ProductCurrency`, `ProductSummaryDescription`,`FeatureImagePath` FROM `Product` WHERE `ProductName` NOT LIKE '%$serachText%' AND (`ProductSummaryDescription` LIKE '%$serachText%' OR `ProductDetailDescription` LIKE '%$serachText%') AND".$comma.$condition.$comma."LIMIT $limit OFFSET $offset");
				$result = $this->dbh->affected_rows;
				if($result > 0){
					$response = $query->fetch_all(MYSQLI_ASSOC);
					$statusCode = 200;
		            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
				}
				else {
					$statusCode = 400;
					$response = array('status' => "Nothing found.");
		            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);		
				}
			}
		}
		else{
			$statusCode = 400;
			$response = array('status' => "Nothing found.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
    }

    /**
     * Get Product Detail information
     */
    public function GetProductDetail(){
    	$productID = intval($this->escapeString($_GET['id']));
    	
    	if($productID != 0 or !empty($productID)){
    		$condition = "`ProductID` = $productID AND `IsPublished` = 1 AND `IsApproved` = 1 AND `LoyaltyRewardOnly` = 0 AND (`StockCount` > 0 OR `IsStockCount` = 0)";
	    	$comma = " ";

    		$query = $this->dbh->query("SELECT `ProductName`, `ProductPrice`, `FeatureImagePath`, `ProductDetailDescription`, `ProductTermsAndConditions`, `StockCount` FROM `Product` WHERE".$comma.$condition.$comma);
			$result = $this->dbh->affected_rows;
			
			if($result > 0){
				$response = $query->fetch_assoc();
				$statusCode = 200;
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
			}
			else {
				$statusCode = 400;
				$response = array('status' => "Nothing found.");
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
			}
    	}
    	else {
    		$statusCode = 400;
			$response = array('status' => "Nothing found.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
    	}
    }

    /**
     * Get All Theme
     */
    public function GetAllTheme(){
    	$query = $this->dbh->query("SELECT `ProductThemeName`, `` 
    								FROM `ProductTheme`");
		$result = $this->dbh->affected_rows;
		
		if($result > 0){
			$response = $query->fetch_all(MYSQLI_ASSOC);
			$statusCode = 200;
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
		else {
			$statusCode = 400;
			$response = array('status' => "Nothing found.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		} 
    }

    /**
     * Get Front Page Slide Show Products
     */
    public function GetFrontPageSlideShowProducts(){
    	$query = $this->dbh->query("SELECT `ProductName`,`ProductPrice`,`ProductSummaryDescription`, `SlideshowImagePath`     FROM `Product`,`ProductSlideshow` 
    								WHERE `ProductSlideshow`.`ProductID` = `Product`.`ProductID`");
		$result = $this->dbh->affected_rows;
		
		if($result > 0){
			$response = $query->fetch_all(MYSQLI_ASSOC);
			$statusCode = 200;
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
		else {
			$statusCode = 400;
			$response = array('status' => "Nothing found.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		} 
    }
    
    /**
     * Get Number Of Coupons To Be Redeemed 
     */
    public function GetNumberOfCouponsToBeRedeemed(){
    	$receiverID = intval($this->escapeString($_GET['id']));
    	
    	if($receiverID != 0 or !empty($receiverID)){
    		$query = $this->dbh->query("SELECT `CouponID` FROM `Coupon` WHERE `ReceiverID` = $receiverID AND `IsRedeemed` = 0");
			$result = $this->dbh->affected_rows;
			
			if($result > 0){
				$statusCode = 200;
            	$this->send_XML_JSON($result, $this->requestContentType, $statusCode);
			}
			else {
				$statusCode = 400;
				$response = array('status' => "Nothing found.");
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
			}
    	}
    	else {
    		$statusCode = 400;
			$response = array('status' => "Nothing found.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
    	}
    }

    /**
     * Get Coupon By User
     */
    public function GetCouponByUser(){
    	// SELECT `ProductName`,`ExpirationTime`,`SenderID`,`SendTimestamp`  FROM `Coupon` JOIN `Product` ON `Product`.ProductID = `Coupon`.ProductID WHERE `Coupon`.SenderID = 44 AND `Coupon`.IsRedeemed = 0 AND `Coupon`.IsRegifted = 0 AND (`Coupon`.`ExpirationTime` -now() <= 20) ORDER BY `ExpirationTime` ASC
    }

    /**
     * Get Coupon Detail
     */
    public function GetCouponDetail(){
    	$couponID = intval($this->escapeString($_GET['id']));
    	
    	if($couponID != 0 or !empty($couponID)){
    		$query = $this->dbh->query("SELECT `ProductName`, `ExpirationTime`, `QRSeed`, `ProductDetailDescription` 						 FROM `Coupon` JOIN `Product` ON `Product`.ProductID = `Coupon`.ProductID 							WHERE `CouponID` = $couponID AND `IsRedeemed` = 0 AND `IsRegifted` = 0");
			$result = $this->dbh->affected_rows;
			
			if($result > 0){
				$response = $query->fetch_assoc();	
				$statusCode = 200;
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
			}
			else {
				$statusCode = 400;
				$response = array('status' => "Nothing found.");
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
			}
    	}
    	else {
    		$statusCode = 400;
			$response = array('status' => "Nothing found.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
    	}	
    }

    /**
     * Get Total Price 
     */
    public function GetTotalPrice(){
    	$userid    = intval($this->escapeString($_POST['userid']));
    	$productID = intval($this->escapeString(key($_POST['productAmount'])));
    	$amount    = intval($this->escapeString($_POST['productAmount'][$productID]));
    	
    	if($userid != 0 or !empty($userid)){
    		$query = $this->dbh->query("SELECT SUM(`ProductPrice`)*$amount AS total FROM `Product` WHERE `ProductID` = $productID");
			$result = $this->dbh->affected_rows;
			
			if($result > 0){
				$response = $query->fetch_assoc(); 
				$statusCode = 200;
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
			}
			else {
				$statusCode = 400;
				$response = array('status' => "Nothing found.");
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
			}
    	}
    	else {
    		$statusCode = 400;
			$response = array('status' => "Nothing found.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
    	}
    }

    /**
     * Purchase Coupon 
     */
    public function PurchaseCoupon(){
    	$userid     = intval($this->escapeString($_POST['userid']));
    	$productID  = intval($this->escapeString($_POST['productID']));
    	$phoneEmail = $this->escapeString($_POST['phoneEmail']);
    	$message    = $this->escapeString($_POST['message']);

    	if($userid != 0 && $productID !=0){
    		$query = $this->dbh->query("INSERT INTO `Coupon`(`CouponID`, `ProductID`, `SenderID`, `ReceiverID`, `SendTimestamp`, `PublicKey`, `QRSeed`, `IsRedeemed`, `ReceiverDisplayName`, `PersonalMessage`, `IsRegifted`, `UsedTime`, `ExpirationTime`, `IsRead`) VALUES (null,$productID,$userid,$userid,null,'','',0,'','$message',0,null,null,0)");
			$result = $this->dbh->affected_rows;
			
			if($result > 0){
				$response = array('status' => "success"); 
				$statusCode = 200;
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
			}
			else {
				$statusCode = 400;
				$response = array('status' => "error");
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
			}
    	}
    	else {
    		$statusCode = 400;
			$response = array('status' => "error");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
    	}	
    }

    /**
     * Send Gift
     */
    public function SendGift(){
    	$userid     = intval($this->escapeString($_POST['userid']));
    	$couponID   = intval($this->escapeString($_POST['couponID']));
    	$phone      = $this->escapeString($_POST['phone']);
    	$email      = $this->escapeString($_POST['email']);
    	$message    = $this->escapeString($_POST['message']);

    	if($userid != 0 && $couponID !=0 && !empty($email) && !empty($phone)){
    		//Get RecipientID
    		$query = $this->dbh->query("SELECT `UserID` as `recipientID`, `ProductID` FROM `User`, `Coupon` WHERE `Email` = '$email' AND `Phone` = '$phone' AND `CouponID` = $couponID");
			$result = $this->dbh->affected_rows;			
			if($result > 0){
				$response    = $query->fetch_assoc();
				$recipientID = $response['recipientID'];
				$productID   = $response['ProductID'];
				
				//Update Coupon IsRegifted = true
				$query = $this->dbh->query("UPDATE `Coupon` SET `IsRegifted` = 1 WHERE `CouponID` = $couponID");
				$result = $this->dbh->affected_rows;
				if($result > 0){
					//Insert new record in Coupon table 
					$query = $this->dbh->query("INSERT INTO `Coupon`(`CouponID`, `ProductID`, `SenderID`, `ReceiverID`, `SendTimestamp`, `PublicKey`, `QRSeed`, `IsRedeemed`, `ReceiverDisplayName`, `PersonalMessage`, `IsRegifted`, `UsedTime`, `ExpirationTime`, `IsRead`) 
						VALUES (null,$productID,$userid,$recipientID,null,'','',0,'','$message',0,null,null,0)");
					$result = $this->dbh->affected_rows;
					if($result > 0){
						$statusCode  = 200;
						$response = array('status' => "success"); 
		            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);		
					}
					else {
						$statusCode = 400;
						$response = array('status' => "error");
            			$this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
					}
				}
				else {
					$statusCode = 400;
					$response = array('status' => "Check CouponID.");
            		$this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
				}
			}
			else {
				$statusCode = 400;
				$response = array('status' => "Check Phone, Email or CouponID.");
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
			}
    	}
    	else {
    		$statusCode = 400;
			$response = array('status' => "Empty Data.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
    	}
    }

    /**
     * Send Email
     * @return boolean
     */
    public function sendEmail($email,$verifyToken){
	    $to      = $email;
	    $subject = "Complate registration for Site name.";
	    $from    = 'example@gmail.com';
	    $message = 'For completing the registration please click on active URL <a href=/restexample/verify.php?verifyToken='.$verifyToken.'>Click here</a> .';
	    $headers = "From: <".$from.">";
	    mail($to,$subject,$message,$headers);
    }

    /**
     * Escapes special characters in a string for use in an SQL statement:
     * Convert the predefined characters "<" (less than) and ">" (greater than) to HTML entities:
     * Remove spaces
     * @param string 
     * @return string
     */
    public function escapeString($string){
    	return mysqli_real_escape_string($this->dbh, htmlspecialchars(trim($string)));
    }

	/**
	 * Cehck if mthod exists
	 * @param string $method
	 */
	public function ErrorMessage($method){
		echo json_encode(array("ErrorMessage"=>$method." mothod doesn't exists."));
	}

	/**
	 * @param  array $responseData  
	 * @return json               
	 */
	public function encodeJson($responseData) {
		$jsonResponse = json_encode($responseData);
		return $jsonResponse;		
	}

	/**
	 * @param  array $responseData 
	 * @return xml
	 */
	public function encodeXml($responseData) {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>';
		$xml .= "<products>";

		if($this->isMultiArray($responseData)){
			foreach($responseData as $val) {
				foreach ($val as $key => $value) {
		            $xml .= "<".$key.">";
		            $xml .= $value;
		            $xml .= "</".$key.">";
				}
	    	}	
		}
		else{				
			foreach($responseData as $key=>$value) {
	            $xml .= "<".$key.">";
	            $xml .= $value;
	            $xml .= "</".$key.">";
		    }
		}
		$xml .= "</products>";		
		return $xml;		
	}

	/**
	 * Create JSON or XML
	 * @param  array $response           
	 * @param  accept type $requestContentType 
	 * @param  code status $statusCode 
	 * @return JSON or XML                     
	 */
	public function send_XML_JSON($response, $requestType, $statusCode){

		$this ->setHttpHeaders($requestType, $statusCode);
		if(strpos($requestType,'application/json') !== false){
			$outResponse = $this->encodeJson($response);
			echo $outResponse;			
		} 
        else if(strpos($requestType,'application/xml') !== false){
			$outResponse = $this->encodeXml($response);
			echo $outResponse;			
		}
	}

	/**
	 * Check if array is multidimensional
	 * @param  array  $array
	 * @return boolean
	 */
	public function isMultiArray($array){
		$checkArray = array_filter($array,'is_array');
    	if(count($checkArray) > 0) return true;
	}

	/**
	 * Set Http Headers 
	 * @param string $contentType 
	 * @param int $statusCode  
	 */
	public function setHttpHeaders($contentType, $statusCode){		
		$statusMessage = $this -> getHttpStatusMessage($statusCode);
		
		header($this->httpVersion." ". $statusCode ." ". $statusMessage);		
		header("Content-Type:". $contentType);
	}
	
	/**
	 * Get Http Status Message
	 * @param  int $statusCode 
	 * @return string
	 */
	public function getHttpStatusMessage($statusCode){
		$httpStatus = array(
			100 => 'Continue',  
			101 => 'Switching Protocols',  
			200 => 'OK',
			201 => 'Created',  
			202 => 'Accepted',  
			203 => 'Non-Authoritative Information',  
			204 => 'No Content',  
			205 => 'Reset Content',  
			206 => 'Partial Content',  
			300 => 'Multiple Choices',  
			301 => 'Moved Permanently',  
			302 => 'Found',  
			303 => 'See Other',  
			304 => 'Not Modified',  
			305 => 'Use Proxy',  
			306 => '(Unused)',  
			307 => 'Temporary Redirect',  
			400 => 'Bad Request',  
			401 => 'Unauthorized',  
			402 => 'Payment Required',  
			403 => 'Forbidden',  
			404 => 'Not Found',  
			405 => 'Method Not Allowed',  
			406 => 'Not Acceptable',  
			407 => 'Proxy Authentication Required',  
			408 => 'Request Timeout',  
			409 => 'Conflict',  
			410 => 'Gone',  
			411 => 'Length Required',  
			412 => 'Precondition Failed',  
			413 => 'Request Entity Too Large',  
			414 => 'Request-URI Too Long',  
			415 => 'Unsupported Media Type',  
			416 => 'Requested Range Not Satisfiable',  
			417 => 'Expectation Failed',  
			500 => 'Internal Server Error',  
			501 => 'Not Implemented',  
			502 => 'Bad Gateway',  
			503 => 'Service Unavailable',  
			504 => 'Gateway Timeout',  
			505 => 'HTTP Version Not Supported');
		return ($httpStatus[$statusCode]) ? $httpStatus[$statusCode] : $status[500];
	}
}
?>