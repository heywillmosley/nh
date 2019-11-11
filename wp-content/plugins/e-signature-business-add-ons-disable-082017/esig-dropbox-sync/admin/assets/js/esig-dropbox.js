

(function($){

	
    $('.signature-dropbox-displayonly').each(function(i,e){
			 
			  alert('you can');
			/*var sigpad = $(e).signaturePad(display_opts);
			var input = $(e).find('input.output');
			if(input && $(input).val()){
				sig = $(input).val();
				sigpad.regenerate(sig);
			} */
		});
		
		// show dialog if esig pdf is not active 
		//alert(esig_dropbox.folder_url);
		$('input[name="esig_dropbox"]').on('change', function(){
			
			if($('input[name="esig_dropbox"]').attr('checked')){
				
				   var url = esig_dropbox.folder_url +"pdf-error-dialog.php" ; 
				   
				   var parent = $(this).attr('data-parent');
				   
				   if(parent == "active")
				   {
				   	 return true ;
				   }
				   // not active parent addon 
				   $('#esig-dialog-content').load(url);
				   // show esig dialog 
				   $( "#esig-dialog-content" ).dialog({
					  dialogClass: 'esig-dialog',
					  height: 500,
				      width: 600,
				      modal: true,
		   			 });
				  
				  $('input[name="esig_dropbox"]').prop('checked',false);
				  return false ;	   
			}
			else
			{
				 $('input[name="esig_dropbox"]').prop('checked',false);
			}
			
		});
	
})(jQuery);
