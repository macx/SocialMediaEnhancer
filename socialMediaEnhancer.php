<?php
/*
Plugin Name: SocialMediaEnhancer
Plugin URI: https://github.com/macx/SocialMediaEnhancer
Description: WprdPress PLugin to enhance your blog
Version: 1.6
Update: 2012-04-16
Author: David Maciejewski
Author URI: http://macx.de/+
*/

add_action('init', array('SocialMediaEnhancer', 'init'));

class SocialMediaEnhancer {
	protected $pluginPath;

	protected $pluginUrl;

	public static function init() {
		new self;
	}
	
	public function __construct() {
		global $wpdb;

		$this->wpdb = &$wpdb;

		$this->pluginPath = dirname(__FILE__);

		$this->pluginUrl = WP_PLUGIN_URL . '/SocialMediaEnhancer';

		// add theme support and  thumbs
		if(function_exists('add_theme_support')) {
			add_theme_support('post-thumbget_template_directory_urianils');
		}

		// set meta data
		add_action('wp_head', array(&$this, 'setMetaData'));

		add_filter('the_content', array(&$this, 'addSocialBar'));

		add_action('init', array(&$this, 'setImageSize'));

		add_action('the_post', array(&$this, 'getSocialData'));

		add_action('wp_enqueue_scripts', array(&$this, 'includeStylesheet'));

		add_theme_support('post-thumbnails');

		// add admin menu
		add_action('admin_menu', array(&$this, 'smeMenu'));
		add_action('admin_init', array(&$this, 'smeRegisterSettings'));

		$this->options = get_option('smeOptions');
	}

	public function setImageSize() {
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
	* SocialMediaEnhancer
	* hook to get facebook and twitter social counts for each post
	* attach the data to every post using add_action('the_post', 'SocialMediaEnhancer');
	*
	* @access public
	* @author David Maciejewski
	* @param object $post
	* @return void
	*/
	function getSocialData($post) {
		$options             = get_option('smeOptions');

		$twitterAccount      = $options['accounts']['twitter'];
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

			// get count data from linkedin
			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL            => 'http://www.linkedin.com/countserv/count/share?url=' . $permalinkUrl . '&format=json',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => $connectionTimeout,
				CURLOPT_CONNECTTIMEOUT => $connectionTimeout,
			));
			$rawData = curl_exec($ch);
			curl_close($ch);

			if($linkedinData = json_decode($rawData, true)) {
				$socialInfo['linkedin']['count'] = intval($linkedinData['count']);
			} else {
				$socialInfo['linkedin']['count'] = intval(0);
			}

			// setup twitter
			if(strlen($postTitle) > $postTitleLimit) {
				$twitterPostTitle = html_entity_decode(mb_substr($postTitle, 0, $postTitleLimit) . '...');
			} else {
				$twitterPostTitle = html_entity_decode($postTitle);
			}

			$message                            = urlencode($twitterPostTitle);
			$related                            = ($twitterAccount) ? '&related=' . $twitterAccount: '';
			$via                                = ($twitterAccount) ? '&via=' . $twitterAccount: '';
			$socialInfo['twitter']['shareUrl']  = 'http://twitter.com/intent/tweet?text=' .$message . '&url=' . $permalinkUrl . $related . $via . '&lang=de';

			// setup facebook
			$socialInfo['facebook']['shareUrl'] = 'http://www.facebook.com/sharer.php?u=' . $permalinkUrl . '&t=' . urlencode($postTitle);

			// setup google +1 button
			$socialInfo['googleplus']['shareUrl'] = 'https://plusone.google.com/u/0/+1/profile/?type=po&ru=' . $permalinkUrl;

			// attach results to $post object
			$post->socialInfo = $socialInfo;

			// save result in api
			set_transient($transientApiKey, $socialInfo, $transientTimeout);
		}
	}

	public function addSocialBar($content) {
		global $post;

		if($this->options['general']['embed'] != 'disabled') {
			ob_start();
			include 'templates/socialShare.php';
			$socialBar = ob_get_contents();
			ob_end_clean();

			if($this->options['general']['embed'] == 'end') {
				$content = $content . $socialBar;
			} else {
				$content = $socialBar . $content;
			}
		}

		return $content;
	}

	public function includeStylesheet() {
		// Load stylesheet
		$cssPath = plugins_url( 'sdg.css', __FILE__ );

		wp_enqueue_style('socialMediaEnhancer', $cssPath, '', '1.0');
	}



	/**
	 * Register Options Page Settings
	 */
	public function smeRegisterSettings() {
		register_setting('smeOptions', 'smeOptions', array(&$this, 'smeOptionsValidate'));
	}

	public function smeMenu() {
		// Add a submenu under Settings
		add_options_page(__('SocialMediaEnhancer Settings', 'smeOptionsTitle'), __('SocialMediaEnhancer', 'smeOptionsMenuTitle'), 'manage_options', 'sme-options', array(&$this, 'smeOptionsPage'));
	}

	/**
	 * Display options page
	 */
	public function smeOptionsPage() {
		// check the capability
		if(!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		$options = get_option('smeOptions');

		include_once $this->pluginPath . '/templates/options.php';
	}

	public function smeOptionsValidate($input) {
		return $input;
	}
}

#SocialMediaEnhancer::init();

