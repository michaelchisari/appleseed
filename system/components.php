<?php
/**
 * @version      $Id$
 * @package      Appleseed.Framework
 * @subpackage   System
 * @copyright    Copyright (C) 2004 - 2010 Michael Chisari. All rights reserved.
 * @link         http://opensource.appleseedproject.org
 * @license      GNU General Public License version 2.0 (See LICENSE.txt)
 */

// Restrict direct access
defined( 'APPLESEED' ) or die( 'Direct Access Denied' );

/** Components Class
 * 
 * Component Management
 * 
 * @package     Appleseed.Framework
 * @subpackage  System
 */
class cComponents extends cBase {
	
	protected $_ComponentCount;

	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {
		
 		// Load component configurations.
 		$this->_Config = new cConf ();
		$this->_Config->Set ( "Data",  $this->_Config->LoadComponents() );
		
		// Load all component base classes.
		$this->_Load ();
		
	}
	
	/**
	 * Loads all component base classes.
	 *
	 * @access  public
	 */
	public function _Load ( ) {
		eval ( GLOBALS );
		
		$configdata = $this->_Config->Get ( "Data" );
		
		// Create the real components
		foreach ( $this->_Config->_Components as $c => $component ) {
			
			$filename = $zApp->GetPath () . DS . 'components' . DS . $component . DS . $component . '.php';
			
			if ( !is_file ( $filename ) ) {
				unset ( $this->_Config->_Components[$c] );
				continue;
			}
			
			require ( $filename );
			
			$componentname = ucwords ( strtolower ( $component ) );
			
			$class = 'c' . $componentname;
			
			if ( !class_exists ( $class ) ) {
				unset ( $this->_Config->_Components[$c] );
				continue;
			}
			
			
			$this->$componentname = new $class();
			
			$this->$componentname->Set ("Config", $configdata[$component] );
			
			$this->$componentname->Set ( 'Component', $component);
			
		}
		
		// Create the component aliases
		foreach ( $this->_Config->_Components as $c => $component ) {
			$componentname = ucwords ( strtolower ( $component ) );
			
			// Set an alias or set of aliases to this component.
			if ( isset ( $configdata[$component]['alias'] ) ) {
				$aliases = $configdata[$component]['alias'];
				if ( is_array ( $aliases ) ) {
					foreach ( $aliases as $a => $alias ) {
						$alias = ucwords ( strtolower ( ltrim ( rtrim ( $alias ) ) ) );
						if ( !isset ( $this->$alias ) ) {
							$this->$alias = clone $this->$componentname;
							$this->$alias->Set ( "Component", $component );
							$this->$alias->Set ( "Alias", $alias );
						} else {
							$warning = __("Alias Name Exists", array ( 'name' => $alias ) );
							$zApp->GetSys ( "Logs" )->Add ( $warning, "Warnings" );
						}
					} 
				} else {
					if ( !isset ( $this->$aliases ) ) {
						$aliases = ucwords ( strtolower ( ltrim ( rtrim ( $aliases ) ) ) );
						$this->$aliases = clone $this->$componentname;
						$this->$aliases->Set ( "Component", $component );
						$this->$aliases->Set ( "Alias", $aliases );
					} else {
						$warning = __("Alias Name Exists", array ( 'name' => $aliases ) );
						$zApp->GetSys ( "Logs" )->Add ( $warning, "Warnings" );
					}
				}
			} 
		}
		
		return ( true );
	}
	
	/**
	 * Load a component
	 *
	 * @access  public
	 * @param string $pController Which controller to use
	 * @param string $pView Which view to load
	 * @param string $pView Which controller task to execute
	 * @param array $pData Extended controller data.
	 */
	public function Go ( $pComponent, $pController = null, $pView = null, $pTask = null, $pData = null ) {
		
		$this->_AddScript ( $pComponent, $pController, $pView, $pTask );
		
		ob_start();

		$context = $this->Execute ( $pComponent, $pController, $pView, $pTask, $pData );
		
		$buffer = ob_get_clean();
		
		if ( $context ) {
			$this->GetSys ( "Buffer" )->AddToCount ( "component" );
			$this->GetSys ( "Buffer" )->Queue ( "component", $context, $buffer );
			$this->GetSys ( "Buffer" )->PlaceHolder ( "component", $context );
		}
		
		return ( true );
	}
	
