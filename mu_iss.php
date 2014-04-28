<?php
/*
 This is the development file
 Plugin Name: Invisible Sunday School
 Plugin URI: http://moladty.com/matt/iss
 Description: A unique plugin used for the distribution of the Invisible Sunday School material.
 Version: 1.59
 Author: Matthew Ude
 Author URI: http://moldaty.com/matt
 License: GPL2

 Copyright YEAR  Matthew R Ude  (email : sozomai@hotmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $iss_db_version;
global $iss_path;
global $iss_url;
global $iss_file_table;
global $iss_stat_table;
global $iss_lang;
global $iss_type;
global $iss_file_list;

$iss_db_version = "1.59";
$dir_arr = wp_upload_dir();
$iss_path = $dir_arr['basedir'].'/iss';
$iss_url = home_url('/files/iss');
$iss_file_table = $wpdb->prefix . "mu_iss_files";
$iss_stat_table = $wpdb->prefix . "mu_iss_stats";
$iss_file_list = get_option('iss_file_list');

// load basic lang and type array if not already saved as options
 if( !get_option( 'iss_type' ) ){
	$iss_type_def = array(
		'bible' => "Bible Stories",
		'creed' => "Apostle's Creed",
		);
	$iss_lang_def =  array(
		'english' => "English",
		'tamil' => "Tamil",
		);

	update_option ('iss_type',$iss_type_def );
	update_option ('iss_lang',$iss_lang_def );
}

// define global arrays for lang and type
$iss_type = get_option( 'iss_type' );
$iss_lang = get_option( 'iss_lang' );

if(get_option( 'iss_checked_file_date' ) < strtotime("-2 days")){

	$dir_scan_arr = scandir($iss_path.'/downloads');
	$dir_scan_arr = array_diff($dir_scan_arr, array('.', '..'));
	foreach ($dir_scan_arr as $file) {
		$file_exploded = explode('_', $file);
		if($file_exploded[1] < strtotime("-2 days")){
			unlink($iss_path.'/downloads'.'/'.$file);
		}
	}

	update_option( 'iss_checked_file_date', time() );
}

// $iss_url = "http://iss.nateude.com/iss/";
// $iss_path = "/home1/nateude/public_html/iss/"

//  Install function to install database table for use with ISS

function mu_iss_install (){
	global $wpdb;
	global $iss_db_version;
	global $iss_file_table;
	global $iss_stat_table;

	$sql = "CREATE TABLE $iss_file_table (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  name tinytext NOT NULL,
	  file VARCHAR(255) DEFAULT '' NOT NULL,
	  path blob NOT NULL,
	  type tinytext NOT NULL,
	  lang tinytext NOT NULL,
	  hits SMALLINT DEFAULT 0 NOT NULL,	  
	  UNIQUE KEY id (id)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	$sql_stat = "CREATE TABLE $iss_stat_table (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  ipaddress VARCHAR(20) DEFAULT '' NOT NULL,
	  totalfiles SMALLINT DEFAULT 0 NOT NULL,
	  totalsize BIGINT DEFAULT 0 NOT NULL,
	  langs MEDIUMTEXT NOT NULL,
	  types MEDIUMTEXT NOT NULL,  
	  UNIQUE KEY id (id)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql_stat );

	update_option( "mu_iss_db_version", $iss_db_version );
}

register_activation_hook( __FILE__, 'mu_iss_install' );

function mu_iss_admin_notice() {
    ?>
    <div class="updated">
        <p><?php _e( 'ISS Plugin Updated!' ); ?></p>
    </div>
    <?php
}

// Run upgrade if plugin version is not the same
if( get_option( "mu_iss_db_version", $iss_db_version ) != $iss_db_version ){
	add_action( 'admin_init', 'mu_iss_install' );
	add_action( 'admin_notices', 'mu_iss_admin_notice' ); 
}

add_action( 'admin_menu', 'mu_iss_menu' );
add_action( 'admin_init', 'mu_iss_admin_scripts_register' );


function mu_iss_menu() {
	add_menu_page( 'ISS Menu', 'ISS', 'upload_files', 'iss', 'mu_iss_admin_page', plugins_url( 'mu_iss/ISSIcon.png' ) );
	$mu_iss_add_files_hook_suffix = add_submenu_page( 'iss', 'ISS Add Files', 'Add Files', 'upload_files', 'iss-add', 'mu_iss_admin_page_add_files' ); 
	add_submenu_page( 'iss', 'ISS File Stats', 'File Stats', 'upload_files', 'iss-stats', 'mu_iss_admin_page_stats' ); 

	// add action to load scripts on particular page(s)
	add_action('admin_print_scripts-' . $mu_iss_add_files_hook_suffix, 'mu_iss_admin_scripts_load');
}

// Register scripts to be used on plugin pages
function mu_iss_admin_scripts_register() {
    /* Register our script. */
    wp_register_script( 'mu-iss-add-files-script', plugins_url( '/js/add-files-script.js', __FILE__ ), array('jquery-ui-sortable', 'jquery-ui-tabs') );
    wp_register_style( 'mu-iss-jquery-admin-style', plugins_url( '/css/overcast/jquery-ui-1.10.4.custom.css', __FILE__ ));
    wp_register_style( 'mu-iss-add-files-style', plugins_url( '/css/add-files-style.css', __FILE__ ), array('mu-iss-jquery-admin-style'));
}

