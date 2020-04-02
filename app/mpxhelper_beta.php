<?php

// TODO: NEED TO REMOVE THIS FILE AFTER GO-LIVE

// beta hash
$sig = 'c41fa7d0dd364dfe3e70196a529ea34b';

$set = array(
    'store=magento.rbc.org&jobtype=odbdonations&company=rbcu&user=mpxdownloads&action=export&start_date=2020-04-02&job_id=138',
    'store=magento.rbc.org&jobtype=odbdonations&company=rbcu&user=mpxdownloads&action=export&start_date=2020-04-03&job_id=139',
);

foreach($set as $call) {
    $signed = md5($sig.$call);

    echo $call, '&sig=', $signed, "\r\n";
}