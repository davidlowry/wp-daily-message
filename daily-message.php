<?php
/**
 * @package Daily_Message
 * @author David Lowry
 * @version 0.2
 */
/*
Plugin Name: Daily Message
Plugin URI: http://davidlowry.co.uk
Description: Simple plugin to fetch a daily message from a database. (For example, to use as a daily message calendar)
Author: David Lowry
Version: 0.1
Author URI: http://davidlowry.co.uk
*/

/* ADMIN STUFF */
// Create a master category for Calendar and its sub-pages
add_action('admin_menu', 'daily_message_menu');

function daily_message_menu() 
{
  global $wpdb;

  // We make use of the Calendar tables so we must have installed daily_message
  check_daily_message();

  // Set admin as the only one who can use daily_message for security
  $allowed_group = 'edit_post'; //'manage_options';
  
  // Add the admin panel pages for daily_message. Use permissions pulled from above
   if (function_exists('add_menu_page')) 
     {
       add_menu_page(__('Daily Message','daily_message'), __('Daily Message','daily_message'), $allowed_group, 'daily_message', 'edit_daily_message');
     }
   if (function_exists('add_submenu_page')) 
     {
       add_submenu_page('daily_message', __('Manage Daily Messages','daily_message'), __('Manage Daily Messages','daily_message'), $allowed_group, 'daily_message', 'edit_daily_message');
       // add_action( "admin_head", 'calendar_add_javascript' );
       // Note only admin can change calendar options
       // add_submenu_page('daily_message', __('Manage Categories','calendar'), __('Manage Categories','calendar'), 'manage_options', 'calendar-categories', 'manage_categories');
       // add_submenu_page('daily_message', __('Calendar Config','calendar'), __('Calendar Options','calendar'), 'manage_options', 'calendar-config', 'edit_calendar_config');
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

// Define the tables used in Daily Message 
define('WP_DAILY_MESSAGE_TABLE', $table_prefix . 'daily_message');
define('WP_DAILY_MESSAGE_DEFAULTS_TABLE', $table_prefix . 'daily_message_defaults');
define('WP_DAILY_MESSAGE_CONFIG_TABLE', $table_prefix . 'daily_message_config');
define('WP_DAILY_MESSAGE_CATEGORIES_TABLE', $table_prefix . 'daily_message_categories');

function check_daily_message() {
  // Lets see if this is first run and create us a table if it is!
  global $wpdb, $initial_style;
  
  $initial_style = ".daily_message_widget { border: 3px solid red; }";
  
  // Assume this is not a new install until we prove otherwise
  $new_install = false;
  
  $wp_daily_message_exists = false;
  $wp_daily_message_defaults_exists = false;
  $wp_daily_message_config_exists = false;
  $wp_daily_message_config_version_number_exists = false;
  
  // Determine the daily message version
  $tables = $wpdb->get_results("show tables;");
  
  foreach ( $tables as $table ) {
    foreach ( $table as $value ) {
  	  if ( $value == WP_DAILY_MESSAGE_TABLE ) {
        $wp_daily_message_exists = true;
      }
      if ( $value == WP_DAILY_MESSAGE_DEFAULTS_TABLE ) {
        $wp_daily_message_defaults_exists = true;
      }
  	  if ( $value == WP_DAILY_MESSAGE_CONFIG_TABLE ) {
        $wp_daily_message_config_exists = true;
          
        // We now try and find the version number
        $version_number = $wpdb->get_var("SELECT config_value FROM " . WP_DAILY_MESSAGE_CONFIG_TABLE . " WHERE config_item='daily_message_version'"); 
        if ($version_number == "0.1") {
          // unneeded leaving for code example.
          // $wp_daily_message_config_version_number_exists = true;
    		}
      }
    }
  }
    $new_install = true;
  if ($wp_daily_message_exists == false && $wp_daily_message_config_exists == false) {

  }
  
  if ( $new_install == true ) {
    $sql = "DROP TABLE " . WP_DAILY_MESSAGE_DEFAULTS_TABLE . ";
        CREATE TABLE " . WP_DAILY_MESSAGE_DEFAULTS_TABLE . " (
        day_of_month INT(3) NOT NULL,
        message_topic VARCHAR(30) NOT NULL,
        message_long TEXT,
        
        PRIMARY KEY (day_of_month)
    )";
    $wpdb->get_results($sql);
    
    for ( $counter = 10; $counter <= 31; $counter += 1) {
      // Set up the default 31 days
      $sql = "INSERT INTO " . WP_DAILY_MESSAGE_DEFAULTS_TABLE . " SET day_of_month=$counter, message_topic='NOT SET', message_long='NOT SET'";
      $wpdb->get_results($sql);
    }

    // $sql = "CREATE TABLE " . WP_DAILY_MESSAGE_TABLE . " (
    //     message_id INT(11) NOT NULL AUTO_INCREMENT,
    //     message_date DATE NOT NULL,
    //     message_topic VARCHAR(30) NOT NULL,
    //     message_long TEXT,
    // 
    //     message_begin DATE NOT NULL ,
    //     message_end DATE NOT NULL ,
    // 
    //     message_recur CHAR(1) ,
    //     message_repeats INT(3) ,
    //     message_author BIGINT(20) UNSIGNED,
    // 
    //     PRIMARY KEY (message_id)
    // )";
    // $wpdb->get_results($sql);

    // $sql = "CREATE TABLE " . WP_DAILY_MESSAGE_CONFIG_TABLE . " (
    //     config_item VARCHAR(30) NOT NULL ,
    //     config_value TEXT NOT NULL ,
    //     PRIMARY KEY (config_item)
    // )";
    // $wpdb->get_results($sql);

  }
}

function wp_daily_messages_display_list()
{
	global $wpdb;
	
	$default_messages = $wpdb->get_results("SELECT * FROM " . WP_DAILY_MESSAGE_DEFAULTS_TABLE . " ORDER BY day_of_month ASC");
  // $overriding_messages = $wpdb->get_results("SELECT * FROM " . WP_DAILY_MESSAGE_TABLE . " ORDER BY message_date ASC");
  
  if (empty($default_messages)) {
    ?>
		<p><?php _e("There are no messages in the database!",'daily_message')	?></p>
		<?php
	} else {
	  ?>
    <h2><?php _e('Daily Message Monthly Entries','daily_message'); ?></h2>
    <?php
		$class = '';
		
		$count = 0;
		
		foreach ( $default_messages as $message )
		{
			$class = ($class == 'alternate') ? '' : 'alternate';
			if ($count==0 || $count== 16) {
			  ?>
    		<table class="widefat page fixed" width="45%" style="width:45%;float:left; clear:none; margin-top: 1em;margin-right: 1em" cellpadding="3" cellspacing="3">
          <thead>
    		    <tr>
      				<th class="manage-column" scope="col" width="33%"><?php _e('Day of Month','daily_message') ?></th>
      				<th class="manage-column" scope="col" width="66%"><?php _e('Text','daily_message') ?></th>
    		    </tr>
          </thead>
          <tbody>
    		<?php
			}
			?>
			<tr class="<?php echo $class; ?>">
				<th scope="row"><?php echo $message->day_of_month; ?> <?php echo $message->message_topic; ?> <br /><a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=daily_message&amp;action=edit&amp;day_of_month=<?php echo $message->day_of_month;?>" class='edit'><?php echo __('Edit','daily_message'); ?></a> | <a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=daily_message&amp;action=delete&amp;event_id=<?php echo $message->day_of_month;?>" class="delete" onclick="return confirm('<?php _e('Are you sure you want to delete this daily_message item?','daily_message'); ?>')"><?php echo __('Delete','daily_message'); ?></a></th>

				<td><?php echo $message->message_long; ?></td>
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
function wp_daily_message_defaults_edit_form($mode='edit', $day_of_month=false)
{
	global $wpdb,$users_entries;
	$data = false;
	
	if ( $day_of_month !== false ) {
		if ( intval($day_of_month) != $day_of_month ) {
			echo "<div class=\"error\"><p>".__('Bad Monkey! No banana!','daily_message')."</p></div>";
			return;
		} else {
			$data = $wpdb->get_results("SELECT * FROM " . WP_DAILY_MESSAGE_DEFAULTS_TABLE . " WHERE day_of_month='" . mysql_escape_string($day_of_month) . "' LIMIT 1");
			if ( empty($data) ) {
				echo "<div class=\"error\"><p>".__("An event with that ID couldn't be found",'daily_message')."</p></div>";
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

	<form name="messageform" id="messageform" class="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=daily_message">
		<input type="hidden" name="action" value="<?php echo "edit_save"?>">
	
		<div id="linkadvanceddiv" class="postbox">
			<div style="float: left; width: 98%; clear: both;" class="inside">
        <fieldset>
          <legend><?php _e('Day of the Month','daily_message'); ?></legend>
          
          <input type="text" name="day_of_month" class="input" size="40" maxlength="30"
            value="<?php if ( !empty($data) ) echo htmlspecialchars($data->day_of_month); ?>" />

				</fieldset>
				
				<fieldset id="" class="">
				  <legend><?php _e('Message Topic','daily_message'); ?></legend>
          <input type="text" name="message_topic" class="input" size="40" maxlength="30" 
            value="<?php if ( !empty($data) ) echo htmlspecialchars($data->message_topic); ?>" />
				  
				</fieldset>

				<fieldset id="" class="">
				  <legend><?php _e('Message Text','daily_message'); ?></legend>
				  <textarea name="message_long" class="input" rows="5" cols="50"><?php if ( !empty($data) ) echo htmlspecialchars($data->message_long); ?></textarea>
				  
				</fieldset>
			</div>
		</div>

    <input type="submit" name="save" class="button bold" value="<?php _e('Save','daily_message'); ?> &raquo;" />
	</form>
	<?php
}

function edit_daily_message()
{
  global $current_user, $wpdb, $users_entries;
  // First some quick cleaning up 
  $edit = $create = $save = $delete = false;

  // Make sure we are collecting the variables we need to select years and months
  $action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
  // $day_of_month = !empty($_REQUEST['day_of_month']) ? $_REQUEST['day_of_month'] : '';

  // Lets see if this is first run and create us a table if it is!
  check_daily_message();

  ?>

  <div class="wrap">
  	<?php
  	if ( $action == 'edit' || ($action == 'edit_save' && $error_with_saving == 1))
  	{
  		?>
  		<h2><?php _e('Edit Entry','daily_message'); ?></h2>
  		<?php
  		if ( empty($day_of_month) ) {
  		  $day_of_month = $_GET['day_of_month'];
  		}

  		if ( empty($day_of_month) ) {
  			echo "<div class=\"error\"><p>".__("You must provide an day_of_month in order to edit it",'daily_message')."</p></div>";
  		} else {
  		  echo "<h3>Edit Message Entry</h3>";
  			wp_daily_message_defaults_edit_form("edit_save",$day_of_month);
  		}	
  	}
  	?>
  </div>
  <?php
  // Deal with edit/saving an event to the database - 1-31 will exist already
  if ( $action == 'edit_save' ) {
  	$day_of_month = !empty($_REQUEST['day_of_month']) ? $_REQUEST['day_of_month'] : '';
  	$message_topic = !empty($_REQUEST['message_topic']) ? $_REQUEST['message_topic'] : '';
  	$message_long = !empty($_REQUEST['message_long']) ? $_REQUEST['message_long'] : '';

  	// Deal with the fools who have left magic quotes turned on
  	if ( ini_get('magic_quotes_gpc') ) {
  		$day_of_month = stripslashes($day_of_month);
  		$message_topic = stripslashes($message_topic);
  		$message_long = stripslashes($message_long);
  	}	

    if ( empty($day_of_month) ) {
  		?>
  		<div class="error"><p><strong><?php _e('Failure','daily_message'); ?>:</strong> <?php _e("You can't update an daily_message entry if you haven't submitted an day of month",'daily_message'); ?></p></div>
  		<?php		
  	} else {
    	// The title must be at least one character in length and no more than 30 - no non-standard characters allowed
    	if (preg_match('/^[a-zA-Z0-9]{1}[a-zA-Z0-9[:space:]]{0,29}$/',$message_topic)) {
    	    $message_topic_ok =1;
    	} else { ?>
        <div class="error"><p><strong><?php _e('Error','daily_message'); ?>:</strong> <?php _e('The message topic must be between 1 and 30 characters in length and contain no punctuation. Spaces are allowed but the title must not start with one.','daily_message'); ?></p></div> <?php 
      } //endif
    
    	if ($message_topic_ok == 1) {
    		$sql = "UPDATE " . WP_DAILY_MESSAGE_DEFAULTS_TABLE . " SET message_topic='" . mysql_escape_string($message_topic) . "', message_long='" . mysql_escape_string($message_long) . "' WHERE day_of_month='" . mysql_escape_string($day_of_month) . "'";
    	$wpdb->get_results($sql);

    	$sql = "SELECT day_of_month FROM " . WP_DAILY_MESSAGE_DEFAULTS_TABLE . " WHERE message_topic='" . mysql_escape_string($message_topic) . "'" . " AND message_long='" . mysql_escape_string($message_long) . "' LIMIT 1";
    	$result = $wpdb->get_results($sql);

    	if ( empty($result) || empty($result[0]->day_of_month) ){ ?>
    		<div class="error"><p><strong><?php _e('Failure','calendar'); ?>:</strong> <?php _e('The database failed to return data to indicate the event has been updated sucessfully. This may indicate a problem with your database or the way in which it is configured.','daily_message'); ?></p></div>
    	<?php
    		} else {
    			?>
    			<div class="updated"><p><?php _e('Entry updated successfully','daily_message'); ?></p></div>
    			<?php
    			wp_daily_messages_display_list();
    		}
      } else {
        // The form is going to be rejected due to field validation issues, so we preserve the users entries here
        $users_entries->message_topic = $message_topic;
        $users_entries->message_long = $message_long;
        $users_entries->day_of_month = $day_of_month;

        $error_with_saving = 1;
      }		
  	}
  } else {
    // Action is not save nor edit, therefore show list
    wp_daily_messages_display_list();
  }

  // Now follows a little bit of code that pulls in the main 
  // components of this page; the edit form and the list of events
  ?>

  <?php
}

function todays_message() {
  global $wpdb;

  // This function cannot be called unless calendar is up to date
  check_daily_message();

  // Find out if we should be displaying todays events
  // $message = grab_message();
  $day_of_month = (int)date("d");
  $messages = $wpdb->get_results("SELECT * FROM " . WP_DAILY_MESSAGE_DEFAULTS_TABLE . " WHERE day_of_month='$day_of_month'");
  // echo print_r($messages);
  if (!empty($messages)) {
    foreach ($messages as $message) {
      if ($message->message_topic) {
        echo "<span id='message_feed'><h2>";
        echo $message->message_topic;
        echo "</h2><p>";
        echo substr($message->message_long,0,90);
        echo "...</p></span>";
      }
    }
  } else {
    echo "<span id='message_feed'><p>";
      echo "No message set for today";
    echo "</p></span>";
  }
}

function grab_message() {
  global $wpdb;
  
  $day_of_month = (int)date("d");
  $data = $wpdb->get_results("SELECT * FROM " . WP_DAILY_MESSAGE_DEFAULTS_TABLE . " WHERE day_of_month == '$day_of_month'");
  
  while($row=mysql_fetch_row($data)) {
   echo print_r($row);
  }
  return $row;
}

?>