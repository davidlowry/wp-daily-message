<?php
/**
 * @package Daily_Prayer
 * @author David Lowry
 * @version 0.2
 */
/*
Plugin Name: Daily Prayer
Plugin URI: http://davidlowry.co.uk
Description: Simple plugin to fetch a daily prayer topic from a database.
Author: David Lowry
Version: 0.1
Author URI: http://davidlowry.co.uk
*/

/* ADMIN STUFF */
// Create a master category for Calendar and its sub-pages
add_action('admin_menu', 'daily_prayer_menu');

function daily_prayer_menu() 
{
  global $wpdb;

  // We make use of the Calendar tables so we must have installed daily_prayer
  check_daily_prayer();

  // Set admin as the only one who can use daily_prayer for security
  $allowed_group = 'edit_post'; //'manage_options';
  
  // Add the admin panel pages for daily_prayer. Use permissions pulled from above
   if (function_exists('add_menu_page')) 
     {
       add_menu_page(__('Daily Prayer','daily_prayer'), __('Daily Prayer','daily_prayer'), $allowed_group, 'daily_prayer', 'edit_daily_prayer');
     }
   if (function_exists('add_submenu_page')) 
     {
       add_submenu_page('daily_prayer', __('Manage Daily Prayers','daily_prayer'), __('Manage Daily Prayers','daily_prayer'), $allowed_group, 'daily_prayer', 'edit_daily_prayer');
       // add_action( "admin_head", 'calendar_add_javascript' );
       // Note only admin can change calendar options
       // add_submenu_page('daily_prayer', __('Manage Categories','calendar'), __('Manage Categories','calendar'), 'manage_options', 'calendar-categories', 'manage_categories');
       // add_submenu_page('daily_prayer', __('Calendar Config','calendar'), __('Calendar Options','calendar'), 'manage_options', 'calendar-config', 'edit_calendar_config');
     }
}


/* VISUAL STUFF */
function convertDate2String($inputDate,$dateFormat=1) {

  switch ($dateFormat) {
    case 1:
      return date('F d, Y h:i:s A', strtotime($inputDate));
    break;

    case 2:
      return date('F d, Y G:i:s', strtotime($inputDate));
    break;

    case 3:
      return date('M d, Y h:i:s A', strtotime($inputDate));
    break;

    case 4:
      return date('M d, Y G:i:s', strtotime($inputDate));
    break;
  }
}

// Define the tables used in Daily Prayer 
define('WP_DAILY_PRAYER_TABLE', $table_prefix . 'daily_prayer');
define('WP_DAILY_PRAYER_DEFAULTS_TABLE', $table_prefix . 'daily_prayer_defaults');
define('WP_DAILY_PRAYER_CONFIG_TABLE', $table_prefix . 'daily_prayer_config');
define('WP_DAILY_PRAYER_CATEGORIES_TABLE', $table_prefix . 'daily_prayer_categories');

