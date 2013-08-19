<?php
if(isset($_COOKIE["username"])){
	echo "Hello ".$_COOKIE["username"];
}
else{
	if(substr($_REQUEST["username"],0,-1) == "2445" && isset($_REQUEST["password"])){
		// registration valid
		echo substr($_REQUEST["username"],4,0);	
		#setcookie (substr($_REQUEST["username"], "", time() - 3600*24*3);
		echo "nice";
	}

	echo "LOGIN";
	echo "<form method=post>";
	echo "Username: <input type=text name=username>";
	echo "Password: <input type=password name=password>";
	echo "<input type=submit>";
	echo "</form><br>";
}




?>
