#!/bin/bash


sql=$(cat <<EOF

truncate table devices;
truncate table devices_passes;
truncate table passes;
truncate table subscribers;      
EOF
)

mysql -u root --password="" mypassbook -e "${sql}"


