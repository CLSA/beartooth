<?php
/**
 * survey_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\business;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * survey_manager: record
 */
class survey_manager extends \cenozo\singleton
{
  /**
   * Constructor.
   * 
   * Since this class uses the singleton pattern the constructor is never called directly.  Instead
   * use the {@link singleton} method.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function __construct() {}

  /**
   * Gets the current survey URL.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string (or false if the survey is not active)
   * @access public
   */
  public function get_survey_url()
  {
    $session = lib::create( 'business\session' );

    // determine the participant
    $db_participant = NULL;
    if( array_key_exists( 'secondary_id', $_COOKIE ) )
    {
      $db_participant = lib::create( 'database\participant', $_COOKIE['secondary_participant_id'] );
    }
    else if( array_key_exists( 'withdrawing_participant', $_COOKIE ) )
    {
      $db_participant = lib::create( 'database\participant', $_COOKIE['withdrawing_participant'] );
    }
    else
    {
      // must have an assignment
      $db_assignment = $session->get_current_assignment();
      if( !is_null( $db_assignment ) )
      {
        // the assignment must have an open call
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'end_datetime', '=', NULL );
        $call_list = $db_assignment->get_phone_call_list( $modifier );
        if( 0 != count( $call_list ) )
          $db_participant = $db_assignment->get_interview()->get_participant();
      }
    }

    if( !is_null( $db_participant ) )
    {
      $sid = $this->get_current_sid();
      $token = $this->get_current_token();
      if( false !== $sid && false != $token )
      {
        // determine which language to use
        $db_language = $db_participant->get_language();
        if( is_null( $db_language ) ) $db_language = $session->get_service()->get_language();
        return sprintf( '%s/index.php?sid=%s&lang=%s&token=%s&newtest=Y',
                        LIMESURVEY_URL,
                        $sid,
                        $db_language->code,
                        $token );
      }
    }

