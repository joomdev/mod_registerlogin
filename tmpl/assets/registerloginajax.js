jQuery(document).ready(function() {	

	jQuery(".view_").change(function() {
		if(jQuery(this).val()  == 1) {								
				jQuery('#registration_').hide();
				jQuery('#login_form').show();
				jQuery('#login_form input#openview').val(jQuery(this).val());
				
			}else if (jQuery(this).val() ==2){							
				jQuery('#login_form').hide();
				jQuery('#registration_').show();				
				jQuery('#registration_ input#openview').val(jQuery(this).val());
			}
	});	
	
	
	
	jQuery("#registration_form").validate({
		rules: {recaptcha_response_field: "required"},
		errorPlacement: function(error, element) {
		if (element.attr("name") == "recaptcha_response_field") {
		  error.insertAfter("#registration_form #recaptcha_area");
		} else if(element.attr("name") == "terms"){
			error.insertAfter("#registration_form #terms_");	
		} else {
		  error.insertAfter(element);
		}
		
	  },
		submitHandler: function() {			
			var submit = jQuery('#register_submit');				 
				jQuery.ajax({
					url: 'index.php?option=com_ajax&module=registerlogin&method=getuserregister&Itemid='+itemId+'&format=json',
					type: 'POST',
					data: jQuery('#registration_form').serialize(),
					async:true,
					beforeSend: function() {				
						submit.attr('disabled', true);
						jQuery('.regload').show();
					},
					success: function(data) {
						jQuery('.regload').hide();
						submit.removeAttr('disabled');
						var obj = jQuery.parseJSON(data);
						if(obj.success){						
							jQuery('#error_message').html(obj.message);
							jQuery('form#registration_form input').val('');	
							jQuery('form#registration_form input#register_submit').val('Register'); 
						}else{							
							jQuery('#error_message').html(obj.message);
						}
					},
					error: function(e) {
						jQuery('.regload').hide();
						submit.removeAttr('disabled');
						console.log(e);
					}
				});
			
		return false;		
        }
	});
	
	jQuery("#login-form").validate({});
	/* jQuery("#login-form").validate({		  
		submitHandler: function() {
			var submit = jQuery('#submit');
			jQuery('#laodingdiv').show();
			jQuery.ajax({
					url: 'index.php?option=com_ajax&module=registerlogin&method=getuserlogin&format=json',
					type: 'POST',
					data: jQuery("#login-form").serialize(),
					async:false,
					beforeSend: function() {				
						jQuery('#laodingdiv').show();
						submit.attr('disabled', true);
						
					},
					success: function(data) {
						jQuery('#laodingdiv').hide();
						submit.removeAttr('disabled');
						var obj = jQuery.parseJSON(data);
						if(obj.success){
							location.reload(true);
							jQuery('#error_message').html(obj.message);
						}else{
							jQuery('#error_message').html(obj.message);
						}
					},
					error: function(e) {
						jQuery('#laodingdiv').hide();
						submit.removeAttr('disabled');
						console.log(e);
					}
				});
			return false;		
        }
	}); */	
});
