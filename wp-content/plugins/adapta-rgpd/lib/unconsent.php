<?php
if ( ! defined( 'ABSPATH' ) ) { 
	exit; 
}

function unconsent_patterns($patterns){
	// https://support.count.ly/hc/en-us/articles/360037441932-Web-analytics-JavaScript-
	$patterns[] = 'countly.js';
	$patterns[] = 'countly.min.js';
	// https://developers.google.com/analytics/devguides/collection/analyticsjs
    $patterns[] = 'google-analytics.com/ga.js';
    $patterns[] = 'www.google-analytics.com/analytics.js';
    $patterns[] = '_getTracker';
    $patterns[] = 'apis.google.com/js/platform.js';
    $patterns[] = 'maps.googleapis.com';
    $patterns[] = 'google.com/recaptcha';    
    $patterns[] = 'googletagmanager.com';
    $patterns[] = "gtag('config'";    
    // https://developers.facebook.com/docs/mediaguide/pixel-and-analytics/
    $patterns[] = 'connect.facebook.net';
    // hotjar    
    $patterns[] = 'static.hotjar.com';
    // linkedin
    $patterns[] = 'platform.linkedin.com/in.js';
    // twitter
    $patterns[] = 'twitter-widgets.js';
    // youtube
    $patterns[] = 'www.youtube.com/iframe_api';
    // instagram
    $patterns[] = 'instagram.com/embed.js';
    // https://help.disqus.com/en/articles/1717112-universal-embed-code
    $patterns[] = 'disqus.com/embed.js';
    return $patterns;
}
add_filter('argpd_unconsent_patterns', 'unconsent_patterns');