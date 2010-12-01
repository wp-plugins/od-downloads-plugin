<?php
/**
 * Widget for downloads plugin
 * 
 * @author Ondrej Donek, <ondrejd@gmail.com>
 * @category odWpDownloadsPlugin
 * @version 0.4
 */
class odWpDownloadsWidget extends WP_Widget
{
  
  function odWpDownloadsWidget() 
  {
	// Widget options
	$widget_ops = array('classname' 	=> 'odWpDownloadsWidget', 
	                    'description' 	=> __('Widget of \'Downloads\' plugin. It can displays list of files.', odWpDownloadsPlugin::$textdomain));
	// Widget control options
	$control_ops = array('width' 		=> 200, 
	                     'height' 		=> 350, 
						 'id_base' 		=> 'downloads-widget' );
	// Create the widget
	$this->WP_Widget('downloads-widget', 
	                 __('Files to download', odWpDownloadsPlugin::$textdomain), 
					 $widget_ops, 
					 $control_ops);
  }
  
  function widget($args, $instance) 
  {
	extract($args);

	// User-selected settings
	$title = apply_filters('widget_title', $instance['title']);
	$maxcount = (int) $instance['limit'];
	$show_thumbs = (isset($instance['show_thumbs'])) ? $instance['show_thumbs'] : false;

	// Before widget (defined by theme)
	echo $before_widget;

	// Title of widget (before and after defined by theme)
	if($title) {
	  echo $before_title . $title . $after_title;
	}
	
	// Print list of downloads
	global $od_downloads_plugin;
	echo $od_downloads_plugin->get_public_itemslist($maxcount, $show_thumbs);
	
	// XXX Get correct URL from plugin self!!!
	echo '<h4><a href="' . get_option('home') . '/ke-stazeni/">' . 
	     __('Next', odWpDownloadsPlugin::$textdomain) . 
		 '</a></h4>';

	// After widget (defined by theme)
	echo $after_widget;
  }
  
  function update($new_instance, $old_instance) 
  {
	$instance = $old_instance;

	// Strip tags (if needed) and update the widget settings
	$instance['title'] = strip_tags($new_instance['title']);
	$instance['limit'] = $new_instance['limit'];
	$instance['show_thumbs'] = $new_instance['show_thumbs'];

	return $instance;
  }
  
  function form($instance) 
  {
	global $od_downloads_plugin;
	$options = $od_downloads_plugin->get_options();
	
	// Set up some default widget settings.
	$defaults = array('title' 		=> __('Files to download', odWpDownloadsPlugin::$textdomain), 
					  'limit' 		=> $options['downloads_shortlist_max_count'], 
					  'show_thumbs' => true);
	$instance = wp_parse_args((array) $instance, $defaults); 
?>
	<p>
	  <label for="<?php echo $this->get_field_id('title');?>"><?php echo __('Title', odWpDownloadsPlugin::$textdomain);?>:</label>
	  <input id="<?php echo $this->get_field_id('title');?>" name="<?php echo $this->get_field_name('title');?>" value="<?php echo $instance['title'];?>" style="width:100%;"/>
	</p>
	<p>
	  <label for="<?php echo $this->get_field_id('limit');?>"><?php echo __('Max. count of items', odWpDownloadsPlugin::$textdomain);?>:</label>
	  <input id="<?php echo $this->get_field_id('limit');?>" name="<?php echo $this->get_field_name('limit');?>" value="<?php echo $instance['limit'];?>"/>
	</p>
	<p>
	  <input class="checkbox" type="checkbox" <?php checked($instance['show_thumbs'], true);?> id="<?php echo $this->get_field_id('show_thumbs');?>" name="<?php echo $this->get_field_name('show_thumbs');?>"/>
	  <label for="<?php echo $this->get_field_id('show_thumbs');?>"><?php echo __('Display thumnails or plain links?', odWpDownloadsPlugin::$textdomain);?></label>
	</p>
<?php
  }
  
}
