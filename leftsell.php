<?php
/**
 * Plugin Name:       LeftSell
 * Plugin URI:        https://leftsell.com/
 * Description:       Webseiten und Shop Boost: Produkte und Dienstleistungen verteilt verkaufen und anbieten
 * Version:           2.0.4
 * Requires at least: 5.2
 * Requires PHP:      5.4
 * Tested up to:      6.0
 * Stable tag:        6.0
 * Author:            Kundschaft Schweiz
 * Author URI:        https://kundschaft.ch/
 * Text Domain:       leftsell
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * LeftSell is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *  
 * LeftSell is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *  
 * You should have received a copy of the GNU General Public License
 * along with LeftSell. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 *  
**/

define ( "leftsellVERSION",    "2.0.4" );
define ( "leftsellGATE",       "https://api002.leftsell.com/?rest_route=/leftsellgate/v2/gate/" );
//define ( "leftsellGATE",       "https://127.0.0.1/dev/main/?rest_route=/leftsellgate/v2/gate/" );
define ( "leftsellROUTE", "?rest_route=/leftsell/v2/crafts/");
define ( "leftsellINCLUDE" ,   plugin_dir_url( __FILE__ ). 'includes/' );

// currently no background jobs due to WP PHP8 bug
if ( get_option( "leftsell_nobackground" ) == 1 ) {
   define ( "leftsellCRON", false );       // use direct calls for updates
} else {
   define ( "leftsellCRON", false );
}

// debugging
define ( "leftsellCONDEBUG", true );  // debug info

// load requirements ------------------------------------------------
if ( is_admin() ) {
   require_once ( plugin_dir_path( __FILE__ ) . 'admin/page-private-market.php' );
   require_once ( plugin_dir_path( __FILE__ ) . 'admin/page-tools.php' );
   require_once ( plugin_dir_path( __FILE__ ) . 'admin/page-com.php' );
}
require_once ( plugin_dir_path( __FILE__ ) . 'includes/ls_tools.php' );
require_once ( plugin_dir_path( __FILE__ ) . 'includes/ls_helper.php' );
require_once ( plugin_dir_path( __FILE__ ) . 'includes/ls_products.php' );
require_once ( plugin_dir_path( __FILE__ ) . 'includes/ls_comments.php' );
require_once ( plugin_dir_path( __FILE__ ) . 'includes/ls_markets.php' );
require_once ( plugin_dir_path( __FILE__ ) . 'includes/ls_messages.php' );

