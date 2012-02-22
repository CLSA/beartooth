<?php
/**
 * assignment_begin.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: assignment begin
 *
 * Assigns a participant to an assignment.
 * @package beartooth\ui
 */
class assignment_begin extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', 'begin', $args );

    $participant_id = $this->get_argument( 'participant_id', false );
    if( $participant_id )
    {
      $this->db_participant = lib::create( 'database\participant', $participant_id );
    }
    else
    {
      // we can't use a transaction, otherwise the semaphore in the finish() method won't work
      lib::create( 'business\session' )->set_use_transaction( false );
    }
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $interview_class_name = lib::get_class_name( 'database\interview' );

    $session = lib::create( 'business\session' );
    $setting_manager = lib::create( 'business\setting_manager' );
    $db_origin_queue = NULL;

    if( !is_null( $session->get_current_assignment() ) )
      throw lib::create( 'exception\notice',
        'Please click the refresh button.  If this message appears more than twice '.
        'consecutively report this error to a superior.', __METHOD__ );

    $semaphore = NULL;

    // if there is no participant then get one from the queue
    if( is_null( $this->db_participant ) )
    {
      // we need to use a semaphore to avoid race conditions
      $semaphore = sem_get( getmyinode() );
      if( !sem_acquire( $semaphore ) )
      {
        log::err( sprintf(
          'Unable to aquire semaphore for user id %d',
          $session->get_user()->id ) );
        throw lib::create( 'exception\notice',
          'The server is busy, please wait a few seconds then click the refresh button.',
          __METHOD__ );
      }

      // Search through every queue for a new assignment until one is found.
      // This search has to be done one qnaire at a time, with interviewers only searching in
      // queues who's qnaire is a home interview and everyone else for site interviews.
      $qnaire_mod = lib::create( 'database\modifier' );
      $qnaire_mod->where(
        'type', '=', 'interviewer' == $session->get_role()->name ? 'home' : 'site' );
      $qnaire_mod->order( 'rank' );

      $queue_mod = lib::create( 'database\modifier' );
      $queue_mod->where( 'rank', '!=', NULL );
      $queue_mod->order( 'rank' );

      foreach( $qnaire_class_name::select( $qnaire_mod ) as $db_qnaire )
      {
        foreach( $queue_class_name::select( $queue_mod ) as $db_queue )
        {
          if( $setting_manager->get_setting( 'queue state', $db_queue->name ) )
          {
            $participant_mod = lib::create( 'database\modifier' );
            $participant_mod->limit( 1 );
            $db_queue->set_site( $session->get_site() );
            $db_queue->set_qnaire( $db_qnaire );
            $participant_list = $db_queue->get_participant_list( $participant_mod );
            if( 1 == count( $participant_list ) )
            {
              $db_origin_queue = $db_queue;
              $this->db_participant = current( $participant_list );
            }
          }

          // stop looping queues if we found a participant
          if( !is_null( $this->db_participant ) ) break;
        }
        // stop looping qnaires if we found a participant
        if( !is_null( $this->db_participant ) ) break;
      }

      // if we didn't find a participant then let the user know none are available
      if( is_null( $this->db_participant ) )
        throw lib::create( 'exception\notice',
          'There are no participants currently available.', __METHOD__ );
    }

    // make sure the qnaire has phases
    $db_qnaire = lib::create( 'database\qnaire', $this->db_participant->current_qnaire_id );
    if( 0 == $db_qnaire->get_phase_count() )
      throw lib::create( 'exception\notice',
        'This participant\'s next questionnaire is not yet ready.  '.
        'Please immediately report this problem to a superior.',
        __METHOD__ );
    
    // get this participant's interview or create a new one if none exists yet
    $interview_mod = lib::create( 'database\modifier' );
    $interview_mod->where( 'participant_id', '=', $this->db_participant->id );
    $interview_mod->where( 'qnaire_id', '=', $this->db_participant->current_qnaire_id );

    $db_interview_list = $interview_class_name::select( $interview_mod );
    
    if( 0 == count( $db_interview_list ) )
    {
      $db_interview = lib::create( 'database\interview' );
      $db_interview->participant_id = $this->db_participant->id;
      $db_interview->qnaire_id = $this->db_participant->current_qnaire_id;

      // Even though we have made sure this interview isn't a duplicate, it seems to happen from
      // time to time anyway, so catch it and tell the operator to try requesting the assignment
      // again
      try
      {
        $db_interview->save();
      }
      catch( \cenozo\exception\database $e )
      {
        if( $e->is_duplicate_entry() )
        {
          throw lib::create( 'exception\notice',
            'The server was too busy to assign a new participant, please wait a few seconds then '.
            'try requesting an assignment again.  If this message appears several times in a row '.
            'please report the error code to your superior.',
            __METHOD__ );
        }

        throw $e;
      }
    }
    else
    {
      $db_interview = $db_interview_list[0];
    }

    // create an assignment for this user
    $db_assignment = lib::create( 'database\assignment' );
    $db_assignment->user_id = $session->get_user()->id;
    $db_assignment->site_id = $session->get_site()->id;
    $db_assignment->interview_id = $db_interview->id;
    $db_assignment->queue_id = is_null( $db_origin_queue ) ? NULL : $db_origin_queue->id;
    $db_assignment->save();

    // release the semaphore, if there is one
    if( !is_null( $semaphore ) )
    {
      if( !sem_release( $semaphore ) )
      {
        log::err( sprintf(
          'Unable to release semaphore for user id %d',
          $session->get_user()->id ) );
      }
    }
  }

  /**
   * The participant to assign (may be specified in the constructor from input arguments)
   * @var database\participant $db_participant
   * @access protected
   */
  protected $db_participant = NULL;
}
?>
