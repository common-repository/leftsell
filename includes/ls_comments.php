<?php
/**
 * Purpose:           Handle Comments and ratings for products and services
 * Function URI:      https://leftsell.com/
 * Version:           2.0.2
 * Author:            Kundschaft Schweiz
 * Author URI:        https://kundschaft.ch/ 
 **/
defined( 'ABSPATH' ) || exit;
global $flagCommentTrigger; // controls trigger on event wp_insert_comment
$flagCommentTrigger = 0;

// WP HOOKS ---------------------------------------------------------------------------
function leftsellOnPMCommentInsert( $comment_ID , $meta_key = "") {
   // a comment was inserted on a subscriber
   $objComment = new leftsellComment();
   $postid = $objComment->GetProductIdFromCommentId( $comment_ID );
   if ( $postid == "" ) { return; }
   if ( $postid == 0 )  { return; }
   $objShop = new leftsellShop();
   $objProduct = new leftsellProduct();
   $objProduct->fromWPId( $postid );
   if ( $objProduct->IsProduct() == false ) { return; } 
   global $flagCommentTrigger;
   if ( $flagCommentTrigger == 0 ) {
      if ( $objProduct->IsOwnProduct() ) {
         // product is on this shop
         if ( $objProduct->IsShared() ) {
            if ( strlen( $objProduct->fields[ "pm_subscribedby" ] ) > 3 ) {
               leftsellOnPMProductUpdate( $postid, '', '' );
            }
         }
      } else {
         // product origins not from this shop
         if ( $objProduct->IsSubscribed() ) {
            // inform publisher and delete local comment ( wait till rating is written )
            if ( leftsellCRON ) {
               $args = array( "jobname" => "SendCommentToOriginator",
                              "commentid"  => $comment_ID,
                              "postid"     => $postid );
               wp_schedule_single_event( time() + 5, 'lefsellEvent', $args  );
            } else {
               $objComment = new leftsellComment();
               $objComment->SendToOriginator( $comment_ID, $postid );
               $objComment = null;
            }
         }
      }
   }
   $objShop = null;
   $objProduct = null;
}
add_action( 'wp_insert_comment', 'leftsellOnPMCommentInsert', 10, 1 );

function leftsellOnPMCommentUpdate( $comment_ID ) {
   // comment updated on local product
   //leftsellAddCommentMeta( $comment_ID );
   $objComment = new leftsellComment();
   $postid = $objComment->GetProductIdFromCommentId( $comment_ID );
   if ( $postid == "" ) { return; }
   if ( $postid == 0 )  { return; }
   $objShop = new leftsellShop();
   $objProduct = new leftsellProduct();
   $objProduct->fromWPId( $postid );
   if ( $objProduct->IsProduct() == false ) { return; } 
   if ( $objProduct->IsOwnProduct() ) {
      if ( $objProduct->IsShared() ) {
         if ( strlen( $objProduct->fields[ "pm_subscribedby" ] ) > 3 ) {
            leftsellOnPMProductUpdate( $postid, '', '' );
         }
      }
   }
   $objProduct = null;
   $objComment = null;
}
add_action( 'comment_on_trash', 'leftsellOnPMCommentUpdate', 10, 1 );
add_action( 'edit_comment',     'leftsellOnPMCommentUpdate', 10, 1 );

function leftsell_approve_comment_callback( $new_status, $old_status, $comment ) {
   if( $old_status != $new_status ) {
      if( $new_status == 'approved' ) {
         leftsellOnPMCommentUpdate( $comment->comment_ID );
      }
   }
}
add_action( 'transition_comment_status', 'leftsell_approve_comment_callback', 10, 3);

function leftsellAddCommentMeta( $comment_ID ) {
   // check if comment meta for rating is initialised after comment on frontpage
   $rating = get_comment_meta( $comment_ID, "rating", true );
   if ( $rating == "" ) { update_comment_meta( $comment_ID, "rating", 5 ); }
}
add_action( 'add_comment_rating', 'leftsellAddCommentMeta', 10, 1 );

function leftsellAddCommentMetaAdmin( $comment ) {
   // check if comment meta for rating is initialised before showing on Admin page
   // requires reload comment edit page
   $rating = get_comment_meta( $comment->comment_ID, "rating", true );
   if ( $rating == "" ) { update_comment_meta( $comment->comment_ID, "rating", 5 ); }
}
add_action( 'add_meta_boxes_comment', 'leftsellAddCommentMetaAdmin', 10, 1 );
// END of Hooks -----------------------------------------------------------------------



