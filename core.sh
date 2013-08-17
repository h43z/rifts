#!/bin/bash

debug(){
	[ "$_DEBUG" == "on" ] && "$@" || :
}

download(){
	debug echo "Downloading: $1"
	res=$(wget -t 1 -T 7 -U notgoogle -qO- --no-check-certificate $1)
	parse "$res"
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
				if ! grep -q "$line" unread.txt ;then
  					echo "$line" >> unread.txt
				fi
			done 
	else
		a=1
		debug echo "XML BROKEN!"
	fi
	rm tmp_$$
}

_DEBUG="on"
# HERE TRAFFIC OPTIMIZATION NYI
touch unread.txt
download $1


