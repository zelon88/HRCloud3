// / This function uses jQuery's Ajax to submit the Navigation Bar login form & update the UI elements.
var frm = $('#loginFormNav');
frm.on("submit",function(e){
  e.preventDefault();
  $.ajax({
    type: 'POST',
    url: 'core.php',
    data: frm.serialize(),
    success: function (data) {
      var result = $data.html();
      console.log('A login request was submitted successfully.'); 
      $("#loginModal").html(result); },
    error: function (data) {
      console.log('An error occurred while sending the login request.'); }, }); });