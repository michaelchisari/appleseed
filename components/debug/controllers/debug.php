<?php
/**
 * @version      $Id$
 * @package      Appleseed.Components
 * @subpackage   Debug
 * @copyright    Copyright (C) 2004 - 2010 Michael Chisari. All rights reserved.
 * @link         http://opensource.appleseedproject.org
 * @license      GNU General Public License version 2.0 (See LICENSE.txt)
 */

// Restrict direct access
defined( 'APPLESEED' ) or die( 'Direct Access Denied' );

/** Debug Component Controller
 * 
 * Debug Component Controller Class
 * 
 * @package     Appleseed.Components
 * @subpackage  Debug
 */
class cDebugDebugController extends cController {
	
	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {       
		parent::__construct( );
	}
	
	function Display ( $pView = null, $pData = array ( ) ) {
		$user = $this->Talk ( "User", "Current" );
		
		$parameters['account'] = $user->Username . '@' . $user->Domain;
		$access = $this->Talk ( "Security", "Access", $parameters );
		
		if ( ( !$user->Username ) or ( !$access->Get ( "Admin" ) ) ) return ( true );
		
		$logs = $this->GetSys ( "Logs ");
		
		$this->Debug = $this->GetView ( $pView );
		
		// Warnings
		$this->_PrepareWarnings ();
		
		// Queries
		$this->_PrepareQueries ();
		
		// Memory
		$this->_PrepareMemory ();
		
		// Benchmarks
		$this->_PrepareBenchmarks ();
		
		echo $this->Debug; 
		
		return ( false );
	}
	
	private function _PrepareWarnings ( ) {
		
		$warnings = $this->GetSys ( "Logs" )->GetLogs ( "Warnings" );
		
		list ( $dir, $null ) = explode ( 'components', __FILE__ );
		
		$count = count ( $warnings );
		
		$this->Debug->Find ( "[id=warnings-system-total]", 0)->innertext = __ ("System Total Warnings", array ( "count" => $count ) );
		
		$tbody = $this->Debug->Find ( "[id=debug-warnings] table tbody", 0);
		
		$row = $tbody->Find ( "tr", 0);
		
		$debugWarningsId = $row->Find( "[class=debug-warnings-id]", 0 );
		$debugWarningsWarning = $row->Find( "[class=debug-warnings-warning]", 0 );
		$debugWarningsFile = $row->Find( "[class=debug-warnings-file]", 0 );
		$debugWarningsLine = $row->Find( "[class=debug-warnings-line]", 0 );
		
		foreach ( $warnings as $w => $warning ) {
		    $oddEven = empty($oddEven) || $oddEven == 'even' ? 'odd' : 'even';
			
			$row->class = $oddEven;
			
			$text = $warning->Value;
			$context = $warning->Context;
			
			list ( $null, $context ) = explode ( $dir, $context );
			
			list ( $file, $line ) = explode ( ':', $context );
			
			$debugWarningsId->innertext = $w;
			$debugWarningsWarning->innertext = $text;
			$debugWarningsFile->innertext = $file;
			$debugWarningsLine->innertext = $line;
			
			$tbody->innertext .= $row->outertext;
		}
		
		//$this->Debug->RemoveElement ( "[id=debug-warnings] tr" );
		
		return ( true );
		
	}
	private function _PrepareQueries ( ) {
		
		$queries = $this->GetSys ( "Logs" )->GetLogs ( "Queries" );
		
		$count = count ( $queries );
		
		$this->Debug->Find ( "[id=queries-system-total]", 0)->innertext = __ ("System Total Queries", array ( "count" => $count ) );
		
		$tbody = $this->Debug->Find ( "[id=debug-queries] table tbody", 0);
		
		$row = $tbody->Find ( "tr", 0);
		
		foreach ( $queries as $q => $query ) {
		    $oddEven = empty($oddEven) || $oddEven == 'even' ? 'odd' : 'even';
			
			$row->class = $oddEven;
			
			$statement = $query->Value;
			$context = $query->Context;
			
			list ( $class, $table ) = explode ('.', $context );
			
			$row->Find( "[class=debug-queries-id]", 0 )->innertext = $q;
			$row->Find( "[class=debug-queries-class]", 0 )->innertext = $class;
			$row->Find( "[class=debug-queries-table]", 0 )->innertext = $table;
			$row->Find( "[class=debug-queries-query]", 0 )->innertext = $statement;
			
			$this->Debug->Find ( "[id=debug-queries] table tbody", 0)->innertext .= $row->outertext;
		
		}
		
		$this->Debug->RemoveElement ( "[id=debug-queries] tr" );
		
		return ( true );
	}
	
