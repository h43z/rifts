<?php
//GLOBAL VARS
$registercode="2445";
$registercodelength=strlen($registercode);
$userdata="./userdata/";

if(isset($_COOKIE["rifts"])){
	
	validation($_COOKIE["rifts"]);
	
}elseif(isset($_REQUEST["username"]) && isset($_REQUEST["password"])){
	
	$code=substr($_REQUEST["username"],0,$registercodelength);
	$password=md5($_REQUEST["password"]);
	
	//tries to register
	if($code == $registercode){
		$username=substr($_REQUEST["username"],4,100);
		file_put_contents($userdata.$username."_".$password."_subscription", "");
		file_put_contents($userdata.$username."_".$password."_read", "");
		
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

function parsefile($path){
	foreach (file($path) as $line){
		$line = explode("###", $line);
		if(is_array($line)){
			foreach($line as $part){
				$data1[] = trim($part);
			}
			$data[] = $data1;
			unset($data1);
		}else{
			$data[] = $line;
		}
	}
	return $data;
}

function validation($cookie,$user,$pass){
	global $userdata;
	if($cookie != null){
		$flag = $cookie;
	}else{
		$flag = $user."_".$pass;
	}
	if(file_exists($userdata.$flag."_subscription")){
		setcookie("rifts",$flag,time()+(3600*24*4));
		$_COOKIE["rifts"] = $flag;
	}else{
		die("login failed!");
	}
}	

function writetofile($path,$str){
	$content = file_get_contents($path);
	file_put_contents($path,$str);
	print_r(error_get_last());
}

// go here after login or registration or valid cookie

$id = $_COOKIE["rifts"];


?>
<style type="text/css">
        body {font-family: "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", Verdana, Tahoma, sans-serif;line-height: 24px;color: #333;font-size:14px}
        a { color: #333; text-decoration: none;padding-left:40px; cursor:pointer }
        a.link:visited {color: #999;}
        a:hover {color: blue !important;}
        .source {color: #999;font-size: 12px;}
        #main {padding-top:15px;padding-bottom:70px;width: 1000px ;margin-left: 230px ;margin-right: auto ;}
        .discuss {color: #333;font-size:12px;padding-left:60px;}
        .discusslink:visited {color: #999;}
        .close {padding-left:10px;padding-right:10px;cursor:pointer;}
        .close:hover {color: blue;}
        #settings{position:absolute;padding-top:15px;position:fixed;}
        .item{}
</style>

<html><meta charset='utf-8'/>
	<title>RIFTS</title>
	<div id='settings'><b><a href=''>RIFTS (<span id='unseencounter'><?echo " $id "?></span>)</a></b><br><a class=set>add</a><br><a class=set>manage</a><br><a class=set>all read</a><br><a class=set>history</a><br><a class=set>backend</a></div>
	<div id=main></div>
</html>


