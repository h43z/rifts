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
		file_put_contents($userdata.$username."_".$password."_news", "");
		file_put_contents($userdata.$username."_".$password."_history", "");
		chmod($userdata.$username."_".$password."_news",0777); // must be write/readable for rifts.sh
		
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
	$data = null;
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

function validation($cookie,$user=null,$pass=null){
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

function getnews($file=null){
	global $newsfile;
	$limit = 200;
	$counter = 0;
	
	if($file == null){	
		$data = file($newsfile);
	}else{
		$data = file($file);
	}
	
	if(!empty($data)){
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
	}else{
			echo "Dude, you read them all!";
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
	global $newsfile, $historyfile;
	
	if($url == "*all*"){
		$file = file($newsfile);
		foreach($file as $line) {
			prepend($historyfile,$line);
		}
		file_put_contents($newsfile, "");
	}else{
		$removed = removeline($newsfile,$url);
		prepend($historyfile,"\n".$removed);
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
			$removed = $file[$key];
			unset($file[$key]);
		}
	}
	file_put_contents($path,$file);
	return $removed;	
}

function getsubscriptions($path){
	$data = parsefile($path);
	foreach($data as $set){
		echo "<div class='item'><a href='$set[0]' target='_blank'>$set[0]</a>&nbsp</span><span class='remove'>.</span></span><a class='remove'></a></div>";

	}
	echo "<br><a>Import</a><input type=file>";
}

function discuss($url){
	$url = urldecode(strtok($_REQUEST["url"]),"?");
	$redditapi = "http://www.reddit.com/api/info.json?url=".$url;
	$data = @file_get_contents($redditapi);
	if(empty($data)){
			if(substr($url, -1) == "/"){
					$url = substr_replace($url ,"",-1);
			}elseif(substr($url, -1) != "/"){
					$url += "/";
			}
			$redditapi = "http://www.reddit.com/api/info.json?url=".$url;
			$data = @file_get_contents($redditapi);
	}
	echo $data;
}

// go here after login/registration or valid cookie existence
$id = $_COOKIE["rifts"];
$username = explode("_",$id)[0];
$newsfile = $userdata.$id."_news";
$historyfile = $userdata.$id."_history";
$subscriptionsfile = $userdata.$id."_subscriptions";
$newscount = count(file($newsfile));

if(isset($_REQUEST["f"])){
	switch($_REQUEST["f"]){
		case "add":
			addsubscription($_REQUEST["url"]);
		break;
		case "getsubscriptions":
			getsubscriptions($subscriptionsfile);
		break;
		case "removesubscription":
			removeline($subscriptionsfile,$_REQUEST["url"]);
		break;
		case "allread":
			markasread("*all*");
		break;
		case "markasread":
			markasread($_REQUEST["url"]);
		break;
		case "gethistory":
			getnews($historyfile);
		break;
		case "discuss":
			discuss($_REQUEST["url"]);
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
        .close,.remove {padding-left:10px;padding-right:10px;cursor:pointer;}
        .close:hover, .remove:hover {color: blue;}
        #options{position:absolute;padding-top:15px;position:fixed;}
        .item{}
</style>

<html><meta charset='utf-8'/>
	<title>RIFTS</title>
	<div id='options'>
		<b><a href=''><?echo "$username@"?>RIFTS ~ (<span id='unseencounter'><? echo $newscount?></span>)</a></b><br>
		<a class=set opt=1>add</a><br>
		<a class=set opt=2>my feeds</a><br>
		<a class=set opt=3>all read</a><br>
		<a class=set opt=4>history</a><br>
		<a class=set opt=5>logout</a>
	</div>
	<div id=main><?getnews();?></div>
</html>

<script>
linkclick=false;
window.onload = function() {
		// article links
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

		// option links
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
									addremoveevents();
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
                                case "5":
									document.cookie = "rifts=; expires=Thu, 01 Jan 1970 00:00:01 GMT;";
									location.reload();
                                break;
                        }
                }
        }

		// article close links
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

function addremoveevents(){
	    var b = document.getElementsByClassName("remove");
        for(var i = 0; i < b.length; i++) {
                b[i].onclick = function() {
						url=encodeURI(this.parentNode.children[0].href);
						this.parentNode.remove();
						ajax("?f=removesubscription&url="+url);
				}
	}
}


</script>

