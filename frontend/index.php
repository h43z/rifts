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
		$item = explode("###", trim($row));
		
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
	
	if($url == "*all*"){
		$file = file($unreadfile);
		foreach($file as $line) {
			prepend($historyfile,$line);
		}
		file_put_contents($unreadfile, "");
	}else{
		removeline($unreadfile,$url);
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

function removeline($path,$str){
	$file = file($path);
	foreach($file as $key => $line) {
		if(strpos($line,$str) !== false){
			unset($file[$key]);
		}
	}
	file_put_contents($path,$file);
}

function getfile($path){
	$data = parsefile($path);
	foreach($data as $set){
		echo "$set[0]<br>";
	}
}


// go here after login/registration or valid cookie existence
$id = $_COOKIE["rifts"];
$unreadfile = $userdata.$id."_unread";
$historyfile = $userdata.$id."_history";
$subscriptionsfile = $userdata.$id."_subscriptions";

if(isset($_REQUEST["f"])){
	switch($_REQUEST["f"]){
		case "add":
			addsubscription($_REQUEST["url"]);
		break;
		case "getsubscriptions":
			getfile($subscriptionsfile);
		break;
		case "allread":
			markasread("*all*");
		break;
		case "markasread":
			markasread($_REQUEST["url"]);
		break;
		case "gethistory":
			getfile($historyfile);
		break;
		default:
		break;
	}
	exit;
}
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
	<div id='settings'>
	<b><a href=''>RIFTS (<span id='unseencounter'><? echo count(file($userdata.$id."_unread"));?></span>)</a></b><br>
	<a class=set opt=1>add</a><br>
	<a class=set opt=2>my feeds</a><br>
	<a class=set opt=3>all read</a><br>
	<a class=set opt=4>history</a><br></div>
	<div id=main><?getunread();?></div>
</html>

<script>
linkclick=false;
window.onload = function() {
        var links = document.getElementsByClassName("link");
        for(var i = 0; i < links.length; i++) {
                links[i].onclick = function(e) {
                        //e.preventDefault();
                        //window.open(this.href);  
                        console.log("clicked on link"); 
                        url=encodeURI(this.href);
                        activeitem=this;
                        linkclick=true;
                }
        }

        var links = document.getElementsByClassName("set");
        for(var i = 0; i < links.length; i++) {
                links[i].onclick = function() {
                        links_ = document.getElementsByClassName("set")
                        for(var x= 0; x <links_.length; x++){
                                links_[x].style.color = "";
                        }
                        this.style.color = "blue";
                        switch(this.getAttribute("opt")){
                                case "1":
                                                var url = window.prompt("RSS URL:","");
                                                if (url != null && url != ""){
                                                        ajax("?f=add&url="+encodeURI(url));
                                                }
                                        break;
                                case "2":
                                                document.getElementById("main").innerHTML = ajax("?f=getsubscriptions");
                                        break;
                                case "3":
                                                var x=window.confirm("Are you sure?")
                                                if (x){
                                                        ajax("?f=allread");
                                                        window.location.reload(false); 
                                                }
                                        break;
                                case "4":
                                                document.getElementById("main").innerHTML = ajax("?f=gethistory");
                                        break;
                        }
                }
        }

        var links = document.getElementsByClassName("reddit");
        for(var i = 0; i < links.length; i++) {
                links[i].onclick = function() {this.parentNode.remove()}
        }

        var links = document.getElementsByClassName("close");
        for(var i = 0; i < links.length; i++) {
                links[i].onclick = function() {
					console.log(this.parentNode.children[0].href);
                        url=encodeURI(this.parentNode.children[0].href);
                        this.parentNode.remove();
                        updatecounter();
                        ajax("?f=markasread&url="+url);
                };
        }

}

window.addEventListener('blur', function() {
        if(linkclick){
                linkclick=false;
                if (discuss(url,activeitem)){
                                return;
                }
                activeitem.parentNode.remove();
                updatecounter();
                ajax("?f=markasread&url="+url);
        }

});

function ajax(url){
        var xhReq = new XMLHttpRequest();
        xhReq.open("POST",url.split("?")[0], false);
        xhReq.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        xhReq.send(url.substr(url.indexOf("?")+1));
        var serverResponse = xhReq.responseText;
        return(serverResponse); 
}

function discuss(url,dest){
        console.log("discuss");
        var res = ajax("?f=discuss&url="+encodeURI(url)); 
        if(res == "" || res == null){
                return false;
        } 
        var obj = JSON.parse(res);
        var i;
        var d = dest.parentNode;
        for ( i = 0; i < obj["data"]["children"].length; i++) {
                if(dest.innerText != obj["data"]["children"][i]["data"]["title"]){
                        var title = obj["data"]["children"][i]["data"]["title"];
                }else{
                        var title = "Discuss this @ /r/"+obj["data"]["children"][i]["data"]["subreddit"];
                }
                var span = document.createElement( 'span' );
                span.innerHTML = "<br><span class='discuss'><a class='discusslink' href='http://reddit.com"+ obj["data"]["children"][i]["data"]["permalink"] +"' target='_blank'>"+title+"</a>&nbsp{"+obj["data"]["children"][i]["data"]["num_comments"]+"}&nbsp["+ obj["data"]["children"][i]["data"]["score"] +"]</span>";
                d.appendChild(span); 
        }
        if(i == 0){
                return false;
        }
        return true;
}

function updatecounter(){
        counter = parseInt(document.getElementById("unseencounter").innerHTML);
        document.getElementById("unseencounter").innerHTML =  --counter;
        document.title = "RIFTS ("+counter+")";
}

</script>