    // there is currently no active survey
    return false;
  }

  /**
   * This method returns the current SID, or false if all surveys are complete.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_current_sid()
  {
    if( is_null( $this->current_sid ) ) $this->determine_current_sid_and_token();
    return $this->current_sid;
  }

  /**
   * This method returns the current token, or false if all surveys are complete.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_current_token()
  {
    if( is_null( $this->current_token ) ) $this->determine_current_sid_and_token();
    return $this->current_token;
  }

  /**
   * Determines the current SID and token.
   * 
   * This method will first determine whether the participant needs to complete the withdraw
   * script or a questionnaire.  It then determines whether the appropriate script has been
   * completed or not.
   * Note: This method will create tokens in the limesurvey database as necessary.
   * This is also where interviews are marked as complete once all phases are finished.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function determine_current_sid_and_token()
  {
    $this->current_sid = false;
    $this->current_token = false;

    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
    $session = lib::create( 'business\session' );

    if( array_key_exists( 'secondary_id', $_COOKIE ) )
    {
      // get the participant being sourced
      $db_participant = lib::create( 'database\participant', $_COOKIE['secondary_participant_id'] );
      if( is_null( $db_participant ) )
      {
        log::warning( 'Tried to determine survey information for an invalid participant.' );
        return false;
      }

      $setting_manager = lib::create( 'business\setting_manager' );
      $sid = $setting_manager->get_setting( 'general', 'secondary_survey' );
      $token = $_COOKIE['secondary_id'];

      $tokens_class_name::set_sid( $sid );
      $survey_class_name::set_sid( $sid );

      // reset the script and token
      $tokens_mod = lib::create( 'database\modifier' );
      $tokens_mod->where( 'token', '=', $token );
      foreach( $tokens_class_name::select( $tokens_mod ) as $db_tokens ) $db_tokens->delete();
      $scripts_mod = lib::create( 'database\modifier' );
      $scripts_mod->where( 'token', '=', $token );
      foreach( $survey_class_name::select( $scripts_mod ) as $db_survey ) $db_survey->delete();

      $db_tokens = lib::create( 'database\limesurvey\tokens' );
      $db_tokens->token = $token;
      $db_tokens->firstname = $db_participant->first_name;
      $db_tokens->lastname = $db_participant->last_name;
      $db_tokens->email = $db_participant->email;

      if( 0 < strlen( $db_participant->other_name ) )
        $db_tokens->firstname .= sprintf( ' (%s)', $db_participant->other_name );

      // fill in the attributes
      $db_surveys = lib::create( 'database\limesurvey\surveys', $sid );
      foreach( $db_surveys->get_token_attribute_names() as $key => $value )
        $db_tokens->$key = static::get_attribute( $db_participant, $value );

      $db_tokens->save();

      // the secondary survey can be brought back up after it is complete, so always set these
      $this->current_sid = $sid;
      $this->current_token = $token;
    }
    else if( array_key_exists( 'withdrawing_participant', $_COOKIE ) )
    {
      // get the participant being withdrawn
      $db_participant = lib::create( 'database\participant', $_COOKIE['withdrawing_participant'] );
      if( is_null( $db_participant ) )
      {
        log::warning( 'Tried to determine survey information for an invalid participant.' );
        return false;
      }

      $this->process_withdraw( $db_participant );
    }
    else
    {
      $db_assignment = $session->get_current_assignment();
      if( is_null( $db_assignment ) )
      {
        log::warning( 'Tried to determine survey information without an active assignment.' );
        return false;
      }

      // records which we will need
      $db_interview = $db_assignment->get_interview();
      $db_participant = $db_interview->get_participant();
      $db_consent = $db_participant->get_last_consent();

      if( $db_consent && false == $db_consent->accept )
      { // the participant has withdrawn, check to see if the withdraw script is complete
        $this->process_withdraw( $db_participant );
      }
      else
      { // the participant has not withdrawn, check each phase of the interview
        $phase_mod = lib::create( 'database\modifier' );
        $phase_mod->order( 'rank' );

        $phase_list = $db_interview->get_qnaire()->get_phase_list( $phase_mod );
        if( 0 == count( $phase_list ) )
        {
          log::emerg( 'Questionnaire with no phases has been assigned.' );
        }
        else
        {
          foreach( $phase_list as $db_phase )
          {
            // let the tokens record class know which SID we are dealing with
            $tokens_class_name::set_sid( $db_phase->sid );

            $token = $tokens_class_name::determine_token_string(
                       $db_interview,
                       $db_phase->repeated ? $db_assignment : NULL );
            $tokens_mod = lib::create( 'database\modifier' );
            $tokens_mod->where( 'token', '=', $token );
            $db_tokens = current( $tokens_class_name::select( $tokens_mod ) );

            if( false === $db_tokens )
            { // token not found, create it
              $db_tokens = lib::create( 'database\limesurvey\tokens' );
              $db_tokens->token = $token;
              $db_tokens->firstname = $db_participant->first_name;
              $db_tokens->lastname = $db_participant->last_name;
              $db_tokens->email = $db_participant->email;

              if( 0 < strlen( $db_participant->other_name ) )
                $db_tokens->firstname .= sprintf( ' (%s)', $db_participant->other_name );

              // fill in the attributes
              $db_surveys = lib::create( 'database\limesurvey\surveys', $db_phase->sid );
              foreach( $db_surveys->get_token_attribute_names() as $key => $value )
                $db_tokens->$key = static::get_attribute( $db_participant, $value );

              // TODO: this is temporary code to fix the TOKEN != "NO" problem in limesurvey
              //       for survey 63834
              if( 63834 == $db_phase->sid && is_null( $db_tokens->attribute_9 ) )
                $db_tokens->attribute_9 = "UNKNOWN";

              $db_tokens->save();

              $this->current_sid = $db_phase->sid;
              $this->current_token = $token;
              break;
            }
            else if( 'N' == $db_tokens->completed )
            { // we have found the current phase
              $this->current_sid = $db_phase->sid;
              $this->current_token = $token;
              break;
            }
            // else do not set the current_sid or current_token
          }
        }

        // The interview is not completed here since the interview must be completed by Onyx
        // and Onyx must report back when it is done.
      }
    }
  }

  /**
   * Internal method to handle the withdraw script
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\participant $db_participant
   * @access private
   */
  private function process_withdraw( $db_participant )
  {
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $withdraw_manager = lib::create( 'business\withdraw_manager' );

    $withdraw_sid = $withdraw_manager->get_withdraw_sid( $db_participant );
    if( is_null( $withdraw_sid ) )
      throw lib::create( 'exception\runtime',
        sprintf( 'Trying to process withdraw for participant %s without a questionnaire.',
                 $db_participant->uid ),
        __METHOD__ );

    $db_surveys = lib::create( 'database\limesurvey\surveys', $withdraw_sid );

    // get the withdraw token
    $tokens_class_name::set_sid( $withdraw_sid );
    $token = $db_participant->uid;
    $tokens_mod = lib::create( 'database\modifier' );
    $tokens_mod->where( 'token', '=', $token );
    $db_tokens = current( $tokens_class_name::select( $tokens_mod ) );

    if( false === $db_tokens )
    { // token not found, create it
      $db_tokens = lib::create( 'database\limesurvey\tokens' );
      $db_tokens->token = $token;
      $db_tokens->firstname = $db_participant->first_name;
      $db_tokens->lastname = $db_participant->last_name;
      $db_tokens->email = $db_participant->email;

      if( 0 < strlen( $db_participant->other_name ) )
        $db_tokens->firstname .= sprintf( ' (%s)', $db_participant->other_name );

      // fill in the attributes
      foreach( $db_surveys->get_token_attribute_names() as $key => $value )
        $db_tokens->$key = static::get_attribute( $db_participant, $value );

      $db_tokens->save();

      $this->current_sid = $withdraw_sid;
      $this->current_token = $token;
    }
    else if( 'N' == $db_tokens->completed )
    {
      $this->current_sid = $withdraw_sid;
      $this->current_token = $token;
    }
    else // token is complete, store the survey results
    {
      $withdraw_manager->process( $db_participant );
    }
  }

  /**
   * Determines attributes needed at survey time.
   * TODO: this method contains many reference to CLSA-specific features which
   *       should be made generic
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\participant $db_participant
   * @param string $key The name of the attribute to return.
   * @return mixed
   * @access public
   */
  public static function get_attribute( $db_participant, $key )
  {
    $value = NULL;

    if( false !== strpos( $key, '.' ) )
    { // key contains a '.', use new style attribute
      $data_manager = lib::create( 'business\data_manager' );
      $value = 0 === strpos( $key, 'participant\.' )
             ? $data_manager->get_participant_value( $db_participant, $key )
             : $data_manager->get_value( $key );
    }
    else if( 'provided data' == $key )
    {
      $event_type_class_name = lib::get_class_name( 'database\event_type' );

      // participants have provided data once their first interview is done
      $event_mod = lib::create( 'database\modifier' );
      $event_mod->where( 'event_type_id', '=',
        $event_type_class_name::get_unique_record( 'name', 'completed (Baseline Home)' )->id );

      $event_list = $db_participant->get_event_list( $event_mod );
      $provided_data = 0 < count( $event_list ) ? 'yes' : 'no';

      $value = $provided_data;
    }
    else if( 'DCS samples' == $key )
    {
      // get data from Opal
      $setting_manager = lib::create( 'business\setting_manager' );
      $opal_url = $setting_manager->get_setting( 'opal', 'server' );
      $opal_manager = lib::create( 'business\opal_manager', $opal_url );

      $value = 0;

      if( $opal_manager->get_enabled() )
      {
        try
        {
          $blood = $opal_manager->get_value(
            'clsa-dcs', 'Phlebotomy', $db_participant, 'AGREE_BS' );
          $urine = $opal_manager->get_value(
            'clsa-dcs', 'Phlebotomy', $db_participant, 'AGREE_URINE' );

          $value = 0 == strcasecmp( 'yes', $blood ) ||
                        0 == strcasecmp( 'yes', $urine )
                      ? 1 : 0;
        }
        catch( \cenozo\exception\base_exception $e )
        {
          // ignore argument exceptions (data not found in Opal) and report the rest
          if( 'argument' != $e->get_type() ) log::warning( $e->get_message() );
        }
      }
    }

    return $value;
  }

  /**
   * This assignment's current sid
   * @var int
   * @access private
   */
  private $current_sid = NULL;

  /**
   * This assignment's current token
   * @var string
   * @access private
   */
  private $current_token = NULL;
}
