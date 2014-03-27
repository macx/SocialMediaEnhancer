<?php
/**
 * Plugin Name: SocialMediaEnhancer
 * Plugin URI: https://github.com/macx/SocialMediaEnhancer
 * Description: Smart social button integration and counter
 * Version: 1.8.6
 * Update: 2013-10-02
 * Author: David Maciejewski
 * Author URI: http://macx.de
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 *	Copyright 2011-2013 David Maciejewski (email : PLUGIN AUTHOR EMAIL)
 *
 *	This program is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License, version 2, as
 *	published by the Free Software Foundation.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

add_action('init', array('SocialMediaEnhancer', 'init'));

class SocialMediaEnhancer {
	#protected $pluginPath;

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

		$this->pluginPathName = basename(__DIR__);
		$this->pluginUrl      = plugins_url() . '/' . $this->pluginPathName;

		if(current_user_can('manage_options') && isset($_GET['smeDebug']) && ($_GET['smeDebug'] == 'true')) {
			$this->isDebugMode = true;
		} else {
			$this->isDebugMode = false;
		}

		// add theme support and  thumbs
		if(function_exists('add_theme_support')) {
			add_theme_support('post-thumbget_template_directory_urianils');
		}

		$this->options = get_option('smeOptions', array(
			'general' => array(
				'services' => array(
					'google'    => 1,
					'facebook'  => 1,
					'twitter'   => 1,
					'linkedin'  => 0,
					'pinterest' => 0,
					'xing'      => 0
				),
				'style'     => 'sme',
				'label'     => 1,
				'embed'     => 'begin',
				'opengraph' => array(
					'disable' => 0
				)
			),
			'accounts' => array(
				'google'    => '',
				'facebook'  => '',
				'twitter'   => '',
				'linkedin'  => '',
				'pinterest' => '',
				'xing'      => '',
			)
		));

		if($this->options['general']['embed'] != 'disabled') {
			$this->postContentModified = array();

			add_filter('the_excerpt', array(&$this, 'addSocialButtonsToExcerpt'));
			add_filter('get_the_excerpt', array(&$this, 'addSocialButtonsToExcerpt'));
			add_filter('the_content', array(&$this, 'addSocialButtons'));
		}

		if($this->options['general']['opengraph']['disable'] != 1) {
			add_action('wp_head', array(&$this, 'setMetaData'));
		}

		// set meta data
		add_filter('plugin_row_meta', array(&$this, 'smeOptionsLink'), 10, 2);
		add_shortcode('socialMediaEnhancer', array(&$this, 'smeShortcode'));

		add_action('init', array(&$this, 'smeInit'));
		add_action('the_post', array(&$this, 'getSocialData'));
		add_action('get_the_post', array(&$this, 'getSocialData'));
		add_action('wp_enqueue_scripts', array(&$this, 'includeScripts'));
		add_action('save_post', array(&$this, 'onSavePost'));

		add_theme_support('post-thumbnails');

		// add admin menu
		add_action('admin_menu', array(&$this, 'smeMenu'));
		add_action('admin_init', array(&$this, 'smeRegisterSettings'));

		if(is_admin()) {
			register_activation_hook(__FILE__, array(&$this, 'smeOptionDefaults'));
		}

	}

	public function smeInit() {
		// specific image sizes
		add_image_size('smeSmall', 300, 300, true);
		add_image_size('smeBig', 600, 600, true);

		// i18n
		// load_plugin_textdomain('SocialMediaEnhancer', get_template_directory() . '/languages');
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
		$postImages  = $this->getPostImages($post);

		// override in single view
		if(is_singular()) {
			$title       = preg_replace('/"/', '', $post->post_title);
			$type        = 'article';
			$url         = get_permalink($post->ID);
			$moreTagPos  = strpos($post->post_content, '<!--more');
			$moreTagPos  = ($moreTagPos) ? $moreTagPos: 130;

			// set description
			$description = substr($post->post_content, 0, $moreTagPos);
			$description = strip_shortcodes($description) . '…';
			if($post->post_excerpt) {
				$description = $post->post_excerpt;
			}

			// overwrite description on single image view
			if(!is_page() && $post->post_parent != 0) {
				$title        = trim(substr($post->post_content, 0, 70)) . '&#8230;';
				$description  = $post->post_content;
			}
		}

		if(!$postImages) {
			if($headerImage = get_header_image()) {
				$postImages[] = $headerImage;
			} else {
				$postImages[] = $this->pluginUrl . '/assets/images/smeShare.png';
			}

		}

		echo "\n\n\t" . '<!--// This WordPress Blog is powered by SocialMediaEnhancer -->' . "\n\t";
		echo '<meta property="og:title" content="' . strip_tags($title) . '">' . "\n\t";
		echo '<meta property="og:type" content="' . $type . '">' . "\n\t";
		echo '<meta property="og:url" content="' . $url . '">' . "\n\t";
		echo '<meta property="og:site_name" content="' . $blogTitle . '">' . "\n\t";
		echo '<meta property="og:description" content="' . strip_tags($description) . '">' . "\n\t";

		// author and publisher informations
		if($this->options['accounts']['facebook']) {
			echo '<meta property="article:publisher" content="' . $this->options['accounts']['facebook'] . '">' . "\n\t";
		}
		if($this->options['accounts']['google']) {
			echo '<link href="' . $this->options['accounts']['google'] . '" rel="publisher">' . "\n\t";
		}
		if($this->options['accounts']['twitter']) {
			echo '<meta name="twitter:site" content="@' . $this->options['accounts']['twitter'] . '">' . "\n\t";
		}

		if(!empty($postImages)) {
			$n             = 0;
			$postImageMain = false;

			foreach($postImages AS $image) {
				echo '<meta property="og:image" content="' . $image['url'] . '">' . "\n\t";
				
				if(isset($image['width'])) {
					echo '<meta property="og:image:width" content="' . $image['width'] . '">' . "\n\t";
				}
				
				if(isset($image['height'])) {
					echo '<meta property="og:image:height" content="' . $image['height'] . '">' . "\n\t";
				}

				if(($n == 0) || ($image['name'] == 'smeSmall')) {
					$postImageMain = $image['url'];
				}
				$n++;
			}

			if($postImageMain) {
				echo '<link rel="image_src" href="' . $postImageMain . '">' . "\n\t";
				$this->options['postImage'] = $postImageMain;				
			}
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
	function getSocialData($post = false) {
		if($post === false) {
			return false;
		}

		$twitterAccount      = $this->options['accounts']['twitter'];
		$permalinkUrl        = get_permalink($post->ID);
		$permalinkUrlEncoded = urlencode($permalinkUrl);
		$postTitle           = get_the_title($post->ID);
		$postTitle           = preg_replace('/(&#8211;|&#8212;)/i', '-', $postTitle);
		$postTitleEncoded    = urlencode($postTitle);
		$postTitleLimit      = 90;
		$postExcerptEncoded  = urlencode($post->excerpt);
		$transientTimeout    = (60 * 60);
		$transientApiKey     = 'post' . $post->ID . '_socialInfo';
		$connectionTimeout   = 3; // set your desired connection timeout for external API calls

		// refresh transient if the admin is forcing the debug mode
		if($this->isDebugMode) {
			delete_transient($transientApiKey);
		}

		// get saved data from wordpress transient api
		// see: http://codex.wordpress.org/Transients_API
		$socialInfo = get_transient($transientApiKey);

		if($socialInfo && ($this->isDebugMode == false)) {
			$post->socialInfo = $socialInfo;
		} else {
			$cntComments = wp_count_comments($post->ID)->approved;
			$socialInfo  = array(
				'comments' => $cntComments,
				'shares'   => 0,
				'total'    => 0
			);

			// setup google +1 button
			if($this->options['general']['services']['google'] == 1) {
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

				// increase share counter
				if($socialInfo['google']['count'] > 0) {
					$socialInfo['shares'] = abs($socialInfo['shares'] + $socialInfo['google']['count']);
				}

				// setup google +1 button
				$socialInfo['google']['shareUrl']   = 'https://plus.google.com/share?url=' . $permalinkUrl;
			} else {
				$socialInfo['google']['count'] = intval(0);
			}

			// get count data from twitter
			if($this->options['general']['services']['twitter'] == 1) {
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

				// setup twitter
				if(strlen($postTitle) > $postTitleLimit) {
					$twitterPostTitle = html_entity_decode(mb_substr($postTitle, 0, $postTitleLimit) . '...');
				} else {
					$twitterPostTitle = html_entity_decode($postTitle);
				}

				// increase share counter
				if($socialInfo['twitter']['count'] > 0) {
					$socialInfo['shares'] = abs($socialInfo['shares'] + $socialInfo['twitter']['count']);
				}

				$message                            = urlencode($twitterPostTitle);
				$related                            = ($twitterAccount) ? '&related=' . $twitterAccount: '';
				$via                                = ($twitterAccount) ? '&via=' . $twitterAccount: '';
				$socialInfo['twitter']['shareUrl']  = 'http://twitter.com/intent/tweet?text=' .$message . '&url=' . $permalinkUrl . $related . $via . '&lang=de';
			} else {
				$socialInfo['twitter']['count'] = intval(0);
			}

			// get count data from facebook
			if($this->options['general']['services']['facebook'] == 1) {
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

				// increase share counter
				if($socialInfo['facebook']['count'] > 0) {
					$socialInfo['shares'] = abs($socialInfo['shares'] + $socialInfo['facebook']['count']);
				}

				// setup facebook
				$socialInfo['facebook']['shareUrl'] = 'http://www.facebook.com/sharer.php?u=' . $permalinkUrl . '&amp;t=' . urlencode($postTitle);
			} else {
				$socialInfo['facebook']['count'] = intval(0);
			}

			// get count data from linkedin
			if($this->options['general']['services']['linkedin'] == 1) {
				$ch = curl_init();
				curl_setopt_array($ch, array(
					CURLOPT_URL            => 'http://www.linkedin.com/countserv/count/share?url=' . $permalinkUrl . '&amp;format=json',
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

				// increase share counter
				if($socialInfo['linkedin']['count'] > 0) {
					$socialInfo['shares'] = abs($socialInfo['shares'] + $socialInfo['linkedin']['count']);
				}

				// setup linkedin button
				// @see https://developer.linkedin.com/documents/share-linkedin
				// @todo add &source=blog_title
				$socialInfo['linkedin']['shareUrl'] = 'http://www.linkedin.com/shareArticle?mini=true&amp;url=' . $permalinkUrlEncoded . '&amp;title=' . $postTitleEncoded . '&amp;summary=' . $postExcerpt;
			} else {
				$socialInfo['linkedin']['count'] = intval(0);
			}

			// get count data from pinterest
			if($this->options['general']['services']['pinterest'] == 1) {
				// get post image from post
				$ch = curl_init();
				curl_setopt_array($ch, array(
					CURLOPT_URL            => 'http://api.pinterest.com/v1/urls/count.json?url=' . $permalinkUrl,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_TIMEOUT        => $connectionTimeout,
					CURLOPT_CONNECTTIMEOUT => $connectionTimeout,
				));
				$rawData = curl_exec($ch);
				curl_close($ch);

				if($linkedinData = json_decode($rawData, true)) {
					$socialInfo['pinterest']['count'] = intval($linkedinData['count']);
				} else {
					$socialInfo['pinterest']['count'] = intval(0);
				}

				// increase share counter
				if($socialInfo['pinterest']['count'] > 0) {
					$socialInfo['shares'] = abs($socialInfo['shares'] + $socialInfo['pinterest']['count']);
				}

				// setup pinterest button
				// @2to add &media=thumbnail
				$socialInfo['pinterest']['shareUrl'] = 'http://pinterest.com/pin/create/button/?url=' . $permalinkUrlEncoded . '&amp;media=' . urlencode($this->options['postImages'][0]['url']) . '&amp;description=' . $postExcerpt;
			} else {
				$socialInfo['pinterest']['count'] = intval(0);
			}

			// get count data from xing
			if($this->options['general']['services']['xing'] == 1) {
				// Get the whole xing-button
				$ch = curl_init();
				curl_setopt_array($ch, array(
					CURLOPT_URL            => 'https://www.xing-share.com/app/share?op=get_share_button;url=' . $permalinkUrlEncoded . ';counter=right;lang=de;type=iframe;hovercard_position=1;shape=square',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSL_VERIFYPEER => false,
				));
				$res = curl_exec($ch);

				curl_close($ch);
				//Find the interesting part to strip
				preg_match("'<span class=\"xing-count right\">(.*?)</span>'si", $res, $matches);

				//To make sure there is a count for that site
				if( isset( $matches ) ) {
					$socialInfo['xing']['count'] = intval($matches[1]);
				} else {
					$socialInfo['xing']['count'] = intval(0);
				}

				// increase share counter
				if($socialInfo['xing']['count'] > 0) {
					$socialInfo['shares'] = abs($socialInfo['shares'] + $socialInfo['xing']['count']);
				}

				// setup xing button
				$socialInfo['xing']['shareUrl'] = 'https://www.xing-share.com/app/user?op=share;sc_p=xing-share;url=' . $permalinkUrlEncoded;
			} else {
				$socialInfo['xing']['count'] = intval(0);
			}

			// count total shares and comments
			$socialInfo['total'] = abs($socialInfo['comments'] + $socialInfo['shares']);

			// attach results to $post object
			$post->socialInfo = $socialInfo;

			// save result in api
			set_transient($transientApiKey, $socialInfo, $transientTimeout);
		}

		return $post->socialInfo;
	}

	/**
	 * get all attached images from a post
	 * @param  object $post [the post object]
	 * @return array        [image array]
	 */
	public function getPostImages($post = false) {
		if($post == false) {
			return false;
		}

		$postImagesSizes = array('smeSmall', 'smeBig', 'thumbnail', 'medium', 'large');
		$images          = array();

		// get the attached images
		if(function_exists('has_post_thumbnail') && has_post_thumbnail($post->ID)) {
			foreach($postImagesSizes AS $size) {
				$postImageData = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), $size);

				if(($postImageData[3] !== false) && ($postImage = esc_attr($postImageData[0]))) {
					$images[] = array(
						'name'   => $size,
						'url'    => $postImage,
						'width'  => $postImageData[1],
						'height' => $postImageData[2]
					);
				}
			}
		}

		$cntImages = count($images);

		// if no images is attached, try to get the image header or fall back to default
		if($cntImages < 1) {
			if($postHeaderImage = get_header_image()) {
				$images[] = array(
					'name' => 'headerImage',
					'url'  => $postHeaderImage
				);
			} else {
				$images[] = array(
					'name'   => 'default',
					'url'    => $this->pluginUrl . '/assets/images/smeDefault.png',
					'width'  => 300,
					'height' => 300
				);
			}
		}

		$this->options['postImages'] = $images;

		return $images;
	}

	public function addSocialButtonsToExcerpt($content = '') {
		global $post;

		unset($this->postContentModified[$post->ID]);
		$content = $this->addSocialButtons($content, true);

		return $content;
	}

	public function addSocialButtons($content = '', $isExcerpt = false) {
		global $post;

		if(!isset($post->socialInfo) || ($this->options['general']['embed'] == 'disabled') || isset($this->postContentModified[$post->ID]) || (($isExcerpt == false) && ($this->options['general']['embed'] == 'begin'))) {
			return $content;
		}

		// get the button template
		ob_start();
		include 'templates/socialButtons.php';
		$smeButtons = ob_get_contents();
		ob_end_clean();

		if($this->options['general']['embed'] == 'begin') {
			$content = $smeButtons . $content;
			$this->postContentModified[$post->ID] = true;
		} else {
			if(is_singular() && $isExcerpt) {
			} else {
				$content = $content . $smeButtons;
				$this->postContentModified[$post->ID] = true;
			}
		}

		return $content;
	}

	public function includeScripts() {
		$pluginPath = $this->pluginUrl . '/assets/';

		wp_enqueue_style('smeStyle', $pluginPath . 'css/sme.css', '', '1.1');
		wp_enqueue_script('smeScript', $pluginPath . 'js/sme.js', array('jquery'), '1.2');
	}

	public function smeShortcode($attr, $content = '') {
		global $post, $wp_locale;

		static $instance = 0;
		$instance++;

		extract(shortcode_atts(array(
			'theme' => 'light'
		), $attr));

		ob_start();
		include 'templates/socialButtons.php';
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

	public function onSavePost($postId) {
		$transientApiKey     = 'post' . $postId . '_socialInfo';
		delete_transient($transientApiKey);
	}
}

function smeButtons($postId = null) {
	if(preg_match('/^[0-9]+$/', $postId)) {
		$post = get_post($postId);
	} elseif($postId == null) {
		return false;
	}

	$sme        = new SocialMediaEnhancer();
	$socialData = $sme->getSocialData($post);

	include $sme->pluginPath . '/templates/socialButtons.php';
}
