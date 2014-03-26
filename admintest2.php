<?php
/*  T. Shaw  June 2003
This script allows (relatively) easy administration of the UVM links database(s). It's very exciting.
What else is there to say? It adds links, associates names, finds links...deletes categories, all sorts of stuff.
In case it's helpful, the tables are set up as follows:

LINK_URLS
  ID		Primary Key
  ShortName	Useful only for finding entries in this table. NO OTHER USES!
  URL			pretty
  OwnerEmail		self
  Annotation		explanatory

LINK_NAMES
  URL_ID	The ID of the URL associated with a given longname
  LongName	Alternate names for a given URL
  nameindex	key for UVM_NamCatBridge (see below)

LINK_CATEGORIES
  ID		Primary Key
  Category	Category Name

There are two tables that act as go-betweens for the rest, they are 
    LINK_URLNAMEBRIDGE, and LINK_NAMECATBRIDGE.
It is important to note that an entry in the URL table may have multiple LongNames, 
    and each of those is associated with a different Category 
    (possibly multiple categories).

LINK_URLNAMEBRIDGE
  URL_ID	ID from UVM_URLs table
  nameindex	nameindex from UVM_Names

LINK_NAMECATBRIDGE
  catindex	ID from UVM_Categories
  nameindex	nameindex from UVM_Names

The database was designed by Matt Storer. Not me. This script is a replacement for the now dated one he wrote.
  found at  http://cgi.uvm.edu/cgi-bin/webteam/uvmupdate.pl  (for the time being).
*/

/*
The following sql statements were used to correct the absence URL_ID's in the UVM_Names table (they were set to the default, 0, useless)

select * from test_UVM_NAMES where URL_ID = 0;

select test_UVM_URLS.ID from test_UVM_URLs, test_UVM_URLNAMBRIDGE where test_UVM_URLNAMBRIDGE.nameindex = $nameindex AND test_UVM_URLNAMBRIDGE.URL_ID = test_UVM_URLS.ID

update test_UVM_NAMES set URL_ID = $id where test_UVM_NAMES.nameindex = $nameindex;

*/


//note: alter table $URL_TABLE add column Annotation text;
// Add Annotation column to URL table

//if insert is set, use the last_id as $current



// Since no one can use this except us, it's not a security risk to do this:
import_request_variables("gp");

//require_once('/usr/local/uvm-inc/webmaster-mysql.inc');
//include("/usr/local/uvm-inc/gjohnso4.inc");

$user = 'gjohnso4_admin';
$pass = 'autumnal';
$db = mysqli_connect("webdb.uvm.edu",$user,$pass,"GJOHNSO4_azlinks") or die("Could not connect: Bad Password.");


//mysqli_select_db("UVM") or die("Could not select Database: " . mysqli_error());

//Table Names
$URL_TABLE = "LINK_URLS";
$CATEGORY_TABLE = "LINK_CATEGORIES";
$NAME_TABLE = "LINK_NAMES";
$NAME_CAT_TABLE = "LINK_NAMECATBRIDGE";
$NAME_URL_TABLE = "LINK_URLNAMEBRIDGE";

//more constants
$DEFAULT_PRIORITY = 3;

function hidden_elements($form_name, $current)
{
	echo "<input type=\"hidden\" name=\"$form_name\" value=\"1\" >\n";
	echo "<input type=\"hidden\" name=\"current\" value=\"$current\" >\n";
}


/**************** Begin form action functions here  */

