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

    if( array_key_exists( 'secondary_id', $_COOKIE ) )
    {
      // get the participant being sourced
      $db_participant = lib::create( 'database\participant', $_COOKIE['secondary_participant_id'] );
      if( is_null( $db_participant ) ) return false;

      // determine the current sid and token
      $sid = $this->get_current_sid();
      $token = $this->get_current_token();
      if( false === $sid || false == $token ) return false;

      // determine which language to use
      $lang = $db_participant->language;
      if( !$lang ) $lang = 'en';

      return LIMESURVEY_URL.sprintf( '/index.php?sid=%s&lang=%s&token=%s&newtest=Y', $sid, $lang, $token );
    }
    else if( array_key_exists( 'withdrawing_participant', $_COOKIE ) ) 
    {
      // get the participant being withdrawn
      $db_participant = lib::create( 'database\participant', $_COOKIE['withdrawing_participant'] );
      if( is_null( $db_participant ) ) return false;

      // determine the current sid and token
      $sid = $this->get_current_sid();
      $token = $this->get_current_token();
      if( false === $sid || false == $token ) return false;

      // determine which language to use
      $lang = $db_participant->language;
      if( !$lang ) $lang = 'en';

      return LIMESURVEY_URL.sprintf( '/index.php?sid=%s&lang=%s&token=%s&newtest=Y', $sid, $lang, $token );
    }
    else
    {
      // must have an assignment
      $db_assignment = $session->get_current_assignment();
      if( is_null( $db_assignment ) ) return false;
      
      // the assignment must have an open call
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'end_datetime', '=', NULL );
      $call_list = $db_assignment->get_phone_call_list( $modifier );
      if( 0 == count( $call_list ) ) return false;

      // determine the current sid and token
      $sid = $this->get_current_sid();
      $token = $this->get_current_token();
      if( false === $sid || false == $token ) return false;
      
      // determine which language to use
      $lang = $db_assignment->get_interview()->get_participant()->language;
      if( !$lang ) $lang = 'en';
      
      return LIMESURVEY_URL.sprintf( '/index.php?sid=%s&lang=%s&token=%s&newtest=Y', $sid, $lang, $token );
    }

    return false; // will never happen
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
      $db_tokens->update_attributes( $db_participant );
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

      $qnaire_id = $db_participant->current_qnaire_id;
      if( is_null( $qnaire_id ) )
      { // finished all qnaires, find the last one completed
        $db_assignment = $db_participant->get_last_finished_assignment();
        if( !is_null( $db_assignment ) )
        {
          $qnaire_id = $db_assignment->get_interview()->qnaire_id;
        }
        if( is_null( $db_assignment ) )
        { // it is possible that the interview was completed without any assignments
          $interview_mod = lib::create( 'database\modifier' );
          $interview_mod->order_desc( 'id' );
          $interview_list = $db_participant->get_interview_list( $interview_mod );
          
          if( 0 == count( $interview_list ) )
            throw lib::create( 'exception\runtime',
                               'Trying to withdraw participant without a questionnaire.',
                               __METHOD__ );

          $db_interview = current( $interview_list );
          $qnaire_id = $db_interview->qnaire_id;
        }
      }

      $db_qnaire = lib::create( 'database\qnaire', $qnaire_id );
      $sid = $db_qnaire->withdraw_sid;

      $survey_class_name::set_sid( $sid );
      $tokens_class_name::set_sid( $sid );
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
        $db_tokens->update_attributes( $db_participant );
        $db_tokens->save();

        $this->current_sid = $sid;
        $this->current_token = $token;
      }
      else if( 'N' == $db_tokens->completed )
      {
        $this->current_sid = $sid;
        $this->current_token = $token;
      }
      else // token is complete, store the survey results
      {   
        // figure out which token attributes are which
        $db_surveys = lib::create( 'database\limesurvey\surveys', $sid );
        $attributes = array();
        foreach( explode( "\n", $db_surveys->attributedescriptions ) as $attribute )
        {   
          if( 10 < strlen( $attribute ) ) 
          {   
            $key = 'attribute_'.substr( $attribute, 10, strpos( $attribute, '=' ) - 10 );
            $value = substr( $attribute, strpos( $attribute, '=' ) + 1 );
            $attributes[$value] = $db_tokens->$key;
          }   
        }   

        // only worry about participants who have provided data
        if( array_key_exists( 'provided data', $attributes ) &&
            'no' != $attributes['provided data'] )
        {
          if( 0 == $attributes['written consent received'] )
            $letter_type = 0 < $attributes['consented to provide HIN'] ? 'q' : 'r';
          else // written consent was received, write the letter type to the database
          {   
            if( 'partial' == $attributes['provided data'] )
              $letter_type = 0 < $attributes['consented to provide HIN'] ? 's' : 't';
            else // full data received
            {   
              if( 'comprehensive' == $db_participant->get_cohort()->name &&  
                  $attributes['last interview date'] == 'DATE UNKNOWN' ) // in-home only
                $letter_type = 0 < $attributes['consented to provide HIN'] ? 'o' : 'p';
              else // not in-home only
              {   
                // from here we need to know whether default was applied or not
                $survey_mod = lib::create( 'database\modifier' );
                $survey_mod->where( 'token', '=', $token );
                $survey_list = $survey_class_name::select( $survey_mod );
                $db_survey = current( $survey_list );
     
                // get the code for the def and opt responses
                $code = 0 < $attributes['consented to provide HIN'] ? 'HIN' : 'NO_HIN';
                $code .= 0 < $attributes['DCS samples'] ? '_SAMP' : '_NO_SAMP';

                $response = array();
                $response['start'] = $db_survey->get_response( 'WTD_START' );
                $response['def'] = $db_survey->get_response( 'WTD_DEF_'.$code );
                $response['opt'] = $db_survey->get_response( 'WTD_OPT_'.$code );

                // the default option was applied if...
                if( 'REFUSED' == $response['start'] ||
                    'YES' == $response['def'] ||
                    'REFUSED' == $response['def'] ||
                    'REFUSED' == $response['opt'] )
                {   
                  if( 1 == $attributes['DCS samples'] )
                    $letter_type = 0 < $attributes['consented to provide HIN'] ? 'k' : 'm';
                  else
                    $letter_type = 0 < $attributes['consented to provide HIN'] ? 'l' : 'n';
                }   
                else
                {
                  if( 'OPTION1' == $response['opt'] )
                  {
                    if( 1 == $attributes['DCS samples'] )
                      $letter_type = 0 < $attributes['consented to provide HIN'] ? 'a' : 'c';
                    else
                      $letter_type = 0 < $attributes['consented to provide HIN'] ? 'b' : 'd';
                  }
                  else if( 'OPTION2' == $response['opt'] )
                  {
                    if( 1 == $attributes['DCS samples'] )
                      $letter_type = 0 < $attributes['consented to provide HIN'] ? 'e' : 'g';
                    else
                      $letter_type = 0 < $attributes['consented to provide HIN'] ? 'f' : 'h';
                  }
                  else // must be OPTION3
                  {
                    // NOTE: to get option 3 participants must have provided HIN
                    $letter_type = 1 == $attributes['DCS samples'] ? 'i' : 'j';
                  }
                }
              }
            }
          }

          // now write the letter type for future reference
          $db_participant->withdraw_letter = $letter_type;
          $db_participant->save();
        }
      }
      // else do not set the current_sid or current_token members!
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
        $db_qnaire = $db_interview->get_qnaire();

        // let the tokens record class know which SID we are dealing with
        $tokens_class_name::set_sid( $db_qnaire->withdraw_sid );
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
          $db_tokens->update_attributes( $db_participant );
          $db_tokens->save();

          $this->current_sid = $db_qnaire->withdraw_sid;
          $this->current_token = $token;
        }
        else if( 'N' == $db_tokens->completed )
        {
          $this->current_sid = $db_qnaire->withdraw_sid;
          $this->current_token = $token;
        }
        // else do not set the current_sid or current_token members!
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
              $db_tokens->update_attributes( $db_participant );

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
