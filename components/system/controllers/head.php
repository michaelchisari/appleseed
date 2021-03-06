<?php
/**
 * @version      $Id$
 * @package      Appleseed.Components
 * @subpackage   System
 * @copyright    Copyright (C) 2004 - 2010 Michael Chisari. All rights reserved.
 * @link         http://opensource.appleseedproject.org
 * @license      GNU General Public License version 2.0 (See LICENSE.txt)
 */

// Restrict direct access
defined( 'APPLESEED' ) or die( 'Direct Access Denied' );

/** System Component Head Controller
 * 
 * System Component Head Controller Class
 * 
 * @package     Appleseed.Components
 * @subpackage  System
 */
class cSystemHeadController extends cController {
	
	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {       
		parent::__construct( );
	}
	
	function Display ( $pView = null, $pData = array ( ) ) {

		$this->View = $this->GetView( 'head' );

		$this->_ProcessScripts();
		$this->_ProcessStyles();

		$this->View->Display();

		return ( true );
	}

	private function _ProcessScripts ( ) {
		$scripts = $this->View->Find ( 'script', 0 )->outertext;

        $clients = $this->GetSys ( 'Client' )->Get ( 'Config' )->GetPath ();

		$foundation = $this->GetSys ( 'Router' )->Get ( 'Foundation' );

		list ( $foundation, $null ) = explode ( '.php', $foundation );

		/*
		 * Load all files in the init/ directory.
		 */
		foreach ( $clients as $c => $client ) {
			$directory = ASD_PATH . 'client/' . $client . '/init/';
			if ( !is_dir ( $directory ) ) continue;

			$Storage = Wob::_('Storage');

		    $files = $Storage->Scan ( $directory, 'js' );	

			foreach ( $files as $f => $file ) {
				$inits[$file] = $client;
			}
		}

		foreach ( $inits as $file => $client ) {
			$initPath = 'http://' . ASD_DOMAIN . '/client/' . $client . '/init/'. $file;

			$finalPaths[] = $initPath;
		}

		/*
		 * Find the most relevant global client js file.
		 */
		$clientPath = null;
		foreach ( $clients as $c => $client ) {
			$clientPath = 'http://' . ASD_DOMAIN . '/client/' . $client . '/'. $client . '.js';
			$clientLocation = ASD_PATH . 'client/' . $client . '/' .  $client . '.js';

			if ( !file_exists ( $clientLocation ) ) continue;

			$finalClientPath = $clientPath;
		}

		if ( $finalClientPath ) {
			$finalPaths[] = $clientPath;
		}

		/*
		 * Find the most relevant foundation-specific js file.
		 */
		$foundationPath = null;
		foreach ( $clients as $c => $client ) {
			$foundationPath = 'http://' . ASD_DOMAIN . '/client/' . $client . '/foundations/' . $foundation . '.js';
			$foundationLocation = ASD_PATH . 'client/' . $client . '/foundations/' .  $foundation . '.js';

			if ( !file_exists ( $foundationLocation ) ) continue;

			$finalFoundationPath = $foundationPath;
		}

		if ( $finalFoundationPath ) $finalPaths[] = $finalFoundationPath;

		foreach ( $finalPaths as $f => $final ) {
            $script = new cHTML ();
            $script->Load ( $scripts );

			$script->Find ( 'script', 0 )->src = $final;

			$this->View->Find ( 'script', 0 )->outertext .= "\n" . $script->outertext;
		}
		$this->View->Reload();

		$this->View->Find ( 'script', 0 )->outertext = "";

		return ( true );
	}

	private function _ProcessStyles ( ) {
		$stylesheets = $this->View->Find ( '[rel="stylesheet"]', 0 )->outertext;

        $styles = $this->GetSys ( 'Theme' )->GetStyles ();

        $order = $this->GetSys ( 'Theme' )->Get ( 'Config' )->GetConfiguration ( 'order' );
        $skip = $this->GetSys ( 'Theme' )->Get ( 'Config' )->GetConfiguration ( 'skip' );

        // Reorder the styles according to the configuration
        if ( isset ( $order ) ) {
            if ( isset ( $skip ) ) $skip = explode ( ' ', $this->GetSys ( 'Theme' )->Get ( 'Config' )->GetConfiguration ( 'skip' ) );
            foreach ( $order as $o => $ostyle ) { 
                foreach ( $styles as $s => $style ) { 
                    if ( strstr ( $style, $ostyle ) ) {
                        if ( in_array ( $ostyle, $skip ) ) continue;
                        $orderedstyles[] = $style;
                    }
                }
            }
        }

		$styles = $orderedstyles;

		foreach ( $styles as $s => $style ) {
			$location = ASD_PATH . 'themes/' . $style;

			$path = 'http://' . ASD_DOMAIN . '/themes/' . $style;

            $style = new cHTML ();
            $style->Load ( $stylesheets );

			$style->Find ( '[rel="stylesheet"]', 0 )->href = $path;

			$this->View->Find ( '[rel="stylesheet"]', 0 )->outertext .= "\n" . $style->outertext;
		}
		$this->View->Reload();

		$this->View->Find ( '[rel="stylesheet"]', 0 )->outertext = "";

		return ( true );
	}
}