	public function Execute ( $pComponent, $pController = null, $pView = null, $pTask = null, $pData = null ) {
		eval ( GLOBALS );
		
		/* 
		 * This allows developers to shorten the "Go" parameters.
		 * 
		 */
		
		if ( is_array ( $pController ) ) {
			$pData = $pController;
			$pController = null;
		}
		
		if ( is_array ( $pView ) ) {
			$pData = $pView;
			$pView = null;
		}
		
		if ( is_array ( $pTask ) ) {
			$pData = $pTask;
			$pTask = null;
		}
		
		$component = ltrim ( rtrim ( strtolower ( $pComponent ) ) );
		$componentname = ucwords ( strtolower ( $component ) );
		
		// Skip components which use reserved names
		if ( in_array ( $component, $zApp->Reserved () ) ) {
			$warning = __("Bad Component Name", array ( 'name' => $component ) );
			$zApp->GetSys ( "Logs" )->Add ( $warning, "Warnings" );
			return ( false );
		}
		
		if ( !isset ( $this->$componentname ) ) {
			echo __("Component Not Found", array ( 'name' => $componentname ) );
			return ( false );
		};

		// Overwrite the Controller from Request data.
		if ( $this->GetSys ( "Request" )->Get ('Controller') ) {
			$pController = $this->GetSys ( "Request" )->Get ( 'Controller' );
		} else {
			if ( !$pController ) $pController = $pComponent;
		}
		
		// Overwrite the View from Request data.
		if ( $this->GetSys ( "Request" )->Get ('View') ) {
			$pView = $this->GetSys ( "Request" )->Get ( 'View' );
		} else {
			if ( !$pView ) $pView = $pComponent;
		}
		
		$this->$componentname->Set ( "View", $pView );
		
		$context = $this->$componentname->CreateContext( $pController );
		
		// Overwrite the Task from Request data.
		if ( $rtask = $this->GetSys ( "Request" )->Get ('Task') ) {
			if ( $usecontext = $this->GetSys ( "Request" )->Get ( "Context" ) ) {
				if ( !preg_match ( '/' . $usecontext . '/', $context ) ) {
					if ( !$pTask ) $pTask = "display";
				} else {
					$pTask = $rtask;
				}
			} else {
				$pTask = $rtask;
			}
		} else {
			if ( !$pTask ) $pTask = 'display';
		}
		
		$parameters = array ( 'component' => $pComponent);
		if ( $pController ) $parameters['controller'] = $pController;
		if ( $pView ) $parameters['view'] = $pView;
		if ( $pTask ) $parameters['task'] = $pTask;
		if ( $pData ) $parameters['data'] = $pData;
		
		$this->GetSys ( "Benchmark" )->MemBegin ( $context );
		$this->GetSys ( "Benchmark" )->Start ( $context );
		
		$return = $this->$componentname->Load ( $pController, $pView, $pTask, $pData );
		
		$this->GetSys ( "Benchmark" )->Stop ( $context );
		$this->GetSys ( "Benchmark" )->MemEnd ( $context );
		
		$this->$componentname->AddToInstance();
		
		if ( $return ) 
			return ( $context );
		else
			return ( false );
	}

	public function _AddScript ( $pComponent, $pController = null, $pView = null, $pTask = null, $pData = null ) {
		$queue = $this->GetSys ( 'Buffer' )->Get ( 'Queue' );

		foreach ( $queue['component'] as $c => $component ) {
			if ( preg_match ( '/head.system.(\d+).(\w+)/', $component->Parameters ) ) {
				if ( !$pView ) $pView = $pComponent;

				$clients = $this->GetSys ( 'Client' )->Get ( 'Config' )->GetPath();

				$path = 'http://' . ASD_DOMAIN . '/components/' . $pComponent . '/client/' . $pView . '.js';
				$location = ASD_PATH . 'components/' . $pComponent . '/client/' . $pView . '.js';

				foreach ( $clients as $client ) {
					$clientPath = 'http://' . ASD_DOMAIN . '/client/' . $client . '/components/' . $pComponent . '/' . $pView . '.js';
					$clientLocation = ASD_PATH . 'client/' . $client . '/components/' . $pComponent . '/' . $pView . '.js';

					if ( !file_exists ( $clientLocation ) ) continue;

					$path = $clientPath;
					$location = $clientLocation;
				}

				if ( !file_exists ( $location ) ) continue;
				$script = '<script type="text/javascript" src="' . $path . '"></script>';
				$queue['component'][$c]->Buffer .= "\n" . $script;
			}
		}

		$this->GetSys ( 'Buffer' )->Set ( 'Queue', $queue );

		return ( true );
	}

	public function Buffer ( $pComponent, $pController = null, $pView = null, $pTask = null, $pData = null ) {
		
		$this->_AddScript ( $pComponent, $pController, $pView, $pTask );
		
		ob_start ();
		
		$this->Go ( $pComponent, $pController, $pView, $pTask, $pData );
		
		$return = ob_get_clean ();
		
		return ( $return );
	}
	
	public function Talk ( $pComponent, $pRequest, $pData = null ) {
		
		$component = ucwords ( strtolower ( ltrim ( rtrim ( $pComponent ) ) ) );
		$function = ltrim ( rtrim ( $pRequest ) );
		
		if ( !isset ( $this->$component ) ) return ( false );
		
		$methods = get_class_methods ( $this->$component );
		
		$this->$component->Set ( 'Source', 'Component' );
		
		if ( !$methods ) return ( false );
		
		if ( in_array ( $function, $methods) ) {
			return ( $this->$component->$function ( $pData ) );
		}
		
		return ( false );
	}

}
