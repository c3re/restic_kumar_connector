<?php

$resticData = file_get_contents("php://input");
$resticData=json_decode($resticData,true);
if(json_last_error() !== JSON_ERROR_NONE){
    http_response_code(400);
    echo "invalid json";
    exit();
}

if(!is_array($resticData)){
    http_response_code(400);
    echo "invalid data";
    exit();
}

if(!count($resticData)){
    http_response_code(400);
    echo "no snapshots";
    exit();
}
$backups=[];
foreach($resticData as $snapshot){
    $id=$snapshot['hostnamer'] . "|" . implode(",",$snapshot['paths']);
    if(!isset($backups[$id])){
        $backups[$id]=0;
    }
    $backupTime=strtotime($snapshot['time']);
    if($backupTime > $backups[$id]) {
        $backups[$id] = $backupTime;
    }
}


ksort($backups);
file_put_contents(
    "/var/www/data/$fileName",
    json_encode($backups, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT)
);



/*

    [0] => Array
        (
            [time] => 2023-12-31T01:00:06.98861049+01:00
            [parent] => 4043eca318f979c0b94c3c999b7834c9a3a8b7db42b6b325142aaf8d6aa54bae
            [tree] => 850bd58eff83ffb70b9d73803b65bd8160ad678e5d98724aca1c00c0f7590c25
            [paths] => Array
                (
                    [0] => /var/lib/docker/volumes
                )

            [hostname] => wau01.c3re.de
            [username] => root
            [id] => 0430b227b440199afa309bb6d41449abc494a64d57894a2a68d225056d008c30
            [short_id] => 0430b227
        )

*/