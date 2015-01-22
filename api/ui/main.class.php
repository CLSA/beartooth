<?php
/**
 * main.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Class that manages variables in main user interface template.
 */
class main extends \cenozo\ui\main
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public static function get_variables()
  {
    $session = lib::create( 'business\session' );
    $survey_manager = lib::create( 'business\survey_manager' );
    $setting_manager = lib::create( 'business\setting_manager' );
    $voip_manager = lib::create( 'business\voip_manager' );
    $db_site = $session->get_site();
    
    $variables = parent::get_variables();
    $variables['survey_url'] = $survey_manager->get_survey_url();
    $variables['show_menu'] =
      is_null( $session->get_current_assignment() ) && false == $variables['survey_url'];

    $voip_enabled = $setting_manager->get_setting( 'voip', 'enabled' );
    
    // get the xor key and make sure it is at least as long as the password
    $xor_key = $setting_manager->get_setting( 'voip', 'xor_key' );
    $password = $_SERVER['PHP_AUTH_PW'];

    // avoid infinite loops by using a counter
    $counter = 0;
    while( strlen( $xor_key ) < strlen( $password ) )
    {
      $xor_key .= $xor_key;
      if( 1000 < $counter++ ) break;
    }
    
    $variables['webphone_parameters'] = sprintf(
      'username=%s&password=%s',
      $_SERVER['PHP_AUTH_USER'],
      base64_encode( $password ^ $xor_key ) );
    $variables['webphone'] = $voip_enabled && !$voip_manager->get_sip_enabled();
    $variables['dialpad'] = !is_null( $voip_manager->get_call() );
    $variables['calculator'] = true;
    $variables['timezone_calculator'] = true;
    $variables['navigation'] = is_null( $session->get_current_assignment() );

    return $variables;
  }
}
