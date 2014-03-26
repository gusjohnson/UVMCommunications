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

require_once('/usr/local/uvm-inc/webmaster-mysql.inc');

$user = 'webmaster';
$pass = getwebmasterpassword($user);
$db = mysql_pconnect("webdb.uvm.edu",$user,$pass) or die("Could not connect: Bad Password.");


mysql_select_db("UVM") or die("Could not select Database: " . mysql_error());

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

//INSERT URL    receives: insert_shortname, insert_url, insert_owneremail, insert_annotation, insert_priority
if(isset($insert) ) {
	$check_url_present = "SELECT ID from $URL_TABLE where URL = '".mysql_real_escape_string($insert_url)."'";
	$check_url_result = mysql_query($check_url_present, $db);
	if( mysql_num_rows($check_url_result) === 0 )
	{
		$insert_entry = "INSERT into $URL_TABLE (ShortName, URL, OwnerEmail, Annotation, Priority)
		                 values ('".mysql_real_escape_string($insert_shortname)."', '".
				            mysql_real_escape_string($insert_url)."', '".
					    mysql_real_escape_string($insert_owneremail)."', '".
					    mysql_real_escape_string($insert_annotation)."', ".
					    mysql_real_escape_string($insert_priority).")";
		$insert_entry_result = mysql_query($insert_entry, $db);

		$latest_entry_result = mysql_query("select last_insert_id()", $db);
		$latest_entry = mysql_fetch_array($latest_entry_result);
		$current = $latest_entry[0];
	}
	else
	{
		$current = mysql_fetch_array($check_url_result);
		$current = $current[0];
		$BIG_RED_ERROR_MESSAGE = "URL ALREADY EXISTS!!!<img src=\"angry_face.png\" />";
	}
}

//UPDATE URL    receives: update_id, update_shortname, update_url, update_owneremail, update_annotation, update_priority
if(isset($update) ) {
	$update_entry = "UPDATE $URL_TABLE SET ".
	                "ShortName = '".mysql_real_escape_string($update_shortname)."', ".
	                "URL = '".mysql_real_escape_string($update_url)."', ".
	                "OwnerEmail = '".mysql_real_escape_string($update_owneremail)."', ".
	                "Annotation = '".mysql_real_escape_string($update_annotation)."', ".
	                "Priority = ".mysql_real_escape_string($update_priority)." ".
	                "WHERE ID = ".mysql_real_escape_string($update_id);
	$update_entry_result = mysql_query($update_entry, $db);
}

//INSERT LONGNAME   receives: new_long_name, new_long_name_urlid
if(isset($new_name) ) {
	$insert_longname = "INSERT into $NAME_TABLE values (".
	                    mysql_real_escape_string($new_long_name_urlid).", '".
			    mysql_real_escape_string($new_long_name)."', NULL)";
	$insert_longname_result = mysql_query($insert_longname, $db);

	$insert_long_assoc = "insert into $NAME_URL_TABLE values (".mysql_real_escape_string($current).", last_insert_id())";
	mysql_query($insert_long_assoc, $db);
}

//INSERT CATEGORY    receives: cat_name
if(isset($new_cat) ) {
	$insert_category = "INSERT into $CATEGORY_TABLE values (NULL, '".mysql_real_escape_string($cat_name)."')";
	$insert_category_result = mysql_query($insert_category, $db);
}

//ASSOCIATE CATEGORY     receives: ass_cat, ass_name
if(isset($add_ass) ) {
	$insert_association = "INSERT into $NAME_CAT_TABLE values (".mysql_real_escape_string($ass_cat).", ".mysql_real_escape_string($ass_name).")";
	$assoc_result = mysql_query($insert_association, $db);
}

//DISASSOCIATE CATEGORY    receives: del_ass_id 
if(isset($del_ass) ) {
	$disassociate = explode("*", $del_ass_id);
	$remove_association = "delete from $NAME_CAT_TABLE where catindex = ".mysql_real_escape_string($disassociate[0])." AND nameindex = ".mysql_real_escape_string($disassociate[1]);
	$disassoc_result = mysql_query($remove_association, $db);
}

//DELETE LONG NAME     receives: del_longname_id
if(isset($del_long)) {
	$remove_longname = "delete from $NAME_TABLE where nameindex = ".mysql_real_escape_string($del_longname_id);
	mysql_query($remove_longname, $db);

	$remove_long_assoc = "delete from $NAME_URL_TABLE where nameindex = ".mysql_real_escape_string($del_longname_id);
	mysql_query($remove_long_assoc, $db);

	$remove_nam_cats = "delete from $NAME_CAT_TABLE where nameindex = ".mysql_real_escape_string($del_longname_id);
	mysql_query($remove_nam_cats, $db);
}

//DELETE CATEGORY     receives: del_cat_id
if(isset($del_cat) ) {
	$remove_category = "delete from $CATEGORY_TABLE where ID = ".mysql_real_escape_string($del_cat_id);
	$remove_category_result = mysql_query($remove_category, $db);

	$remove_associations = "delete * from $NAME_CAT_TABLE where catindex = ".mysql_real_escape_string($del_cat_id);
	mysql_query($remove_associations, $db);
}

//DELETE URL        receives: del_url_id
if(isset($del_url) ) {
	$remove_url = "delete from $URL_TABLE where ID = ".mysql_real_escape_string($del_url_id);
	$remove_url_result = mysql_query($remove_url, $db);

	//get all the rows that will be deleted, then axe the associations. I think there was a neat query to do this without a loop, except it wasn't neat. Not at all.
	$select_longnames = "select nameindex from $NAME_TABLE where URL_ID = ".mysql_real_escape_string($del_url_id);
	$longname_list = mysql_query($select_longnames, $db);

	while(list($name) = mysql_fetch_row($longname_list) ) {
		$remove_name_cat = "delete from $NAME_CAT_TABLE where nameindex = $name";
		$result_rmv_nc = mysql_query($remove_name_cat, $db);
		$remove_name_url = "delete from $NAME_URL_TABLE where nameindex = $name";
		$result_rmv_nu = mysql_query($remove_name_url, $db);
	}

	$remove_longnames = "delete from $NAME_TABLE where URL_ID = ".mysql_real_escape_string($del_url_id);
	$result_rmv_ln = mysql_query($remove_longnames, $db);
}

//RENAME CATEGORY	receives:update_cat_id, update_cat_name
if(isset($update_cat) ) {
	$update_cat = "update $CATEGORY_TABLE set Category = '".mysql_real_escape_string($update_cat_name)."' where ID = ".mysql_real_escape_string($update_cat_id);
	mysql_query($update_cat, $db);
}

//RENAME LONG NAME	receives: ren_long_name, ren_new_name;
if(isset($ren_name) ) {
	$update_long_name = "update $NAME_TABLE set LongName = '".mysql_real_escape_string($ren_new_name)."' where nameindex=".mysql_real_escape_string($ren_long_name);
	mysql_query($update_long_name, $db);
}
/************* find ***************** */
//$current gets set in here, unless it gets set somewhere else. (like up there in insert URL).

//FIND     receives: find_item, find_type
if(isset($find) ) {
	if($find_type == "longname") {
		$find_sql = "select $URL_TABLE.ID from $NAME_TABLE, $URL_TABLE, $NAME_URL_TABLE WHERE LongName like '%".mysql_real_escape_string($find_item)."%' AND $NAME_TABLE.nameindex = $NAME_URL_TABLE.nameindex AND $NAME_URL_TABLE.URL_ID = $URL_TABLE.ID ORDER BY ID  ASC limit 1";
		$find_result = mysql_query($find_sql, $db);
		$current = mysql_fetch_array($find_result);

		$current = $current[0];
	}

	if($find_type == "shortname") {
		$find_sql = "select * from $URL_TABLE where ShortName like '%".mysql_real_escape_string($find_item)."%' ORDER BY ShortName";
		$find_result = mysql_query($find_sql, $db);
		$current = mysql_fetch_array($find_result);
		$current = $current[0];
	}

	if($find_type == "id") {
		$find_sql = "select * from $URL_TABLE where ID = ".mysql_real_escape_string($find_item)." ORDER BY ID";
		$find_result = mysql_query($find_sql, $db);
		$current = mysql_fetch_array($find_result);
		$current = $current[0];
	}

	if($find_type == "email") {
		$find_sql = "select * from $URL_TABLE where OwnerEmail = '".mysql_real_escape_string($find_item)."' ORDER BY OwnerEmail";
		$find_result = mysql_query($find_sql, $db);
		$current = mysql_fetch_array($find_result);
		$current = $current[0];		
	}

	if($find_type == "url") {
		$find_sql = "select * from $URL_TABLE where URL like '".mysql_real_escape_string($find_item)."%' ORDER BY URL";
		$find_result = mysql_query($find_sql);
		$current = mysql_fetch_array($find_result);

		$current = $current[0];
	}

	if($find_type == "down") {
		$down = "select ID from $URL_TABLE where ID < $current order by ID desc limit 1";
		$find_result = mysql_query($down, $db);
		$current = mysql_fetch_array($find_result);
		$current = $current[0];
		if($current < 1)  //maybe this should test mysql_error.... seems to work though
			$current = 1;
	}

	if($find_type == "up") {
		$up = "select ID from $URL_TABLE where ID > $current order by ID asc limit 1";
		$find_result = mysql_query($up, $db);
		$current = mysql_fetch_array($find_result);
		$current = $current[0];
	}

	if(mysql_num_rows($find_result) == '')
	{
		$none_found = "No records found.";
	}

} 


//For the Search Selector. This could probably bypass some queries, but that's for another day.
if(isset($found) ) {
	$current = $found;
}

//catchall, so there aren't any bad messups... mysql errors happening on screen, etc
$select_highest = "select MAX(ID) from $URL_TABLE";
$result_highest = mysql_query($select_highest, $db);
$highest = mysql_fetch_array($result_highest);
$highest = $highest[0];	

if(!isset($current) || $current == "" ) {
	$current = $highest;	
}



/****************** Done with actions *****************




/******** defaults ********* 
	(form contents)
*/

$select_default_entry = "select * from $URL_TABLE where ID = '$current'";
$result_default_entry = mysql_query($select_default_entry, $db);
$default_entry = mysql_fetch_array($result_default_entry);

$select_default_longnames = "select $NAME_TABLE.nameindex, $NAME_TABLE.LongName from $NAME_TABLE, $NAME_URL_TABLE WHERE $NAME_URL_TABLE.URL_ID = $current AND $NAME_URL_TABLE.nameindex = $NAME_TABLE.nameindex";
$result_default_longnames = mysql_query($select_default_longnames, $db);




/* What follows is a nested table mess. 3 columns, 2 rows they are :

	find 		| update 	| insert
	(select results)| add stuff	| delete stuff

*/
?>
<html>

<head>
  <title>Link Updater</title>

<style type="text/css">
h3 { font-size: 12pt; }
h1 { color: red; }
label { font-size: 10pt; }
#border { border-bottom: groove black; border-left: groove black; }
#right { border-left: groove black; }
#red { color: red; background-color: silver; }
#yellow { color: yellow; background-color: gray; }
#green { color: green; background-color: silver; }

</style>

</head>

<body>
<h1><? echo $BIG_RED_ERROR_MESSAGE ?></h1>
<h3>Current URL: <? echo $default_entry['ShortName']  ?> : <? echo $default_entry['URL'] ?>
<?
if($current == $highest)
	echo '<div align="right" id="red">LAST RECORD</div>';
?>
</h3>

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
			<input type="submit" value="Find">
		</form>

		<table> 
			<th>Browse</th>
			<tr>	
				<td>	
					<form name="frm_down" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
					<input type="hidden" name="find_type" value="down" >
					<? hidden_elements('find', $current); ?>
					<input type="submit" value="<" >
					</form>
				</td><td>
					<form name="frm_down" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
					<input type="hidden" name="find_type" value="up" >
					<? hidden_elements('find', $current); ?>
					<input type="submit" value=">" >
					</form>
				</td>
			</tr>
		</table>

  </td>
  <td>
	<h3 id="yellow">Update Current URL</h3>
		<form name="frm_update" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Short Name<br /><input type="text" name="update_shortname" value="<? echo $default_entry['ShortName']  ?>" ></label><?  if($current == $highest) { echo '<span> - LAST RECORD</span>'; } ?>

				<br />
			<label>URL [ <a href="<? echo $default_entry['URL'] ?>" target="_blank" > visit  </a> ] <br /><input type="text" name="update_url" value="<? echo $default_entry['URL']  ?>" ></label>
				<br />
			<label>Owner Email<br /><input type="text" name="update_owneremail" value="<? echo $default_entry['OwnerEmail']  ?>" ></label>
				<br />
			<label>Annotation
				<br />
				<textarea name="update_annotation" cols="30" rows="7" ><? echo $default_entry['Annotation']  ?></textarea>
			</label>
				<br />
			<label>Priority
				<select name="update_priority">
				<?
					for($i=1; $i <= 5; $i++) {
						echo "<option value=\"$i\"";
						echo $i == $default_entry['Priority'] ? 'selected' : '';
						echo " >$i</option>";
					}
				?>
				</select>
			</label>
				<br />
			<?
				hidden_elements('update', $current);
			?>
			<input type="hidden" name="update_id" value="<? echo $default_entry['ID'] ?>" >
			<input type="submit" value="Update" >
		</form>

		<form name="frm_del_url" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<input type="hidden" name="del_url_id" value="<?  echo $current  ?>" >
			<?
				hidden_elements('del_url', "");
			?>
			<input type="submit" value="DELETE" >
		</form>
  </td>
  <td>
	<h3 id="green">Insert New URL</h3>
		<form name="frm_insert" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post">

			<label>Short Name<br /><input type="text" name="insert_shortname" value="" ></label>
				<br />
			<label>URL<br /><input type="text" name="insert_url" value="" ></label>
				<br />
			<label>Owner Email<br /><input type="text" name="insert_owneremail" value="" ></label>
				<br />
			<label>Annotation
				<br />
				<textarea name="insert_annotation" cols="30" rows="7" ></textarea>
			</label>
				<br />
			<label>Priority
				<select name="insert_priority">
				<?
					for($i=1; $i <= 5; $i++) {
						echo "<option value=\"$i\"";
						echo $i == $DEFAULT_PRIORITY ? 'selected' : '';
						echo " >$i</option>";
						
					}
				?>
				</select>
			</label>
				<br />
			<?
				hidden_elements('insert', $current);
			?>
			<input type="submit" value="Insert" >
		</form>
  </td>
</tr>
<tr>
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
				$find_sql = "select * from $URL_TABLE where ShortName like '%".mysql_real_escape_string($find_item)."%' ORDER BY ShortName ASC";
				$result_sql = mysql_query($find_sql, $db);

				while(list($ID, $ShortName, $URL, $OwnerEmail, $Annotation) = mysql_fetch_row($result_sql) ) {
					echo "<option value=\"$ID\" >$ShortName</option>";
				}
			}

			if($find_type == "longname") {
				$find_sql = "select $URL_TABLE.ID, $NAME_TABLE.LongName from $NAME_TABLE, $URL_TABLE, $NAME_URL_TABLE WHERE LongName like '%".mysql_real_escape_string($find_item)."%' AND $NAME_TABLE.nameindex = $NAME_URL_TABLE.nameindex AND $NAME_URL_TABLE.URL_ID = $URL_TABLE.ID ORDER BY ID ASC";
				$result_sql = mysql_query($find_sql, $db);		

				while(list($ID, $LongName) = mysql_fetch_row($result_sql) ) {
					echo "<option value=\"$ID\" >$LongName</option>";
				}
			}

			if($find_type == "email") {
				$find_sql = "select * from $URL_TABLE where OwnerEmail = '".mysql_real_escape_string($find_item)."' ORDER BY OwnerEmail ASC";
				$result_sql = mysql_query($find_sql, $db);

				while(list($ID, $ShortName, $URL, $OwnerEmail, $Annotation) = mysql_fetch_row($result_sql) ) {
					echo "<option value=\"$ID\" >$ShortName</option>";
				}
			}

			if($find_type == "url") {
				$find_sql = "select * from $URL_TABLE where URL like '".mysql_real_escape_string($find_item)."%' ORDER BY URL ASC";
				$result_sql = mysql_query($find_sql, $db);

				while(list($ID, $ShortName, $URL, $OwnerEmail, $Annotation) = mysql_fetch_row($result_sql) ) {
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
		<a href="admin_help.html" target="_blank">Help!</a>
  </td>
  <td valign="top">
	<h3 id="green">New Long Name</h3>
		<form name="frm_new_long" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>New Long Name (To be associated with the current URL)
				<br /> 
			<input type="text" name="new_long_name" value=""></label>
			<input type="hidden" name="new_long_name_urlid" value="<? echo $current ?>" >
			<?
				hidden_elements('new_name', $current);
			?>
				<br />
				<br />
			<input type="submit" value="Create" >
		</form>
	<hr />

	<h3 id="yellow">Rename Long Name</h3>
		<form name="frm_ren_long" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Long Name: 
					<br />
				<select name="ren_long_name">
				<?
					$select_default_longnames = "select nameindex, LongName from $NAME_TABLE where URL_ID = $current";
					$result_default_longnames = mysql_query($select_default_longnames, $db);

					while(list($nameindex, $name) = mysql_fetch_row($result_default_longnames) ) {
						echo "<option value=\"$nameindex\">$name</option>\n";
					}

				?>
				</select>
			</label>
				<br />
			<label>New Long Name:<br /><input type="text" name="ren_new_name" value="" ></label>
				<br />
			<?
				hidden_elements('ren_name', $current);
			?>
				<br />
			<input type="submit" value="Rename" >			
		</form>
	<hr />

	<h3 id="green">Add Association</h3>
		<form name="frm_ass" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Long Name: 
					<br />
				<select name="ass_name">
				<?
					$select_default_longnames = "select nameindex, LongName from $NAME_TABLE where URL_ID = $current";
					$result_default_longnames = mysql_query($select_default_longnames, $db);

					while(list($nameindex, $name) = mysql_fetch_row($result_default_longnames) ) {
						echo "<option value=\"$nameindex\">$name</option>\n";
					}

				?>
				</select>
			</label>
				<br />
			<label>Category: 
				<br />
				<select name="ass_cat">
				<?
					$select_all_categories = "select distinct $CATEGORY_TABLE.ID, $CATEGORY_TABLE.Category from $CATEGORY_TABLE ORDER BY Category";
					$result_all_categories = mysql_query($select_all_categories, $db);

					while(list($ID, $Category) = mysql_fetch_row($result_all_categories) ) {
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
			<input type="submit" value="Associate" >
		</form>
	<hr />

	<h3 id="green">New Category</h3>
		<h6>Use the / (forward slash) character in place of spaces!</h6>
		<form name="frm_new_cat" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>New Category
				<br />
			<input type="text" name="cat_name" size="64"></label>
			<?
				hidden_elements('new_cat', $current);
			?>
				<br />
			<input type="submit" value="Create" >
		</form>
  </td>
  <td valign="top">
	<h3 id="yellow">Remove Association</h3>
		<form name="frm_dis" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Category -> Long Name
				<br />
			<select name="del_ass_id" size="5" >
			<?
				//Gets a list of long names, finds the associated categories with each long name

				$select_default_longnames = "select nameindex, LongName from $NAME_TABLE where URL_ID = $current";

				$result_default_longnames = mysql_query($select_default_longnames, $db);


				while( list($nameindex, $LongName) = mysql_fetch_row($result_default_longnames) ) {
					$select_default_longcat = "select $CATEGORY_TABLE.ID, $CATEGORY_TABLE.Category from $CATEGORY_TABLE, $NAME_CAT_TABLE WHERE $NAME_CAT_TABLE.nameindex = $nameindex AND $CATEGORY_TABLE.ID = $NAME_CAT_TABLE.catindex";
					$result_default_longcat = mysql_query($select_default_longcat, $db);

					while( list($ID, $Category) = mysql_fetch_row($result_default_longcat) ) {
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
			<input type="submit" value="Remove" >
		</form>
	<hr />

	<h3 id="red">Delete Long Name</h3>
		<form name="frm_del_longname" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Long Name: 
				<br />
			<select name="del_longname_id">
			<?
				$select_default_longnames = "select nameindex, LongName from $NAME_TABLE where URL_ID = $current";
				$result_default_longnames = mysql_query($select_default_longnames, $db);

				while(list($nameindex, $name) = mysql_fetch_row($result_default_longnames) ) {
					echo "<option value=\"$nameindex\"> $name</option>\n";
				}
			?>
			</select>
			<?
				hidden_elements('del_long', $current);
			?>
				<br />
				<br />
			<input type="submit" value="Delete" >
		</form>

	<hr />
	<h3 id="yellow">Rename Category</h3>
		<form name="frm_update_cat" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Category: 
				<br />
			<select name="update_cat_id">
			<?
				$select_all_categories = "select distinct $CATEGORY_TABLE.ID, $CATEGORY_TABLE.Category from $CATEGORY_TABLE ORDER BY Category";
				$result_all_categories = mysql_query($select_all_categories, $db);

				// A-Z is protected... wouldn't want to accidentally delete it.
				while(list($ID, $Category) = mysql_fetch_row($result_all_categories) ) {
					if($Category != "A-Z")  
						echo "<option value=\"$ID\"> $Category</option>\n";
				}

			?>
			</select>
			</label>

			<br />

			<label>New Name:<input type="text" name="update_cat_name"></label> 
			<?
				hidden_elements('update_cat', $current);
			?>
				<br />
			<input type="submit" value="Rename" >
		</form>

	<hr />
	<h3 id="red">Delete Category</h3>
		<h6>Note: all Longname-Category associations for this category will be erased. </h6>

		<form name="frm_del_cat" action="<? echo $_SERVER['PHP_SELF'] ?>" method="post" >
			<label>Category: 
				<br />
			<select name="del_cat_id">
			<?
				$select_all_categories = "select distinct $CATEGORY_TABLE.ID, $CATEGORY_TABLE.Category from $CATEGORY_TABLE ORDER BY Category";
				$result_all_categories = mysql_query($select_all_categories, $db);

				// A-Z is protected... wouldn't want to accidentally delete it.
				while(list($ID, $Category) = mysql_fetch_row($result_all_categories) ) {
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
			<input type="submit" value="Delete" >
		</form>

  </td>

</tr>
</table>
</body>

</html>
