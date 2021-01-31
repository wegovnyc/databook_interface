function unescape(t)
{
	return t.replace(/""/g, '"').replace(/''/g, "'")
}

function toDashDate(d)
{
	if (!d)
		return ''
	//console.log(s)
	y  = d.toString().substr(0, 4)
	m  = d.toString().substr(4, 2)
	d  = d.toString().substr(6, 2)
	return '<span class="text-nowrap">'+y+'-'+m+'-'+d+'</span>';
}

function usToDashDate(d)
{
	if (!d)
		return ''
	console.log(d)
	dd  = d.toString().substr(0, 2)
	m  = d.toString().substr(3, 2)
	y  = d.toString().substr(8, 4)
	//console.log(y,m,d)
	return '<span class="text-nowrap">20'+y+'-'+m+'-'+dd+'</span>';
}

function toFin(d)
{
	return '$' + parseFloat(d).toFixed(2)
}

window.onscroll = function() {scrollFunction()}

function scrollFunction() {
  if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
    $('#return-to-top').show()
  } else {
    $('#return-to-top').hide()
  }
}

function topFunction() {
  document.body.scrollTop = 0; // For Safari
  document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
}

function subscribe_newsletter()
{
	var email = $('#newsletter-email').val()
	$.get(`/api/newsletter_subscription`, {'key': 'as9s8d6d78as6f9sdf876', 'email': email}, function (data) {
		var jj = JSON.parse(data)
		if (jj['success']) {
			$('#newsletter-subs div.row').html('<div class="col-sm-12 col-form-label">Successfull. Thank you for subscribing.</div>');
			$('#newsletter-subs small').html('Your email address');
			$('#newsletter-subs small').attr('style', 'color:white;');
		}
		else {
			$('#newsletter-subs small').html('Failed. Please try again');
			$('#newsletter-subs small').attr('style', 'color:red;');
		}
	})
}