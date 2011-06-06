<?php

add_action('the_post', 'socialDataGrabber');

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
	$permalinkUrl     = urlencode(get_permalink());
	$transientApiKey  = 'post' . get_the_ID() . '_socialInfo';
	$transientTimeout = (60 * 15);
	$twitterAccount   = 'macx'; // set your twitter account name
	$postTitleLimit   = 90;
	
	// debug
	if($_GET['debugMode'] == 1) {
		delete_transient($transientApiKey);
	}
	
	// get saved data from wordpress transient api
	// see: http://codex.wordpress.org/Transients_API
	if($socialInfo = get_transient($transientApiKey)) {
		$post->socialInfo = $socialInfo;
	} else {
		$socialInfo = array();
		
		// get count data from twitter
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => 'http://urls.api.twitter.com/1/urls/count.json?url=' . $permalinkUrl,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FRESH_CONNECT  => 1,
		));
		$rawData = curl_exec($ch);
		curl_close($ch);
		
		if($twitterData = json_decode($rawData)) {
			$socialInfo['twitter']['count'] = $twitterData->count;
		}
		
		// get count data from facebook
		// see: http://developers.facebook.com/docs/reference/rest/links.getStats
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => 'http://api.facebook.com/method/links.getStats?urls=' . $permalinkUrl . '&format=json',
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FRESH_CONNECT  => 1,
		));
		$rawData = curl_exec($ch);
		curl_close($ch);
		
		if($facebookData = json_decode($rawData)) {
			$socialInfo['facebook']['count'] = $facebookData[0]->total_count;
		}
		
		// setup title and links
		$postTitle      = get_the_title();
		$postTitle      = preg_replace('/(&#8211;|&#8212;)/i', '-', $postTitle);
		$postUrl        = get_permalink();
		$postUrlEncoded = ($postUrl);
		
		// setup twitter
		if(strlen($postTitle) > $postTitleLimit) {
			$twitterPostTitle = html_entity_decode(mb_substr($postTitle, 0, $postTitleLimit) . '...');
		} else {
			$twitterPostTitle = html_entity_decode($postTitle);
		}
		
		$message                            = urlencode($twitterPostTitle);
		#$socialInfo['twitter']['shareUrl']  = 'http://twitter.com/share?text=' .$message . '&url=' . $postUrlEncoded . '&related=' . $twitterAccount . '&via=' . $twitterAccount;
		$socialInfo['twitter']['shareUrl']  = 'http://twitter.com/intent/tweet?related=' . $twitterAccount . '&text=' .$message . '&url=' . $postUrlEncoded . '&via=' . $twitterAccount . '&lang=de';
		
		// setup facebook
		$socialInfo['facebook']['shareUrl'] = 'http://www.facebook.com/sharer.php?u=' . $postUrlEncoded . '&t=' . urlencode($postTitle);
		
		// setup google +1 button
		// see: http://code.google.com/intl/de-AT/apis/+1button/
		$socialInfo['plusone']['shareUrl'] = 'https://plusone.google.com/u/0/+1/profile/?type=po&ru=' . $postUrlEncoded;
		
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => 'https://clients6.google.com/rpc?key=AIzaSyCKSbrvQasunBoV16zDH9R33D88CeLr9gQ',
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $postUrl . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
			CURLOPT_FRESH_CONNECT  => 1,
		));
		$rawData = curl_exec($ch);
		curl_close($ch);
		
		if($plusOneData = json_decode($rawData, true)) {
			$socialInfo['plusone']['count'] = $plusOneData[0]['result']['metadata']['globalCounts']['count'];
		}

		// attach results to $post object
		$post->socialInfo = $socialInfo;
		
		// save result in api
		set_transient($transientApiKey, $socialInfo, $transientTimeout);
	}
}
