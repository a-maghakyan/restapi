<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>Confirm Phone</title>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">	
	</head>
	<body>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-lg-offset-3">
				<label>Verification Phone</label>
				<div class="form-group">
					<label>UserID</label>
					<input type="number" class="form-control" id="verifyUser">
					<label>Phone</label>
					<input type="number" class="form-control" id="verifyPhone">
					<label>Verification Code</label>
					<input type="text" class="form-control" id="verifyCode">
					<button class="btn btn-success" id="activatephone">Verify my phone number</button>
				</div>
				<div id="show-phone"></div>
			</div>
		</div>

		<div class="row" id="update-phnoe" style="display:none">
			<div class="col-lg-6 col-md-6 col-lg-offset-3">
				<label>Updat Phone</label>
				<div class="form-group">
					<label>UserID</label>
					<input type="number" class="form-control" id="updateUser1">
					<label>New Phone</label>
					<input type="number" class="form-control" id="updatePhone">
					
					<button class="btn btn-success" id="updatephonebutton">Update</button>
				</div>
				<div id="show-update"></div>
			</div>
		</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<script src="app.js"></script>	
	</body>
</html>