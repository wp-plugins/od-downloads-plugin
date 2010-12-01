<?php
/*
Plugin Name: odDownloadsPlugin
Plugin URI: http://www.ondrejd.info/projects/wordpress-plugins/od-downloads-plugin/
Description: Plugin for administrating files to download for your site.
Author: Ondrej Donek
Version: 0.4.1
Author URI: http://www.ondrejd.info/
*/

// Add our widget
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'od-downloads-widget.php';

/**
 * Main plugin object
 * 
 * @author Ondrej Donek, <ondrejd@gmail.com>
 * @category odWpDownloadsPlugin
 * @version 0.4
 */
class odWpDownloadsPlugin /* XXX extends WP_Plugin */
{
  static $plugin_id;
  static $version;
  static $textdomain;
  
  var $default_options = array(
	  'main_downloads_dir' => 'wp-content/ke-stazeni/',
	  'downloads_page_id'  => 0,
	  'downloads_thumb_size_width' => 146,
	  'downloads_shortlist_max_count' => 2
  );
  
  /**
   * Constructor.
   * 
   * @return void
   */
  function odWpDownloadsPlugin()
  {
	// Set up the plugin
	odWpDownloadsPlugin::$plugin_id = 'od-downloads-plugin';
	odWpDownloadsPlugin::$version = '0.4';
	odWpDownloadsPlugin::$textdomain = odWpDownloadsPlugin::$plugin_id;
	
	// Initialize the plugin
	// XXX Use WordPress pre-defined plugins URL!
	load_plugin_textdomain(odWpDownloadsPlugin::$textdomain, 
	                       '/wp-content/plugins/' . odWpDownloadsPlugin::$plugin_id);
	
	register_activation_hook(__FILE__, array(&$this, 'activate'));
	register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));
	
	if(is_admin()) {
	  add_action('admin_menu', array(&$this, 'register_admin_menu'));
	  // XXX add_action('init', array(&$this, 'register_tinymce_buttons'));
	}
	
	add_action('widgets_init', array(&$this, 'init_widgets'));
  }

  /**
   * Activates the plugin
   * 
   * @returns void
   */
  function activate()
  {
	global $wpdb;
	
	// Ensure that plugin's options are initialized
	$this->get_options();
	
	// Create the database table
	$wpdb->query("CREATE TABLE `{$wpdb->prefix}downloads` (
				`ID` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`title` VARCHAR( 255 ) NOT NULL ,
				`file_img` VARCHAR( 255 ) NOT NULL ,
				`file` VARCHAR( 255 ) NOT NULL ,
				`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
				`display` TINYINT( 1 ) NOT NULL
				) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");
  }

  /**
   * Deactivates the plugin
   * 
   * @returns void
   */
  function deactivate() { /* nothing to do ... */ }
  
  /**
   * Returns plugin's options
   *
   * @return array
   */
  function get_options() 
  {
	$options = get_option(odWpDownloadsPlugin::$plugin_id . '-options');
	$need_update = false;

	if($options === false) {
	  $need_update = true;
	  $options = array();
	}

	foreach($this->default_options as $key => $value) {
	  if(!array_key_exists($key, $options)) {
		$options[$key] = $value;
	  }
	}

	if(!array_key_exists('latest_used_version', $options)) {
	  $options['latest_used_version'] = odWpDownloadsPlugin::$version;
	  $need_update = true;
	}

	if($need_update === true) {
	  update_option(odWpDownloadsPlugin::$plugin_id . '-options', $options);
	}

	return $options;
  }
  
  /**
   * Registers administration menu for the plugin
   * 
   * @returns void
   */
  function register_admin_menu() 
  {
	add_menu_page(__('Downloads', odWpDownloadsPlugin::$textdomain),
				  __('Downloads', odWpDownloadsPlugin::$textdomain),
				  0,
				  odWpDownloadsPlugin::$plugin_id,
				  array(&$this, 'admin_page'),
				  WP_PLUGIN_URL . '/' . odWpDownloadsPlugin::$plugin_id . '/icon16.png');
	add_submenu_page(odWpDownloadsPlugin::$plugin_id,
					 __('Downloads - Add new item', odWpDownloadsPlugin::$textdomain),
					 __('Add item', odWpDownloadsPlugin::$textdomain),
					 0,
					 'od-downloads-plugin-add',
					 array(&$this, 'add_page'));
	add_submenu_page(odWpDownloadsPlugin::$plugin_id,
					 __('Downloads - Settings', odWpDownloadsPlugin::$textdomain),
					 __('Settings', odWpDownloadsPlugin::$textdomain),
					 0,
					 'od-downloads-plugin-settings',
					 array(&$this, 'settings_page'));
  }
  
  /**
   * Initializes widgets
   * 
   * @return void
   */
  function init_widgets()
  {
	register_widget('odWpDownloadsWidget');
  }
  
  /**
   * Renders main admin page for the plugin
   * 
   * @returns void
   */
  function admin_page()
  {
	global $wpdb;
	
	$options = $this->get_options();
?>
	<div class="wrap">
	  <div class="icon32">
		<img src="<?php echo WP_PLUGIN_URL . '/' . odWpDownloadsPlugin::$plugin_id . '/icon32.png';?>"/>
	  </div>
	  <h2><?php echo __('Downloads', odWpDownloadsPlugin::$textdomain);?></h2>
<?php
	if((isset($_POST['doaction']) || isset($_POST['doaction2']))) {
	  $action = ($_POST['action'] != '-1') ? $_POST['action'] : $_POST['action2'];
	  $items = (isset($_POST['items'])) ? $_POST['items'] : array();
	  
	  if(count($items) == 0) {
?>
		<div id="message" class="error fade"><p><strong><?php echo __('No items selected', odWpDownloadsPlugin::$textdomain);?></strong>.</p></div>
<?php
	  } else if($action == '-1') {
?>
			<div id="message" class="error fade"><p><strong><?php echo __('No items selected', odWpDownloadsPlugin::$textdomain);?></strong>.</p></div>
<?php
	  } else if($action == 'delete') {
		$query = "DELETE FROM `{$wpdb->prefix}downloads` WHERE ";
		
		for($i=0; $i<count($items); $i++) {
		  $query = $query . "`ID`='" . $items[$i] . "' " . ((($i+1)==count($items)) ? '' : 'OR ');
		}
		
		$res = $wpdb->query($query);
?>
		  <div id="message" class="updated fade"><p><?php echo __('Deleted items count: ', odWpDownloadsPlugin::$textdomain);?><strong><?php echo $res;?></strong>.</p></div>
<?php
	  }
	}
	else if(isset($_GET['item_ID'])) {
	  // User want to edit selected item
	  $item_ID = (int) $_GET['item_ID'];
	  $res = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}downloads` WHERE `ID` = '{$item_ID}' LIMIT 1 ", ARRAY_A);
	  
	  if(!is_null($res)) {
		if(count($res) == 1) {
		  $item = $res[0];
?>
		<h3><?php echo __('Edit item #', odWpDownloadsPlugin::$textdomain);?><code><?php echo $item_ID;?></code></h3>
		<div id="downloadshelpdiv" class="postbox" style="width: 90%; padding: 10px 20px 10px 20px;">
		  <form action="<?php echo get_option('home');?>/wp-admin/admin.php?page=od-downloads-plugin" method="post" enctype="multipart/form-data">
			<input type="hidden" name="item_ID" value="<?php echo $item_ID;?>"/>
			<table cellspacing="1" cellpadding="1" style="width: 80%;">
			  <tr>
				<th scope="row" style="text-align:left;"><label for="title"><?php echo __('Title', odWpDownloadsPlugin::$textdomain);?>:</label></th>
				<td><input type="text" name="title" value="<?php echo $item['title'];?>" style="width: 100%;"/></td>
			  </tr>
			  <tr>
				<th scope="row" style="text-align:left;"><label><?php echo __('Thumbnail', odWpDownloadsPlugin::$textdomain);?>:</label></th>
				<td><a href="<?php echo $this->get_public_url($item['file_img']);?>" target="_BLANK"><?php echo $item['file_img'];?></a></td>
			  </tr>
			  <tr>
				<th scope="row" style="text-align:left;"><label><?php echo __('File to download', odWpDownloadsPlugin::$textdomain);?>:</label></th>
				<td><a href="<?php echo $this->get_public_url($item['file']);?>" target="_BLANK"><?php echo $item['file'];?></a></td>
			  </tr>
			  <tr>
				<td colspan="2">
				  <label for="display"><?php echo __('Display this item on the public site?', odWpDownloadsPlugin::$textdomain);?>
				  <input type="checkbox" name="display"<?php if($item['display'] == '1') echo ' checked="checked"';?>/>
				</td>
			  </tr>
			</table>
			<p>
			  <input type="submit" value="<?php echo __('Save', odWpDownloadsPlugin::$textdomain);?>" name="savedownloaditem" class="button-primary action" />
			</p>
		  </form>
		</div>
<?php
		}
	  }
	}
	else if(isset($_POST['savedownloaditem'])) {
	  $item_ID = $_POST['item_ID'];
	  $title = $_POST['title'];
	  $display = (isset($_POST['display'])) ? 1 : 0;
	  
	  $query = "UPDATE `{$wpdb->prefix}downloads` SET `title` = '{$title}', `display` = '{$display}' WHERE `ID`={$item_ID} LIMIT 1 ;";
	  $res = $wpdb->query($query);
?>
	  <div id="message" class="updated fade">
		<p><strong><?php echo __('Item was successfully updated.', odWpDownloadsPlugin::$textdomain);?></strong></p>
	  </div>
<?php
	}
?>
	  <form action="<?php echo get_option('home');?>/wp-admin/admin.php?page=od-downloads-plugin" method="post" enctype="multipart/form-data">
		<input type="hidden" name="page" value="od-downloads"/>
		<div class="tablenav">
		  <div class="alignleft actions">
			<select name="action" class="select-action">
			  <option value="-1" selected="selected"><?php echo __('Multiple actions', odWpDownloadsPlugin::$textdomain);?></option>
			  <option value="delete"><?php echo __('Delete', odWpDownloadsPlugin::$textdomain);?></option>
			</select>
			<input type="submit" value="<?php echo __('Apply', odWpDownloadsPlugin::$textdomain);?>" name="doaction" id="doaction" class="button-secondary action" />
		  </div>
		  <br class="clear" />
		</div>
		<div class="clear"></div>
		<table class="widefat post fixed" cellspacing="0">
		  <thead>
			<tr>
			  <th scope="col" style="width: 5%;"><input type="checkbox" id="downloads-table-selectall-check"/></th>
			  <th scope="col" style="width:28%;"><?php echo __('Title', odWpDownloadsPlugin::$textdomain);?></th>
			  <th scope="col" style="width:26%;"><?php echo __('Image file', odWpDownloadsPlugin::$textdomain);?></th>
			  <th scope="col" style="width:26%;"><?php echo __('File to download', odWpDownloadsPlugin::$textdomain);?></th>
			  <th scope="col" style="width: 5%;"><?php echo __('Display', odWpDownloadsPlugin::$textdomain);?></th>
			</tr>
		  </thead>
		  <tfoot>
			<tr>
			  <th scope="col" style="width: 5%;"><input type="checkbox" id="downloads-table-selectall-check"/></th>
			  <th scope="col" style="width:28%;"><?php echo __('Title', odWpDownloadsPlugin::$textdomain);?></th>
			  <th scope="col" style="width:26%;"><?php echo __('Image file', odWpDownloadsPlugin::$textdomain);?></th>
			  <th scope="col" style="width:26%;"><?php echo __('File to download', odWpDownloadsPlugin::$textdomain);?></th>
			  <th scope="col" style="width: 5%;"><?php echo __('Display', odWpDownloadsPlugin::$textdomain);?></th>
			</tr>
		  </tfoot>
		  <tbody id="the-list" class="list:oddownloads">
			<?php echo $this->render_admin_list();?>
		  </tbody>
		</table>
		<div class="tablenav">
		  <div class="alignleft actions">
			<select name="action2" class="select-action">
			  <option value="-1" selected="selected"><?php echo __('Multiple actions', odWpDownloadsPlugin::$textdomain);?></option>
			  <option value="delete"><?php echo __('Delete', odWpDownloadsPlugin::$textdomain);?></option>
			</select>
			<input type="submit" value="<?php echo __('Apply', odWpDownloadsPlugin::$textdomain);?>" name="doaction2" id="doaction2" class="button-secondary action" />
		  </div>
		  <br class="clear" />
		</div>
	  </form>
	</div>
<?php 
  }
  
  /**
   * Renders list of downloads
   * 
   * @return string
   */
  function render_admin_list() 
  {
	global $wpdb;
	
	$root_dir = $this->get_rootdir();
	$ret = "";
	// XXX Here should not be only displayed!
	$rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}downloads ", ARRAY_A);
	$i = 0;

	if(is_null($rows)) {
	  return "
			  <tr class=\"status-inherit\">
				  <td colspan=\"3\">
					  <div id=\"message\" class=\"updated fade\">
						  <p>" . __('No items created yet.', odWpDownloadsPlugin::$textdomain) . "</p>
					  </div>
				  </td>
			  </tr>";
	}
	
	foreach($rows as $row) {
	  $ret .= "
			  <tr class=\"".(($i++%2)?'alternate':'')."status-inherit\">
				  <th scope=\"row\"><input type=\"checkbox\" name=\"items[]\" value=\"".$row['ID']."\"/></td>
				  <td><a href=\"" . get_option('home') . "/wp-admin/admin.php?page=od-downloads-plugin&amp;item_ID=" . $row['ID'] . "\">" . $row['title'] . "</a></td>
				  <td><code style=\"font-size: 6pt;\">" . $row['file_img'] . "</code></td>
				  <td><code style=\"font-size: 6pt;\">" . $row['file'] . "</code></td>
				  <td><span style=\"color: " . (($row['display']=='1')?'green;">' . __('yes', odWpDownloadsPlugin::$textdomain) : '#f30;">' . __('no', odWpDownloadsPlugin::$textdomain)) . "</span></td>
			  </tr>";
	}
	
	return $ret;
  }
  
  /**
   * Renders add page for the plugin
   * 
   * @returns void
   */
  function add_page()
  {
	global $wpdb;
	
	$options = $this->get_options();
	
	if(isset($_POST['savenewitem'])) {
	  $title = $_POST['title'];
	  $file_img = $_FILES['file_img'];
	  $file = $_FILES['file'];
	  $display = (isset($_POST['title'])) ? 1 : 0;
	  $uploaddir = $this->get_rootdir();
	  $error_msgs = array();
	  $filename = '';
	  $img_filename = '';
	  
	  // Upload download file
	  $file_upload_failed = true;
	  if($file['name'] != '') {
		$filename = basename($file['name']);
		$uploadfile = $uploaddir . DIRECTORY_SEPARATOR . $filename;
		
		// XXX Check `max_upload_filesize` value!!!
		if(@move_uploaded_file($file['tmp_name'], $uploadfile)) {
		  $file_upload_failed = false;
		}
	  }
	  
	  if($file_upload_failed === true) {
		$error_msgs[] = __('File to download was not successfully uploaded to the server!', odWpDownloadsPlugin::$textdomain);
	  }
	  
	  // Upload and resize thumbnail image
	  $upload_img_failed = true;
	  if($file_img['name'] != '') {
		// We allow only JPEG images
		// XXX Enable more images types (define them in plugin's settings)
		$ext = strtolower(strrchr($file_img['name'], '.')); 
		// XXX Oh, and what about files with more than one dot!
		
		if($ext != '.jpg') {
		  $error_msgs[] = __('You can upload only images of <code>JPG</code> type!', odWpDownloadsPlugin::$textdomain);
		} else {
		  $img_filename = basename($file_img['name']);
		  $uploadfile = $uploaddir . DIRECTORY_SEPARATOR . str_replace('.jpg', '_.jpg', $img_filename);
		  
		  // XXX Check `max_upload_filesize` value!!!
		  if(@move_uploaded_file($file_img['tmp_name'], $uploadfile)) {
			list($imagewidth, $imageheight) = getimagesize($uploadfile);
			$scale = $imagewidth / (int) $options['downloads_thumb_size_width'];
			$new_width = round($imagewidth * (1 / $scale));
			$new_height = round($imageheight * (1 / $scale));
			
			$image_resized = imagecreatetruecolor($new_width, $new_height);
			$image_tmp = imagecreatefromjpeg($uploadfile);
			imagecopyresampled($image_resized, $image_tmp, 0, 0, 0, 0, $new_width, 
							   $new_height, $imagewidth, $imageheight);
			
			// Save image with the same name but suffixed with '_'
			imagejpeg($image_resized, str_replace('_.jpg', '.jpg', $uploadfile));
			imagedestroy($image_resized);
			// XXX Delete original (not-resized) image!!!!
		  }
		}
	  }
	  
	  if($upload_img_failed === true) {
		$errror_msgs[] = __('Image was not successfully uploaded!', odWpDownloadsPlugin::$textdomain);
	  }
	  
	  if(count($error_msgs) == 0) {
		$query = "
			INSERT INTO `{$wpdb->prefix}downloads` 
			  (`ID`,`title`, `file_img`, `file`, `created`, `display`)
			VALUES
			  (NULL, '{$title}', '{$img_filename}', '{$filename}', CURRENT_TIMESTAMP, '{$display}');";
		
		$res = $wpdb->query($query);
?>
		<div id="message" class="updated fade">
		  <p><strong><?php echo __('Item was successfully created (with ID <code>', odWpDownloadsPlugin::$textdomain); echo $wpdb->insert_id; echo '</code>).';?></strong></p>
		</div>
<?php 
	  } else {
?>
		<div id="message" class="updated fade">
		  <p><strong><?php echo __('Item was not successfully created!', odWpDownloadsPlugin::$textdomain);?></strong></p>
		  <?php foreach($error_msgs as $error_msg):?>
			<p><?php echo $error_msg;?></p>
		  <?php endforeach;?>
		</div>
<?php
	  }
	}
?>
	<div class="wrap">
	  <div class="icon32">
		<img src="<?php echo WP_PLUGIN_URL . '/' . odWpDownloadsPlugin::$plugin_id . '/icon32.png';?>"/>
	  </div>
	  <h2><?php echo __('Downloads - Add new item', odWpDownloadsPlugin::$textdomain);?></h2>
	  <div id="downloadshelpdiv" class="postbox" style="width: 90%; padding: 10px 20px 10px 20px;">
		<form action="<?php echo get_option('home');?>/wp-admin/admin.php?page=od-downloads-add" method="post" enctype="multipart/form-data">
		  <table cellspacing="1" cellpadding="1" style="width: 90%;">
			<tr>
			  <th scope="row" style="text-align:left; min-width: 190px;"><label for="title"><?php echo __('Title:', odWpDownloadsPlugin::$textdomain);?></label></th>
			  <td><input type="text" name="title" value="" style="width: 100%;"/></td>
			</tr>
			<tr>
			  <th scope="row" style="text-align:left; vertical-align:top;"><label for="file_img"><?php echo __('Thumbnail:', odWpDownloadsPlugin::$textdomain);?></label></th>
			  <td>
				<input type="file" name="file_img" value="" style="width: 100%;"/><br/>
				<?php echo __('Upload only images of the <code>JPEG</code> type (file extension <code>.jpg</code>). Uploaded images will be resized to width 146 of pixels.', odWpDownloadsPlugin::$textdomain);?>
			  </td>
			</tr>
			<tr>
			  <th scope="row" style="text-align:left;"><label for="file_img"><?php echo __('File to download:', odWpDownloadsPlugin::$textdomain);?></label></th>
			  <td><input type="file" name="file" value="" style="width: 100%;"/></td>
			</tr>
			<tr>
			  <td colspan="2">
				<label for="display"><?php echo __('Display this item on the public site?', odWpDownloadsPlugin::$textdomain);?>
				<input type="checkbox" name="display" checked="checked"/>
			  </td>
			</tr>
		  </table>
		  <p>
			<input type="submit" value="<?php echo __('Save', odWpDownloadsPlugin::$textdomain);?>" name="savenewitem" class="button-primary action" />
		  </p>
		</form>
	  </div>
	</div>
<?php 
  }
  
  /** 
   * Renders settings page for the plugin 
   * 
   * @return void
   */
  function settings_page()
  {
	$options = $this->get_options();
?>
	<div class="wrap">
	  <div class="icon32">
		<img src="<?php echo WP_PLUGIN_URL . '/' . odWpDownloadsPlugin::$plugin_id . '/icon32.png';?>"/>
	  </div>
	  <h2><?php echo __('Downloads - Settings', odWpDownloadsPlugin::$textdomain);?></h2>
<?php
	if(isset($_POST['settings_save'])){
	  $options['main_downloads_dir'] = $_POST['option-main_downloads_dir']; 
	  $options['downloads_page_id'] = (int) $_POST['option-download_page_id']; 
	  $options['downloads_thumb_size_width'] = (int)$_POST['option-downloads_thumb_size_width'];
	  $options['downloads_shortlist_max_count'] = (int) $_POST['option-downloads_shortlist_max_count'];
	  update_option('site_customization-options', $options);
    ?>
	  <div id="message" class="updated fade">
		<p><?php echo __('Settings updated.', odWpDownloadsPlugin::$textdomain);?></p>
	  </div>
<?php
	}
?>
	  <form action="<?php echo get_option('home');?>/wp-admin/admin.php?page=od-downloads-settings" method="post" enctype="multipart/form-data">
		<div>
		  <table class="widefat post fixed" cellpadding="1" cellspacing="1" style="100%;">
			<tr>
			  <th scope="row"><label for="option-download_page_id"><?php echo __('Insert ID of page, where you want to display downloads list:', odWpDownloadsPlugin::$textdomain);?></label></th>
			  <td><input type="text" name="option-download_page_id" style="min-width: 300px;" value="<?php echo $options['downloads_page_id'];?>"/></td>
			</tr>
			<tr>
			  <th scope="row"><label for="option-main_downloads_dir"><?php echo __('Insert relative path to downloads directory:', odWpDownloadsPlugin::$textdomain);?></label></th>
			  <td><input type="text" name="option-main_downloads_dir" style="min-width: 300px;" value="<?php echo $options['main_downloads_dir'];?>"/></td>
			</tr>
			<tr>
			  <th scope="row"><label for="option-downloads_thumb_size_width"><?php echo __('Insert max. width for the thumbnail images:', odWpDownloadsPlugin::$textdomain);?></label></th>
			  <td><input type="text" name="option-downloads_thumb_size_width" value="<?php echo $options['downloads_thumb_size_width'];?>"/>&nbsp;<?php echo __('pixels', odWpDownloadsPlugin::$textdomain);?></td>
			</tr>
			<tr>
			  <th scope="row"><label for="option-downloads_shortlist_max_count"><?php echo __('Set the count of items displayed in the short list:', odWpDownloadsPlugin::$textdomain);?></label></th>
			  <td><input type="text" name="option-downloads_shortlist_max_count" value="<?php echo $options['downloads_shortlist_max_count'];?>"/>&nbsp;<?php echo __('items', odWpDownloadsPlugin::$textdomain);?></td>
			</tr>
		  </table>
		  <hr/>
		  <input type="submit" value=" <?php echo __('Save', odWpDownloadsPlugin::$textdomain);?> " name="settings_save" class="button-primary action" />
		</div>
	  </form>
	</div>
<?php
  }
  
  /** 
   * Returns full path to directory where are stored single downloads files.
   * 
   * @return string
   */
  function get_rootdir() 
  {
	$options = $this->get_options();
	$dir = str_replace('/', DIRECTORY_SEPARATOR, $options['main_downloads_dir']);
	
	// XXX Get correct path by any other way (use WP internals)!!!
	return dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . $dir;
  }
  
  /**
   * Generates public URL for download's cover image or download file.
   * 
   * @param string $file
   * @return string
   */
  function get_public_url($file)
  {
	$options = $this->get_options();
	
	$url  = get_option('home') . '/';
	$url .= $options['main_downloads_dir']; // XXX This expecting that there is allways ending '/'
	$url .= $file;
	
	return $url;
  }
  
  /**
   * Get short list of latest downloads for public site.
   * 
   * @param int $max_count Optional. Defaultly $options['downloads_shortlist_max_count'].
   * @param bool $show_thumbnails Optional. Defaultly TRUE.
   * @return string
   */
  function get_public_itemslist($max_count = false, $show_thumbnails = true)
  {
	global $wpdb; 
	
	$options = $this->get_options();
	$root_dir = $this->get_rootdir();
	$max_count = ($max_count !== false) ? $max_count : $options['downloads_shortlist_max_count'];
	$ret = '';
	$rows = $wpdb->get_results('SELECT * FROM `' . $wpdb->prefix . 'downloads` WHERE `display` = 1 ORDER BY `created` DESC LIMIT 0, ' . $max_count . ' ', ARRAY_A);
	$i = 0;

	if(is_null($rows)) {
	  return __('No items found.', odWpDownloadsPlugin::$textdomain);
	}
	
	foreach($rows as $row) {
	  $ret .= '<p>' . 
	          '<a href="' . $this->get_public_url($row['file']) . '" target="_blank">' . 
			  (($show_thumbnails) ? '<img src="' . $this->get_public_url($row['file_img']) . '" title="' . $row['title'] . '" alt="' . $row['title'] . '" />' : $row['file']) . 
			  '</a>' . 
			  '</p>';
	}
	
	return $ret;
  }
  
  /**
   * Get list of downloads as the page for the public site
   * 
   * XXX Rename this method [get_public_page]!!!
   * @return string
   */
  function render_list_for_public()
  {
	global $wpdb;
	
	$root_dir = $this->get_rootdir();
	$ret = '<div class="listy">';
	$rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}downloads WHERE `display` = 1 ", ARRAY_A);
	$i = 0;

	if(is_null($rows)) {
	  return _('No items found.');
	}
	
	foreach($rows as $row) {
	  $ret .= '<div class="list">' . 
				  '<a href="' . $this->get_public_url($row['file']) . '" target="_blank"><img title="' . $row['title'] . '" src="' . $this->get_public_url($row['file_img']) . '" alt="' . $row['title'] . '" /></a>' . 
			  '</div>';
	}
	
	$ret .= '</div>';
	
	return $ret;
  }
	
}

// ===========================================================================
// Plugin initialization

global $od_downloads_plugin;

$od_downloads_plugin = new odWpDownloadsPlugin();
