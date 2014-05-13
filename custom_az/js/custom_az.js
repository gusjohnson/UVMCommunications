/**
 * @file
 * Provides navigation for the a-z list.
 */

(function ($) {

  $(document).ready(function() {
    $("ul#azlist > li").click(function() {
      var idDiv = "#" + this.id + "Div";
      $(idDiv).slideToggle();
      $('body').scrollTo(idDiv);
    });
  });

})(jQuery);