	private function _PrepareMemory ( ) {
		
		$memories = $this->GetSys ( "Logs" )->GetLogs ( "Memory" );
		
		// First get the system memory amount
		foreach ( $memories as $m => $memory ) {
			if ($memory->Context == "_system" ) {
				$total = $memory->Value;
				$memory = sprintf("%2.2f", ( $total / 1024 / 1024 ) );
				unset ( $memories[$m]);
			}
		}
		$this->Debug->Find ( "[id=memory-system-total]", 0)->innertext = __ ("System Total Memory", array ( "memory" => $memory ) );
		
		$tbody = $this->Debug->Find ( "[id=debug-memory] table tbody", 0);
		
		$row = $tbody->Find ( "tr", 0);
		
		foreach ( $memories as $l => $log ) {
		    $oddEven = empty($oddEven) || $oddEven == 'even' ? 'odd' : 'even';
			
			$row->class = $oddEven;
			
			$value = $log->Value;
			$context = $log->Context;
			
			list ( $controller, $component, $instance, $view ) = explode ('.', $context );
			
			$row->Find( "[class=debug-memory-id]", 0 )->innertext = $l;
			$row->Find( "[class=debug-memory-controller]", 0 )->innertext = $controller;
			$row->Find( "[class=debug-memory-component]", 0 )->innertext = $component;
			$row->Find( "[class=debug-memory-instance]", 0 )->innertext = $instance;
			$row->Find( "[class=debug-memory-view]", 0 )->innertext = $view;
			$memory = sprintf("%2.2f", ( $value / 1024 / 1024 ) );
			$row->Find( "[class=debug-memory-amount]", 0 )->innertext = __("Memory In Megabytes", array ( "memory" => $memory ) );
			
			$this->Debug->Find ( "[id=debug-memory] table tbody", 0)->innertext .= $row->outertext;
		
		}
		
		$this->Debug->RemoveElement ( "[id=debug-memory] tr" );
		
		return ( true );
	}
	
	private function _PrepareBenchmarks ( ) {
		
		$benchmarks = $this->GetSys ( "Logs" )->GetLogs ( "Benchmarks" );
		
		// First get the system benchmark
		foreach ( $benchmarks as $b => $benchmark ) {
			if ($benchmark->Context == "_system" ) {
				$total = $benchmark->Value;
				$seconds = sprintf("%.4f", ($total));
				unset ( $benchmarks[$b]);
			}
		}
		$this->Debug->Find ( "[id=benchmarks-system-total]", 0)->innertext = __ ("System Total Time", array ( "seconds" => $seconds ) );
		
		$tbody = $this->Debug->Find ( "[id=debug-benchmarks] table tbody", 0);
		
		$row = $tbody->Find ( "tr", 0);
		
		foreach ( $benchmarks as $l => $log ) {
		    $oddEven = empty($oddEven) || $oddEven == 'even' ? 'odd' : 'even';
			
			$row->class = $oddEven;
			
			$value = $log->Value;
			$context = $log->Context;
			
			list ( $controller, $component, $instance, $view ) = explode ('.', $context );
			
			$row->Find( "[class=debug-benchmark-id]", 0 )->innertext = $l;
			$row->Find( "[class=debug-benchmark-controller]", 0 )->innertext = $controller;
			$row->Find( "[class=debug-benchmark-component]", 0 )->innertext = $component;
			$row->Find( "[class=debug-benchmark-instance]", 0 )->innertext = $instance;
			$row->Find( "[class=debug-benchmark-view]", 0 )->innertext = $view;
			$seconds = sprintf("%.4f", ($value));
			$row->Find( "[class=debug-benchmark-time]", 0 )->innertext = __("Benchmark In Seconds", array ( "seconds" => $seconds ) );
			
			$this->Debug->Find ( "[id=debug-benchmarks] table tbody", 0)->innertext .= $row->outertext;
		
		}
		
		$this->Debug->RemoveElement ( "[id=debug-benchmarks] tr" );
		
		return ( true );
	}

}