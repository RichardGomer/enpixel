<?php

namespace enpixel;

use QuickAPI as API;

class StatusHandler implements API\APIHandler {


    public function handleCall($args) {

        $doc = new Document($args['documentid']);
        return $doc;

    }

}
