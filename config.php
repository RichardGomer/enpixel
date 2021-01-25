<?php

namespace enpixel;

$ENPIXEL = array();

// This is where documents are worked on
$ENPIXEL['workpath'] = dirname(__FILE__).'/public/work';

// This is the HTTP path to where documents are worked on (for generating links)
$ENPIXEL['httpworkpath'] = "http".(!empty($_SERVER['HTTPS'])?"s":"").
"://".$_SERVER['SERVER_NAME'].dirname($_SERVER['REQUEST_URI']).'/work/';

// Scanner configurations
// These are just the options passed to scanimage - they could conceivably specify a scanner, too
$ENPIXEL['scanopts'] = array();
$ENPIXEL['scanopts']['colour duplex'] = "--mode=color --duplex=yes --batch=scan-%04d.pnm";
$ENPIXEL['scanopts']['greyscale duplex'] = "--mode=gray --duplex=yes --batch=scan-%04d.pnm";
$ENPIXEL['scanopts']['colour simplex'] = "--mode=colour --duplex=no --batch=scan-%04d.pnm";
$ENPIXEL['scanopts']['greyscale simplex'] = "--mode=gray --duplex=no --batch=scan-%04d.pnm";

$ENPIXEL['bayesfile'] = dirname(__FILE__).'/public/work/bayes.json';

require 'lib/classifier.php';
$ENPIXEL['classifier'] = $c = new Classifier($ENPIXEL['bayesfile']);

//$c->addClass('kolola-lloyds', new FileAction('/home/kolola/Finance/statements/', 'KOLOLA Statement'));
//$c->addClass('xebre-lloyds', new FileAction('/home/richard/Documents/xebre/Finance/statements/', 'Xebre Statement'));