//INSERT URL    receives: insert_shortname, insert_url, insert_owneremail
if(isset($insert) ) {
	$check_url_present = "SELECT ID from $URL_TABLE where URL = '".mysqli_real_escape_string($db, $insert_url)."'";
	$check_url_result = mysqli_query($db, $check_url_present);
	if( mysqli_num_rows($check_url_result) === 0 )
	{
		$insert_entry = "INSERT into $URL_TABLE (ShortName, URL, OwnerEmail)
		                 values ('".mysqli_real_escape_string($db, $insert_shortname)."', '".
				            mysqli_real_escape_string($db, $insert_url)."', '".
					    mysqli_real_escape_string($db, $insert_owneremail)."')";
		$insert_entry_result = mysqli_query($db, $insert_entry);

		$latest_entry_result = mysqli_query($db, "select last_insert_id()");
		$latest_entry = mysqli_fetch_array($latest_entry_result);
		$current = $latest_entry[0];
                
                $insert_firstLongName = "INSERT into $NAME_TABLE (URL_ID, LongName) values (". $current .", '".
			    mysqli_real_escape_string($db, $insert_longname1)."')";
	        $insert_firstLongName_result = mysqli_query($db, $insert_firstLongName);
                
	}
	else
	{
		$current = mysqli_fetch_array($check_url_result);
		$current = $current[0];
		$BIG_RED_ERROR_MESSAGE = "URL ALREADY EXISTS!!!<img src=\"angry_face.png\" />";
	}

	$insert_long_assoc = "insert into $NAME_URL_TABLE values (".mysqli_real_escape_string($db, $current).", last_insert_id())";
	mysqli_query($db, $insert_long_assoc);
}

//UPDATE URL    receives: update_id, update_shortname, update_url, update_owneremail
if(isset($update) ) {
	$update_entry = "UPDATE $URL_TABLE SET ".
	                "ShortName = '".mysqli_real_escape_string($db, $update_shortname)."', ".
	                "URL = '".mysqli_real_escape_string($db, $update_url)."', ".
	                "OwnerEmail = '".mysqli_real_escape_string($db, $update_owneremail)."' ".
	                "WHERE ID = ".mysqli_real_escape_string($db, $update_id);
	$update_entry_result = mysqli_query($db, $update_entry);
}

//INSERT LONGNAME   receives: new_long_name, new_long_name_urlid
if(isset($new_name) ) {
	$insert_longname = "INSERT into $NAME_TABLE values (".
	                    mysqli_real_escape_string($db, $new_long_name_urlid).", '".
			    mysqli_real_escape_string($db, $new_long_name)."', NULL)";
	$insert_longname_result = mysqli_query($db, $insert_longname);

	$insert_long_assoc = "insert into $NAME_URL_TABLE values (".mysqli_real_escape_string($db, $current).", last_insert_id())";
	mysqli_query($db, $insert_long_assoc);
}

//INSERT CATEGORY    receives: cat_name
if(isset($new_cat) ) {
	$insert_category = "INSERT into $CATEGORY_TABLE values (NULL, '".mysqli_real_escape_string($db, $cat_name)."')";
	$insert_category_result = mysqli_query($db, $insert_category);
}

//ASSOCIATE CATEGORY     receives: ass_cat, ass_name
if(isset($add_ass) ) {
	$insert_association = "INSERT into $NAME_CAT_TABLE values (".mysqli_real_escape_string($db, $ass_cat).", ".mysqli_real_escape_string($db, $ass_name).")";
	$assoc_result = mysqli_query($db, $insert_association);
}

//DISASSOCIATE CATEGORY    receives: del_ass_id 
if(isset($del_ass) ) {
	$disassociate = explode("*", $del_ass_id);
	$remove_association = "delete from $NAME_CAT_TABLE where catindex = ".mysqli_real_escape_string($db, $disassociate[0])." AND nameindex = ".mysqli_real_escape_string($db, $disassociate[1]);
	$disassoc_result = mysqli_query($db, $remove_association);
}

//DELETE LONG NAME     receives: del_longname_id
if(isset($del_long)) {
	$remove_longname = "delete from $NAME_TABLE where nameindex = ".mysqli_real_escape_string($db, $del_longname_id);
	mysqli_query($db, $remove_longname);

	$remove_long_assoc = "delete from $NAME_URL_TABLE where nameindex = ".mysqli_real_escape_string($db, $del_longname_id);
	mysqli_query($db, $remove_long_assoc);

	$remove_nam_cats = "delete from $NAME_CAT_TABLE where nameindex = ".mysqli_real_escape_string($db, $del_longname_id);
	mysqli_query($db, $remove_nam_cats);
}

//DELETE CATEGORY     receives: del_cat_id
if(isset($del_cat) ) {
	$remove_category = "delete from $CATEGORY_TABLE where ID = ".mysqli_real_escape_string($db, $del_cat_id);
	$remove_category_result = mysqli_query($db, $remove_category);

	$remove_associations = "delete * from $NAME_CAT_TABLE where catindex = ".mysqli_real_escape_string($db, $del_cat_id);
	mysqli_query($db, $remove_associations);
}

//DELETE URL        receives: del_url_id
if(isset($del_url) ) {
	$remove_url = "delete from $URL_TABLE where ID = ".mysqli_real_escape_string($db, $del_url_id);
	$remove_url_result = mysqli_query($db, $remove_url);

	//get all the rows that will be deleted, then axe the associations. I think there was a neat query to do this without a loop, except it wasn't neat. Not at all.
	$select_longnames = "select nameindex from $NAME_TABLE where URL_ID = ".mysqli_real_escape_string($db, $del_url_id);
	$longname_list = mysqli_query($db, $select_longnames);

	while(list($name) = mysqli_fetch_row($longname_list) ) {
		$remove_name_cat = "delete from $NAME_CAT_TABLE where nameindex = $name";
		$result_rmv_nc = mysqli_query($db, $remove_name_cat);
		$remove_name_url = "delete from $NAME_URL_TABLE where nameindex = $name";
		$result_rmv_nu = mysqli_query($db, $remove_name_url);
	}

	$remove_longnames = "delete from $NAME_TABLE where URL_ID = ".mysqli_real_escape_string($db, $del_url_id);
	$result_rmv_ln = mysqli_query($db, $remove_longnames);
}

//RENAME CATEGORY	receives:update_cat_id, update_cat_name
if(isset($update_cat) ) {
	$update_cat = "update $CATEGORY_TABLE set Category = '".mysqli_real_escape_string($db, $update_cat_name)."' where ID = ".mysqli_real_escape_string($db, $update_cat_id);
	mysqli_query($db, $update_cat);
}

//RENAME LONG NAME	receives: ren_long_name, ren_new_name;
if(isset($ren_name) ) {
	$update_long_name = "update $NAME_TABLE set LongName = '".mysqli_real_escape_string($db, $ren_new_name)."' where nameindex=".mysqli_real_escape_string($db, $ren_long_name);
	mysqli_query($db, $update_long_name);
}
/************* find ***************** */
//$current gets set in here, unless it gets set somewhere else. (like up there in insert URL).

//FIND     receives: find_item, find_type
if(isset($find) ) {
	if($find_type == "longname") {
		$find_sql = "select $URL_TABLE.ID from $NAME_TABLE, $URL_TABLE, $NAME_URL_TABLE WHERE LongName like '%".mysqli_real_escape_string($db, $find_item)."%' AND $NAME_TABLE.nameindex = $NAME_URL_TABLE.nameindex AND $NAME_URL_TABLE.URL_ID = $URL_TABLE.ID ORDER BY ID  ASC limit 1";
		$find_result = mysqli_query($db, $find_sql);
		$current = mysqli_fetch_array($find_result);

		$current = $current[0];
	}

	if($find_type == "shortname") {
		$find_sql = "select * from $URL_TABLE where ShortName like '%".mysqli_real_escape_string($db, $find_item)."%' ORDER BY ShortName";
		$find_result = mysqli_query($db, $find_sql);
		$current = mysqli_fetch_array($find_result);
		$current = $current[0];
	}

	if($find_type == "id") {
		$find_sql = "select * from $URL_TABLE where ID = ".mysqli_real_escape_string($db, $find_item)." ORDER BY ID";
		$find_result = mysqli_query($db, $find_sql);
		$current = mysqli_fetch_array($find_result);
		$current = $current[0];
	}

	if($find_type == "email") {
		$find_sql = "select * from $URL_TABLE where OwnerEmail = '".mysqli_real_escape_string($db, $find_item)."' ORDER BY OwnerEmail";
		$find_result = mysqli_query($db, $find_sql);
		$current = mysqli_fetch_array($find_result);
		$current = $current[0];		
	}

	if($find_type == "url") {
		$find_sql = "select * from $URL_TABLE where URL like '".mysqli_real_escape_string($db, $find_item)."%' ORDER BY URL";
		$find_result = mysqli_query($db, $find_sql);
		$current = mysqli_fetch_array($find_result);

		$current = $current[0];
	}

	if($find_type == "down") {
		$down = "select ID from $URL_TABLE where ID < $current order by ID desc limit 1";
		$find_result = mysqli_query($db, $down);
		$current = mysqli_fetch_array($find_result);
		$current = $current[0];
		if($current < 1)  //maybe this should test mysqli_error.... seems to work though
			$current = 1;
	}

	if($find_type == "up") {
		$up = "select ID from $URL_TABLE where ID > $current order by ID asc limit 1";
		$find_result = mysqli_query($db, $up);
		$current = mysqli_fetch_array($find_result);
		$current = $current[0];
	}

	if(mysqli_num_rows($find_result) == '')
	{
		$none_found = "No records found.";
	}

} 


//For the Search Selector. This could probably bypass some queries, but that's for another day.
if(isset($found) ) {
	$current = $found;
}

//catchall, so there aren't any bad messups... mysqli errors happening on screen, etc
$select_highest = "select MAX(ID) from $URL_TABLE";
$result_highest = mysqli_query($db, $select_highest);
$highest = mysqli_fetch_array($result_highest);
$highest = $highest[0];	

if(!isset($current) || $current == "" ) {
	$current = $highest;	
}



/****************** Done with actions *****************




/******** defaults ********* 
	(form contents)
*/

$select_default_entry = "select * from $URL_TABLE where ID = '$current'";
$result_default_entry = mysqli_query($db, $select_default_entry);
$default_entry = mysqli_fetch_array($result_default_entry);

$select_default_longnames = "select $NAME_TABLE.nameindex, $NAME_TABLE.LongName from $NAME_TABLE, $NAME_URL_TABLE WHERE $NAME_URL_TABLE.URL_ID = $current AND $NAME_URL_TABLE.nameindex = $NAME_TABLE.nameindex";
$result_default_longnames = mysqli_query($db, $select_default_longnames);




/* What follows is a nested table mess. 3 columns, 2 rows they are :

	find 		| update 	| insert
	(select results)| add stuff	| delete stuff

*/

$selection = "";
$selection = $_POST["decide"];

?>
<html>

<head>
  <title>Link Updater</title>


<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="desktop.css" />

<script src="jquery-2.1.0.min.js">
</script>
<script>
$(document).ready(function(){
    $("#add").hide();
    $("#edit").hide();
    $("#delete").hide();
  
  $("#addButton").click(function(){
        $("#add").show();
        $("#edit").hide();
        $("#delete").hide();
    });
    
  $("#editButton").click(function(){
        $("#edit").show();
        $("#add").hide();
        $("#delete").hide();
    }); 
    
  $("#deleteButton").click(function(){
        $("#delete").show();
        $("#add").hide();
        $("#edit").hide();
    });
    
  $('button[type="submit"]').click(function(){
        //add functions to keep form up after button clicks
    
    });
});
</script>


</head>

<body>
    <br />

    <div id="decision">
            <fieldset>
                <legend>I Want To...</legend>
                <button id="addButton">Add a New Link or Category</button><br />
                <button id="editButton">Manage an Existing Link and its Associations</button><br />
                <button id="deleteButton">Delete an Existing Link</button><br />
            </fieldset>
    </div>
    <br />

    <div id="edit"> 
    <h1><? echo $BIG_RED_ERROR_MESSAGE ?></h1>
<h2><a href="admin_help.html" target="_blank">Help!</a></h2>

<table width="100%" border="1">

<tr>
  <td>
	<h3>Find</h3>
		<form name="frm_find" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Find <input type="text" name="find_item" ></label>
			<fieldset>
				<legend>Find What?</legend>
				<label><input type="radio" name="find_type" value="longname" checked>LongName (case sensitive)</label><br />
				<label><input type="radio" name="find_type" value="shortname" >ShortName</label><br />
				<label><input type="radio" name="find_type" value="id" >ID</label><br />
				<label><input type="radio" name="find_type" value="url">URL</label></br>
				<label><input type="radio" name="find_type" value="email">Email</label><br />
			</fieldset>
			<? 
				hidden_elements('find', $current);
			?>
			<input type="submit" name="findForm" value="Find">
		</form>

  </td>
  <td valign="top">

<?
if(!isset($default_entry['URL']) )
{
	echo "<h6>No entries found</h6>";
}
else if(isset($find) ) {
?>
	<h3>Select</h3>
		<form name="frm_select" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<select name="found" size="5">
			<?

			if($find_type == "shortname") {
				$find_sql = "select * from $URL_TABLE where ShortName like '%".mysqli_real_escape_string($db, $find_item)."%' ORDER BY ShortName ASC";
				$result_sql = mysqli_query($db, $find_sql);

				while(list($ID, $ShortName, $URL, $OwnerEmail) = mysqli_fetch_row($result_sql) ) {
					echo "<option value=\"$ID\" >$ShortName</option>";
				}
			}

			if($find_type == "longname") {
				$find_sql = "select $URL_TABLE.ID, $NAME_TABLE.LongName from $NAME_TABLE, $URL_TABLE, $NAME_URL_TABLE WHERE LongName like '%".mysqli_real_escape_string($db, $find_item)."%' AND $NAME_TABLE.nameindex = $NAME_URL_TABLE.nameindex AND $NAME_URL_TABLE.URL_ID = $URL_TABLE.ID ORDER BY ID ASC";
				$result_sql = mysqli_query($db, $find_sql);		

				while(list($ID, $LongName) = mysqli_fetch_row($result_sql) ) {
					echo "<option value=\"$ID\" >$LongName</option>";
				}
			}

			if($find_type == "email") {
				$find_sql = "select * from $URL_TABLE where OwnerEmail = '".mysqli_real_escape_string($db, $find_item)."' ORDER BY OwnerEmail ASC";
				$result_sql = mysqli_query($db, $find_sql);

				while(list($ID, $ShortName, $URL, $OwnerEmail) = mysqli_fetch_row($result_sql) ) {
					echo "<option value=\"$ID\" >$ShortName</option>";
				}
			}

			if($find_type == "url") {
				$find_sql = "select * from $URL_TABLE where URL like '".mysqli_real_escape_string($db, $find_item)."%' ORDER BY URL ASC";
				$result_sql = mysqli_query($db, $find_sql);

				while(list($ID, $ShortName, $URL, $OwnerEmail) = mysqli_fetch_row($result_sql) ) {
					echo "<option value=\"$ID\" >$ShortName</option>";
				}
			}
 
			?>
			</select>

			<?
				hidden_elements('find', $current);
			?>
			<input type="hidden" name="find_item" value="<? echo $find_item ?>" >
			<input type="hidden" name="find_type" value="<? echo $find_type ?>" >
				<br />
			<input type="submit" value="Select" >
<?
}  //end if($find)
echo "<h3>$none_found</h3>";

?>

		</form>		
  </td>  
  <td>
      <table> 
	<th>Browse</th>
	 <tr>	
	  <td>	
            <form name="frm_down" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
            <input type="hidden" name="find_type" value="down" >
            <? hidden_elements('find', $current); ?>
            <input type="submit" value="<" >
            </form>
        </td>
        <td>
            <form name="frm_down" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
            <input type="hidden" name="find_type" value="up" >
            <? hidden_elements('find', $current); ?>
            <input type="submit" value=">" >
            </form>
                </td>
        </tr>
      </table>
  </td>
</tr>
</table>


<h3>Current URL: <? echo $default_entry['ShortName']  ?> : <? echo $default_entry['URL'] ?>
<?
if($current == $highest)
	echo '<div align="right" id="red">LAST RECORD</div>';
?>
</h3>

<div class="columns">
<div class="row">  
<div class="column1">
        <h3 id="yellow">Update Current URL</h3>
		<form name="frm_update" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Short Name<br /><input type="text" name="update_shortname" value="<? echo $default_entry['ShortName']  ?>" class="input-text"></label><?  if($current == $highest) { echo '<span> - LAST RECORD</span>'; } ?>

				<br />
			<label>URL [ <a href="<? echo $default_entry['URL'] ?>" target="_blank" > visit  </a> ] <br /><input type="text" name="update_url" value="<? echo $default_entry['URL']  ?>" class="input-text"></label>
				<br />
			<label>Owner Email<br /><input type="text" name="update_owneremail" value="<? echo $default_entry['OwnerEmail']  ?>" class="input-text"></label>
				<br />
			<?
				hidden_elements('update', $current);
			?>
			<input type="hidden" name="update_id" value="<? echo $default_entry['ID'] ?>" >
			<br />
                        <input type="submit" value="Update" class="input-button">
		</form>

		<form name="frm_del_url" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<input type="hidden" name="del_url_id" value="<?  echo $current  ?>" >
			<?
				hidden_elements('del_url', "");
			?>
			<input type="submit" value="DELETE" class="input-button">
		</form>
  </div>
    
    <div class="column1" valign="top">
	<h3 id="yellow">New Long Name</h3>
		<form name="frm_new_long" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>New Long Name (To be associated with the current URL)
				<br /> 
			<input type="text" name="new_long_name" value="" class="input-text"></label>
			<input type="hidden" name="new_long_name_urlid" value="<? echo $current ?>" >
			<?
				hidden_elements('new_name', $current);
			?>
				<br />
				<br />
			<input type="submit" value="Create" class="input-button">
                </form>
    </div>
</div>
    <div class ="row">
        <div class="column1">
	<h3 id="yellow">Rename Long Name</h3>
		<form name="frm_ren_long" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Long Name: 
					<br />
				<select name="ren_long_name" class="input-select">
				<?
					$select_default_longnames = "select nameindex, LongName from $NAME_TABLE where URL_ID = $current";
					$result_default_longnames = mysqli_query($db, $select_default_longnames);

					while(list($nameindex, $name) = mysqli_fetch_row($result_default_longnames) ) {
						echo "<option value=\"$nameindex\">$name</option>\n";
					}

				?>
				</select>
			</label>
				<br />
			<label>New Long Name:<br /><input type="text" name="ren_new_name" value="" class="input-text"></label>
				<br />
			<?
				hidden_elements('ren_name', $current);
			?>
				<br />
			<input type="submit" value="Rename" class="input-button">			
		</form>
        </div>
        
        <div class="column1"> 
        <h3 id="yellow">Add Association</h3>
		<form name="frm_ass" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Long Name: 
					<br />
				<select name="ass_name" class="input-select">
				<?
					$select_default_longnames = "select nameindex, LongName from $NAME_TABLE where URL_ID = $current";
					$result_default_longnames = mysqli_query($db, $select_default_longnames);

					while(list($nameindex, $name) = mysqli_fetch_row($result_default_longnames) ) {
						echo "<option value=\"$nameindex\">$name</option>\n";
					}

				?>
				</select>
			</label>
				<br />
			<label>Category: 
				<br />
				<select name="ass_cat" class="input-select">
				<?
					$select_all_categories = "select distinct $CATEGORY_TABLE.ID, $CATEGORY_TABLE.Category from $CATEGORY_TABLE ORDER BY Category";
					$result_all_categories = mysqli_query($db, $select_all_categories);

					while(list($ID, $Category) = mysqli_fetch_row($result_all_categories) ) {
						echo "<option value=\"$ID\"> $Category</option>\n";
					}
				?>
				</select>
			</label>
				<br />
			<?
				hidden_elements('add_ass', $current);
			?>
				<br />
			<input type="submit" value="Associate" class="input-button">
		</form>
            </div>
    </div>
        
        <div class="row">
        <div class="column1" valign="top">
	<h3 id="yellow">Remove Association</h3>
		<form name="frm_dis" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Category -> Long Name
				<br />
			<select name="del_ass_id" size="5"  class="input-select">
			<?
				//Gets a list of long names, finds the associated categories with each long name

				$select_default_longnames = "select nameindex, LongName from $NAME_TABLE where URL_ID = $current";

				$result_default_longnames = mysqli_query($db, $select_default_longnames);


				while( list($nameindex, $LongName) = mysqli_fetch_row($result_default_longnames) ) {
					$select_default_longcat = "select $CATEGORY_TABLE.ID, $CATEGORY_TABLE.Category from $CATEGORY_TABLE, $NAME_CAT_TABLE WHERE $NAME_CAT_TABLE.nameindex = $nameindex AND $CATEGORY_TABLE.ID = $NAME_CAT_TABLE.catindex";
					$result_default_longcat = mysqli_query($db, $select_default_longcat);

					while( list($ID, $Category) = mysqli_fetch_row($result_default_longcat) ) {
						echo "<option value=\"$ID*$nameindex\" >$Category -> $LongName</option>\n";
					}
				}

			?>
			</select>	
			</label>
				<br />
			<?
				hidden_elements('del_ass', $current);
			?>
				<br />
			<input type="submit" value="Remove" class="input-button">
		</form>
        </div>
        <div class="column1">
        <h3 id="yellow">Rename Category</h3>
		<form name="frm_update_cat" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Category: 
				<br />
			<select name="update_cat_id" class="input-select">
			<?
				$select_all_categories = "select distinct $CATEGORY_TABLE.ID, $CATEGORY_TABLE.Category from $CATEGORY_TABLE ORDER BY Category";
				$result_all_categories = mysqli_query($db, $select_all_categories);

				// A-Z is protected... wouldn't want to accidentally delete it.
				while(list($ID, $Category) = mysqli_fetch_row($result_all_categories) ) {
					if($Category != "A-Z")  
						echo "<option value=\"$ID\"> $Category</option>\n";
				}

			?>
			</select>
			</label>

			<br />

			<label>New Name:
                            <br /><input type="text" name="update_cat_name" class="input-text"></label> 
			<?
				hidden_elements('update_cat', $current);
			?>
				<br />
                                <br />
			<input type="submit" value="Rename" class="input-button">
		</form>
        </div>
    </div>
</div>
</div>
      
    <div id="add">
    <div class="columns">    
    <div class="row">
        <div class="column1">
	<h3 id="green">Insert New URL</h3>
		<form name="frm_insert" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post">

			<label>Short Name<br /><input type="text" name="insert_shortname" value="" ></label>
				<br />
                        <label>Long Name<br /><input type="test" name="insert_longname1" value="" ></label>
                                <br />
			<label>URL<br /><input type="text" name="insert_url" value="" ></label>
				<br />
			<label>Owner Email<br /><input type="text" name="insert_owneremail" value="" ></label>
				<br />
			<?
				hidden_elements('insert', $current);
			?>
			<br />
                        <input id="insertNew" type="submit" value="Insert" >
		</form>
        </div>   
        <div class="column1">
	<h3 id="green">New Category</h3>
		<h6>Use the / (forward slash) character in place of spaces!</h6>
		<form name="frm_new_cat" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>New Category
				<br />
			<input type="text" name="cat_name" size="64" class="input-text"></label>
			<?
				hidden_elements('new_cat', $current);
			?>
				<br />
                                <br />
			<input id="newCat" type="submit" value="Create" class="input-button">
		</form>
         </div>
    </div>
    </div>
</div>
                
    <div id="delete">
    <div class="columns">
        <div class="row">
            <div class="column1">
	<h3 id="red">Delete Long Name</h3>
		<form name="frm_del_longname" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Long Name: 
				<br />
			<select name="del_longname_id" class="input-select">
			<?
				$select_default_longnames = "select nameindex, LongName from $NAME_TABLE where URL_ID = $current";
				$result_default_longnames = mysqli_query($db, $select_default_longnames);

				while(list($nameindex, $name) = mysqli_fetch_row($result_default_longnames) ) {
					echo "<option value=\"$nameindex\"> $name</option>\n";
				}
			?>
			</select>
                        </label>
			<?
				hidden_elements('del_long', $current);
			?>
				<br />
				<br />
			<input type="submit" value="Delete" class="input-button">
		</form>

            </div>
            <div class="column1">
	
        <h3 id="red">Delete Category</h3>
		<h6>Note: all Longname-Category associations for this category will be erased. </h6>

		<form name="frm_del_cat" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Category: 
				<br />
			<select name="del_cat_id" class="input-select">
			<?
				$select_all_categories = "select distinct $CATEGORY_TABLE.ID, $CATEGORY_TABLE.Category from $CATEGORY_TABLE ORDER BY Category";
				$result_all_categories = mysqli_query($db, $select_all_categories);

				// A-Z is protected... wouldn't want to accidentally delete it.
				while(list($ID, $Category) = mysqli_fetch_row($result_all_categories) ) {
					if($Category != "A-Z")  
						echo "<option value=\"$ID\"> $Category</option>\n";
				}

			?>
			</select>
			</label>
			<?
				hidden_elements('del_cat', $current);
			?>
				<br />
                                <br />
			<input type="submit" value="Delete" class="input-button">
		</form>

</div>
</div>
</div>
    </div>

</body>

</html>
