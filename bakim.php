<?php

/*
Plugin Name: Bakim
Version: 1.4
Description: Simple maintenance mode plugin with a toggle in the adminbar. Why this plugin name? Bakim means "maintenance" in Turkish.
Plugin URI: https://github.com/lutrov/bakim
Copyright: 2020, Ivan Lutrov
Author: Ivan Lutrov
Author URI: http://lutrov.com

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc., 51 Franklin
Street, Fifth Floor, Boston, MA 02110-1301, USA. Also add information on how to
contact you by electronic and paper mail.
*/

defined('ABSPATH') || die();

//
// Activate maintenance mode.
//
add_action('get_header', 'bakim_header_action', 10, 0);
function bakim_header_action() {
	if (current_user_can('administrator') == false) {
		$maintenance = get_option('bakim_maintenance_mode');
		if (empty($maintenance) == false) {
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
			if (headers_sent() == false) {
				header('HTTP/1.1 503 Service Unavailable');
				header('Content-Type: text/html; charset=' . $atts['charset']);
			}
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
		<meta name="kurac" content="mine">
		<meta name="viewport" content="width=device-width">
		<meta name="robots" content="noindex, follow">
		<link href="<?php echo $atts['icon']['guid']; ?>" type="<?php echo $atts['icon']['type']; ?>" rel="icon">
		<title><?php echo $atts['title']; ?></title>
		<style type="text/css">
			html {
				margin: 0;
				background: dimgray;
				padding: 0;
			}
			@media screen and (min-width: 360px) {
				html {
					background: dimgray url(<?php echo $atts['background']; ?>) center center fixed;
					background-size: cover;
					background-repeat: no-repeat;
					background-blend-mode: overlay;
				}
			}
			body {
				height: 100vh;
				margin: 0;
				padding: 0;
				background: none;
				box-shadow: none;
				color: white;
			}
			section {
				margin: 0;
				padding: 0;
				position: fixed;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);
			}
			p {
				padding: 20px;
				background-color: rgba(40, 40, 40, .8);
				font: normal bold 32px/1.1 Helvetica, Arial, sans-serif;
				text-align: center;
			}
			a {
				text-decoration: underline;
			}
			img {
				display: block;
				margin: 0 auto;
			}
		</style>
	</head>
	<body>
		<section>
			<?php if (empty($atts['logo']['guid']) == false): ?>
				<a href="<?php echo home_url(); ?>" title="<?php echo $atts['sitename']; ?>"><img src="<?php echo $atts['logo']['guid']; ?>"></a>
			<?php endif; ?>
			<p><?php echo $atts['message']; ?></p>
		</section>
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
