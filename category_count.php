<?php

require_once('/usr/local/uvm-inc/webmaster-mysql.inc');

$user = 'webmaster';
$pass = getwebmasterpassword($user);
$db = mysqli_connect("webdb.uvm.edu",$user,$pass,"UVM") or die("Could not connect: Bad Password.");

$select = 'select * from LINK_CATEGORIES;';
$result = mysqli_query($db, $select);

while($row = mysqli_fetch_array($result, MYSQLI_NUM)){
    $row_list[] = $row;
}



foreach($row_list as $id){
    $query = 'select count(catindex) from LINK_NAMECATBRIDGE where catindex= ' . $id[0] . ';';
    $success = mysqli_query($db, $query);
    $total = mysqli_fetch_row($success);
    
    if ($total[0] >= 15){
        echo '<p>' . $id[1] . ':  ' . $total[0] . '<br/>';
        echo '</p>';
    }
}