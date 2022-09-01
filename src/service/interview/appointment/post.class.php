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
  public function get_file_as_array()
  {
    // store non-standard columns into temporary variables
    $post_array = parent::get_file_as_array();

    if( array_key_exists( 'disable_mail', $post_array ) )
    {
      $this->disable_mail = $post_array['disable_mail'];
      unset( $post_array['disable_mail'] );
    }

    return $post_array;
  }

  /**
   * Override parent method
   */
  protected function execute()
  {
    $setting_manager = lib::create( 'business\setting_manager' );
    $session = lib::create( 'business\session' );

    parent::execute();

    // create appointment mail
    $db_appointment = $this->get_leaf_record();
    if( !$this->disable_mail ) $db_appointment->add_mail();

    // Add participant to CANTAB, if the following conditions are met:
    // 1) We're booking a DCS interview
    // 2) The CANTAB consent type found in the settings is valid
    // 3) The application is linked to a study-phase
    // 4) The participant has accepted the consent type
    // 5) The CANTAB manager is enabled
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
        $db_study_phase = $session->get_application()->get_study_phase();
        if( !is_null( $db_study_phase ) && !is_null( $db_consent ) && $db_consent->accept )
        {
          $cantab_manager = lib::create( 'business\cantab', $db_study_phase );
          if( $cantab_manager->get_enabled() )
          {
            $cantab_manager->add_participant( $db_participant );
          }
        }
      }
    }
  }

  /**
   * Caching variable
   */
  protected $disable_mail = false;
}
