<?php
/**
 * Purpose:           Manage shops and markets
 * Function URI:      https://leftsell.com/
 * Version:           2.0.2
 * Author:            Kundschaft Schweiz
 * Author URI:        https://kundschaft.ch/ 
 **/
defined( 'ABSPATH' ) || exit;

class leftsellShop {
   /**
   *
   * Handles the shop object 
   * V 1.0.1: register a shop for communication
   * V 2.0.0: join shops to private market
   * 
   **/

   public $m_intId      = 0;
   public $m_intReg     = 0;
   public $m_intEnabled = 0;
   public $m_intBlock   = 0;
   public $m_strURL     = "";

   function __construct () {
      $shopid = sanitize_text_field( get_option( "leftsell_shop_id" ) );
      if ( $shopid != "" ) {
         $this->m_intId = $shopid;
      }
      $this->m_intBlock   = sanitize_text_field( get_option( "leftsell_shop_blocked" ) );
      $this->m_intEnabled = sanitize_text_field( get_option( "leftsell_shop_enabled" ) );
   }
   public function GetId( $shopurl ) {
      $objMarket = new leftsellMarket( true );
      foreach ( $objMarket->m_aryShops as $objShop ) {
         if ( $objShop->m_strURL == $shopurl ) {
            return $objShop->m_intId;
         }
      }
      $objMarket = null;
      return false;
   }
   // activation ----------------------------------------------------------------------
   public function ActivateShop() {
      $objMsg = new leftsellMessage( "plugin_activation" );
      $result = $objMsg->SendMessage();
      $objMsg = null;
      if ( intval( $result == 0 ) ) {
         wp_die( $result );
      } else {
         $deprecated = null;
         $autoload = 'no';
         update_option( "leftsell_shop_id", $result, $deprecated, $autoload );
         update_option( "leftsell_shop_enabled", 1, $deprecated, $autoload );
      }
   }
   public function Register( $result ) {
      $result = sanitize_text_field( $result );
      if ( intval( $result ) > 0 ) {
         $deprecated = null;
         $autoload = 'no';
         update_option ( "leftsell_shop_id", $result, $deprecated, $autoload );
         update_option ( "leftsell_shop_enabled", 1, $deprecated, $autoload );
         update_option ( "leftsell_shop_blocked", 0, $deprecated, $autoload );
         return esc_html__( 'Successfully registered. Please reload page.', 'leftsell' );
      }
      if ( $result == "blocked" ) {
         $deprecated = null;
         $autoload = 'no';
         update_option ( "leftsell_shop_blocked", 1, $deprecated, $autoload );
         return esc_html__( 'Blocked', 'leftsell' );
      }
      return $result;
   }
   public function GetShopValues( $shopURL ) {
      // update shop values from server
      $objMsg = new leftsellMessage( "shopinfourl" );
      $objMsg->m_aryFields[ "shopurl" ] = $shopURL;
      $result = $objMsg->SendMessage();
      if ( is_array( $result ) ) {
         if ( isset ( $result[ "id" ] ) )      { $this->m_intId = $result[ "id" ]; }
         if ( isset ( $result[ "enabled" ] ) ) { $this->m_intEnabled = $result[ "enabled" ]; }
         if ( isset ( $result[ "blocked" ] ) ) { $this->m_intBlocked = $result[ "blocked" ]; }
         // default values
         if ( $this->m_intEnabled == "" )      { $this->m_intEnabled = 1; }
         if ( $this->m_intBlocked == "" )      { $this->m_intBlocked = 1; }
      }
   }
   public function IsAllowedToJoinMarket() {
      // is the local shop allowed to join a market
      if ( intval( $this->m_intId)  == 0 )      { return false; }
      if ( intval( $this->m_intEnabled)  == 0 ) { return false; }
      if ( intval( $this->m_intBlock)  > 0 )    { return false; }
      // remote check if this shop is blocked
      $objMsg = new leftsellMessage( "shopinfo" );
      $result = $objMsg->SendMessage();
      $objMsg = null;
      if ( !is_array( $result ) )            { return false; }
      if ( !isset( $result [ "id" ] ) )      { return false; }
      if ( !isset( $result [ "enabled" ] ) ) { return false; }
      if ( !isset( $result [ "blocked" ] ) ) { return false; }
      if ( $this->m_intId != sanitize_text_field( $result[ "id" ] ) ) { return false; }
      if ( sanitize_text_field( $result [ "enabled" ] ) != 1 ) { 
         update_option ( "leftsell_shop_enabled", 0, null, "no" );
         return false; 
      }
      if ( sanitize_text_field( $result [ "blocked" ] ) != 0 ) { 
         update_option ( "leftsell_shop_blocked", 1, null, "no" );
         return false; 
      }
      return true;
   }
   // init ----------------------------------------------------------------------------
   public function FromArray( $ary ) {
      if ( isset( $ary[ "m_intId" ] ) )      { $this->m_intId      = $ary[ "m_intId" ]; }
      if ( isset( $ary[ "m_intReg" ] ) )     { $this->m_intReg     = $ary[ "m_intReg" ]; }
      if ( isset( $ary[ "m_intEnabled" ] ) ) { $this->m_intEnabled = $ary[ "m_intEnabled" ]; }
      if ( isset( $ary[ "m_intBlock" ] ) )   { $this->m_intBlock   = $ary[ "m_intBlock" ]; }
      if ( isset( $ary[ "m_strURL" ] ) )     { $this->m_strURL     = $ary[ "m_strURL" ]; }
   }
   public function ToArray() {
      return array( "m_intId" => $this->m_intId,
                    "m_intReg" => $this->m_intReg,
                    "m_intEnabled" => $this->m_intEnabled,
                    "m_intBlock" => $this->m_intBlock,
                    "m_strURL" => $this->m_strURL
                  );
   }
   // Private Market ------------------------------------------------------------------
   public function DisableAllProducts() {
      // shop is not anymore in market
   }
   public function aryGetProducts( $type = 'all' ) {
      // get products as array
      $args = array();
      switch ( $type ) {
         case "all":             // all own products
            $args = array( 'numberposts' => -1, 'post_type'	=> 'product', 'post_status' => 'published',);
            break;
         case "published":       // all execpt subscribed
            $args = array( 'numberposts' => -1, 'post_type'	=> 'product', 'post_status' => 'published',
                           'meta_query' => array(
                                             array(
                                                'key' => 'leftsell_post_private_share',
                                                'value' => '1',
                                                'compare' => '=='
                                             ) ) );
            break;
         case "subscribed":      // only subscribed
            $args = array( 'numberposts' => -1, 'post_type'	=> 'product', 'post_status' => 'published',
                           'meta_query' => array(
                                 array(
                                    'key' => 'leftsell_post_pm_subscribed',
                                    'value' => '1',
                                    'compare' => '==',
                                 ) ) );
            break;
      }
      $posts = get_posts( $args );
      if( empty( $posts ) ) { return array(); } 
      $aryResult = array();
      foreach ( $posts as $post ) {
         if ( get_post_status( $post ) == "publish" ) {
            $objProduct = new leftsellProduct();
            $objProduct->fromPost( $post );
            if ( $type == 'all' ) {
               if ( $objProduct->fields[ "pm_subscribed" ] != 1 ) {
                  $aryResult[] = $objProduct->fields;
               }
            } else {
               $aryResult[] = $objProduct->fields;
            }
            $objProduct = null;
         }
      }
      return $aryResult;
   }
   public function GetPMProductList() {
      // get products not yet subscribed from remote shop -> due to REST call from other shop
      $objMsg = new leftsellMessage( "productlist" );
      $result = $objMsg->SendMessage( $this->m_strURL . leftsellROUTE );
      
      $aryResult = array();
      if ( is_array( $result ) ) {
         foreach( $result as $item ) {
            if (isset( $item["wpid"] )) {
               $aryResult[] = $item;
            }
         }
         return $aryResult;
      } else {
         return ""; 
      }
      $objProduct = null;
   }
   public function GetPublishedProductList() {
      // deliver minimized list of published products to REST
      $aryDeliver = array();
      foreach ($this->aryGetProducts( "published" ) as $aryProduct) {
         $aryDeliver[] = array( "wpid" => $aryProduct[ "wpid" ], 
                                "shopname" => $aryProduct[ "shopname" ],
                                "shopurl" => $aryProduct[ "shopurl" ],
                                "title" => $aryProduct[ "title" ],
                                "isservice" => $aryProduct[ "isservice" ],
                                "private_share" => 0, //$aryProduct[ "private_share" ],
                                "shopid" => $aryProduct[ "shopid" ],
                                "shortdescription" => $aryProduct[ "shortdescription" ],
                                "pm_imported" => $aryProduct[ "pm_imported" ],
                                "pm_subscribed" => $aryProduct[ "pm_subscribed" ],
                                "pm_imported" => $aryProduct[ "pm_imported" ],
                                "price" => $aryProduct[ "price" ] );
      }
      return $aryDeliver;
   }
} // end class leftsellShop

