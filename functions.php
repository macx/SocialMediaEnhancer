<?php

/**
 * socialDataGrabber
 * hook to get facebook and twitter social counts for each post
 * attach the data to every post using add_action('the_post', 'socialDataGrabber');
 * 
 * @access public
 * @author David Maciejewski
 * @param object $post
 * @return void
 */
function socialDataGrabber($post) {
	$twitterAccount      = 'macx'; // set your twitter account name
	$permalinkUrl        = get_permalink();
	$permalinkUrlEncoded = urlencode($permalinkUrl);
	$postTitle           = get_the_title();
	$postTitle           = preg_replace('/(&#8211;|&#8212;)/i', '-', $postTitle);
	$postTitleLimit      = 90;
	$transientTimeout    = (60 * 15);
	$transientApiKey     = 'post' . get_the_ID() . '_socialInfo';
	
	// debug
	if($_GET['flushSocialGraph'] == 'flushAll') {
		delete_transient($transientApiKey);
	}
	
	// get saved data from wordpress transient api
	// see: http://codex.wordpress.org/Transients_API
	if($socialInfo = get_transient($transientApiKey)) {
		$post->socialInfo = $socialInfo;
	} else {
		$socialInfo = array();
		
		// set timeout
		$context = stream_context_create(array('http' => array('timeout' => 3)));
		
		// get count data from twitter
		$rawData = file_get_contents('http://urls.api.twitter.com/1/urls/count.json?url=' . $permalinkUrl, 0, $context);
		
		if($twitterData = json_decode($rawData)) {
			$socialInfo['twitter']['count'] = intval($twitterData->count);
		} else {
			$socialInfo['twitter']['count'] = intval(0);
		}
		
		// get count data from facebook
		// see: https://developers.facebook.com/docs/reference/api/
		$rawData = file_get_contents('http://graph.facebook.com/?ids=' . $permalinkUrl, 0, $context);
		
		if($facebookData = json_decode($rawData, true)) {
			$socialInfo['facebook']['count'] = intval($facebookData[$permalinkUrl]['shares']);
		} else {
			$socialInfo['facebook']['count'] = intval(0);
		}
		
		// setup google +1 button
		// see: http://code.google.com/intl/de-AT/apis/+1button/
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => 'https://clients6.google.com/rpc',
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $permalinkUrl . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
			CURLOPT_RETURNTRANSFER => 3,
			CURLOPT_TIMEOUT        => 3,
			CURLOPT_CONNECTTIMEOUT => 3
		));
		$rawData = curl_exec($ch);
		curl_close($ch);
		
		if($plusOneData = json_decode($rawData, true)) {
			$socialInfo['plusone']['count'] = intval($plusOneData[0]['result']['metadata']['globalCounts']['count']);
		} else {
			$socialInfo['plusone']['count'] = intval(0);
		}
		
		// setup twitter
		if(strlen($postTitle) > $postTitleLimit) {
			$twitterPostTitle = html_entity_decode(mb_substr($postTitle, 0, $postTitleLimit) . '...');
		} else {
			$twitterPostTitle = html_entity_decode($postTitle);
		}
		
		$message                            = urlencode($twitterPostTitle);
		$socialInfo['twitter']['shareUrl']  = 'http://twitter.com/intent/tweet?related=' . $twitterAccount . '&text=' .$message . '&url=' . $permalinkUrl . '&via=' . $twitterAccount . '&lang=de';
		
		// setup facebook
		$socialInfo['facebook']['shareUrl'] = 'http://www.facebook.com/sharer.php?u=' . $permalinkUrl . '&t=' . urlencode($postTitle);
		
		// setup google +1 button
		$socialInfo['plusone']['shareUrl']  = 'https://plusone.google.com/u/0/+1/profile/?type=po&ru=' . $permalinkUrl;
		
		// attach results to $post object
		$post->socialInfo = $socialInfo;
		
		// save result in api
		set_transient($transientApiKey, $socialInfo, $transientTimeout);
	}
}

add_action('the_post', 'socialDataGrabber');
