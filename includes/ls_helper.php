<?php
/**
 * Purpose:           Helper for JSON and UI functions like WP Options and repeating UI parts
 * Function URI:      https://leftsell.com/
 * Version:           2.0.2
 * Author:            Kundschaft Schweiz
 * Author URI:        https://kundschaft.ch/ 
 **/
defined( 'ABSPATH' ) || exit;


// Background Jobs
add_action( 'lefsellEvent', 'lefsellBackgroundJob', 10 , 3);
function lefsellBackgroundJob( $jobname, $p1 = "", $p2 = "" ) {
   switch ( $jobname ) {
      case "PMnotifyProductUpdate":
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( esc_html__( 'Sending product update to', 'leftsell' ) . ": " . $p2 . " (" . $p1 . ")" );
         $objMarket->PMInformProductUpdate( $p1, $p2 );
         $objMarket = null;
         break;
      // images / gallery -------------------------------------------------------------
      case "FetchProductImage":
         $objProduct = new leftsellProduct();
         $objProduct->GetFeaturedImage( $p1, $p2 );
         $objProduct = null;
         break;
      case "FetchProductGallery":
         $objProduct = new leftsellProduct();
         $objProduct->GetGallery( $p1, $p2 );
         $objProduct = null;
         break;
      // comments ---------------------------------------------------------------------
      case "SendCommentToOriginator":
         $objComment = new leftsellComment();
         $objComment->SendToOriginator( $p1, $p2 );
         $objComment = null;
         break;
      case "DeleteComment":
         wp_delete_comment( $p1 );
         break;
   }
}

// divers helpers
function leftsellfixUTF8( $text ) {
   $text = str_replace ( "&auml;", "ae", $text );
   $text = str_replace ( "&ouml;", "oe", $text );
   $text = str_replace ( "&uuml;", "ue", $text );
   $text = str_replace ( "&Auml;", "Ae", $text );
   $text = str_replace ( "&Ouml;", "Oe", $text );
   $text = str_replace ( "&Uuml;", "Ue", $text );
   $text = str_replace ( "&copy;", " ", $text );

   $umlaute = array( "/ä/","/ö/","/ü/","/Ä/","/Ö/","/Ü/","/ß/" );
   $replace = array( "ae" ,"oe" ,"ue" ,"Ae" ,"Oe" ,"Ue" ,"ss" );
   return preg_replace( $umlaute, $replace, $text );  
}
function leftsellIsYes( $value ) {
   $blnYes = false;
   switch ($value) {
      case 0:       $blnYes = false; break;
      case "":      $blnYes = false; break;
      case "no":    $blnYes = false; break;
      case "false": $blnYes = false; break;
      case 1:       $blnYes = true;  break;
      case "yes":   $blnYes = true;  break;
      case "true":  $blnYes = true;  break;
      case true:    $blnYes = true;  break;
      default: $blnYes = false; break;
   }
   return $blnYes;
}
function leftsellDebug( $text, $array = null ) {
   // debug function
   if ( leftsellCONDEBUG ) {
      try {
         $logfile = plugin_dir_path( __FILE__ ) . '../../../../leftsell-log.txt';
         if ( validate_file( $logfile ) == true ) {
            $fh = fopen( $logfile, 'a');
            if ( $array == null ) {
               fwrite( $fh, "\r\n" . $text. "\r\n" );
            } else {
               if ( is_array( $array ) ) {
                  fwrite( $fh, "\r\n" . $text . " - ". var_export( $array,true ) . "\r\n" );
               } else {
                  fwrite( $fh, "\r\n" . $text . " - ? no data\r\n" );
               }
            }
            fclose( $fh );
         } 
      } catch (Exception $e) {
         return;
      }
   }
}