class leftsellMarket {
   /**
   *
   * Handles market events
   * LeftSell V 2.0.0 private Markets
   * 
   **/

   public $m_blnPrivate;                         // is a private market
   public $m_aryShops            = array();      // joined shops
   public $m_aryJoinRequestsTo   = array();      // shop waiting to confirm join request
   public $m_aryJoinRequestsFrom = array();      // shops waiting for this shop to confirm

   function __construct ( $blnPrivate = false ) {
      $this->m_blnPrivate = $blnPrivate;
      if ( $this->m_blnPrivate == true ) {
         $this->GetShops();
         $this->GetJoinRequests();
      }
   }
   private function GetShops() {
      // get private shops as array
      $objJSON = new leftsellJSON();
      $aryShops = $objJSON->aryFromJSONOption( "leftsell_pm_shops" );
      $this->m_aryShops = array();
      $blnSave = false;
      foreach ( $aryShops as $aryShop ) {
         if ( is_array( $aryShop ) ) {
            $objShop = new leftsellShop();
            $objShop->FromArray( $aryShop );
            if ($objShop->m_intId == 0 ) {
               $objShop->GetShopValues( $objShop->m_strURL );
               $blnSave = true;
            }
            $this->m_aryShops[$objShop->m_intId] = $objShop;
            $objShop = null;
         }
      }
      if ( $blnSave ) {
         $objJSON->aryToJSONOption( $this->m_aryShops, "leftsell_pm_shops" );
      }
      $aryShops = null;
      $objJSON = null;
   }
   public function IsShopInPM( $shopurl ) {
      // verify if a shop is in private market ( call on subscriptions / imports for products )
      $blnResult = false;
      if ( is_array ( $this->m_aryShops ) ) {
         if ( count( $this->m_aryShops ) > 0 ) {
            foreach ( $this->m_aryShops as $objShop ) {
               if ( $objShop->m_intBlock == 0 or $objShop->m_intBlock == "" ) {
                  if ( substr( $objShop->m_strURL, 0, strlen( $shopurl ) ) == $shopurl ) {
                     $blnResult = true;
                  }
                  if ( substr( $shopurl, 0, strlen( $objShop->m_strURL ) ) == $objShop->m_strURL) {
                     $blnResult = true;
                  }
               }
            }
         }
      } 
      return $blnResult;
   }
   public function IsShopIdInPM( $shopid ) {
      // verify if a shop is in private market ( called on external product update )
      $blnResult = false;
      if ( is_array ( $this->m_aryShops ) ) {
         return isset( $this->m_aryShops[ $shopid ] );
      } else {
         return false;
      }
   }
   public function PMLog( $text ) { 
      $text = sanitize_text_field( $text );
      // log market events
      $objJSON = new leftsellJSON();
      $aryLog = array();
      $aryLog[] = date( "d-m-Y h:i", time() ) . " " . esc_sql( $text ) ;
      foreach( $objJSON->aryFromJSONOption( "leftsell_pm_log" ) as $entry ) {
         $aryLog[] = $entry;
      }
      // set maximum
      if ( is_array( $aryLog ) ) {
         if ( count ( $aryLog ) > 199 ) {
            while( count ( $aryLog ) > 199 ) {
               array_pop( $aryLog );
            }
         }
      }
      $objJSON->aryToJSONOption( $aryLog, "leftsell_pm_log" );
      $objJSON = null;
      $aryLog = null;
   }
   public function PMGetLog() {
      $objJSON = new leftsellJSON();
      $aryLog = $objJSON->aryFromJSONOption( "leftsell_pm_log", true );
      $objJSON = null;
      return $aryLog;
      $aryLog = null;
   }
   
