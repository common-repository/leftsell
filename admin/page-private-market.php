<?php
/**
 * Purpose:           Show the Private Market admin page
 * Function URI:      https://leftsell.com/
 * Version:           2.0.2
 * Author:            Kundschaft Schweiz
 * Author URI:        https://kundschaft.ch/ 
 **/
   defined( 'ABSPATH' ) || exit;
   global $current_user;
   
   // the page Private Market ---------------------------------------------------------
   function leftsell_page_private_market() {
      if ( !current_user_can( 'manage_options' ) ) { echo esc_html__( 'No access', 'leftsell' ); return; }
      echo '<div class="wrap">';
      $objShop = new leftsellShop();
      if ( $objShop->m_intEnabled == 0 ) {
         echo '<button id="leftsellInitShop" onclick="leftsellSendMessage(\'leftsellInitShop\', \'reg\' , \'\', 1, \'reload\')">' .
               esc_html__( 'Initialize shop', 'leftsell' ) . '</button>';
      } else { 
         echo '<img src="' . plugin_dir_url( __FILE__ ) . '../includes/images/logo.png" id="leftselllogo" />';
         echo '<h1>' . esc_html__( 'Private Market', 'leftsell' ) .'</h1>';
         echo esc_html__( 'Share services and products to your shops', 'leftsell' );
         if ( class_exists( 'WooCommerce' ) == false ) {
            echo '<br /><br /><p style="font-size:x-large">';
            echo esc_html__( 'No shop system found.', 'leftsell' );
            echo '<br />';
            echo esc_html__( 'We recommend WooCommerce.', 'leftsell' );
            echo '</p></div>';
            return;
         }
         $objUI = new leftsellUI();
         $curTab = "tabPublish";
         if ( isset( $_GET["tab"] ) ) { $curTab = sanitize_text_field( $_GET["tab"] ); }
         if ( isset( $_REQUEST["_wp_http_referer"] ) ) { // bulk actions
            if ( strpos( $_REQUEST["_wp_http_referer"], "tabPublish" ) !== false ) {
               $curTab = "tabPublish";
            }
            if ( strpos( $_REQUEST["_wp_http_referer"], "tabInMarket" ) !== false ) {
               $curTab = "tabInMarket";
            }
         }
         echo $objUI->BuildNav(
            array( "Publish"        => esc_html__( 'Publish', 'leftsell' ),
                   "InMarket"       => esc_html__( 'On Market', 'leftsell' ),
                   "PrivateMarket"  => esc_html__( 'Shops', 'leftsell' ),        
                   "PMOptions"      => esc_html__( 'Options', 'leftsell' ),
                   "PMLog"          => esc_html__( 'Log', 'leftsell' )
            ) , $curTab
         );
         echo '<div class="leftsell_content">';
         
         // Publish products ----------------------------------------------------------
         echo '<div id="tabPublish">';
         if ( $curTab == "tabPublish" ) { 
            echo '<h2>' . esc_html__( 'Private published Products and Services', 'leftsell' ) .'</h2>';
            echo esc_html__( 'Share your goods to all shops in your private market. Unsharing deletes the products on the other shop(s).', 'leftsell' );
            echo '<br />';
            if ( get_option( "leftsell_privatemarket" ) == 1 and class_exists( 'WooCommerce' ) ) {
               $objTable = new leftsell_Products_List_Table( 30 );
               $objTable->m_blnOwnProducts = true;
               $objTable->AddColumn( new leftsell_List_Table_Column( "title", "Name", true ) ); 
               $objTable->AddColumn( new leftsell_List_Table_Column( "price", "Price", true ) ); 
               $objTable->AddColumn( new leftsell_List_Table_Column( "subscribedby", "Active", true ) ); 
               $objTable->AddColumn( new leftsell_List_Table_Column( "type", "Type", true ) ); 
               $objTable->AddColumn( new leftsell_List_Table_Column( "private_share", "Private share", true ) ); 
               $objTable->m_aryBulkActions = array(
                                             "share" => esc_html__( 'Share', 'leftsell' ),
                                             "unshare" => esc_html__( 'Unshare', 'leftsell' )
                                             );
               $objShop = new leftsellShop( true );
               $objTable->m_aryData = $objShop->aryGetProducts( "all" );
               $objTable->prepare_items();
               ?>
               <form id="private-share-filter" method="get">
                  <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                  <?php $objTable->display() ?>
               </form>
               <?php
               $objShop = null;
            } else {
               echo esc_html__( 'Your private market is currently disabled.', 'leftsell' );
               echo '<br /><br />';
               echo esc_html__( 'You can switch in on in ', 'leftsell' );
               echo '<a href="' . admin_url() . 'admin.php?page=' . esc_html( sanitize_text_field( $_GET[ "page" ] ) ) . 
                        '&tab=tabPMOptions">' . esc_html__( 'settings.', 'leftsell' ) . '</a>';
               echo '<br /><br />';
            }
         }
         echo '</div>'; // tabPublish
         
         // Products in private market ------------------------------------------------
         echo '<div id="tabInMarket">';
         if ( $curTab == "tabInMarket" ) { 
            echo '<h2>' . esc_html__( 'Products and Services in private market', 'leftsell' ) .'</h2>';
            echo esc_html__( 'Shared goods from your private market. Unsubscribe deletes the local product. Import creates it as independent product. ', 'leftsell' );
            echo '<br />';
            
            if ( get_option( "leftsell_privatemarket" ) == 1 and class_exists( 'WooCommerce' ) ) {
               $objTable = new leftsell_Products_List_Table( 50 );
               $objTable->m_blnOwnProducts = false;
               $objTable->AddColumn( new leftsell_List_Table_Column( "title", "Name", true ) ); 
               $objTable->AddColumn( new leftsell_List_Table_Column( "price", "Price", true ) ); 
               $objTable->AddColumn( new leftsell_List_Table_Column( "type", "Type", true ) ); 
               $objTable->AddColumn( new leftsell_List_Table_Column( "import", "Import", true ) ); 
               $objTable->AddColumn( new leftsell_List_Table_Column( "subscribe", "Subscribe", true ) ); 
               $objTable->m_aryBulkActions = array(
                                             "subscribe" => esc_html__( 'Subscribe', 'leftsell' ),
                                             "unsubscribe" => esc_html__( 'Unsubscribe', 'leftsell' ),
                                             "import" => esc_html__( 'Import', 'leftsell' ),
                                             "delete" => esc_html__( 'Delete imported', 'leftsell' )
                                             );
               $objMarket = new leftsellMarket( true );
               $objTable->m_aryData = $objMarket->GetPMProducts(); // get from Rest
               $objTable->prepare_items();
               ?>
               <form id="private-share-filter" method="get">
                  <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                  <?php $objTable->display() ?>
               </form>
               <?php
               $objMarket = null;
            } else {
               echo esc_html__( 'Your private market is currently disabled.', 'leftsell' );
               echo '<br /><br />';
               echo esc_html__( 'You can switch in on in ', 'leftsell' );
               echo '<a href="' . admin_url() . 'admin.php?page=' . esc_html( sanitize_text_field( $_GET[ "page" ] ) ) . 
                        '&tab=tabPMOptions">' . esc_html__( 'settings.', 'leftsell' ) . '</a>';
               echo '<br /><br />';
            }
         }
         echo '</div>'; // tabInMarket
      
         // Shops in PrivateMarket ----------------------------------------------------    
         echo '<div id="tabPrivateMarket">';
         if ( $curTab == "tabPrivateMarket" ) { 
            if ( get_option( "leftsell_privatemarket" ) == 1 ) {
               // add new shop
               echo '<h2>' . esc_html__( 'Add new shop', 'leftsell' ) .'</h2>';
               echo esc_html__( 'You can only add shops with the LeftSell plugin activated and where you are a WP admin. ', 'leftsell' ) .' ';
               echo esc_html__( 'Once you have sent your request, confirm the connection on the other side. ', 'leftsell' ) .' ';
               echo esc_html__( 'If adding a shop for which you do not have admin rights, you might get blocked for all markets.', 'leftsell' ) .' ';
               echo '<br />';
               echo esc_html__( 'Enter base URL of your other WordPress shop: ', 'leftsell' ) .' ';
               echo '<div id="leftsell_newshop" style="margin-top:10px">';
               echo '<input id="newshop" autocomplete="off" style="min-width:50%" type="text" value="https://" /> ';
               echo '<button onclick="leftsellConnectPrivate();">Connect</button> ';
               echo '</div>';
               echo '<br /><br />';
               echo '<br /><br />';
               // joined shops
               echo '<h2>' . esc_html__( 'Connected shops', 'leftsell' ) .'</h2>';
               echo esc_html__( 'Shops belonging to your private market: share to or subscribe from these sites.', 'leftsell' ) . ' ';
               echo esc_html__( 'Each shop in your list has to be confirmed.', 'leftsell' ) .' ';
               echo esc_html__( 'Selling is done inside each shop, not on the publisher (except it is the publisher of an item).', 'leftsell' );
               $objMarket = new leftsellMarket( true );
               $objTable  = new leftsell_Shop_List_Table( 50 );
               $objTable->AddColumn( new leftsell_List_Table_Column( "shopurl", "Shop", true ) ); 
               $objTable->AddColumn( new leftsell_List_Table_Column( "shopaction", "Action", true ) ); 
               foreach( $objMarket->m_aryShops as $objShop ) {
                  $objTable->m_aryData[] = $objShop->ToArray();
               }
               $objTable->prepare_items();
               $objTable->display();
               
               // join requests from outside
               echo '<h3>' . esc_html__( 'Join requests to this shop', 'leftsell' ) .'</h3>';
               if ( !empty( $objMarket->m_aryJoinRequestsFrom ) ) {
                  $i = 0;
                  echo '<table>';
                  foreach ( $objMarket->m_aryJoinRequestsFrom as $objShopRequest ) {
                     $i++;
                     echo '<tr><td style="padding-right:20px">';
                     echo '<span style="font-weight:bold; color:red">' . esc_url( $objShopRequest->m_strURL ) . '</span>';
                     echo '</td>';
                     echo '<td id="leftsellAccept' . esc_html( $i ) . '">';
                     echo $objUI->BuildAjaxButton( "acceptjoin", esc_url( $objShopRequest->m_strURL ), 
                                                   'leftsellAccept' . esc_html( $i ), esc_html__( 'Accept', 'leftsell' ), 'reload' );
                     echo ' ';
                     echo $objUI->BuildAjaxButton( "declinejoin", esc_url( $objShopRequest->m_strURL ), 
                                                   'leftsellAccept' . esc_html( $i ), esc_html__( 'Decline', 'leftsell' ), 'reload' );
                     echo '</td>';
                     echo '</tr>';
                  }
                  echo '</table>';
               } else { 
                  echo esc_html__( 'none', 'leftsell' ); 
               }
               echo '<br />';
               echo '<br />';

               // pending join requests to outside
               echo '<h3>' . esc_html__( 'Your pending join requests', 'leftsell' ) .'</h3>';
               if ( !empty( $objMarket->m_aryJoinRequestsTo ) ) {
                  echo '<table>';
                  $i = 0;
                  foreach ( $objMarket->m_aryJoinRequestsTo as $objShopRequest ) {
                     $i++;
                     echo '<tr><td style="padding-right:20px">';
                     echo esc_url( $objShopRequest->m_strURL );
                     echo '</td>';
                     echo '<td id="leftsellCancel' . esc_html( $i ) . '">';
                     echo $objUI->BuildAjaxButton( "canceljoin", esc_url( $objShopRequest->m_strURL ), 
                                                   'leftsellCancel' . esc_html( $i ), esc_html__( 'Cancel', 'leftsell' ), 'reload' );
                     echo '</td>';
                     echo '</tr>';
                  }
                  echo '</table>';
               } else { 
                  echo esc_html__( 'none', 'leftsell' ); 
               }
               $objMarket = null;
               echo '<br />';
               echo '<br />';

               
            } else {
               echo esc_html__( 'Your private market is currently disabled.', 'leftsell' );
               echo '<br /><br />';
               echo esc_html__( 'You can switch in on in ', 'leftsell' );
               echo '<a href="' . admin_url() . 'admin.php?page=' . esc_html( sanitize_text_field( $_GET[ "page" ] ) ) . 
                        '&tab=tabPMOptions">' . esc_html__( 'settings.', 'leftsell' ) . '</a>';
               echo '<br /><br />';
            }
         }
         echo '</div>'; // tabPrivateMarket

         // Settings ------------------------------------------------------------------
         echo '<div id="tabPMOptions">';
         if ( $curTab == "tabPMOptions" ) { 
            // market settings
            $objUI = new leftsellUI();
            echo '<h2>' . esc_html__( 'Private Market', 'leftsell' ) .'</h2>';
            echo '<table><tr><td>';
            echo '<h3>' . esc_html__( 'Enable private market', 'leftsell' ) .'</h3>';
            echo esc_html__( 'Enable sharing products and services for known websites: start the private market.', 'leftsell' );
            echo '<br />';
            echo esc_html__( 'Disabling it sets all subscribed products to disabled.', 'leftsell' );
            echo '<br /><br /></td>';
            echo '<td>' . $objUI->GetOptionSwitch( "leftsell_privatemarket" ) . '</td>';
            echo '</tr>';
            echo '<tr><td>';
            echo '<h4 style="margin-bottom:5px">' . esc_html__( 'Text Modification', 'leftsell' ) .'</h4>';
            echo esc_html__( 'Add option to remove links and text-formats for subscribed shops: submit clean text', 'leftsell' );
            echo '<br /><br /></td><td>';
            echo $objUI->GetOptionSwitch( "leftsell_pm_default_sanitize" );
            echo '</td></tr>';
            echo '<tr><td>';
            echo '<h4 style="margin-bottom:5px">' . esc_html__( 'Include Comments', 'leftsell' ) .'</h4>';
            echo esc_html__( 'Share comments and ratings of products', 'leftsell' );
            echo '</td><td>';
            echo $objUI->GetOptionSwitch( "leftsell_pm_share_comments" );
            echo '</td></tr>';

            echo '<tr><td>';
            echo '<h4 style="margin-bottom:5px">' . esc_html__( 'Synchronize Pictures', 'leftsell' ) .'</h4>';
            echo esc_html__( 'Synchronize pictures on product update', 'leftsell' );
            echo '</td><td>';
            echo $objUI->GetOptionSwitch( "leftsell_pm_sync_pics" );
            echo '</td></tr>';

            echo '<tr><td>';
            echo '<h4 style="margin-bottom:5px">' . esc_html__( 'No background jobs', 'leftsell' ) .'</h4>';
            echo esc_html__( 'Do not use background jobs for synchronizing', 'leftsell' );
            echo '</td><td>';
            echo $objUI->GetOptionSwitch( "leftsell_nobackground" );
            echo '</td></tr>';

            echo '</table>';
            echo '<br />';
            echo '<br />';
            echo '<br />';
         }
         echo '</div>'; // tabPMOptions

         // Log ------------------------------------------------------------------------
         echo '<div id="tabPMLog">';
         if ( $curTab == "tabPMLog" ) {
            echo '<h2>' . esc_html__( 'Private Market Log', 'leftsell' ) .'</h2>';
            echo '<span id="leftsellclearlog" style="margin-right:10px">' . 
                     $objUI->BuildAjaxButton( "clearoption", 'leftsell_pm_log', 'leftsellclearlog', 
                     esc_html__( 'Clear', 'leftsell' ), 'reload' ) . 
                  '</span>';
            echo '<button onclick="location.reload()">'. esc_html__( 'Refresh', 'leftsell' ) . '</button><br /><br />';
            $objMarket = new leftsellMarket( true );
            foreach( $objMarket->PMGetLog() as $entry ) {
               echo esc_html( sanitize_text_field( $entry ) ) . "<br />";
            }
            echo '<br />';
            echo '<br />';
            echo '<br />';
         }
         echo '</div>'; // tabPMLog
         echo '</div>'; // leftsell_content

         $objUI = null;
      }
      echo '</div>'; // wrap
   }
?>