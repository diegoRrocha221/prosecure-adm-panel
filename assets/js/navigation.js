$(document).ready(function() {
  // Set active nav item based on current page
  var currentPage = $('body').data('page');
  if (currentPage) {
      $('.sidebar .nav-link[data-page="' + currentPage + '"]').addClass('active');
  }
});