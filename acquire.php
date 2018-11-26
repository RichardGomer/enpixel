<?php

/**
 * A wrapper script that acquires images from the scanner and passes them through
 * the processing flow, updating status as it goes.
 *
 */

namespace enpixel;

if(count($argv) < 3) {
    echo "USAGE: php acquire.php profile docid\n";
    exit;
}

require 'config.php';
require 'lib/document.lib.php';

$profile = $argv[1];
$did = $argv[2];

try {
    $doc = new Document($did);
} catch(Exception $e) {
    echo "Document could not be opened: {$e->getMessage()}";
    exit;
}

// Helper for setting busy status
function status($msg){
    global $doc;

    $doc->loadMeta();
    $doc->setStatus(Document::STATUS_BUSY, $msg);
    $doc->release(); // Release the document so other processes can access the metadata
}

/**
 * Change to document working directory
 */
$wd = getcwd();
chdir($doc->getDirectoryPath()); // Change into the document directory

/**
 * Acquire images from the scanner
 */
status("scanning");
exec("scanimage ".$profile); // Run the command

/**
 * Create a list of raw image files
 */
status("indexing");
exec("ls *.pnm > pages.txt");

/**
 * Use tesseract to do OCR and produce a single text-enriched PDF
 */
$fn = 'doc-'.date('Y-m-d-h_i');
status("ocr");
exec("tesseract --tessdata-dir /usr/share pages.txt $fn -l eng --psm 1 pdf");

/**
 * Extract text
 */
status("extract text");
exec("pdftotext $fn.pdf");

/**
 * Tidy up
 */
exec("rm *.pnm pages.txt");

/**
 * Finish up
 */
chdir($wd); // Change back to original working directory

$doc->loadMeta(); // Reacquire the metadata lock
$doc->setStatus(Document::STATUS_READY, "");