class leftsellComment {
   /**
   *
   * Handles product comments and ratings
   * 
   **/
   public $fields = array(
                       "id" => "",
                       "postid" => "",
                       "author" => "",
                       "author_email" => "",
                       "author_url" => "",
                       "author_ip" => "",
                       "date" => "",
                       "date_gmt" => "",
                       "content" => "",
                       "karma" => "",
                       "approved" => "",
                       "agent" => "",
                       "type" => "",
                       "parentid" => "",
                       "userid" => "",
                       "rating" => "",
                       "leftsellshopid" => "",
                       "leftsellExtCommentId" => "",
                       "leftsellExtCommentParentId" => "",
                       "leftsellExtPostId" => "",
                    );
   public function fromWPComment( $objWPComment) {
      $aryComment = $objWPComment->to_array();
      $this->fields[ "id" ]                         = $aryComment[ "comment_ID" ];
      $this->fields[ "postid" ]                     = $aryComment[ "comment_post_ID" ];
      $this->fields[ "author" ]                     = $aryComment[ "comment_author" ];
      $this->fields[ "author_email" ]               = $aryComment[ "comment_author_email" ];
      $this->fields[ "author_url" ]                 = $aryComment[ "comment_author_url" ];
      $this->fields[ "author_ip" ]                  = $aryComment[ "comment_author_IP" ];
      $this->fields[ "date" ]                       = $aryComment[ "comment_date" ];
      $this->fields[ "date_gmt" ]                   = $aryComment[ "comment_date_gmt" ];
      $this->fields[ "content" ]                    = $aryComment[ "comment_content" ];
      $this->fields[ "karma" ]                      = $aryComment[ "comment_karma" ];
      $this->fields[ "approved" ]                   = $aryComment[ "comment_approved" ];
      $this->fields[ "agent" ]                      = $aryComment[ "comment_agent" ];
      $this->fields[ "type" ]                       = $aryComment[ "comment_type" ];
      $this->fields[ "parentid" ]                   = $aryComment[ "comment_parent" ];
      $this->fields[ "userid" ]                     = $aryComment[ "user_id" ];
      $this->fields[ "rating" ]                     = get_comment_meta( $this->fields[ "id" ], "rating", true );
      if ( $this->fields[ "rating" ] == 0 ) { $this->fields[ "rating" ] = 5; }
      $this->fields[ "leftsellshopid" ]             = get_comment_meta( $this->fields[ "id" ], "leftsellshopid", true );
      $this->fields[ "leftsellExtCommentId" ]       = get_comment_meta( $this->fields[ "id" ], "leftsellExtCommentId", true );
      $this->fields[ "leftsellExtCommentParentId" ] = get_comment_meta( $this->fields[ "id" ], "comment_parent", true );
      $this->fields[ "leftsellExtPostId" ]          = get_comment_meta( $this->fields[ "id" ], "leftsellExtPostId", true );
      if ( $this->fields[ "leftsellshopid" ] == "" ) {
         $objShop = new leftsellShop();
         $this->fields[ "leftsellshopid" ] = $objShop->m_intId;
         $objShop = null;
      }
      if ( $this->fields[ "leftsellExtPostId" ] == "" ) {
         $this->fields[ "leftsellExtPostId" ] = $this->fields[ "postid" ];
      }
   }
   public function toWPCommentArray() {
      $aryWP = array();
      $aryWP[ "comment_agent" ]        = $this->fields[ "agent" ];
      $aryWP[ "comment_approved" ]     = $this->fields[ "approved" ];
      $aryWP[ "comment_author" ]       = $this->fields[ "author" ];
      $aryWP[ "comment_author_email" ] = $this->fields[ "author_email" ];
      $aryWP[ "comment_author_IP" ]    = $this->fields[ "author_ip" ];
      $aryWP[ "comment_author_url" ]   = $this->fields[ "author_url" ];
      $aryWP[ "comment_content" ]      = $this->fields[ "content" ];
      $aryWP[ "comment_date" ]         = $this->fields[ "date" ];
      $aryWP[ "comment_date_gmt" ]     = $this->fields[ "date_gmt" ];
      $aryWP[ "comment_karma" ]        = $this->fields[ "karma" ];
      $aryWP[ "comment_parent" ]       = $this->fields[ "parentid" ];
      $aryWP[ "comment_post_ID" ]      = $this->fields[ "postid" ];
      $aryWP[ "comment_type" ]         = $this->fields[ "type" ];
      $aryWP[ "user_id" ]              = $this->fields[ "userid" ];
      $aryWP[ "comment_meta" ] = array( "leftsellExtCommentId" => $this->fields[ "leftsellExtCommentId" ],
                                        "leftsellExtPostId"    => $this->fields[ "leftsellExtPostId" ],
                                        "leftsellshopid"       => $this->fields[ "leftsellshopid" ],
                                        "leftsellExtCommentParentId" => $this->fields[ "leftsellExtCommentParentId" ],
                                        "rating"               => $this->fields[ "rating" ] );
      return $aryWP;
   }
   private function fromRESTArray( $ary ) {
      if ( !isset( $ary[ "fields" ] ) ) { return false; }
      $this->fields = $ary[ "fields" ];
      return $this;
   }
   private function PrepareContent( $text ) {
      // prepare comment text for transmission via json REST
      $text =  ( str_replace( "\r\n", "\\\r\\\n", $text ) );
      return $text;
   }
   private function SortComments( $aryComments ) {
      // given: array of leftsell comments arrays to sort
      // going down to level 2 of answers to transmit to market
      if ( !is_array( $aryComments ) ) { return array(); }
      $aryResult = array();
      $aryParents = array();
      foreach( $aryComments as $objComment ) {
         if ( $objComment->fields[ "parentid" ] == 0 ) {
            $aryParents[ $objComment->fields[ "id" ] ] = $objComment;
         }
      }
      // sort to level 5
      foreach ( $aryParents as $objParent ) {
         // parent
         $aryResult[] = $objParent;
         // children level 1
         foreach( $aryComments as $objChild1 ) {
            if ( $objChild1->fields[ "parentid" ] == $objParent->fields[ "id" ] ) {
               $aryResult[] = $objChild1;
               // childern level 2
               foreach( $aryComments as $objChild2 ) {
                  if ( $objChild2->fields[ "parentid" ] == $objChild1->fields[ "id" ] ) {
                     $aryResult[] = $objChild2;
                     // childern level 3
                     foreach( $aryComments as $objChild3 ) {
                        if ( $objChild3->fields[ "parentid" ] == $objChild2->fields[ "id" ] ) {
                           $aryResult[] = $objChild3;
                           // childern level 4
                           foreach( $aryComments as $objChild4 ) {
                              if ( $objChild4->fields[ "parentid" ] == $objChild3->fields[ "id" ] ) {
                                 $aryResult[] = $objChild4;
                                 // childern level 5
                                 foreach( $aryComments as $objChild5 ) {
                                    if ( $objChild5->fields[ "parentid" ] == $objChild4->fields[ "id" ] ) {
                                       $aryResult[] = $objChild5;
                                    }
                                 }
                              }
                           }
                        }
                     }
                  }
               }
            }
         }
      }
      return $aryResult;
   }
   public function GetProductIdFromCommentId ( $comment_ID ) {
      $objComment = get_comment( $comment_ID, OBJECT );
      if ( empty( $objComment ) ) { return ''; }
      $aryComment = $objComment->to_array();
      if ( !is_array( $aryComment ) ) { return ''; }
      if ( isset( $aryComment[ "comment_post_ID" ] ) ) {
         return $aryComment[ "comment_post_ID" ];
      } else {
         return '';
      }
   }
   public function Insert( $aryInsert ) {
      global $flagCommentTrigger;
      $flagCommentTrigger = 1;
      $newId = wp_insert_comment( $aryInsert );
      $flagCommentTrigger = 0;
      return $newId;
   }

