<?php
/**
 * Purpose:           Show the communication admin page
 * Function URI:      https://leftsell.com/
 * Version:           2.0.2
 * Author:            Kundschaft Schweiz
 * Author URI:        https://kundschaft.ch/ 
 **/
   defined( 'ABSPATH' ) || exit;

   function leftsell_page_com() {
      if ( !current_user_can( 'manage_options' ) ) { return; }
      echo '<div class="wrap">';
      echo '<img src="' . plugin_dir_url( __FILE__ ) . '../includes/images/logo.png" id="leftselllogo" />';
      echo '<h1>' . esc_html__( 'Communicate', 'leftsell' ). '</h1>';
      echo esc_html__( 'Communicate with LeftSell Management and Development', 'leftsell' );
      $objUI = new leftsellUI();
      echo $objUI->BuildNav(
         array( "News"      => esc_html__( 'News', 'leftsell' ), 
                "Bug"       => esc_html__( 'Report Bug', 'leftsell' ),
                "Feature"   => esc_html__( 'Request Feature', 'leftsell' )
         ) , "tabNews"
      );
      $curTab = "tabNews";
      if ( isset( $_GET["tab"] ) ) { $curTab = sanitize_text_field( $_GET["tab"] ); }

      $objShop = new leftsellShop();
      echo '<div class="leftsell_content">';

      // TAB NEWS ---------------------------------------------------------------------
      echo '<div id="tabNews">';
      if ( $curTab == "tabNews" ) { 
         echo '<h2>' . esc_html__( 'Latest News', 'leftsell' ) .'</h2>';
         if ( $objShop->m_intEnabled == 0 ) {
            echo '<button id="leftsellInitShop" onclick="leftsellSendMessage(\'leftsellInitShop\', \'reg\' , \'\', 1, \'reload\')">
                  Init Shop</button>';
         } else {
            echo '<div id="leftsellCommunicate" style="margin: 10px 0 20px 0; overflow:auto; 
                           border:1px solid rgb(0,0,0,.2); background:#fcfcfc; height: auto; padding:10px">';
            echo '<u>' . esc_html__( 'Waiting for server', 'leftsell' ). "</u><br />";
            echo '</div>';
            echo '<script type="text/javascript">
                           window.addEventListener( "load", function() {
                              leftsellSendMessage( \'leftsellCommunicate\', \'news\' , \'\', 0, \'\' );
                           }, false); 
                        </script>';
         }
      }
      echo '</div>'; // tabNews

      // TAB BUGS ---------------------------------------------------------------------
      echo '<div id="tabBug">';
      if ( $curTab == "tabBug" ) { 
         echo '<h2>' . esc_html__( 'Bug reporting', 'leftsell' ) .'</h2>';
         if ( $objShop->m_intEnabled == 0 ) {
            echo '<button onclick="this.style.display=\'none\';leftsellSendMessage(\'leftsellCommunicate\', \'reg\' , \'\', 1, \'\')">
                  Init Shop</button>';
         } else {
            echo  esc_html__( 'Report a bug. We are always aware of bugs and software changements. Please feel free to report bugs, so that we can improve our software for you.', 'leftsell' );
            echo '<br /><br />';
            echo '<div id="bugger">';
            echo  '<h3>' . esc_html__( 'Report a bug', 'leftsell' ) . ':</h3>';
            echo '<textarea autocomplete="off" id="bugtext" style="width:100%; padding:10px;" 
                     onkeypress="return event.keyCode != 13;"></textarea>
                     <button onclick="leftsellReportBug()" style="margin-top:10px">' .
                     esc_html__( 'Report Bug', 'leftsell' ). '</button>
                  </div>';
            echo '<br /><br />'; 
            $objJSON = new leftsellJSON();
            $aryStored = $objJSON->aryFromJSONOption( "leftsell_bugregports" );
            if ( is_array( $aryStored ) ) {
               if ( count($aryStored) > 0 ) {
                  echo  '<h3>' . esc_html__( 'Reported bugs', 'leftsell' ) . ':</h3>';
                  foreach ( $aryStored as $bug ) {
                     if ( is_array ( $bug ) ) {
                        if ( isset( $bug[ "reportdate" ] ) ) {
                           echo esc_html( sanitize_text_field( $bug[ "reportdate" ] ) ) . ": ";
                        }
                        if ( isset( $bug[ "text" ] ) ) {
                           echo nl2br( esc_html( sanitize_text_field( $bug[ "text" ] ) ) );
                        }
                        echo '<br /><br />';
                     }
                  }
               }
            }
            $objJSON = null;
         }
      }
      echo '</div>'; // tabBug

      // TAB FEATURES -----------------------------------------------------------------
      echo '<div id="tabFeature">';
      if ( $curTab == "tabFeature" ) { 
         echo '<h2>' . esc_html__( 'Request feature', 'leftsell' ) .'</h2>';
         if ( $objShop->m_intEnabled == 0 ) {
            echo '<button onclick="this.style.display=\'none\';leftsellSendMessage(\'leftsellCommunicate\', \'reg\' , \'\', 1, \'\')">
                  Init Shop</button>';
         } else {
            echo esc_html__( 'Request a feature: we will implement, what is possible.', 'leftsell' );
            echo '<br /><br />';
            echo '<div id="featurer">';
            echo  '<h3>' . esc_html__( 'Request a feature', 'leftsell' ) . ':</h3>';
            echo '<textarea autocomplete="off" id="featuretext" style="width:100%; padding:10px;" 
                     onkeypress="return event.keyCode != 13;"></textarea>
                     <button onclick="leftsellRequestFeature()" style="margin-top:10px">'.
                     esc_html__( 'Request', 'leftsell' ) . '</button>
                  </div>';
            echo '<br /><br />'; 
            $objJSON = new leftsellJSON();
            $aryStored = $objJSON->aryFromJSONOption( "leftsell_featurerequests" );
            if ( is_array( $aryStored ) ) {
               if ( count($aryStored) > 0 ) {
                  echo  '<h3>' . esc_html__( 'Your requests', 'leftsell' ) . ':</h3>';
                  foreach ( $aryStored as $feature ) {
                     if ( is_array ( $feature ) ) {
                        if ( isset( $feature[ "requestdate" ] ) ) {
                           echo esc_html( sanitize_text_field( $feature[ "requestdate" ] ) ) . ": ";
                        }
                        if ( isset( $feature[ "featuretext" ] ) ) {
                           echo nl2br( esc_html( sanitize_text_field( $feature[ "featuretext" ] ) ) );
                           echo '<br /><br />';
                        }
                     }
                  }
               }
            }
            echo '</div>';
         }
      }
      echo '</div>'; // tabFeature

      echo '</div>'; // leftsell_content
      $objShop = null;
      $objUI = null;
      echo '</div>'; // wrap
   }
?>