class leftsellJSON  {
   /**
   *
   * Helper for JSON 
   * properly convert arrays to JSON
   * store and read wp_options with format JSON
   * 
   **/
   function ToJSON( $ary ) { 
      // return json string from array
      if ( !is_array( $ary ) ) { return ""; }
      return json_encode( $ary, JSON_UNESCAPED_UNICODE );
   }
   function aryFromJSONOption( $option, $blnNative = false ) { 
      // return array from JSON values stored as wp_option
      $option = sanitize_text_field( $option );
      $json = get_option( $option );
      return $this->FromJSON( $json, $blnNative );
   }
   function aryToJSONOption( $ary, $option ) {
      // convert an array to a JSON string and put it into wp_option
      $option = sanitize_text_field( $option );
      if ( !is_array( $ary ) ) { return ""; }
      $this->ToOption( $this->ToJSON( $ary ), $option );
   }
   function FromJSON( $json, $blnNative = false ) { 
      // return array from JSON string
      $jsonsan = sanitize_text_field( $json );
      $jsonstripped = stripslashes( $jsonsan ); 
      $aryResult = json_decode( $jsonstripped, true );
      if ($aryResult == null) {
         // try native
         if ( $blnNative ) {
            $aryResult = json_decode( $json, true );
            if ($aryResult == null) {
               return array();
            } else {
               return $aryResult;
            }
         } else {
            return array();
         }
      } else {
         return $aryResult;
      }
   }
   function ToOption($strJSON, $strOptionName) {
      // put a JSON string into a wp_option
      $strOptionName = sanitize_text_field( $strOptionName );
      if ( trim( $strOptionName ) == "" ) { return; }
      $deprecated = null;
      $autoload = 'no';
      update_option( $strOptionName, sanitize_text_field( $strJSON ), $deprecated, $autoload );
   }
   function ClearOption( $option ) {
      if ( substr( $option, 0, 8 )  != 'leftsell' ) { return false; }
      $oldValue = get_option( $option );
      if ( empty( $oldValue ) ) { return false; }
      $deprecated = null;
      $autoload = 'no';
      update_option( $option, '', $deprecated, $autoload );
   }
} // end class leftsellJSON


class leftsellUI {
   /**
   *
   * Helper for repeating UI functions 
   * 
   **/
   function GetOptionSwitch( $option ) {
      // get enabled / disabled switch for a wp_option
      $option = sanitize_text_field( $option );
      if ( trim( $option ) == "" )  { return ""; }
      $aryCall = array( "call" => "changeoption", "option" => $option );
      $objJSON = new leftsellJSON();
      $call    = $objJSON->ToJSON( $aryCall );
      $objJSON = null;
      $image   = 'disabled.png';
      if ( get_option( $option ) == 1 ) { $image = 'enabled.png'; }
      return '<div id="' . esc_html( $option ) . '">
              <img onclick="leftsellAJAX_event(\'' . esc_js( $call ) . '\', \'' . esc_js( $option ) . '\' , \'\' );"  
              class="leftsell_check" src="' . esc_html( leftsellINCLUDE ) . 'images/' . esc_html( $image ) . '" /></div>';
   }
   function GetOptionTextBox( $option, $title, $description ) {
      // get a input field for a wp_option containing text as value
      $buffer = '';
      $option = sanitize_text_field( $option );
      $title = sanitize_text_field( $title );
      $description = sanitize_text_field( $description );
      if ( trim( $option ) == "" ) { return ""; }
      $value = get_option( $option );
      if ( $value == false ) { $value = ''; }
      $buffer .= '<p><strong>' . esc_html( $title ) . '</strong><br />' . esc_html( $description ) . '</p>';
      $buffer .= '<input id="' . esc_html( $option ) . '" 
                     class="leftsell_textbox" type="text" 
                     value="' . esc_html( $value ) . '" 
                     onkeydown="document.getElementById(\'btn' . esc_js( $option ) . '\').innerHTML = \'' . esc_html__( 'Save', 'leftsell' ) .'\';" />';
      $buffer .= '<button class="button button-primary" id="btn' . esc_html( $option ) . '" 
                     onclick="SaveOptionTextBox_' . esc_js( $option ) . '()">' . esc_html__( 'Save', 'leftsell' ) . 
                  '</button>';
      $buffer .= '<script type="text/javascript">
                  function SaveOptionTextBox_' . esc_js( $option ) . '() {
                     var newvalue = document.getElementById("' . esc_js( $option ) . '").value;
                     var strCall = { "call":    "changetextoption", 
                                     "option":  "' . esc_js( $option ) . '", 
                                     "value":   newvalue };
                     strCall = JSON.stringify( strCall );
                     leftsellAJAX_event( strCall, "btn' . esc_js( $option ) . '" , "" );
                  }
                  </script>';
      return $buffer;
   }
   function BuildNav( $aryTabs, $defaultTab ) {
      // build a navi for an admin page
      if ( !is_array( $aryTabs ) ) { return ""; }
      $defaultTab = sanitize_text_field( $defaultTab );
      $buffer = '<ul id="leftsell_nav" aria-label="Secondary menu">';
      $curTab = $defaultTab;
      if ( isset( $_GET[ "tab" ] ) )  { $curTab = sanitize_text_field( $_GET["tab"] ); }
      if ( !isset( $_GET[ "page" ]) ) { return ""; }
      foreach ( $aryTabs as $key=>$description ) {
         $tabName = 'tab' . sanitize_text_field( $key );
         $tabclass = '';
         if ($tabName == $curTab) {
            $tabclass = ' class="tabactive" ';
         } 
         $buffer .= '<li ' . $tabclass . '>';  // do not escape, can be empty
         $buffer .= '<a id="cmd' . esc_html( sanitize_text_field( $key ) ) . '" 
                     href="' . admin_url() . 'admin.php?page=' . 
                     esc_html( sanitize_text_field( $_GET[ "page" ] ) ) . 
                     '&tab=' . esc_html( $tabName ) . '">';
         $buffer .= esc_html( sanitize_text_field( $description ) );
         $buffer .= '</a></li>';
      }
      $buffer .= '</ul>';
      return $buffer;
   }
   function BuildAjaxButton( $call, $data, $targetid, $caption, $action='' ) {
      $call     = sanitize_text_field( $call );
      $targetid = sanitize_text_field( $targetid );
      $caption  = sanitize_text_field( $caption );
      $action   = esc_js( sanitize_text_field( $action ) );
      
      if ( is_array( $data ) ) {
         $buffer = '<button onclick="leftsellAJAX_event( JSON.stringify( { \'call\':\''.$call.'\',';
         foreach ( $data as $key => $value ) {
            $buffer .= '\'' . sanitize_text_field( $key ) . '\':\'' . sanitize_text_field( $value ) . '\',';
         }
         $buffer .= '} ), \'' . $targetid. '\' , \'' . $action . '\' );">' .
                     $caption . '</button>';
      } else {
         $data = sanitize_text_field( $data );
         $buffer = '<button onclick="leftsellAJAX_event( JSON.stringify( { \'call\':\''.$call.'\', \'data\':\'' .
                     $data . '\'} ), \'' . $targetid. '\' , \'' . $action . '\' );">' .
                     $caption . '</button>';
      }
      return $buffer;
   }
} // end class leftsellUI


