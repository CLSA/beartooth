<?php
/**
 * interview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\database
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * interview: record
 *
 * @package beartooth\database
 */
class interview extends \cenozo\database\has_note
{
  /**
   * Extend the select() method by adding a custom join to the participant_site table.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @static
   * @access public
   */
  public static function select( $modifier = NULL, $count = false )
  {
    $participant_site_mod = lib::create( 'database\modifier' );
    $participant_site_mod->where(
      'appointment.participant_id', '=', 'participant_site.participant_id', false );
    static::customize_join( 'participant_site', $participant_site_mod );

    return parent::select( $modifier, $count );
  }

  
  /**
   * Returns the time in seconds that it took to complete a particular phase of this interview
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\phase $db_phase Which phase of the interview to get the time of.
   * @param database\assignment $db_assignment Repeated phases have their times measured for each
   *                      iteration of the phase.  For repeated phases this determines which
   *                      assignment's time to return.  It is ignored for phases which are not
   *                      repeated.
   * @return float
   * @access public
   */
  public function get_interview_time( $db_phase, $db_assignment = NULL )
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine interview time for interview with no id.' );
      return 0.0;
    }
    
    if( is_null( $db_phase ) )
    {
      log::warning( 'Tried to determine interview time for null phase.' );
      return 0.0;
    }

    if( $db_phase->repeated && is_null( $db_assignment ) )
    {
      log::warning(
        'Tried to determine interview time for repeating phase without an assignment.' );
      return 0.0;
    }

    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $survey_timings_class_name = lib::get_class_name( 'database\limesurvey\survey_timings' );
    
    $survey_class_name::set_sid( $db_phase->sid );
    $survey_mod = lib::create( 'database\modifier' );
    $survey_mod->where( 'token', '=',
      $tokens_class_name::determine_token_string( $this, $db_assignment ) );
    $survey_list = $survey_class_name::select( $survey_mod );

    if( 0 == count( $survey_list ) ) return 0.0;

    if( 1 < count( $survey_list ) ) log::alert( sprintf(
      'There are %d surveys using the same token (%s)! for SID %d',
      count( $survey_list ),
      $token,
      $db_phase->sid ) );

    $db_survey = current( $survey_list );

    $survey_timings_class_name::set_sid( $db_phase->sid );
    $timing_mod = lib::create( 'database\modifier' );
    $timing_mod->where( 'id', '=', $db_survey->id );
    $db_timings = current( $survey_timings_class_name::select( $timing_mod ) );
    return $db_timings ? (float) $db_timings->interviewtime : 0.0;
  }
}
?>