function check_daily_prayer() {
  // Lets see if this is first run and create us a table if it is!
  global $wpdb, $initial_style;
  
  $initial_style = ".daily_prayer_widget { border: 3px solid red; }";
  
  // Assume this is not a new install until we prove otherwise
  $new_install = false;
  
  $wp_daily_prayer_exists = false;
  $wp_daily_prayer_defaults_exists = false;
  $wp_daily_prayer_config_exists = false;
  $wp_daily_prayer_config_version_number_exists = false;
  
  // Determine the daily prayer version
  $tables = $wpdb->get_results("show tables;");
  
  foreach ( $tables as $table ) {
    foreach ( $table as $value ) {
  	  if ( $value == WP_DAILY_PRAYER_TABLE ) {
        $wp_daily_prayer_exists = true;
      }
      if ( $value == WP_DAILY_PRAYER_DEFAULTS_TABLE ) {
        $wp_daily_prayer_defaults_exists = true;
      }
  	  if ( $value == WP_DAILY_PRAYER_CONFIG_TABLE ) {
        $wp_daily_prayer_config_exists = true;
          
        // We now try and find the version number
        $version_number = $wpdb->get_var("SELECT config_value FROM " . WP_DAILY_PRAYER_CONFIG_TABLE . " WHERE config_item='daily_prayer_version'"); 
        if ($version_number == "0.1") {
          // unneeded leaving for code example.
          // $wp_daily_prayer_config_version_number_exists = true;
    		}
      }
    }
  }
    $new_install = true;
  if ($wp_daily_prayer_exists == false && $wp_daily_prayer_config_exists == false) {

  }
  
  if ( $new_install == true ) {
    $sql = "DROP TABLE " . WP_DAILY_PRAYER_DEFAULTS_TABLE . ";
        CREATE TABLE " . WP_DAILY_PRAYER_DEFAULTS_TABLE . " (
        day_of_month INT(3) NOT NULL,
        prayer_topic VARCHAR(30) NOT NULL,
        prayer_long TEXT,
        
        PRIMARY KEY (day_of_month)
    )";
    $wpdb->get_results($sql);
    
    for ( $counter = 10; $counter <= 31; $counter += 1) {
      // Set up the default 31 days
      $sql = "INSERT INTO " . WP_DAILY_PRAYER_DEFAULTS_TABLE . " SET day_of_month=$counter, prayer_topic='NOT SET', prayer_long='NOT SET'";
      $wpdb->get_results($sql);
    }

    // $sql = "CREATE TABLE " . WP_DAILY_PRAYER_TABLE . " (
    //     prayer_id INT(11) NOT NULL AUTO_INCREMENT,
    //     prayer_date DATE NOT NULL,
    //     prayer_topic VARCHAR(30) NOT NULL,
    //     prayer_long TEXT,
    // 
    //     prayer_begin DATE NOT NULL ,
    //     prayer_end DATE NOT NULL ,
    // 
    //     prayer_recur CHAR(1) ,
    //     prayer_repeats INT(3) ,
    //     prayer_author BIGINT(20) UNSIGNED,
    // 
    //     PRIMARY KEY (prayer_id)
    // )";
    // $wpdb->get_results($sql);

    // $sql = "CREATE TABLE " . WP_DAILY_PRAYER_CONFIG_TABLE . " (
    //     config_item VARCHAR(30) NOT NULL ,
    //     config_value TEXT NOT NULL ,
    //     PRIMARY KEY (config_item)
    // )";
    // $wpdb->get_results($sql);

  }
}

function wp_daily_prayers_display_list()
{
	global $wpdb;
	
	$default_prayers = $wpdb->get_results("SELECT * FROM " . WP_DAILY_PRAYER_DEFAULTS_TABLE . " ORDER BY day_of_month ASC");
  // $overriding_prayers = $wpdb->get_results("SELECT * FROM " . WP_DAILY_PRAYER_TABLE . " ORDER BY prayer_date ASC");
  
  if (empty($default_prayers)) {
    ?>
		<p><?php _e("There are no prayers in the database!",'daily_prayer')	?></p>
		<?php
	} else {
	  ?>
    <h2><?php _e('Daily Prayer Monthly Entries','daily_prayer'); ?></h2>
    <?php
		$class = '';
		
		$count = 0;
		
		foreach ( $default_prayers as $prayer )
		{
			$class = ($class == 'alternate') ? '' : 'alternate';
			if ($count==0 || $count== 16) {
			  ?>
    		<table class="widefat page fixed" width="45%" style="width:45%;float:left; clear:none; margin-top: 1em;margin-right: 1em" cellpadding="3" cellspacing="3">
          <thead>
    		    <tr>
      				<th class="manage-column" scope="col" width="33%"><?php _e('Day of Month','daily_prayer') ?></th>
      				<th class="manage-column" scope="col" width="66%"><?php _e('Text','daily_prayer') ?></th>
    		    </tr>
          </thead>
          <tbody>
    		<?php
			}
			?>
			<tr class="<?php echo $class; ?>">
				<th scope="row"><?php echo $prayer->day_of_month; ?> <?php echo $prayer->prayer_topic; ?> <br /><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=daily_prayer&amp;action=edit&amp;day_of_month=<?php echo $prayer->day_of_month;?>" class='edit'><?php echo __('Edit','daily_prayer'); ?></a> | <a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=daily_prayer&amp;action=delete&amp;event_id=<?php echo $prayer->day_of_month;?>" class="delete" onclick="return confirm('<?php _e('Are you sure you want to delete this daily_prayer item?','daily_prayer'); ?>')"><?php echo __('Delete','daily_prayer'); ?></a></th>

				<td><?php echo $prayer->prayer_long; ?></td>
			</tr>
			
			<?php
		$count++;
			if ($count==16 || $count==31) {
			?>
  			</tbody>
			</table>
			<?php
			}
		}
		?>
					<div class="clearfix" style="clear: left; height: 1px;">&nbsp;</div>
		<?php
	}
}


