<?php
  // +-------------------------------------------------------------------+
  // | Appleseed Web Community Management Software                       |
  // | http://appleseed.sourceforge.net                                  |
  // +-------------------------------------------------------------------+
  // | FILE: login.php                               CREATED: 01-01-2005 + 
  // | LOCATION: /code/site/                        MODIFIED: 04-11-2007 +
  // +-------------------------------------------------------------------+
  // | Copyright (c) 2004-2008 Appleseed Project                         |
  // +-------------------------------------------------------------------+
  // | This program is free software; you can redistribute it and/or     |
  // | modify it under the terms of the GNU General Public License       |
  // | as published by the Free Software Foundation; either version 2    |
  // | of the License, or (at your option) any later version.            |
  // |                                                                   |
  // | This program is distributed in the hope that it will be useful,   |
  // | but WITHOUT ANY WARRANTY; without even the implied warranty of    |
  // | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     |
  // | GNU General Public License for more details.                      |	
  // |                                                                   |
  // | You should have received a copy of the GNU General Public License |
  // | along with this program; if not, write to:                        |
  // |                                                                   |
  // |   The Free Software Foundation, Inc.                              |
  // |   59 Temple Place - Suite 330,                                    | 
  // |   Boston, MA  02111-1307, USA.                                    |
  // |                                                                   |
  // |   http://www.gnu.org/copyleft/gpl.html                            |
  // +-------------------------------------------------------------------+
  // | AUTHORS: Michael Chisari <michael.chisari@gmail.com>              |
  // +-------------------------------------------------------------------+
  // | VERSION:      0.7.9                                               |
  // | DESCRIPTION:  Login code.                                         |
  // +-------------------------------------------------------------------+
  
  eval( GLOBALS ); // Import all global variables  
  
  // Change to document root directory.
  chdir ($_SERVER['DOCUMENT_ROOT']);

  // Include BASE API classes.
  require_once ('legacy/code/include/classes/BASE/application.php'); 
  require_once ('legacy/code/include/classes/BASE/debug.php'); 
  require_once ('legacy/code/include/classes/base.php'); 
  require_once ('legacy/code/include/classes/system.php'); 
  require_once ('legacy/code/include/classes/BASE/remote.php'); 
  require_once ('legacy/code/include/classes/BASE/tags.php'); 
  require_once ("legacy/code/include/classes/BASE/xml.php");

  // Include Appleseed classes.
  require_once ('legacy/code/include/classes/appleseed.php'); 
  require_once ('legacy/code/include/classes/privacy.php'); 
  require_once ('legacy/code/include/classes/friends.php'); 
  require_once ('legacy/code/include/classes/messages.php'); 
  require_once ('legacy/code/include/classes/users.php'); 
  require_once ('legacy/code/include/classes/auth.php'); 
  require_once ('legacy/code/include/classes/search.php'); 
  require_once ('legacy/code/include/classes/asd/0.7.3.php'); 

  // Create the Application class.
  $zOLDAPPLE = new cOLDAPPLESEED ();
  
  // Set Global Variables (Put this at the top of wrapper scripts)
  $zOLDAPPLE->SetGlobals ();

  // Initialize Appleseed.
  $zOLDAPPLE->Initialize("site.login", TRUE);

  global $gLOGINBOX;
  $gLOGINBOX = "login";

  // Initialize tabs
  global $gLOCALLOGINTAB, $gREMOTELOGINTAB;
  $gLOCALLOGINTAB = ""; $gREMOTELOGINTAB = "_off";

  $parameters = explode ('/', $gLOGINREQUEST, 3);
  
  $gLOGINREQUEST = $parameters[0];

  if ($parameters[1]) {
    $reference = $parameters[1];
    $redirect = $parameters[2];
    list ($gREFERENCEUSERNAME, $gREFERENCEDOMAIN) = explode ('@', $reference);
  } // if
  
  switch ($gLOGINREQUEST) {
    case 'check':
      $self = $_SERVER['HTTP_HOST'];
      $check = explode ('@', $reference);
      $username = $check[0];
      $host = $check[1];

      // Check if requesting user is logged in
      if ($zLOCALUSER->Username == $username) {
        // Delete all previous records for this user.
        $zVERIFY = new cAUTHVERIFICATION ();

        // Delete existing records for this user.
        $existingcriteria = array ("Username" => $zLOCALUSER->Username,
                                   "Domain"   => $host);
        $zVERIFY->DeleteByMultiple ($existingcriteria);
   
        // Delete all records older than 24 hours.
        $deletestatement = "DELETE FROM " . $zVERIFY->TableName . " WHERE Stamp <  DATE_ADD(now(),INTERVAL -1 DAY)";
        $zVERIFY->Query ($deletestatement);

        // Set a database marker saying that this user is verified.
        $zVERIFY->Username = $username;
        $zVERIFY->Domain = $host;
        $zVERIFY->Verified = TRUE;
        $zVERIFY->Address = $_SERVER['REMOTE_ADDR'];
        $zVERIFY->Host = $_SERVER['REMOTE_HOST'];
        $zVERIFY->Stamp = SQL_NOW;
        $zVERIFY->Active = TRUE;
        if (!$zVERIFY->Host) $zVERIFY->Host = gethostbyaddr ($zVERIFY->Address);

        $zVERIFY->Add();
      } // if

      // Redirect back to origin.
      $location = 'http://' . $host . '/login/return/' . $username . '@' . $self . '/' . $redirect;
      
      Header ('Location: ' . $location);
      exit;
    break;
    case 'remote':
      // If we're logging in remotely, turn on remote tab.
      $gLOCALLOGINTAB = "_off"; $gREMOTELOGINTAB = "";
      $gLOGINBOX = "remotelogin";
    break;
    case 'return':
      //****** BEGIN    ******/
      
      // Create the Remote class.
      $zREMOTE = new cREMOTE ($gREFERENCEDOMAIN);

      $self = $_SERVER['HTTP_HOST'];
      $self = str_replace ("www.", NULL, $self);
      
      $VERIFY = new cAUTHTOKENS ();
      $token = $VERIFY->LoadToken ($gREFERENCEUSERNAME, $gREFERENCEDOMAIN);
      if (!$token) {
        $token = $VERIFY->CreateToken ($gREFERENCEUSERNAME, $gREFERENCEDOMAIN);
      } // if

      $datalist = array ("gACTION"   => "ASD_LOGIN_CHECK",
                         "gUSERNAME" => $gREFERENCEUSERNAME,
                         "gTOKEN"    => $token,
                         "gVERSION"  => $gAPPLESEEDVERSION,
                         "gDOMAIN"   => $self);
      $zREMOTE->Post ($datalist);

      $zXML->Parse ($zREMOTE->Return);

      $ip_address = $zXML->GetValue ("address", 0);
      $zREMOTEUSER->Username = $zXML->GetValue ("username", 0);
      $zREMOTEUSER->Fullname = $zXML->GetValue ("fullname", 0);
      $zREMOTEUSER->Domain = $zXML->GetValue ("domain", 0);

      //****** END    ******/
      
      if ($zREMOTEUSER->Username) {
        $zREMOTEUSER->Create (FALSE, "gREMOTELOGINSESSION");
      } // if

      if ($redirect) {
      	$location = $gSITEURL . '/' . $redirect;
      } else {
      	$location = $gSITEURL;
      } // if
      Header ('Location: ' . $location);
      exit;
    break;
  } // switch
  
  // Determine which action to take 
  switch ($gACTION) {
    case 'FORGOT':
      $zLOCALUSER->Synchronize ();
      
      if (!$zLOCALUSER->Username) {
        $zLOCALUSER->Message = __( "You must enter a username" );
        $zLOCALUSER->Error = -1;
        break;
      } // if

      // Load the user information for that user.
      $zLOCALUSER->Select ("Username", $zLOCALUSER->Username);

      // Check if any users were found.
      if ($zLOCALUSER->CountResult() === 0) {
        $zLOCALUSER->Message = __( "No Such User" );
        $zLOCALUSER->Error = -1;
      } else {
        $zLOCALUSER->FetchArray();
        if (!$zLOCALUSER->userProfile->ChangePassword ()) {
          $zLOCALUSER->Message = __( "Error Updating Account" );
          $zLOCALUSER->Error = -1;
        } else {
          $zLOCALUSER->Message = __( "New Password Sent" );
        } // if
      } // if

    break;
    case 'LOGIN':

      // Synchronize post variables.
      $zLOCALUSER->Synchronize ();
  
      // Set the context for error reporting.
      $zLOCALUSER->PageContext = 'SITE.LOGIN';
  
      // Sanity check, but don't check for unique data entries.
      $zLOCALUSER->Sanity(SKIP_UNIQUE);

      // Only Authenticate if no error is found.
      if ( ($zLOCALUSER->Error) or ($zLOCALUSER->Username == ANONYMOUS) ) {
        // Go back to same page.
        $gACTION = "";
      } else {
  
        // Authenticate the user login.
        $zLOCALUSER->Authenticate ();

        // Only set cookie if no error is found.
        if ($zLOCALUSER->Error) {
          // Go back to same page.
          $gACTION = "";
        } else {

          // Determine the status of the account logging in.
          switch ($zLOCALUSER->Standing) {
            case '1':
              $zLOCALUSER->Message = __( "Account Is Inactive" );
              $zLOCALUSER->Error = -1;
              $gACTION = "";
            break;
    
            case '2':
              $zLOCALUSER->Message = __( "Account Is Disabled" );
              $zLOCALUSER->Error = -1;
              $gACTION = "";
            break;
    
            default:
              // Attempt to create the session cookie.
              $session_id = $zLOCALUSER->userSession->Create ($gREMEMBER);
    
              // Display an error if cookies can't be set.
              if ($session_id == "") {
                $zLOCALUSER->Message = __( "Cannot Set Cookie" );
                $zLOCALUSER->Error = -1;
                $gACTION = "";
              } else {
   
                // Set the LastLogin to current date/time.
                $zLOCALUSER->userInformation->LastLogin = date ('Y-m-d H:i:s', time());
      
                // Check if the user has logged in before.
                if ($zLOCALUSER->userInformation->FirstLogin == "") {
                  // Set FirstLogin and LastLogin to be the same.
                  $zLOCALUSER->userInformation->FirstLogin = $zLOCALUSER->userInformation->LastLogin;
                  // Add record to the userInformation table.
                  $zLOCALUSER->userInformation->Add();
                } else {
                  // Update the userInformation table.
                  $zLOCALUSER->userInformation->Update();
                } // if
              } // if
            break;
          } // switch
        } // if
      } // if
    break;
    case 'REMOTE_LOGIN':
      
      if (!$zOLDAPPLE->CheckEmail ($gLOCATION) ) {
        $zLOCALUSER->Error = -1;
        $zLOCALUSER->Message = __("An error occurred while logging in");
        $zLOCALUSER->Errorlist['Location'] = __("This is not a valid address");
        break;
      } // if

      $info = explode ('@', $gLOCATION);
      $username = $info[0];
      $domain = $info[1];

      $zNODE = new cSYSTEMNODES();
      if ($errorcode = $zNODE->Blocked ($username, $domain) ) {
        $zLOCALUSER->Error = -1;
        $zLOCALUSER->Message = __($errorcode);
        break;
      } // if
      
      $self = $_SERVER['HTTP_HOST'];
      $self = str_replace ("www.", NULL, $self);

      $location = 'http://' . $domain . '/login/check/' . $username . '@' . $self . $gREDIRECT;

      // Redirect to Site B to check cookie.
      Header ('Location: ' . $location);
      exit;

    break;
  } // switch

  // Set "Remember Me" as on by default.
  if ($zLOCALUSER->Error == 0) $gREMEMBER = 'on';

  global $gJOINLOCATION;
  $gJOINLOCATION = "$gFRAMELOCATION/objects/site/join.aobj";

  // Set the page title.
  $gPAGESUBTITLE = ' - Login';

  // Refresh if successful in or if already logged in.
  if ( ($gACTION == "LOGIN") OR (!$zAUTHUSER->Anonymous) ) {

    if ($gREDIRECT) {
  		global $gREDIRECT;
  		$refreshurl = $gREDIRECT; 
    } else {
    	// Refresh to the user's profile page.
    	$refreshurl = "/profile/" . $zLOCALUSER->Username . "/";
    } // if

    // Create the meta refresh line.
    $bREFRESHLINE = $zHTML->Refresh ($refreshurl);

    // Include the outline frame.
    $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/frames/site/loggedin.afrw", INCLUDE_SECURITY_NONE);

    // Exit fully from applicaton.
    $zOLDAPPLE->End();
  } // if
  
  global $bINVITECODE;
  
  if (  $gSETTINGS['UseInvites'] == YES ) {
    ob_start ();
    
      $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/site/join.invite.aobj", INCLUDE_SECURITY_NONE);
      
    $bINVITECODE = ob_get_clean();
  }

  // Set password to NULL.
  $gPASS = NULL;

  // Include the outline frame.
  $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/frames/site/login.afrw", INCLUDE_SECURITY_NONE);

?>
