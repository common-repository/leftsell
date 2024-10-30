<?php
/**
 * Purpose:           Manage shop products and services
 * Function URI:      https://leftsell.com/
 * Version:           2.0.2
 * Author:            Kundschaft Schweiz
 * Author URI:        https://kundschaft.ch/ 
 **/
defined( 'ABSPATH' ) || exit;

// WP HOOKS ------------------------------------------------------------------------
function leftsellOnPMProductUpdate( $post_ID, $post_after, $post_before ) { 
   // a product got updated -> inform all subscribers
   global $post;
   $post = get_post( $post_ID );
   // product is here at originator and has subscribers
   if ( get_post_meta( $post->ID, "leftsell_post_private_share", true ) == 1 ) {
      if ( get_post_meta( $post->ID, "leftsell_post_pm_subscribedby", true ) != "" ) {
         $objProduct = new leftsellProduct(); 
         $objProduct->OnLocalUpdate( $post->ID );
         $objProduct = null;
      }
   }
}
add_action( 'post_updated', 'leftsellOnPMProductUpdate', 10, 3 );

// Woocommerce Admin HOOKS ---------------------------------------------------------
add_action( 'edit_form_after_title', 'leftsell_product_editor_addon' );
function leftsell_product_editor_addon() {
   if ( class_exists( 'WooCommerce' ) ) {
      if ( get_option( "leftsell_privatemarket" ) == 1 ) {
         $post = get_post();
         $objProduct = new leftsellProduct();
         $objProduct->fromWPId( $post->ID );
         if ( $objProduct->IsProduct() == false ) { return; }
         if ( $objProduct->IsSubscribed() == false and $objProduct->IsImported() == false ) { return; }
         $objUI = new leftsellUI();
         // subscribed
         if ( $objProduct->IsSubscribed() ) { 
            echo '<br /><br />';
            echo '<div class="postbox"><div class="inside">';
            echo '<img src="' . plugin_dir_url( __FILE__ ) . '../includes/images/logoblue.png" id="leftselllogo" />'; 
            echo '<h2 style="font-size:large; color:#006799">';
            echo esc_html__('LeftSell Subscribed Product', 'leftsell' );
            echo '</h2>';
            echo '<p style="color:red">' . esc_html__('Every change to this product will be overwritten if the originator shop changes it.', 'leftsell' ) . '</p>';
            echo '<div id="leftsell_pmunsubscribe" style="float:left; margin-right: 20px">'. 
                  $objUI->BuildAjaxButton( "pmunsubscribe", 
                                          array( "wpid" => $objProduct->fields[ "externalwpid" ], "shopid" => $objProduct->fields[ "shopid" ] ), 
                                          'leftsell_pmunsubscribe', esc_html__( 'Unsubscribe', 'leftsell' ), 'document.location="edit.php?post_type=product"' ).
                  '</div>';
            echo '<div id="pmrequestupdate">'. 
                   $objUI->BuildAjaxButton( "pmrequestupdate", 
                                           array( "externalwpid" => $objProduct->fields[ "externalwpid" ], "shopid" => $objProduct->fields[ "shopid" ] ), 
                                           'pmrequestupdate', esc_html__( 'Update', 'leftsell' ), 'reload' ).
                   
                   '</div>';

            echo '</div></div>'; 
         }
         // imported
         if ( $objProduct->IsImported() ) { 
            echo '<br /><br />';
            echo '<div class="postbox"><div class="inside">';
            echo '<img src="' . plugin_dir_url( __FILE__ ) . '../includes/images/logogreen.png" id="leftselllogo" />'; 
            echo '<h2 style="font-size:large; color:#009963">';
            echo esc_html__('LeftSell Imported Product', 'leftsell' );
            echo '</h2>';
            echo '<p>' . esc_html__('This product is imported. Changes are only stored locally and will not be overwritten.', 'leftsell' ) . '</p>';
            echo '</div></div>'; 
         }
         $objUI = null;
         $objProduct = null;
      }
   }
}

   

class leftsellVariation {
   // Handle product variations
   public $fields = array(
      "wpid"               => "", // wpid
      "parent_id"          => "", // wpid
      "slug"               => "", // slug
      "title"              => "", 
      "status"             => "", 
      "sku"                => "", 
      "manage_stock"       => "", 
      "stock_quantity"     => "", 
      "stock_status"       => "", 
      "backorders"         => "", 
      "weight"             => "", 
      "length"             => "", 
      "width"              => "", 
      "height"             => "", 
      "tax_class"          => "", 
      "shipping_class_id"  => "", 
      "image_id"           => "", 
      "image_url"          => "",  // manual
      "purchase_note"      => "", 
      "catalog_visibility" => "", 
      "low_stock_amount"   => "", 
      "name"               => "", 
      "featured"           => "", 
      "description"        => "", 
      "short_description"  => "", 
      "price"              => "", 
      "regular_price"      => "", 
      "sale_price"         => "", 
      "date_on_sale_from"  => "", 
      "date_on_sale_to"    => "", 
      "total_sales"        => "", 
      "tax_status"         => "", 
      "sold_individually"  => "", 
      "upsell_ids"         => "", 
      "cross_sell_ids"     => "", 
      "reviews_allowed"    => "", 
      "attributes"         => array(), 
      "default_attributes" => array(), 
      "menu_order"         => "", 
      "virtual"            => "", 
      "downloadable"       => "", 
      "shipping_class_id"  => "", 
      "downloads"          => array(), 
      "image_id"           => "", 
      "gallery_image_ids"  => array(), 
      "download_limit"     => "", 
      "download_expiry"    => "", 
      "attribute_summary"  => "" 
   );
   public function fromWPId( $id ) {
      // get variation product from wp id
      $wooProduct = wc_get_product( $id );
      if ( $wooProduct == false ) { return; }
      $this->fields[ "wpid" ]                = $id;
      $this->fields[ "parent_id" ]           = $wooProduct->get_parent_id();
      $this->fields[ "slug" ]                = $wooProduct->get_slug();
      $this->fields[ "title" ]               = $wooProduct->get_title();
      $this->fields[ "status" ]              = $wooProduct->get_status();
      $this->fields[ "sku" ]                 = $wooProduct->get_sku();
      $this->fields[ "manage_stock" ]        = $wooProduct->get_manage_stock();
      $this->fields[ "stock_quantity" ]      = $wooProduct->get_stock_quantity();
      $this->fields[ "stock_status" ]        = $wooProduct->get_stock_status();
      $this->fields[ "backorders" ]          = $wooProduct->get_backorders();
      $this->fields[ "weight" ]              = $wooProduct->get_weight();
      $this->fields[ "length" ]              = $wooProduct->get_length();
      $this->fields[ "width" ]               = $wooProduct->get_width();
      $this->fields[ "height" ]              = $wooProduct->get_height();
      $this->fields[ "tax_class" ]           = $wooProduct->get_tax_class();
      $this->fields[ "shipping_class_id" ]   = $wooProduct->get_shipping_class_id();
      $this->fields[ "purchase_note" ]       = $wooProduct->get_purchase_note();
      $this->fields[ "low_stock_amount" ]    = $wooProduct->get_low_stock_amount();
      $this->fields[ "name" ]                = $wooProduct->get_name();
      $this->fields[ "featured" ]            = $wooProduct->get_featured();
      $this->fields[ "description" ]         = $wooProduct->get_description();
      $this->fields[ "short_description" ]   = $wooProduct->get_description();
      $this->fields[ "price" ]               = $wooProduct->get_price();
      $this->fields[ "regular_price" ]       = $wooProduct->get_regular_price();
      $this->fields[ "sale_price" ]          = $wooProduct->get_sale_price();
      $this->fields[ "date_on_sale_from" ]   = $wooProduct->get_date_on_sale_from();
      $this->fields[ "date_on_sale_to" ]     = $wooProduct->get_date_on_sale_to();
      $this->fields[ "total_sales" ]         = $wooProduct->get_total_sales();
      $this->fields[ "tax_status" ]          = $wooProduct->get_tax_status();
      $this->fields[ "sold_individually" ]   = $wooProduct->get_sold_individually();
      $this->fields[ "upsell_ids" ]          = $wooProduct->get_upsell_ids();
      $this->fields[ "cross_sell_ids" ]      = $wooProduct->get_cross_sell_ids();
      $this->fields[ "reviews_allowed" ]     = $wooProduct->get_reviews_allowed();
      $aryAttributes = array();
      foreach ( $wooProduct->get_attributes() as $key=>$attribute ) {
         $aryAttributes[$key] = $this->fixUTF8( $attribute);  
      }
      $this->fields[ "attributes" ]          = $aryAttributes;
      $this->fields[ "default_attributes" ]  = $wooProduct->get_default_attributes();
      $this->fields[ "menu_order" ]          = $wooProduct->get_menu_order();
      $this->fields[ "virtual" ]             = $wooProduct->get_virtual();
      $this->fields[ "downloadable" ]        = $wooProduct->get_downloadable();
      $this->fields[ "shipping_class_id" ]   = $wooProduct->get_shipping_class_id();
      $this->fields[ "downloads" ]           = $wooProduct->get_downloads();
      $this->fields[ "image_id" ]            = $wooProduct->get_image_id();
      $this->fields[ "gallery_image_ids" ]   = $wooProduct->get_gallery_image_ids();
      $this->fields[ "download_limit" ]      = $wooProduct->get_download_limit();
      $this->fields[ "download_expiry" ]     = $wooProduct->get_download_expiry();
      $this->fields[ "attribute_summary" ]   = $wooProduct->get_attribute_summary();
      // image
      $this->fields[ "image_id" ]            = $wooProduct->get_image_id();
      $this->fields[ "image_url" ]           = wp_get_attachment_url( $this->fields[ "image_id" ] );
   }
   public function saveVariation( $product_id, $data ){
      // create a variation from array of data and store it to the parent product
      $product = wc_get_product( $product_id );
      
      // all children already deleted -> create new
      $variation_post = array(
         'post_title'  => $data[ "title" ],
         'post_name'   => $data[ "name" ],      
         'post_status' => $data[ "status" ],
         'post_parent' => $product_id,
         'post_type'   => 'product_variation',
         'guid'        => $product->get_permalink()
      );

      // creating post
      $variation_id = wp_insert_post( $variation_post );

      // Get an instance of the WC_Product_Variation object
      $variation = new WC_Product_Variation( $variation_id );

      // Iterating through the variations attributes
      foreach ( $data[ 'attributes']  as $attribute=>$term_name ) {
         $term_name = $this->fixUTF8( $term_name );
         $taxonomy  = $this->fixUTF8( $attribute );
         // create taxonomy if not exists
         if( ! taxonomy_exists( $taxonomy ) ) {
            register_taxonomy(
                  $taxonomy,
                  'product_variation',
                  array(
                     'hierarchical' => false,
                     'label' => ucfirst( $taxonomy ),
                     'query_var' => true,
                     'rewrite' => array( 'slug' => '$taxonomy')
                  )
            );
         }
         if( ! term_exists( $term_name, $taxonomy ) ) {
            wp_insert_term( $term_name , $taxonomy ); // create the term
         }
         $term_slug = get_term_by( 'name', $term_name, $taxonomy )->slug; // Get the term slug

         // Get the post Terms names from the parent variable product.
         $post_term_names =  wp_get_post_terms( $product_id, $taxonomy, array('fields' => 'names') );

         // Check if the post term exists and if not set it in the parent variable product
         if( ! in_array( $term_name , $post_term_names ) ) {
            wp_set_post_terms( $product_id, $term_name, $taxonomy, true );
         }
         // save the attribute data in the product variation
         update_post_meta( $variation_id, 'attribute_'.$taxonomy, $term_slug );
      }
      // set data
      if( !empty( $data[ "sku" ] ) ) { $variation->set_sku( $data[ "sku" ] ); }
      if( empty( $data[ 'sale_price' ] ) ) {
         $variation->set_price( $data[ 'regular_price' ] );
      } else {
         $variation->set_price( $data[ 'sale_price' ] );
         $variation->set_sale_price( $data[ 'sale_price' ] );
      }
      $variation->set_regular_price( $data[ 'regular_price' ] );

      // stock
      update_post_meta( $variation_id, '_manage_stock',          wp_kses_post( $data[ "manage_stock" ] ) );
      update_post_meta( $variation_id, '_stock',                 wp_kses_post( $data[ "stock_quantity" ] ) );
      update_post_meta( $variation_id, '_stock_status',          wp_kses_post( sanitize_text_field($data[ "stock_status" ] ) ) );
      update_post_meta( $variation_id, '_low_stock_amount',      wp_kses_post( $data[ "low_stock_amount" ] ) );
      
      $variation->set_weight( $data[ 'weight' ] ); 
      $variation->set_width( $data[ 'width' ] ); 
      $variation->set_length( $data[ 'length' ] ); 
      $variation->set_height( $data[ 'height' ] ); 
      $variation->set_description( $data[ 'description' ] ); 
      $variation->set_short_description( $data[ 'short_description' ] ); 
      $variation->set_menu_order( $data[ 'menu_order' ] ); 

      // get the pic
      if ( $data[ "image_url" ] != "" ) {
         require_once( ABSPATH . "wp-admin/includes/file.php" );
         require_once( ABSPATH . "wp-admin/includes/media.php" );
         require_once( ABSPATH . "wp-admin/includes/image.php" );

         // prepare filename
         preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $data[ "image_url" ], $matches );
         if ( $matches !== false ) {
            $file_array = array();
            $file_array[ 'name' ] = basename( $matches[0] );
            
            // download
            add_filter( 'https_local_ssl_verify', '__return_false' );
            add_filter( 'https_ssl_verify', '__return_false' );
            $file_array[ 'tmp_name' ] = download_url( $data[ "image_url" ] );
            
            // save the file and attach it to the post
            if ( !is_wp_error( $file_array['tmp_name'] ) ) {
               $id = media_handle_sideload( $file_array, $variation_id, $term_name );
               if ( is_wp_error( $id ) ) {
                  @unlink( $file_array[ 'tmp_name' ] );
               } else {
                  set_post_thumbnail( $variation_id, $id );
               }
            }
         }
      }
      $variation->save(); 
   }
   public function fixUTF8( $text ) {
      $umlaute = array( "/ä/","/ö/","/ü/","/Ä/","/Ö/","/Ü/","/ß/" );
      $replace = array( "ae" ,"oe" ,"ue" ,"Ae" ,"Oe" ,"Ue" ,"ss" );
      return preg_replace( $umlaute, $replace, $text );  
   }
} // end class leftsellVariation

