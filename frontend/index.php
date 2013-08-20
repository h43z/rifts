<?php

//GLOBAL VARS
$registercode="2445";
$registercodelength=strlen($registercode);



function getunreadlinks(){
	
}

function getsubscriptions(){

}

function validation($cookie,$user,$pass){
	if($cookie != null){
		$flag = $cookie;
	}else{
		$flag = $user."_".$pass;
	}
	if(file_exists("./userdata/".$flag."_subscription")){
		setcookie("rifts",$flag,time()+(3600*24*4));
	}else{
		die("login failed!");
	}
}	

if(isset($_COOKIE["rifts"])){
	validation($_COOKIE["rifts"]);
}elseif(isset($_REQUEST["username"]) && isset($_REQUEST["password"])){
	
	$code=substr($_REQUEST["username"],0,$registercodelength);
	$password=md5($_REQUEST["password"]);
	
	//tries to register
	if($code == $registercode){
		$username=substr($_REQUEST["username"],4,100);
		file_put_contents("./userdata/".$username."_".$password."_subscription", "");
		file_put_contents("./userdata/".$username."_".$password."_unread", "");
		validation(null,$username,$password);
	}else{
	//tries to login
		$username=$_REQUEST["username"];
		validation(null,$username,$password);
	}
}else{
	echo "<form method=post>";
	echo "Username: <input type=text name=username>";
	echo "Password: <input type=password name=password>";
	echo "<input type=submit>";
	echo "</form><br>";
	die();
}


echo "Hello ".$_COOKIE["rifts"]."<br>";
echo "hier artikel auflisten!";



?>
