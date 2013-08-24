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
		--else -v "atom:link/@href" -b -n |
			while read line
			do
				location=$(echo $line | awk -F'###' '{print $2}')
				if [[ "$line" == *feedproxy.google.com* ]];then
					location=$(wget -t 1 -T 7 -U notgoogle --no-check-certificate -S --spider $_PARAMETERURL 2>&1 | grep "Location:" | tr "\n" "|")
					location=$(echo $location | grep -Po '(?<=Location: ).*?(?=\|)' | awk '{print $1}' | tail -n1)
					title=$(echo $line | awk -F'###' '{print $1}')
					line="$title###$location"
				fi
				if ! grep -q "$location" $_NEWSFILE && ! grep -q "$location" $_HISTORYFILE ;then
  					echo "$line" >> $_NEWSFILE
				fi
			done 
	else
		debug echo "XML BROKEN!"
	fi
	rm tmp_$$
}

# GLOBAL VARS
_DEBUG="on"
_PARAMETERURL=$1
_NEWSFILE=$2
_HISTORYFILE=$3

# HERE TRAFFIC OPTIMIZATION NYI
touch $_NEWSFILE $_HISTORYFILE &>2 /dev/null
download $_PARAMETERURL