if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class leftsell_List_Table_Column {
   /**
   *
   * Helper for WP_List_Table
   * 
   **/
   public $m_strFieldName;
   public $m_strTitle;
   public $m_blnSortable;

   function __construct ( $strFieldName, $strTitle, $blnSortable ) {
      $this->m_strFieldName = $strFieldName;
      $this->m_strTitle = $strTitle;
      $this->m_blnSortable = $blnSortable;
   }
}

class leftsell_Products_List_Table extends WP_List_Table {
   public  $m_aryData;
   public  $m_aryBulkActions;
   private $m_aryColumns;
   private $m_intPerPage;
   public $m_blnOwnProducts = false;

   // manage from outside
   public function AddColumn( $objColumn ) {
      $this->m_aryColumns[] = $objColumn;
   }
   function get_columns() {
      $columns = array ( 'cb'=> '<input type="checkbox" />' );
      foreach( $this->m_aryColumns as $objColumn ) {
         $columns[ $objColumn->m_strFieldName ] = $objColumn->m_strTitle;
      }
      return $columns;
   }
   function get_sortable_columns() {
      $sortable_columns = array();
      foreach( $this->m_aryColumns as $objColumn ) {
         if ( $objColumn->m_blnSortable ) {
            $sortable_columns[ $objColumn->m_strFieldName ] = array( $objColumn->m_strFieldName, false );
         }
      }
      return $sortable_columns;
   }
   function __construct( $perpage = 20 ) {
      global $status, $page;
      echo '<h1>'.$page.'</h1>';
      $this->m_intPerPage = $perpage;
      // Set parent defaults
      parent::__construct( array(
         'singular'  => esc_html__( 'product', 'leftsell' ),
         'plural'    => esc_html__( 'products', 'leftsell' ),
         'ajax'      => false
      ) );
   }

