<?php
/*
Plugin Name: ZENpressed Widget
Plugin URI: http://n00bism.net/dokuwiki/wordpress/zenpressed
Description: Adds a sidebar widget for ZENpressed functions
Author: Kristof Klee
Author URI: http://n00bism.net/blog
Version: 0.2
*/

function widget_zenpressed_init( ) {

	// Check for the required plugin functions. This will prevent fatal
	// errors occurring when you deactivate the dynamic-sidebar plugin.
	if ( !function_exists('register_sidebar_widget') )
		return;

	// This is the function that outputs our little Google search form.
	function widget_zenpressed( $args ) {
		
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract( $args );

		// Each widget can store its own options. We keep strings here.
		$options = get_option( 'widget_zenpressed' );
		$title = $options['title'];
		$conf = zenpressed_conf( );
		if( $options['select'] != "" ) $conf[ 'select' ] = $options['select'];
		if( $options['count'] != "") $conf['count'] = $options['count'];
		if( $options['size'] != "" ) $conf['size'] = $options['size'];
		if( $options['album'] != "" ) $conf['album'] = $options['album'];
		
		$conf['showtitle'] = $options['showtitle'] ? "on" : "off";
		$conf['showdescription'] = $options['showdescription'] ? "on" : "off";
		$conf['showalbumtitle'] = $options['showalbumtitle'] ? "on" : "off";
		$conf['showalbumdescription'] = $options['showalbumdescription'] ? "on" : "off";
		$conf['lightbox'] = $options['lightbox'] ? "on" : "off";
		if( $options['lightbox_size'] != "" ) $conf['lightbox_size'] = $options['lightbox_size'];

		// These lines generate our output. Widgets can be very complex
		// but as you can see here, they can also be very, very simple.
		echo $before_widget . $before_title . $title . $after_title;
		zenpressed_photos( $conf['album'], $conf );
		echo $after_widget;
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function widget_zenpressed_control() {

		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_zenpressed');
		if ( !is_array($options) )
			$options = array( 'title' => '', 'album' => '', 'select' => 'random', 'count' => '4', 'size' => 'thumb',  );
		if ( $_POST['zenpressed-submit'] ) {
			// Remember to sanitize and format use input appropriately.
			$options['title'] = strip_tags(stripslashes($_POST['zenpressed-title']));
			$options['select'] = strip_tags(stripslashes($_POST['zenpressed-select']));
			$options['album'] = strip_tags(stripslashes($_POST['zenpressed-album']));
			$options['count'] = strip_tags(stripslashes($_POST['zenpressed-count']));
			$options['size'] = strip_tags(stripslashes($_POST['zenpressed-size']));
			
			$options['showtitle'] = isset($_POST['zenpressed-showtitle']);
			$options['showdescription'] = isset($_POST['zenpressed-showdescription']);
			$options['showalbumtitle'] = isset($_POST['zenpressed-showalbumtitle']);
			$options['showalbumdescription'] = isset($_POST['zenpressed-showalbumdescription']);
			
			$options['lightbox'] = isset($_POST['zenpressed-lightbox']);
			$options['lightbox_size'] = strip_tags(stripslashes($_POST['zenpressed-lightboxsize']));
			
			update_option('widget_zenpressed', $options);
		}
		
		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.
		?>
		<p style="text-align:right;"><label for="zenpressed-title">Title: <input style="width: 200px;" id="zenpressed-title" name="zenpressed-title" type="text" value="<?= $options['title'] ?>" /></label></p>
		<p style="text-align:right;"><label for="zenpressed-album">Album: <input style="width: 200px;" id="zenpressed-album" name="zenpressed-album" type="text" value="<?= $options['album'] ?>" /></label></p>
		<p style="text-align:right;"><label for="zenpressed-select">Select: <select style="width: 200px;" id="zenpressed-select" name="zenpressed-select">
				<option<?= $options['select'] == "" ? " selected" : "" ?>></option>
				<option<?= $options['select'] == "random" ? " selected" : "" ?>>random</option>
				<option<?= $options['select'] == "latest" ? " selected" : "" ?>>latest</option>
				<option<?= $options['select'] == "mostviewed" ? " selected" : "" ?>>mostviewed</option>
				<option<?= $options['select'] == "leastviewed" ? " selected" : "" ?>>leastviewed</option>
			</select>
		</label></p>
		<p style="text-align:right;"><label for="zenpressed-count">Count: <input style="width: 200px;" id="zenpressed-count" name="zenpressed-count" type="text" value="<?= $options['count'] ?>" /></label></p>
		<p style="text-align:right;"><label for="zenpressed-size">Size: <input style="width: 200px;" id="zenpressed-size" name="zenpressed-size" type="text" value="<?= $options['size'] ?>" /></label></p>
		
		<p style="text-align:right;margin-right:40px;"><label for="zenpressed-showtitle" style="text-align:right;">Show Title <input class="checkbox" type="checkbox" <?= $options['showtitle'] ? 'checked="checked"' : '' ?> id="zenpressed-showtitle" name="zenpressed-showtitle" /></label></p>
		<p style="text-align:right;margin-right:40px;"><label for="zenpressed-showdescription" style="text-align:right;">Show Description <input class="checkbox" type="checkbox" <?= $options['showdescription'] ? 'checked="checked"' : '' ?> id="zenpressed-showdescription" name="zenpressed-showdescription" /></label></p>
		<p style="text-align:right;margin-right:40px;"><label for="zenpressed-showalbumtitle" style="text-align:right;">Show Album Title <input class="checkbox" type="checkbox" <?= $options['showalbumtitle'] ? 'checked="checked"' : '' ?> id="zenpressed-showalbumtitle" name="zenpressed-showalbumtitle" /></label></p>
		<p style="text-align:right;margin-right:40px;"><label for="zenpressed-showalbumdescription" style="text-align:right;">Show Album Description <input class="checkbox" type="checkbox" <?= $options['showalbumdescription'] ? 'checked="checked"' : '' ?> id="zenpressed-showalbumdescription" name="zenpressed-showalbumdescription" /></label></p>
		
		<p style="text-align:right;margin-right:40px;"><label for="zenpressed-lightbox" style="text-align:right;">Use Lightbox <input class="checkbox" type="checkbox" <?= $options['lightbox'] ? 'checked="checked"' : '' ?> id="zenpressed-lightbox" name="zenpressed-lightbox" /></label></p>
		<p style="text-align:right;"><label for="zenpressed-lightboxsize">Lightbox Size: <input style="width: 200px;" id="zenpressed-lightboxsize" name="zenpressed-lightboxsize" type="text" value="<?= $options['lightbox_size'] ?>" /></label></p>
		
		<input type="hidden" id="zenpressed-submit" name="zenpressed-submit" value="1" />
		<?php
	}
	
	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	register_sidebar_widget('ZENpressed Photos', 'widget_zenpressed');

	// This registers our optional widget control form. Because of this
	// our widget will have a button that reveals a 300x100 pixel form.
	register_widget_control('ZENpressed Photos', 'widget_zenpressed_control', 300, 360);
}

// Run our code later in case this loads prior to any required plugins.
add_action('plugins_loaded', 'widget_zenpressed_init');

?>
