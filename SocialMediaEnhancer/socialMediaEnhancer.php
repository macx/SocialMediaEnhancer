<?php
/*
Plugin Name: SocialMediaEnhancer
Plugin URI: https://github.com/macx/SocialMediaEnhancer
Description: Smart social button integration and counter
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

		$this->wpdb             = &$wpdb;
		$this->pluginPath       = dirname(__FILE__);
		$this->pluginBaseName   = plugin_basename(__FILE__);
		$this->pluginBaseFolder = plugin_basename(dirname(__FILE__));
		$this->pluginFileName   = str_replace($this->pluginBaseFolder . '/', '', $this->pluginBaseName);
		$this->pluginUrl        = WP_PLUGIN_URL . '/' . $this->pluginBaseFolder;

		// add theme support and  thumbs
		if(function_exists('add_theme_support')) {
			add_theme_support('post-thumbget_template_directory_urianils');
		}

		// set meta data
		add_filter('the_content', array(&$this, 'addSocialBar'));
		add_filter('plugin_row_meta', array(&$this, 'smeOptionsLink'), 10, 2);

		add_action('init', array(&$this, 'smeInit'));
		add_action('wp_head', array(&$this, 'setMetaData'));
		add_action('the_post', array(&$this, 'getSocialData'));
		add_action('get_the_post', array(&$this, 'getSocialData'));
		add_action('wp_enqueue_scripts', array(&$this, 'includeScripts'));

		add_shortcode('socialMediaEnhancer', array(&$this, 'smeShortcode'));

		add_theme_support('post-thumbnails');

		// add admin menu
		add_action('admin_menu', array(&$this, 'smeMenu'));
		add_action('admin_init', array(&$this, 'smeRegisterSettings'));

		if(is_admin()) {
			register_activation_hook(__FILE__, array(&$this, 'smeOptionDefaults'));
		}

		$this->options = get_option('smeOptions', array(
			'general' => array(
				'services' => array(
					'google'   => 1,
					'facebook' => 1,
					'twitter'  => 1
				),
				'style' => 'light',
				'embed' => 'begin'
			)
		));
	}

	public function smeInit() {
		// specific image sizes
		add_image_size('socialShareSmall', 100, 57, true);
		add_image_size('socialShareBig', 400, 225, true);

		// i18n
		load_plugin_textdomain('socialMediaEnhancer', false, 'socialMediaEnhancer/languages' );
	}

	public function smeOptionDefaults() {
		update_option('sdgOptions', $this->options);
	}
	
	function setMetaData() {
		global $post;

		// set default variables
		$blogTitle   = get_bloginfo('name');
		$title       = $blogTitle;
		$type        = 'website';
		$url         = get_bloginfo('url');
		$description = get_bloginfo('description');

		// override in single view
		if(is_singular()) {
			$title       = preg_replace('/"/', '', $post->post_title);
			$type        = 'article';
			$url         = get_permalink($post->ID);
			$moreTagPos  = strpos($post->post_content, '<!--more');
			$description = substr($post->post_content, 0, $moreTagPos);
			$description = strip_shortcodes($description);

			if($post->post_excerpt) {
				$description = $post->post_excerpt;
			}

			if(function_exists('has_post_thumbnail') && has_post_thumbnail($post->ID)) {
				$postImagesSizes = array('preview', 'large');
				$images          = array();

				foreach($postImagesSizes AS $size) {
					$postImageData = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), $size);

					if($postImage = esc_attr($postImageData[0])) {
						$images[] = array(
							'url'    => $postImage,
							'width'  => $postImageData[1],
							'height' => $postImageData[2]
						);
					}
				}
				
				// overwrite description on single image view
				if($post->post_parent != 0) {
					$title        = trim(substr($post->post_content, 0, 70)) . '&#8230;';
					$description  = $post->post_content;
				}
			}

			// embedded youtube
			$youtubeId = get_post_custom_values('youtube', false);
			$youtubeId = $youtubeId[0];
		}

		if(!$image) {
			if($headerImage = get_header_image()) {
				$image = $headerImage;
			} else {
				$image = $this->pluginUrl . '/images/smeShare.png';
			}
		}

		echo "\n\n\t" . '<meta property="og:title" content="' . strip_tags($title) . '">' . "\n\t";
		echo '<meta property="og:type" content="' . $type . '">' . "\n\t";
		echo '<meta property="og:url" content="' . $url . '">' . "\n\t";
		echo '<meta property="og:site_name" content="' . $blogTitle . '">' . "\n\t";
		echo '<meta property="og:description" content="' . strip_tags($description) . '">' . "\n\t";
		if(!empty($images)) {
			$n = 0;
			foreach($images AS $image) {
				echo '<meta property="og:image" content="' . $image['url'] . '">' . "\n\t";
				echo '<meta property="og:image:width" content="' . $image['width'] . '">' . "\n\t";
				echo '<meta property="og:image:height" content="' . $image['height'] . '">' . "\n\t";

				if($n == 0) {
					echo '<link rel="image_src" href="' . $image['url'] . '">' . "\n";
				}
				$n++;
			}
		} else {
			echo '<link rel="image_src" href="' . $image . '">' . "\n";
		}
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
		$twitterAccount      = $this->options['accounts']['twitter'];
		$permalinkUrl        = get_permalink();
		$permalinkUrlEncoded = urlencode($permalinkUrl);
		$postTitle           = get_the_title();
		$postTitle           = preg_replace('/(&#8211;|&#8212;)/i', '-', $postTitle);
		$postTitleEncoded    = urlencode($postTitle);
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
				$socialInfo['google']['count'] = intval($plusOneData[0]['result']['metadata']['globalCounts']['count']);
			} else {
				$socialInfo['google']['count'] = intval(0);
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
			$socialInfo['google']['shareUrl']   = 'https://plus.google.com/share?url=' . $permalinkUrl;

			// setup linkedin button
			// @see https://developer.linkedin.com/documents/share-linkedin
			$socialInfo['linkedin']['shareUrl'] = 'http://www.linkedin.com/shareArticle?mini=true&url=' . $permalinkUrlEncoded . '&title=' . $postTitleEncoded;

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

	public function includeScripts() {
		$pluginPath = $this->pluginUrl . '/assets/';

		wp_enqueue_style('smeStyle', $pluginPath . 'sme.css', '', '1.0');
		wp_enqueue_script('smeScript', $pluginPath . 'sme.js', '', '1.0');
	}

	public function smeShortcode($attr, $content = '') {
		global $post, $wp_locale;

		static $instance = 0;
		$instance++;

		extract(shortcode_atts(array(
			'theme' => 'light'
		), $attr));

		ob_start();
		include 'templates/socialShare.php';
		$buttons = ob_get_contents();
		$output  = "\n" . $buttons . "\n";
		ob_end_clean();

		return $output;
	}

	/**
	 * Register Options Page Settings
	 */
	public function smeRegisterSettings() {
		register_setting('smeOptions', 'smeOptions', array(&$this, 'smeOptionsValidate'));
	}

	public function smeMenu() {
		// Add a submenu under Settings
		add_options_page(__('SocialMediaEnhancer Settings', 'smeOptionsTitle'), __('SocialMediaEnhancer', 'smeOptionsMenuTitle'), 'manage_options', $this->pluginBaseName, array(&$this, 'smeOptionsPage'));
	}

	/**
	 * Display options page
	 */
	public function smeOptionsPage() {
		// check the capability
		if(!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		include_once $this->pluginPath . '/templates/options.php';
	}

	public function smeOptionsValidate($input) {
		return $input;
	}

	public function smeOptionsLink($links, $file) {
		if($file == $this->pluginBaseName) {
			return array_merge(
				$links,
				array(sprintf('<a href="options-general.php?page=%s">%s</a>', $this->pluginBaseName, __('Settings')))
			);
		}

		return $links;
	}
}

#SocialMediaEnhancer::init();

