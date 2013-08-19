usage(){
	echo "RIFTS : Read It From The Source"
	echo "A backend shell script that checks and stores new RSS links"
	echo ""
	echo "Usage: ${0##*/} [ -h ] [ -g subscriptions.xml ] [ -c ]"
	echo ""
	echo "Options:"
	echo "	-h : Display this help and exit"
	echo " 	-g : Index a google reader subscriptions.xml file"
	echo "	-c : Check for rss updates of all links which should be stored (line by line) in the file 'feeds.db'"
	echo ""
	echo ""

}


googleindex(){
	cat $1 | xmlstarlet sel -T -t -m opml/body/outline -v @xmlUrl -o "###" -o "offline" -o "###" -o "offline" -n >> $_DB
}

# GLOBAL VARS
_DB=feeds.db
_CORE=./core.sh

while getopts "hic" OPTION
do
	case $OPTION in
		h)
			usage
			exit 0
		;;
		g)
			googleindex $1
		;;
		c)
			if [ -s feeds.db ]; then
				awk -F'###' '{print $1}' $_DB | xargs -P 20 -n 1 $_CORE
			else
				echo "feeds.db is empty or does not exist"
			fi
			exit 0
		;;
		?)
			usage
			exit 1
		;;
	esac
done