class leftsellProduct {
   /**
   *
   * Handles the products and services
   * 
   **/
   public $fields = array( 
      // leftsell fields
      "leftsellid"       => "",   // leftsell id:        // meta:leftsell_post_leftsellid
      "shopurl"          => "",   // leftsell shop link: // get_home_url()
      "shopid"           => "",   // leftsell shop id:   // meta: leftsell_shop_id
      "shopname"         => "",   // leftsell shop name: // get_bloginfo( "name" )
      "provision"        => "",   // leftsell seller provision as amount: // meta:leftsell_post_provision
      "isservice"        => 0,    // product is service yes / no // meta: leftsell_post_service
      "private_share"    => 0,    // product is shared to private market  // meta:leftsell_post_private_share
      "pm_subscribed"    => 0,    // product is subscribed by private market // meta: leftsell_post_pm_subscribed
      "pm_imported"      => 0,    // product is imported from private market // meta: leftsell_post_pm_imported
      "pm_subscribedby"  => "",   // list of shops subscribing // meta: leftsell_post_pm_subscribedby
      "externalwpid"     => "",   // product id in external shop // meta: leftsell_post_pm_external_id
      "product_currency" => "",   // own currency per product V3
      "keywords"         => "",   // product keywords

      // wp
      "wpid"                    => "",         // google product relevant: wp id
      "title"                   => "",         // google product relevant: wp title
      "text"                    => "",         // google product relevant: woo product text
      "link"                    => "",         // google product relevant: woo product url
      "shortdescription"        => "",         // excerpt
      "slug"                    => "",         // wp slug 
      "status"                  => "",         // status

      // kcms
      "metapath"                => "",         // kcms content path
      "metaid"                  => "",         // kcms content id

      // woo
      "sku"                     => "",         // sku    -> _sku
      "gtin"                    => "",         // gtin   -> _gtin
      "mpn"                     => "",         // mpn    -> _mpn
      "brand"                   => "",         // google product brand (required)
      "condition"               => "new",      // google product relevant: new, used or refurbished
      "adult"                   => "no",       // google product relevant: sexual content, no or yes
      "price"                   => "",         // google product relevant: woo price
      "regularprice"            => "",         // regular price
      "saleprice"               => "",         // sale price
      "saleprice_dates_from"    => "",         // sale price from date
      "saleprice_dates_to"      => "",         // sale price till date
      "onsale"                  => "no",         // on sale
      "tax_status"              => "",         // _tax_status
      "purchase_note"           => "",         // purchase note
      "weight"                  => "",         // weight
      "length"                  => "",         // length
      "width"                   => "",         // width
      "height"                  => "",         // height
      "attributes"              => "",         // attributes
      "sold_indiviually"        => "",         // sold individually
      "virtual"                 => "",         // woo real product or virtual 
      "stock_status"            => "",         // stock status
      "visibility"              => "",         // visibility
      "downloadable"            => "",         // downloadable
      "featured"                => "",         // is featured
      "manage_stock"            => "",         // manage_stock
      "stock_quantity"          => "",         // stock_quantity
      "low_stock_amount"        => "",         // low_stock_amount
      "backorders"              => "",         // backorders
      "currency"                => "",         // mapped to shortcode currency
      "currencysymbol"          => "",         // currency symbol
      "imagelink"               => "",         // google product relevant: woo featured image url
      "galleryimages"           => "",         // list of images for gallery
      "datemodified"            => "",         // date
      "datecreated"             => "",         // date
      "availability"            => "",         // google product relevant: woo stock
      "identifierexists"        => "no",       // google product relevant: yes (has gtin, mpn or brand), no (no numbers)
      "google_product_category" => "",         // google product category
      "comments"                => "",         // comments and ratings as JSON
      "attributes"              => "",         // custom attributes
      "variations"              => ""          // product variations
   );

