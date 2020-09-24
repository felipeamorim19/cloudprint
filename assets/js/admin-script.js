// JavaScript Document
jQuery(document).ready(function(e) {
    jQuery(document).on("click",".xc_ajax_button",function(event){
		event.preventDefault();
		var data = "";
		var txt = jQuery(this).html();
		jQuery("#xc_woo-printer-box").addClass("loading");
		jQuery.post(jQuery(this).attr("href"),data,function(dt){
			jQuery("#xc_woo-printer-box").removeClass("loading");
			if(dt.success){
				alert(txt+" done!");
			}else{
				alert(dt.message);	
			}
		},"JSON");	
	});
});