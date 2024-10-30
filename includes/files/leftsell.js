/* leftsell helper js */
function leftsellSendMessage( targetdiv, msgid, data, additiv, evalscript ) {
   var strCall = {
      "call": "sendmessage",
      "msgid": msgid,
      "value": data
   };
   strCall = JSON.stringify( strCall );
   leftsellAJAX_event( strCall, targetdiv, evalscript, additiv );
} 
function leftsellGetElemValue( id ) {
   var element = document.getElementById( id );
   if ( typeof( element ) != 'undefined' && element != null ) {
      return encodeURI( document.getElementById( id ).value );
   } else {
      return "";
   }
}
function leftsellReportBug() {
   var bugtext = leftsellGetElemValue( "bugtext" );
   var strCall = {
      "call": "reportbug",
      "value": bugtext
   };
   strCall = JSON.stringify( strCall );
   leftsellAJAX_event( strCall, "bugger", "", 0 );
}
function leftsellRequestFeature() {
   var featuretext = leftsellGetElemValue( "featuretext" );
   var strCall = {
      "call": "requestfeature",
      "value": featuretext
   };
   strCall = JSON.stringify( strCall );
   leftsellAJAX_event( strCall, "featurer", "", 0 );
}
/* Market -------------------------------------------------------------------------- */
function leftsellConnectPrivate() {
   var shopURL = leftsellGetElemValue( "newshop" );
   var strCall = {
      "call": "market_connect_private",
      "newshop": shopURL
   };
   strCall = JSON.stringify( strCall );
   leftsellAJAX_event( strCall, "leftsell_newshop", "", 0 );
}
