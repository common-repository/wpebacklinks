<?php
/*
Plugin Name: WPeBacklinks
Plugin URI: http://www.netmdp.com/wpebacklinks
Description: Show multiple instances widget with the assigned links from BackLinks site for page content and textlinks on different sections, pages and posts.
Version: 1.2
Author: etruel
Author URI: http://www.netmdp.com/
License: GPL2
*/

/*  Copyright 2012  etruel  (email : esteban@netmdp.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
$backlinks_key = '';

add_filter( "plugin_action_links", "WPeBacklinksmenu", 10, 2);

function WPeBacklinksmenu($links, $file) {
	if ($file == plugin_basename(__FILE__)) {
		array_push($links, '<a href="plugins.php?page=backlinks-key-config">Content Links</a>');
		array_push($links, '<a href="widgets.php">Widgets Links</a>');
	}
	return $links;
}


function backlinks_init() {
	global $backlinks_key;

	add_action('admin_menu', 'backlinks_config_page');
}

add_action('init', 'backlinks_init');

function backlinks_config_page() {
	if ( function_exists('add_submenu_page') )
		add_submenu_page('plugins.php', __('WPeBackLinks'), __('WPeBackLinks'), 'manage_options', 'backlinks-key-config', 'backlinks_conf');
	
}

function backlinks_conf() {
	global $backlinks_key;

    $ms = array();

	if ( isset($_POST['submit']) ) {
		if ( function_exists('current_user_can') && !current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));

		$key = preg_replace( '/[^A-Z0-9\-]/', '', $_POST['key'] );

		if ( empty($key) ) {
			$key_status = 'empty';
			$ms[] = 'key_empty';
			delete_option('backlinks_key');
		} 
        else {
			$key_status = 'valid';
			update_option('backlinks_key', $key);
			update_option('backlinks_open_in_nw', intval($_POST['open_in_nw']));
        }
	}


	$messages = array (
		'key_empty' => array('color' => 'aa0', 'text' => __('Please enter a BackLinks key. (<a href="http://www.backlinks.com/login.php" style="color:#fff">Get your key.</a>)')),
		'key_valid' => array('color' => '2d2', 'text' => __('This key is valid.')) );
?>
<?php if ( !empty($_POST ) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
<?php endif; ?>
<div class="wrap">
<h2><?php _e('BackLinks My Content Link Pages Configuration'); ?></h2>
<div class="narrow">
<form action="" method="post" id="backlinks-conf" style="margin: auto; width: 400px; ">
If you have configured this site as Content Link Page in Backlinks.com then you must fill this field with your:
<h3><label for="key"><?php _e('BackLinks.com Key'); ?></label></h3>
<?php foreach ( $ms as $m ) : ?>
	<p style="padding: .5em; background-color: #<?php echo $messages[$m]['color']; ?>; color: #fff; font-weight: bold;"><?php echo $messages[$m]['text']; ?></p>
<?php endforeach; ?>
<p><input id="key" name="key" type="text" size="15" maxlength="14" value="<?php echo get_option('backlinks_key'); ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;" /> (<?php _e('<a href="http://www.backlinks.com/WordPress/how-to-install.php">What is this?</a>'); ?>)</p>

<p><input id="open_in_nw" name="open_in_nw" type="checkbox" value="1" <?=((!get_option('backlinks_open_in_nw'))?'':'checked')?> /> Open partner links in a new window</p>

<p class="submit"><input type="submit" name="submit" value="<?php _e('Update options &raquo;'); ?>" /></p>
</form>
<P style="margin: auto; width: 470px;"><A HREF="http://www.backlinks.com/?aff=52126" target="_blank">
<IMG SRC="http://www.backlinks.com/images/468X60.jpg" BORDER="0" WIDTH="468" HEIGHT="60" ALT="Buy and Sell text links">
</A></P>

</div>
</div>
<?php
}

function backlinks_content_links() {
	global $backlinks_key;

    if(!$backlinks_key) $backlinks_key = get_option('backlinks_key');

    $OpenInNewWindow = intval(get_option('backlinks_open_in_nw'));

    // # DO NOT MODIFY ANYTHING ELSE BELOW THIS LINE!
    // ----------------------------------------------
    $BLKey = $backlinks_key;

    $QueryString  = "LinkUrl=".urlencode((($_SERVER['HTTPS']=='on')?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    $QueryString .= "&Key=" .urlencode($BLKey);
    $QueryString .= "&OpenInNewWindow=" .urlencode($OpenInNewWindow);


    if(intval(get_cfg_var('allow_url_fopen')) && function_exists('file_get_contents')) {
        if($content = @file_get_contents("http://www.backlinks.com/enginec.php?".$QueryString))
            return $content;
    }
    elseif(intval(get_cfg_var('allow_url_fopen')) && function_exists('file')) {
        if($content = @file("http://www.backlinks.com/enginec.php?".$QueryString)) 
            return @join('', $content);
    }
    elseif(function_exists('curl_init')) {
        $ch = curl_init ("http://www.backlinks.com/enginec.php?".$QueryString);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER  ,1); 
        $content = curl_exec ($ch);
        curl_close ($ch);

        if($content===false)
            return "Error processing request";

        return $content;

    }
    else {
        return "It appears that your web host has disabled all functions for handling remote pages and as a result the BackLinks software will not function on your web page. Please contact your web host for more information.";
    }
}


add_filter('posts_results', 'bl_add_incontent_ads',  888888);


function bl_add_incontent_ads ($posts) {
    global $wp_query;

    if(is_object($posts[0]) && $posts[0]->ID){
        $ad = backlinks_content_links();
        if(is_single()) {
            #print_r($posts[0]);
            $posts[0]->post_content .= $ad;
        }
        else {
            #print_r($posts[0]);
            if (preg_match('/<!--more(.*?)?-->/', $posts[0]->post_content, $matches) ) {
                $posts[0]->post_content = preg_replace('/(<!--more(.*?)?-->)/', "{$ad}{$matches[0]}" ,$posts[0]->post_content);
            }
            else {
                $posts[0]->post_content .= $ad;
            }
        }
        #print_r($posts);
    }
    return $posts;
}


/*** COMIENZAN LOS WIDGETS  ******/
class WPeBacklinks extends WP_Widget
{
	/**
	* Declares the WPeBacklinks class.
	*
	*/
	function WPeBacklinks(){
		$widget_ops = array('classname' => 'widget_etruel_backlinks', 'description' => __( "Show backlinks.com links in every widget") );
		$control_ops = array('width' => 300, 'height' => 300);
		$this->WP_Widget('WPeBacklinks', __('WPeBacklinks'), $widget_ops, $control_ops);
	}
	
