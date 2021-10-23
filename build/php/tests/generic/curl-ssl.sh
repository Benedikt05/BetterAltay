#!/bin/bash

cat > curl-ssl.php <<'EOF'
<?php

$ch = curl_init("https://www.google.com/");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 PocketMine-MP", "Content-Type: application/json"));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$ret = curl_exec($ch);
curl_close($ch);

if($ret === false){
	echo "0";
}else{
	echo "1";
}

EOF

OUTPUT=$("$PHP_BINARIES" curl-ssl.php)

rm curl-ssl.php

if [ "$OUTPUT" != "1" ]; then
	exit 1
fi

exit 0