<?php
/*
Plugin Name: ZENpressed
Plugin URI: http://n00bism.net/dokuwiki/wordpress/zenpressed/
Description: integrate ZENphoto into WordPress (based on <a href="http://www.ruicruz.com/zenshow/">ZenShow</a> by <a href="http://www.ruicruz.com">Rui Cruz</a>)
Version: 0.7
Author: Kristof Klee
Author URI: http://n00bism.net
*/

/* all options zenpressed uses with defaults (starting with an ZENpressed_) */
$zenpressed_options = array(
	"otherdb" => "off",
	"server" => "localhost",
	"db" => "zen",
	"username" => "",
	"password" => "",
	"prefix" => "zen_",
	"url" => "http://www.mydomain.com/zen/",
	"size" => "thumb",
	"showtitle" => "on",
	"showdescription" => "off",
	"count" => "4",
	"showalbumtitle" => "off",
	"showalbumdescription" => "on",
	"quicktag" => "on",
	"niceurls" => "off",
	"lightbox" => "off",
	"lightbox_size" => "512",
	"fixme" => "off"
);

/*	gets the configuration from the database if not allready set */
function zenpressed_conf( $conf = null ) {
	global $zenpressed_options;
	if( $conf == null ) $conf = array( );
	foreach( $zenpressed_options as $o => $d )
		if( !isset( $conf[$o] ) )
			$conf[$o] = get_option( "ZENpressed_" . $o );
	return $conf;
}

/* connect to db */
function zenpressed_connect( $conf ) {
	global $wpdb;
	if( $conf["otherdb"] == "on" )
		if( empty( $conf["db"] ) || empty( $conf["server"] ) || empty( $conf["username"] ) || empty( $conf["password"] ) )
			trigger_error( 'Please check your ZENpressed database settings. Some information is missing.' );
		else
			return new wpdb( $conf["username"], $conf["password"], $conf["db"], $conf["server"] );
	else
		return $wpdb;
}

/* shows one photo */
function zenpressed_showphoto( $photo, $conf = null ) {
	$conf = zenpressed_conf( $conf );
	
	// generate data
	if( $conf["niceurls"] == "on" ) {
		$u = $conf["url"] . $photo["folder"] ."/" . $photo["filename"];
		$s = $conf["url"] . $photo["folder"] . "/image/" . $conf["size"] . "/" . $photo["filename"];
	}
	else {
		$u = $conf["url"] . "?album=" . $photo["folder"]."&image=" . $photo["filename"];
		$s = $conf["url"] . "zen/i.php?a=" . $photo["folder"] . "&i=" . $photo["filename"] . "&s=" . $conf["size"];
	}
	$t = $photo["title"];
	$d = $photo["description"];
	$class = "zp_image" . ( isset( $conf["class"] ) && $conf["class"] != "" ) ? " " . $conf["class"] : ""; 
	if( $conf["lightbox"] == "on" ) {
		$l = ' rel="lightbox[' . $photo["folder"] . ']"';
		$u = $conf["url"] . $photo["folder"] . "/image/" . $conf["lightbox_size"] . "/" . $photo["filename"];
	}

	// output
	$c = '<div class="zp_photo"><a href="' . $u . '" title="' . $t . '" class="zp_link"' . $l . '><img src="' . $s . '" alt="' . $t . '" class="' . $class . '" />' . ( ( $conf["showtitle"] == "on" ) ? '<div class="zp_title">' . $t. '</div>' : '' ) . '</a>' . ( ( $conf["showdescription"] == "on" ) ? '<div class="zp_description">' . $d . '</div>' : '' ) . '</div>';
	
	return $c;
}