   // constructions and gets ----------------------------------------------------------
   function __construct() {
      if ( !function_exists( 'get_home_url' ) ) {  require_once ABSPATH . WPINC . '/link-template.php'; } 
      // general fields
      $this->fields[ "shopname" ]          = get_bloginfo( "name" );    
      $this->fields[ "shopid" ]            = sanitize_text_field( get_option( "leftsell_shop_id" ) );
      $this->fields[ "shopurl" ]           = sanitize_text_field( get_home_url() );
      if ( substr( $this->fields[ "shopurl" ], strlen( $this->fields[ "shopurl" ] ) - 1, 1 ) != "/" ) {
         $this->fields[ "shopurl" ] .= "/";
      }
   }
   public function fromWPId( $wpid ) { 
      $wpid = sanitize_text_field( $wpid );
      if ( trim( $wpid ) == "" ) { return; }
      $post = get_post( $wpid );
      if( empty( $post ) ) { return; } 
      return $this->fromPost( $post );
   }
   public function fromPost( $post ) { 
      if ( empty( $post ) ) { return false; }
      
      // wordpress fields
      $this->fields[ "wpid" ]              = $post->ID;
      if ( $this->fields[ "wpid" ] == "" ) { return false; }
      $this->fields[ "title" ]             = $post->post_title;   // can be overwritten by leftsell
      $this->fields[ "text" ]              = $post->post_content; // can be overwritten by leftsell
      $this->fields[ "link" ]              = get_permalink( $post->ID );
      $this->fields[ "shortdescription" ]  = strip_tags( $post->post_excerpt ); // can be overwritten by shop systems
      $this->fields[ "slug" ]              = $post->post_name;    
      $this->fields[ "status" ]            = $post->status;       // can be overwritten by shop systems
     
      // leftsell sharing
      $share = get_post_meta( $post->ID, "leftsell_post_private_share", true );
      if ( intval( $share ) == 1 ) {
         $this->fields[ "private_share" ] = 1;
      } else {
         $this->fields[ "private_share" ] = 0;
      }
      $service = get_post_meta( $post->ID, "leftsell_post_service", true );
      if ( intval( $service ) == 1 ) {
         $this->fields[ "isservice" ] = 1;
      } else {
         $this->fields[ "isservice" ] = 0;
      } 
      $subscribed = get_post_meta( $post->ID, "leftsell_post_pm_subscribed", true );
      if ( intval( $subscribed == 1 ) ) {
         $this->fields[ "pm_subscribed" ] = 1;
      } else {
         $this->fields[ "pm_subscribed" ] = 0;
      }
      $imported = get_post_meta( $post->ID, "leftsell_post_pm_imported", true );
      if ( intval( $imported == 1 ) ) {
         $this->fields[ "pm_imported" ] = 1;
      } else {
         $this->fields[ "pm_imported" ] = 0;
      }
      $this->fields[ "pm_subscribedby" ] = get_post_meta( $post->ID, "leftsell_post_pm_subscribedby", true );
      if ( get_post_meta( $post->ID, "leftsell_post_shopid", true ) != "" ) {
         $this->fields[ "shopid" ]        = get_post_meta( $post->ID, "leftsell_post_shopid", true );
      }
      if ( get_post_meta( $post->ID, "leftsell_post_shopname", true ) != "" ) {
         $this->fields[ "shopname" ]      = get_post_meta( $post->ID, "leftsell_post_shopname", true );
      }
      if ( get_post_meta( $post->ID, "leftsell_post_pm_external_id", true ) != "" ) {
         $this->fields[ "externalwpid" ]  = get_post_meta( $post->ID, "leftsell_post_pm_external_id", true );
      }
      if ( get_post_meta( $post->ID, "leftsell_post_shopurl", true ) != "" ) {
         $this->fields[ "shopurl" ]      = get_post_meta( $post->ID, "leftsell_post_shopurl", true );
      }

      // WooCommerce fields
      $this->fields[ "sku" ]                  = get_post_meta( $post->ID, "_sku", true );
      $this->fields[ "gtin" ]                 = get_post_meta( $post->ID, "_gtin", true );
      $this->fields[ "mpn" ]                  = get_post_meta( $post->ID, "_mpn", true );
      $this->fields[ "brand" ]                = get_post_meta( $post->ID, "_brand", true );
      $this->fields[ "condition" ]            = get_post_meta( $post->ID, "_condition", true );
      $this->fields[ "adult" ]                = get_post_meta( $post->ID, "_adult", true );
      $this->fields[ "price" ]                = get_post_meta( $post->ID, "_price", true );
      $this->fields[ "regularprice" ]         = get_post_meta( $post->ID, "_regular_price", true );
      $this->fields[ "saleprice" ]            = get_post_meta( $post->ID, "_sale_price", true );
      $this->fields[ "saleprice_dates_from" ] = get_post_meta( $post->ID, "_sale_price_dates_from", true );
      $this->fields[ "saleprice_dates_to" ]   = get_post_meta( $post->ID, "_sale_price_dates_to", true );
      $this->fields[ "tax_status" ]           = get_post_meta( $post->ID, "_tax_status", true );
      $this->fields[ "purchase_note" ]        = get_post_meta( $post->ID, "_purchase_note", true );
      $this->fields[ "weight" ]               = get_post_meta( $post->ID, "_weight", true );
      $this->fields[ "length" ]               = get_post_meta( $post->ID, "_length", true );
      $this->fields[ "width" ]                = get_post_meta( $post->ID, "_width", true );
      $this->fields[ "height" ]               = get_post_meta( $post->ID, "_height", true );
      $this->fields[ "attributes" ]           = get_post_meta( $post->ID, "_product_attributes", true );
      if ( get_post_meta( $post->ID, "_sold_individually", true ) == 'yes' ) {
         $this->fields[ "sold_indiviually" ] = 1;
      } else {
         $this->fields[ "sold_indiviually" ] = 0;
      }
      if ( get_post_meta( $post->ID, "_virtual", true ) == 'yes' ) {
         $this->fields[ "virtual" ] = 1;
         $this->fields[ "isservice" ] = 1;
      } else {
         $this->fields[ "virtual" ] = 0;
      }
      $this->fields[ "stock_status" ]         = get_post_meta( $post->ID, "_stock_status", true );
      $this->fields[ "visibility" ]           = get_post_meta( $post->ID, "_visibility", true );
      $this->fields[ "downloadable" ]         = get_post_meta( $post->ID, "_downloadable", true );
      if ( get_post_meta( $post->ID, "_featured", true ) == 'yes' ) {
         $this->fields[ "featured" ] = 1;
      } else {
         $this->fields[ "featured" ] = 0;
      }
      if ( get_post_meta( $post->ID, "_manage_stock", true ) == 'yes' ) {
         $this->fields[ "manage_stock" ] = 1;
      } else {
         $this->fields[ "manage_stock" ] = 0;
      }
      if ( get_post_meta( $post->ID, "_backorders", true ) == 'yes' ) {
         $this->fields[ "backorders" ] = 1;
      } else {
         $this->fields[ "backorders" ] = 0;
      }

      // shop systems -----------------------------------------------------------------
      if ( class_exists( 'WooCommerce' ) ) {
         $this->MapWoocommerce( $post );
      }
      $this->MapKCMS( $post );

      // logical fields ---------------------------------------------------------------
      if ( $this->fields[ "brand" ] == "" )     { $this->fields[ "brand" ] = $this->fields[ "shopname" ]; }
      if ( $this->fields[ "sku" ] != "" )       { $this->fields[ "identifierexists" ] = "yes"; }
      if ( $this->fields[ "mpn" ] != "" )       { $this->fields[ "identifierexists" ] = "yes"; }
      if ( $this->fields[ "gtin" ] != "" )      { $this->fields[ "identifierexists" ] = "yes"; }
      if ( $this->fields[ "condition" ] == "" ) { $this->fields[ "condition" ] = "new"; }
      if ( $this->fields[ "adult" ] == "" )     { $this->fields[ "adult" ] = "no"; }
      
      // leftsell V3 fields and overrides
      if ( get_post_meta( $post->ID, "leftsell_post_currency", true ) != "" ) {
         $this->fields[ "product_currency" ] = get_post_meta( $post->ID, "leftsell_post_currency", true );
      } else {
         if ( class_exists( 'WooCommerce' ) ) {
            $this->fields[ "product_currency" ] = $this->MapCurrencySymbol( get_woocommerce_currency_symbol() );
         }
      }
      // use leftsell fields if set
      $leftsellTitle = sanitize_text_field( get_post_meta( $post->ID, "leftsell_post_titletag", true ) );
      if ( trim( $leftsellTitle ) != "" ) { 
         $this->fields[ "title" ] = $leftsellTitle; 
      }
      $leftsellDescription = sanitize_text_field( get_post_meta( $post->ID, "leftsell_post_description", true ) );
      if ( $leftsellDescription != "" ) { 
         $this->fields[ "shortdescription" ] = strip_tags( $leftsellDescription ); 
      }
      $leftsellKeywords = sanitize_text_field( get_post_meta( $post->ID, "leftsell_post_keywords", true ) );
      if ( $leftsellKeywords != "" ) { 
         $this->fields[ "keywords" ] = $leftsellKeywords; 
      }
      
      // currency map ------------------------------------------------------------------
      $this->fields[ "currency" ] = $this->MapCurrencySymbol( $this->fields[ "currencysymbol" ] );

      // comments ---------------------------------------------------------------------
      if ( get_option( "leftsell_pm_share_comments" ) == 1 ) {
         $objComments = new leftsellComment();
         $this->fields[ "comments" ] = $objComments->GetLocalCommentsasJSON( $this->fields[ "wpid" ] );
         $objComments = null;
      }
   }
   public function ToArray() {
      // put all fields to array
      if ( intval( $this->fields[ "wpid" ] ) == 0  )  { return; }
      $aryResult = array();
      foreach( $this->fields as $key=>$value ) {
         $aryResult[ $key ] = $value;
      }
      return $aryResult;
   }
   public function FromArray( $ary ) {
      // resolves result from GetPMExternalProduct
      if ( !isset( $ary[ "wpid" ] ) )  { return; }
      foreach( $this->fields as $key=>$value ) {
         if ( isset( $ary[ $key ] ) )  { $this->fields[ $key ] = $ary[ $key ]; }
      }
   }
   private function MapWoocommerce( $post ) {
      // map values for WooCommerce
      if ( empty( $post ) ) { return; }
      if ( class_exists( 'WooCommerce' ) ) {
         $product = wc_get_product( $post->ID );
         if ( empty( $product ) ) { return; }
         $this->fields[ "imagelink" ]        = $this->ImageURL( $product->get_image('view') );
         $this->fields[ "slug" ]             = $product->get_slug();
         $this->fields[ "virtual" ]          = $product->get_virtual();
         switch ( $this->fields[ "virtual" ] ) {
            case 1:       $this->fields[ "isservice" ] = 1;  break;
            case "yes":   $this->fields[ "isservice" ] = 1;  break;
            case "true":  $this->fields[ "isservice" ] = 1;  break;
            case true:    $this->fields[ "isservice" ] = 1;  break;
         }
         $this->fields[ "shortdescription" ] = strip_tags( $product->get_short_description() );
         $this->fields[ "title" ]            = get_the_title( $post->ID ); //$product->get_name();
         $this->fields[ "text" ]             = $product->get_description();
         $this->fields[ "regularprice" ]     = $product->get_regular_price();
         $this->fields[ "saleprice" ]        = $product->get_sale_price();
         $this->fields[ "price" ]            = $product->get_price();
         $this->fields[ "currencysymbol" ]   = get_woocommerce_currency_symbol();
         $this->fields[ "sold_indiviually" ] = $product->get_sold_individually();
         $this->fields[ "datemodified" ]     = $product->get_date_modified();
         $this->fields[ "datecreated" ]      = $product->get_date_created();
         $this->fields[ "status" ]           = $product->get_status();
         $this->fields[ "featured" ]         = $product->get_featured();
         $this->fields[ "sku" ]              = $product->get_sku();
         $this->fields[ "availability" ]     = $product->get_stock_status();
         $this->fields[ "tax_status" ]       = get_post_meta( $post->ID, "_tax_status", true );
         // stock
         $this->fields[ "manage_stock" ]     = get_post_meta( $post->ID, "_manage_stock", true );
         $this->fields[ "stock_quantity" ]   = get_post_meta( $post->ID, "_stock", true );
         $this->fields[ "stock_status" ]     = get_post_meta( $post->ID, "_stock_status", true );
         $this->fields[ "low_stock_amount" ] = get_post_meta( $post->ID, "_low_stock_amount", true );
         
         // WooCommerce logical fields
         if ( intval( $this->fields[ "saleprice" ] != 0 ) ) {
            if ( $this->fields[ "saleprice" ] != $this->fields[ "price" ] ) {
               $this->fields[ "onsale" ]    = "yes";
            } else {
               $this->fields[ "onsale" ]    = "no";
            }
         }

         // product featured image
         $this->fields[ "imagelink" ]        = $this->ImageURL( $product->get_image( 'view' ) );

         // gallery
         $this->fields[ "galleryimages" ]    = $this->GetWooGalleryJSON( $product );
         
         // custom attributs
         $this->fields[ "attributes" ] = get_post_meta( $post->ID, "_product_attributes", true );
         if ( is_array( $this->fields[ "attributes" ] ) ) {
            $objVariation = new leftsellVariation();
            $aryAttributes = array();
            foreach ( $this->fields[ "attributes" ] as $key=>$attribute ) {
               $aryAttributes[$key] = $objVariation->fixUTF8( $attribute);  
            }
            $this->fields[ "attributes" ] = $aryAttributes;
            $objVariation = null;
         }

         // variations
         if ( $product->is_type( 'variable' ) ) {
            $aryVariations = array();
            $variations = $product->get_children();
            foreach( $variations as $variation ) { 
               $objVariation = new leftsellVariation();
               $objVariation->fromWPId( $variation );
               $aryVariations[] = $objVariation->fields;
               $objVariation = null;
            }
            $this->fields[ "variations" ] =  $aryVariations;
         }
         $product = null;
      }
   }
   private function MapKCMS( $post ) { 
      if ( empty( $post ) ) { return; }
      $this->fields[ "metapath" ] = get_post_meta( $post->ID, "kcms_content_folder", true );
      $this->fields[ "metaid" ]   = get_post_meta( $post->ID, "kcms_content_file", true );
      // check KCMS installation
      if ( ( $this->fields[ "metapath" ] . $this->fields[ "metaid" ] ) == "" ) { return; }
      require_once( ABSPATH . "wp-admin/includes/file.php" );
      if ( file_exists( get_home_path() . "../system/api_wp.php" ) ) {
         require_once( get_home_path() . "../system/api_wp.php" );
      } else { 
         return; 
      }
      // get KCMS data
      $this->fields[ "text" ]        = KCMS_GetText( $this->fields[ "metapath" ], $this->fields[ "metaid" ] );
      $this->fields[ "imagelink" ]   = KCMS_GetField( "anPageImage", $this->fields[ "metapath" ], $this->fields[ "metaid" ] );
      $this->fields[ "title" ]       = KCMS_GetField( "anHeaderTitleTag", $this->fields[ "metapath" ], $this->fields[ "metaid" ] );
      $this->fields[ "description" ] = KCMS_GetField( "anPageDescription", $this->fields[ "metapath" ], $this->fields[ "metaid" ] );
      $this->fields[ "shortdescription" ] = strip_tags( $this->fields[ "description" ] );
      $this->fields[ "keywords" ]    = KCMS_GetField( "anKeywords", $this->fields[ "metapath" ], $this->fields[ "metaid" ] );
   }
   private function ImageURL( $wooimage ) {
      // extract img url from woo <img src="...
      if ( $wooimage == "" ) { return ""; }
      $pos = strpos( $wooimage, 'src=' );
      if ( $pos === false ) { return ""; }
      $pos = $pos + 5;
      $wooimage = substr( $wooimage, $pos, strlen( $wooimage ) );
      $pos = strpos( $wooimage, '"' );
      if ( $pos === false ) { return ""; }
      $wooimage = substr( $wooimage, 0 ,$pos );
      return $wooimage;
   }
   private function GetWooGalleryJSON( $product ) {
      // Woocommerce get gallery image link collection as JSON
      $buffer = "";
      if ( class_exists( 'WooCommerce' ) ) {
         $aryAttachIds = $product->get_gallery_image_ids();
         $aryBuffer = array();
         foreach( $aryAttachIds as $AttachIds ) {
            $aryBuffer[] = wp_get_attachment_url( $AttachIds );
         }
         $objJSON = new leftsellJSON();
         $buffer = $objJSON->ToJSON( $aryBuffer );
         $objJSON = null;
      }
      return $buffer;
   }
   public function ConvertTextToSimpleDescription( $text ) {
      if ( $text == "" ) { return ""; }
      $text = str_replace ( "<br />", " ", $text );
      $text = str_replace ( "<br>", " ", $text );
      $text = strip_tags( $text );
      $text = str_replace ( "\n", " ", $text );
      $text = str_replace ( "\r", " ", $text );
      $text = str_replace ( "&nbsp;", " ", $text );
      $text = preg_replace( '/[\t|\s{2,}]/', ' ', $text);
      while ( strpos ( $text, "  ") !== false ) {
         $text = str_replace ( "  ", " ", $text );
      }
      // put html-codes back to german
      $text = leftsellfixUTF8( $text );
      return trim( $text );
   }
   public function MapGoogleValues( $value ) {
      $value = sanitize_text_field( $value );
      switch ( $value ) {
         case "instock"    : $value = "in_stock";     break;
         case "outofstock" : $value = "out_of_stock"; break;
         case "onbackorder": $value = "in_stock";     break;
         default: break;
      }
      return $value;
   }
   private function MapCurrencySymbol( $symbol ) {
      if ( $symbol == "" ) { return ""; }
      switch ( $symbol ) {
         case "&#36;":                       return "USD"; break;
         case "$":                           return "USD"; break;
         case "&dollar":                     return "USD"; break;
         case "&#162;":                      return "EUR"; break;
         case "&cent;":                      return "EUR"; break;
         case "&#128;":                      return "EUR"; break;
         case "&euro;":                      return "EUR"; break;
         case "€":                           return "EUR"; break;
         case "&#163;":                      return "GBP"; break;
         case "&pound;":                     return "GBP"; break;
         case "&#165;":                      return "JPY"; break;
         case "&yen;":                       return "JPY"; break;
         case "&#8355;":                     return "XPF"; break;
         case "&#8356;":                     return "TRY"; break;
         case "&#8359;":                     return "PTS"; break;
         case "&#x20B9;":                    return "INR"; break;
         case "&#8361;":                     return "KPW"; break;
         case "&#8372;":                     return "UAH"; break;
         case "&#8367;":                     return "GRD"; break;
         case "&#8366;":                     return "MNT"; break;
         case "&#8370;":                     return "PYG"; break;
         case "&#8369;":                     return "PHP"; break;
         case "&#8371;":                     return "AUD"; break;
         case "&#8373;":                     return "GHS"; break;
         case "&#8365;":                     return "LAK"; break;
         case "&#8362;":                     return "ILS"; break;
         case "&#8363;":                     return "VND"; break;
         case "&#76;&#101;&#107;":           return "ALL"; break;
         case "&#1547;":                     return "AFN"; break;
         case "&#36;":                       return "ARS"; break;
         case "&#402;":                      return "AWG"; break;
         case "&#36;":                       return "AUD"; break;
         case "&#1084;&#1072;&#1085;":       return "AZN"; break;
         case "&#36;":                       return "BSD"; break;
         case "&#36;":                       return "BBD"; break;
         case "&#66;&#114;":                 return "BYN"; break;
         case "&#66;&#90;&#36;":             return "BZD"; break;
         case "&#36;":                       return "BMD"; break;
         case "&#36;&#98;":                  return "BOB"; break;
         case "&#75;&#77;":                  return "BAM"; break;
         case "&#80;":                       return "BWP"; break;
         case "&#1083;&#1074;":              return "BGN"; break;
         case "&#82;&#36;":                  return "BRL"; break;
         case "&#36;":                       return "BND"; break;
         case "&#6107;":                     return "KHR"; break;
         case "&#36;":                       return "CAD"; break;
         case "&#36;":                       return "KYD"; break;
         case "&#36;":                       return "CLP"; break;
         case "&#165;":                      return "CNY"; break;
         case "&#36;":                       return "COP"; break;
         case "&#8353;":                     return "CRC"; break;
         case "&#107;&#110;":                return "HRK"; break;
         case "&#8369;":                     return "CUP"; break;
         case "&#75;&#269;":                 return "CZK"; break;
         case "&#107;&#114;":                return "DKK"; break;
         case "&#82;&#68;&#36;":             return "DOP"; break;
         case "&#36;":                       return "XCD"; break;
         case "&#163;":                      return "EGP"; break;
         case "&#36;":                       return "SVC"; break;
         case "&#8364;":                     return "EUR"; break;
         case "&#163;":                      return "FKP"; break;
         case "&#36;":                       return "FJD"; break;
         case "&#162;":                      return "GHS"; break;
         case "&#163;":                      return "GIP"; break;
         case "&#81;":                       return "GTQ"; break;
         case "&#163;":                      return "GGP"; break;
         case "&#36;":                       return "GYD"; break;
         case "&#76;":                       return "HNL"; break;
         case "&#36;":                       return "HKD"; break;
         case "&#70;&#116;":                 return "HUF"; break;
         case "&#107;&#114;":                return "ISK"; break;
         case "&#8377;":                     return "INR"; break;
         case "&#82;&#112;":                 return "IDR"; break;
         case "&#65020;":                    return "IRR"; break;
         case "&#163;":                      return "IMP"; break;
         case "&#8362;":                     return "ILS"; break;
         case "&#74;&#36;":                  return "JMD"; break;
         case "&#165;":                      return "JPY"; break;
         case "&#163;":                      return "JEP"; break;
         case "&#1083;&#1074;":              return "KZT"; break;
         case "&#8361;":                     return "KPW"; break;
         case "&#8361;":                     return "KRW"; break;
         case "&#1083;&#1074;":              return "KGS"; break;
         case "&#8365;":                     return "LAK"; break;
         case "&#163;":                      return "LBP"; break;
         case "&#36;":                       return "LRD"; break;
         case "&#1076;&#1077;&#1085;":       return "MKD"; break;
         case "&#82;&#77;":                  return "MYR"; break;
         case "&#8360;":                     return "MUR"; break;
         case "&#36;":                       return "MXN"; break;
         case "&#8366;":                     return "MNT"; break;
         case "&#77;&#84;":                  return "MZN"; break;
         case "&#36;":                       return "NAD"; break;
         case "&#8360;":                     return "NPR"; break;
         case "&#402;":                      return "ANG"; break;
         case "&#36;":                       return "NZD"; break;
         case "&#67;&#36;":                  return "NIO"; break;
         case "&#8358;":                     return "NGN"; break;
         case "&#8361;":                     return "KPW"; break;
         case "&#107;&#114;":                return "NOK"; break;
         case "&#65020;":                    return "OMR"; break;
         case "&#8360;":                     return "PKR"; break;
         case "&#66;&#47;&#46;":             return "PAB"; break;
         case "&#71;&#115;":                 return "PYG"; break;
         case "&#83;&#47;&#46;":             return "PEN"; break;
         case "&#8369;":                     return "PHP"; break;
         case "&#122;&#322;":                return "PLN"; break;
         case "&#65020;":                    return "QAR"; break;
         case "&#108;&#101;&#105;":          return "RON"; break;
         case "&#1088;&#1091;&#1073;":       return "RUB"; break;
         case "&#163;":                      return "SHP"; break;
         case "&#65020;":                    return "SAR"; break;
         case "&#1044;&#1080;&#1085;&#46;":  return "RSD"; break;
         case "&#8360;":                     return "SCR"; break;
         case "&#36;":                       return "SGD"; break;
         case "&#36;":                       return "SBD"; break;
         case "&#83;":                       return "SOS"; break;
         case "&#82;":                       return "ZAR"; break;
         case "&#8361;":                     return "KRW"; break;
         case "&#8360;":                     return "LKR"; break;
         case "&#107;&#114;":                return "SEK"; break;
         case "&#67;&#72;&#70;":             return "CHF"; break;
         case "&#36;":                       return "SRD"; break;
         case "&#163;":                      return "SYP"; break;
         case "&#78;&#84;&#36;":             return "TWD"; break;
         case "&#3647;":                     return "THB"; break;
         case "&#84;&#84;&#36;":             return "TTD"; break;
         case "&#;":                         return "TRY"; break;
         case "&#36;":                       return "TVD"; break;
         case "&#8372;":                     return "UAH"; break;
         case "&#163;":                      return "GBP"; break;
         case "&#36;":                       return "USD"; break;
         case "&#36;&#85;":                  return "UYU"; break;
         case "&#1083;&#1074;":              return "UZS"; break;
         case "&#66;&#115;":                 return "VEF"; break;
         case "&#8363;":                     return "VND"; break;
         case "&#65020;":                    return "YER"; break;
         case "&#90;&#36;":                  return "ZWD"; break;
         default:                            return $symbol; break;
      }
   }

