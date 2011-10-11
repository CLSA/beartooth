<?php
/**
 * self_shortcuts.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * widget self shortcuts
 * 
 * @package beartooth\ui
 */
class self_shortcuts extends \beartooth\ui\widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'self', 'shortcuts', $args );
    $this->show_heading( false );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    $voip_enabled = bus\setting_manager::self()->get_setting( 'voip', 'enabled' );
    $is_interviewer = 'interviewer' == bus\session::self()->get_role()->name;
    
    // get the xor key and make sure it is at least as long as the password
    $xor_key = bus\setting_manager::self()->get_setting( 'voip', 'xor_key' );
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
      $voip_enabled && !bus\voip_manager::self()->get_sip_enabled() );
    $this->set_variable( 'dialpad', !is_null( bus\voip_manager::self()->get_call() ) );
    $this->set_variable( 'timer',
      $is_interviewer && !is_null( bus\session::self()->get_current_phone_call() ) );
    $this->set_variable( 'calculator', true );
    $this->set_variable( 'timezone_calculator', true );
    $this->set_variable( 'navigation', !$is_interviewer );
    $this->set_variable( 'refresh', true );
    $this->set_variable( 'home', false );
  }
}
?>