/* show photos from query */
function zenpressed_showphotos( $query, $conf = null ) {
	$conf = zenpressed_conf( $conf );
	$db = zenpressed_connect( $conf );
	if( $db != null )
		if( $photos = $db->get_results( $query ) ) {
			if( count( $photos ) > 1 ) {
				$c = '<div class="zp_photos">';
				if( isset( $conf["album"] ) ) {
					if( is_array( $conf["album"] ) ) {
						if( $conf["showalbumtitle"] == "on" ) {
							$c .= '<div class="zp_albumtitle">';
							foreach( $conf["album"] as $album_name ) {
								$album = $db->get_row( "SELECT * FROM " . $conf["prefix"] . "albums WHERE folder LIKE '" . $album_name . "'" );
								if( $conf["niceurls"] == "on" )
									$u = $conf["url"] . $album->folder;
								else
									$u = $conf["url"] . "?album=" . $album->folder;

								$c .= '<a href="' . $u . '">' . $album->title . '</a>';
							}
							$c .= '</div>';
						}
					}
					else {
						$album = $db->get_row( "SELECT * FROM " . $conf["prefix"] . "albums WHERE folder LIKE '" . $conf["album"] . "'" );
						if( $conf["showalbumtitle"] == "on" ) {
							if( $conf["niceurls"] == "on" )
								$u = $conf["url"] . $album->folder;
							else
								$u = $conf["url"] . "?album=" . $album->folder;

							$c .= '<div class="zp_albumtitle"><a href="' . $u . '">' . $album->title . '</a></div>';
						}
						if( $conf["showalbumdescription"] == "on" )
							$c .= '<div class="zp_albumdescription">' . $album->desc . '</div>';
					}
				}
			}
			
			foreach( $photos as $photo )
				$c .= zenpressed_showphoto( get_object_vars( $photo ), $conf );
			
			if( count( $photos ) > 1 )
				$c .= '</div>';
			
			if( isset( $conf["string"] ) )
				return $c;
			else
				echo $c;
		}
		else
			trigger_error( "No photos matching the criteria." );
	else
		trigger_error( "Unable to connect to database. Please check your ZENpressed database settings." );
	
	if( $conf["otherdb"] == "on" ) {
		if( $conf["fixme"] == "on" )
			$db = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
		$db = null;
	}
}

/* show photo by id*/
function zenpressed_photobyid( $id, $conf = null ) {
	$conf = zenpressed_conf( $conf );
	if( $id == null ) return "";
	
	$query = "SELECT images.filename AS filename, images.`desc` AS description, album.folder AS folder, images.title AS title
				FROM " . $conf["prefix"] . "images AS images INNER JOIN " . $conf["prefix"] . "albums AS album ON album.id = images.albumid
				WHERE images.show = 1 AND images.id = " . $id;
	return zenpressed_showphotos( $query, $conf );
}

/* show photo by filename and album */
function zenpressed_photo( $filename, $album = null, $conf = null ) {
	$conf = zenpressed_conf( $conf );
	if( $filename == null ) return "";
	
	if( $album != null && $conf["showtitle"] != "on" && $conf["showdescription"] != "on" ) {
		$c = zenpressed_showphoto( array( "folder" => $album, "filename" => $filename ), $conf );
		if( isset( $conf["string"] ) )
			return $c;
		else
			echo $c;
	}
		
	if( $album != null ) $query_album = " AND album.folder LIKE '" . $album . "'";
	$query = "SELECT images.filename AS filename, images.`desc` AS description, album.folder AS folder, images.title AS title
				FROM " . $conf["prefix"] . "images AS images INNER JOIN " . $conf["prefix"] . "albums AS album ON album.id = images.albumid
				WHERE images.show = 1 AND images.filename LIKE '" . $filename . "'" . $query_album;
	return zenpressed_showphotos( $query, $conf );
}

/* shows photos */
function zenpressed_photos( $album = null, $conf = null ) {
	$conf = zenpressed_conf( $conf );
	if( $album != null ) {
		if( is_array( $album ) ) {
			$query_album = " AND (";
			$first_album = true;
			foreach( $album as $a ) {
				if( ! $first_album )
					$query_album .= " OR";
				else
					$first_album = false;
				$query_album .= " album.folder LIKE '" . $a . "'";
			}
			$query_album .= " )";
		}
		else {
			$query_album = " AND album.folder LIKE '" . $album . "'";
		}
		$conf["album"] = $album;
	}
	
	if( $conf["select"] == "latest" ) $query_order = " ORDER BY images.id DESC";
	elseif( $conf["select"] == "random" ) $query_order = " ORDER BY RAND( )";
	elseif( $conf["select"] == "mostviewed" ) $query_order = " ORDER BY images.hit DESC";
	elseif( $conf["select"] == "leastviewed" ) $query_order = " ORDER BY images.hit";
	
	if( $conf["count"] != "all" ) $query_count = " LIMIT " . $conf["count"];
	$query = "SELECT images.filename AS filename, images.`desc` AS description, album.folder AS folder, images.title AS title
				FROM " . $conf["prefix"] . "images AS images INNER JOIN " . $conf["prefix"] . "albums AS album ON album.id = images.albumid
				WHERE images.show = 1" . $query_album . $query_order . $query_count;
	return zenpressed_showphotos( $query, $conf );
}

