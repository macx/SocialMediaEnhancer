<?php

add_action('the_post', 'socialDataGrabber');

/**
 * socialDataGrabber
 * hook to get facebook and twitter social counts for each post
 * attach the data to every post using add_action('the_post', 'yeeGetSocialData');
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
	$twitterAccount   = 'yeebase_t3n';
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
		$postUrlEncoded = urlencode($postUrl);
		
		// setup twitter
		if(strlen($postTitle) > $postTitleLimit) {
			$twitterPostTitle = mb_substr($postTitle, 0, $postTitleLimit) . '...';
		} else {
			$twitterPostTitle = $postTitle;
		}
		
		$message                            = urlencode($twitterPostTitle . ' via @') . $twitterAccount;
		$socialInfo['twitter']['shareUrl']  = 'http://twitter.com/share?text=' .$message . '&url=' . $postUrlEncoded;
		
		// setup facebook
		$socialInfo['facebook']['shareUrl'] = 'http://www.facebook.com/sharer.php?u=' . $postUrlEncoded . '&t=' . urlencode($postTitle);

		// attach results to $post object
		$post->socialInfo = $socialInfo;
		
		// save result in api
		set_transient($transientApiKey, $socialInfo, $transientTimeout);
	}
}