// The event edit form for the manage events admin page
function wp_daily_prayer_defaults_edit_form($mode='edit', $day_of_month=false)
{
	global $wpdb,$users_entries;
	$data = false;
	
	if ( $day_of_month !== false ) {
		if ( intval($day_of_month) != $day_of_month ) {
			echo "<div class=\"error\"><p>".__('Bad Monkey! No banana!','daily_prayer')."</p></div>";
			return;
		} else {
			$data = $wpdb->get_results("SELECT * FROM " . WP_DAILY_PRAYER_DEFAULTS_TABLE . " WHERE day_of_month='" . mysql_escape_string($day_of_month) . "' LIMIT 1");
			if ( empty($data) ) {
				echo "<div class=\"error\"><p>".__("An event with that ID couldn't be found",'daily_prayer')."</p></div>";
				return;
			}
			$data = $data[0];
		}
		// Recover users entries if they exist; in other words if editing an event went wrong
		if (!empty($users_entries)) {
	    $data = $users_entries;
	  }
	} else {
    $data = $users_entries;
  }
	
	?>

	<form name="prayerform" id="prayerform" class="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=daily_prayer">
		<input type="hidden" name="action" value="<?php echo "edit_save"?>">
	
		<div id="linkadvanceddiv" class="postbox">
			<div style="float: left; width: 98%; clear: both;" class="inside">
        <fieldset>
          <legend><?php _e('Day of the Month','daily_prayer'); ?></legend>
          
          <input type="text" name="day_of_month" class="input" size="40" maxlength="30"
            value="<?php if ( !empty($data) ) echo htmlspecialchars($data->day_of_month); ?>" />

				</fieldset>
				
				<fieldset id="" class="">
				  <legend><?php _e('Prayer Topic','daily_prayer'); ?></legend>
          <input type="text" name="prayer_topic" class="input" size="40" maxlength="30" 
            value="<?php if ( !empty($data) ) echo htmlspecialchars($data->prayer_topic); ?>" />
				  
				</fieldset>

				<fieldset id="" class="">
				  <legend><?php _e('Prayer Text','daily_prayer'); ?></legend>
				  <textarea name="prayer_long" class="input" rows="5" cols="50"><?php if ( !empty($data) ) echo htmlspecialchars($data->prayer_long); ?></textarea>
				  
				</fieldset>
			</div>
		</div>

    <input type="submit" name="save" class="button bold" value="<?php _e('Save','daily_prayer'); ?> &raquo;" />
	</form>
	<?php
}

