function leftsellAJAX_event( params, divid = "", evalscript = "", additiv = 0 ) { 
   if ( divid != "" ) {
      if ( additiv == 0 ) {
         document.getElementById( divid ).innerHTML = '<img src="' + leftsell.pluginsUrl + 'includes/images/wait.gif" style="height: 10px" />';
      }
   }
   jQuery.ajax( {
      type: "POST", dataType: "html",  
      url: leftsellAJAX_AJAX.ajax_url,      
      data: { action: "leftsellAJAX_action", params: params, nonce : leftsellAJAX_AJAX.security, },
      success: function( data ) {
         if (data == "" ) { data = '<span style="color:red">Communication error - please try again</span>'; }
         if ( divid != "" ) { 
            if ( additiv == 0 ) {
               document.getElementById(divid).innerHTML = data;
            } else {
               document.getElementById(divid).innerHTML += "<br />" + data;
            }
         }
         if ( evalscript != "" ) {
            if ( evalscript == "reload" ) {
               document.location.reload();
            } else {
               eval(  evalscript);
            }
         }
      },
      error: function( errorThrown ) { console.log( "Error: " + errorThrown.statusText );  console.dir( errorThrown ); }
   });
}