   // product attributes ---------------------------------------------------------------
   public function IsProduct() {
      // check necessary fields
      if ( trim( $this->fields[ "wpid" ] )  == "" ) { return false; }
      if ( trim( $this->fields[ "title" ] ) == "" ) { return false; }
      return true;
   }
   public function IsOwnProduct() {
      return sanitize_text_field( get_option( "leftsell_shop_id" ) ) == $this->fields["shopid"]; 
   }
   public function IsSubscribed() {
      if ( $this->IsOwnProduct() == false ) { 
         $objShop = new leftsellShop();
         $objMarket = new leftsellMarket( true );
         $objShop = $objMarket->GetJoinedShop( $this->fields[ "shopurl" ] );
         if ( $objShop === false ) { 
            return false; 
         } 
         $objShop = null;
         $objMarket = null;
         return $this->fields[ "pm_subscribed" ] == 1;
      } else {
         return false;
      }
   }
   public function IsImported() {
      return get_post_meta( $this->fields[ "wpid" ], "leftsell_post_pm_imported", true )  == 1;
   }
   public function IsShared() {
      if ( $this->IsOwnProduct() ) {
         return $this->fields[ "private_share" ] == 1;
      } else {
         return false;
      }
   }
   public function IsSubscribedFrom( $remoteshopid, $remoteid ) {
      // return local wpid if found, else === false
      $args = array( 'numberposts' => 1, 'post_type'	=> 'product',
                     'meta_query' => array( 
                                       array('key' => 'leftsell_post_pm_external_id',
                                             'value' => $remoteid,
                                             'compare' => '==', ),
                                       array('key' => 'leftsell_post_shopid',
                                             'value' => $remoteshopid,
                                             'compare' => '==', ),
                                       array('key' => 'leftsell_post_pm_subscribed',
                                             'value' => 1,
                                             'compare' => '==', )
                                       ) ) ;
      $posts = get_posts( $args );
      if( !empty( $posts ) ) {  
         return $posts[0]->ID;
      } else {
         return false;
      }
   }

