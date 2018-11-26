<?php

/**
 *
 * This is a wrapper to provide a unified and abstracted view of content in the
 * work area.
 *
 * Documents consist of pages, plus metadata.  A document has a status, and
 * can undergo processing in the background.
 *
 */

namespace enpixel;

class Document {

    /**
     * Create a blank document
     */
    public static function create() {
        global $ENPIXEL;
        $workpath = $ENPIXEL['workpath'];

        $did = uniqid('doc-');
        $path = $workpath.'/'.$did;
        mkdir($path);

        file_put_contents($path.'/metadata.json', json_encode(array('created'=>date('c'))));

        $doc = new Document($did);
        $doc->setStatus(Document::STATUS_READY, "new");

        return $doc;
    }

    public function __construct($did) {

        global $ENPIXEL;
        $workpath = $ENPIXEL['workpath'];
        $path = $workpath.'/'.$did;

        if(!is_dir($path)) {
            throw new DocumentException("'$path' does not exist or is not a directory)");
        }

        $this->did = $did;
        $this->path = $path;
        $this->metapath = $path.'/metadata.json';

        if(!file_exists($this->metapath)) {
            throw new DocumentException("'$path' does not contain document metadata");
        }

        $this->loadMeta();

    }

    public function getDocumentID() {
        return $this->did;
    }

    /**
     * Get path to the Document, which is actually a directory
     */
    public function getDirectoryPath() {
        return $this->path;
    }

    /**
     * Basic status handling
     */
    const STATUS_READY = 1;
    const STATUS_BUSY = 2;

    public function setStatus($status, $text="") {
        $this->metadata['status'] = $status;
        $this->metadata['status-text'] = $text;
        $this->saveMeta();
    }

    public function getStatus() {
        return $this->metadata['status'];
    }

    public function getStatusText() {
        return $this->metadata['status-text'];
    }

    public function isBusy() {
        return $this->metadata['status'] == STATUS_BUSY;
    }

    public function log($msg) {

        if(!array_key_exists('log', $this->metadata)){
            $this->metadata['log'] = array();
        }

        $this->metadata['log'][] = '['.date('Y-m-d H:i:s').']:'.$msg;
        $this->saveMeta();
    }


    /**
     * Metadata handling.
     * Metadata is stored in a JSON file in the directory
     */
    private $metahandle, $metadata, $hasLock = false;
    public function loadMeta() {

        if($this->hasLock)
            return;

        $this->metahandle = fopen($this->metapath, 'r+');

        $tries = 0;
        $locked = false;
        do
        {
            flock($this->metahandle, \LOCK_EX | \LOCK_NB, $blocked);

            if($blocked) {
                $tries++;
            } else {
                $locked = true;
            }

            if($tries > 10) {
                throw new DocumentException('Document metadata is locked');
            }

            usleep(500000);
        }
        while(!$locked);

        $this->hasLock = true;

        $json = fread($this->metahandle, filesize($this->metapath) + 1000);
        $this->metadata = json_decode($json, true);

        if($this->metadata === NULL) {
            throw new DocumentException("Document metadata could not be parsed: $json");
        }
    }

    public function getMetadata(){
        return $this->metadata;
    }

    protected function saveMeta() {
        ftruncate($this->metahandle, 0);
        fseek($this->metahandle, 0);
        fwrite($this->metahandle, json_encode($this->metadata, JSON_PRETTY_PRINT));
    }

    public function release() {
        // Release the lock and close the file
        flock($this->metahandle, LOCK_UN);
        fclose($this->metahandle);
        $this->hasLock = false;
    }

    public function __sleep() {
        throw new DocumentException("Don't put documents to sleep; recreate the object from the path.");
        return false;
    }

}

class DocumentException extends \Exception {}
