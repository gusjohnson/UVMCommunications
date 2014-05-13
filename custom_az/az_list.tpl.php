<?php

/**
 * @file
 * Provides the full display for the A-Z list.
 */
?>

<style>
    li {display: inline;}
    .hidden {display: none;}
</style>

<h2>Below are all the long names and their corresponding URL's currently associated with the A-Z links page.</h2>

<ul id="azlist">
  <?php foreach ($letters as $letter) { ?>
  <li id="<?php print $letter; ?>"><a title="Links starting with the letter '<?php print drupal_strtolower($letter); ?>'" href="#"><?php print $letter; ?></a></li>
  <?php } ?>
</ul>


<?php foreach ($letters as $letter) { ?>
<div class = "hidden" id ="<?php print $letter; ?>Div">
  <h2><?php print $letter; ?></h2>

  <?php if (!empty($links[$letter])) {
    foreach ($links[$letter] as $link) { ?>
  <p><?php print $link; ?></p><br />

  <?php }} else { ?>
	<p>There are no links starting with this letter!</p>
  <?php } ?>
</div>
<?php } ?>