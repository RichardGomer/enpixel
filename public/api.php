<?php

namespace enpixel;

require '../config.php';

// Load some libraries
require '../lib/quapi/api.lib.php';
require '../lib/document.lib.php';

use QuickAPI as API;

// Handlers implement specific operations
require '../handlers/statusHandler.class.php';
require '../handlers/acquireHandler.class.php';

$api = new API\API(\array_merge($_GET, $_POST), 'action');

$status = new StatusHandler();
$api->addOperation(false, array('status', 'documentid'), $status);

$scan = new AcquireHandler($ENPIXEL['scanopts']);
$api->addOperation(false, array('scan', 'profile'), $scan);



class DocumentConverter implements API\APIResultHandler {
    public function prepareResult($res) {

        // Prepare a list of files
        $files = scandir($res->getDirectoryPath());
        foreach($files as $i=>$f){
            if($f == '.' || $f == '..' || $f == 'metadata.json') {
                unset($files[$i]);
            }
        }

        $files = array_values($files);

        return array(
            'documentid' => $res->getDocumentID(),
            'status'=>$res->getStatus() == Document::STATUS_BUSY ? "BUSY" : "READY",
            'status_text'=>$res->getStatusText(),
            'metadata'=>$res->getMetadata(),
            'files'=>$files
        );
    }
}

$api->registerResultHandler('enpixel\Document', new DocumentConverter());

ob_start();
$api->handle();

// Fetch whole buffer
$output = ob_get_contents();
ob_end_clean();

// Send content-length header to prevent chunked output being used
$len = strlen($output);
header("Content-Length: $len");
header("Connection: Close");
header('Content-Encoding: none');

// Now actually send the data
echo $output."\n";

// Clear any other buffers
while(ob_get_level() > 0)
{
    ob_end_flush();
}

flush();
