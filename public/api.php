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
require '../handlers/profileHandler.class.php';
require '../handlers/deleteHandler.class.php';
require '../handlers/docHandler.class.php';
require '../handlers/classifyHandler.class.php';

header('Access-Control-Allow-Origin: *');

$api = new API\API(\array_merge($_GET, $_POST), 'action');

$status = new StatusHandler();
$api->addOperation(false, array('status', 'documentid'), $status);

$scan = new AcquireHandler($ENPIXEL['scanopts']);
$api->addOperation(false, array('scan', 'profile'), $scan);

$scanners = new ProfileHandler($ENPIXEL['scanopts']);
$api->addOperation(false, array('scanners'), $scanners);

$del = new DeleteHandler();
$api->addOperation(false, array('delete', 'documentid'), $del);

$docs = new DocHandler();
$api->addOperation(false, array('docs'), $docs);

$suggest = new ClassifySuggestHandler($ENPIXEL['classifier']);
$api->addOperation(false, array('suggest', 'did'), $suggest);

$classify = new ClassifyDoHandler($ENPIXEL['classifier']);
$api->addOperation(false, array('classify', 'did', 'class'), $classify);


class DocumentConverter implements API\APIResultHandler {
    public function prepareResult($res) {

        global $ENPIXEL;

        // Prepare a list of files
        $files = $res->getFiles();

        return array(
            'documentid' => $res->getDocumentID(),
            'status'=>$res->getStatus() == Document::STATUS_BUSY ? "BUSY" : "READY",
            'status_text'=>$res->getStatusText(),
            'metadata'=>$res->getMetadata(),
            'baseurl'=>$ENPIXEL['httpworkpath'].$res->getDocumentID().'/',
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