   // product actions
   public function SaveProduct( $blnUpdated = false) {
      $id = $this->fields[ "wpid" ];
      $post = get_post( $id );
      if( empty( $post ) ) { 
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( "Post to save not found: " . $id . " - " . wp_kses_post( $this->fields[ "title" ] ) );
         $objMarket = null;
         return; 
      } 

      // wp fields
      $post->post_title     = wp_kses_post( $this->fields[ "title" ] );
      $post->post_name      = sanitize_text_field( $this->fields[ "slug" ] );
      $post->post_type      = 'product';
      $post->post_status    = wp_kses_post( sanitize_text_field( $this->fields[ "status" ] ) );
      $post->comment_status = 'open';
      $post->post_content   = wp_kses_post( $this->fields[ "text" ] );
      $post->post_excerpt   = wp_kses_post( $this->fields[ "shortdescription" ] );
      wp_update_post( $post );

      // leftsell fields
      update_post_meta( $id, 'leftsell_post_leftsellid',       $this->fields[ "leftsellid" ] );
      update_post_meta( $id, 'leftsell_post_shopurl',          esc_url( sanitize_text_field( $this->fields[ "shopurl" ] ) ));
      update_post_meta( $id, 'leftsell_post_shopid',           $this->fields[ "shopid" ] );
      update_post_meta( $id, 'leftsell_post_shopname',         wp_kses_post( sanitize_text_field( $this->fields[ "shopname" ] ) ));
      update_post_meta( $id, 'leftsell_post_provision',        $this->fields[ "provision" ] );
      update_post_meta( $id, 'leftsell_post_service',          $this->fields[ "isservice" ] );
      update_post_meta( $id, 'leftsell_post_private_share',    $this->fields[ "private_share" ] );
      update_post_meta( $id, 'leftsell_post_pm_subscribed',    $this->fields[ "pm_subscribed" ] );
      update_post_meta( $id, 'leftsell_post_pm_imported',      $this->fields[ "pm_imported" ] );
      update_post_meta( $id, 'leftsell_post_pm_external_id',   $this->fields[ "externalwpid" ] );
      update_post_meta( $id, 'leftsell_post_currency',         wp_kses_post( sanitize_text_field( $this->fields[ "product_currency" ] ) ) );
      update_post_meta( $id, 'leftsell_post_keywords',         wp_kses_post( sanitize_text_field( $this->fields[ "keywords" ] ) ) );

      // overwrites
      update_post_meta( $id, 'leftsell_post_titletag',         wp_kses_post( sanitize_text_field( $this->fields[ "title" ] ) ) );
      update_post_meta( $id, 'leftsell_post_description',      wp_kses_post( sanitize_text_field( $this->fields[ "shortdescription" ] ) ) );
      
      // kcms
      update_post_meta( $id, 'kcms_content_folder',    wp_kses_post( sanitize_text_field( $this->fields[ "metapath" ] ) ) );
      update_post_meta( $id, 'kcms_content_file',      wp_kses_post( sanitize_text_field( $this->fields[ "metaid" ] ) ) );
      
      // WooCommerce fields
      if ( class_exists( "WooCommerce" ) ) {
         update_post_meta( $id, '_sku',                   wp_kses_post( sanitize_text_field( $this->fields[ "sku" ] ) ) );
         update_post_meta( $id, '_gtin',                  wp_kses_post( sanitize_text_field( $this->fields[ "gtin" ] ) ) );
         update_post_meta( $id, '_mpn',                   wp_kses_post( sanitize_text_field( $this->fields[ "mpn" ] ) ) );
         update_post_meta( $id, '_brand',                 wp_kses_post( sanitize_text_field( $this->fields[ "brand" ] ) ) );
         update_post_meta( $id, '_condition',             wp_kses_post( sanitize_text_field( $this->fields[ "condition" ] ) ) );
         update_post_meta( $id, '_adult',                 wp_kses_post( sanitize_text_field( $this->fields[ "adult" ] ) ) );
         update_post_meta( $id, '_price',                 wp_kses_post( sanitize_text_field( $this->fields[ "price" ] ) ) );
         update_post_meta( $id, '_regular_price',         wp_kses_post( sanitize_text_field( $this->fields[ "regularprice" ] ) ) );
         update_post_meta( $id, '_sale_price',            wp_kses_post( sanitize_text_field( $this->fields[ "saleprice" ] ) ) );
         update_post_meta( $id, '_sale_price_dates_from', wp_kses_post( sanitize_text_field( $this->fields[ "saleprice_dates_from" ] ) ) );
         update_post_meta( $id, '_sale_price_dates_to',   wp_kses_post( sanitize_text_field( $this->fields[ "saleprice_dates_to" ] ) ) );
         update_post_meta( $id, '_tax_status',            wp_kses_post( sanitize_text_field( $this->fields[ "tax_status" ] ) ) );
         update_post_meta( $id, '_purchase_note',         wp_kses_post( sanitize_text_field( $this->fields[ "purchase_note" ] ) ) );
         update_post_meta( $id, '_weight',                wp_kses_post( sanitize_text_field( $this->fields[ "weight" ] ) ) );
         update_post_meta( $id, '_length',                wp_kses_post( sanitize_text_field( $this->fields[ "length" ] ) ) );
         update_post_meta( $id, '_width',                 wp_kses_post( sanitize_text_field( $this->fields[ "width" ] ) ) );
         update_post_meta( $id, '_height',                wp_kses_post( sanitize_text_field( $this->fields[ "height" ] ) ) );
         update_post_meta( $id, '_sold_individually',     $this->MapToYesNo( $this->fields[ "sold_indiviually" ] ) );
         update_post_meta( $id, '_virtual',               $this->MapToYesNo( $this->fields[ "virtual" ] ) );
         update_post_meta( $id, '_visibility',            wp_kses_post( sanitize_text_field( $this->fields[ "visibility" ] ) ) );
         update_post_meta( $id, '_downloadable',          wp_kses_post( sanitize_text_field( $this->fields[ "downloadable" ] ) ) );
         update_post_meta( $id, '_featured',              $this->MapToYesNo( $this->fields[ "featured" ] ) );
         update_post_meta( $id, '_backorders',            $this->MapToYesNo( $this->fields[ "backorders" ] ) );
         // stock
         update_post_meta( $id, '_manage_stock',          wp_kses_post( $this->fields[ "manage_stock" ] ) );
         update_post_meta( $id, '_stock',                 wp_kses_post( $this->fields[ "stock_quantity" ] ) );
         update_post_meta( $id, '_stock_status',          wp_kses_post( sanitize_text_field( $this->fields[ "stock_status" ] ) ) );
         update_post_meta( $id, '_low_stock_amount',      wp_kses_post( $this->fields[ "low_stock_amount" ] ) );
         
         // delete all variations
         $product = wc_get_product( $id );
         foreach ($product->get_children() as $child_id) { 
            $child = wc_get_product($child_id);
            $child->delete(true);
         }
         if ( is_array( $this->fields["variations"] ) ) {
            foreach( $this->fields["variations"] as $variation ) {
               // insert new variations
               $objVariation = new leftsellVariation();
               $objVariation->saveVariation( $id, $variation );
               $objVariation = null;
            }
         }

         // attributes
         if ( $this->fields[ "attributes" ] != "" ) {
            if ( is_array( $this->fields[ "attributes" ] ) ) {
               // write terms
               $the_query = new WP_Term_Query( array( 'taxonomy'    => 'product_type',
                                                      'slug'        => 'variable',
                                                      'hide_empty'  => false ) );
               $termId = $the_query->terms[0]->term_id;
               if ( $termId != "" ) {
                  wp_set_object_terms( $id, $termId, 'product_type' );
               }
               
               // write variations
               $attributes = array();
               foreach( $this->fields[ "attributes" ] as $key=>$attribute ) {
                  $attributes[$key] = array( 'name'          => $attribute[ "name" ],
                                             'value'         => $attribute[ "value" ],
                                             'position'      => $attribute[ "position" ],
                                             'is_visible'    => $attribute[ "is_visible" ],
                                             'is_variation'  => $attribute[ "is_variation" ],
                                             'is_taxonomy'   => $attribute[ "is_taxonomy" ] );
               }
               update_post_meta( $id, '_product_attributes', $attributes );
               wp_set_object_terms( $id, $attributes, 'variable' );
            }
         }
         $product = wc_get_product( $id );
         if ( !empty( $product ) ) { 
            $product->save();
         }
      }
      
      // comments 
      if ( get_option( "leftsell_pm_share_comments" ) == 1 ) {
         $objComments = new leftsellComment();
         $objComments->SavePostComments( $id, $this->fields[ "comments" ] );
         $objComments = null;
      }
   }
   
   // Background Tasks ----------------------------------------------------------------
   private function BackgroundGetFeaturedImage() { 
      // invoke background job to get featured image
      if ( $this->fields[ "imagelink" ] == "" ) { return; }
      if ( leftsellCRON == true ) {
         $args = array( "jobname" => "FetchProductImage",
                                     "wpid"     => $this->fields[ "wpid" ],
                                     "imageurl" => $this->fields[ "imagelink" ] );
         wp_schedule_single_event( time(), 'lefsellEvent', $args  );
      } else {
         $this->GetFeaturedImage( $this->fields[ "wpid" ], $this->fields[ "imagelink" ] );  
      }
   }
   private function BackgroundGetGallery() {
      // invoke background job to get gallery images
      if ( !isset( $this->fields[ "wpid" ] ) ) { return; }
      if ( !isset( $this->fields[ "galleryimages" ] ) ) { return; }
      if ( $this->fields[ "wpid" ] == "" ) { return; }
      if ( leftsellCRON == true ) {
         $args = array( "jobname" => "FetchProductGallery",
                                     "wpid"     => $this->fields[ "wpid" ],
                                     "gallery" => $this->fields[ "galleryimages" ] );
         wp_schedule_single_event( time(), 'lefsellEvent', $args  );
      } else {
         $this->GetGallery( $this->fields[ "wpid" ], $this->fields[ "galleryimages" ]);
      }
   }