   // Column Functions ----------------------------------------------------------------
   function column_title( $item ) { 
      // Build column title
      if ( !is_array( $item ) )          { return esc_html( santitize_text_field( $item ) ); }
      if ( !isset( $item[ "wpid" ] ) )   { return ""; }
      $style = 'color:grey';
      $buffer = '';
      if ( $this->m_blnOwnProducts == true ) {
         // tab Own Products (Publish) ---------------------------------------------------------
         $objProduct = new leftsellProduct();
         $objProduct->fromWPId( $item[ "wpid" ] );
         if ( $objProduct->IsShared() ) {
            $style = 'color: #006799';
         }
         if ( $objProduct->IsImported() ) {
            $style = "color: #009963";
         }
         $buffer = '<a title="' . esc_html__( 'Edit', 'leftsell' ) . '" style="font-weight:bold; ' . $style . '" href="post.php?post=' . $item[ 'wpid' ] . '&action=edit">' . 
                     get_the_post_thumbnail( $item[ 'wpid' ], "leftsellpreview" ) . esc_html( $item[ 'title' ] ) . '</a><br />' .
                     '<span style="' . $style . '">' . strip_tags( $objProduct->fields[ 'shortdescription' ] ) . '</span>';
         $objProduct = null;
      } else {
         // tab On Market (Subscribe or Import) ---------------------------------------------------------
         if ( !isset( $item[ "shopid" ] ) )           { return ""; }
         if ( !isset( $item[ "shortdescription" ] ) ) { return ""; }
         $objProduct = new leftsellProduct();
         $objProduct->GetFromExternalId( $item[ "shopid" ], $item[ "wpid" ] );
         if ( $objProduct->IsProduct() ) {
            // corresponding local product exists
            if ( $objProduct->IsSubscribedFrom( $item[ "shopid" ], $item[ "wpid" ] ) ) {
               $style = 'color: #006799';
            }
            if ( $objProduct->IsImported() ) {
               $style = "color: #009963";
            }
            $buffer = '<a title="' . esc_html__( 'Edit', 'leftsell' ) . '" style="font-weight:bold; ' . $style . '" href="post.php?post=' . $objProduct->fields[ 'wpid' ] . '&action=edit">' . 
                     get_the_post_thumbnail( $objProduct->fields[ 'wpid' ], "leftsellpreview" ) . esc_html( $item[ 'title' ] ) . '</a><br />' .
                     '<span style="' . $style . '">' . $objProduct->fields[ 'shortdescription' ] . '</span>';
            $objProduct = null;
         } else {
            // product not existing locally
            $buffer = '<span style="font-weight:bold; ' . $style . '">' . 
                        $item[ 'title' ] . ' </span><br /><span style="' . $style . '">' . $item[ 'shortdescription' ] . '</span>';
         }
      }
      return $buffer;
   }
   function column_subscribedby( $item ) {
      $objJSON = new leftsellJSON();
      $arySubscriptions = $objJSON->FromJSON( $item[ "pm_subscribedby" ] );
      if ( count( $arySubscriptions ) == 0 ) {
         return "";
      } else {
         $info = '';
         $info = '';
         foreach( $arySubscriptions as $subscription ) {
            if ( intval( $subscription ) > 0 ) {
               $objMarket = new leftsellMarket( true );
               if ( isset( $objMarket->m_aryShops[ $subscription ] ) ) {
                  $info .= $objMarket->m_aryShops[ $subscription ]->m_strURL . "\n";
               }
               $objMarket = null;
            }
         }
         return '<span style="cursor:pointer" onclick="alert(\'' . esc_js( $info ) . '\');" title="' .  esc_html( $info ) . '">' . count( $arySubscriptions ) . '</span>';
      }
   }
   function column_type( $item ) {
      if ( !is_array( $item ) ) { return ""; }
      if ( isset( $item[ "isservice" ] ) ) {
         if ( $item[ "isservice" ] == 1 ) { 
            return esc_html__( 'Service', 'leftsell' ); 
         } else {
            return esc_html__( 'Product', 'leftsell' ); 
         }
      }
   }
   function column_subscribe( $item ) {
      if ( !is_array( $item ) ) { return ""; }
      if ( !isset( $item[ "wpid" ] ) ) { return ""; }
      if ( !isset( $item[ "shopid" ] ) ) { return ""; }
      $objUI = new leftsellUI();
      $objProduct = new leftsellProduct();
      $objProduct->GetFromExternalId( $item[ "shopid" ], $item[ "wpid" ] );
      if ( $objProduct->IsProduct() ) {
         // corresponding local product exists
         if ( $objProduct->IsImported() ) { return ""; }
         if ( $objProduct->IsSubscribedFrom( $item[ "shopid" ], $item[ "wpid" ] ) ) {
            // show unsubscribe
            return '<span id="pmrequestupdate' . $item[ "wpid" ] . '">'. 
                     $objUI->BuildAjaxButton( "pmrequestupdate", 
                                             array( "externalwpid" => $objProduct->fields[ "externalwpid" ], "shopid" => $item[ "shopid" ] ), 
                                             'pmrequestupdate' . $item[ "wpid" ], esc_html__( 'Update', 'leftsell' ), 'reload' ).
                     
                     '</span> '.
                     '<span id="pmunsubscribe' . $item[ "wpid" ] . '">'. 
                     $objUI->BuildAjaxButton( "pmunsubscribe", 
                                           array( "wpid" => $item[ "wpid" ], "shopid" => $item[ "shopid" ] ), 
                                           'pmunsubscribe' . $item[ "wpid" ], esc_html__( 'Unsubscribe', 'leftsell' ), 'reload' ).
                   '</span> '
                   ;
         } else {
            return esc_html__( 'Undefined error 20501', 'leftsell' );
         }
      } else {
         // product not existing locally
         // show subscribe
         $id = $item[ "shopid" ] . $item[ "wpid" ];
         return '<div id="pmsubscribe' . $id . '">'. 
                  $objUI->BuildAjaxButton( "pmsubscribe", 
                                          array( "wpid" => $item[ "wpid" ], "shopurl" => $item[ "shopurl" ], "shopid" => $item[ "shopid" ] ), 
                                          'pmsubscribe' . $id, esc_html__( 'Subscribe', 'leftsell' ), 'reload' ).
                '</div>';
      }
   }
   function column_import( $item ) {
      if ( !is_array( $item ) ) { return ""; }
      if ( !isset( $item[ "wpid" ] ) ) { return ""; }
      if ( !isset( $item[ "shopid" ] ) ) { return ""; }
      $objUI = new leftsellUI();
      $objProduct = new leftsellProduct();
      $objProduct->GetFromExternalId( $item[ "shopid" ], $item[ "wpid" ] );
      if ( $objProduct->IsProduct() ) {
         // corresponding local product exists
         if ( $objProduct->IsSubscribedFrom( $item[ "shopid" ], $item[ "wpid" ] ) == true ) { return ''; }
         if ( $objProduct->IsImported() == true ) {
            return '<div id="deleteproduct' . $objProduct->fields[ "wpid" ] . '">'. 
                  $objUI->BuildAjaxButton( "deleteimportedproduct", 
                                          array( "wpid" => $objProduct->fields[ "wpid" ], "shopid" => $objProduct->fields[ "shopid" ] ), 
                                          'deleteproduct' . $objProduct->fields[ "wpid" ], 
                                          esc_html__( 'Delete', 'leftsell' ), 'reload' ).
                  '</div>';
         }
      } else {
         // product not existing locally
         return '<div id="pmimport' . $item[ "wpid" ] . '">'. 
                  $objUI->BuildAjaxButton( "pmimport", 
                                          array( "wpid" => $item[ "wpid" ], "shopurl" => $item[ "shopurl" ] ), 
                                          'pmimport' . $item[ "wpid" ], 
                                          esc_html__( 'Import', 'leftsell' ), 'reload' ).
                  '</div>';
         $objUI = null;  
      }
   }
   function column_private_share( $item ) {
      if ( !is_array( $item ) ) { return ""; }
      $objProduct = new leftsellProduct();
      $objProduct->FromArray( $item );
      $objUI = new leftsellUI();
      if ( $objProduct->IsShared() ) {
         $objProduct = null;
         return '<div id="unshare' . $item[ "wpid" ] . '">'. 
                  $objUI->BuildAjaxButton( "unshare_private", $item[ "wpid" ], 'unshare' . $item[ "wpid" ], 
                                           esc_html__( 'Unshare', 'leftsell' ), 'reload' ).
                  '</div>';
      } else {
         if ( $objProduct->IsImported() ) {
            return "";
         }
         $objProduct = null;
         return '<div id="share_private' . $item[ "wpid" ] . '">'. 
                  $objUI->BuildAjaxButton( "share_private", $item[ "wpid" ], 'share_private' . $item[ "wpid" ], 
                                           esc_html__( 'Share', 'leftsell' ), 'reload' ).
                  '</div>';
      } 
   }
   function column_price( $item ) {
      if ( !is_array( $item ) ) { return ""; }
      $buffer = '';
      $style  = 'color: #808080';
      if ( isset( $item[ "wpid" ] ) ) {
         $objProduct = new leftsellProduct();
         $objProduct->GetFromExternalId( $item[ "shopid" ], $item[ "wpid" ] );
         if ( $objProduct->IsShared() ) {
            $style = 'color: #006799';
         }
         if ( $objProduct->IsImported() ) {
            $style = "color: #009963";
         }
         if ( $objProduct->IsSubscribedFrom( $item[ "shopid" ], $item[ "wpid" ] ) ) {
            $style = "color: #006799";
         }
      }
      if ( isset ( $item[ "price" ] ) ) {
         if ( trim( $item[ "price" ] ) != "" ) { 
            $buffer .= '<span style="' . $style . '">' . number_format( floatval( $item[ "price" ] ), 2 ) . '</span>';
         }
         $buffer .= '<br />';
         if ( isset ( $item[ "shopname" ] ) and isset( $item[ "shopurl" ] ) ) {
            $buffer .= '<a style="' . $style . '" href="' . $item[ "shopurl" ] . '" target="_blank">' . esc_html( $item[ "shopname" ] ) . '</a><br />';
         }
         return $buffer;
      } else { 
         return ""; 
      }
   }
   function column_cb( $item ) {
      if ( !is_array( $item ) ) { return ""; }
      return sprintf(
         '<input type="checkbox" name="%1$s[]" value="%2$s" />',
         /*$1%s*/ $this->_args['singular'],  
         /*$2%s*/ $item['wpid']              
      );
   }
   function get_bulk_actions() {
      return $this->m_aryBulkActions;
   }
   function process_bulk_action() {
      // process bulk action, then redirect
      switch ( $this->current_action() ) {
         // tabPublish ----------------------------------------------------------------
         case 'share': // share selected products
            foreach( $_GET[ 'product' ] as $id ) {
               $objProduct = new leftsellProduct();
               $objProduct->fromWPId( esc_sql( $id ) );
               if ( $objProduct->fields[ "private_share" ] != 1 ) {
                  $objProduct->SharePrivate();
               }
               $objProduct = null;
            }
            wp_redirect( admin_url() . 'admin.php?page=leftsell&tab=tabPublish&paged=' . 
                         esc_html( sanitize_text_field( $_GET[ "paged" ] ) ) );
            break;
         case 'unshare': // unshare selected products
            foreach( $_GET[ 'product' ] as $id ) {
               $objProduct = new leftsellProduct();
               $objProduct->fromWPId( esc_sql( $id ) );
               if ( $objProduct->fields[ "private_share" ] == 1 ) {
                  $objProduct->UnSharePrivate();
               }
               $objProduct = null;
            }
            wp_redirect( admin_url() . 'admin.php?page=leftsell&tab=tabPublish&paged=' . 
                         esc_html( sanitize_text_field( $_GET[ "paged" ] ) ) );
            break;
         // tabInMarket ---------------------------------------------------------------
         case 'subscribe': // subscribe to selected products
            if ( isset( $this->m_aryData ) ) {
               foreach( $_GET[ 'product' ] as $id ) {
                  // get shop url
                  $shopURL = ""; $shopId = "";
                  foreach( $this->m_aryData as $data ) {
                     if ( isset( $data[ "wpid" ] ) and isset( $data[ "shopurl" ] ) and (isset( $data[ "shopid" ] ) ) ) {
                        if ( $data[ "wpid" ] == $id ) {
                           $shopURL = $data[ "shopurl" ];
                           $shopId  = $data[ "shopid" ];
                        }
                     }
                  }
                  // subscribe to external product
                  if ( $shopURL != "" and $shopId != "" ) {
                     $objProduct = new leftsellProduct();
                     if ( $objProduct->IsSubscribedFrom( $shopId, $id ) == false ) {
                        $objProduct->FetchRemote( $shopId, $id );
                     }
                     $objProduct = null;
                  }
               }
            }
            wp_redirect( admin_url() . 'admin.php?page=leftsell&tab=tabInMarket&paged=' . 
                         esc_html( sanitize_text_field( $_GET[ "paged" ] ) ) );
            break;
         case 'unsubscribe': // unsubscribe from selected products
            if ( isset( $this->m_aryData ) ) {
               foreach( $_GET[ 'product' ] as $id ) {
                  $shopId = "";
                  foreach( $this->m_aryData as $data ) {
                     if ( isset( $data[ "wpid" ] ) and (isset( $data[ "shopid" ] ) ) ) {
                        if ( $data[ "wpid" ] == $id ) {
                           $shopId  = $data[ "shopid" ];
                        }
                     }
                  }
                  if ( $shopId == "" ) { continue; }
                  $objProduct = new leftsellProduct();
                  $objProduct->GetFromExternalId( $shopId, esc_sql( $id ) );
                  // unsubscribe from external product
                  $objProduct->PMUnSubscribe( $shopId, $id );
                  $objProduct = null;
               }
            }
            wp_redirect( admin_url() . 'admin.php?page=leftsell&tab=tabInMarket&paged=' . 
                         esc_html( sanitize_text_field( $_GET[ "paged" ] ) ) );
            break;
         case 'import': // import selected products
            if ( isset( $this->m_aryData ) ) {
               foreach( $_GET[ 'product' ] as $id ) {
                  // get shop url
                  $shopURL = ""; $shopId = "";
                  foreach( $this->m_aryData as $data ) {
                     if ( isset( $data[ "wpid" ] ) and isset( $data[ "shopurl" ] ) and (isset( $data[ "shopid" ] ) ) ) {
                        if ( $data[ "wpid" ] == $id ) {
                           $shopURL = $data[ "shopurl" ];
                           $shopId  = $data[ "shopid" ];
                        }
                     }
                  }
                  // import external product
                  if ( $shopURL != "" and $shopId != "" ) {
                     $objProduct = new leftsellProduct();
                     $objProduct->GetFromExternalId( $shopId, esc_sql( $id ) );
                     if ( $objProduct->IsImported() == false ) {
                        $objProduct->PMImport( $shopURL, $id );
                     }
                     $objProduct = null;
                  }
               }
            }
            wp_redirect( admin_url() . 'admin.php?page=leftsell&tab=tabInMarket&paged=' . 
                         esc_html( sanitize_text_field( $_GET[ "paged" ] ) ) );
            break;
         case 'delete': // delete imported products
            if ( isset( $this->m_aryData ) ) {
               foreach( $_GET[ 'product' ] as $id ) {
                  $shopId = "";
                  foreach( $this->m_aryData as $data ) {
                     if ( isset( $data[ "wpid" ] ) and (isset( $data[ "shopid" ] ) ) ) {
                        if ( $data[ "wpid" ] == $id ) {
                           $shopId  = $data[ "shopid" ];
                        }
                     }
                  }
                  if ( $shopId == "" ) { continue; }
                  $objProduct = new leftsellProduct();
                  $objProduct->GetFromExternalId( $shopId, esc_sql( $id ) );
                  // delete imports
                  if ( $objProduct->IsProduct() ) {
                     $objProduct->DeleteImported( $objProduct->fields["wpid"], $shopId );
                  }
                  $objProduct = null;
               }
            }
            wp_redirect( admin_url() . 'admin.php?page=leftsell&tab=tabInMarket&paged=' . 
                         esc_html( sanitize_text_field( $_GET[ "paged" ] ) ) );
            break;
      }
   }
   function prepare_items() {
      echo '<style type="text/css">';
      echo '.wp-list-table .column-ID      { width: 5%; }';
      echo '.wp-list-table .column-title   { width: 45%; }';
      echo '.wp-list-table .column-type    { width: 110px; overflow:hidden }';
      echo '.wp-list-table .column-subscribedby   { width: 90px; }';
      echo '.wp-list-table .column-subscribe   { width: 200px; }';
      echo '.wp-list-table .column-import   { width: 100px; }';
      echo '.wp-list-table .column-private_share  { overflow:hidden }';
      echo '</style>';

      global $wpdb; //This is used only if making any database queries
      $per_page = $this->m_intPerPage;  // records per page
      $columns = $this->get_columns();
      $hidden = array();
      $sortable = $this->get_sortable_columns();
      $this->_column_headers = array( $columns, $hidden, $sortable );
      $this->process_bulk_action();
      $data = $this->m_aryData;
                
      function usort_reorder( $a, $b ) {
         $orderby = ( !empty( $_REQUEST[ 'orderby' ] ) ) ? $_REQUEST[ 'orderby' ] : 'title'; //If no sort, default to title
         $order   = ( !empty( $_REQUEST[ 'order' ] ) ) ? $_REQUEST[ 'order' ] : 'asc'; //If no order, default to asc
         $result  = strcmp( $a[$orderby], $b[$orderby] ); //Determine sort order
         return ( $order === 'asc' ) ? $result : -$result; //Send final sort direction to usort
      }

      $total_items = 0;
      $current_page = $this->get_pagenum();
      if ( is_array( $data ) ) {
         usort( $data, 'usort_reorder' );
         $total_items = count( $data );
         $data = array_slice( $data, ( ( $current_page-1 ) * $per_page ), $per_page );
      }
      
      $this->items = $data;
        
      $this->set_pagination_args( array(
         'total_items' => $total_items,                    // calculate the total number of items
         'per_page'    => $per_page,                       // determine how many items to show on a page
         'total_pages' => ceil( $total_items / $per_page ) // calculate the total number of pages
      ) );
   }
} // end class LeftSell_Products_List_Table

