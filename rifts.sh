usage(){
	echo "Usage: NYI"
}

googleindex(){
	cat $1 | xmlstarlet sel -T -t -m opml/body/outline -v @xmlUrl -o "###" -o "offline" -o "###" -o "offline" -n >> $db
}

db=feeds.db
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
				awk -F'###' '{print $1}' feeds.db | xargs -P 20 -n 1 ./core.sh
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