   // Attachements --------------------------------------------------------------------
   public function GetFeaturedImage( $wpid, $imageurl ) {
      // background: read remote image and put it to local
      if ( $imageurl == "" ) { return; }
      if ( $wpid == "" )     { return; }
      if ( $this->fields[ "wpid" ] != $wpid ) {
         $this->fromWPId( $wpid );
      }
      if ( $this->IsProduct() == false ) { return; }

      // requirements
      require_once( ABSPATH . "wp-admin/includes/file.php" );
      require_once( ABSPATH . "wp-admin/includes/media.php" );
      require_once( ABSPATH . "wp-admin/includes/image.php" );

      // prepare filename
      preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $imageurl, $matches );
      if ( ! $matches ) {
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( "Invalid image: " . esc_url( $imageurl )  );
         $objMarket = null;
         return;
      }
      $file_array = array();
      $file_array[ 'name' ] = basename( $matches[0] );
      
      // Enable connections to shops without ssl certificate
      add_filter( 'https_local_ssl_verify', '__return_false' );
      add_filter( 'https_ssl_verify', '__return_false' );
      
      // Download file to temp location.
      $file_array[ 'tmp_name' ] = download_url( $imageurl );
      
      // If error storing temporarily, return the error.
      if ( is_wp_error( $file_array['tmp_name'] ) ) {
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( "Error saving image: " . $file_array['tmp_name']->get_error_message()  );
         $objMarket = null;
         return;
      }

      // Do the validation and storage stuff.
      $id = media_handle_sideload( $file_array, $wpid, $this->fields[ "title" ] );