function edit_daily_prayer()
{
  global $current_user, $wpdb, $users_entries;
  // First some quick cleaning up 
  $edit = $create = $save = $delete = false;

  // Make sure we are collecting the variables we need to select years and months
  $action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
  // $day_of_month = !empty($_REQUEST['day_of_month']) ? $_REQUEST['day_of_month'] : '';

  // Lets see if this is first run and create us a table if it is!
  check_daily_prayer();

  ?>

  <div class="wrap">
  	<?php
  	if ( $action == 'edit' || ($action == 'edit_save' && $error_with_saving == 1))
  	{
  		?>
  		<h2><?php _e('Edit Entry','daily_prayer'); ?></h2>
  		<?php
  		if ( empty($day_of_month) ) {
  		  $day_of_month = $_GET['day_of_month'];
  		}

  		if ( empty($day_of_month) ) {
  			echo "<div class=\"error\"><p>".__("You must provide an day_of_month in order to edit it",'daily_prayer')."</p></div>";
  		} else {
  		  echo "<h3>Edit Prayer Entry</h3>";
  			wp_daily_prayer_defaults_edit_form("edit_save",$day_of_month);
  		}	
  	}
  	?>
  </div>
  <?php
  // Deal with edit/saving an event to the database - 1-31 will exist already
  if ( $action == 'edit_save' ) {
  	$day_of_month = !empty($_REQUEST['day_of_month']) ? $_REQUEST['day_of_month'] : '';
  	$prayer_topic = !empty($_REQUEST['prayer_topic']) ? $_REQUEST['prayer_topic'] : '';
  	$prayer_long = !empty($_REQUEST['prayer_long']) ? $_REQUEST['prayer_long'] : '';

  	// Deal with the fools who have left magic quotes turned on
  	if ( ini_get('magic_quotes_gpc') ) {
  		$day_of_month = stripslashes($day_of_month);
  		$prayer_topic = stripslashes($prayer_topic);
  		$prayer_long = stripslashes($prayer_long);
  	}	

    if ( empty($day_of_month) ) {
  		?>
  		<div class="error"><p><strong><?php _e('Failure','daily_prayer'); ?>:</strong> <?php _e("You can't update an daily_prayer entry if you haven't submitted an day of month",'daily_prayer'); ?></p></div>
  		<?php		
  	} else {
    	// The title must be at least one character in length and no more than 30 - no non-standard characters allowed
    	if (preg_match('/^[a-zA-Z0-9]{1}[a-zA-Z0-9[:space:]]{0,29}$/',$prayer_topic)) {
    	    $prayer_topic_ok =1;
    	} else { ?>
        <div class="error"><p><strong><?php _e('Error','daily_prayer'); ?>:</strong> <?php _e('The prayer topic must be between 1 and 30 characters in length and contain no punctuation. Spaces are allowed but the title must not start with one.','daily_prayer'); ?></p></div> <?php 
      } //endif
    
    	if ($prayer_topic_ok == 1) {
    		$sql = "UPDATE " . WP_DAILY_PRAYER_DEFAULTS_TABLE . " SET prayer_topic='" . mysql_escape_string($prayer_topic) . "', prayer_long='" . mysql_escape_string($prayer_long) . "' WHERE day_of_month='" . mysql_escape_string($day_of_month) . "'";
    	$wpdb->get_results($sql);

    	$sql = "SELECT day_of_month FROM " . WP_DAILY_PRAYER_DEFAULTS_TABLE . " WHERE prayer_topic='" . mysql_escape_string($prayer_topic) . "'" . " AND prayer_long='" . mysql_escape_string($prayer_long) . "' LIMIT 1";
    	$result = $wpdb->get_results($sql);

    	if ( empty($result) || empty($result[0]->day_of_month) ){ ?>
    		<div class="error"><p><strong><?php _e('Failure','calendar'); ?>:</strong> <?php _e('The database failed to return data to indicate the event has been updated sucessfully. This may indicate a problem with your database or the way in which it is configured.','daily_prayer'); ?></p></div>
    	<?php
    		} else {
    			?>
    			<div class="updated"><p><?php _e('Entry updated successfully','daily_prayer'); ?></p></div>
    			<?php
    			wp_daily_prayers_display_list();
    		}
      } else {
        // The form is going to be rejected due to field validation issues, so we preserve the users entries here
        $users_entries->prayer_topic = $prayer_topic;
        $users_entries->prayer_long = $prayer_long;
        $users_entries->day_of_month = $day_of_month;

        $error_with_saving = 1;
      }		
  	}
  } else {
    // Action is not save nor edit, therefore show list
    wp_daily_prayers_display_list();
  }

  // Now follows a little bit of code that pulls in the main 
  // components of this page; the edit form and the list of events
  ?>

  <?php
}

function todays_prayer() {
  global $wpdb;

  // This function cannot be called unless calendar is up to date
  check_daily_prayer();

  // Find out if we should be displaying todays events
  // $prayer = grab_prayer();
  $day_of_month = (int)date("d");
  $prayers = $wpdb->get_results("SELECT * FROM " . WP_DAILY_PRAYER_DEFAULTS_TABLE . " WHERE day_of_month='$day_of_month'");
  // echo print_r($prayers);
  if (!empty($prayers)) {
    foreach ($prayers as $prayer) {
      if ($prayer->prayer_topic) {
        echo "<span id='prayer_feed'><h2>";
        echo $prayer->prayer_topic;
        echo "</h2><p>";
        echo substr($prayer->prayer_long,0,90);
        echo "...</p></span>";
      }
    }
  } else {
    echo "<span id='prayer_feed'><p>";
      echo "No prayer topic set for today";
    echo "</p></span>";
  }
}

function grab_prayer() {
  global $wpdb;
  
  $day_of_month = (int)date("d");
  $data = $wpdb->get_results("SELECT * FROM " . WP_DAILY_PRAYER_DEFAULTS_TABLE . " WHERE day_of_month == '$day_of_month'");
  
  while($row=mysql_fetch_row($data)) {
   echo print_r($row);
  }
  return $row;
}

?>