   // Private Market local comments distribution --------------------------------------
   public function GetLocalCommentsasJSON( $postid ) {  
      // used for product delievery, return all comments of post as json
      $aryLocalComments = get_comments( array( 'post_id' => $postid ) );
      if ( empty( $aryLocalComments ) ) { 
         // check KCMS
         return ""; 
      }
      $aryOfComments = array();
      // convert wp comments to leftsell
      foreach ( $aryLocalComments as $objLocalComment ) { 
         $objComment = new leftsellComment();
         $objComment->fromWPComment( $objLocalComment );
         // prepare for transmission
         $objComment->fields[ "content" ]  = $this->PrepareContent( $objComment->fields[ "content" ] );
         $objComment->fields[ "leftsellExtCommentId" ]  = $objComment->fields[ "id" ];
         $objComment->fields[ "leftsellExtPostId" ]  = $objComment->fields[ "postid" ];
         $objComment->fields[ "leftsellExtCommentParentId" ]  = $objComment->fields[ "parentid" ];
         // add to transmission array
         $aryOfComments[ $objComment->fields[ "id" ] ] =  $objComment;
         $objComment = null;
      }
      $aryLocalComments = null;
      $aryOfComments = $this->SortComments( $aryOfComments );
      $objJSON = new leftsellJSON;
      $buffer = $objJSON->ToJSON( $aryOfComments );
      $objJSON = null;
      return $buffer;
   }
   public function SavePostComments( $postid,  $strJSONComments ) {
      // received comments via REST for a subscribed product
      // invoked by leftsellProduct::SaveProduct

      // This is the subscriber
      if ( $postid == "" )      { return; }
      if ( $strJSONComments == "" ) { return; }
      $objJSON = new leftsellJSON();
      $aryComments = $objJSON->FromJSON( $strJSONComments );
      if ( !is_array( $aryComments ) ) { return; }
      if ( count( $aryComments ) == 0 ) {
         $aryComments = $objJSON->FromJSON( $strJSONComments, true );
      }
      if ( !is_array( $aryComments ) ) { return; }
      
      // remove all comments
      $objProduct = new leftsellProduct();
      $objProduct->fromWPId( $postid );
      if ( $objProduct->IsSubscribed() ) {
         $aryToDel = get_comments( array( 'post_id' => $postid ) );
         foreach( $aryToDel as $objExisting ) {
            wp_delete_comment( $objExisting->comment_ID );
         }
      }
      $aryNew = array();
      // handle new comments for product
      foreach( $aryComments as $fieldsarray ) {
         $leftsellComment = new leftsellComment();
         $leftsellComment->fromRESTArray( $fieldsarray );
         
         // transfer values to local
         $leftsellComment->fields[ "leftsellExtPostId" ] = $leftsellComment->fields[ "postid" ];
         $leftsellComment->fields[ "postid" ] = $postid;
         $leftsellComment->fields[ "leftsellExtCommentId" ] = $leftsellComment->fields[ "id" ];
         $leftsellComment->fields[ "leftsellExtCommentParentId" ] = $leftsellComment->fields[ "parentid" ];
         $leftsellComment->fields[ "postid" ] = $postid;
         $leftsellComment->fields[ "userid" ] = "";
         
         // find parent
         foreach( $aryNew as $objNew ) {
            if ( $objNew->fields[ "leftsellExtCommentId" ] == $leftsellComment->fields[ "parentid" ] ) {
               $leftsellComment->fields[ "parentid" ] = $objNew->fields[ "id" ];
            }
         }

         // insert comment
         $aryInsert = $leftsellComment->toWPCommentArray();
         $aryInsert[ "id" ] = "";
         $newId = $this->Insert( $aryInsert );
         if ( $newId === false ) {
            $objMarket = new leftsellMarket( true );
            $objMarket->PMLog( 'Failed to insert comment: ' . $leftsellComment->fields[ "content" ]);
            $objMarket = null;
         } else {
            $objMarket = new leftsellMarket( true );
            $objMarket->PMLog( 'Inserted comment: ' . $leftsellComment->fields[ "content" ] );
            $objMarket = null;
         }
         $leftsellComment->fields[ "id" ] = $newId;
         $aryNew[ $newId ] = $leftsellComment;
      }
      $aryComments = null;
      $objProduct = null;
   }
   public function SendToOriginator( $comment_ID, $postid ) {
      // send a comment to originator shop of product
      $objSend = new leftsellComment();
      $objSend->FromWPComment( get_comment( $comment_ID ) );
      if ( $objSend->fields[ "id" ] == "" ) { return; }
      $objProduct = new leftsellProduct();
      $objProduct->fromWPId( $postid );
      if ( $objProduct->IsProduct()    == false ) { return; }
      if ( $objProduct->IsSubscribed() == false ) { return; }
      if ( $objProduct->fields[ "shopurl" ] == "" ) { return; }
      // prepare for transmission
      $objSend->fields[ "content" ]  = $objSend->PrepareContent( $objSend->fields[ "content" ] );
      $objSend->fields[ "leftsellExtCommentId" ]  = $objSend->fields[ "id" ];
      $objSend->fields[ "leftsellExtPostId" ]  = $objProduct->fields[ "externalwpid" ];
      $objSend->fields[ "leftsellExtCommentParentId" ]  = $objSend->fields[ "parentid" ];
      $objSend->fields[ "rating" ]  = get_comment_meta( $objSend->fields[ "id" ], "rating", true );
      // Inform about new comment
      $objMsg = new leftsellMessage( "PMsubscribercomment" );
      $objJSON = new leftsellJSON();
      $objMsg->m_aryFields[ "jsoncomment" ]   = $objJSON->ToJSON( $objSend->fields );
      $result = $objMsg->SendMessage( $objProduct->fields[ "shopurl" ] . leftsellROUTE );   
      $objJSON = null;
      $objProduct = null;
      $objMsg = null;
      if ( $result == "OK" ) {
         // delete the comment on this site -> full product is resent from originator
         if ( leftsellCRON ) {
            $args = array( "jobname"     => "DeleteComment",
                           "commentid"   => $comment_ID,
                           "wpphp8error" => 'dump' );
         } else {
            $args = array( "jobname"     => "DeleteComment",
                           "commentid"   => $comment_ID );
         }
         wp_schedule_single_event( time() + 5, 'lefsellEvent', $args  );
      } else {
          // was not informed or did not get the comment
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( esc_html__( 'Informing about new comment failed.', 'leftsell' ) );
         $objMarket = null;
      }
      $objSend = null;
      return;
   }
   public function FetchJSONComment( $jsoncomment, $shopid ) {
      // subscribing shop informs about a new or updated comment on its site
      if ( $jsoncomment == "" ) { return "NAK"; }
      if ( $shopid == "" )      { return "NAK"; }
      // fill REST data into leftsellComment
      $objNew = new leftsellComment();
      $objJSON = new leftsellJSON();
      $objNew->fields = $objJSON->FromJSON( $jsoncomment );
      $objJSON = null;
      if ( !isset( $objNew->fields[ "leftsellExtPostId" ] ) )    { return "NAK"; }
      if ( !isset( $objNew->fields[ "leftsellExtCommentId" ] ) ) { return "NAK"; }
      if ( $objNew->fields[ "leftsellExtCommentId" ] == "" )     { return "NAK"; }
      if ( $objNew->fields[ "leftsellExtPostId" ] == "" )        { return "NAK"; }
      // find local product
      $objProduct = new leftsellProduct();
      $objProduct->fromWPId ( $objNew->fields[ "leftsellExtPostId" ] );
      // transfer values to local
      $objNew->fields[ "leftsellExtPostId" ] = $objNew->fields[ "postid" ];
      $objNew->fields[ "postid" ] = $objProduct->fields[ "wpid" ];
      $objNew->fields[ "leftsellExtCommentId" ] = $objNew->fields[ "id" ];
      $objNew->fields[ "leftsellExtCommentParentId" ] = $objNew->fields[ "parentid" ];
      $objNew->fields[ "parentid" ] = ""; // should be a parent
      $objNew->fields[ "userid" ] = "";
      // insert new comment
      $aryInsert = $objNew->toWPCommentArray();
      $aryInsert[ "comment_Id" ] = "";
      $aryInsert[ "comment_approved" ] = 0;
      // do not trigger event
      $newId = $this->Insert( $aryInsert );
      if ( $newId === false ) {
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( 'Failed to insert comment: ' . $objNew->fields[ "content" ]);
         $objMarket = null;
         return "NAK";
      } else {
         update_comment_meta( $newId, "rating", $objNew->fields[ "rating" ] );
         $objMarket = new leftsellMarket( true );
         $objMarket->PMLog( 'Inserted comment: ' . $objNew->fields[ "content" ] );
         $objMarket = null;
         return "OK";
      }
   }
} // end class leftsellComment
?>