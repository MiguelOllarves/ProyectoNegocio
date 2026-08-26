<?php
echo "==== DOLAR API ====\n";
$resp1 = file_get_contents('https://ve.dolarapi.com/v1/dolares/oficial', false, stream_context_create(['ssl' => ['verify_peer' => false]]));
echo $resp1 . "\n";

echo "==== PYDOLAR VENEZUELA ====\n";
$resp2 = file_get_contents('https://pydolarvenezuela-api.vercel.app/api/v1/dollar/page?page=bcv', false, stream_context_create(['ssl' => ['verify_peer' => false]]));
echo $resp2 . "\n";
