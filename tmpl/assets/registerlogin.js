jQuery(document).ready(function() {			jQuery(".view_").change(function() {		if(jQuery(this).val()  == 1) {				//var recaptch = jQuery('#captcha2').clone(true,true);				var captcha = jQuery('#captcha2').html();								jQuery('#registration_').hide();				jQuery('#login_form').show();				jQuery('#login_form input#openview').val(jQuery(this).val());				jQuery('#captcha1').html(captcha);			}else if (jQuery(this).val() ==2){				var captcha = jQuery('#captcha1').html();							jQuery('#login_form').hide();				jQuery('#registration_').show();				jQuery('#captcha2').html(captcha);				jQuery('#registration_ input#openview').val(jQuery(this).val());			}	});		jQuery("#login-form").validate({});	jQuery("#registration_form").validate({			rules: {recaptcha_response_field: "required"},			errorPlacement: function(error, element) {			if (element.attr("name") == "recaptcha_response_field") {			  error.insertAfter("#registration_form #recaptcha_area");			} else if(element.attr("name") == "terms"){				error.insertAfter("#registration_form #terms_");				} else {			  error.insertAfter(element);			}					  },	});});