// translations      ------------------------------------------------------------------
function leftsell_load_plugin_textdomain() {
   load_plugin_textdomain( 'leftsell', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'leftsell_load_plugin_textdomain' );

// styles and scripts -----------------------------------------------------------------
function leftsell_style_script() {
   wp_enqueue_style ( 'leftsellcss',  plugin_dir_url( __FILE__ ). 'includes/files/leftsell.css', false, leftsellVERSION, 'all');
   wp_enqueue_script( 'leftsellajax', plugin_dir_url( __FILE__ ). 'includes/files/leftsell_ajax.js',  false, leftsellVERSION, 'all');
   wp_enqueue_script( 'leftselljs',   plugin_dir_url( __FILE__ ). 'includes/files/leftsell.js',  false, leftsellVERSION, 'all');
   wp_localize_script('leftsellajax', 'leftsell', array( 'pluginsUrl' => plugin_dir_url( __FILE__ )));
}
add_action( 'admin_enqueue_scripts', 'leftsell_style_script' );

// start REST route -------------------------------------------------------------------
add_action( 'rest_api_init', function () { 
   // required for news / bugreports / featurerequest 
   register_rest_route( 'leftsell/v2', '/crafts', array(
      'methods' => 'GET',
      'callback' => 'leftsellRESTHandler',
      'permission_callback' => function () {
         $objShop = new leftsellShop();
         switch ( $objShop->m_intEnabled ) {
            case "1": $objShop = null; return '__return_true';
            default:  $objShop = null; return '__return_false';
         }
      }
   ) );
} );
add_filter( 'https_local_ssl_verify', '__return_false' );
add_filter( 'backwpup_cacert_bundle', '__return_false' );

// check for message defintions client version <=> server version
leftSellGetMsgDefinitions();

// menus ------------------------------------------------------------------------------
function leftsell_plugin_create_menu() {
   $imagedata = esc_html( file_get_contents( plugin_dir_path( __FILE__ ) .  'includes/images/leftsell-icon.src' ) );
   add_menu_page( 'LeftSell', 'LeftSell', 'manage_options', 'leftsell', 'leftsell_page_private_market', $imagedata, 56 );
   $objMarket = new leftsellMarket( true );
   $pmindicator = "";
   if ( is_array( $objMarket->m_aryJoinRequestsFrom ) ) {
      if ( count ( $objMarket->m_aryJoinRequestsFrom ) > 0 ) {
         $pmindicator = '<span style="margin-left:5px; background-color:red; color: white; line-height: 1.6; border-radius:50px; padding:0px 7px">' .
                        count ( $objMarket->m_aryJoinRequestsFrom ) . '</span>';
      }
   }
   add_submenu_page(
      'leftsell',                                        // parent slug
      esc_html__( 'Private Market', 'leftsell' ),        // page title
      esc_html__( 'Private Market', 'leftsell' ) . $pmindicator,        // menu title
      'manage_options',                                  // capability
      'leftsell',                                        // slug
      'leftsell_page_private_market'                     // callback
   ); 
   add_submenu_page(
      'leftsell',                                        // parent slug
      esc_html__( 'LeftSell Options', 'leftsell' ),      // page title
      esc_html__( 'Options', 'leftsell' ),               // menu title
      'manage_options',                                  // capability
      'leftsell-tools',                                  // slug
      'leftsell_page_tools'                              // callback
   ); 
   add_submenu_page(
      'leftsell',                                        // parent slug
      esc_html__( 'Take part of LeftSell', 'leftsell' ), // page title
      esc_html__( 'Communicate', 'leftsell' ),           // menu title
      'manage_options',                                  // capability
      'leftsell-com',                                    // slug
      'leftsell_page_com'                                // callback
   ); 
}
add_action( 'admin_menu', 'leftsell_plugin_create_menu' );

// AJAX -------------------------------------------------------------
function leftsellAJAX_RegisterScripts() {
   wp_register_script( 'leftsellAJAX', false , array( 'jquery' ), leftsellVERSION, true );
   //wp_register_script( 'leftsellAJAX', true , array( 'jquery' ), leftsellVERSION, true );
   wp_enqueue_script ( 'leftsellAJAX' );
   wp_localize_script( 'leftsellAJAX', 'leftsellAJAX_AJAX', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) , 'security' => wp_create_nonce('leftsellAJAX_callback') ) );
}
add_action( 'admin_enqueue_scripts',       'leftsellAJAX_RegisterScripts' );
add_action( 'wp_ajax_leftsellAJAX_action', 'leftsellAJAX_callback' );
function leftsellAJAX_callback() { 
   if ( wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'leftsellAJAX_callback' ) == false) { 
      esc_html__( "403", "leftsell" );
      wp_die();
   }
   if ( !isset( $_POST[ "params" ] ) ) { esc_html__( "4032", "leftsell" ); wp_die(); }
   $params = sanitize_text_field( $_POST[ "params" ] );
   $params = str_replace("%20", " ", $params);
   echo leftsellHandleAJAX( $params );
   wp_die();
}
function leftsellHandleAJAX( $param ) { 
   $objJSON = new leftsellJSON();
   $aryParams = $objJSON->FromJSON( $param );
   if ( $aryParams == false ) { return esc_html__( "404", "leftsell" ); }
   if ( !isset( $aryParams["call"]) ) { return esc_html__( "405", "leftsell" ); }
   $call = sanitize_text_field( $aryParams["call"] );
   switch ( $call ) {
      // Tools ------------------------------------------------------------------------
      case "changeoption":
         if ( !isset( $aryParams[ "option" ]) ) { return esc_html__( "406", "leftsell" ); }
         if ( $aryParams[ "option" ] == "" )    { return esc_html__( "407", "leftsell" ); }
         $option = sanitize_text_field( $aryParams[ "option" ] );
         $value = get_option( $option );
         $value = sanitize_text_field( $value );
         $deprecated = null;
         $autoload = 'no';
         if ( $value === false ) { 
            add_option( $option, "1", $deprecated, $autoload );
         }
         if ( $value == 1 ) { 
            update_option( $option, "0", $deprecated, $autoload );
         } else {  
            update_option( $option, "1", $deprecated, $autoload );
         }
         $objUI = new leftsellUI();
         return $objUI->GetOptionSwitch( $option );
         break;
      case "changetextoption": 
         if ( !isset( $aryParams[ "option" ]) ) { return esc_html__( "506", "leftsell" ); }
         if ( !isset( $aryParams[ "value" ]) )  { return esc_html__( "507", "leftsell" ); }
         $option = sanitize_text_field( $aryParams["option"] );
         if ( $option == "" ) { return esc_html__( "508", "leftsell" ); }
         $value = sanitize_text_field( $aryParams["value"] );
         $value = html_entity_decode( $value );
         $oldvalue = get_option ( $option );
         $deprecated = null;
         $autoload = 'no';
         if ( $oldvalue === false ) { 
            add_option( $option, $value, $deprecated, $autoload );
         } else {
            update_option( $option, $value, $deprecated, $autoload );
         }
         return "OK";
         break;
      case "clearoption": 
         if ( isset( $aryParams[ "data" ] ) ) {
            $objJSON = new leftsellJSON();
            $objJSON->ClearOption( $aryParams[ "data" ] );
            $objJSON = null;
         }
         return "OK";
         break;
      case "creategooglesitemap":
         leftsell_CreateGoogleXML();
         return "OK";
         break;
      case "creategoogleproductmap":
         return leftsell_CreateGoogleProductMap();
         break;
      case "sendmessage":
         return leftSellSendMessage( $aryParams );
         break;
      case "reportbug":
         $objBug = new leftsellCom();
         return $objBug->ReportBug( $aryParams );
         break;
      case "requestfeature":
         $objFeature = new leftsellCom();
         return $objFeature->RequestFeature( $aryParams );
         break;

      // private market join ----------------------------------------------------------
      case "market_connect_private":   // connection try to a new shop in private market 
         if ( isset ( $aryParams[ "newshop" ] ) ) {
            $objMarket = new leftsellMarket( true );
            return $objMarket->ConnectPrivateShop( sanitize_text_field( esc_url( $aryParams[ "newshop" ] ) ) );
         } else {
            return "Market Error 2010";
         }
         break;
      case "market_establish_private": // following the process to market_connect_private
         if ( isset ( $aryParams[ "newshop" ] ) ) {
            $objMarket = new leftsellMarket( true );
            return $objMarket->EstablishPrivateShop( sanitize_text_field( esc_url( $aryParams[ "newshop" ] ) ) );
         } else {
            return "Market Error 2011";
         }
         break;
      case "canceljoin":               // cancel a private market join request
         if ( !isset( $aryParams[ "data" ] ) ) { return "error 20019"; }
         $objMarket = new leftsellMarket( true );
         return $objMarket->CancelJoinRequest( sanitize_text_field( esc_url( $aryParams[ "data" ] ) ) );
         $objMarket = null;
         break;
      case "acceptjoin":               // accept a connection from a shop for private market
         if ( !isset( $aryParams[ "data" ] ) ) { return "error 20020"; }
         $objMarket = new leftsellMarket( true );
         return $objMarket->AcceptJoin( sanitize_text_field( esc_url( $aryParams[ "data" ] ) ) );
         $objMarket = null;
         break;
      case "declinejoin":              // decline a connection from a shop for private market
         if ( !isset( $aryParams[ "data" ] ) ) { return "error 20021"; }
         $objMarket = new leftsellMarket( true );
         return $objMarket->DeclineJoin( sanitize_text_field( esc_url( $aryParams[ "data" ] ) ) );
         $objMarket = null;
         break;
      case "removejoin":               // remove a shop from private market
         if ( !isset( $aryParams[ "data" ] ) ) { return "error 20022"; }
         $objMarket = new leftsellMarket( true );
         return $objMarket->RemoveJoin( sanitize_text_field( esc_url( $aryParams[ "data" ] ) ) );
         $objMarket = null;
         break;

      // private market sharing -------------------------------------------------------
      case "unshare_private":          // remove share from private market 
         if ( isset( $aryParams[ "data" ] ) ) {
            $objProduct = new leftsellProduct();
            $post = get_post( sanitize_text_field( $aryParams[ "data" ] ) );
            if ( empty ($post ) ) { return "Error 20001"; }
            $objProduct->fromPost( $post );
            return $objProduct->UnSharePrivate();
         }  
         break;
      case "share_private":            // share to private market 
         if ( isset( $aryParams[ "data" ] ) ) {
            $objProduct = new leftsellProduct();
            $post = get_post( sanitize_text_field( $aryParams[ "data" ] ) );
            if ( empty ($post ) ) { return "Error 20001"; }
            $objProduct->fromPost( $post );
            return $objProduct->SharePrivate();
         }  
         break;
      case "pmsubscribe":              // subscribe to a product of private market
        if ( isset( $aryParams[ "wpid" ] ) and isset( $aryParams[ "shopurl" ] ) and isset( $aryParams[ "shopid" ] ) ) {
            $objProduct = new leftsellProduct();
            $objProduct->FetchRemote( $aryParams[ "shopid" ], $aryParams[ "wpid" ] );
            return esc_html__( "Subscription requested", "leftsell");
         } else {
            return "Error 20100";
         }
         break;
      case "pmunsubscribe":            // unsubscribe a privat market product
         if ( isset ( $aryParams[ "wpid" ] ) and isset ( $aryParams[ "shopid" ] ) ) {
            $objProduct = new leftsellProduct();
            return $objProduct->PMUnSubscribe( $aryParams[ "shopid" ], $aryParams[ "wpid" ] );
         } else {
            return "Error 20220";
         }
         break;
      case "pmrequestupdate":         // manual update of pm product requested
         if ( isset ( $aryParams[ "externalwpid" ] ) and isset ( $aryParams[ "shopid" ] ) ) {
            $objProduct = new leftsellProduct();
            $objProduct->FetchRemote( $aryParams[ "shopid" ], $aryParams[ "externalwpid" ] );
            return esc_html__( "Update requested", "leftsell");
         } else {
            return "Error 20220";
         }
         break;
      case "pmimport":                 // import a product from private market
         if ( isset( $aryParams[ "wpid" ] ) and isset( $aryParams[ "shopurl" ] ) ) {
            $objProduct = new leftsellProduct();
            return $objProduct->PMImport( $aryParams[ "shopurl" ], $aryParams[ "wpid" ] );
         }
         break;
      case "deleteimportedproduct":    // delete an imported product
         if ( isset( $aryParams[ "wpid" ] ) and isset( $aryParams[ "shopid" ] ) ) {
            $objProduct = new leftsellProduct();
            return $objProduct->DeleteImported( $aryParams[ "wpid" ], $aryParams[ "shopid" ] );
         }
         break;
      default: return esc_html__( "999", "leftsell" );
   }
}

