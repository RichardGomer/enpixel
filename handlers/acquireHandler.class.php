<?php

namespace enpixel;

use QuickAPI as API;

/**
 * This handler creates a new document and acquires images from a scanner by running
 * scanImage
 *
 * It returns the new document immediately, and the scanning operation continues
 * in the background.
 */
class AcquireHandler implements API\APIHandler {

    public function __construct($profiles) {
        $this->profiles = $profiles;
    }

    public function handleCall($args) {

        if(!array_key_exists('profile', $args)) {
            throw new AcquireHandlerException("A scanner profile was not specified");
        }

        $profile = $args['profile'];

        if(!array_key_exists($profile, $this->profiles)) {
            throw new AcquireHandlerException("Profile '$profile' was not found");
        }

        // Check that a scanner is available!
        exec('scanimage -L -f "scanner number %i, %d is a %t %v %m" 2>/dev/null', $scanners, $return);

        // Scanimage returns a message when no scanners are found :-/ but the first line is blank
        if(trim($scanners[0]) == "") {
            throw new AcquireHandlerException("No scanners are available");
        }

        $doc = Document::create();
        $doc->setStatus(Document::STATUS_BUSY, "Scanning");

        $cmd = 'php ../acquire.php '.escapeshellarg($this->profiles[$profile]).' '.escapeshellarg($doc->getDocumentID()).' 2>&1 | tee -a '.escapeshellarg($doc->getDirectoryPath().'/acquire.log').' 2>/dev/null >/dev/null &';
        $doc->log($cmd);
        exec($cmd);

        $doc->release();

        return $doc;

    }

}

class AcquireHandlerException extends \Exception {}
