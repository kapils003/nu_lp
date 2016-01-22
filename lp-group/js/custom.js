jQuery(document).ready(function() {
	jQuery('.chosen-select').chosen();
	jQuery('body').click( function(){
	     jQuery('.chosen-drop').toggle(); 
	 });
	var words = jQuery('.regular-text .groups').val().length();
	if(!words){
		jQuery('.groups').html('No group');
	}
});


