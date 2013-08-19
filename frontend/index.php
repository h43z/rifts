<?php
function prepend($string, $filename) {
        $context = stream_context_create();
        $fp = fopen($filename, 'r', 1, $context);
        $tmpname = "./data/".md5($string);
        file_put_contents($tmpname, $string);
        file_put_contents($tmpname, $fp, FILE_APPEND);
        fclose($fp);
        unlink($filename);
        rename($tmpname, $filename);
}

function getredirecturl ($url) {
    $headers = get_headers($url, 1);
    if ($headers !== false && isset($headers['Location'])) {
        return $headers['Location'];
    }
    return $url;
}

session_start();
if(isset($_REQUEST["f"])){
        switch($_REQUEST["f"]){
                        case "discuss":
                                //$url = "http://www.stephenking.com/promo/utd_on_tv/letter.html";
                                //fixme: reddit api sucks... test.com/article != test.com/article/ != test.com/article/?bob 
                                $url = urldecode(strtok(getredirecturl($_REQUEST["url"]),"?"));
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
                        break;
                        case "shift":
                                $unseenpath = "./data/unseenlinks";
                                $seenpath = "./data/seenlinks";
                                $unseen = file($unseenpath);
                                //$url = urldecode($_REQUEST["url"]); failed bei manchen
                                $url = $_REQUEST["url"];
                                foreach($unseen as $key => $line) {
                                        if(strpos($line,$url) !== false){
                                                unset($unseen[$key]);
                                                prepend($line,$seenpath);
                                        }
                                }
                                file_put_contents($unseenpath,$unseen);
                        break;
                        case "add":
                                $url = urldecode($_REQUEST["url"]);
                                if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
                                        die('Not a valid URL');
                                }else{
                                        file_put_contents("./data/feeds.db","$url###etag###lastmod\n", FILE_APPEND);
                                        echo "added";
                                }
                        break;
                        case "allread":
                                $unseenpath = "./data/unseenlinks";
                                $seenpath = "./data/seenlinks";
                                //$unseen = file($unseenpath);
                                //file_put_contents($seenpath, $unseen, FILE_APPEND);
                                $str = file_get_contents($unseenpath);
                                prepend($str,$seenpath);
                                file_put_contents($unseenpath, "");
                                echo "marked all";
                        break;
                        case "manage":
                                echo file_get_contents("./data/feeds.db");
                        break;
                        case "backend":
                                echo file_get_contents("./rifts.sh");
                        break;
                        case "news":
                        case "history":
                                if($_REQUEST["f"] == "news"){
                                        $filename = "./data/unseenlinks";
                                }elseif($_REQUEST["f"] == "history"){
                                        $filename = "./data/seenlinks";
                                }
                                $data = file($filename);
                                $limit = 200;
                                $counter = 0;
                                if(empty($data)){
                                        echo "<div style='padding-left:100px'>
                                                        <a href='http://hckrnews.com/' target='_blank'>Hckrnews</a><br>
                                                        <a href='http://reddit.com/r/netsec' target='_blank'>netsec</a><br>
                                                        <a href='http://www.reddit.com/r/IAmA/' target='_blank'>iama</a><br>
                                                        <a href='http://getprismatic.com/news/home' target='_blank'>getprismatic</a><br>
                                                        <a href='http://digg.com/' target='_blank'>digg</a><br>
                                                        <a href='http://www.reddit.com/r/lolcats' target='_blank'>lolcatz</a><br>
                                                        <a href='http://motherboard.vice.com' target='_blank'>motherbard.vice</a><br>
                                                </div>";
                                }



                                foreach ($data as $row) {
                                        $counter++;
                                        $item = explode("###", $row);
                                        if(empty($item[0]) || empty($item[1])){
                                                continue;
                                        }

                                        $host = str_replace("www.","",parse_url($item[1], PHP_URL_HOST));
                                        if(empty($host)){
                                                $host = "nope";
                                                #fixme... delete from unread list
                                                continue;
                                        }else if($host == "feedproxy.google.com"){
                                                $host = "feedproxy";
                                        }

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
                                //echo "<br><br><br>";
                        break;
                        case "updatedb":
                                file_put_contents("./data/feeds.db",$_REQUEST["db"]);
                        break;
                        case "debug":
                                echo file_get_contents('http://www.reddit.com/api/info.json?url=http://www.stephenking.com/promo/utd_on_tv/letter.html');
                        break;
                        default:

                        break;
                }
        return;


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


<?php
$unseen = file("./data/unseenlinks");
echo "<html><meta charset='utf-8'/><title>RIFTS (".count($unseen).")</title>";
echo "<div id='settings'><b><a href=''>RIFTS (<span id='unseencounter'>".count($unseen)."</span>)</a></b><br><a class=set>add</a><br><a class=set>manage</a><br><a class=set>all read</a><br><a class=set>history</a><br><a class=set>backend</a></div>";
echo "<div id=main></div></html>";
?>

<script>

document.getElementById("main").innerHTML = ajax("?f=news");
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
                        switch(this.innerHTML){
                                case 'add':
                                                var url = window.prompt("RSS URL:","");
                                                if (url != null && url != ""){
                                                        ajax("?f=add&url="+encodeURI(url));
                                                }
                                        break;
                                case 'manage':
                                                document.getElementById("main").innerHTML = "<textarea style='height:90%;width:100%;font-size: 10px !important;'>"+ajax("?f=manage")+"</textarea><button onclick=ajax('?f=updatedb&db='+encodeURIComponent(this.previousSibling.value))>save</button>";
                                        break;
                                case 'all read':
                                                var x=window.confirm("Are you sure?")
                                                if (x){
                                                        ajax("?f=allread");
                                                        window.location.reload(false); 
                                                }
                                        break;
                                case 'history':
                                                document.getElementById("main").innerHTML = ajax("?f=history");

                                        break;
                                case 'backend':
                                                document.getElementById("main").innerHTML = "<textarea style='height:95%;width:100%;font-size: 10px !important;'>"+ajax("?f=backend")+"</textarea>";
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
                        url=encodeURI(this.parentNode.children[0].href);
                        this.parentNode.remove();
                        updatecounter();
                        ajax("?f=shift&url="+url);
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
                ajax("?f=shift&url="+url);
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
