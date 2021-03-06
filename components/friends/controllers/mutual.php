<?php
/**
 * @version      $Id$
 * @package      Appleseed.Components
 * @subpackage   Friends
 * @copyright    Copyright (C) 2004 - 2010 Michael Chisari. All rights reserved.
 * @link         http://opensource.appleseedproject.org
 * @license      GNU General Public License version 2.0 (See LICENSE.txt)
 */

// Restrict direct access
defined( 'APPLESEED' ) or die( 'Direct Access Denied' );

/** Friends Component Mutual Controller
 * 
 * Friends Component Mutual Controller Class
 * 
 * @package     Appleseed.Components
 * @subpackage  Friends
 */
class cFriendsMutualController extends cController {
	
	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {       
		parent::__construct( );
	}
	
	public function Display ( $pView = null, $pData = array ( ) ) {
		
		switch ( $pView ) {
			case 'summary':
				return ( $this->_DisplaySummary() );
			break;
		}
		
		return ( true );
	}
	
	private function _DisplaySummary ( ) {
		
		$this->_Current = $this->Talk ( 'User', 'Current' );
		$this->_Focus = $this->Talk ( 'User', 'Focus' );
		
		// If user isn't logged in, or we're viewing our own page, then don't display.
		if ( ( $this->_Focus->Account == $this->_Current->Account ) or ( !$this->_Current ) ) {
			return ( true );
		}
		
		$this->View = $this->GetView ( 'summary' ); 
		
		$this->View->Find ( '[class=all-mutual-friends-link]', 0 )->href = 'http://' . $this->_Focus->Domain . '/profile/' . $this->_Focus->Username . '/friends/mutual/';
		
		if ( !$this->_PrepSummary() ) return ( false );
		
		$this->View->Display();
		
		return ( true );
	}
	
	private function _PrepSummary ( ) {
		
		$tabs =  $this->View->Find ('nav[id=profile-friends-tabs]', 0);
		$tabs->innertext = $this->GetSys ( 'Components' )->Buffer ( 'friends', 'tabs' );
		
		$data = array ( "account" => $this->_Current->Account, 'source' => ASD_DOMAIN, 'request' => $this->_Current->Account );
		$currentInfo = $this->GetSys ( "Event" )->Trigger ( "On", "User", "Info", $data );
		$currentInfo->username = $current->Username;
		$currentInfo->domain = $current->Domain;
		$currentInfo->account = $current->Username . '@' . $current->Domain;
		
		$data = array ( "account" => $this->_Focus->Account, 'source' => ASD_DOMAIN, 'request' => $this->_Focus->Account );
		$focusInfo = $this->GetSys ( "Event" )->Trigger ( "On", "User", "Info", $data );
		$focusInfo->username = $this->_Focus->Username;
		$focusInfo->domain = $this->_Focus->Domain;
		$focusInfo->account = $this->_Focus->Username . '@' . $this->_Focus->Domain;
		
		// Calculate the mutual friends, randomize them, and limit to 10.
		$mutualFriends = array_intersect ( $currentInfo->friends, $focusInfo->friends );
		shuffle ( $mutualFriends );
		$mutualFriends = array_slice ( $mutualFriends, 0, 10 );
		
		if ( count ( $mutualFriends ) < 1 ) return ( false );
		
		$li = $this->View->Find ( "[class=friends-mutual-summary-item]", 0);
		
		$row = $this->View->Copy ( "[class=friends-mutual-summary-item]" )->Find ( "[class=friends-mutual-summary-item]", 0 );
		
		$rowOriginal = $row->outertext;
		
		$li->innertext = '';
		
		foreach ( $mutualFriends as $m => $mutualFriend ) {
			$row = new cHTML ();
			$row->Load ( $rowOriginal );
		
			list ( $username, $domain ) = split ( '@', $mutualFriend );
			
			$data = array ( "username" => $username, "domain" => $domain, "width" => 32, "height" => 32 );
			$row->Find ( '[class=friends-icon]', 0 )->src = $this->GetSys ( "Event" )->Trigger ( "On", "User", "Icon", $data );
			
			$row->Find ( '[class=friends-icon-link]', 0 )->href = 'http://' . $domain . '/profile/' . $username . '/';
			
		    $li->innertext .= $row->outertext;
		    unset ( $row );
		}
		
		return ( true );
	}
	
}