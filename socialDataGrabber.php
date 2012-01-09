<?php
/*
Plugin Name: SocialDataGrabber
Plugin URI: https://github.com/macx/SocialDataGrabber
Description: Smart social button integration and counter
Version: 1.0
Update: 2012-01-09
Author: David Maciejewski
Author URI: http://macx.de
*/

class socialDataGrabber {
	public function __construct() {
		add_theme_support('post-thumbnails');
	}

	public function setImageSize() {
		// regular thumbnail
		set_post_thumbnail_size(150, 90, true);

		// specific image sizes
		add_image_size('socialShareSmall', 100, 57, true);
		add_image_size('socialShareBig', 400, 225, true);
	}

	function setMetaData() {
		global $post;

		// set default variables
		$blogTitle   = get_bloginfo('name');
		$title       = $blogTitle;
		$type        = 'website';
		$url         = get_bloginfo('url');
		$description = get_bloginfo('description');
		$image       = get_template_directory_uri() . '/images/share.jpg';

		// override in single view
		if(is_singular()) {
			$title       = preg_replace('/"/', '', $post->post_title);
			$type        = 'article';
			$url         = get_permalink($post->ID);
			$moreTagPos  = strpos($post->post_content, '<!--more');
			$description = substr($post->post_content, 0, $moreTagPos);

			if(function_exists('has_post_thumbnail') && has_post_thumbnail($post->ID)) {
				$postImageData = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'socialShareBig');

				if($postImage = esc_attr($postImageData[0])) {
					$image       = $postImage;
					$imageWidth  = $postImageData[1];
					$imageHeight = $postImageData[2];
				}
			}
		}

		echo "\n\n\t" . '<meta property="og:title" content="' . strip_tags($title) . '">' . "\n\t";
		echo '<meta property="og:type" content="' . $type . '">' . "\n\t";
		echo '<meta property="og:url" content="' . $url . '">' . "\n\t";
		echo '<meta property="og:site_name" content="' . $blogTitle . '">' . "\n\t";
		echo '<meta property="og:description" content="' . strip_tags($description) . '">' . "\n\t";
		echo '<meta property="og:image" content="' . $image . '">' . "\n\t";
		if(isset($imageWidth)) {
			echo '<meta property="og:image:width" content="' . $imageWidth . '">' . "\n\t";
		}
		if(isset($imageHeight)) {
			echo '<meta property="og:image:height" content="' . $imageHeight . '">' . "\n\t";
		}
		echo '<link rel="image_src" href="' . $image . '">' . "\n\n";
	}

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
	function getSocialData($post) {
		$twitterAccount      = 'macx'; // set your twitter account name
		$permalinkUrl        = get_permalink();
		$permalinkUrlEncoded = urlencode($permalinkUrl);
		$postTitle           = get_the_title();
		$postTitle           = preg_replace('/(&#8211;|&#8212;)/i', '-', $postTitle);
		$postTitleLimit      = 90;
		$transientTimeout    = (60 * 15);
		$transientApiKey     = 'post' . get_the_ID() . '_socialInfo';
		$connectionTimeout   = 3; // set your desired connection timeout for external API calls

		// debug
		if(isset($_GET['flushSocialGraph']) && ($_GET['flushSocialGraph'] == 'flushAll')) {
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
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => $connectionTimeout,
				CURLOPT_CONNECTTIMEOUT => $connectionTimeout,
			));
			$rawData = curl_exec($ch);
			curl_close($ch);

			if($twitterData = json_decode($rawData)) {
				$socialInfo['twitter']['count'] = intval($twitterData->count);
			} else {
				$socialInfo['twitter']['count'] = intval(0);
			}

			// get count data from facebook
			// see: https://developers.facebook.com/docs/reference/api/
			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL            => 'http://graph.facebook.com/?ids=' . $permalinkUrl,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => $connectionTimeout,
				CURLOPT_CONNECTTIMEOUT => $connectionTimeout,
			));
			$rawData = curl_exec($ch);
			curl_close($ch);

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
				CURLOPT_TIMEOUT        => $connectionTimeout,
				CURLOPT_CONNECTTIMEOUT => $connectionTimeout,
			));
			$rawData = curl_exec($ch);
			curl_close($ch);

			if($plusOneData = json_decode($rawData, true)) {
				$socialInfo['googleplus']['count'] = intval($plusOneData[0]['result']['metadata']['globalCounts']['count']);
			} else {
				$socialInfo['googleplus']['count'] = intval(0);
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
			$socialInfo['googleplus']['shareUrl']  = 'https://plusone.google.com/u/0/+1/profile/?type=po&ru=' . $permalinkUrl;

			// attach results to $post object
			$post->socialInfo = $socialInfo;

			$oObj = $socialInfo; echo '<pre style="border: 2px solid red; padding: 5px; background: #fff; color: #333; margin: 0 0 10px 0;">' . print_r($oObj, 1) . '</pre>';

			// save result in api
			set_transient($transientApiKey, $socialInfo, $transientTimeout);
		}
	}

	public function addSocialBar($content) {
		global $post;
		
		include_once 'templates/socialShare.php';

		return $content;
	}

	public function includeStylesheet() {
		// Load stylesheet
		$cssPath = plugins_url('socialDataGrabber/sdg.css');

		wp_enqueue_style('socialDataGrabber', $cssPath, '', '1.0');
	}
}

// add theme support and  thumbs
if(function_exists('add_theme_support')) {
	add_theme_support('post-thumbget_template_directory_urianils');
}

// set meta data
add_action('wp_head', array('socialDataGrabber', 'setMetaData'));

add_filter('the_content', array('socialDataGrabber', 'addSocialBar'));

add_action('init', array('socialDataGrabber', 'setImageSize'));

add_action('the_post', array('socialDataGrabber', 'getSocialData'));

add_action('wp_enqueue_scripts', array('socialDataGrabber', 'includeStylesheet'));