	/**
	* Displays the Widget
	*
	*/
	function widget($args, $instance){
		extract($args);
		// Control logico de $BacklinksPage
		$BacklinksPage = (empty($instance['BacklinksPage'])) ? 'true' : stripslashes($instance['BacklinksPage']);
		//$wl_value   = ( !empty( $wl_options[$id] ) )     ? stripslashes( $wl_options[$id] ) : "true";
		$BacklinksPage = (stristr( $BacklinksPage,"return")) ? $BacklinksPage : "return (" . $BacklinksPage . ");";
		$URL_logic=(eval($BacklinksPage));
		if ( $URL_logic ) {
			$show_title = htmlspecialchars($instance['show_title']);
			$title = apply_filters('widget_title', empty($instance['title']) ? '&nbsp;' : $instance['title']);
			$validkey = ($instance['backlinks_key_status']['valid']); 
			# Before the widget
			echo $before_widget;
			
			# The title
			if ( $title && $show_title )
				echo $before_title . $title . $after_title;
			
			# Make the etruel Backlinks  widget
			if(!$validkey == 'YES'){
				echo '<div style="text-align:center;padding:10px;">' . __('Please enter a valid BackLinks key.') . '<br /></div>';
			}else{
				$backlinks_key = empty($instance['backlinks_key']) ? '' : $instance['backlinks_key'];
				$backlinks_open_in_nw = htmlspecialchars($instance['backlinks_open_in_nw']);
				etruel_backlinks_links($backlinks_key, $backlinks_open_in_nw);
			}
			
			# After the widget
			echo $after_widget;
		}
	}
	
	/**
	* Saves the widgets settings.
	*
	*/
	function update($new_instance, $old_instance){
		$instance = $old_instance;
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['show_title'] = intval($new_instance['show_title']);
		$instance['backlinks_key'] = strip_tags(stripslashes($new_instance['backlinks_key']));
		$instance['backlinks_open_in_nw'] = intval($new_instance['backlinks_open_in_nw']);
		$instance['BacklinksPage'] = strip_tags(stripslashes($new_instance['BacklinksPage']));
		
		$key = preg_replace( '/[^A-Z0-9\-]/', '', $instance['backlinks_key'] );

		if ( empty($key) ) {
			$instance['backlinks_key_status'] = array('valid' => 'NO', 'color' => 'aa0', 'text' => __('Please enter a valid BackLinks key.<br /><a href="http://www.backlinks.com/login.php" target="_blank" style="color:#555;float:Right;">Get your key.</a>'));
		} 
        else {
			$instance['backlinks_key_status'] = array('valid' => 'YES', 'color' => '2d2', 'text' => __('This key is valid.'));
        }
		
		return $instance;
	}
	