      // If error storing permanently, unlink.
      if ( is_wp_error( $id ) ) {
         @unlink( $file_array[ 'tmp_name' ] );
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( "Error storing image: " . esc_url( $file_array['tmp_name'] )  );
         $objMarket = null;
         return;
      }
      if ( set_post_thumbnail( $wpid, $id ) == true ) {
         // do not log
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( "Product image saved for product " . $this->fields[ "title" ] );
         $objMarket = null;
      } else {
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( "Error getting product image: " . $this->fields[ "imagelink" ]  );
         $objMarket = null;
      }
   }
   public function GetGallery( $wpid, $jsonImages ) {
      // remotely get all images in gallery of product
      if ( $jsonImages == "" )              { return; }
      if ( $wpid == "" )                    { return; }
      if ( $this->fields[ "wpid" ] != $wpid ) {
         $this->fromWPId( $wpid );
      }
      if ( $this->IsProduct() == false )    { return; }
      if ( class_exists( 'WooCommerce' ) ) {
         $product = wc_get_product( $wpid );
         if ( !$product )                   { return; }
      } else                                { return; }
      
      // requirements
      require_once( ABSPATH . "wp-admin/includes/file.php" );
      require_once( ABSPATH . "wp-admin/includes/media.php" );
      require_once( ABSPATH . "wp-admin/includes/image.php" );

      // prepare filenames
      $objJSON = new leftsellJSON();
      $aryImages = $objJSON->FromJSON( $jsonImages );
      $objJSON = null;
      if ( empty( $aryImages ) ) { return; }
      
      // Enable connections to shops without ssl certificate
      add_filter( 'https_local_ssl_verify', '__return_false' );
      add_filter( 'https_ssl_verify', '__return_false' );

      $aryIds = array();
      foreach( $aryImages as $image ) {
         preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image, $matches );
         if ( ! $matches ) { continue; }
         $file_array = array();
         $file_array[ 'name' ] = basename( $matches[0] );
         
         // Download file to temp location.
         $file_array[ 'tmp_name' ] = download_url( $image );

         // If error storing temporarily, return the error.
         if ( is_wp_error( $file_array['tmp_name'] ) ) {
            $objMarket = new leftsellMarket( true );
            $objMarket->PMLog( "Error saving gallery image: " . $image . ' ' . $file_array['tmp_name']->get_error_message()  );
            $objMarket = null;
            continue;
         }
         
         // Do the validation and storage stuff.
         $id = media_handle_sideload( $file_array, $wpid, $this->fields[ "title" ] );

         // If error storing permanently, unlink.
         if ( is_wp_error( $id ) ) {
            @unlink( $file_array[ 'tmp_name' ] );
            $objMarket = new leftsellMarket( true );
            $objMarket->PMLog( "Error storing gallery image: " . esc_url( $file_array['tmp_name'] )  );
            $objMarket = null;
            continue;
         }
         $aryIds[] = $id;
      } 
      // link image ids to attached media of product 
      if ( !empty( $aryIds ) ) {
         update_post_meta($wpid, '_product_image_gallery', implode(',', $aryIds));
      }
   }
   private function DeleteAttachements( $wpid ) {
      global $wpdb;
      $args = array(
            'post_parent' => $wpid,
            'post_type'   => 'attachment', 
            'numberposts' => -1,
            'post_status' => 'any' 
      ); 
      $aryAttachements = get_children( $args );
      if( $aryAttachements ) {
         foreach($aryAttachements as $attachment) {   
            wp_delete_attachment( $attachment->ID, true ); 
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id = ".$attachment->ID);
            wp_delete_post($attachment->ID,true ); 
         }
      }
   }
   private function MapToYesNo( $value ) {
      switch ( $value ) {
         case 0:   return "no";   break;
         case "":  return "no";   break;
         case 1:   return "yes";  break;
         default:  return $value; break;
      }
   }
   private function Insert() {
      $post_id = wp_insert_post(array(
                  'post_title'      => wp_kses_post( sanitize_text_field( $this->fields[ "title" ] ) ),
                  'post_name'       => wp_kses_post( sanitize_text_field( $this->fields[ "slug" ] ) ),
                  'post_type'       => 'product',
                  'post_status'     => 'publish', 
                  'comment_status'  => 'open', 
                  'post_content'    => wp_kses_post( sanitize_text_field( $this->fields[ "text" ] ) ),
                  'post_excerpt'    => wp_kses_post( sanitize_text_field( $this->fields[ "shortdescription" ] ) )
                  ));
      $this->fields[ "wpid" ] = $post_id;
      return $post_id;
   }
   private function Erase() {
      if ( $this->IsProduct() == false ) { return false; }
      require_once( ABSPATH . "wp-includes/pluggable.php" );
      $id = $this->fields[ "wpid" ];
      $this->DeleteAttachements( $id );
      $product = wc_get_product( $id );
      foreach ( $product->get_children() as $child_id ) { 
         $child = wc_get_product( $child_id );
         $child->delete( true );
      }

      // leftsell fields
      delete_post_meta( $id, "leftsell_post_private_share" );
      delete_post_meta( $id, "leftsell_post_service" );
      delete_post_meta( $id, "leftsell_post_pm_subscribed" );
      delete_post_meta( $id, "leftsell_post_pm_imported" );
      delete_post_meta( $id, "leftsell_post_pm_subscribedby" );
      delete_post_meta( $id, "leftsell_post_shopid" );
      delete_post_meta( $id, "leftsell_post_shopname" );
      delete_post_meta( $id, "leftsell_post_pm_external_id" );
      delete_post_meta( $id, "leftsell_post_shopurl" );
      delete_post_meta( $id, "leftsell_post_currency" );
      delete_post_meta( $id, "leftsell_post_titletag" );
      delete_post_meta( $id, "leftsell_post_description" );
      delete_post_meta( $id, "leftsell_post_keywords" );
      
      // kcms fields
      delete_post_meta( $id, "kcms_content_folder" );
      delete_post_meta( $id, "kcms_content_file" );

      // woocommerce fields
      if ( class_exists ( "WooCommerce" ) ) {
         delete_post_meta( $id, "_sku" );
         delete_post_meta( $id, "_gtin" );
         delete_post_meta( $id, "_mpn" );
         delete_post_meta( $id, "_brand" );
         delete_post_meta( $id, "_condition" );
         delete_post_meta( $id, "_adult" );
         delete_post_meta( $id, "_price" );
         delete_post_meta( $id, "_regular_price" );
         delete_post_meta( $id, "_sale_price" );
         delete_post_meta( $id, "_sale_price_dates_from" );
         delete_post_meta( $id, "_sale_price_dates_to" );
         delete_post_meta( $id, "_tax_status" );
         delete_post_meta( $id, "_purchase_note" );
         delete_post_meta( $id, "_weight" );
         delete_post_meta( $id, "_length" );
         delete_post_meta( $id, "_width" );
         delete_post_meta( $id, "_height" );
         delete_post_meta( $id, "_product_attributes" );
         delete_post_meta( $id, "_sold_individually" );
         delete_post_meta( $id, "_virtual" );
         delete_post_meta( $id, "_stock_status" );
         delete_post_meta( $id, "_visibility" );
         delete_post_meta( $id, "_downloadable" );
         delete_post_meta( $id, "_featured" );
         delete_post_meta( $id, "_manage_stock" );
         delete_post_meta( $id, "_backorders" );
         delete_post_meta( $id, "total_sales" );
         delete_post_meta( $id, "_wp_attachment_metadata" );
         delete_post_meta( $id, "_wp_attached_file" );
      }

      // wordpress comments
      $aryComments = get_comments( array( 'post_id' => $id ) );
      foreach ( $aryComments as $objComment ) { 
         $aryComment = $objComment->to_array();
         delete_comment_meta( $aryComment[ "comment_ID" ], 'rating', true);
         delete_comment_meta( $aryComment[ "comment_ID" ], 'leftsellshopid', true);
         delete_comment_meta( $aryComment[ "comment_ID" ], 'leftsellExtCommentId', true);
         wp_delete_comment( $aryComment[ "comment_ID" ] );
         $aryComment = null;
      }
      $aryComments = null;

      // wordpress post
      wp_delete_post( $id , false );

      $objMarket = new leftsellMarket( true );
      $objMarket->PMLog( esc_html__( 'Product deleted', 'leftsell' ) . ": " . $this->fields[ "title" ] );
      $objMarket = null;
      return true;
   }
   

   // V2 Private Market ---------------------------------------------------------------
   public function SharePrivate() {
      // set product as private shared
      if ( $this->fields[ "wpid" ] == "" ) { return "Error 2002"; }
      if ( $this->fields[ "wpid" ] < 1 )   { return "Error 2003"; }
      update_post_meta( $this->fields[ "wpid" ], 'leftsell_post_private_share', 1 );
      if ( sanitize_text_field( get_post_meta( $this->fields[ "wpid" ], "leftsell_post_private_share", true ) ) == 1 ) {
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( esc_html__( 'Product shared', 'leftsell' ) . ": " . $this->fields[ "title" ] );
         $objMarket = null;
         return "OK";
      } else {
         return "Error 2004";
      }
   }
   public function UnSharePrivate() {
      // set product as private shared
      if ( $this->fields[ "wpid" ] == "" ) { return "Error 2032"; }
      if ( $this->fields[ "wpid" ] < 1 )   { return "Error 2033"; }

      // get all subscriptions of product
      $objJSON = new leftsellJSON();
      $arySubscribingShops = $objJSON->FromJSON( get_post_meta( $this->fields[ "wpid" ], "leftsell_post_pm_subscribedby", true ) );
      $blnMissing = false;
      if ( is_array( $arySubscribingShops ) ) {
         if ( count( $arySubscribingShops ) > 0 ) {
            foreach( $arySubscribingShops as $shopid ) {
               $objMarket = new leftsellMarket( true );
               $remoteURL = "";
               // get remote shop url
               foreach( $objMarket->m_aryShops as $objShop ) {
                  if ( $objShop->m_intId == $shopid ) {
                     $remoteURL = $objShop->m_strURL;
                  }
               }
               // send delete subscriptions for all shops
               if ( trim( $remoteURL ) != "" ) {
                  $objMsg = new leftsellMessage( "pmunshared" );
                  $objMsg->m_aryFields[ "remotewpid" ]   = $this->fields[ "wpid" ];
                  $objMsg->m_aryFields[ "remoteshopid" ] = sanitize_text_field( get_option( "leftsell_shop_id" ) );
                  $result = $objMsg->SendMessage( $remoteURL . leftsellROUTE );   
                  if ( $result == "OK" ) {
                     // delete subscription entry
                     $this->RemoveSubscriptionBy( $shopid );
                  } else {
                     $blnMissing = true;
                     $objMarket = new leftsellMarket( true );
                     $objMarket->PMLog( esc_html__( 'Remote shop did not remove subscription for', 'leftsell' ) . ": " . $this->fields[ "title" ] );
                     $objMarket = null;
                  }
               }
            }
         }
      }
      if ( $blnMissing ) {
         return esc_html( 'Could not reach all subscribing shops', 'leftsell' );
      } else {
         // do unshare
         $arySubscribingShops = $objJSON->FromJSON( get_post_meta( $this->fields[ "wpid" ], "leftsell_post_pm_subscribedby", true ) );
         if ( is_array( $arySubscribingShops ) ) {
            if ( count( $arySubscribingShops ) == 0 ) {
               update_post_meta( $this->fields[ "wpid" ], 'leftsell_post_private_share', 0 );
               if ( sanitize_text_field( get_post_meta( $this->fields[ "wpid" ], "leftsell_post_private_share", true ) ) == 0 ) {
                  $objMarket = new leftsellMarket( true );
                  $objMarket->PMLog( esc_html__( 'Product unshared', 'leftsell' ) . ": " . $this->fields[ "title" ] );
                  $objMarket = null;
                  return esc_html( 'unshared', 'leftsell' );
               } else {
                  return "Error 2034";
               }
            } 
         } 
      }
   }
   public function GotUnshared( $remotewpid, $remoteshopid ) {
      // product got unshared by publisher
      $this->GetFromExternalId( $remoteshopid, $remotewpid );
      if ( $this->IsProduct() ) {
         if ( $this->IsSubscribedFrom( $remoteshopid, $remotewpid ) ) { 
            if ( $this->Erase() ) {
               $objMarket = new leftsellMarket( true );
               $objMarket->PMLog( esc_html__( 'Product got unshared by publisher and was deleted', 'leftsell' ) . ": " . $this->fields[ "title" ] );
               $objMarket = null;
               return "OK";
            } else { return "NAK"; }
         } else { return "OK"; }
      } else {
         return "OK";
      }
   }
   public function GetFromExternalId( $remoteshopid, $remoteid ) {
      // local get product from an external id and shopid
      $args = array( 'numberposts' => 1, 'post_type'	=> 'product',
                     'meta_query' => array( 
                                       array('key' => 'leftsell_post_pm_external_id',
                                             'value' => $remoteid,
                                             'compare' => '==', ),
                                       array('key' => 'leftsell_post_shopid',
                                             'value' => $remoteshopid,
                                             'compare' => '==', )
                                       ) ) ;
      $posts = get_posts( $args );
      if ( !is_array( $posts ) )  { return false; }
      if ( count( $posts ) == 0 ) { return false; }
      if( !empty( $posts ) ) { 
         $post = $posts[ 0 ];
         if ( empty( $post ) )           { return false; }
         if ( $post->ID == "" )          { return false; }
         if ( intval( $post->ID ) == 0 ) { return false; }
         return $this->fromPost( $post );
      } else {
         return false;
      }
   }
   public function AddPMSubscriptionBy( $localwpid, $remoteshopid ) {
      // a shop from private market has subscribed to this product
      $this->fromWPId( $localwpid );
      if ( $this->IsProduct() == false ) { return "error remote product id"; }
      $objJSON = new leftsellJSON();
      $arySubscribingShops = $objJSON->FromJSON( get_post_meta( $localwpid, "leftsell_post_pm_subscribedby", true ) );
      // check already in list
      $blnAlreadySubscribed = false;
      if ( is_array( $arySubscribingShops ) ) {
         foreach( $arySubscribingShops as $shopid ) {
            if ( $shopid == $remoteshopid ) {
               $blnAlreadySubscribed = true;
            }
         }
      }
      // add to list
      if ( $blnAlreadySubscribed == false ) {
         $arySubscribingShops[] = $remoteshopid;
         update_post_meta( $localwpid, 'leftsell_post_pm_subscribedby', $objJSON->ToJSON( $arySubscribingShops ) );
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( esc_html__( 'Remote shop subscribed to product', 'leftsell' ) . ": " . $this->fields[ "title" ] );
         $objMarket = null;
      }
      return "OK";
   }
   private function RemoveSubscriptionBy( $remoteshopid ) {
      // remove entry for subscribing shop
      if ( $this->IsProduct() == false ) { return; }
      $objJSON = new leftsellJSON();
      $arySubscribingShops = $objJSON->FromJSON( get_post_meta( $this->fields[ "wpid" ], "leftsell_post_pm_subscribedby", true ) );
      $aryNew = array();
      if ( is_array( $arySubscribingShops ) ) {
         foreach( $arySubscribingShops as $shopid ) {
            if ( $shopid != $remoteshopid ) {
               $aryNew[] = $shopid;
            }
         }
      }
      update_post_meta( $this->fields[ "wpid" ], 'leftsell_post_pm_subscribedby', $objJSON->ToJSON( $aryNew ) );
      $objMarket = new leftsellMarket( true );
      $objMarket->PMLog( 'Removed subscription by remote shop ' . $remoteshopid . ' for product ' . $this->fields[ "title" ] );
      $objMarket = null;
   }
   public function PMUnSubscribe( $shopid, $wpid ) {
      if ( trim( $shopid ) == "" ) { return "Communcation error 20400"; }
      if ( trim( $wpid ) == "" )   { return "Communcation error 20401"; }
      
      // is product subscribed?
      $this->GetFromExternalId( $shopid, $wpid );
      if ( $this->IsProduct() == false )    { return esc_html__( 'Error unsubscribing: product not found', 'leftsell' ); }
      if ( $this->IsSubscribed() == false ) { return esc_html__( 'Error unsubscribing: product not subscribed', 'leftsell' ); }
      
      // send notification to publishing shop
      $objMsg = new leftsellMessage( "pmunsubscribe" );
      $objMsg->m_aryFields[ "localwpid" ]   = $wpid;
      $objMsg->m_aryFields[ "remoteshopid" ] = sanitize_text_field( get_option( "leftsell_shop_id" ) );
      $result = $objMsg->SendMessage( $this->fields[ "shopurl" ] . leftsellROUTE );    // -> RemoveSubscription

      if ( $result == "NAK" ) { 
         // was not subscribed
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( esc_html__( 'Remote shop did not remove subscription for', 'leftsell' ) . ": " . $this->fields[ "title" ] );
         $objMarket = null;
      }
      if ( $result == "OK" ) {
         // remote subscription removed
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( esc_html__( 'Remote shop was successfully notified to remove subscription for', 'leftsell' ) . ": " . $this->fields[ "title" ] );
         $objMarket = null;
      } else {
         // tell server
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( esc_html__( 'Remote shop answered on remove subscription for', 'leftsell' ) . ": " . $this->fields[ "title" ] . " " . $result );
         $objMarket = null;
      }
      // erase product
      if ( $this->Erase() == false ) {
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( esc_html__( 'Deleting failed for product', 'leftsell' ) . ": " . $this->fields[ "title" ] );
         $objMarket = null;
         return esc_html__( 'Error deleting product', 'leftsell' );
      } else {
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( esc_html__( 'Subscribed product successfully deleted', 'leftsell' ) . ": " . $this->fields[ "title" ] );
         $objMarket = null;
         return esc_html__( 'Deleted', 'leftsell' );
      }
   }
   public function RemoveSubscription( $localwpid, $remoteshopid ) {
      // remove a subscription ( asked from subscribing shop via REST )
      $objJSON = new leftsellJSON();
      $arySubscribingShops = $objJSON->FromJSON( get_post_meta( $localwpid, "leftsell_post_pm_subscribedby", true ) );
      $aryNew = array();
      $blnFound = false;
      if ( is_array( $arySubscribingShops ) ) {
         foreach( $arySubscribingShops as $shopid ) {
            if ( $shopid != $remoteshopid ) {
               $aryNew[] = $shopid;
            } else {
               $blnFound = true;
            }
         }
      }
      if ( $blnFound == true ) {
         update_post_meta( $localwpid, 'leftsell_post_pm_subscribedby', $objJSON->ToJSON( $aryNew ) );
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( esc_html__( 'Remote shop removed subscription for product', 'leftsell' ) . " :" . $localwpid );
         $objMarket = null;
         $objJSON = null;
         return "OK";
      } else {
         $objJSON = null;
         return "NAK";
      }
   }
   public function PMImport( $shopurl, $remotewpid ) {
      // import product from private market shop
      if ( trim( $shopurl ) == "" ) { return "Communcation error 20500"; }
      $result = $this->GetPMExternalProduct( $shopurl, $remotewpid );
      if ( $result == NULL ) { 
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( esc_html__( 'Imported product', 'leftsell' ) . ": " . $this->fields[ "title" ] );
         $objMarket = null;
         return esc_html__( 'Error getting product', 'leftsell' ); 
      }
      $result = null;
      // set import fields
      $this->fields[ "private_share" ] = 0;
      $this->fields[ "pm_subscribed" ] = 0;
      $this->fields[ "pm_imported" ]   = 1;
      $this->fields[ "shopurl" ]       = $shopurl;
      if ( get_option("leftsell_pm_default_sanitize") == 1 ) {
         $this->fields[ "text" ]             = $this->GetSanitizedText( $this->fields[ "text" ] );
         $this->fields[ "shortdescription" ] = $this->GetSanitizedText( $this->fields[ "shortdescription" ] );
      } 
      // create post
      $this->fields[ "wpid" ] = $this->Insert();
      
      // images
      if ( get_option("leftsell_pm_sync_pics") == 1 ) {
         // featured image
         if ( isset( $this->fields[ "imagelink" ] ) ) {
            $this->BackgroundGetFeaturedImage();
         }
         // gallery
         if ( isset( $this->fields[ "galleryimages" ] ) ) {
            $this->BackgroundGetGallery();
         }
      }
      $this->SaveProduct();
      $objMarket = new leftsellMarket( true );
      $objMarket->PMLog( esc_html__( 'Imported product', 'leftsell' ) . ": " . $this->fields[ "title" ] );
      $objMarket = null;
      return esc_html__( 'imported', 'leftsell' );
   }
   public function DeleteImported( $wpid, $remoteshopid) {
      if ( intval( $wpid ) == 0 )         { return esc_html( 'Error deleting product', 'leftsell' ); }
      if ( trim( $remoteshopid ) == "" )  { return esc_html( 'Error deleting imported product', 'leftsell' ); }
      $this->fromWPId( $wpid );
      if ( $this->IsProduct() == false )  { return esc_html( 'Error getting imported product', 'leftsell' ); }
      if ( $this->IsImported() == false ) { return esc_html( 'Error deleting imported product 200505', 'leftsell' ); }
      if ( $this->fields[ "shopid" ] != $remoteshopid ) { return esc_html( 'Error deleting imported product 200506', 'leftsell' ); }
      if ( $this->Erase() == false ) {
         return esc_html( 'Error deleting imported product 200507', 'leftsell' ); 
      } else {
         return esc_html( 'Imported product deleted', 'leftsell' ); 
      }
   }

   // updating ------------------------------------------------------------------------
   public function OnLocalUpdate( $wpid ) { 
      // fired by hook, already tested: product is shared and has subscribers 
      // -> create background tasks for each shop update
      $objThisShop = new leftsellShop();
      $objMarket = new leftsellMarket( true );
      $objMarketShops = new leftsellMarket( true );
      $objJSON = new leftsellJSON();
      $arySubscribingShops = $objJSON->FromJSON( get_post_meta( $wpid, "leftsell_post_pm_subscribedby", true ) );
      if ( is_array( $arySubscribingShops ) ) {
         foreach( $arySubscribingShops as $shopid ) {
            if ( isset( $objMarketShops->m_aryShops[ $shopid ] ) ) {
               $objShop = new leftsellShop();
               $objShop = $objMarketShops->m_aryShops[ $shopid ];
               if ( !$objShop == null ) {
                  $url = trim( $objShop->m_strURL );
                  if ( substr( $url, strlen( $url ) - 1, 1 ) != "/" ) {
                     $url .= "/";
                  }
                  if ( leftsellCRON == true ) {
                     $args = array( "jobname" => "PMnotifyProductUpdate",
                                    "wpid"    => $wpid,
                                    "url"     => $url . leftsellROUTE );
                     wp_schedule_single_event( time(), 'lefsellEvent', $args  );
                  } else {
                     $objMarket->PMLog( esc_html__( 'Sending product update notification to', 'leftsell' ) . ": " . $url . " (" . $wpid . ")" );
                     $objMarketShops->PMInformProductUpdate( $wpid, $url );
                  }
               } else {
                  $objMarket->PMLog( esc_html__( "Error initializing communication with shop id: ", 'leftsell' ) . $shopid );
               }
               $objShop = null;
            } 
         }
         $objMarket = null;
         $objMarketShops = null;
      } 
   }
   public function DeliverToPM( $shopurl, $wpid ) { 
      // product is requestest by private market
      if ( get_option( "leftsell_privatemarket" ) == 1 ) {
         $objMarket = new leftsellMarket( true );
         if ( $objMarket->IsShopInPM( $shopurl ) or leftsellCONDEBUG == true ) {
            // update subscriptions
            $objTempShop = new leftsellShop();
            $remoteshopid = $objTempShop->GetId( $shopurl );
            if ( $remoteshopid != false ) {
               $this->AddPMSubscriptionBy( $wpid, $remoteshopid );
            }
            $objTempShop = null;
            
            $this->fromWPId( $wpid );
            // set fields for subscriber
            $this->fields[ "private_share" ] = 0;
            $this->fields[ "pm_subscribed" ] = 1;
            $this->fields[ "pm_imported" ]   = 0;
            $this->fields[ "externalwpid" ]  = $wpid;
            $this->fields[ "shopid" ]        = get_option( "leftsell_shop_id" );
            // sanitize
            $this->fields[ "text" ] = $this->GetSanitizedText( $this->fields[ "text" ] );
            $this->fields[ "shortdescription" ] = $this->GetSanitizedText( $this->fields[ "shortdescription" ] );
            
            $objMarket = new leftsellMarket( true );
            $objMarket->PMLog( esc_html__( 'Delivering product ', 'leftsell' ) . ": " . $this->fields[ "title" ] );
            $objMarket = null;
            return $this->fields;
         } else {
            $objMarket = new leftsellMarket( true );
            $objMarket->PMLog( esc_html__( 'Requesting shop is not in market', 'leftsell' ) . ": " . $shopurl );
            $objMarket = null;
            return "Market error 20301";
         }
      } else {
         return esc_html__( 'Market not enabled', 'leftsell' );
      }
   }
   public function FetchRemote( $publisherid, $remotewpid ) {
      // received msg PMnotifyProductUpdate to update a product or on manual subscribing 
      if ( intval( $publisherid ) == 0 ) { return; }
      if ( intval( $remotewpid )  == 0 ) { return; }
      $objMarket = new leftsellMarket( true );
      if ( $objMarket->IsShopIdInPM( $publisherid ) ) {
         $objShop = new leftsellShop();
         $objShop = $objMarket->m_aryShops[ $publisherid ];
         if ($objShop != null ) {
            // get external data into this
            $result = $this->GetPMExternalProduct( $objShop->m_strURL, $remotewpid );
            if ( $result == NULL ) { 
               $objMarket->PMLog( "Getting product failed from shop " . $objShop->m_strURL . " - id: " . $remotewpid );
               return;
            }
            // does local product exist?
            $objProduct = new leftsellProduct();
            
            // no local product
            if ( $objProduct->GetFromExternalId( $publisherid, $remotewpid ) === false ) {
               // local product not found -> insert
               // set subscribed fields
               $this->fields[ "private_share" ] = 0;
               $this->fields[ "pm_subscribed" ] = 1;
               $this->fields[ "pm_imported" ] = 0;
               $this->fields[ "shopurl" ]     = $objShop->m_strURL;
               
               // could be existing
               $newPost = $this->IsSubscribedFrom( $objShop->m_strURL, $remotewpid );
               if ( $newPost === false ) {
                  // insert product
                  $newPost = $this->Insert();
                  if ( intval( $newPost ) == 0 ) { $objMarket->PMLog( esc_html__( 'Error saving product', 'leftsell' ) ); return; }
                  // set to subscribedby on origin shop
                  $objMsg = new leftsellMessage( "pmissubsribed" );
                  $objMsg->m_aryFields[ "localwpid" ]   = $remotewpid;
                  $objMsg->m_aryFields[ "remoteshopid" ] = sanitize_text_field( get_option( "leftsell_shop_id" ) );
                  $objMsg->SendMessage( $objShop->m_strURL . leftsellROUTE );
               } 
            } else {
               // local product found -> update
               // product fetched -> prepare for update
               $this->fields[ "wpid" ]             = $objProduct->fields[ "wpid" ];
               $this->fields[ "externalwpid" ]     = $remotewpid;
               $this->fields[ "private_share" ]    = 0;
               $this->fields[ "pm_subscribed" ]    = 1;
               $this->fields[ "pm_imported" ]      = 0;
               $this->fields[ "shopurl" ]          = $objShop->m_strURL;
               $this->fields[ "shopid" ]           = $publisherid;
            }

            // modifications
            if ( get_option("leftsell_pm_default_sanitize") == 1 ) {
               $this->fields[ "text" ]             = $this->GetSanitizedText( $this->fields[ "text" ] );
               $this->fields[ "shortdescription" ] = $this->GetSanitizedText( $this->fields[ "shortdescription" ] );
            } 
            // images
            if ( get_option("leftsell_pm_sync_pics") == 1 ) {
               $this->DeleteAttachements( $this->fields[ "wpid" ] );
               // featured image
               if ( isset( $result->fields[ "imagelink" ] ) ) {
                  $this->fields[ "imagelink" ] = $result->fields[ "imagelink" ];
                  $this->BackgroundGetFeaturedImage();
                  //$this->GetFeaturedImage( $this->fields[ "wpid" ], $this->fields[ "imagelink" ] );
               }
               // gallery
               if ( isset( $this->fields[ "galleryimages" ] ) ) {
                  $this->BackgroundGetGallery();
               }
            }
            $this->SaveProduct( true);
            $objMarket->PMLog( "Product saved " . $this->fields["title"] );
            $objProduct = null;
         }
      } else {
         $objMarket->PMLog( "Update reqeusted by shop not in market " . $publisherid . " - prod: " . $remotewpid );
      }
      $objMarket = null;
   }
   private function GetSanitizedText( $text ) {
      if ( get_option( "leftsell_pm_default_sanitize" ) == 1 ) {
         $buffer = $text;
         $buffer = strip_tags( $buffer, '<b><i><sup><sub><em><img><strong><u><br><br/><br /><span><h1><h2><h3><h4><h5><blockquote><del><ins><ul><ol><li><code><p><div><hr><hr />' );
         return $buffer;
      } else {
         return $text;
      }
   }
   public function GetPMExternalProduct( $shopurl, $wpid ) {
      // get product from external shop in private market
      if ( trim( $shopurl ) == "" ) { return NULL; }
      if ( trim( $wpid ) == "" )    { return NULL; }
      // get product from REST
      $objMsg = new leftsellMessage( "getpmproduct" );
      $objMsg->m_aryFields[ "productwpid" ] = $wpid;
      $result = $objMsg->SendMessage( $shopurl . leftsellROUTE );
      
      if ( is_array( $result ) ) {
         $this->FromArray( $result );
         if ( $this->IsProduct() == false ) { 
            $objMarket = new leftsellMarket( true );
            $objMarket->PMLog( esc_html__( 'External product not idetified as product ', 'leftsell' ) . ": " .  $shopurl. " - Product: ". $wpid);
            $objMarket = null;
            return NULL; 
         }
         // changes for local use
         $this->fields[ "wpid" ]             = ""; // local id not yet known
         $this->fields[ "externalwpid" ]     = $wpid;
         $this->fields[ "private_share" ]    = 0;
         $this->fields[ "pm_subscribed" ]    = 0;
         $this->fields[ "pm_subscribedby" ]  = "";
         $this->fields[ "pm_imported" ]      = 0;
         $this->fields[ "shopurl" ]          = $shopurl;
         
      } else {
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( esc_html__( 'External product not completely readable ', 'leftsell' ) . ": " .  $shopurl. " - Product: ". $wpid);
         $objMarket = null;
         return NULL;
      }
      return $this;
   }
} // end class leftsellProduct
?>