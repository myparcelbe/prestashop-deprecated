<?php

// forward the request to avoid blocked cross-origin requests

$requestType = ('delivery' == $_GET['t']) ? 'delivery' : 'pickup';

$globalParams = array(
    'cc'                    => 'BE',
    'postal_code'           => urldecode($_GET['p']),
    'street_number'         => urldecode($_GET['s']),
    'carrier'               => 'bpost',
    'cutoff_time'           => '16:30', // default is 16:30
    'exclude_days'          => '1;7', // default is 1;7
    'earliest_deliver_date' => '2015-08-10',
    'latest_delivery_date'  => '2015-08-20',
);

$deliveryParams = array(
    'delivery_date'         => date('Y-m-d', strtotime('+1 day')),
    'exclude_delivery_type' => 'earlymorning;morning;evening',
);

$pickupParams = array(
    'exclude_pickup_type'   => 'retailexpress',
);

$params = ('delivery' == $requestType)
        ? $globalParams + $deliveryParams
        : $globalParams + $pickupParams;

// Replace file_get_contents with cUrl

$ch = curl_init();
curl_setopt( $ch, CURLOPT_URL, 'https://www.sendmyparcel.be/deliveryoptions/' . $requestType . '?' . http_build_query($params) );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100);

$content = curl_exec( $ch );
curl_close ( $ch );

if (!json_decode($content)) {
    $content = null;
}

//$json = file_get_contents('https://www.sendmyparcel.be/deliveryoptions/' . $requestType . '?' . http_build_query($params));

echo $content;