/* shows latest photos (selected by bigger id) */
function zenpressed_latestphotos( $album = null, $conf = null ) {
	$conf = zenpressed_conf( $conf );
	$conf["select"] = "latest";
	return zenpressed_photos( $album, $conf );
}

/* shows random photos */
function zenpressed_randomphotos( $album = null, $conf = null ) {
	$conf = zenpressed_conf( $conf );
	$conf["select"] = "random";
	return zenpressed_photos( $album, $conf );
}

/* shows most viewed photos (by royz) */
function zenpressed_mostviewedphotos( $album = null, $conf = null ) {
	$conf = zenpressed_conf( $conf );
	$conf["select"] = "mostviewed";
	return zenpressed_photos( $album, $conf );
}

/* shows least viewed photos */
function zenpressed_leastviewedphotos( $album = null, $conf = null ) {
	$conf = zenpressed_conf( $conf );
	$conf["select"] = "leastviewed";
	return zenpressed_photos( $album, $conf );
}

/*	admin options */
function zenpressed_adminoptions( ) {
	global $zenpressed_options;
	
	// update options
	if( isset( $_POST['info_update'] ) ) {
		foreach( $zenpressed_options as $o => $d )
			update_option( "ZENpressed_" . $o, $_POST["form_" . $o] );
		
		$url = trim( $_POST["form_url"] );
		if( $url{ strlen( $url ) - 1 } != '/' ) update_option( "ZENpressed_url", $url . "/" );
		
		// deal with the checkboxes TODO: if they get more -> foreach and array with checkbox entrys
		if( !isset( $_POST["form_otherdb"] ) ) update_option( "ZENpressed_otherdb", "off" );
		if( !isset( $_POST["form_showtitle"] ) ) update_option( "ZENpressed_showtitle", "off" );
		if( !isset( $_POST["form_showdescription"] ) ) update_option( "ZENpressed_showdescription", "off" );
		if( !isset( $_POST["form_showalbumtitle"] ) ) update_option( "ZENpressed_showalbumtitle", "off" );
		if( !isset( $_POST["form_showalbumdescription"] ) ) update_option( "ZENpressed_showalbumdescription", "off" );
		if( !isset( $_POST["form_quicktag"] ) ) update_option( "ZENpressed_quicktag", "off" );
		if( !isset( $_POST["form_niceurls"] ) ) update_option( "ZENpressed_niceurls", "off" );
		if( !isset( $_POST["form_lightbox"] ) ) update_option( "ZENpressed_lightbox", "off" );
		if( !isset( $_POST["form_fixme"] ) ) update_option( "ZENpressed_fixme", "off" );
		
		echo '<div class="updated"><p><strong>ZENpressed options updated</strong></p></div>';
	}
	
	$conf = zenpressed_conf( );
	
	// default values
	foreach( $zenpressed_options as $o => $d )
		if( empty( $conf[$o] ) ) $conf[$o] = $d;
	
	// output
	?>
	<div class="wrap">
		<h2>ZENpressed</h2>
		
		<div align="right"><a href="<?php echo( $conf["url"] ); ?>zen/admin.php" target="_blank">ZENphoto Admin</a></div>
		
		<form method="post">
			
			<fieldset name="database" title="Database configuration" class="options">
			
			<legend>ZENphoto Database Options</legend>
			
				<table width="100%" border="0" align="center">
				<tr>
					<th width="200" align="right"><?php _e('Use another Database', 'Localization name') ?></th>
					<td><input name="form_otherdb" type="checkbox" <?php if( $conf["otherdb"] == "on" ) { ?>" checked="checked" <?php } ?> /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Server', 'Localization name') ?></th>
					<td><input name="form_server" type="text" value="<?php echo $conf["server"]; ?>" size="40" /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Database', 'Localization name') ?></th>
					<td><input name="form_db" type="text" value="<?php echo $conf["db"]; ?>" size="40" /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Username', 'Localization name') ?></th>
					<td><input name="form_username" type="text" value="<?php echo $conf["username"]; ?>" size="40" /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Password', 'Localization name') ?></th>
					<td><input name="form_password" type="text" value="<?php echo $conf["password"]; ?>" size="40" /></td>
				</tr>
				<tr>
					<th align="right"><?php _e('Tables Prefix', 'Localization name') ?></th>
					<td><input name="form_prefix" type="text" value="<?php echo $conf["prefix"]; ?>" size="40" />
					  Usually &quot;zen_&quot; </td>
				</tr>
				</table>
			 
			</fieldset>
			
			<fieldset name="folder" title="Database configuration" class="options">
			
			<legend>Url Options</legend>
			
				<table width="100%" border="0" align="center">
				<tr>
					<th width="200" align="right"><?php _e('ZENphoto URL', 'Localization name') ?></th>
					<td><input name="form_url" type="text" value="<?php echo $conf["url"]; ?>" size="40" /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Use nice URLs', 'Localization name') ?></th>
					<td><input name="form_niceurls" type="checkbox" <?php if( $conf["niceurls"] == "on" ) { ?> checked="checked" <?php } ?> /></td>
				</tr>
				</table>
			
			</fieldset>
			
			<fieldset name="view" title="Appereance configuration" class="options">
			
			<legend>Appereance Options</legend>
			
				<table width="100%" border="0" align="center">
				<tr>
					<th width="200" align="right"><?php _e('Image size', 'Localization name') ?></th>
					<td><input name="form_size" type="text" value="<?php echo $conf["size"]; ?>" size="40" /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Show Title', 'Localization name') ?></th>
					<td><input name="form_showtitle" type="checkbox" <?php if( $conf["showtitle"] == "on" ) { ?> checked="checked" <?php } ?> /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Show Description', 'Localization name') ?></th>
					<td><input name="form_showdescription" type="checkbox" <?php if( $conf["showdescription"] == "on" ) { ?> checked="checked" <?php } ?> /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Display how many photos', 'Localization name') ?></th>
					<td><input name="form_count" type="text" value="<?php echo $conf["count"]; ?>" size="40" /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Show Album Title', 'Localization name') ?></th>
					<td><input name="form_showalbumtitle" type="checkbox" <?php if( $conf["showalbumtitle"] == "on" ) { ?> checked="checked" <?php } ?> /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Show Album Description', 'Localization name') ?></th>
					<td><input name="form_showalbumdescription" type="checkbox" <?php if( $conf["showalbumdescription"] == "on" ) { ?> checked="checked" <?php } ?> /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Show QuickTag', 'Localization name') ?></th>
					<td><input name="form_quicktag" type="checkbox" <?php if( $conf["quicktag"] == "on" ) { ?> checked="checked" <?php } ?> /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Use Lightbox', 'Localization name') ?></th>
					<td><input name="form_lightbox" type="checkbox" <?php if( $conf["lightbox"] == "on" ) { ?> checked="checked" <?php } ?> /></td>
				</tr>
				<tr>
					<th width="200" align="right"><?php _e('Lightbox Linked Image Size', 'Localization name') ?></th>
					<td><input name="form_lightbox_size" type="text" value="<?php echo $conf["lightbox_size"]; ?>" size="40" /></td>
				</tr>
				</table>
			</fieldset>
			
			<fieldset name="misc" title="Misc configuration" class="options">
			<legend>Misc Options</legend>
				<table width="100%" border="0" align="center">
				<tr>
					<th width="200" align="right"><?php _e('WPDB Fix', 'Localization name') ?></th>
					<td><input name="form_fixme" type="checkbox" <?php if( $conf["fixme"] == "on" ) { ?> checked="checked" <?php } ?> /></td>
				</tr>
				</table>
			</fieldset>
			
			<p>
			For some extra help and documentation visit <a href="http://n00bism.net/dokuwiki/wordpress/zenpressed">our wiki</a>.
			</p>
			
			<div class="submit">
				<input type="submit" name="info_update" value="<?php _e( 'Update options', 'Localization name' ) ?> &raquo;" />
			</div>
			
		</form>
	</div>
	<?php
}

function zenpressed_adminmenu ( ) {
	if( function_exists( "add_options_page" ) )
		add_options_page( "ZENpressed", "ZENpressed", 9, basename( __FILE__ ), "zenpressed_adminoptions" );
}

add_action( "admin_menu", "zenpressed_adminmenu" );

/* content filter for <zen> tags */
function zenpressed_replace( $text ) {
	// find all zen tags
	preg_match_all( '!<zen([^>]*)[ ]*[/]*>!imU', $text, $tags, PREG_SET_ORDER );
	
	foreach( $tags as $tag ) {
		// find all attributes
		preg_match_all( '!(url|album|image|id|size|showlink|showtitle|showdescription|title|description|select|count|showalbumtitle|showalbumdescription|class|lightbox|lightbox_size)="([^"]*)"!i', $tag[0], $attributes, PREG_SET_ORDER );
		$conf = array( );
		foreach( $attributes as $attribute )
			$conf[ $attribute[ 1 ] ] = $attribute[ 2 ];
		if( isset( $conf["url"] ) ) {
			$conf["album"] = substr( $conf["url"], 0, strpos( $conf["url"], "/" ) );
			$conf["image"] = substr( strrchr( $conf["url"], '/' ), 1 );
			$conf["url"] = null;
		}
		$conf = zenpressed_conf( $conf );
		
		// generate replacement code
		$conf["string"] = "true";
		if( isset( $conf["id"] ) )
			$c = zenpressed_photobyid( $conf["id"], $conf );
		if( isset( $conf["image"] ) )
			$c = zenpressed_photo( $conf["image"], $conf["album"], $conf );
		else
			$c = zenpressed_photos( $conf["album"], $conf );
		
		// replace tag with html */
		$text = str_replace ( $tag[0], $c, $text );	       
	}
	return $text;
}

add_filter('the_content', 'zenpressed_replace', 18);
add_filter('the_content_rss', 'zenpressed_replace', 18);
add_filter('the_excerpt', 'zenpressed_replace', 18);
add_filter('the_excerpt_rss', 'zenpressed_replace', 18);

/* insert quicktag into editor */
function zenpressed_quicktag( ) {
	$conf = zenpressed_conf( );
	if( $conf["quicktag"] == "on" ) {
		?>
		<script type="text/javascript">
		<!--
		var zp_tb = document.getElementById( "ed_toolbar" );
		if( zp_tb ) {
			var zp_nr = edButtons.length;
			edButtons[edButtons.length] = new edButton('ed_zp','','','','');
			var zp_btn = zp_tb.lastChild;
			while ( zp_btn.nodeType != 1 ) {
				zp_btn = zp_btn.previousSibling;
			}
			zp_btn = zp_btn.cloneNode( true );
			zp_tb.appendChild( zp_btn );
			zp_btn.value = 'zen';
			zp_btn.onclick = edInsertZP;
			zp_btn.title = "Insert a ZENphoto item";
			zp_btn.id = "ed_zp";
		}
		
		function edInsertZP( ) {
			if( !edCheckOpenTags( zp_nr ) ){
				var I = prompt('Give the name of the image' , '.jpg' );
				var A = prompt('Give the name of the album' , '' );
				var O = confirm('Do you want to add all other attributes, so you can edit them?');
				var theTag = '<zen'
				theTag += (I) ? ' image="' + I + '"' : "" 
				theTag += (A) ? ' album="' + A + '"' : "";
				theTag += (O) ? ' size="<?=$conf["size"]?>" showtitle="<?=$conf["showtitle"]?>" />' : ' />'
				edButtons[zp_nr].tagStart  = theTag;
				edInsertTag( edCanvas, zp_nr );
			}
			else {
				edInsertTag( edCanvas, zp_nr );
			}
		}
		
		//-->
		</script>
		<?php
	}
}

if( strpos( $_SERVER['REQUEST_URI'], 'post.php' ) || strpos( $_SERVER['REQUEST_URI'], 'page-new.php' ) )
	add_action( "admin_footer", "zenpressed_quicktag" );

?>
