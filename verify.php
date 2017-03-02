<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>Verify</title>
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">	
	</head>
	<body>
		<?php
		if(isset($_GET['verifyToken'])){
			$verifyToken = $_GET['verifyToken'];
		?>
			<div class="row">
				<div class="col-lg-6 col-md-6 col-lg-offset-3">
					<div class="form-group">
						<label>Registration Complate</label>
						<input type="hidden" class="form-control" id="verifyToken" value='<?php echo $verifyToken ?>'>
						<button class="btn btn-success" id="activate">Activate</button>
					</div>
					<div id="userID"></div>
				</div>
			</div>
		<?php
			}
		?>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
		<script src="app.js"></script>	
	</body>
</html>