// plugin activation ------------------------------------------------------------------
function leftsell_activation() {
   $objShop = new leftsellShop();
   $objShop->ActivateShop();
   $objShop = null;
}
register_activation_hook( __FILE__, 'leftsell_activation' );

// plugin deactivation ----------------------------------------------------------------
function leftsell_deactivation() {
   if ( class_exists ( "leftsellMessage" ) ) {
      $objMsg = new leftsellMessage( "plugin_deactivation" );
      $result = $objMsg->SendMessage();
      $objMsg = null;
      if ( intval( $result != "OK" ) ) {
         wp_die( $result );
      } else {
         $deprecated = null;
         $autoload = 'no';
         update_option ( "leftsell_shop_enabled", 0 , $deprecated, $autoload );
      }
   }
}
register_deactivation_hook( __FILE__, 'leftsell_deactivation' );

// plugin uninstall -------------------------------------------------------------------
function leftsell_uninstall() {
   if ( class_exists ( "leftsellMessage" ) ) {
      $objMsg = new leftsellMessage( "plugin_deactivation" );
      $objMsg->SendMessage();
      $objMsg = null;
   }
   // remove all options
   delete_option( "leftsell_additionalheader" );
   delete_option( "leftsell_adminmessages" );
   delete_option( "leftsell_adminthanks" );
   delete_option( "leftsell_bugregports" );
   delete_option( "leftsell_description" );
   delete_option( "leftsell_featurerequests" );
   delete_option( "leftsell_googlebreadcrumbs" );
   delete_option( "leftsell_googleorganisation" );
   delete_option( "leftsell_googleproductxml" );
   delete_option( "leftsell_googlesitemap" );
   delete_option( "leftsell_keywords" );
   delete_option( "leftsell_messagedefintions" );
   delete_option( "leftsell_messageupdate" );
   delete_option( "leftsell_ogimage" );
   delete_option( "leftsell_ogtitle" );
   delete_option( "leftsell_ogtype" );
   delete_option( "leftsell_ogurl" );
   delete_option( "leftsell_removeshortlinks" );
   delete_option( "leftsell_robotstext" );
   delete_option( "leftsell_shop_adult" );
   delete_option( "leftsell_shop_blocked" );
   delete_option( "leftsell_shop_brand" );
   delete_option( "leftsell_shop_enabled" );
   delete_option( "leftsell_shop_gtin" );
   delete_option( "leftsell_shop_id" );
   delete_option( "leftsell_shop_itemcondition" );
   delete_option( "leftsell_shop_mpn" );
   delete_option( "leftsell_shop_sku" );
   delete_option( "leftsell_titletag" );
   delete_option( "leftsell_woo_storealerts" );
   delete_option( "leftsell_woo_themesupport" );
   delete_option( "leftsell_shop_description_tab_heading" );
   delete_option( "leftsell_shop_service" );
   delete_option( "leftsell_footer" );
   delete_option( "leftsell_comments_author" );
   delete_option( "leftsell_comments_email" );
   delete_option( "leftsell_comments_url" );
   delete_option( "leftsell_comments_cookies" );
   delete_option( "leftsell_disablexmlrpc" );
   delete_option( "leftsell_pageheaders" );
   // V2 private market
   delete_option( "leftsell_privatemarket" );
   delete_option( "leftsell_privatemarket_default_sanitize" );
   delete_option( "leftsell_pm_joinrequestsfrom" );
   delete_option( "leftsell_pm_joinrequeststo" );
}
register_uninstall_hook( __FILE__, 'leftsell_uninstall' );
?>