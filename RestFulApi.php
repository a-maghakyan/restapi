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

	/**
	 * @var string
	 */
	private $serverName;

    public function __construct() {
		$this->serverName         = "http://" . $_SERVER['SERVER_NAME'];
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
    public function CreateUser_post(){
		$firstname   = $this->escapeString($_POST['firstname']);
		$lastname    = $this->escapeString($_POST['lastname']);
		$password    = $this->escapeString($_POST['password']);
		$email       = $this->escapeString($_POST['email']);
		$phone 	     = $this->escapeString($_POST['phone']);
		$language    = $this->escapeString($_POST['language']);
		$countryId   = intval($this->escapeString($_POST['countryId']));
		$imagePath   = $this->escapeString($_POST['imagePath']);
		$verifyToken = substr(md5(mt_rand()),0,30);
		$confirmCode = substr(md5(mt_rand()),0,5);

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
				$addUser = $this->dbh->query("INSERT INTO `User`(`FirstName`, `LastName`,`Password`, `Email`, `Phone`, `ProfileImagePath`, `CountryID`, `Language`,	`VerifyToken`, `VerifyPhone`) 
					VALUES ('$firstname', '$lastname', '$password_hash', '$email', '$phone', '$imagePath', $countryId,'$language', '$verifyToken', '$confirmCode')");

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
    public function ActivateUser_post(){
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
    public function ResendKeyActivateUser_post(){
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
    public function UserAuth_post(){
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
    public function UpdateUserInfo_put(){
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
    public function SearchProductsByText_post(){
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

////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Get All Theme
     */
    public function GetAllTheme(){
    	$query = $this->dbh->query("SELECT `ProductThemeName`, `ProductThemeImage` 
    								FROM `ProductTheme` ORDER BY `ProductThemeOrder` ASC");
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
    								WHERE `ProductSlideshow`.`ProductID` = `Product`.`ProductID` 
    								ORDER BY `ProductSlideshowOrder` ASC");
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
    public function GetCouponByUser_post(){
    	$userid     = intval($this->escapeString($_POST['userid']));
    	$expireFlag = intval($this->escapeString($_POST['expireflag']));
    	$offset     = intval($this->escapeString($_POST['offset']));
    	//Check number of times regefted and set flag true or  false
    	$updateQuery = $this->dbh->query("UPDATE `Coupon`,`CouponRegift` SET `IsRegifted` = 0 WHERE `CouponRegift`.`NumTimesRegifted` > 10 AND `Coupon`.`CouponID` = `CouponRegift`.`CouponID`");

		//Select Coupon details
    	$sql = "SELECT `ProductName`, `ExpirationTime`, `SenderID`, `SendTimestamp`, `PersonalMessage`, `IsRegifted` FROM `Coupon` JOIN `Product` ON `Product`.ProductID = `Coupon`.ProductID WHERE `Coupon`.SenderID = $userid AND `Coupon`.IsRedeemed = 0 AND `Coupon`.IsRegifted = 0";
    	$addedSql = "AND (`Coupon`.`ExpirationTime` - now() <= 20) ORDER BY `ExpirationTime` ASC";
    	$comma = " ";
    	
    	if($expireFlag == 1){
    		$sql .= $comma.$addedSql;	
    	}

    	$query  = $this->dbh->query($sql);
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
    public function GetTotalPrice_post(){
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
    public function PurchaseCoupon_post(){
    	$userid     = intval($this->escapeString($_POST['userid']));
    	$productID  = intval($this->escapeString($_POST['productID']));
    	$message    = $this->escapeString($_POST['message']);
    	$publicKey  = substr(md5(mt_rand()),0,32).substr(md5(mt_rand()),0,32);
    	$QRSeed	    = substr(md5(mt_rand()),0,32).substr(md5(mt_rand()),0,32);
    	if($userid != 0 && $productID != 0){
    		$getNameQuery = $this->dbh->query("SELECT CONCAT(`FirstName`,' ',`LastName`) AS fullName FROM `User` WHERE `UserID` = $userid");
			$getName = $getNameQuery->fetch_assoc();
    		
    		//Get receiver name 
			$receiverName = $getName['fullName'];
    		
    		//Insert dsatas to Coupon table
    		$query = $this->dbh->query("INSERT INTO `Coupon`(`CouponID`, `ProductID`, `SenderID`, `ReceiverID`, `SendTimestamp`, `PublicKey`, `QRSeed`, `IsRedeemed`, `ReceiverDisplayName`, `PersonalMessage`, `IsRegifted`, `UsedTime`, `ExpirationTime`, `IsRead`) VALUES (null,$productID,$userid,$userid,null,'$publicKey','$QRSeed',0,'$receiverName','$message',0,null,null,0)");
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
    public function SendGift_post(){
    	$userid     = intval($this->escapeString($_POST['userid']));
    	$couponID   = intval($this->escapeString($_POST['couponID']));
    	$phone      = $this->escapeString($_POST['phone']);
    	$email      = $this->escapeString($_POST['email']);
    	$message    = $this->escapeString($_POST['message']);

    	if($userid != 0 && $couponID !=0 && !empty($email) && !empty($phone)){   		
    		//Get RecipientID
    		$query = $this->dbh->query("SELECT `UserID` AS `recipientID`, `ProductID`, CONCAT(`FirstName`,' ',`LastName`) AS fullName FROM `User`, `Coupon` WHERE `Email` = '$email' AND `Phone` = '$phone' AND `CouponID` = $couponID AND `SenderID` = $userid AND `ReceiverID` = $userid");
			$result = $this->dbh->affected_rows;
						
			if($result > 0){
				//Check Number of times regifted. Cannot regift a gift more than 10 times.
				$numTimesQuery = $this->dbh->query("SELECT `NumTimesRegifted` FROM `CouponRegift` WHERE `CouponID` = $couponID");
				$numTimes = $this->dbh->affected_rows;
				if($numTimes < 10){
					$response    = $query->fetch_assoc();
					$recipientID = $response['recipientID'];
					$productID   = $response['ProductID'];
					$fullName    = $response['fullName'];
					$publicKey  = substr(md5(mt_rand()),0,32).substr(md5(mt_rand()),0,32);
    				$QRSeed	    = substr(md5(mt_rand()),0,32).substr(md5(mt_rand()),0,32);
					
					//Update Coupon IsRegifted = true
					$query = $this->dbh->query("SELECT `CouponID` FROM `Coupon` WHERE `IsRegifted` = 0 AND `CouponID` = $couponID");
					$result = $this->dbh->affected_rows;
					if($result > 0){
						$query = $this->dbh->query("UPDATE `Coupon` SET `IsRegifted` = 1 WHERE `CouponID` = $couponID");
						$result = $this->dbh->affected_rows;
					}

					//Insert new record in Coupon table 
					$query = $this->dbh->query("INSERT INTO `Coupon`(`CouponID`, `ProductID`, `SenderID`, `ReceiverID`, `SendTimestamp`, `PublicKey`, `QRSeed`, `IsRedeemed`, `ReceiverDisplayName`, `PersonalMessage`, `IsRegifted`, `UsedTime`, `ExpirationTime`, `IsRead`) 
						VALUES (null,$productID,$userid,$recipientID,null,'$publicKey','$QRSeed',0,'$fullName','$message',0,null,null,0)");
					$result = $this->dbh->affected_rows;
					if($result > 0){

						//Save history
						$newCouponID = $this->dbh->insert_id;
						$query = $this->dbh->query("INSERT INTO `CouponRegift`(`CouponID`, `RegiftedTimestamp`, `RegiftedCouponID`, `NumTimesRegifted`) SELECT $couponID,null,$newCouponID,count(`NumTimesRegifted`)+1 FROM `CouponRegift` WHERE `CouponID` = $couponID");	
							                                                                                                     
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
					$response = array('status' => "This coupon can not send more than 10 times.");
            		$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
				}	
			}
			else {
				$statusCode = 400;
				$response = array('status' => "Check data of Recipient or CouponID.");
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
     * Get Coupon Link
     */
    public function GetCouponLink(){
    	$couponID  = intval($this->escapeString($_GET['id']));
    	$query = $this->dbh->query("SELECT `PublicKey` FROM `Coupon` WHERE `CouponID` = $couponID");
    		$result = $this->dbh->affected_rows;
    		if($result > 0){
				$getPublicKey = $query->fetch_assoc(); 
				$publicKey = $getPublicKey['PublicKey'];

				$statusCode = 200; 
				$response = $this->serverName."/LoadCouponQR.php?couponID=$couponID&pulicKey=$publicKey";
				$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
			}
    		else {
    			$statusCode = 400;
				$response = array('status' => "Incorrect CouponID.");
            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
    		}
    }
    
    /**
     * Get Complete Message
     */
    public function GetCompleteMessage_post(){
    	$userID       = intval($this->escapeString($_POST['userID']));
    	$couponID     = intval($this->escapeString($_POST['couponID']));
    	$recipientID  = intval($this->escapeString($_POST['recipientID']));
    	 
    	if($userID != 0 && $couponID != 0 && $recipientID != 0){
    		//Check if this coupon belongs to Curren User ($UserID)
	    	$checkQuery = $this->dbh->query("SELECT `CouponID` FROM `Coupon` WHERE `SenderID` = $userID AND `CouponID` = $couponID");
	    	$result = $this->dbh->affected_rows;
	    		if($result > 0){
		    		$query = $this->dbh->query("SELECT `ReceiverDisplayName`, `ProductName`, `Email`, `Phone` 
		    									FROM `Coupon`, `User`, `Product` 
		    									WHERE `Coupon`.`ProductID` = `Product`.`ProductID` AND `ReceiverID` = $recipientID AND `CouponID` = $couponID AND `UserID` = `ReceiverID`");
		    		$result = $this->dbh->affected_rows;
		    		if($result > 0){
		    			$statusCode = 200;
		    			$response = $query->fetch_assoc();
		            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);	
		    		}
		    		else {
		    			$statusCode = 400;
						$response = array('status' => "Check RecipientID.");
		            	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		    		}	    				
	    		}
	    		else {
	    			$statusCode = 400;
					$response = array('status' => "This coupon was not bought by the current user.");
		            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
	    		}
    	}
    	else {
    		$statusCode = 400;
			$response = array('status' => "Empty data.");
            $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
    	} 
    } 
    
    /**
     * Get User Coupon History
     */
    public function GetUserCouponHistory(){
    	$userID = intval($this->escapeString($_GET['id']));
    	
    	//Check if User exists 
    	$checkQuery = $this->dbh->query("SELECT `UserID` FROM `User` WHERE `UserID` = $userID");
	    	$result = $this->dbh->affected_rows;
	    	$response = array();
	    	if($result > 0){
		    	//Get Sent to coupon 
		    	$sentByquery = $this->dbh->query("SELECT `CouponID`, `SenderID` AS 'Sent by' FROM `Coupon` WHERE `ReceiverID` = $userID AND NOT `SenderID` = `ReceiverID` AND `IsRegifted` = 1");
				$result = $this->dbh->affected_rows;
				if($result > 0){
					$byResponse = $sentByquery->fetch_all(MYSQLI_ASSOC);
					$response['sentBy'] = $byResponse;
				}

				//Get Sent to coupon
				$sentToquery = $this->dbh->query("SELECT `CouponID`, `ReceiverID` AS 'Sent to' FROM `Coupon` WHERE `SenderID` = $userID AND NOT `SenderID` = `ReceiverID` AND `IsRegifted` = 1");
				$result = $this->dbh->affected_rows;
				if($result > 0){
					$toResponse = $sentToquery->fetch_all(MYSQLI_ASSOC);
					$response['sentTo'] = $toResponse;
				}

				//Get Bought by yourself
				$sentByYourself = $this->dbh->query("SELECT `CouponID`, `ReceiverID` AS 'Bought by yourself' FROM `Coupon` WHERE `ReceiverID` = $userID AND `SenderID` = `ReceiverID` AND `IsRegifted` = 1");
				$result = $this->dbh->affected_rows;
				if($result > 0){
					$yourselfResponse = $sentByYourself->fetch_all(MYSQLI_ASSOC);
					$response['yourself'] = $yourselfResponse;
				}

				$statusCode = 200;
        		$this->send_XML_JSON($response, $this->requestContentType, $statusCode);		
	    	}
	    	else {
				$statusCode = 400;
				$response = array('status' => "Check UserID.");
        		$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
			}
    }

    /**
     * Send Gift Request
     */
    public function SendRequest_post(){
    	$userID      = intval($this->escapeString($_POST['userID']));
    	$productID   = intval($this->escapeString($_POST['productID']));
    	$recipientID = intval($this->escapeString($_POST['recipientID']));
    	$message     = $this->escapeString($_POST['message']);
    	
    	if($userID != 0 && $productID != 0 && $recipientID != 0 && !empty($message)){
	    	$query = $this->dbh->query("INSERT INTO `ProductRequest`(`RequestID`, `ProductID`, `InitiateUserID`, `ReceiverUserID`, `PersonalMessage`, `IsRead`) VALUES (null,$productID,$userID,$recipientID,'$message',0)");
		    $result = $this->dbh->affected_rows;
		    if($result > 0){
		    	$statusCode = 200;
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
			$response = array('status' => "Empty data.");
	        $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
    	}
	}

	/**
	 * List Gift Requests
	 */
	public function ListGiftRequests_post(){
		$userID = intval($this->escapeString($_POST['userID']));
    	$offset = intval($this->escapeString($_POST['offset']));
    	$limit  = intval($this->escapeString($_POST['limit']));		

    	if($userID != 0){
	    	$query = $this->dbh->query("SELECT * FROM `ProductRequest` WHERE `ReceiverUserID` = $userID  LIMIT $limit OFFSET $offset");
		    $result = $this->dbh->affected_rows;
		    if($result > 0){
		    	$statusCode = 200;
				$response = $query->fetch_all(MYSQLI_ASSOC);
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
			$response = array('status' => "UserId can not be 0.");
	        $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
    	}
	}

	/**
	 * Delete Gift Request
	 */
	public function DeleteGiftRequest_delete(){
		parse_str(file_get_contents("php://input"),$getData);
		$requestID = intval($this->escapeString($getData['requestID']));
		
		if($requestID !=0){
			$query = $this->dbh->query("DELETE FROM `ProductRequest` WHERE `RequestID` = $requestID");
		    $result = $this->dbh->affected_rows;
		    if($result > 0){
		    	$statusCode = 200;
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
			$response = array('status' => "ReduestsID can not be 0.");
	        $this->send_XML_JSON($response, $this->requestContentType, $statusCode);
    	}
	}

	/**
	 * Get Loyalty Swipes
	 */
	public function GetLoyaltySwipes(){
		$userID = intval($this->escapeString($_GET['id']));
    	
    	$checkQuery = $this->dbh->query("SELECT `UserID` FROM `User` WHERE `UserID` = $userID");
	    $result = $this->dbh->affected_rows;
	    if($result > 0){
	    	$query = $this->dbh->query("SELECT `CompanyName`, `SwipesCount`, `NumberOfSwipesRequired`, `ProductName`  FROM `UserLoyaltyRewards`, `Product`, `UserLoyaltySwipes`, `Vendor` WHERE `Vendor`.`VendorID` = `UserLoyaltyRewards`.`VendorID` AND `UserLoyaltyRewards`.`ProductID` = `Product`.`ProductID` AND `UserLoyaltyRewards`.`LoyaltyID` = `UserLoyaltySwipes`.`LoyaltyID` AND `UserLoyaltySwipes`.`UserID` = $userID");
	    	$result = $this->dbh->affected_rows;
	    	if($result > 0){
	    		$statusCode = 200;
	    		$response = $query->fetch_all(MYSQLI_ASSOC);
        		$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
	    	}	
	    }
	    else {
	    	$statusCode = 400;
			$response = array('status' => "Check UserID.");
        	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
	    }
	}

	/**
	 * Get Loyalty Earned Rewards
	 */
	public function GetLoyaltyEarnedRewards(){
		$userID = intval($this->escapeString($_GET['id']));
    	
    	$checkQuery = $this->dbh->query("SELECT `UserID` FROM `User` WHERE `UserID` = $userID");
	    $result = $this->dbh->affected_rows;
	    if($result > 0){
	    	$query = $this->dbh->query("SELECT `CompanyName`,`EarnedRewardsCount` FROM `UserLoyaltyEarnedRewards`, `Vendor`, `UserLoyaltyRewards` WHERE `Vendor`.`VendorID` = `UserLoyaltyRewards`.`VendorID` AND `UserLoyaltyRewards`.`LoyaltyID` = `UserLoyaltyEarnedRewards`.`LoyaltyID` AND `UserLoyaltyEarnedRewards`.`UserID` = $userID");
	    	$result = $this->dbh->affected_rows;
	    	if($result > 0){
	    		$statusCode = 200;
	    		$response = $query->fetch_all(MYSQLI_ASSOC);
        		$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
	    	}	
	    }
	    else {
	    	$statusCode = 400;
			$response = array('status' => "Check UserID.");
        	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
	    }	
	} 

	/**
	 * Update User Phone Send Confirmation Code
	 */
	public function UpdateUserPhoneSendConfirmationCode_post(){
		$userID = intval($this->escapeString($_POST['userID']));
    	$phone  = $this->escapeString($_POST['phone']);

		$query  = $this->dbh->query("SELECT `Phone`, `VerifyPhone` FROM `User` WHERE `UserID` = $userID AND `Phone` = $phone");
    	$result = $this->dbh->affected_rows;
		
		if($result > 0){
			$result      = $query->fetch_all(MYSQLI_ASSOC);
			$sendPhone   = $result[0]['Phone'];
			$confirmCode = $result[0]['VerifyPhone'];

			//Send SMS
			// $AUTH_ID = '';

			// // Plivo AUTH TOKEN
			// $AUTH_TOKEN = '';

			// //SMS sender ID.
			// $src = '';

			// //SMS destination number
			// $dst = '';

			// //SMS text
			// $text = '';

			// $url = 'https://api.plivo.com/v1/Account/'.$AUTH_ID.'/Message/';
			// $data = array("src" => "$src", "dst" => "$dst", "text" => "$text");
			// $data_string = json_encode($data);
			// $ch = curl_init($url);
			// curl_setopt($ch, CURLOPT_POST, true);
			// curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			// curl_setopt($ch, CURLOPT_HEADER, true);
			// curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
			// curl_setopt($ch, CURLOPT_USERPWD, $AUTH_ID . ":" . $AUTH_TOKEN);
			// curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			// $response = curl_exec( $ch );
			// curl_close($ch);
			$statusCode = 200;
			$response = array('status' => "success");
        	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
		else {
			$statusCode = 400;
			$response = array('status' => "error");
        	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
	}

	/**
	 * Update User Phone Test Confirmation Code
	 */
	public function UpdateUserPhoneTestConfirmationCode_post(){
		$userID      = intval($this->escapeString($_POST['userID']));
    	$phone       = $this->escapeString($_POST['phone']);
    	$confirmCode = $this->escapeString($_POST['confirmCode']);

		$query = $this->dbh->query("SELECT `UserID` FROM `User` WHERE `UserID` = $userID AND `Phone` = $phone AND `VerifyPhone` = '$confirmCode'");
    	$result = $this->dbh->affected_rows;
		if($result > 0){
			$statusCode = 200;
			$response = array('status' => "success");
        	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
		else {
			$statusCode = 400;
			$response = array('status' => "error");
        	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
		}
	}

	/**
	 * Update User Phone
	 */
	public function UpdateUserPhone_post(){
		$userID = intval($this->escapeString($_POST['userID']));
    	$phone  = $this->escapeString($_POST['phone']);
    	
    	//Check if the phone already exists 
    	$getQuery = $this->dbh->query("SELECT `UserID` FROM `User` WHERE `Phone` = '$phone'");
    	$result   = $this->dbh->affected_rows;
		if($result > 0){
			$getUserID         = $getQuery->fetch_assoc();
			$deletePhoneUserID = $getUserID['UserID'];

			//Delete phone from old account	
			$deleteQuery = $this->dbh->query("UPDATE `User` SET `Phone` = '' WHERE `Phone` = '$phone'");
    		
    		$query  = $this->dbh->query("UPDATE `User` SET `Phone` = '$phone' WHERE `UserID` = $userID");
	    	$result = $this->dbh->affected_rows;
			if($result > 0){
				$statusCode = 200;
				$response = array('status' => "success", 'UserID' => $deletePhoneUserID, 'message' => "Must have a phone number." );
	        	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
			}
			else {
				$statusCode = 400;
				$response = array('status' => "error");
	        	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
			}	
		}
		else {
			$query  = $this->dbh->query("UPDATE `User` SET `Phone` = '$phone' WHERE `UserID` = $userID");
	    	$result = $this->dbh->affected_rows;
			if($result > 0){
				$statusCode = 200;
				$response = array('status' => "success");
	        	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
			}
			else {
				$statusCode = 400;
				$response = array('status' => "error");
	        	$this->send_XML_JSON($response, $this->requestContentType, $statusCode);
			}
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
		echo json_encode(array("ErrorMessage"=>"Mothod doesn't exists."));
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