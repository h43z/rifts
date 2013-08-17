

awk -F'###' '{print $1}' feeds.db | xargs -P 20 -n 1 ./core.sh
