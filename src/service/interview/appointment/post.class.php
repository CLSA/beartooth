<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\interview\appointment;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Special service for handling the post meta-resource
 */
class post extends \cenozo\service\post
{
  /**
   * Override parent method
   */
  protected function prepare()
  {
    $this->extract_parameter_list[] = 'disable_mail';

    parent::prepare();
  }

  /**
   * Override parent method
   */
  protected function execute()
  {
    $consent_type_class_name = lib::get_class_name( 'database\consent_type' );
    $setting_manager = lib::create( 'business\setting_manager' );
    $session = lib::create( 'business\session' );
    $db_appointment = $this->get_leaf_record();

    // Add participant to CANTAB, if the following conditions are met:
    // 1) The CANTAB manager is enabled
    // 2) We're booking a DCS interview
    // 3) The CANTAB consent type found in the settings is valid
    // 4) The participant has accepted the consent type
    try
    {
      $cantab_manager = lib::create( 'business\cantab_manager' );
      if( $cantab_manager->get_enabled() )
      {
        $db_interview = $db_appointment->get_interview();
        if( 'site' == $db_interview->get_qnaire()->type )
        {
          $db_consent_type = $consent_type_class_name::get_unique_record(
            'name', $setting_manager->get_setting( 'cantab', 'consent_type_name' )
          );

          if( !is_null( $db_consent_type ) )
          {
            $db_participant = $db_interview->get_participant();
            $db_consent = $db_participant->get_last_consent( $db_consent_type );
            if( !is_null( $db_consent ) && $db_consent->accept )
            {
              $cantab_manager->add_participant( $db_participant );
            }
          }
        }
      }
    }
    catch( \cenozo\exception\runtime $e )
    {
      throw lib::create( 'exception\notice', $e->get_raw_message(), __METHOD__, $e );
    }

    // now create the appointment
    parent::execute();

    // create appointment mail
    if( !$this->get_argument( 'disable_mail', false ) ) $db_appointment->add_mail();
  }
}
