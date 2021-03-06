usage(){
	echo "RIFTS : Read It From The Source ..."
	echo ""
	echo "is a backend shell scipt that manages RSS feeds for mulitple users"
	echo "and puts new unread articles to specific places. Edit the configuration file"
	echo "rifts.config with the needed scalability"
	echo ""
	echo "The layout for that file has to be the following"
	echo "path/to/subscriptions###path/to/put/news###path/to/already/read/article"
	echo ""
	echo "Subscriptions file: One feed url per line"
	echo ""
	echo "Usage: ${0##*/} [ -h ] [ -g subscriptions.xml ] [ -c ]"
	echo ""
	echo " "
	echo "Options:"
	echo "	-h : Display this help and exit"
	echo " 	-g : Index a google reader subscriptions.xml file"
	echo "	-c : Check for rss updates of all files/paths from rifts.config"
	echo ""
	echo ""

}


googleindex(){
	# Think further...
	1=1 
	#cat $1 | xmlstarlet sel -T -t -m opml/body/outline -v @xmlUrl -o "###" -o "offline" -o "###" -o "offline" -n >> $_DB
}

# GLOBAL VARS
_BACKEND=/var/www/rifts/backend
_USERDATA=/var/www/rifts/frontend/userdata
_CORE=$_BACKEND/core.sh
_CONFIG=$_BACKEND/rifts.config
_CACHE=$_BACKEND/rifts.cache

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
			if [ -s $_CONFIG ]; then
				touch $_CACHE
				while read configline;do
					feedfile=$_USERDATA/$(echo $configline | awk -F'###' '{print $1}')
					newsfile=$_USERDATA/$(echo $configline | awk -F'###' '{print $2}')
					historyfile=$_USERDATA/$(echo $configline | awk -F'###' '{print $3}')
					if [ -s $feedfile ]; then
						echo "[RIFTS] Checking feeds from: $(basename $feedfile)"
						awk -v newsfile=$newsfile -v historyfile=$historyfile -F'###' '{print $1" "newsfile" "historyfile}' $feedfile | xargs -P 20 -n 3 $_CORE
					fi
				done < $_CONFIG
				rm $_CACHE
			else
				echo "rifts.config is empty or does not exist"
			fi
			exit 0
		;;
		?)
			usage
			exit 1
		;;
	esac
done
