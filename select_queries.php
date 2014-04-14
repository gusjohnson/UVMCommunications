<html>
 <head>
    <style>
    li {display: inline;}
    div {display: none;}
    </style> 
    
    <script src ="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js">
    </script>
    <script>
    $(document).ready(function(){
        $("ul#azlist > li").click(function(){
            var idDiv = "#" + this.id + "Div";
            $(idDiv).slideToggle();
            $('body').scrollTo(idDiv);
        });
    });
    </script>
    
 </head>
<body>
<ul id="azlist">
<li id="A"><a href="#">A</a></li>
<li id="B"><a title="Links starting with the letter 'b'" href="#">B</a></li>
<li id="C"><a title="Links starting with the letter 'c'" href="#">C</a></li>
<li id="D"><a title="Links starting with the letter 'd'" href="#">D</a></li>
<li id="E"><a title="Links starting with the letter 'e'" href="#">E</a></li>
<li id="F"><a title="Links starting with the letter 'f'" href="#">F</a></li>
<li id="G"><a title="Links starting with the letter 'g'" href="#">G</a></li>
<li id="H"><a title="Links starting with the letter 'h'" href="#">H</a></li>
<li id="I"><a title="Links starting with the letter 'i'" href="#">I</a></li>
<li id="J"><a title="Links starting with the letter 'j'" href="#">J</a></li>
<li id="K"><a title="Links starting with the letter 'k'" href="#">K</a></li>
<li id="L"><a title="Links starting with the letter 'l'" href="#">L</a></li>
<li id="M"><a title="Links starting with the letter 'm'" href="#">M</a></li>
<li id="N"><a title="Links starting with the letter 'n'" href="#">N</a></li>
<li id="O"><a title="Links starting with the letter 'o'" href="#">O</a></li>
<li id="P"><a title="Links starting with the letter 'p'" href="#">P</a></li>
<li id="Q"><a title="Links starting with the letter 'q'" href="#">Q</a></li>
<li id="R"><a title="Links starting with the letter 'r'" href="#">R</a></li>
<li id="S"><a title="Links starting with the letter 's'" href="#">S</a></li>
<li id="T"><a title="Links starting with the letter 't'" href="#">T</a></li>
<li id="U"><a title="Links starting with the letter 'u'" href="#">U</a></li>
<li id="V"><a title="Links starting with the letter 'v'" href="#">V</a></li>
<li id="W"><a title="Links starting with the letter 'w'" href="#">W</a></li>
<li id="X"><a title="Links starting with the letter 'x'" href="#">X</a></li>
<li id="Y"><a title="Links starting with the letter 'y'" href="#">Y</a></li>
<li id="Z"><a title="Links starting with the letter 'z'" href="#">Z</a></li>
</ul>

<?

require_once('/usr/local/uvm-inc/webmaster-mysql.inc');

$user = 'webmaster';
$pass = getwebmasterpassword($user);
$db = mysqli_connect("webdb.uvm.edu",$user,$pass,"UVM") or die("Could not connect: Bad Password.");


//Table Names
$URL_TABLE = "LINK_URLS";
$CATEGORY_TABLE = "LINK_CATEGORIES";
$NAME_TABLE = "LINK_NAMES";
$NAME_CAT_TABLE = "LINK_NAMECATBRIDGE";
$NAME_URL_TABLE = "LINK_URLNAMEBRIDGE";

echo "<h2>Below are all the long names and their corresponding URL's currently associated with the A-Z links page.</h3>";

$letters = range('A','Z');
foreach ($letters as $letter) {

    echo '<div id ="' . $letter . 'Div">';
    echo '<h2>' . $letter . '</h2>';


    $a_z = "select LongName from $NAME_CAT_TABLE, $NAME_TABLE where LINK_NAMES.nameindex = LINK_NAMECATBRIDGE.nameindex and 
            LINK_NAMECATBRIDGE.catindex = 169 and LongName != '' and LongName like '".$letter."%' order by LongName;";

    $result = mysqli_query($db, $a_z);

    while($row = mysqli_fetch_array($result, MYSQLI_NUM)){
        $row_list[] = $row;
    }

    foreach($row_list as $azcategory) {
        $url_query = 'select URL from LINK_URLS, LINK_NAMES where LINK_NAMES.URL_ID = LINK_URLS.ID and LINK_NAMES.LongName = "'
            . $azcategory[0] . '";';
        $result2 = mysqli_query($db, $url_query);
        $url_array = mysqli_fetch_row($result2);

        if ($url_array[0] != '') {
            echo "<p><a href = " . $url_array[0] . ">" . $azcategory[0] . "</a><br />";
            echo "" . $url_array[0] . "<br/><br/>";
        }
    }

    echo "</p>";
    echo "</div>";
    $row_list = array();
}

?>
</body>
</html>