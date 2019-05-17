<?php

/*
Plugin Name: Bakim
Description: Simple maintenance mode plugin with a toggle in the adminbar. Why this plugin name? Bakim means "maintenance" in Turkish.
Plugin URI: https://github.com/lutrov/bakim
Author: Ivan Lutrov
Author URI: http://lutrov.com/
Version: 1.0
*/

defined('ABSPATH') || die();

//
// Activate maintenance mode.
//
add_action('get_header', 'bakim_header_action', 10, 0);
function bakim_header_action() {
	if (current_user_can('administrator') == false) {
		$maintenance = get_option('bakim_maintenance_mode');
		if (strlen($maintenance) > 0) {
			if (headers_sent() == false) {
				header('HTTP/1.1 503 Service Unavailable');
				header('Content-Type: text/html; charset=utf-8');
			}
			$atts = array(
				'title' => __('Website Under Maintenance'),
				'language' => get_option('WPLANG'),
				'charset' => get_option('blog_charset'),
				'sitename' => get_option('blogname'),
				'icon' => bakim_site_image('icon'),
				'logo' => bakim_site_image('logo'),
				'background' => sprintf('%s/images/background.jpg', plugins_url(null, __FILE__)),
				'message' => __('We are performing <em>scheduled maintenance</em> and will be back online shortly!')
			);
			bakim_template_html($atts);
			die();
		}
	}
}

//
// Maintenance mode template.
//
function bakim_template_html($atts) {
?>
<!DOCTYPE html>
<html lang="<?php echo $atts['language']; ?>">
<head>
<meta charset="<?php echo $atts['charset']; ?>">
<meta name="viewport" content="width=device-width">
<meta name="robots" content="noindex,nofollow">
<link href="<?php echo $atts['icon']['guid']; ?>" type="<?php echo $atts['icon']['type']; ?>" rel="icon">
<title><?php echo $atts['title']; ?></title>
<style type="text/css">
@import url('https://fonts.googleapis.com/css?family=Montserrat');
html {
	margin: 0;
	background: slategray url(<?php echo $atts['background']; ?>) center center fixed;
	background-size: cover;
	background-repeat: no-repeat;
	background-blend-mode: overlay;
	padding: 0;
}
@media screen and (max-width: 800px) {
	html {
		background-size: 1920px 1280px;
	}
}
body {
	margin: 0;
	padding: 0;
	background: none;
	box-shadow: none;
	color: white;
}
p {
	margin: 0;
	padding: 0;
	position: fixed;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	font: normal bold 32px/1.1 Montserrat, sans-serif;
	text-align: center;
}
img {
	display: block;
	margin: 0 auto;
}
</style>
</head>
<body>
<p><img src="<?php echo $atts['logo']['guid']; ?>" title="<?php echo $atts['sitename']; ?>"><br><?php echo $atts['message']; ?></p>
</body>
</html>
<?php
}

//
// Get site logo or icon image from media library.
//
function bakim_site_image($type) {
	$image = array('guid' => null, 'type' => null, 'size' => array());
	switch ($type) {
		case 'logo':
			$post = get_theme_mod('custom_logo');
			break;
		case 'icon':
			$post = get_option('site_icon');
			break;
		default:
			$post = null;
	}
	if (empty($post) == false) {
		$post = get_post($post);
		if (empty($post) == false) {
			if ($post->post_type == 'attachment') {
				$metadata = wp_get_attachment_metadata($post->ID);
				if (isset($metadata['width']) == true && isset($metadata['height']) == true) {
					$image = array(
						'guid' => $post->guid,
						'type' => $post->post_mime_type,
						'size' => array($metadata['width'], $metadata['height'])
					);
				}
			}
		}
	}
	return $image;
}

//
// Toggle maintenance mode in admin.
//
add_action('wp_before_admin_bar_render', 'bakim_toggle_action', 20, 0);
function bakim_toggle_action() {
	global $wp_admin_bar;
	if (is_admin() == true) {
		$request = $_SERVER['REQUEST_URI'];
		parse_str(parse_url($request, PHP_URL_QUERY), $query);
		if (isset($query['maintenance']) == true) {
			if ($query['maintenance'] == 'on') {
				update_option('bakim_maintenance_mode', date_i18n('Y-m-d H:i:s'), DAY_IN_SECONDS);
			} elseif ($query['maintenance'] == 'off') {
				update_option('bakim_maintenance_mode', null);
			}
			$request = remove_query_arg('maintenance', $request);
		}
		$maintenance = get_option('bakim_maintenance_mode');
		$wp_admin_bar->add_menu(array(
			'id' => 'bakim-maintenance-mode-toggle',
			'title' => sprintf('<span class="ab-label" style="color:%s;font-style:normal">%s</span>', empty($maintenance) == false ? '#d4d400' : 'inherit', sprintf('%s %s', __('Maintenance'), strtoupper(empty($maintenance) == false ? __('on') : __('off')))),
			'meta' => array(
				'title' => sprintf('%s %s', __('Switch maintenance'), strtoupper(empty($maintenance) == false ? __('off') : __('on')))
			),
			'href' => sprintf('%s%smaintenance=%s', $request, strpos($request, '?') > 0 ? '&' : '?', empty($maintenance) == false ? __('off') : __('on'))
		));
	}
}

?>