function mu_iss_admin_scripts_load() {
    /* Link our already registered script to a page */
    wp_enqueue_script( 'mu-iss-add-files-script' );
    wp_enqueue_style( 'mu-iss-add-files-style' );
}

// notice function for updated types
function mu_iss_admin_notice_type() {
    ?>
    <div class="updated">
        <p><?php _e( 'Iss Types Updated!' ); ?></p>
    </div>
    <?php
}
// notice function for updated languages
function mu_iss_admin_notice_lang() {
    ?>
    <div class="updated">
        <p><?php _e( 'Iss Languages Updated!' ); ?></p>
    </div>
    <?php
}
// notice function for updated lesson list
function mu_iss_admin_notice_lesson_list() {
    ?>
    <div class="updated">
        <p><?php _e( 'Lesson List Updated!' ); ?></p>
    </div>
    <?php
}

// retrieve and save any post information

// retrieve post data for updating types
if( $_POST["nType"]){

		$newTypes_paths = $_POST["nType-path"];
		$newTypes_names = $_POST["nType-name"];
		unset($iss_type);
		foreach( $newTypes_paths as $key => $value ){
			if(!empty($value)){
				$iss_type[$value] = $newTypes_names[$key];
			}
		}

		update_option ('iss_type',$iss_type );
		add_action( 'admin_notices', 'mu_iss_admin_notice_type' );
} 
// Retrieve post data for updating languages
if( $_POST["nLang"]){

		$newLang_paths = $_POST["nLang-path"];
		$newLang_names = $_POST["nLang-name"];
		unset($iss_lang);
		foreach( $newLang_paths as $key => $value ){
			if(!empty($value)){
				$iss_lang[$value] = $newLang_names[$key];
			}
		}

		update_option ('iss_lang',$iss_lang );
		add_action( 'admin_notices', 'mu_iss_admin_notice_lang' );
} 
// Retrieve post data for updating files
if( $_POST["oFiles"]){
	global $err_ct;
	global $suc_ct;
	$err_ct=0;$suc_ct=0;

	$file_ups = $_POST["oFiles-up"];
	$file_ids = $_POST["oFiles-id"];
	$file_names = $_POST["oFiles-name"];
	foreach( $file_ups as $key => $value ){
		if($value==1){
			$data = array( 'name' => $file_names[$key] );
			$where = array( 'id' => $file_ids[$key] );
			$format = array( '%s' );
			$update_success = $wpdb->update(  $iss_file_table, $data, $where, $format );
			if( !$update_success ){	$err_ct++; } else { $suc_ct++;}
			$wpdb->print_error();
		}
	}
	
	function mu_iss_admin_notice_file_results() {
		global $err_ct;
		global $suc_ct;

		if($suc_ct>0 ){
	    ?>
	    <div class="updated">
	        <p><?php _e( $suc_ct.' files successfully Updated!' ); ?></p>
	    </div>
	    <?php }

		if($err_ct>0 ){
	    ?>
	    <div class="error">
	        <p><?php _e( 'Error! '.$err_ct.' files were unable to update!' ); ?></p>
	    </div>
	    <?php }	    
	}

	add_action( 'admin_notices', 'mu_iss_admin_notice_file_results' );
} 

