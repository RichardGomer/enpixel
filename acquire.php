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


$cmd = "scanimage ".$profile; 

$workpath = getcwd();
// key based auth is set up
$sshcmd = "ssh enpixel@10.0.0.8 -i $wd/../enpixel_rsa \"cd $workpath; $cmd;\"";

echo $sshcmd."\n";
exec($sshcmd);

/**
 * Create a list of raw image files
 */
status("indexing");
exec("ls *.pnm > pages.txt");

/**
 * Check something got scanned!
 */
exec("wc -l < pages.txt", $output);
if($output[0] < 1) {
    chdir($wd); // Change back to original working directory
    $doc->loadMeta(); // Reacquire the metadata lock
    $doc->setStatus(Document::STATUS_READY, "NO PAGES");
    exit;
}

/**
 * Use tesseract to do OCR and produce a single text-enriched PDF
 */
$fn = 'doc-'.date('Y-m-d-h_i');
status("recognising text");
exec("tesseract --tessdata-dir /usr/share/tesseract-ocr/4.00/tessdata/  pages.txt $fn -l eng --psm 1 pdf");

// Use graphicksmagic to combine into PDF
//status("creating PDF");
//exec("gm convert *.pnm $fn.pdf");

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
$doc->setStatus(Document::STATUS_READY, "DONE");