class leftsell_Shop_List_Table extends WP_List_Table {
   public  $m_aryData;
   private $m_aryColumns;
   private $m_intPerPage;

   // manage from outside
   public function AddColumn( $objColumn ) {
      $this->m_aryColumns[] = $objColumn;
   }
   function get_columns() {
      foreach( $this->m_aryColumns as $objColumn ) {
         $columns[ $objColumn->m_strFieldName ] = $objColumn->m_strTitle;
      }
      return $columns;
   }
   function __construct( $perpage = 20 ) {
      global $status, $page;
      $this->m_intPerPage = $perpage;
      parent::__construct( array(
         'singular'  => esc_html__( 'product', 'leftsell' ),
         'plural'    => esc_html__( 'products', 'leftsell' ),
         'ajax'      => false
      ) );
   }
   function prepare_items() {
      $per_page = $this->m_intPerPage;  
      $columns = $this->get_columns();
      $hidden = array();
      $sortable = $this->get_sortable_columns();
      $this->_column_headers = array( $columns, $hidden, $sortable );
      $this->process_bulk_action();
      $data = $this->m_aryData;
      $total_items = 0;
      $current_page = $this->get_pagenum();
      if ( is_array( $data ) ) {
         $total_items = count( $data );
         $data = array_slice( $data, ( ( $current_page-1 ) * $per_page ), $per_page );
      }
      $this->items = $data;
      $this->set_pagination_args( array(
         'total_items' => $total_items,                  
         'per_page'    => $per_page,                     
         'total_pages' => ceil($total_items/$per_page)   
      ) );
   }
   function column_shopurl( $item ) {
      return '<a href="' . esc_url( $item[ "m_strURL" ] ) . '" target="_blank" style="color: #006799; font-weight:bold">' . 
              esc_url( $item[ "m_strURL" ] ) . '</a>';
   }
   function column_shopaction( $item ) {
      $objUI = new leftsellUI();
      $id = str_replace( "/", "", $item[ "m_strURL" ] );
      $id = str_replace( ":", "", $item[ "m_strURL" ] );
      return '<div id="leftsellRemoveShop' . esc_html( $id ) . '">'. 
               $objUI->BuildAjaxButton( "removejoin", esc_url( $item[ "m_strURL" ] ), 
                                                'leftsellRemoveShop' . esc_html( $id ), esc_html__( 'Remove', 'leftsell' ), 'reload' ).
              '</div>';
   }
} // end class leftsell_Shop_List_Table
?>