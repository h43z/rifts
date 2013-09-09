#!/bin/bash

debug(){
	[ "$_DEBUG" == "on" ] && "$@" || :
}

download(){
	debug echo "   +----[CORE] Downloading: $1"
	res=$(wget -t 1 -T 7 -U notgoogle -qO- --no-cache --no-check-certificate $1)
	parse "$res" # "" are needed!
}

parse(){
	res=$(echo $1 | xmlstarlet fo -Q -R)
	echo $res > tmp_$$
	xmlstarlet val -w tmp_$$ > /dev/null
	if [[ $? == 0 ]];then
	echo $res | xmlstarlet sel -T \
		-N rss="http://purl.org/rss/1.0/" \
		-N rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" \
		-N atom="http://www.w3.org/2005/Atom" \
		-t \
		-m "//rdf:RDF/rss:item" \
		-v 'normalize-space(rss:title)' \
		-o "###" \
		-v "rss:link" -n \
		-t \
		-m "//rss/channel/item" \
		-v 'normalize-space(title)' \
		-o "###" \
		-v "link" -n \
		-t \
		-m "//atom:feed/atom:entry" \
		-v 'normalize-space(atom:title)' \
		-o "###" \
		--if "string-length(atom:link[@rel = 'alternate' ]/@href) > 3" -v "atom:link[@rel = 'alternate' ]/@href" \
		--else -v "atom:link/@href" -b -n |
			while read line
			do
				location=$(echo $line | awk -F'###' '{print $2}')
				title=$(echo $line | awk -F'###' '{print $1}')
				if [[ "$location" == *feedproxy.google.com* ]];then
					location=$(wget -t 1 -T 7 -U notgoogle --no-check-certificate -S --spider $location 2>&1 | grep "Location:" | tr "\n" "|")
					location=$(echo $location | grep -Po '(?<=Location: ).*?(?=\|)' | awk '{print $1}' | tail -n1 )		
				fi
					location = $(echo $location | tr '[A-Z]' '[a-z]')
					line="$title###$location"
					echo "$_PARAMETERURL###$line" >> $_CACHE
					debug echo "maybe new: $line"
					addnews "$line"
			done 
	else
		debug echo "XML BROKEN!"
	fi
	rm tmp_$$
}

addnews(){
	line=$1
	title=$(echo $line | awk -F'###' '{print $1}')
	location=$(echo $line | awk -F'###' '{print $2}')
	
	if ( ! grep -q -F "$title" $_NEWSFILE && ! grep -q -F "$title" $_HISTORYFILE ) && ( ! grep -q -F "$location" $_NEWSFILE && ! grep -q -F "$location" $_HISTORYFILE ) ;then
		debug echo "   +----[CORE] New Content: $line"
		
		if [[ -s $_NEWSFILE ]] ; then
			sed -i "1i $line" $_NEWSFILE  #Block anscheined bei parallelisierung
			#echo $line >> $_NEWSFILE
		else
			echo $line > $_NEWSFILE
		fi
	fi
}

# GLOBAL VARS
_BACKEND=/var/www/rifts/backend
_DEBUG="on"
_PARAMETERURL=$1
_NEWSFILE=$2
_HISTORYFILE=$3
_CACHE=$_BACKEND/rifts.cache


touch $_NEWSFILE $_HISTORYFILE $_CACHE 2> /dev/null
# HERE TRAFFIC OPTIMIZATION NYI

if grep -q "$_PARAMETERURL" $_CACHE ;then
	debug echo "   +----[CORE] Found cached version of $_PARAMETERURL";
	grep "$_PARAMETERURL" $_CACHE | awk -F'###' '{print $2"###"$3}' | 
		while read line
		do
			addnews "$line"
		done
else
	download $_PARAMETERURL
fi


