#!/bin/bash
debug(){
 [ "$_DEBUG" == "on" ] &&  $@
}

usage(){
	local progname=${0##*/}
	cat <<EOF
			RIFTS. Read It From The Source. Backend shell script.
			Fetch and store new RSS links.
			Usage:

				$progname [ -h ] [ -i file.xml] [ -c ]

			Options:

				-h : display this help and exit
				-i : Index google reader subscriptions file
				-c : Fetch new rss links
				
				
EOF
}

createdb(){
	# creats db from google reader takeout subscriptions.xml
	# Format xmlurl###etag###lastmod###
	subscriptions=$1
	rm -rf ./data/
	mkdir ./data/
	touch $seen $unseen
	cat $subscriptions | xmlstarlet sel -T -t -m opml/body/outline -v @xmlUrl -o "###" -o "offline" -o "###" -o "offline" -n >> $db
	chmod 006 $db $seen $unseen
	chmod 007 ./data
}

downloadrss(){
	# downloads the rss of an url
	# also checks status if url is on/offline 
	# updats db with new md5(lastmodified) and md5(etag) or its status
	line=$2
	feed=$1
	url=$(echo $feed | awk -F'###' '{print $1}')
	oldetag=$(echo $feed | awk -F'###' '{print $2}')
	oldlastmod=$(echo $feed | awk -F'###' '{print $3}')
	echo -e "[+] |$line| Checking $url"
	wget -t 1 -T 10 -U notgoogle --no-check-certificate --spider -qO- $url
	[ $? -ne 0 ] && { debug echo "[+] Offline";return; }
	
	header=$(wget -t 1 --cache=off -T 12 -U notgoogle --no-check-certificate -S --spider $url 2>&1 | grep "Location:\|Last-Modified:\|ETag:" | tr "\n" "|")

	location=$(echo $header | grep -Po '(?<=Location: ).*?(?=\|)' | awk '{print $1}' | tail -n1)
	lastmod=$(echo $header | grep -Po '(?<=Last-Modified: ).*?(?=\|)' | tail -n1)
	etag=$(echo $header | grep -Po '(?<=ETag: ).*?(?=\|)' | tail -n1)
			
	[ -n "$location" ] && { debug echo "[+] Location changed --> |$location| ";sed -i 's#'$url'#'$location'#g' $db; }
	
	etag=$( [ -z "$etag" ] && echo "NO_ETAG" || echo -n $etag | md5sum | awk '{print $1}')
	lastmod=$( [ -z "$lastmod" ] && echo "NO_LM" || echo -n $lastmod | md5sum | awk '{print $1}')

	debug echo "newetag : $etag"
	debug echo "oldetag : $oldetag"
	debug echo "newlmod : $lastmod"
	debug echo "oldlmod : $oldlastmod"

	sed -i ${line}s#${oldetag}#${etag}# $db
	sed -i ${line}s#${oldlastmod}#${lastmod}# $db
		
	debug echo "[+] Online"
	
#	if [[ $oldetag != $etag && $oldlastmod != $lastmod || ( "$oldlastmod" == "NO_LM" && "$oldetag" == "NO_ETAG" ) ]];then
	if [[ 1 ]];then
		wget -t 1 -T 7 -U notgoogle --no-check-certificate -q $url -O tmp
		debug echo "[+] RSS updated"
	else
		debug echo "[+] Old RSS, nothing new "
		return
	fi

	
	validaterss
	if [[  $? -ne 0  ]];then
		debug echo "[+] RSS validated"
		parserss
		filter
	else
		debug echo "[+] RSS is broken"
	fi
	rm tmp
}

validaterss(){
	# tries to repair broken xml
	#feed=/opt/lampp/htdocs/rifts/tmp
	xmlstarlet val -w tmp >/dev/null
	if [[ $? == 1 ]];then
		xmlstarlet fo -Q -R  tmp > tmp2
		cat tmp2 > tmp
		rm tmp2
		xmlstarlet val -w tmp >/dev/null
		if [[ $? == 1 ]];then
			return 0
		else
			return 1
		fi
	else
		return 1
	fi
}

parserss(){
	# gets article title,link from rss and writes all to newunseenlinks
	#feed=/opt/lampp/htdocs/rifts/tmp
	cat tmp | xmlstarlet sel -T \
		-N rss="http://purl.org/rss/1.0/" \
		-N rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" \
		-N atom="http://www.w3.org/2005/Atom" \
		-t \
		-m "//rdf:RDF/rss:item" \
		-v "rss:title" \
		-o "###" \
		-v "rss:link" -n \
		-t \
		-m "//rss/channel/item" \
		-v "title" \
		-o "###" \
		-v "link" -n \
		-t \
		-m "//atom:feed/atom:entry" \
		-v "atom:title" \
		-o "###" \
		--if "string-length(atom:link[@rel = 'alternate' ]/@href) > 3" -v "atom:link[@rel = 'alternate' ]/@href" \
		--else -v "atom:link/@href" -b -n >> newunseen
}

filter(){
	# checks if rss article already downloaded in the past, only if not write to unseenlinks
	# Also gets the source link of the article
	while read line; do
		srcurl=$(echo $line | awk -F'###' '{print $2}')
		debug echo "filter |$srcurl|"
		if ! grep -Fq "$srcurl" "$unseen" && ! grep -Fq "$srcurl" "$seen" ; then
			if [[ "$srcurl" == *feedproxy.google.com* ]];then
				echo $srcurl >> $seen
				location=$(wget -t 1 -T 12 -U notgoogle --no-check-certificate -S --spider $srcurl 2>&1 | grep "Location:" | tr "\n" "|")
				srcurl=$(echo $location | grep -Po '(?<=Location: ).*?(?=\|)' | awk '{print $1}' | tail -n1)
			fi
			
			title=$(echo $line | awk -F'###' '{print $1}')
			newentry="$title###$srcurl"
			debug echo "New article: $newentry"
			echo -e "$newentry$( [ -s $unseen ] && { echo "";cat $unseen; } || cat $unseen)" > $unseen
		fi
	done < newunseen
	rm newunseen
}

_DEBUG="on"

data=/opt/lampp/htdocs/rifts/data/
db=/opt/lampp/htdocs/rifts/data/feeds.db
seen=/opt/lampp/htdocs/rifts/data/seenlinks
unseen=/opt/lampp/htdocs/rifts/data/unseenlinks

while getopts "hic" OPTION
do
	case $OPTION in
		h)
			usage
			exit 0
			;;
		c)
			;&
		i)
			if [[ $OPTION == "i" ]];then
				createdb $2
			fi
			while read feed; do
				let i++
				downloadrss "$feed" $i
			done < $db
			rm $data/sed*
			exit 0
			;;
		?)
			usage
			exit 1
			;;
		esac
done

