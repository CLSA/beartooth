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
    
    return LIMESURVEY_URL.sprintf( '/index.php?sid=%s&lang=%s&token=%s', $sid, $lang, $token );
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
    $session = lib::create( 'business\session' );
    $db_assignment = $session->get_current_assignment();
    if( is_null( $db_assignment ) )
    {
      log::warning( 'Tried to determine survey information without an active assignment.' );
      return false;
    }
    
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );

    $this->current_sid = false;
    $this->current_token = false;

    // records which we will need
    $db_interview = $db_assignment->get_interview();
    $db_participant = $db_interview->get_participant();
    $db_consent = $db_participant->get_last_consent();
    
    if( $db_consent && 'withdraw' == $db_consent->event )
    { // the participant has withdrawn, check to see if the withdraw script is complete
      $db_qnaire = $db_interview->get_qnaire();
      
      // let the tokens record class know which SID we are dealing with
      $tokens_class_name::set_sid( $db_qnaire->withdraw_sid );

      $token = $tokens_class_name::determine_token_string( $db_interview );
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
?>
