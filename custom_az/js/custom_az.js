/**
 * @file
 * Provides navigation for the a-z list.
 */

(function ($) {

  $(document).ready(function() {
      var pathname = location.pathname;
      if (pathname == "/a_to_z") {
	$("#ADiv").show();
      }
      else {
      var ID = window.location.hash.substring(1);
      var dropDown = "#" + ID + "Div";
      $(dropDown).show();
      }
      
      $("ul#azlist > li").click(function() {
      var idDiv = "#" + this.id + "Div";
      $('.hidden').hide();
      $(idDiv).show();
      $('body').scrollTo(idDiv);
    });
  });

})(jQuery);
