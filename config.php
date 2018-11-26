<?php

namespace enpixel;

$ENPIXEL = array();

// This is where documents are worked on
$ENPIXEL['workpath'] = dirname(__FILE__).'/public/work/';

// Scanner configurations
// These are just the options passed to scanimage - they could conceivably specify a scanner, too
$ENPIXEL['scanopts'] = array();
$ENPIXEL['scanopts']['colour duplex'] = "--mode=color --duplex=yes --batch=scan-%04d.pnm";
$ENPIXEL['scanopts']['greyscale duplex'] = "--mode=gray --duplex=yes --batch=scan-%04d.pnm";
$ENPIXEL['scanopts']['colour simplex'] = "--mode=colour --duplex=no --batch=scan-%04d.pnm";
$ENPIXEL['scanopts']['gray simplex'] = "--mode=gray --duplex=no --batch=scan-%04d.pnm";