	/**
	* Creates the edit form for the widget.
	*
	*/
	function form($instance){
		//Defaults
		$instance = wp_parse_args( (array) $instance, array('title'=>'', 'show_title'=>'1', 'backlinks_key'=>'', 'backlinks_open_in_nw'=>'1', 'BacklinksPage'=>'','backlinks_key_status'=>array('valid' => 'NO', 'color' => 'aa0', 'text' => __('Please enter a valid BackLinks key.<br /><a href="http://www.backlinks.com/login.php" target="_blank" style="color:#555;float:Right;">Get your key.</a>')) ) );
		
		$title = htmlspecialchars($instance['title']);
		$show_title = htmlspecialchars($instance['show_title']);
		$backlinks_key = htmlspecialchars($instance['backlinks_key']);
		$backlinks_open_in_nw = htmlspecialchars($instance['backlinks_open_in_nw']);
		$BacklinksPage = htmlspecialchars($instance['BacklinksPage']);
		//$valid = ($instance['backlinks_key_status']['valid'] == 'YES'); 
		
		# Output the options
		echo '<p><label for="show_title" style="float:Right;"><input id="' . $this->get_field_id('show_title') . '" name="' . $this->get_field_name('show_title') . '" type="checkbox" value="' . $show_title . '"'.((!$show_title)?'':'checked').'/> ' . __('Show Title') . '</label><label for="' . $this->get_field_name('title') . '">' . __('Title:') . '<br /><input style="width: 100%;" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" /></label></p>';
		# backlinks_key
		echo '<p style="padding: .5em; background-color: #'. $instance['backlinks_key_status']['color'].' color: #fff; font-weight: bold;">'. $instance['backlinks_key_status']['text'].'</p>';
		echo '<p><label for="' . $this->get_field_name('backlinks_key') . '">' . __('BackLinks.com Key:') . ' <input size="15" maxlength="14"  style="font-family: \'Courier New\', Courier, mono; font-size: 1.5em;" id="' . $this->get_field_id('backlinks_key') . '" name="' . $this->get_field_name('backlinks_key') . '" type="text" value="' . $backlinks_key . '" /></label></p>';
		# target _Blank
		echo '<p><label for="backlinks_open_in_nw"><input id="' . $this->get_field_id('backlinks_open_in_nw') . '" name="' . $this->get_field_name('backlinks_open_in_nw') . '" type="checkbox" value="' . $backlinks_open_in_nw . '"'.((!$backlinks_open_in_nw)?'':'checked').'/> ' . __('Open partner links in a new window') . '</label></p>';
		# BacklinksPage = Condicion para mostrar el widget Ej: is_page('algo') o is_front_page()
		echo '<p><label for="' . $this->get_field_name('BacklinksPage') . '">' . __('Logic URL:') . ' <input style="width: 200px;" id="' . $this->get_field_id('BacklinksPage') . '" name="' . $this->get_field_name('BacklinksPage') . '" type="text" value="' . $BacklinksPage . '" /></label><br /><small>' . __('Examples:') . '<span id="exa"><table style="width:100%;display:block;margin-left:20px;font-family: \'Courier New\', Courier, mono;"> <tr><td>is_front_page()</td><td>is_page(\'slug\')</td></tr><tr><td>is_single(\'slug\')</td><td>is_author(\'slug\')</td></tr><tr><td>is_tag(\'slug\')</td><td>is_category(\'slug\')</td></tr></table></span></small></p>';
	}

}// END class
	
	/**
	* Register WPeBacklinks widget.
	*
	* Calls 'widgets_init' action after the etruel Backlinks widget has been registered.
	*/
	function etruelBackLinksInit() {
	register_widget('WPeBacklinks');
	}	
	add_action('widgets_init', 'etruelBackLinksInit');
	
//****** Backlinks functions

function etruel_backlinks_links($backlinks_key,$backlinks_open_in_nw) {

    //if(!$backlinks_key) $backlinks_key = get_option('backlinks_key');

    $OpenInNewWindow = $backlinks_open_in_nw;

    // # DO NOT MODIFY ANYTHING ELSE BELOW THIS LINE!
    // ----------------------------------------------
    $BLKey = $backlinks_key;

    $QueryString  = "LinkUrl=".urlencode((($_SERVER['HTTPS']=='on')?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    $QueryString .= "&Key=" .urlencode($BLKey);
    $QueryString .= "&OpenInNewWindow=" .urlencode($OpenInNewWindow);


    if(intval(get_cfg_var('allow_url_fopen')) && function_exists('readfile')) {
        @readfile("http://www.backlinks.com/engine.php?".$QueryString); 
    }
    elseif(intval(get_cfg_var('allow_url_fopen')) && function_exists('file')) {
        if($content = @file("http://www.backlinks.com/engine.php?".$QueryString)) 
            print @join('', $content);
    }
    elseif(function_exists('curl_init')) {
        $ch = curl_init ("http://www.backlinks.com/engine.php?".$QueryString);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_exec ($ch);

        if(curl_error($ch))
            print "Error processing request";

        curl_close ($ch);
    }
    else {
        print "It appears that your web host has disabled all functions for handling remote pages and as a result the BackLinks software will not function on your web page. Please contact your web host for more information.";
    }
}

?>