   // JOIN ----------------------------------------------------------------------------
   private function GetJoinRequests() {
      // return array of shops wanting to join the market and shops tried to connect to
      $objJSON = new leftsellJSON();
      $aryRequestsFrom = $objJSON->aryFromJSONOption( "leftsell_pm_joinrequestsfrom" );
      $this->m_aryJoinRequestsFrom = array();
      foreach ( $aryRequestsFrom as $aryRequest ) {
         if ( is_array( $aryRequest ) ) {
            $objShop = new leftsellShop();
            $objShop->FromArray( $aryRequest );
            $this->m_aryJoinRequestsFrom[] = $objShop;
            $objShop = null;
         }
      }
      $aryRequestsFrom = null;
      $aryRequestsTo = $objJSON->aryFromJSONOption( "leftsell_pm_joinrequeststo" );
      $this->m_aryJoinRequestsTo = array();
      foreach ( $aryRequestsTo as $aryRequest ) {
         if ( is_array( $aryRequest ) ) {
            $objShop = new leftsellShop();
            $objShop->FromArray( $aryRequest );
            $this->m_aryJoinRequestsTo[] = $objShop;
            $objShop = null;
         }
      }
      $aryRequestsTo = null;
      $objJSON = null;
   }
   private function AddMarketShop( $type , $shopid = 0, $shopURL = "" ) {
      $objNewShop = new leftsellShop();
      $objJSON = new leftsellJSON();
      $objNewShop->m_intId = $shopid;
      $objNewShop->m_strURL = $shopURL;
      $objNewShop->m_intReg = 1;
      $objNewShop->m_intEnabled = 1;
      $objNewShop->m_intBlocked = 0;
      switch ( $type ) {
         case "requestfrom":
            $this->m_aryJoinRequestsFrom[] = $objNewShop; 
            $objJSON->aryToJSONOption( $this->m_aryJoinRequestsFrom, "leftsell_pm_joinrequestsfrom" );
            $objMarket = new leftsellMarket( true );
            $objMarket->PMLog( esc_html__( 'Market shop requests to join', 'leftsell' ) . ": " . $shopURL );
            $objMarket = null;
            break;
         case "requestto":
            $this->m_aryJoinRequestsTo[] = $objNewShop;
            $objJSON->aryToJSONOption( $this->m_aryJoinRequestsTo, "leftsell_pm_joinrequeststo" );
            break;
         case "pm_shops":
            if ( $objNewShop->m_intId == 0 ) {
               $objNewShop->GetShopValues( $objNewShop->m_strURL );
            }
            $this->m_aryShops[] = $objNewShop;
            $objJSON->aryToJSONOption( $this->m_aryShops, "leftsell_pm_shops" );
            $objMarket = new leftsellMarket( true );
            $objMarket->PMLog( esc_html__( 'New Market shop joined', 'leftsell' ) . ": " . $shopURL );
            $objMarket = null;
            break;
      }
      $objNewShop = null;
      $objJSON = null;
   }
   private function RemoveMarketShop( $type, $shopURL = "" ) {
      $aryNew = array();
      switch ( $type ) {
         case "requestto":
            if ( count( $this->m_aryJoinRequestsTo ) > 0 ) {
               foreach ( $this->m_aryJoinRequestsTo as $objShop ) {
                  if ( substr( $objShop->m_strURL, 0, strlen( $shopURL ) ) != $shopURL ) {
                     $aryNew[] = $objShop;
                  }
               }   
               $this->m_aryJoinRequestsTo = $aryNew;
            }
            $this->SaveMarketShops( "requestto" );
            break;
         case "requestfrom":
            if ( count( $this->m_aryJoinRequestsFrom ) > 0 ) {
               foreach ( $this->m_aryJoinRequestsFrom as $objShop ) {
                  if ( substr( $objShop->m_strURL, 0, strlen( $shopURL ) ) != $shopURL ) {
                     $aryNew[] = $objShop;
                  }
               }   
               $this->m_aryJoinRequestsFrom = $aryNew;
            }
            $this->SaveMarketShops( "requestfrom" );
            break;
         case "pm_shops":
            if ( count( $this->m_aryShops ) > 0 ) {
               foreach ( $this->m_aryShops as $objShop ) {
                  if ( substr( $objShop->m_strURL, 0, strlen( $shopURL ) ) != $shopURL ) {
                     $aryNew[] = $objShop;
                  } else {
                     $objShop->DisableAllProducts();
                  }
               }   
               $this->m_aryShops = $aryNew;
            }
            $this->SaveMarketShops( "pm_shops" );
            break;
      }
      $aryNew = null;
   }
   private function SaveMarketShops( $type ) {
      // save array due to type
      $objJSON = new leftsellJSON();
      switch ( $type ) {
         case "requestfrom":
            $objJSON->aryToJSONOption( $this->m_aryJoinRequestsFrom, "leftsell_pm_joinrequestsfrom" );
            break;
         case "requestto":
            $objJSON->aryToJSONOption( $this->m_aryJoinRequestsTo, "leftsell_pm_joinrequeststo" );
            break;
         case "pm_shops":
            $objJSON->aryToJSONOption( $this->m_aryShops, "leftsell_pm_shops" );
            $objMsg = new leftsellMessage( "pmchanged" );
            $objMsg->SendMessage( );
            break;
      }
      $objJSON = null;
   } 
   public function AddJoinRequest( $shopid, $shopURL ) {
      // private market: a shop connects
      $shopid  = sanitize_text_field( $shopid );   
      $shopURL = sanitize_url( $shopURL );   
      
      // shop is already in join requests?
      if ( !empty( $this->m_aryJoinRequestsFrom ) ) {
         foreach ( $this->m_aryJoinRequestsFrom as $shop ) {
            if ( $shop->m_intId == $shopid )   { 
               $this->RemoveMarketShop ( "requestfrom", $shop->m_strURL );
               return esc_html__( 'already requested - now removed', 'leftsell' ); 
            }
            if ( $shop->m_strURL == $shopURL ) { 
               $this->RemoveMarketShop ( "requestfrom", $shop->m_strURL );
               return esc_html__( 'already requested - now removed', 'leftsell' ); 
            }
         }
      }

      // shop is already in shops?
      if ( !empty( $this->m_aryShops ) ) {
         foreach ( $this->m_aryShops as $shop ) {
            if ( $shop->m_intId == $shopid )   { 
               $this->RemoveMarketShop ( "pm_shops", $shop->m_strURL );
               return esc_html__( 'already joined - now removed', 'leftsell' ); 
            }
            if ( $shop->m_strURL == $shopURL ) { 
               $this->RemoveMarketShop ( "pm_shops", $shop->m_strURL );
               return esc_html__( 'already joined - now removed', 'leftsell' ); 
            }
         }
      }
      
      // verify remote shop id and url
      $objMsg = new leftsellMessage( "verifyshop" );
      $objMsg->m_aryParams[ "shopid" ] = $shopid;
      $verify = $objMsg->SendMessage();
      if ( !is_array( $verify ) ) { 
         return esc_html__( 'server cannot verify shop', 'leftsell' ); 
      } else {
         if ( isset( $verify[ "blocked"] ) ) {
            if ( $verify[ "blocked"] == 1 ) { return esc_html__( 'shop is blocked', 'leftsell' ); }
         }
         if ( isset( $verify[ "enabled"] ) ) {
            if ( $verify[ "enabled"] != 1 ) { return esc_html__( 'shop is not active', 'leftsell' ); }
         }
      }

      // add shop to joinrequests-from
      $this->AddMarketShop ( "requestfrom", $shopid, $shopURL );
      return "OK";
   }
   public function ConnectPrivateShop( $newshopURL ) {
      $newshopURL = sanitize_text_field ( $newshopURL );
      $newshopURL = esc_js ( $newshopURL );
      
      // check shop error: myself
      if ( !function_exists( 'get_home_url' ) ) {  require_once ABSPATH . WPINC . '/link-template.php'; } 
      if ( get_home_url() == $newshopURL ) {
         return esc_html( 'Cannot connect to myself', 'leftsell' );
      }
      
      // local check if this shop is blocked
      $objlocalShop = new leftsellShop();
      if ( $objlocalShop->IsAllowedToJoinMarket() == false ) { 
         $objlocalShop->ActivateShop();
         return esc_html__( 'Problem with local shop: market communication failing. Please try again.', 'leftsell' ); 
      } else {
         return '<button onclick="var strCall = { \'call\': \'market_establish_private\', \'newshop\': \'' . esc_js( $newshopURL ) . '\'};
                     strCall = JSON.stringify( strCall );
                     leftsellAJAX_event( strCall, \'leftsell_newshop\', \'\', 0 );
                  ">' . esc_html__( 'Confirm connection', 'leftsell' ) . '</button> ';
      }
   }
   public function EstablishPrivateShop( $newshopURL ) {
      $newshopURL = sanitize_text_field ( $newshopURL );
      $newshopURL = esc_html ( $newshopURL );
      if ( substr( $newshopURL, strlen( $newshopURL ) - 1, 1 ) != '/' ) {
         $newshopURL = $newshopURL . '/';
      }
      $objMsg = new leftsellMessage( "joinrequest" );
      $result = $objMsg->SendMessage( $newshopURL . leftsellROUTE );
      if ( trim( $result ) == "OK" ) {
         // add shop to joinrequests-to
         $this->AddMarketShop( "requestto", 0, $newshopURL );
         return esc_html__( 'Join request successful. Please confirm on the other shop.' , 'leftsell' ) . 
                '<br /><br /><button onclick="location.reload()">' . esc_html__( 'Reload', 'leftsell' ) . '</button>';
      } else {
         return '<strong>' . $result . '</strong><br /><br /><button onclick="location.reload()">' . esc_html__( 'Reload', 'leftsell' ) . '</button>'; 
      }
   }
   public function CancelJoinRequest( $shopURL ) {
      // cancel a pending join request to a shop (whitch replies with RemoteCancelJoinRequest)
      $objMsg = new leftsellMessage( "canceljoinrequest" );
      $result = $objMsg->SendMessage( $shopURL . leftsellROUTE );
      if ( $result == "OK" ) {
         $this->RemoveMarketShop ( "requestto", $shopURL );
         return esc_html__( 'deleted', 'leftsell' );
      } else {
         return $result;
      }
   }
   public function RemoteCancelJoinRequest( $shopURL ) {
      // a shop with pending join request cancells the request
      if ( empty( $this->m_aryJoinRequestsFrom ) ) { return "OK"; }
      $this->RemoveMarketShop ( "requestfrom", $shopURL );
      return "OK";
   }
   public function AcceptJoin( $shopURL ) {
      // from local ajax: joining shop accepted for private market
      if ( empty( $this->m_aryJoinRequestsFrom ) ) { return esc_html( 'Something went wrong', 'leftsell' );  }
      $this->RemoveMarketShop ( "requestfrom", $shopURL );
      $this->AddMarketShop( "pm_shops", 0, $shopURL );
      $objMsg = new leftsellMessage( "joinrequestaccepted" );
      $objMsg->SendMessage( $shopURL . leftsellROUTE );
      $objMsg = null;
      return esc_html( 'Join accepted', 'leftsell' );
   }
   public function DeclineJoin( $shopURL ) {
      // from local ajax: joining shop declined for private market
      if ( empty( $this->m_aryJoinRequestsFrom ) ) { return "OK"; }
      $this->RemoveMarketShop ( "requestfrom", $shopURL );
      $this->RemoveMarketShop ( "pm_shops", $shopURL );
      $objMsg = new leftsellMessage( "joinrequestdeclined" );
      $objMsg->SendMessage( $shopURL . leftsellROUTE );
      $objMsg = null;
      return esc_html( 'Join request declined', 'leftsell' );
   }
   public function JoinRequestAccepted( $shopURL ) {
      // remote shop accepts join
      if ( empty( $this->m_aryJoinRequestsTo ) ) { return "OK"; }
      $this->RemoveMarketShop ( "requestto", $shopURL );
      $this->AddMarketShop( "pm_shops", 0, $shopURL );
      $objMarket = new leftsellMarket( true );
      $objMarket->PMLog( esc_html__( 'Market shop has accepted join request', 'leftsell' ) . ": " . $shopURL );
      $objMarket = null;
      return "OK";
   }
   public function JoinRequestDeclined( $shopURL ) {
      // remote shop declines join
      $this->RemoveMarketShop ( "requestto", $shopURL );
      $this->RemoveMarketShop ( "pm_shops", $shopURL );
      $objMarket = new leftsellMarket( true );
      $objMarket->PMLog( esc_html__( 'Market shop has declined join request', 'leftsell' ) . ": " . $shopURL );
      $objMarket = null;
      return "OK";
   }
   public function RemoveJoin( $shopURL ) {
      $objMsg = new leftsellMessage( "joinrequestdeclined" );
      $objMsg->SendMessage( $shopURL . leftsellROUTE );
      $objMsg = null;  
      $this->RemoveMarketShop ( "pm_shops", $shopURL );
      return "OK";
   }
   // shop functions after join ------------
   public function GetJoinedShop( $shopURL ) {
      // retrieve all necessary shop informations of joined shops
      if ( trim( $shopURL ) == "" ) { return false; }
      $objShopResult = new leftsellShop();
      $blnFound = false;
      foreach( $this->m_aryShops as $objShop ) {
         if ( substr( $objShop->m_strURL, 0, strlen( $shopURL ) ) == $shopURL ) {
            $objShopResult = $objShop;
            $blnFound = true;
         }
         if ( substr( $shopURL, 0, strlen( $objShop->m_strURL ) ) == $objShop->m_strURL ) {
            $objShopResult = $objShop;
            $blnFound = true;
         }
      }
      if ( $blnFound == false ) { 
         return false; 
      } else {
         return $objShopResult;
      }
   }

   // Market Products -----------------------------------------------------------------
   public function GetPMProducts() {
      // get all products from all shops in private market via REST
      $aryResult = array();
      $this->GetShops();
      if ( !empty( $this->m_aryShops ) ) {
         foreach ( $this->m_aryShops as $objShop ) {
            $aryList = $objShop->GetPMProductList();
            if ( !empty( $aryList ) ) {
               foreach ( $aryList as $aryProduct ) {
                  $aryResult[] = $aryProduct;
               }
            }
         }
      }
      return $aryResult;
   }
   public function PMInformProductUpdate( $wpid, $shopURL ) { 
      // send message to a subscribed shop: it shall fetch new values via REST
      $objMsg = new leftsellMessage( "PMupdateSubscribed" );
      $objShop = new leftsellShop();
      $objMsg->m_aryFields["publisher"] = $objShop->m_intId;
      $objMsg->m_aryFields["remotewpid"] = $wpid;
      $objMsg->SendMessage( $shopURL . leftsellROUTE );
      $objShop = null;
      $objMsg = null;
   }

} // end class leftsellMarket
