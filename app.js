$(document).ready(function() {
    
    //Create User    
   $("#createUser").click(function(){
            var firstname = $("#firstName").val();
            var lastname  = $("#lastName").val();
            var password  = $("#password").val();
            var email     = $("#email").val();
            var phone     = $("#phone").val();
            var language  = $("#language").val();
            var countryId = $("#countryId").val();
            var imagePath = $("#imagePath").val();
        
        $.ajax({
            // url: "http://armdeveloper.hol.es/restexample/api/CreateUser",
            url: "http://localhost/restexample/api/CreateUser",
            type: 'POST',
            dataType: 'json',
            data:{firstname:firstname,lastname:lastname,password:password,email:email,phone:phone,language:language,countryId:countryId,imagePath:imagePath},
            success: function (result) {
                console.log(result);
                
                $("#createResult").html(JSON.stringify(result));

            },
            error: function (error) {
                console.log(error);
                $("#createResult").html(JSON.stringify(error.responseText));  
            }
        });
   });

    //ActivateUser
    $("#activate").click(function(){
        
        var verifyToken = $('#verifyToken').val();
            
            $.ajax({
                // url: "http://armdeveloper.hol.es/restexample/api/ActivateUser",
                url: "http://localhost/restexample/api/ActivateUser",
                type: 'POST',
                dataType: 'json',
                data:{verifyToken:verifyToken},
                success: function (result) {
                    console.log(result);
                    
                    $("#userID").html(JSON.stringify(result));

                },
                error: function (error) {
                    console.log(error);
                    $("#userID").html(JSON.stringify(error.responseText));  
                }
            });
    });

    //ResendKeyActivateUser
     $("#ResendKey").click(function(){
        var userID = $('#userID').val();
            
        $.ajax({
            // url: "http://armdeveloper.hol.es/restexample/api/ResendKeyActivateUser",
            url: "http://localhost/restexample/api/ResendKeyActivateUser",
            type: 'POST',
            dataType: 'json',
            data:{userID:userID},
            success: function (result) {
                console.log(result);
               
                $("#showResendKey").html(JSON.stringify(result));
            },
            error: function (error) {
                console.log(error);
                $("#showResendKey").html(JSON.stringify(error.responseText));  
            }
        });
    });

    //UserAuth
     $("#UserAuth").click(function(){
        var userID   = $('#userIdVal').val();            
        var password = $('#passVal').val();           
            $.ajax({
                // url: "http://armdeveloper.hol.es/restexample/api/UserAuth",
                url: "http://localhost/restexample/api/UserAuth",
                type: 'POST',
                dataType: 'json',
                data:{userID:userID,password:password},
                success: function (result) {
                    console.log(result);                   
                    $("#showUserAuth").html(JSON.stringify(result));
                },
                error: function (error) {
                    console.log(error);
                    $("#showUserAuth").html(JSON.stringify(error.responseText));  
                }
            });
    });

    //UpdateUserInfo
    $("#updateUser").click(function(){
        var userID    = $('#userIDUpdate').val();            
        var firstName = $("#firstNameUpdate").val();
        var lastName  = $("#lastNameUpdate").val();
        var phoneNo   = $("#phoneUpdate").val();
        var language  = $("#languageUpdate").val();
        var country   = $("#countryIdUpdate").val();
        var image     = $("#imagePathUpdate").val();
        
        //Passing parameters
        var userinfo       = {};
        userinfo.userID    = userID;
        userinfo.firstName = firstName;
        userinfo.lastName  = lastName;
        userinfo.phoneNo   = phoneNo;
        userinfo.language  = language;
        userinfo.country   = country;
        userinfo.image     = image;
        
        $.ajax({
            // url: "http://armdeveloper.hol.es/restexample/api/UpdateUserInfo",
            url: "http://localhost/restexample/api/UpdateUserInfo",
            type: 'PUT',
            dataType: 'json',
            data:{userinfo:userinfo},            
            success: function (result) {
                console.log(result);
                $("#showUserUpdate").html(JSON.stringify(result));
            },
            error: function (error) {
                console.log(error);
                $("#showUserUpdate").html(JSON.stringify(error.responseText));  
            }
        });
    });

    //Get Functions
   $('#send-get').click(function(){
        var method = $(".method-name").val();
        var id = Number($(".method-id").val());
        if(method != ''){    
            $.ajax({
                // url: "http://armdeveloper.hol.es/restexample/api/"+method+"/"+id,
                url: "http://localhost/restexample/api/"+method+"/"+id,
                type: 'GET',
                dataType: 'json',
                success: function (result) {
                    console.log(result);
                    
                    $("#show-result").html(JSON.stringify(result));

                },
                error: function (error) {
                    console.log(error);
                    $("#show-result").html(JSON.stringify(error.responseText));  
                }
            });
        }
   });

   //Search
   $('#search').click(function(){
        var serachText = $("#serachtext").val();
        var offset = $("#offset").val();
        var limit  = $("#limit").val();
        $.ajax({
            // url: "http://armdeveloper.hol.es/restexample/api/SearchProductsByText",
            url: "http://localhost/restexample/api/SearchProductsByText",
            type: 'POST',
            dataType: 'json',
            data:{serachText:serachText,offset:offset,limit:limit},
            
            success: function (result) {
                console.log(result);
                
                $("#search-result").html(JSON.stringify(result));

            },
            error: function (error) {
                console.log(error);
                $("#search-result").html(JSON.stringify(error.responseText));  
            }
        });
   });

   //getcoupon
   $('#cupon').click(function(){
        var userid = $("#couponuser").val();
        var offset = $("#couponoffset").val();
        // var limit  = $("#couponlimit").val();
        var expireflag  = $("#ExpireFlag").val();
        
        $.ajax({
            // url: "http://armdeveloper.hol.es/restexample/api/SearchProductsByText",
            url: "http://localhost/restexample/api/GetCouponByUser",
            type: 'POST',
            dataType: 'json',
            data:{userid:userid,offset:offset,expireflag:expireflag},
            
            success: function (result) {
                console.log(result);
                $("#show-cupon").html(JSON.stringify(result));
            },
            error: function (error) {
                console.log(error);
                $("#show-cupon").html(JSON.stringify(error.responseText));  
            }
        });
   });

   //GetTotalPrice
   $('#GetTotalPrice').click(function(){
        var userid = $("#GetTotalPriceuser").val();
        var pID = Number($("#GetTotalPriceID").val());
        var amount = $("#GetTotalPriceAmount").val();
        //Passing ProductID as key and Amount as value
        var productAmount  = {};
        productAmount[pID] = amount;
        $.ajax({
            // url: "http://armdeveloper.hol.es/restexample/api/SearchProductsByText",
            url: "http://localhost/restexample/api/GetTotalPrice",
            type: 'POST',
            dataType: 'json',
            data:{userid:userid,productAmount:productAmount},
            
            success: function (result) {
                console.log(result);
                $("#show-GetTotalPrice").html(JSON.stringify(result));
            },
            error: function (error) {
                console.log(error);
                $("#show-GetTotalPrice").html(JSON.stringify(error.responseText));  
            }
        });
   });

   //GetTotalPrice
   $('#PurchaseCoupon').click(function(){
        var userid = $("#PurchaseCouponuser").val();
        var productID =$("#PurchaseCouponID").val();
        var phoneEmail = $("#PurchaseCouponemail").val();
        var message = $("#PurchaseCouponmessage").val();
        //Passing ProductID as key and Amount as value
        $.ajax({
            // url: "http://armdeveloper.hol.es/restexample/api/SearchProductsByText",
            url: "http://localhost/restexample/api/PurchaseCoupon",
            type: 'POST',
            dataType: 'json',
            data:{userid:userid,productID:productID,phoneEmail:phoneEmail,message:message},
            
            success: function (result) {
                console.log(result);
                $("#show-PurchaseCoupon").html(JSON.stringify(result));
            },
            error: function (error) {
                console.log(error);
                $("#show-PurchaseCoupon").html(JSON.stringify(error.responseText));  
            }
        });
   });

   //SendGift
   $('#SendGift').click(function(){
        var userid = $("#SendGiftuser").val();
        var couponID = $("#SendGiftID").val();
        var phone = $("#SendGiftphone").val();
        var email = $("#SendGiftemail").val();
        var message = $("#SendGiftmessage").val();
        //Passing ProductID as key and Amount as value
        $.ajax({
            // url: "http://armdeveloper.hol.es/restexample/api/SearchProductsByText",
            url: "http://localhost/restexample/api/SendGift",
            type: 'POST',
            dataType: 'json',
            data:{userid:userid,couponID:couponID,phone:phone,email:email,message:message},
            
            success: function (result) {
                console.log(result);
                $("#show-SendGift").html(JSON.stringify(result));
            },
            error: function (error) {
                console.log(error);
                $("#show-SendGift").html(JSON.stringify(error.responseText));  
            }
        });
   });

});