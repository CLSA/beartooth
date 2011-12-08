<?php
/**
 * self_shortcuts.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * widget self shortcuts
 * 
 * @package beartooth\ui
 */
class self_shortcuts extends \cenozo\ui\widget\self_shortcuts
{
  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    $voip_enabled = lib::create( 'business\setting_manager' )->get_setting( 'voip', 'enabled' );
    
    // get the xor key and make sure it is at least as long as the password
    $xor_key = lib::create( 'business\setting_manager' )->get_setting( 'voip', 'xor_key' );
    $password = $_SERVER['PHP_AUTH_PW'];

    // avoid infinite loops by using a counter
    $counter = 0;
    while( strlen( $xor_key ) < strlen( $password ) )
    {
      $xor_key .= $xor_key;
      if( 1000 < $counter++ ) break;
    }
    
    $this->set_variable( 'webphone_parameters', sprintf(
      'username=%s&password=%s',
      $_SERVER['PHP_AUTH_USER'],
      base64_encode( $password ^ $xor_key ) ) );
    $this->set_variable( 'webphone',
      $voip_enabled && !lib::create( 'business\voip_manager' )->get_sip_enabled() );
    $this->set_variable( 'dialpad', !is_null( lib::create( 'business\voip_manager' )->get_call() ) );
    $this->set_variable( 'timezone_calculator', true );
  }
}
?>
