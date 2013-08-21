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
		file_put_contents($userdata.$username."_".$password."_subscriptions", "");
		file_put_contents($userdata.$username."_".$password."_unread", "");
		
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
	if(file_exists($userdata.$flag."_subscriptions")){
		setcookie("rifts",$flag,time()+(3600*24*4));
		$_COOKIE["rifts"] = $flag;
	}else{
		die("login failed!");
	}
}	

function getunread(){
	global $userdata,$id;
	$limit = 200;
	$counter = 0;
	$data = file($userdata.$id."_unread");
	foreach ($data as $row) {
		$counter++;
		$item = explode("###", $row);
		
		if(empty($item[0]) || empty($item[1])){
				continue;
		}
		$host = str_replace("www.","",parse_url(trim($item[1]), PHP_URL_HOST));

		if((strlen($item[0]) + strlen($host)) > 100){
			while((strlen($item[0]) + strlen($host)) > 100){
				$item[0] = substr_replace($item[0] ,"",-1);
			}
			$item[0].="...";
		}
		echo "<div class='item'><a class='link' href='$item[1]' target='_blank'>$item[0]</a>&nbsp<span class='source'>(".$host.")</span><span class='close'>.</span></span><a class='close'></a></div>";
		
		if($counter >= $limit){
				return;
		}	
	}
}

function prepend($path,$str) {
		global $userdata;
        $context = stream_context_create();
        $fp = fopen($path, 'r', 1, $context);
        $tmpname = $userdata.md5($str);
        file_put_contents($tmpname, $str);
        file_put_contents($tmpname, $fp, FILE_APPEND);
        fclose($fp);
        unlink($path);
        rename($tmpname, $path);
}

function markasread($url){ // $url == "*all*" == all marked as read
	global $unreadfile, $historyfile;
	removeline($unreadfile,$url);
	if($url !== "*all*"){
		prepend($historyfile,$url);
	}
}

function addsubscription($url){
	global $subscriptionsfile;
	$url = urldecode($url);
	if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
			die('Not a valid URL');
	}else{
			file_put_contents($subscriptionsfile,$url."\n", FILE_APPEND);
	}
}
// go here after login/registration or valid cookie existence

function removeline($path,$str){
	global $historyfile;
	$file = file($path);
	foreach($file as $key => $line) {
		if(strpos($line,$str) !== false || $str == "*all*"){
				unset($file[$key]);
				if($str == "*all*"){
					prepend($historyfile,$str);
				}
		}
	}
	file_put_contents($path,$file);
}


function managefeeds(){
	
}

$id = $_COOKIE["rifts"];
$unreadfile = $userdata.$id."_unread";
$historyfile = $userdata.$id."_history";
$subscriptionsfile = $userdata.$id."_subscriptions";


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
	<div id='settings'><b><a href=''>RIFTS (<span id='unseencounter'><? echo count(file($userdata.$id."_unread"));?></span>)</a></b><br><a class=set>add</a><br><a class=set>manage</a><br><a class=set>all read</a><br><a class=set>history</a><br><a class=set>backend</a></div>
	<div id=main><?getunread();?></div>
</html>


