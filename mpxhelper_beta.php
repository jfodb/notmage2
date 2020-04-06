<?php

// TODO: NEED TO REMOVE THIS FILE AFTER GO-LIVE

// to run, you need to add your IP address in <trustedips> at app/code/Psuedo/Mpxdownload/etc/config.php
// beta hash
// beta url: https://beta.donations.ourdailybread.org/mpxdownloads/?    or    https://beta.ourdailybreadpublishing.org/mpxdownloads/?

$sig = 'c41fa7d0dd364dfe3e70196a529ea34b';

// MPX checkback rollback:
// 'user=mpxdownloads&action=rollback&company=rbcu&jobtype=odbdonations&job_id=140'

$set = array(

    'store=magento.rbc.org&jobtype=odbdonations&company=rbcu&user=mpxdownloads&action=checkback&start_date=2020-04-04',

    'store=magento.rbc.org&jobtype=odbdonations&company=rbcu&user=mpxdownloads&action=export&start_date=2020-04-03&job_id=142',

    'store=magento.rbc.org&jobtype=odbdonations&company=rbcu&user=mpxdownloads&action=export&start_date=2020-04-04&job_id=143',

);


foreach ($set as $call) {

    $signed = md5($sig . $call);

    echo $call, '&sig=', $signed, "\r\n";

}