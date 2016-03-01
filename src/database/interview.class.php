<?php
/**
 * interview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * interview: record
 */
class interview extends record
{
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

  /**
   * Returns the most recent total number of consecutive failed calls.  A maximum of one
   * failed call per assignment is counted.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_failed_call_count()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to get failed call count for interview with no id.' );
      return;
    }
    
    $assignment_mod = lib::create( 'database\modifier' );
    $assignment_mod->order_desc( 'start_datetime' );
    $assignment_mod->where( 'end_datetime', '!=', NULL );
    $failed_calls = 0;
    foreach( $this->get_assignment_list( $assignment_mod ) as $db_assignment )
    {
      // find the most recently completed phone call
      $phone_call_mod = lib::create( 'database\modifier' );
      $phone_call_mod->order_desc( 'start_datetime' );
      $phone_call_mod->where( 'status', '=', 'contacted' );
      $phone_call_mod->where( 'end_datetime', '!=', NULL );
      if( 0 < $db_assignment->get_phone_call_count( $phone_call_mod ) ) break;
      $failed_calls++;
    }

    return $failed_calls;
  }
  
  /**
   * Creates the interview_failed_call_count temporary table needed by all queues.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   * @static
   */
  public static function create_interview_failed_call_count()
  {
    if( static::$interview_failed_call_count_created ) return;
    static::db()->execute( 'SET @next := @series := @nc := @interview_id := 0' );
    $sql = 'CREATE TEMPORARY TABLE IF NOT EXISTS interview_failed_call_count '.
           static::$interview_failed_call_count_sql;
    static::db()->execute( $sql );
    static::db()->execute(
      'ALTER TABLE interview_failed_call_count '.
      'ADD INDEX dk_interview_id ( interview_id ), '.
      'ADD INDEX dk_total ( total )' );
    static::$interview_failed_call_count_created = true;
  }
  
  /**
   * Whether the interview_failed_call_count temporary table has been created.
   * @var boolean
   * @static
   */
  protected static $interview_failed_call_count_created = false;

  /**
   * A string containing the SQL used to create the interview_failed_call_count data
   * @var string
   * @static
   */
  protected static $interview_failed_call_count_sql = <<<'SQL'
SELECT interview_id, total FROM
(
  SELECT interview_id, series, max( nc ) AS total
  FROM
  (
    SELECT
      @next := IF( interview_id != COALESCE( @interview_id, 0 ) OR status = "contacted", 1, 0 ) AS next,
      @series := COALESCE( @series, 0 ) + IF( @next, 1, 0 ) AS series,
      @nc := IF( @next, IF( status = "contacted", 0, 1 ), @nc + 1 ) AS nc,
      @interview_id := interview_id AS interview_id,
      status
    FROM
    (
      SELECT interview_id, status
      FROM assignment
      JOIN phone_call on assignment.id = phone_call.assignment_id
      WHERE phone_call.end_datetime is not null
      ORDER by interview_id, phone_call.end_datetime
    ) AS t1
  ) AS t2
  GROUP BY interview_id, series ORDER BY interview_id, series DESC
) AS t3
GROUP BY interview_id
SQL;
}
