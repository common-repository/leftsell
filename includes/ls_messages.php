<?php
/**
 * Purpose:           Define and handle message protocol with leftsell server
 * Function URI:      https://leftsell.com/
 * Version:           2.0.2
 * Author:            Kundschaft Schweiz
 * Author URI:        https://kundschaft.ch/ 
 **/
   defined( 'ABSPATH' ) || exit;

   function leftsellRESTHandler( WP_REST_Request $request ) { 
      // received data from REST
      if ( empty( $request ) ) { return; }
      $aryParams = $request->get_params();
      if ( !is_array( $aryParams ) ) { return "Message Error 349"; } 
      if ( isset ( $aryParams["msg"] ) ) {
         $objMsg = new leftsellMessage( sanitize_text_field( $aryParams["msg"] ) );
         return $objMsg->FromREST( $request );
      } else {
         return "Message Error 32";
      }
   }

   function leftSellGetMsgDefinitions() { 
      // get the message defintions
      if ( get_option ( "leftsell_messageupdate" ) == date("Y-m-d", time() ) ) {
         return; // definitions are actual
      }
      global $wp_version;
      if ( !function_exists( 'get_home_url' ) ) { require_once ABSPATH . WPINC . '/link-template.php'; } 
      $args = array( 'timeout'     => 10,
                     'redirection' => 5,
                     'httpversion' => '1.0',
                     'user-agent'  => 'leftsell/' . esc_html( $wp_version ) . '|' . get_home_url(),
                     'blocking'    => true,
                     'headers'     => array(),
                     'cookies'     => array(),
                     'body'        => null,
                     'compress'    => true,
                     'decompress'  => true,
                     'sslverify'   => false,
                     'stream'      => false,
                     'filename'    => null ); 
      $restURL = leftsellGATE . "&msg=getmsgdef";
      $response = wp_remote_get( $restURL, $args );
      if ( empty( $response ) )       { return "error 952"; }
      if ( is_wp_error( $response ) ) { return "error 953"; } 
      $body = wp_remote_retrieve_body( $response );
      if ( empty( $body ) )           { return "error 954"; }
      $data = sanitize_text_field( json_decode( $body ) ); // all as string
      $objJSON = new leftsellJSON(); 
      $objJSON->ToOption( $data, "leftsell_messagedefintions" );
      $deprecated = null;
      $autoload = 'no';
      update_option ( "leftsell_messageupdate", date("Y-m-d", time() ), $deprecated, $autoload );
      $objJSON = null;
      return $data;
   }
   
   function leftSellSendMessage( $aryParams ) {
      // AJAX callback: send message via REST and handle reply 
      // plugin activation/deactivation/uninstall callback
      if ( empty( $aryParams ) ) { return "error 430"; }
      if ( isset( $aryParams[ "msgid" ] ) ) {
         $objMsg = new leftsellMessage( sanitize_text_field( $aryParams[ "msgid" ] ) );
         if ( empty( $objMsg ) ) { return "error 432"; }
         $objMsg->FromArray( $aryParams );
         return $objMsg->SendMessage();
      } else {
         return "error 431";
      }
   }
   
   class leftsellMessage {
      /**
      * Message Object to send and receive 
      * from and to server communication
      * 
      **/
      public  $m_strName = "";
      public  $m_aryFields = array();
      public  $m_aryParams  = array();    
      public  $m_strCallerURL;
      public  $m_intCallerId;
      private $m_aryAllMessages;  

      function __construct ( $strName ) {
         $this->m_strName = sanitize_text_field( $strName );
         $this->InitMessages();
         if ( isset( $this->m_aryAllMessages[ $strName ] ) ) {
            $this->m_aryFields = $this->m_aryAllMessages[ $strName ];  // associate msgfields
         }
         $this->m_intCallerId = sanitize_text_field( get_option( "leftsell_shop_id" ) );
         if ( !function_exists( 'get_home_url' ) ) {  require_once ABSPATH . WPINC . '/link-template.php'; } 
         $this->m_strCallerURL = get_home_url();
      }

      private function InitMessages() {
         // assure to have message definitions
         $objJSON = new leftsellJSON();
         $this->m_aryAllMessages = $objJSON->aryFromJSONOption( "leftsell_messagedefintions" );
         if ( count( $this->m_aryAllMessages ) == 0 ) {
            // request messages
            leftSellGetMsgDefinitions();
            $this->m_aryAllMessages = $objJSON->aryFromJSONOption( "leftsell_messagedefintions" );
         }
      }

      public function OnMessage( $objMsg ) {
         // received request message from REST -> provide requested data
         if ( empty( $objMsg ) ) {
            return new WP_REST_Response( wp_json_encode( "401", JSON_FORCE_OBJECT ), 10000 );
         } 
         if ( !isset( $objMsg->m_aryParams[ "msg" ] ) ) { 
            return new WP_REST_Response( wp_json_encode( "404", JSON_FORCE_OBJECT ), 10000 );
         }
         if ( $objMsg->m_strCallerURL == "" ) {
            return new WP_REST_Response( wp_json_encode( "403", JSON_FORCE_OBJECT ), 10000 );
         }
         if ( isset( $this->m_aryParams [ "shopid" ] ) ) {
            $this->m_intCallerId = $this->m_aryParams [ "shopid" ];
         }
         // message debugger
         leftsellDebug ("Message received ". $objMsg->m_aryParams[ "msg" ]. " on ". get_home_url());
         
         switch ( sanitize_text_field( $this->m_aryParams[ "msg" ] ) ) {
            // communication ----------------------------------------------------
            case "getbugreports": 
               $objJSON = new leftsellJSON();
               $aryStored = $objJSON->aryFromJSONOption( "leftsell_bugregports" );
               $result = $aryStored;
               break;
            case "getfeaturerequests":
               $objJSON = new leftsellJSON();
               $aryStored = $objJSON->aryFromJSONOption( "leftsell_featurerequests" );
               $result = $aryStored;
               break;

            // private market joins ---------------------------------------------
            case "joinrequest":
               $objMarket = new leftsellMarket( true );
               $result = $objMarket->AddJoinRequest( $this->m_intCallerId, $this->m_strCallerURL );
               $objMarket = null;
               break;
            case "canceljoinrequest":
               $objMarket = new leftsellMarket( true );
               $result = $objMarket->RemoteCancelJoinRequest( $this->m_strCallerURL );
               $objMarket = null;
               break;
            case "joinrequestaccepted":
               $objMarket = new leftsellMarket( true );
               $result = $objMarket->JoinRequestAccepted( $this->m_strCallerURL );
               $objMarket = null;
               break;
            case "joinrequestdeclined":
               $objMarket = new leftsellMarket( true );
               $result = $objMarket->JoinRequestDeclined( $this->m_strCallerURL );
               $objMarket = null;
               break;
            case "getprivatemarket":     // server gets all shops in private market
               $objMarket = new leftsellMarket( true );
               $result = $objMarket->m_aryShops;
               $objMarket = null;
               break;
            
            // private market products ------------------------------------------
            case "productlist":          // deliver product list
               $objShop = new leftsellShop();
               $result = $objShop->GetPublishedProductList();
               $objShop = null;
               break;
            case "getpmproduct":         // deliver a product
               if ( isset( $this->m_aryParams[ "productwpid" ] ) ) {
                  $objProduct = new leftsellProduct();
                  $result = $objProduct->DeliverToPM( $this->m_strCallerURL, $this->m_aryParams[ "productwpid" ] );
                  $objProduct = null;
               } else {
                  $result = "Market error 20300";
               }
               break;
            case "pmissubsribed":        // a product got subscribed
               if ( isset( $this->m_aryParams[ "localwpid" ] ) and isset( $this->m_aryParams[ "remoteshopid" ] ) ) {
                  $objProduct = new leftsellProduct();
                  $result = $objProduct->AddPMSubscriptionBy( $this->m_aryParams[ "localwpid" ], $this->m_intCallerId );
                  $objProduct = null;
               } else { $result = "Id error 203101"; }
               break;
            case "pmunsubscribe":        // product is getting unsubscribed
               if ( isset( $this->m_aryParams[ "localwpid" ] ) and isset( $this->m_aryParams[ "remoteshopid" ] ) ) {
                  $objProduct = new leftsellProduct();
                  $result = $objProduct->RemoveSubscription( $this->m_aryParams[ "localwpid" ], $this->m_aryParams[ "remoteshopid" ] );
                  $objProduct = null;
               } else { $result = "Id error 204101"; }
               break;
            case "pmunshared":           // product got unshared by publisher
               if ( isset( $this->m_aryParams[ "remotewpid" ] ) and isset( $this->m_aryParams[ "remoteshopid" ] ) ) {
                  $objProduct = new leftsellProduct();
                  $result = $objProduct->GotUnshared( $this->m_aryParams[ "remotewpid" ], $this->m_aryParams[ "remoteshopid" ] );
                  $objProduct = null;
               }
               break;
            case "PMupdateSubscribed":   // a subscribed product was updated
               if ( isset( $this->m_aryParams[ "remotewpid" ] ) ) {
                  $objProduct = new leftsellProduct();
                  $objProduct->FetchRemote( $this->m_intCallerId, $this->m_aryParams[ "remotewpid" ] );
                  $objProduct = null;
                  $result = "OK"; // give back positive -> info in PM Log
               } else {
                  $result = "NAK";
               }
               break;
            case "PMsubscribercomment":  // a subscriber informs about new comment
               if ( isset ( $this->m_aryParams[ "jsoncomment" ] ) ) {
                  $objComment = new leftsellComment();
                  $result = $objComment->FetchJSONComment( $this->m_aryParams[ "jsoncomment" ], $this->m_intCallerId ); 
                  $objComment = null;
               } else {
                  $result = "NAK";
               }
               break;
         }
         return new WP_REST_Response( wp_json_encode( $result, JSON_FORCE_OBJECT ), 10000 );
      }
      
      public function SendMessage( $target = leftsellGATE) {
         if ( $this->m_strName == "" ) { leftSellGetMsgDefinitions(); return "326 MsgDef"; }
         global $wp_version;
         if ( !function_exists( 'get_home_url' ) ) { require_once ABSPATH . WPINC . '/link-template.php'; } 
         $objShop = new leftsellShop();
         $args = array( 'timeout'     => 10,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'user-agent'  => 'lefsell/' . esc_html( $wp_version ) . '|' . get_home_url(),
                        'blocking'    => true,
                        'headers'     => array(),
                        'cookies'     => array(),
                        'body'        => null,
                        'compress'    => true,
                        'decompress'  => true,
                        'sslverify'   => false,
                        'stream'      => false,
                        'filename'    => null ); 
         $restURL = $target . "&shopid=" . esc_html( sanitize_text_field( $this->m_intCallerId ) ) . "&msg=" . esc_html( sanitize_text_field( $this->m_strName ) );
         foreach( $this->m_aryFields as $key=>$data ) {
            $restURL .= "&" . sanitize_text_field( $key ) . "=" . sanitize_text_field( $data );
         }
         $objShop = null;
         // get data from remote
         $response = wp_remote_get( $restURL, $args);
         if ( empty( $response ) )       { return "error 780"; }
         if ( is_wp_error( $response ) ) { return "error connection 781"; } 
         $body = wp_remote_retrieve_body( $response ); 
         if ( empty( $body ) )           {
            leftsellDebug( var_export( $response, true ) ); 
            return "error 782: no response"; 
         }
         $preOK = str_replace( '"', '', $body);    //  "\"OK\""
         $preOK = str_replace( '\\', '', $preOK);  //  "\"OK\""
         if ( $preOK == "OK" ) { return "OK"; }
         $data = json_decode( $body ) ; // all as string
         $data = json_decode( $data, true ); // to array
         
         // message debugger
         leftsellDebug( "SentMsg " . $this->m_strName ); //leftsellDebug( "SentMsg " . $this->m_strName . "\rResult", $data );
         return $this->HandleReply( $data );
      }

      private function HandleReply( $data ) { 
         // got result from requested REST call
         if ( empty( $data ) ) { return "error no reply 880"; }
         switch ( $this->m_strName ) {
            // communication ----------------------------------------------------
            case "reg":
               $objShop = new leftsellShop();
               return $objShop->Register( $data );
               break;
            case "news":
               return nl2br( $data );
               break;
            default: return $data; break;
         }
      }
      
      // receive messages ----------------------------------------------------
      public function FromREST(WP_REST_Request $request) { 
         // received a msg from REST -> get standard values and send it to handle
         if ( empty( $request ) ) { return "error 980"; }
         $this->m_aryParams = $request->get_params();
         $user_agent = sanitize_text_field( $request->get_header( "user_agent" ) );
         $aryTemp = explode( "|", $user_agent );
         if ( is_array( $aryTemp ) ) {
            if ( count( $aryTemp ) > 1 ) {
               $this->m_strCallerURL = $aryTemp[ 1 ];
            }
         }
         $aryTemp = null;
         return $this->OnMessage( $this );
      }

      public function FromArray($ary) {
         // fill message from array
         if ( empty( $ary ) )                   { return; }
         if ( isset( $ary["m_strName"] ) )      { $this->m_strName      = sanitize_text_field( $ary["m_strName"] ); }
         if ( isset( $ary["m_aryFields"] ) )    { $this->m_aryFields    = $ary["m_aryFields"]; }
         if ( isset( $ary["m_aryParams"] ) )    { $this->m_aryParams    = $ary["m_aryParams"]; }
         if ( isset( $ary["m_intCallerId"] ) )  { $this->m_intCallerId  = sanitize_text_field( $ary["m_intCallerId"] ); }
         if ( isset( $ary["m_strCallerURL"] ) ) { $this->m_strCallerURL = sanitize_text_field( $ary["m_strCallerURL"] ); }
      }
   }


   class leftsellCom {
      /**
      * Business layer 
      * to execute Messages
      * 
      **/

      // communication ----------------------------------------------------
      public function ReportBug( $aryParams ) {
         // process reporting a bug: store locally in option, request server to receive all reported bugs
         if ( !is_array( $aryParams ) ) { return "error 1080"; }
         if ( isset( $aryParams["value"] ) ) {
            if ( $aryParams["value"] == "" ) {
               return esc_html__( 'Nothing to transmit error 1081', 'leftsell' );
            }
            // store locally
            $objJSON = new leftsellJSON();
            // get already stored bugs
            $aryStored = $objJSON->aryFromJSONOption( "leftsell_bugregports" );
            // add new bug
            $aryStored[] = array( "reportdate" => date("d.m.Y", time()) , 
                                  "text" => sanitize_text_field( $aryParams["value"] ), 
                                  "answer" => "", 
                                  "submitted" => 0 );
            // save back to option
            $objJSON->aryToJSONOption( $aryStored, "leftsell_bugregports" );
            // request server fetch
            $objMsg = new leftsellMessage( "bugreport" );
            $strReply = $objMsg->SendMessage();
            $objMsg = null;
            if ($strReply == "OK") {
               return esc_html__( 'Bug successfully transmitted','leftsell' );
            } else {
               return $strReply;
            }
         } else {
            return esc_html__( 'Transmission error 548', 'leftsell' );
         }
      }
      public function RequestFeature( $aryParams ) {
         // process feature reqeust: store locally in option, request server to receive all features
         if ( !is_array( $aryParams ) ) { return "error 1180"; }
         if ( isset( $aryParams["value"] ) ) {
            if ( $aryParams["value"] == "" ) {
               return esc_html__( 'Nothing to transmit error 550', 'leftsell' );
            }
            // store locally
            $objJSON = new leftsellJSON();
            $aryStored = $objJSON->aryFromJSONOption( "leftsell_featurerequests" );
            $aryStored[] = array( "requestdate" => date("d.m.Y", time()) , 
                                  "featuretext" => sanitize_text_field( $aryParams[ "value" ] ) );
            $objJSON->aryToJSONOption( $aryStored, "leftsell_featurerequests" );
            // request server fetch
            $objMsg = new leftsellMessage( "featurerequest" );
            $strReply = sanitize_text_field( $objMsg->SendMessage() );
            $objMsg = null;
            if ($strReply == "OK") {
               return esc_html__( 'Feature request successfully transmitted', 'leftsell' );
            } else {
               return $strReply;
            }
         } else {
            return esc_html__( 'Transmission error 551', 'leftsell' );
         }
      }
   } // end class leftsellCom
?>