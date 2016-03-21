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
class interview extends \cenozo\database\record
{
  /**
   * Get the interview's last (most recent) assignment.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return assignment
   * @access public
   */
  public function get_last_assignment()
  {
    // check the last key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query interview with no primary key.' );
      return NULL;
    }

    $select = lib::create( 'database\select' );
    $select->from( 'interview_last_assignment' );
    $select->add_column( 'assignment_id' );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview_id', '=', $this->id );

    $assignment_id = static::db()->get_one( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );
    return $assignment_id ? lib::create( 'database\assignment', $assignment_id ) : NULL;
  }

  /**
   * Performes all necessary steps when completing an interview.
   * 
   * This method encapsulates all processing required when an interview is completed.
   * If you wish to "force" the completion or uncompletion of an interview please use
   * the force_complete() and force_uncomplete() methods intead.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\site $db_credit_site If null then the session's site is credited
   * @access public
   */
  public function complete( $db_credit_site = NULL )
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to complete interview with no primary key.' );
      return;
    }

    if( !is_null( $this->end_datetime ) )
    {
      log::warning( sprintf( 'Tried to complete interview id %d which already has an end_datetime.', $this->id ) );
    }
    else
    {
      $now = util::get_datetime_object();
      if( is_null( $db_credit_site ) ) $db_credit_site = lib::create( 'business\session' )->get_site();

      // update the record
      $this->end_datetime = $now;
      $this->site_id = $db_credit_site->id;
      $this->save();
    }
  }

  /**
   * Forces an interview to become completed.
   * 
   * This method will update an interview's status to be complete.  It will also update the
   * correspinding limesurvey data to be set as complete.  This action cannot be undone.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function force_complete()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to force complete interview with no primary key.' );
      return;
    }

    // do nothing if the interview is already set as completed
    if( !is_null( $this->end_datetime ) ) return;

    // there are no additional operations involved in completing an interview
    $this->complete();
  }

  /**
   * Forces an interview to become incomplete.
   * 
   * This method will update an interview's status to be incomplete.  It will also delete the
   * correspinding limesurvey data.  This action cannot be undone.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function force_uncomplete()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to force uncomplete interview with no primary key.' );
      return;
    }

    // update the record (nothing else is required)
    $this->end_datetime = NULL;
    $this->site_id = NULL;
    $this->save();
  }
}