// retrieve and save post information for lesson list
if( $_POST["lessonList"]){
		$iss_file_list = $_POST["file-list"];
		update_option ('iss_file_list',$iss_file_list );
		add_action( 'admin_notices', 'mu_iss_admin_notice_lesson_list' );
} 


// Main dashboard page for ISS
function mu_iss_admin_page() {
	if ( !current_user_can( 'upload_files' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	global $iss_path;
	global $iss_url;

}

// Show report on file Statistics 
function mu_iss_admin_page_stats() {
	if ( !current_user_can( 'upload_files' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	global $iss_path;
	global $iss_url;

}


// Search Directory for new files and add to Database
function mu_iss_admin_page_add_files() {
	if ( !current_user_can( 'upload_files' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	global $wpdb;
	global $iss_type;
	global $iss_lang;
	global $iss_path;
	global $iss_url;
	global $iss_file_table;
	global $iss_file_list;

	// Search for and add any new type of lang folders that have been added to the ISS upload folder
	// search iss directory for any new folders
	$dir_scan_arr = scandir($iss_path);
	// remove . and .. from directory array
	$dir_scan_arr = array_diff($dir_scan_arr, array('.', '..', 'downloads', 'Introduction.pdf'));
	$new_types = array_diff($dir_scan_arr, array_flip($iss_type));

	// General Page Header etc
	?><div id="mu_iss_add_page" class="wrap">
	<h2>Invisible Sunday School - Add files</h2> 
	<div class="tabs">
		<ul>
			<li><a href="#typlan">Types / Languages</a></li>
			<li><a href="#names">Lesson Names</a></li>
			<li><a href="#files">Files</a></li>
		</ul>
	<?php

	// Form for entering or changing new or existing types
	?><div id="typlan">
	<h3>Change or Add Types</h3>
	<form id="new_types" method="post"><input type="hidden" name="nType" value=1 ><?php
	foreach ($iss_type as $nType => $nName) {
		?>
		Type Path: <input type="text" class="disabled" name="nType-path[]" value="<?php echo $nType;  ?>"> Type Name: <input type="text" name="nType-name[]" class="nType" value="<?php echo $nName;  ?>"><br>
		<?php
	}
	if(!empty($new_types)){ ?><h4>New Types</h4><?php 
		foreach ($new_types as $nType) {
			?>
			Type Path: <input type="text" class="disabled" name="nType-path[]" value="<?php echo $nType;  ?>"> Type Name: <input type="text" name="nType-name[]" class="nType"><br>
			<?php
		}

		// if there are new types available we do not want the rest of the form available until they are taken care of. But first we need to print the submit button since otherwise it won't be available
		?><input type="submit" name="submit"  class="button button-primary" value="Save Changes"></form></div></div></div><?php
		die();
	}
	?><input type="submit" name="submit"  class="button button-primary" value="Save Changes"></form><?php


	// Search each type folder for new languages
	$count=0;
	foreach ($iss_type as $nType => $nName) {
		$dir_scan_arr = scandir($iss_path.'/'.$nType);
		// remove . and .. from directory array
		$dir_scan_arr = array_diff($dir_scan_arr, array('.', '..'));
		// we want to remove all the languages we already know about but first we want to create an array documenting the path to every type + lang folder, we will use this later
		foreach($dir_scan_arr as $nLang){
			$dir_type_folder[] = array(
								'path' => $iss_path.'/'.$nType.'/'.$nLang,
								'lang' => $nLang,
								'type' => $nType,
								);
		}
		// now we get rid of each lang we already know about leaving only the new ones
		$new_lang_array = array_diff($dir_scan_arr, array_flip($iss_lang));
		// now we create an array saving the new lang values
		if(!empty($new_lang_array)){
			foreach ($new_lang_array as $value) {
				$new_lang[$count] = $value;
				$count++;
			}
		}
	}
	// get rid of all multiple instances of a language
	$new_lang = array_unique($new_lang);

	// Form for entering or changing new or existing languages
	?><h3>Change or Add Languages</h3>
	<form id="new_lang" method="post"><input type="hidden" name="nLang" value=1 ><?php
	foreach ($iss_lang as $nLang => $nName) {
		?>
		Language Path: <input type="text" class="disabled" name="nLang-path[]" value="<?php echo $nLang;  ?>"> Language Name: <input type="text" name="nLang-name[]" class="nLang" value="<?php echo $nName;  ?>"><br>
		<?php
	}
	if(!empty($new_lang)){ ?><h4>New Languages</h4><?php 
		foreach ($new_lang as $nLang) {
			?>
			Language Path: <input type="text" class="disabled" name="nLang-path[]" value="<?php echo $nLang;  ?>"> Language Name: <input type="text" name="nLang-name[]" class="nLang"><br>
			<?php
		}

		// if there are new languages available we do not want the rest of the form available until they are taken care of. But first we need to print the submit button since otherwise it won't be available
		?><input type="submit" name="submit"  class="button button-primary" value="Save Changes"></form></div></div></div><?php
		die();
	}
	?><input type="submit" name="submit"  class="button button-primary" value="Save Changes"></form></div><?php

	// Create list of lesson names based on types of lessons, and form for updating list
	?><div id="names">
	<h3>List of Lesson Names</h3>
	<form id="lesson_list" method="post"><input type="hidden" name="lessonList" value=1 >
		<div class="tabs no-border">
			<ul>
				<?php foreach ($iss_type as $type => $name) { ?>
				<li><a href="#<?php echo $type ?>"><?php echo $name ?></a></li>
				<?php } ?>
			</ul>
		<?php
	foreach ($iss_type as $type => $name) {
		?><div id="<?php echo $type; ?>" ><h3><?php echo $name; ?></h3><ul class="sortable lesson-list"><?php
		// if there is on file list for this type of lesson we will create one based on the file names for the English lessons
		if(empty($iss_file_list[$type])){
			foreach ($dir_type_folder as $folder) {
				if( $folder['type']==$type){
					// we found a folder of the type we want we will save this path and break our foreach loop
					$path_to_be_scanned = $folder['path'];
					break;
				}
			}
			if(isset($path_to_be_scanned) ){
				// assuming we found an acceptable path we want to scan, remove '.' and '..' from the list, and delete '.pdf' from the file name. Then we will have a good starting point for our list of files
				$iss_file_list[$type] = scandir($path_to_be_scanned);
				$iss_file_list[$type] = array_diff($iss_file_list[$type], array('.', '..'));
				$iss_file_list[$type] = str_ireplace('.pdf', '', $iss_file_list[$type]);
			}
		}
		// by now every type should have a list of lessons and we can proceed to build our form
		if(!empty($iss_file_list[$type])){ 
			foreach( $iss_file_list[$type] as $file ){
				?><li><input type="text" class="regular-text code" name="file-list[<?php echo $type; ?>][]" value="<?php echo $file; ?>" ></li><?php
			}
		}
		?></ul></div><?php
	}
	?></div><input type="submit" name="submit"  class="button button-primary" value="Save Changes"></form></div><?php

	// Now we want to use the $dir_type_folder array we created earlier to search all available folders for files
	?> <div id="files"> <?php
	if(is_array($dir_type_folder)){
		foreach ($dir_type_folder as $value) {
			// scan directory and remove '.' and '..'
			$dir_scan_arr = scandir($value['path']);
			$dir_scan_arr = array_diff($dir_scan_arr, array('.', '..'));
			if(!empty($dir_scan_arr)){
				foreach ($dir_scan_arr as $file) {
					if(is_file($value['path'].'/'.$file)){
						$type = $value['type'];
						$lang = $value['lang'];
						// Search database for file
						$sql = "SELECT * FROM $iss_file_table WHERE type='$type' AND lang='$lang' AND file=".'"'."$file".'"'." ";
						$db_row = $wpdb->get_row($sql, ARRAY_A);
						// if file is not found in database add it to new file array
						if( empty($db_row) ){
							$new_files[] = array(
									'file' => $file,
									'type' => $value['type'],
									'lang' => $value['lang'],
								);
						}
					}
				}
			}

		}
	}

	if(!empty($new_files)){
		// if we have new files check to see if there is already a request to add them to the database
		if($_POST['nFiles']){
			$format = array('%s','%s','%s','%s');
			// add name for file and date file was added to the array
			foreach ($new_files as $key => $file) {
				$new_files[$key]['name'] = mu_iss_match_file_name($new_files[$key]['file'], $new_files[$key]['type']);
				$new_files[$key]['time'] = date('Y-m-d h:i:s');
				$wpdb->insert( $iss_file_table, $new_files[$key], $format );
				$new_files[$key]['insertID'] = $wpdb->insert_id;	
			}
			foreach ($new_files as $nFile) {
				if( !$nFile['insertID'] ){ echo '<div class="error" > Error: '; $message = " was NOT added to the databse!"; }
				else { echo '<div class="updated" >'; $message = " was successfully added to the databse!"; }
				echo " File: ". $nFile['file'];
				echo " Type: ".$nFile['type'];
				echo " Language: ".$nFile['lang']." ".$message."</div>";
			}

		// if there is no request to add them ask
		} else {
			?> <h3>Add New Files</h3>
			<p>The following new files were found in the ISS directory</p>
			<form id="new_files" method="post"><input type="hidden" name="nFiles" value=1 >
			<table>
				<tr><th>File</th><th>Type</th><th>Lang</th> </tr>
				<?php foreach ($new_files as $file) { ?>
					<tr><td><?php echo $file['file'] ?></td><td><?php echo $file['type'] ?></td><td><?php echo $file['lang'] ?></td> </tr>
				<?php } ?>
			</table>
			<p>Add these files to the directory? <input type="submit" name="submit"  class="button button-primary" value="Yes"> </p>
			</form>
			<?php
		}
	}


	// Create table with all the files from the database listed
	$db_file_array = $wpdb->get_results( "SELECT * FROM $iss_file_table ORDER BY type,lang,time,name ", ARRAY_A );

	?> 
	<h3>Files</h3>
	<form id="files" method="post"><input type="hidden" name="oFiles" value=1 >
		<div class="tabs no-border">
			<ul> 
				<?php 
				foreach ($db_file_array as $key => $file) {  
					$FileGroup = $file['type'].'_'.$file['lang'];
					if( $FileGroup != $pFileGroup  ){
					?>
					<li><a href="#<?php echo $FileGroup; ?>"><?php echo ucwords(str_ireplace('_', ' ', $FileGroup)); ?></a></li>
				<?php 
					}
					$pFileGroup = $FileGroup;
				} ?>
			</ul>

		<?php $pFileGroup="";
		foreach ($db_file_array as $key => $file) { 
			$FileGroup = $file['type'].'_'.$file['lang'];
			if( $FileGroup != $pFileGroup  ){
				if(!empty($pFileGroup)){ ?></table><?php }
				?><table id="<?php echo $FileGroup; ?>">
			<tr> <th>Update?</th><th>Name</th><th>File</th><th>Type</th><th>Lang</th><th>Uploaded</th><th>Hits</th></tr>
			<?php
			}
			?>
			<tr> 
				<td><input type="checkbox" name="oFiles-up[<?php echo $key; ?>]" value=1> <input type="hidden" name="oFiles-id[<?php echo $key; ?>]" value="<?php echo $file['id'] ?>" ></td>
				<td><input type="text" name="oFiles-name[<?php echo $key; ?>]" value="<?php echo $file['name'] ?>" class="regular-text"></td>
				<td><?php echo $file['file'] ?></td>
				<td><?php echo $file['type'] ?></td>
				<td><?php echo $file['lang'] ?></td> 
				<td><?php echo $file['time'] ?></td>
				<td><?php echo $file['hits'] ?></td>
			</tr>
		<?php 		
			$pFileGroup = $FileGroup;
		} ?> </table>
	<p><input type="submit" name="submit"  class="button button-primary" value="Save Changes"> </p>
	</form></div>
	<?php

	// end div for div class="wrap" and for div id tabs started at begining of function
	echo "</div></div>";
}

function mu_iss_match_file_name( $file_name, $type ){
	global $iss_file_list;

	if(!empty($iss_file_list[$type])){
		foreach ($iss_file_list[$type] as $name) {
			$cnum = similar_text ( $file_name , $name );
			if( empty($lname) || $cnum > $lnum ){
				$lname = $name;
				$lnum = $cnum;
			}
		}
	}

	return $lname;
}


/**  FUNCTIONS FOR SHOWING ON FRONT PAGE  **/


/** Intialization and action hooks **/

// Always use wp_enqueue_scripts action hook to both enqueue and register scripts
add_action( 'wp_enqueue_scripts', 'mu_enqueue_iss_scripts' );


/** scripts and style functions **/


function mu_enqueue_iss_scripts(){
    // Use `get_stylesheet_directoy_uri() if your script is inside your theme or child theme.
    wp_register_script( 'mu-iss-front-script', plugins_url( '/js/front-page-script.js', __FILE__ ), array('jquery-ui-core','jquery-ui-dialog','jquery-ui-progressbar') );
    wp_register_style( 'mu-iss-front-style', plugins_url( '/css/shortcode.css', __FILE__ ));
    wp_register_style( 'mu-iss-overcast', plugins_url( '/css/overcast/jquery-ui-1.10.4.custom.css', __FILE__ ));

    wp_enqueue_script( 'mu-iss-front-script' );
    wp_enqueue_style( 'mu-iss-overcast' );
    wp_enqueue_style( 'mu-iss-front-style' );
}

/** shortcode functions **/

function mu_iss_shortcode( $atts ){
	global $wpdb;
	global $iss_file_table;
	global $iss_lang;
	global $iss_type;
	global $iss_file_list;
	global $iss_url;
	global $iss_path;
	global $iss_stat_table;

	$sql = "SELECT * FROM $iss_file_table ORDER BY lang, type, name ";
	$file_query_results = $wpdb->get_results( $sql, ARRAY_A );

	$html_dispaly = "";
	// Deal with download request
	if(!empty($_POST['file_ids'])){
		if(empty($_POST['license-agreement'])){
			$html_dispaly .= "<div class='error'>You must agree to the license, before you can download any files.</div>";
		} else {
			$list_ids = $_POST['file_ids'];

			// define a few variables for later use 
			$stat_data['totalfiles']=0;
			$stat_types[$row['type']]=0;
			$stat_langs[$row['lang']]=0;

			if(!empty($list_ids)){
				$full_table = $wpdb->get_results( "SELECT * FROM $iss_file_table ", ARRAY_A );
				if(is_array($full_table)){
					foreach ($full_table as $row) {
						if( in_array($row['id'], $list_ids) ){
							$files_to_download[$row['id']] = array(
								'name' => $row['name'],
								'file' => $row['file'],
								'type' => $row['type'],
								'lang' => $row['lang'],
								);
							$hits = $row['hits']+1;
							$data = array('hits'=>$hits);
							$where = array( 'id'=>$row['id']);
							$wpdb->update( $iss_file_table, $data, $where, '%d' );

							// Information for stat table
							$stat_data['totalfiles']++;
							$stat_types[$row['type']]++;
							$stat_langs[$row['lang']]++;
						}
					}
				} 

				if(!empty($files_to_download)){

						if( $_POST['download_type'] == 'pdf' ){

							$filename = "ISSPDF_".time()."_".rand(100,999).".pdf";
							$full_filename = $iss_path."/downloads"."/".$filename;


							/*$pdf = PDF::API2->new();
							$page->mediabox('Letter');
							$font = $pdf->corefont('Helvetica-Bold');
						    # Add some text to the page
						    $text = $page->text();
						    $text->font($font, 20);
						    $text->translate(200, 700);
						    $text->text('This is a triumph!');

						    # Save the PDF
						    $pdf->saveas($full_filename);

							 intended to be use with pdf merger but doesn't work on encrypted files
							include 'pdfmerge/PDFMerger.php';

							// Create coverpage and back page

							$pdf = new PDFMerger;

							$count=0;
							foreach ($files_to_download as $file) {
							$full_file_name = $iss_path."/".$file['type']."/".$file['lang']."/".$file['file'];
								if(file_exists($full_file_name)){
									$pdf->addPDF($full_file_name,'all');
									$count++;
								}
							} 

							// $pdf->addPDF('samplepdfs/one.pdf', '1, 3, 4')
							//	->addPDF('samplepdfs/two.pdf', '1-2')
							//	->addPDF('samplepdfs/three.pdf', 'all') 

							$pdf->merge('file', $full_filename);  
							*/

							$html_dispaly .= "<div id='files_ready' ><h4>Your file is ready to dowload</h4>";
							$html_dispaly .= "<p>Number of Lessons Requested: " . $stat_data['totalfiles'] . "</p>";
							$html_dispaly .= "<p>Lessons successfully Added: " . $count++ . "</p>";
							$stats = stat($full_filename);
							// information for stat table
							$stat_data['totalsize'] = $stats['size'];
							$html_dispaly .= "<p>Total size of download:" . formatBytes($stats['size']) . "</p>";
							$html_dispaly .= '<p id="file_download_p"><a id="file_download_a" href="'.$iss_url.'/downloads'.'/'.$filename.'" >Click Here To Start Download</a></p></div>' ;

						} else {

							$zip = new ZipArchive();

							$filename = "ISSFolder_".time()."_".rand(100,999).".zip";
							$full_filename = $iss_path."/downloads"."/".$filename;

							if ($zip->open($full_filename, ZipArchive::CREATE)!==TRUE) {
							    exit("cannot open <$full_filename>\n");
							}
							// first add introduction file
							$zip->addFile($iss_path."/Introduction.pdf","/"."ISS_Introduction.pdf");

							foreach ($files_to_download as $file) {
							$full_file_name = $iss_path."/".$file['type']."/".$file['lang']."/".$file['file'];
								if(file_exists($full_file_name)){
									$zip->addFile($full_file_name,"/".$file['type']."/".$file['lang']."/".$file['name'].".pdf");
								}
							} 
							$html_dispaly .= "<div id='files_ready' ><h4>Your files are ready for dowload</h4>";
							$html_dispaly .= "<p>Number of Files: " . $stat_data['totalfiles'] . "</p>";
							$zip->close();
							$stats = stat($full_filename);
							// information for stat table
							$stat_data['totalsize'] = $stats['size'];
							$html_dispaly .= "<p>Total size of download:" . formatBytes($stats['size']) . "</p>";
							$html_dispaly .= '<p id="file_download_p"><a id="file_download_a" href="'.$iss_url.'/downloads'.'/'.$filename.'" >Click Here To Start Download</a></p></div>' ;

						}
						
						// update stat table
						$stat_data['langs']=serialize($stat_langs);
						$stat_data['types']=serialize($stat_types);
						$stat_data['time']=date('Y-m-d H:i:s');
						$stat_data['ipaddress']=$_SERVER['REMOTE_ADDR'];
						$format = array('%d','%d','%s','%s','%s','%s');

						$wpdb->insert( $iss_stat_table, $stat_data, $format );
				}
			} 
		}
	} 

	$html_dispaly .= "<div id='getting_started_wrap' class='hidden'><button id='getting_started_button' >Need Help?</button></div>";
	$html_dispaly .='<div id="getting_started" class="help" ><h3> Getting Started </h3><p>1. Select a series and a language.</p><p>2. Select the lessons you would like to download.</p><p>3. Switch to another series or language and select more files. Your selections are not erased when you switch between series and langauge groups. </p><p>4. Click the "Get Files" button at the end of the page. All you files will be downloaded in one convient zip file.</p></div>';
	// Type Menu
	$html_dispaly .='<div id="type_menu" class="hidden"><ul>';
	if (is_array($iss_type)) {
		ksort($iss_type); 
		foreach ($iss_type as $type => $type_name) {
			$html_dispaly .='<li><h4><a href="" id="'.$type.'" class="type-menu-item">'.$type_name.'</a></h4></li>';
		}
	}
	$html_dispaly .='</ul></div>';

	// lang Menu
	$html_dispaly .='<div id="lang_menu" class="hidden"><ul>';
	if (is_array($iss_lang)) { 
		ksort($iss_lang); 
		foreach ($iss_lang as $lang => $lang_name) {
			$html_dispaly .='<li><h4><a href="" id="'.$lang.'" class="lang-menu-item">'.$lang_name.'</a></h4></li>';
		}
	}
	$html_dispaly .='</ul></div>';

	// File Table
	if(isset($_GET['language'])){
		$html_dispaly .='<div id="file_div" data-lang="'.$_GET['language'].'" >';
	}else {
		$html_dispaly .='<div id="file_div" data-lang="english" >';		
	}
	$html_dispaly .='<form id="retrieve_files" method="post" action="" ><table id="file_table" >';
	if (is_array($file_query_results)) { 
		foreach ($file_query_results as $file) {
			if( $file['type'] != $p_file_type ||  $file['lang'] != $p_file_lang ){
				$html_dispaly .='<tr class="'.$file['type'].' '.$file['lang'].'"><td></td><td class="header_cell name_cell"><h3>'.$iss_type[$file['type']].' in '.$iss_lang[$file['lang']].'</h3></td><td></td></th>';	
				$html_dispaly .='<tr class="'.$file['type'].' '.$file['lang'].' file-item"><td class="check_cell"><input id="select_all_'.$file['type'].'_'.$file['lang'].'" type="checkbox" name="select_all[]" class="select_all" value="1" ></td><td class="name_cell">Select All '.$iss_type[$file['type']].' in '.$iss_lang[$file['lang']].' </td><td class="type_lang_cell"></td></tr>';	
			}
			$html_dispaly .='<tr class="'.$file['type'].' '.$file['lang'].'" ><td class="check_cell"><input type="checkbox" name="file_ids[]" value="'.$file['id'].'" ></td><td class="name_cell">'.$file['name'].'</td><td class="type_lang_cell"><small>'.$file['lang'].' '.$file['type'].'</small></td></tr>';
			$p_file_type = $file['type']; $p_file_lang = $file['lang']; 
		}
	}
	$html_dispaly .= "</table>";
	// $html_dispaly .='<tr><td colspan="3" id="submit_cell" ><input type="submit" value="Get Files" ></td></tr></table></form>';
	
	$html_dispaly .='<div id="license-content">';

	$html_dispaly .= '<h3>TERMS OF USE:</h3>
			<p>The copyright owner has granted permission to each participating church (or individual) to use the “The Invisible Sunday School” for their community outreach or for their own personal use. Any outreach program that the church or individual is serving is an appropriate use with the permission of this copyright owner except for the disclaimers here listed: </p>
			<p>• No changes may be made to the content on any page of this program. </p>
			<p>• The copyright owner of this program does not give anyone permission to use it for monetary pro fit or to place the program on an Internet site. </p>
			<p>• No translations are permitted without the express written approval of the copyright owner. </p>
			<p>• No recordings or any other media may be used with the Invisible Sunday School material without the prior written permission of the copyright owner. </p>
			';

/*	ob_start();
	include "cc/cc_summary.php";
	$html_dispaly .= ob_get_contents();
	ob_end_clean();

	$html_dispaly .= '<div><strong>The above is a summary of the license below but not a substitute. You must agree to the the following license before you can download any files from this site.</strong></div>';

	$html_dispaly .= '<div id="full-license" >';
	ob_start();
	include "cc/cc_lcense.php";
	$html_dispaly .= ob_get_contents();
	ob_end_clean();
	$html_dispaly .= '</div>';

	$html_dispaly .= '<p>For a copy of the above license of for further explanation please <a src="http://creativecommons.org/licenses/by-nc-nd/4.0/" >click here</a>.</p> ';
*/
	$html_dispaly .= '<p id="license-agreement-paragraph" ><input type="checkbox" id="license-agreement" name="license-agreement" value=1  > I have read and agree to use any material recieved from this site within the limitations set forth above.</p>';
	// $html_dispaly .= '<h4>Choose a file type for your download:<p></h4><input type="radio" id="download_type_zip" name="download_type" value="zip" checked="checked" ><label for="download_type_zip">Zip <small>A Zipped folder conataining multiple pdf files.</small></label></p> <p><input type="radio" id="download_type_pdf" name="download_type" value="pdf" ><label for="download_type_pdf">Pdf <small> a single pdf file containing all the lessons you requested.</small></label></p>';

	$html_dispaly .= '</div>';

	$html_dispaly .='<div id="progressbar"></div><input type="submit" value="Get Files" ></form></div>';	

	return $html_dispaly;

}
add_shortcode( 'iss_display', 'mu_iss_shortcode' );


/** User Page Functions **/


/** Donation Page **/


/** General Functions **/

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 

    // Uncomment one of the following alternatives
    $bytes /= pow(1024, $pow);
    // $bytes /= (1 << (10 * $pow)); 

    return round($bytes, $precision) . ' ' . $units[$pow]; 
}