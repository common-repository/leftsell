<?php
/**
 * Purpose:           Show the Settings admin page
 * Function URI:      https://leftsell.com/
 * Version:           2.0.2
 * Author:            Kundschaft Schweiz
 * Author URI:        https://kundschaft.ch/ 
 **/
   defined( 'ABSPATH' ) || exit;
   global $current_user;
   
   // Help functions ------------------------------------------------------------------
   function leftsell_BuildRowOption( $option, $title, $descripton ) {
      // build a table row for a LeftSell option
      $buffer = '<tr>';
      $buffer .= '<td><strong>' . $title . '</strong>' . '<br />' . $descripton . '</td>';
      $buffer .= '<td>';
      $objUI = new leftsellUI();
      $buffer .= $objUI->GetOptionSwitch( $option );
      $objUI = null;
      $buffer .= '</td>';
      $buffer .= '</tr>';
      return $buffer;
   }

   function leftsell_register_mysettings() { 
      register_setting( 'leftsell_option-group', 'leftsell_additionalheader' );
   }
   add_action( 'admin_init', 'leftsell_register_mysettings' );

   // the page Tools ------------------------------------------------------------------
   function leftsell_page_tools() {
      if ( !current_user_can( 'manage_options' ) ) { echo esc_html__( 'No access', 'leftsell' ); return; }
      echo '<div class="wrap">';
      echo '<img src="' . plugin_dir_url( __FILE__ ) . '../includes/images/logo.png" id="leftselllogo" />';
      echo '<h1>LeftSell V ' . esc_html( leftsellVERSION ) .'</h1>';
      echo esc_html__( 'Sell and let sell services and products professionally', 'leftsell' );
      $objUI = new leftsellUI();
      echo $objUI->BuildNav(
         array( "Tweaks" => esc_html__( 'Hacks', 'leftsell' ),
                "SEO"    => esc_html__( 'SEO', 'leftsell' ),
                "Maps"   => esc_html__( 'Sitemaps', 'leftsell' ),
                "Header" => esc_html__( 'Header', 'leftsell' ),
                "Shop"   => esc_html__( 'Shop', 'leftsell' )
         ) , "tabTweaks"
      );
      $curTab = "tabTweaks";
      if ( isset( $_GET["tab"] ) ) { $curTab = sanitize_text_field( $_GET["tab"] ); }
      echo '<div class="leftsell_content">';
      
      // Tweaks -----------------------------------------------------------------------    
      echo '<div id="tabTweaks">';
      if ( $curTab == "tabTweaks" ) { 
         echo '<h2>' . esc_html__( 'Wordpress Admin', 'leftsell' ) .'</h2>';
         echo '<table class="leftsell_option">';

         // WP Admin Messages ---------------------------------------------------------
         echo leftsell_BuildRowOption(
            'leftsell_adminmessages',
            esc_html__( 'Disable Wordpress Admin Messages', 'leftsell' ),
            esc_html__( 'Disable most notifications inside Wordpress Admin', 'leftsell' )
         );

         // WP Admin Thanks  ----------------------------------------------------------
         echo leftsell_BuildRowOption(
            'leftsell_adminthanks',
            esc_html__( 'Disable Wordpress Admin Thank You', 'leftsell' ),
            esc_html__( 'Remove Thank you for creating with...', 'leftsell' )
         );

         // Security ------------------------------------------------------------------
         echo '<tr colspan="2"><td style="padding:40px 0 0 0"><h2>' . esc_html__( 'Security', 'leftsell' ) . '</h2></td></tr>';
         // XML RPC -------------------------------------------------------------------
         echo leftsell_BuildRowOption(
            'leftsell_disablexmlrpc',
            esc_html__( 'Disable Wordpress XML RPC', 'leftsell' ),
            esc_html__( 'Normally you do not need XML RPC, it is a security risk', 'leftsell' )
         );

         echo '<tr colspan="2"><td style="padding:40px 0 0 0"><h2>' . esc_html__( 'Site functions', 'leftsell' ) . '</h2></td></tr>';
         // WP Shortlinks -------------------------------------------------------------
         echo leftsell_BuildRowOption(
            'leftsell_removeshortlinks',
            esc_html__( 'Disable Wordpress Shortlinks creation in Header', 'leftsell' ),
            esc_html__( 'Might confuse search engines', 'leftsell' )
         );

         // Footer --------------------------------------------------------------------
         echo leftsell_BuildRowOption(
            'leftsell_footer',
            esc_html__( 'Create own footer', 'leftsell' ),
            esc_html__( 'Uses a WordPress page called ~footer as content for your footer', 'leftsell' )
         );

         // Indivial Header --------------------------------------------------------------------
         echo leftsell_BuildRowOption(
            'leftsell_pageheaders',
            esc_html__( 'Header per page', 'leftsell' ),
            esc_html__( 'Enable header additions per page (e.g. css, js)', 'leftsell' )
         );

         echo '<tr colspan="2"><td style="padding:40px 0 0 0"><h2>' . esc_html__( 'Comment functions', 'leftsell' ) . '</h2></td></tr>';
         // Comments Author -----------------------------------------------------------
         echo leftsell_BuildRowOption(
            'leftsell_comments_author',
            esc_html__( 'remove Author', 'leftsell' ),
            esc_html__( 'Remove author in standard WordPress Comment function', 'leftsell' )
         );
         // Comments E-Mail -----------------------------------------------------------
         echo leftsell_BuildRowOption(
            'leftsell_comments_email',
            esc_html__( 'remove E-Mail', 'leftsell' ),
            esc_html__( 'Remove email in standard WordPress Comment function', 'leftsell' )
         );
         // Comments URL --------------------------------------------------------------
         echo leftsell_BuildRowOption(
            'leftsell_comments_url',
            esc_html__( 'remove Website', 'leftsell' ),
            esc_html__( 'Remove website url in standard WordPress Comment function', 'leftsell' )
         );
         // Comments Cookies ----------------------------------------------------------
         echo leftsell_BuildRowOption(
            'leftsell_comments_cookies',
            esc_html__( 'remove Cookies', 'leftsell' ),
            esc_html__( 'Remove Cookie option to store values in standard WordPress Comment function', 'leftsell' )
         );
         
         echo '</table>';
      }
      echo '</div>';

      // SEO --------------------------------------------------------------------------
      echo '<div id="tabSEO">';
      if ( $curTab == "tabSEO" ) {
         echo '<h2>' . esc_html__( 'Google Metas', 'leftsell' ) . '</h2>';
         echo '<table class="leftsell_option">';

         // Google Metas --------------------------------------------------------------
         // Own Title Tags ------------------------------------------------------------
         echo leftsell_BuildRowOption(  
            'leftsell_titletag',
            esc_html__( 'Add Own Page Titles', 'leftsell' ),
            esc_html__( 'Add meta titletag and exchange <title>. Edit for each post, page and product.', 'leftsell' )
         );

         // Keywords ------------------------------------------------------------------
         echo leftsell_BuildRowOption( 
            'leftsell_keywords',
            esc_html__( 'Add Keyword Meta Tag', 'leftsell' ),
            esc_html__( 'Add meta tag keyword in header. Edit for each post, page and product.', 'leftsell' )
         );

         // Descriptions --------------------------------------------------------------
         echo leftsell_BuildRowOption( 
            'leftsell_description',
            esc_html__( 'Add Description Meta Tag', 'leftsell' ),
            esc_html__( 'add meta tag description in header. Edit for each post, page and product.', 'leftsell' )
         );

         // Google JSON ---------------------------------------------------------------
         echo '<tr colspan="2"><td style="padding:40px 0 0 0"><h2>' . esc_html__( 'Google JSON', 'leftsell' ) . '</h2></td></tr>';
         
         // Google Breadcrumbs --------------------------------------------------------
         echo leftsell_BuildRowOption( 
            'leftsell_googlebreadcrumbs',
            esc_html__( 'Create Google JSON Breadcrumbs', 'leftsell' ),
            esc_html__( 'Pages base: parent/child, Post base: category-tree, Product base: category-tree', 'leftsell' )
         );
         echo '<tr colspan="2"><td style="padding:40px 0 0 0"><h2>' . esc_html__('Open Graphs', 'leftsell') . '</h2></td></tr>';
         
         // OGs  ----------------------------------------------------------------------
         // OG Image ------------------------------------------------------------------
         echo leftsell_BuildRowOption( 
            'leftsell_ogimage',
            esc_html__( 'Add OG: Image', 'leftsell' ),
            esc_html__( 'use featured image to header tag og:image', 'leftsell' )
         );

         // OG Title ------------------------------------------------------------------
         echo leftsell_BuildRowOption( 
            'leftsell_ogtitle',
            esc_html__( 'Add OG: Title', 'leftsell' ),
            esc_html__( 'Put Title Tag to header tag og:title. Uses own Page title if available', 'leftsell' )
         );

         // OG Type -------------------------------------------------------------------
         echo leftsell_BuildRowOption( 
            'leftsell_ogtype',
            esc_html__( 'Add OG: Type', 'leftsell' ),
            esc_html__( 'determine type website / article and put it to header tag og:type', 'leftsell' )
         );

         // OG URL --------------------------------------------------------------------
         echo leftsell_BuildRowOption( 
            'leftsell_ogurl',
            esc_html__( 'Add OG: URL', 'leftsell' ),
            esc_html__( 'to header tag og:url', 'leftsell' )
         );
         echo '</table>';
      }
      echo '</div>';

      
      // Sitemaps ---------------------------------------------------------------------
      echo '<div id="tabMaps">';
      if ($curTab == "tabMaps") {
         echo '<table class="leftsell_option">';

         // XML Sitemap ---------------------------------------------------------------
         echo leftsell_BuildRowOption( 
            'leftsell_googlesitemap',
            esc_html__( 'Create Google Sitemap on every save of page', 'leftsell' ),
            '<a href="' . get_home_url() . '/sitemap.xml" target="_blank">' . get_home_url() . '/sitemap.xml</a><br />'.
            esc_html__( 'Algorithm from LeftSell', 'leftsell')
         );
         echo '<tr colspan="2"><td style="padding:0px 0 60px 0">
                  <button id="btnLeftSellSiteMap" 
                     onclick="var strCall = {\'call\':\'creategooglesitemap\'};
                              strCall = JSON.stringify(strCall);
                              leftsellAJAX_event(strCall, \'btnLeftSellSiteMap\' , \'\' );">' . 
                              esc_html__( 'Create Sitemap now', 'leftsell' ) . '</button>
               </td></tr>';

         // Google Merchant Center Feed -----------------------------------------------
         echo leftsell_BuildRowOption( 
                              'leftsell_googleproductxml',
                              esc_html__( 'Create Google Merchant Center feed of products', 'leftsell' ),
                              '<a href="' . get_home_url() . '/products.xml" target="_blank">' . get_home_url() . '/products.xml</a><br />'.
                              esc_html__( 'Algorithm from LeftSell', 'leftsell' )
         );
         echo '<tr colspan="2"><td style="padding:0 0 60px 0">
                  <button id="btnLeftSellProductMap"
                     onclick="var strCall = {\'call\':\'creategoogleproductmap\'};
                              strCall = JSON.stringify(strCall);
                              leftsellAJAX_event(strCall, \'btnLeftSellProductMap\' , \'\' );">' . 
                              esc_html__( 'Create Product Map now', 'leftsell' ) . '</button>
               </td></tr>';
         echo '</table>';
      }
      echo '</div>';


      // Shop -------------------------------------------------------------------------
      echo '<div id="tabShop">';
      if ($curTab == "tabShop") {

         // WooCommerce ---------------------------------------------------------------
         if ( class_exists( 'WooCommerce' ) ) {
            echo '<h2>Woocommerce</h2>';
            echo '<table class="leftsell_option">';

            // Product type Service ---------------------------------------------------
            echo leftsell_BuildRowOption( 
               'leftsell_shop_service',
               esc_html__( 'Add Product Type Service', 'leftsell' ),
               esc_html__( 'Declare products as services', 'leftsell' )
            );

            // WooCommerce Theme support ----------------------------------------------
            echo leftsell_BuildRowOption( 
               'leftsell_woo_themesupport',
               esc_html__( 'Add WooCommerce Theme support', 'leftsell' ),
               esc_html__( 'Pretend your theme supports WooCommerce', 'leftsell' )
            );

            // Store alerts -----------------------------------------------------------
            echo leftsell_BuildRowOption( 
               'leftsell_woo_storealerts',
               esc_html__( 'Disable WooCommerce Store Alerts', 'leftsell' ),
               esc_html__( 'Don\'t show WooCommerce Advertising', 'leftsell' )
            );

            // Additional product fields ----------------------------------------------
            // Gtin -------------------------------------------------------------------
            echo leftsell_BuildRowOption( 
               'leftsell_shop_gtin',
               esc_html__( 'Add gtin field for products', 'leftsell' ),
               esc_html__( 'Enable additional field for Global Trading Number', 'leftsell' )
            );
            // SKU --------------------------------------------------------------------
            echo leftsell_BuildRowOption( 
               'leftsell_shop_sku',
               esc_html__( 'Add sku field for products', 'leftsell' ),
               esc_html__( 'Enable additional field for Stock keeping unit', 'leftsell' )
            );
            // MPN --------------------------------------------------------------------
            echo leftsell_BuildRowOption( 
               'leftsell_shop_mpn',
               esc_html__( 'Add mpn field for products', 'leftsell' ),
               esc_html__( 'Enable additional field for Manufacturer Part Number', 'leftsell' )
            );
            // Brand -------------------------------------------------------------------
            echo leftsell_BuildRowOption( 
               'leftsell_shop_brand',
               esc_html__( 'Add brand field for products', 'leftsell' ),
               esc_html__( 'Enable additional field for Brand', 'leftsell' )
            );
            echo leftsell_BuildRowOption( 
               'leftsell_shop_adult',
               esc_html__( 'Add adult field for products', 'leftsell' ),
               esc_html__( 'Enable additional field for Adult Content Declaration', 'leftsell' )
            );
            // Condition --------------------------------------------------------------
            echo leftsell_BuildRowOption( 
               'leftsell_shop_itemcondition',
               esc_html__( 'Add field for product item condition', 'leftsell' ),
               esc_html__( 'Enable additional field for Item Condition', 'leftsell' )
            );

            echo '<tr><td colspan="2" style="padding:40px 0 0 0">';
            // Frontend ---------------------------------------------------------------
            echo '<h3>' . esc_html__( 'Frontend', 'leftsell' ) . '</h3>';
            echo '</td></tr>';

            // Description h2 ---------------------------------------------------------
            echo leftsell_BuildRowOption( 
               'leftsell_shop_description_tab_heading',
               esc_html__( 'Remove Heading Description in Tab Description', 'leftsell' ),
               esc_html__( 'In the description tab of products, remove the title Description', 'leftsell' )
            );
         
         echo '</table>';
         } else {
            echo esc_html__( 'WooCommerce not found or activated', 'leftsell' );
            echo '<br /><br />';
         }
      }
      echo '</div>';

      
      // Header -----------------------------------------------------------------------
      echo '<div id="tabHeader">';
      if ($curTab == "tabHeader") {

         // ROBOTS instructions -------------------------------------------------------
         $objUI = new leftsellUI();
         echo $objUI->GetOptionTextBox( 'leftsell_robotstext', 
                                        esc_html__( 'Create Meta instructions for robots', 'leftsell'), 
                                        esc_html__( '(like: NOODP, index, follow)',  'leftsell' ) );
         echo '<br /><br /><br /><br />';

         // Additional Header ---------------------------------------------------------
         echo '<form method="post" action="options.php">';
         settings_fields( 'leftsell_option-group' );
         do_settings_sections( 'leftsell_option-group' );
         $leftsell_addheader = get_option( "leftsell_additionalheader" );
         echo '<p><strong>' . esc_html__( 'Add additional content inside header', 'leftsell' ) . '</strong><br />';
         echo esc_html__( 'e.g. Google Analytics / Facebook Pixel etc.',  'leftsell' ) . '</p>';
         echo '<textarea name="leftsell_additionalheader" style="width:100%; height: 40vh">' . 
               esc_textarea( $leftsell_addheader ) . '</textarea>';
         submit_button( esc_html__( 'Save',  'leftsell' ) );
         echo '</form>';
         echo '<br /><br />';
      }
      echo '</div>'; // tabHeader
      echo '</div>'; // leftsell_content
      echo '</div>'; // wrap

      $objUI = null;
   }
?>