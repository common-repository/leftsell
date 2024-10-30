<?php
/**
 * Purpose:           Execute all activated LeftSell options
 * Function URI:      https://leftsell.com/
 * Version:           2.0.3
 * Author:            Kundschaft Schweiz
 * Author URI:        https://kundschaft.ch/ 
 **/
   defined( 'ABSPATH' ) || exit;

   // TWEAKS --------------------------------------------------------------------------
   
   // WP thanks
   if ( get_option( "leftsell_adminthanks" ) == 1 ) { 
      add_filter( 'admin_footer_text', '__return_false' ); 
      add_filter( 'update_footer', '__return_false', 11 );
   } 

   // Admin Messages
   function leftsell_hide_adminnotifications()      { remove_all_actions( 'admin_notices' ); }
   if ( get_option("leftsell_adminmessages") == 1 ) { add_action( 'admin_head', 'leftsell_hide_adminnotifications', 1 ); }
   
   // Page sanitizer shortlinks
   if ( get_option("leftsell_removeshortlinks") == 1 ) { remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0); } 
   
   // security
   if ( get_option("leftsell_disablexmlrpc") == 1 ) { add_filter( 'xmlrpc_enabled', '__return_false' ); } 
   

   // footer
   function leftsell_hide_footer( $content ) { 
      return $content . '<style type="text/css">footer {display:none; } footer#leftsell_footer {display:block}</style>';
   }
   function leftsell_footer_page() {
      $post = get_post();
      if ( empty( $post ) ) { return; }
      $page = get_page_by_title( '~footer' );
      if ( empty( $page ) ) { 
         // footer page does not exist
         $my_post = array(
         'post_title'    => "~footer",
         'post_type'     => "page",
         'post_content'  => "",
         'post_status'   => 'publish',
         'post_author'   => 1,
         );
         wp_insert_post( $my_post );
         $page = get_page_by_title( '~footer' );
      }
      if ( empty( $page ) ) { return; }
      $content = apply_filters( 'the_content', $page->post_content ); 
      echo '<footer id="leftsell_footer">' . $content . '</footer>';
   }
   if ( get_option( "leftsell_footer" ) == 1) {
      add_action( 'the_content', 'leftsell_hide_footer', 1 );
      add_action( 'wp_footer', 'leftsell_footer_page', 99 );
   }
   
   // comment function
   function leftsell_comment_fields( $fields ) {
      if ( !is_array( $fields ) ) { return; }
      if ( get_option( "leftsell_comments_url" ) == 1) {
         if ( array_key_exists ( 'url', $fields ) ) {
            unset( $fields['url'] );
         }
      }
      if ( get_option( "leftsell_comments_author" ) == 1) {
         if ( array_key_exists ( 'author', $fields ) ) {
            unset( $fields['author'] );
         }
      }
      if ( get_option( "leftsell_comments_cookies" ) == 1) {
         if ( array_key_exists ( 'cookies', $fields ) ) {
            unset( $fields['cookies'] );
         }
      }
      if ( get_option( "leftsell_comments_email" ) == 1) {
         if ( array_key_exists ( 'email', $fields ) ) {
            unset( $fields['email'] );
         }
      }
      return $fields;
    }
   add_filter( 'comment_form_default_fields', 'leftsell_comment_fields' );
   function leftsell_comment_hide_email( $content ) { 
      return $content . '<style type="text/css">#email-notes {display:none; }</style>';
   }
   if ( get_option( "leftsell_comments_email" ) == 1) {
      add_action( 'the_content', 'leftsell_comment_hide_email', 1  );
   }


   // SEO -----------------------------------------------------------------------------
   $blnLeftSellTitleTag = false;
   $blnLeftSellKeywords = false;
   $blnLeftSellDescription = false;
   $blnLeftSellHeaders = false;
   if ( get_option( "leftsell_titletag" ) == 1 )    { $blnLeftSellTitleTag = true; }
   if ( get_option( "leftsell_keywords" ) == 1 )    { $blnLeftSellKeywords = true; }
   if ( get_option( "leftsell_description" ) == 1 ) { $blnLeftSellDescription = true; }
   if ( get_option( "leftsell_pageheaders" ) == 1 ) { $blnLeftSellHeaders = true; }
   if ( $blnLeftSellTitleTag or $blnLeftSellKeywords or $blnLeftSellDescription or $blnLeftSellHeaders) {
      add_action( 'add_meta_boxes', 'leftsell_add_custom_box' );
      add_action( 'save_post', 'leftsell_save_postdata', 10, 2 );
      if ( $blnLeftSellTitleTag ) {
         add_action( 'wp_head', 'leftsell_titletagheader');
         add_filter( 'pre_get_document_title', 'leftsell_replaceTitle' );
      }
      if ( $blnLeftSellKeywords ) {
         add_action( 'wp_head', 'leftsell_TagKeywords');
      }
      if ( $blnLeftSellDescription ) {
         add_action( 'wp_head', 'leftsell_TagDescription');
      }
      if ( $blnLeftSellHeaders ) {
         add_action( 'wp_head', 'leftsell_AddPageHeader');
      }
   }

   // SEO Backend 
   function leftsell_add_custom_box() {
      $screens = ['post', 'page', 'product'];
      foreach ($screens as $screen) {
         add_meta_box(
               'leftsell_box_id',          
               'LeftSell SEO',
               'leftsell_custom_box_html', 
               $screen
         );
      }
   }

   function leftsell_custom_box_html( $post ) {
      if ( empty( $post ) ) { return ""; }
      $title = "";
      $keywords = "";
      $description = "";
      if ( get_option( "leftsell_titletag" ) == 1 ) {
         $title = get_post_meta( $post->ID, "leftsell_post_titletag", true );
         $title = sanitize_text_field( $title );
         echo '<p><strong>' . esc_html__( 'Title Tag' , 'leftsell' ) . '</strong><br />';
         echo '<i>' . esc_html__( 'Should not be longer than 55 chars. Creates <meta titletag> in header.', 'leftsell' ) . '</i>';
         echo '</p>';
         echo '<table class="leftsell_ui"><tbody><tr>';
         echo '<td><input type="text" autocomplete="off" name="leftsell_post_titletag" id="leftsell_post_titletag" 
                    style="width:100%" value="' . esc_html( $title ) . '" 
                    onclick="leftsellCheckTextLength(this.value, 55, \'leftsell_maxtitle\')" 
                    onkeydown="leftsellCheckTextLength(this.value, 55, \'leftsell_maxtitle\')" 
                    onkeypress="leftsellCheckTextLength(this.value, 55, \'leftsell_maxtitle\')" 
                    onkeyup="leftsellCheckTextLength(this.value, 55, \'leftsell_maxtitle\')" /></td>';
         echo '<td id="leftsell_maxtitle"></td>';
         echo '</tr></tbody></table>';
      }
      if ( get_option( "leftsell_description" ) == 1 ) {
         $description = get_post_meta( $post->ID, "leftsell_post_description", true );
         $description = sanitize_text_field( $description );
         echo '<p><strong>' . esc_html__( 'Page description' , 'leftsell' ) . '</strong><br />';
         echo '<i>' . esc_html__( 'Should not be longer than 156 chars. Creates <description> Tag in header.', 'leftsell' ) . '</i>';
         echo '</p>';
         echo '<table class="leftsell_ui"><tbody><tr>';
         echo '<td><textarea rows="2" autocomplete="off" name="leftsell_post_description" id="leftsell_post_description" 
                    style="width:100%"
                  onclick="leftsellCheckTextLength(this.value, 156, \'leftsell_maxdescription\')" 
                  onkeydown="leftsellCheckTextLength(this.value, 156, \'leftsell_maxdescription\')" 
                  onkeypress="leftsellCheckTextLength(this.value, 156, \'leftsell_maxdescription\')" 
                  onkeyup="leftsellCheckTextLength(this.value, 156, \'leftsell_maxdescription\')">'. 
                  esc_html( $description ).'</textarea></td>';
         echo '<td id="leftsell_maxdescription"></td>';
         echo '</tr></tbody></table>';
      }
      if ( get_option( "leftsell_keywords" ) == 1 ) {
         $keywords = get_post_meta( $post->ID, "leftsell_post_keywords", true );
         $keywords = sanitize_text_field ( $keywords );
         echo '<p onclick="leftsell_ShowKeywords()" style="cursor:pointer">
                  <strong>' . esc_html__( 'Keywords' , 'leftsell' ) . 
                  ' <span id="leftsell_ShowKeywordsIndicator">&crarr; (' . esc_html__( 'Click for examples' , 'leftsell' ) . ')
                  </span></strong><br />';
         echo '<i>' . esc_html__( 'Write lowercase, just chars, no spaces. Separate by comma. Max. 8 words. Every keyword must appear in content or title. Creates <meta keyword> Tag in header. ', 'leftsell' ) . '</i>';
         echo '</p>';
         echo '<table class="leftsell_ui" style="margin-bottom: 5px"><tbody><tr>';
         echo '<td><input type="text" autocomplete="off" name="leftsell_post_keywords" id="leftsell_post_keywords" 
                  style="width:100%" value="' . esc_html ( $keywords ) . '" 
                  onclick="leftsellCheckKeywords(this.value);leftsell_ShowKeywords();" 
                  onkeydown="leftsellCheckKeywords(this.value);leftsell_ShowKeywords();" 
                  onkeypress="leftsellCheckKeywords(this.value);leftsell_ShowKeywords();" 
                  onkeyup="leftsellCheckKeywords(this.value);leftsell_ShowKeywords();" /></td>';
         echo '<td id="leftsell_maxkeywords"></td>';
         echo '</tr></tbody></table>';
         // generator
         $title = get_post_meta( $post->ID, "leftsell_post_titletag", true );
         $title = sanitize_text_field( $title );
         setup_postdata( $post );
         $searchtext = $title . " " . get_the_excerpt( $post ) . " " . get_the_content( $post ) ;
         $searchtext = strip_tags( $searchtext, "" );
         $searchtext = str_replace( '"', ' ', $searchtext );
         $searchtext = str_replace( "'", ' ', $searchtext );
         $searchtext = str_replace( "\n", ' ', $searchtext );
         $searchtext = str_replace( ".", ' ', $searchtext );
         $searchtext = str_replace( "?", ' ', $searchtext );
         $searchtext = str_replace( "!", ' ', $searchtext );
         $searchtext = str_replace( "(", ' ', $searchtext );
         $searchtext = str_replace( ")", ' ', $searchtext );
         $searchtext = strtolower( $searchtext );
         $aryWords = explode( " ", $searchtext );
         $result = "";
         foreach ( $aryWords as $word ) {
            if ( strlen( $word ) > 4 ) {
               if (strpos( $result, $word ) === false) {
                  $result .= trim( $word ) . ", ";  
               }
            }
         }
         $result = str_replace ( ",,", ",", $result );
         echo '<table id="leftsell_genkeywords" style="width:100%; display:none"><tbody><tr>';
         echo '<td id="leftsell_generatedkeywords" style="text-align:justify">' . esc_html( $result ). '</td>';
         echo '</tr></tbody></table>';
      }
      // additional page header
      if ( get_option( "leftsell_pageheaders" ) == 1 ) {
         //$additionals = json_decode( get_post_meta( $post->ID, "leftsell_post_headers", true ) );
         $additionals = ( get_post_meta( $post->ID, "leftsell_post_headers", true ) );
         echo '<p><strong>' . esc_html__( 'Additional page header' , 'leftsell' ) . '</strong><br />';
         echo '<i>' . esc_html__( 'Individually add code to <head> for this page', 'leftsell' ) . '</i>';
         echo '</p>';
         echo '<table class="leftsell_ui"><tbody><tr>';
         echo '<td><textarea rows="2" autocomplete="off" name="leftsell_post_headers" id="leftsell_post_headers" 
                    style="width:100%; height: 150px">'. 
                    $additionals .'</textarea></td>';
         echo '<td id="leftsell_maxdummy"></td>';
         echo '</tr></tbody></table>';
      }
      echo '<br />';
      echo '<script type="text/javascript">
            function leftsellCheckTextLength(strText, maxlength, id) { 
               if (strText.length > maxlength) { 
                  document.getElementById(id).innerHTML = \'<b style="color:red">\' + ( maxlength - strText.length ) + \'</b>\';
               } else { 
                  document.getElementById(id).innerHTML = ( maxlength - strText.length ); 
               }
            }
            function leftsell_ShowKeywords() {
               document.getElementById(\'leftsell_genkeywords\').style.display = "block";
               document.getElementById(\'leftsell_ShowKeywordsIndicator\').style.display = "none";
            }
            function leftsellCheckKeywords(strText) {
               if ( strText.indexOf(",") == -1) {
                  if ( strText.trim() == "" ) {
                     document.getElementById(\'leftsell_maxkeywords\').innerHTML = 8;
                  } else {
                     document.getElementById(\'leftsell_maxkeywords\').innerHTML = 7;
                  }
                  exit;
               }
               strText = strText.replace(/,/g, " ");
               strText = strText.replace(/  /g, " ");
               strText = strText.replace(/ /g, ",");
               var items = strText.split(",");
               if (items.length > 8) { 
                  document.getElementById(\'leftsell_maxkeywords\').innerHTML = \'<b style="color:red">\' + ( 8 - items.length ) + \'</b>\';
               } else { 
                  document.getElementById(\'leftsell_maxkeywords\').innerHTML = ( 8 - items.length );
               }
            }
            document.getElementById(\'leftsell_post_titletag\').value = "' . esc_js ( $title ) . '";
            document.getElementById(\'leftsell_post_description\').value = "' . esc_js ( $description ) . '";
            leftsellCheckTextLength(\'' . esc_js ( $title ) . '\', 55, \'leftsell_maxtitle\');
            leftsellCheckTextLength(\'' . esc_js ( $description ) . '\', 156, \'leftsell_maxdescription\');
            leftsellCheckKeywords(\'' . esc_js ( $keywords ) . '\');
         </script>';
   }

   function leftsell_save_postdata($post_id) { 
      if ( isset ( $_POST[ 'leftsell_post_titletag' ] ) ) {
         $headertitle = sanitize_text_field( $_POST[ 'leftsell_post_titletag' ] );
         if (trim ( $headertitle ) != "") { update_post_meta( $post_id, 'leftsell_post_titletag', $headertitle ); }
      }
      if ( isset ( $_POST[ 'leftsell_post_description' ] ) ) {
         $description = sanitize_text_field( $_POST[ 'leftsell_post_description' ] );
         if (trim ( $description ) != "") { update_post_meta( $post_id, 'leftsell_post_description', $description ); }
      }
      if ( isset ( $_POST[ 'leftsell_post_keywords' ] ) ) {
         $keywords = sanitize_text_field( $_POST[ 'leftsell_post_keywords' ] );
         update_post_meta( $post_id, 'leftsell_post_keywords', $keywords );
      }
      if ( isset ( $_POST[ 'leftsell_post_headers' ] ) ) {
         //$headeradditions = json_encode( $_POST[ 'leftsell_post_headers' ] );
         // V 2.0.3
         $headeradditions = $_POST[ 'leftsell_post_headers' ];
         update_post_meta( $post_id, 'leftsell_post_headers', $headeradditions );
      }
   }


   // SEO Front End  ------------------------------------------------------------------
   function leftsell_titletagheader() {
      global $post;
      if ( empty( $post ) ) { return; }
      $headertitle = sanitize_text_field( get_post_meta( get_the_ID(), "leftsell_post_titletag", true ) );
      if ( $headertitle != "" ) { echo '<meta name="titletag" content="'. esc_html( $headertitle ) .'" />'; } 
   }

   function leftsell_replaceTitle( $title ) {
      global $post;
      if ( empty( $post ) ) { return; }
      $newtitle = sanitize_text_field( get_post_meta( get_the_ID(), "leftsell_post_titletag", true ) );
      if ( trim( $newtitle ) == "" ) { $newtitle = sanitize_text_field( $title ); }
      return $newtitle;
   }

   function leftsell_TagKeywords() {
      global $post;
      if ( empty( $post ) ) { return; }
      $keywords = sanitize_text_field( get_post_meta( get_the_ID(), "leftsell_post_keywords", true ) );
      if ( $keywords != "" ) { echo '<meta name="keywords" content="' . esc_html( $keywords ) . '" />'; }
   }

   function leftsell_TagDescription() {
      global $post;
      if ( empty( $post ) ) { return; }
      $description = sanitize_text_field( get_post_meta( get_the_ID(), "leftsell_post_description", true ) );
      if ( $description != "" ) { echo '<meta name="description" content="' . esc_html( $description ) . '" />'; }
   }

   function leftsell_AddPageHeader() {
      global $post;
      if ( empty( $post ) ) { return; }
      $headeradditions = get_post_meta( get_the_ID(), "leftsell_post_headers", true );
      if ( trim( $headeradditions == "" ) ) { return; }
      //if ( $headeradditions != "" ) { echo json_decode( $headeradditions ); }
      if ( $headeradditions != "" ) { echo $headeradditions; }
   }

   // Open Graph ----------------------------------------------------------------------
   // og:url
   function leftsell_TagOGURL() {
      global $post;
      if ( empty( $post ) ) { return; }
      $permalink = get_permalink( $post );
      if ($permalink != "") { echo '<meta property="og:url" content="'. esc_html( $permalink ) .'" />'; }
   }
   if ( get_option( "leftsell_ogurl" ) == 1 ) {
      add_action( 'wp_head', 'leftsell_TagOGURL' );
   }

   // og:title
   function leftsell_TagOGTitle() {
      global $post;
      if ( empty( $post ) ) { return; }
      $ogtitle = get_post_meta( $post->ID, "leftsell_post_titletag", true );
      if (trim($ogtitle) == "") { $ogtitle = get_the_title(); }
      $ogtitle = sanitize_text_field( $ogtitle );
      if (trim($ogtitle) != "") { echo '<meta property="og:title" content="' . esc_html( $ogtitle ) . '" />'; }
   }
   if ( get_option( "leftsell_ogtitle" ) == 1 ) {
      add_action( 'wp_head', 'leftsell_TagOGTitle' );
   }

   // og:type
   function leftsell_TagOGType() {
      global $post;
      if ( empty( $post ) ) { return; }
      $ogtype = sanitize_text_field( get_post_type( $post->ID ) );
      if ( $ogtype != false and trim( $ogtype != "" ) ) {
         switch ( strtolower($ogtype) ) {
            case "post":    $ogtype = "article"; break;
            case "page":    $ogtype = "website"; break;
            case "product": $ogtype = "website"; break;
            default:        $ogtype = "website"; break;
         }
      }
      echo '<meta property="og:type" content="' . esc_html( $ogtype ) . '" />';
   }
   if ( get_option( "leftsell_ogtype" ) == 1 ) {
      add_action( 'wp_head', 'leftsell_TagOGType' );
   }
   
   // og:image
   function leftsell_TagOGImage() {
      global $post;
      if ( empty( $post ) ) { return; }
      $ogimage = get_the_post_thumbnail( $post->ID );
      if ( trim( $ogimage ) != "" ) {
         preg_match( '/src=\"(.*)\"/iU', $ogimage, $arySplit );
         if ( is_array( $arySplit) ) {
            if ( count ( $arySplit ) > 0 ) {
               foreach ( $arySplit as $image ) {
                  if ( strpos( $image, "src=" ) === false ) {
                     $ogimage = sanitize_text_field( $image );
                  }
               }
            }
         }
         echo '<meta property="og:image" content="' . esc_html( $ogimage ) . '" />';
      }
   }
   if ( get_option( "leftsell_ogimage" ) == 1 ) {
      add_action( 'wp_head', 'leftsell_TagOGImage' );
   }


   // Google JSON ---------------------------------------------------------------------
   // Breadcrumbs 
   function leftsell_AddBreadCrumbs() {
      global $post;
      if ( empty( $post ) ) { return; }
      $buffer = '<script type="application/ld+json">
                  {"@context": "http://schema.org", "@type": "BreadcrumbList","itemListElement": [{{ITEMS}}] };
                  </script>';
      $itemtemplate = '{"@type": "ListItem", "position": {{POS}}, "item": { "@id": "{{URL}}", "name": "{{NAME}}", "image": "{{IMAGE}}"} },';


      if ( $buffer == "" ) { return; }
      if ( $itemtemplate == "" ) { return; }
      $url = $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
      $root = substr( home_url(), strpos( home_url(), "//" ) + 2, strlen( home_url() ) );
      $path = substr( $url, strlen( $root ), strlen( $url ) );
      if ( substr( $path, strlen( $path ) -1, 1 ) == "/" ) { $path = substr ( $path, 0, strlen( $path ) -1 ); }
      
      $items = "";
      $pos = 1;

      // first item
      $temp = $itemtemplate;
      $incrementalfolder = home_url() . "/";
      $exceptionimage = get_the_post_thumbnail_url( url_to_postid( $incrementalfolder ) );
      $temp = str_replace( "{{POS}}", $pos, $temp );
      $temp = str_replace( "{{URL}}", $incrementalfolder, $temp );
      $temp = str_replace( "{{NAME}}", get_the_title( url_to_postid( $incrementalfolder ) ), $temp );
      $temp = str_replace( "{{IMAGE}}", $exceptionimage, $temp );
      $items = $temp;

      $folders = explode( "/", $path );
      if ( is_array( $folders ) ) {
         if ( count( $folders ) > 0 ) {
            foreach( $folders as $folder) {
               if ( $folder != "" ) {
                  $pos++;
                  $postid = "";
                  $incrementalfolder .= $folder . "/";
                  $temp = $itemtemplate;
                  $temp = str_replace( "{{URL}}", $incrementalfolder, $temp );
                  $temp = str_replace( "{{POS}}", $pos, $temp );
                  $postid = url_to_postid( $incrementalfolder );
                  if ( $postid == 0 ) { 
                     $title = strtoupper( substr($folder, 0,1) ) . substr( $folder , 1, strlen($folder) );
                     $image = $exceptionimage;
                  } else {
                     $title = get_the_title( $postid );
                     $image = get_the_post_thumbnail_url( $postid );
                  }
                  if ($image == "") { $image = $exceptionimage; }
                  $temp = str_replace( "{{NAME}}", $title, $temp );
                  $temp = str_replace( "{{IMAGE}}", $image, $temp );
                  $items .= $temp;
               }
            }
         }
         $items = substr( $items, 0, strlen( $items ) -1 );
      }
      $buffer = str_replace( "{{ITEMS}}", $items, $buffer );
      echo $buffer;
   }
   if ( get_option( "leftsell_googlebreadcrumbs" ) == 1 ) {
      add_action( 'wp_head', 'leftsell_AddBreadCrumbs' );
   }
   

   // Header Additions ----------------------------------------------------------------
   // robots instructions
   function leftsell_RobotsText() {
      $robotstext = sanitize_text_field( get_option( "leftsell_robotstext" ) );
      if ( trim( $robotstext ) != "" ) {
         echo '<meta name="robots" content="' . esc_html( $robotstext ) . '" />'; 
      }
   }
   if ( get_option( "leftsell_robotstext" ) != "" ) {
      add_action( 'wp_head', 'leftsell_RobotsText' );
   }

   // additional header
   function leftsell_AddHeaderText() {
      echo get_option( "leftsell_additionalheader" ); // already sanitized and escaped
   }
   $addheader = get_option( "leftsell_additionalheader" );
   if ( trim( $addheader ) != "" ) {
      add_action( 'wp_head', 'leftsell_AddHeaderText' );
   }
   
   
   // Sitemaps ------------------------------------------------------------------------
   // google xml
   function leftsell_CreateGoogleXML() {
      // exclude woocommerce special pages
      $aryExcludes = array( );
      $page = get_page_by_title( '~footer' );
      if ( !empty( $page ) ) { $aryExcludes[] = $page->ID; } 
      
      if ( class_exists( 'WooCommerce' ) ) {
         $aryExcludes[] = wc_get_page_id( 'cart' );
         $aryExcludes[] = wc_get_page_id( 'checkout' );
         $aryExcludes[] = wc_get_page_id( 'myaccount' );
         $aryExcludes[] = wc_get_page_id( 'payment-methods' );
      }
      $filecontent = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
      $args = array(
         'numberposts'    => 10000,
         'post_type'      => array( 'product', 'page', 'post'),
         'post_status'    => 'publish',
         'posts_per_page' => 10000
         );
      $pages = get_posts( $args );
      if ( is_array( $pages ) ) {
         if ( count( $pages ) > 0 ) {
         foreach ( $pages as $page ) {
            if ( !in_array( $page->ID, $aryExcludes ) ) {
               $filecontent .= '<url>';
               $filecontent .= '<loc>' . get_permalink( $page->ID ) . '</loc>';   
               $filecontent .= '<lastmod>' . get_the_date( "Y-m-d", $page->ID ) . '</lastmod>';
               $filecontent .= '<changefreq>monthly</changefreq>';   
               $filecontent .= '<priority>.5</priority>';   
               $filecontent .= '</url>';
            }
         }
      }
      }
      $filecontent .= '</urlset>';
      try {
         $xmlfile = plugin_dir_path( __FILE__ ) . '../../../../sitemap.xml';
         if ( validate_file( $xmlfile ) == true ) {
            $fh = fopen( $xmlfile, 'w');
            fwrite( $fh, $filecontent );
            fclose( $fh );
         } 
      } catch (Exception $e) {
         return true;
      }  
   }
   if ( get_option( "leftsell_googlesitemap" ) == 1 ) {
      add_action( 'save_post', 'leftsell_CreateGoogleXML', 10, 2 );
   }

   // Google Product Map
   function leftsell_CreateGoogleProductMap() {
      $filecontent = '<?xml version="1.0" encoding="utf-8"?>
      <rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
                        <channel>
                        <title>' . esc_html( get_bloginfo( "name" ) ) . '</title>
                        <link>' . esc_url( get_bloginfo( "url" ) ) . '</link>
                        <description>' . esc_html( get_bloginfo( "description" ) ). '</description>';
      
         $args = array(
            'numberposts'    => 10000,
            'post_type'      => array( 'product' ),
            'post_status'    => 'publish',
            'posts_per_page' => 10000
            );
         $posts = get_posts( $args );
         if ( is_array( $posts ) ) {
            if ( count( $posts ) ) {
               foreach ( $posts as $resultpost ) {
                  if ( !empty( $resultpost ) ) {
                     $objProduct = new leftsellProduct();
                     $objProduct->fromPost( $resultpost );
                     $price = $objProduct->fields[ "price" ];
                     if ( $price == "" ) { $price = 0; }
                     if ( $price != 0 and $objProduct->fields["isservice"] == 0 ) {
                        $filecontent .= '<item>
                           <g:id>' . substr( $objProduct->fields[ "wpid" ], 0, 50 ) .'</g:id>
                           <title>' . esc_html( substr( $objProduct->fields[ "title" ], 0, 150 ) ) .'</title>
                           <g:description>' . esc_html( substr( $objProduct->ConvertTextToSimpleDescription( $objProduct->fields[ "text" ] ), 0, 1500 ) )  .'</g:description>
                           <link>' . esc_url( $objProduct->fields[ "link" ] ) .'</link>
                           <g:image_link>' . esc_url( $objProduct->fields[ "imagelink" ] ) .'</g:image_link>
                           <g:price>' . number_format ( $price , 2 ) . ' ' . $objProduct->fields[ "product_currency" ] .'</g:price>
                           <g:condition>' . $objProduct->fields[ "condition" ] .'</g:condition>
                           <g:availability>' . $objProduct->MapGoogleValues( $objProduct->fields[ "availability" ] ).'</g:availability>
                           <g:adult>' . $objProduct->fields[ "adult" ] .'</g:adult>
                           <g:brand>' . esc_html( substr( $objProduct->fields[ "brand" ], 0, 70 ) ) .'</g:brand>';
                        if ( $objProduct->fields[ "gtin" ] != "" ) {
                           $filecontent .= '
                           <g:gtin>' . $objProduct->fields[ "gtin" ] . '</g:gtin>';
                        }
                        if ( $objProduct->fields[ "mpn" ] != "" ) {
                           $filecontent .= '
                           <g:mpn>' . substr( $objProduct->fields[ "mpn" ], 0, 70 ) . '</g:mpn>';
                        }
                        $filecontent .= '
                           <g:identifier_exists>' . $objProduct->fields[ "identifierexists" ] .'</g:identifier_exists>
                        </item>';
                     }
                     $objProduct = null;
                  }
               }
            }
         }
         $filecontent .= '
                        </channel>
                     </rss>';
         try {
            $xmlfile = plugin_dir_path( __FILE__ ) . '../../../../products.xml';
            if ( validate_file( $xmlfile ) == true ) {
               $fh = fopen( $xmlfile, 'w' );
               fwrite( $fh, $filecontent );
               fclose( $fh );
            }
         } catch ( Exception $e ) {
            return var_export($e, true);
         }
      return "OK";
   }
   if ( get_option( "leftsell_googleproductxml" ) == 1 ) {
      add_action( 'save_post', 'leftsell_CreateGoogleProductMap', 10, 2 );
   }


   // Shop ----------------------------------------------------------------------------
   // service type
   function leftsell_service_add_to_cart_text() {
      $post = get_post();
      if ( empty( $post ) ) { return esc_html__( 'Add to cart', 'woocommerce' ); }
      $blnChangeText = leftsellIsYes( sanitize_text_field( get_post_meta( $post->ID, "leftsell_post_service", true ) ) );
      if ( $blnChangeText == false ) {
         $blnChangeText = leftsellIsYes( get_post_meta( $post->ID, "_virtual", true ) );

      }
      if ( $blnChangeText == true ) {
         return esc_html__( 'Book', 'leftsell' );
      } else {
         return esc_html__( 'Add to cart', 'woocommerce' );
      }
   }
   if ( get_option( "leftsell_shop_service" ) == 1 ) {
      add_filter( 'woocommerce_product_add_to_cart_text', 'leftsell_service_add_to_cart_text' );  
      add_filter( 'woocommerce_product_single_add_to_cart_text', 'leftsell_service_add_to_cart_text' ); 
      add_filter( 'woocommerce_booking_single_add_to_cart_text', 'leftsell_service_add_to_cart_text' ); 
   }

   // theme support
   function leftsell_add_woocommerce_support() {
      add_theme_support( 'woocommerce' );
   }
   if ( class_exists( 'WooCommerce' ) ) {
      if ( get_option( "leftsell_woo_themesupport" ) == 1) { 
         add_action( 'after_setup_theme', 'leftsell_add_woocommerce_support' ); 
      }
   }

   // store alerts
   function leftsell_custom_admin() {
      echo '<style>.woocommerce-store-alerts { display:none }</style>';
   }
   if ( get_option( "leftsell_woo_storealerts" ) == 1 ) { 
      add_action( 'admin_head', 'leftsell_custom_admin' ); 
   }
   
   // additional shop fields
   $blnLeftSellGtin      = false;
   $blnLeftSellSKU       = false;
   $blnLeftSellMPN       = false;
   $blnLeftSellBrand     = false;
   $blnLeftSellAdult     = false;
   $blnLeftSellCondition = false;
   if ( get_option( "leftsell_shop_gtin" )          == 1 ) { $blnLeftSellGtin = true; }
   if ( get_option( "leftsell_shop_sku" )           == 1 ) { $blnLeftSellSKU = true; }
   if ( get_option( "leftsell_shop_mpn" )           == 1 ) { $blnLeftSellMPN = true; }
   if ( get_option( "leftsell_shop_brand" )         == 1 ) { $blnLeftSellBrand = true; }
   if ( get_option( "leftsell_shop_adult" )         == 1 ) { $blnLeftSellBrand = true; }
   if ( get_option( "leftsell_shop_itemcondition" ) == 1 ) { $blnLeftSellCondition = true; }
   if ( $blnLeftSellGtin or $blnLeftSellSKU or $blnLeftSellMPN or $blnLeftSellBrand or $blnLeftSellBrand or $blnLeftSellCondition ) {
      add_action( 'add_meta_boxes', 'leftsell_add_custom_shopbox' );
      add_action( 'save_post', 'leftsell_save_shoppostdata', 10, 2 );
   }

   function leftsell_add_custom_shopbox() {
      // add fields below editor
      $screens = [ 'product' ];
      foreach ( $screens as $screen ) {
         add_meta_box(
               'leftsell_shopbox_id',          
               esc_html__( 'LeftSell Product Fields', 'leftsell' ) ,
               'leftsell_custom_shopbox_html', 
               $screen
         );
      }
   }

   function leftsell_custom_shopbox_html( $post ) {
      if ( empty( $post ) ) { return; }
      if ( get_option( "leftsell_shop_service" ) == 1 ) {
         echo '<p style="margin-bottom:5px"><strong>' . esc_html__( 'Service' , 'leftsell' ) . '</strong> - ';
         echo '<i>' . esc_html__( 'Declare product as a service', 'leftsell' ) . '</i></p>';
         $value = sanitize_text_field( get_post_meta( $post->ID, "leftsell_post_service", true ) );
         $image = 'disabled.png';
         switch ( $value ) {
            case 0:       $image = 'disabled.png'; $value = 0; break;
            case "":      $image = 'disabled.png'; $value = 0; break;
            case "no":    $image = 'disabled.png'; $value = 0; break;
            case "false": $image = 'disabled.png'; $value = 0; break;
            case false:   $image = 'disabled.png'; $value = 0; break;
            case 1:       $image = 'enabled.png';  $value = 1; break;
            case "yes":   $image = 'enabled.png';  $value = 1; break;
            case "true":  $image = 'enabled.png';  $value = 1; break;
            case true:    $image = 'enabled.png';  $value = 1; break;
         }
         echo '<img id="leftsellServiceSwitch" style="margin:0" class="leftsell_check" src="' . esc_html( leftsellINCLUDE ) . 'images/' . esc_html( $image ) . '"
               onclick="leftsellSwitchService()" />';
         echo '<input type="hidden" autocomplete="off" name="leftsell_post_service" id="leftsell_post_service" style="width:90%" 
               value="'. esc_html( $value )  .'" /></td>';
         echo '<br /><br />';
         echo '<script type="text/javascript">
               function leftsellSwitchService() {
                  if (document.getElementById("leftsell_post_service").value == 0) {
                     document.getElementById("leftsell_post_service").value = 1;
                     document.getElementById("leftsellServiceSwitch").src = "' . esc_js( leftsellINCLUDE ) . 'images/enabled.png";
                  } else {
                     document.getElementById("leftsell_post_service").value = 0;
                     document.getElementById("leftsellServiceSwitch").src = "' . esc_js( leftsellINCLUDE ) . 'images/disabled.png";
                  }
               }
               </script>';
      }
      if ( get_option( "leftsell_shop_brand" ) == 1 ) {
         echo '<p style="margin-bottom:5px"><strong>' . esc_html__( 'Brand' , 'leftsell' ) . '</strong> - ';
         echo '<i>' . esc_html__( 'Add the brand name', 'leftsell' ) . '</i></p>';
         echo '<input type="text" autocomplete="off" name="leftsell_post_brand" id="leftsell_post_brand" style="width:90%" 
         value="'. esc_html( sanitize_text_field( get_post_meta( $post->ID, "_brand", true ) ) ) .'" /></td>';
         echo '<br /><br />';
      }
      if ( get_option( "leftsell_shop_gtin" ) == 1 ) {
         echo '<p style="margin-bottom:5px"><strong>' . esc_html__( 'GTIN' , 'leftsell' ) . '</strong> - ';
         echo '<i>' . esc_html__( 'Add the Global Trading Number', 'leftsell' ) . '</i></p>';
         echo '<input type="text" autocomplete="off" name="leftsell_post_gtin" id="leftsell_post_gtin" style="width:90%" 
         value="'. esc_html( sanitize_text_field( get_post_meta( $post->ID, "_gtin", true ) ) ) .'" /></td>';
         echo '<br /><br />';
      }
      if ( get_option( "leftsell_shop_sku" ) == 1 ) {
         echo '<p style="margin-bottom:5px"><strong>' . esc_html__( 'SKU' , 'leftsell' ) . '</strong> - ';
         echo '<i>' . esc_html__( 'Add the Stock Keeping Unit', 'leftsell' ) . '</i></p>';
         echo '<input type="text" autocomplete="off" name="leftsell_post_sku" id="leftsell_post_sku" style="width:90%" 
         value="'. esc_html( sanitize_text_field( get_post_meta( $post->ID, "_sku", true ) ) ) .'" /></td>';
         echo '<br /><br />';
      }
      if ( get_option( "leftsell_shop_mpn" ) == 1 ) {
         echo '<p style="margin-bottom:5px"><strong>' . esc_html__( 'MPN' , 'leftsell' ) . '</strong> - ';
         echo '<i>' . esc_html__( 'Add the Manufactor Part Number', 'leftsell' ) . '</i></p>';
         echo '<input type="text" autocomplete="off" name="leftsell_post_mpn" id="leftsell_post_mpn" style="width:90%" 
         value="'. esc_html( sanitize_text_field( get_post_meta( $post->ID, "_mpn", true ) ) ) .'" /></td>';
         echo '<br /><br />';
      }
      if ( get_option( "leftsell_shop_adult" ) == 1 ) {
         echo '<p style="margin-bottom:5px"><strong>' . esc_html__( 'Adult Content' , 'leftsell' ) . '</strong> - ';
         echo '<i>' . esc_html__( 'Declare content as only for adults', 'leftsell' ) . '</i></p>';
         $value = sanitize_text_field( get_post_meta( $post->ID, "_adult", true ) );
         $image = 'disabled.png';
         switch ( $value ) {
            case "":      $image = 'disabled.png'; $value = 'no'; break;
            case "no":    $image = 'disabled.png'; $value = 'no'; break;
            case "false": $image = 'disabled.png'; $value = 'no'; break;
            case false:   $image = 'disabled.png'; $value = 'no'; break;
            case "yes":   $image = 'enabled.png';  $value = 'yes'; break;
            case "true":  $image = 'enabled.png';  $value = 'yes'; break;
            case true:    $image = 'enabled.png';  $value = 'yes'; break;
         }
         echo '<img id="leftsellAdultSwitch" style="margin:0" class="leftsell_check" src="' . esc_html( leftsellINCLUDE ) . 'images/' . esc_html( $image ) . '"
               onclick="leftsellSwitchAdult()" />';
         echo '<input type="hidden" autocomplete="off" name="leftsell_post_adult" id="leftsell_post_adult" style="width:90%" 
               value="'. esc_html( $value ) .'" /></td>';
         echo '<br /><br />';
         echo '<script type="text/javascript">
               function leftsellSwitchAdult() {
                  if (document.getElementById("leftsell_post_adult").value == "no") {
                     document.getElementById("leftsell_post_adult").value = "yes";
                     document.getElementById("leftsellAdultSwitch").src = "' . esc_js( leftsellINCLUDE ) . 'images/enabled.png";
                  } else {
                     document.getElementById("leftsell_post_adult").value = "no";
                     document.getElementById("leftsellAdultSwitch").src = "' . esc_js( leftsellINCLUDE ) . 'images/disabled.png";
                  }
               }
               </script>';
      }
      if ( get_option( "leftsell_shop_itemcondition" ) == 1 ) {
         echo '<p style="margin-bottom:5px"><strong>' . esc_html__( 'Item Condition' , 'leftsell' ) . '</strong> - ';
         echo '<i>' . esc_html__( 'Declare the condition of the item', 'leftsell' ) . '</i></p>';
         $value = sanitize_text_field( get_post_meta( $post->ID, "_condition", true ) );
         echo '<select autocomplete="off" id="leftsell_post_condition" name="leftsell_post_condition" class="select short">';
         $selected = "";      
         if ( $value == "new" or $value == "" ) { $selected = 'selected="selected"'; } else { $selected = ""; }
         echo '<option value="new" ' . esc_html( $selected ) . '>' .esc_html__( 'new', 'leftsell' ) . '</option>';
         if ( $value == "refurbished" ) { $selected = 'selected="selected"'; } else { $selected = ""; }
         echo '<option value="refurbished" ' . esc_html( $selected ) . '>' .esc_html__( 'refurbished', 'leftsell' ) . '</option>';
         if ( $value == "used" ) { $selected = 'selected="selected"'; } else { $selected = ""; }
         echo '<option value="used" ' . esc_html( $selected ) . '>' .esc_html__( 'used', 'leftsell' ) . '</option>';
         echo '</select>';
         echo '<br /><br />';
      }
   }
   
   function leftsell_save_shoppostdata( $post_id ) { 
      if ( isset ( $_POST[ 'leftsell_post_service' ] ) ) {
         $servicevalue = sanitize_text_field( $_POST[ 'leftsell_post_service' ] );
         update_post_meta( $post_id, 'leftsell_post_service', $servicevalue ); 
         update_post_meta( $post_id, '_virtual', $servicevalue ); 
      }
      if ( isset ( $_POST[ 'leftsell_post_gtin' ] ) ) {
         update_post_meta( $post_id, '_gtin', sanitize_text_field( $_POST[ 'leftsell_post_gtin' ] ) ); 
      }
      if ( isset ( $_POST[ 'leftsell_post_sku' ] ) ) {
         update_post_meta( $post_id, '_sku', sanitize_text_field( $_POST[ 'leftsell_post_sku' ] ) ); 
      }
      if ( isset ( $_POST[ 'leftsell_post_mpn' ] ) ) {
         update_post_meta( $post_id, '_mpn', sanitize_text_field( $_POST[ 'leftsell_post_mpn' ] ) ); 
      }
      if ( isset ( $_POST[ 'leftsell_post_brand' ] ) ) {
         update_post_meta( $post_id, '_brand', sanitize_text_field( $_POST[ 'leftsell_post_brand' ] ) ); 
      }
      if ( isset ( $_POST[ 'leftsell_post_adult' ] ) ) {
         update_post_meta( $post_id, '_adult', sanitize_text_field( $_POST[ 'leftsell_post_adult' ] ) ); 
      }
      if ( isset ( $_POST[ 'leftsell_post_condition' ] ) ) {
         update_post_meta( $post_id, '_condition', sanitize_text_field( $_POST[ 'leftsell_post_condition' ] ) ); 
      }
   }

   // remove description heading
   function leftsell_change_product_description_tab_heading( $title ) {
      return;
   }
   if (get_option( "leftsell_shop_description_tab_heading" )  == 1 ) {
      add_filter( 'woocommerce_product_description_heading', 'leftsell_change_product_description_tab_heading', 10, 1 );
   }
?>