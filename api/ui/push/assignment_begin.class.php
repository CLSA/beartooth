<?php
/**
 * assignment_begin.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: assignment begin
 *
 * Assigns a participant to the user.
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

    // we can't use a transaction, otherwise the semaphores in the queue record won't work
    lib::create( 'business\session' )->set_use_transaction( false );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $participant_id = $this->get_argument( 'participant_id' );
    $this->db_participant = lib::create( 'database\participant', $participant_id );
  }

  /**
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    if( !is_null( lib::create( 'business\session' )->get_current_assignment() ) )
      throw lib::create( 'exception\notice',
        'Please click the refresh button.  If this message appears more than twice '.
        'consecutively report this error to a superior.', __METHOD__ );

    $db_effective_qnaire = $this->db_participant->get_effective_qnaire();
    if( is_null( $db_effective_qnaire ) )
      throw lib::create( 'exception\runtime',
        sprintf( 'Trying to assign participant %s who has already completed all qnaires.',
                 $this->db_participant->uid ),
        __METHOD__ );

    // make sure the qnaire has phases
    if( 0 == $db_effective_qnaire->get_phase_count() )
      throw lib::create( 'exception\notice',
        'This participant\'s next questionnaire is not yet ready.  '.
        'Please immediately report this problem to a superior.',
        __METHOD__ );

    // make sure the participant isn't already assigned
    if( !is_null( $this->db_participant->get_current_assignment() ) )
      throw lib::create( 'exception\notice',
        'The participant is already assigned, please refresh the assignment list and try again.',
        __METHOD__ );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $interview_class_name = lib::get_class_name( 'database\interview' );
    $session = lib::create( 'business\session' );

    // get this participant's interview or create a new one if none exists yet
    $db_effective_qnaire = $this->db_participant->get_effective_qnaire();
    $db_interview = $interview_class_name::get_unique_record(
      array( 'qnaire_id', 'participant_id' ),
      array( $db_effective_qnaire->id, $this->db_participant->id ) );

    if( is_null( $db_interview ) )
    {
      $db_interview = lib::create( 'database\interview' );
      $db_interview->participant_id = $this->db_participant->id;
      $db_interview->qnaire_id = $db_effective_qnaire->id;

      // Even though we have made sure this interview isn't a duplicate, it seems to happen from
      // time to time anyway, so catch it and tell the user to try requesting the assignment again
      try
      {
        $db_interview->save();
      }
      catch( \cenozo\exception\database $e )
      {
        if( $e->is_duplicate_entry() )
        {
          throw lib::create( 'exception\notice',
            'The server was too busy to begin your assignment, please wait a few seconds then '.
            'try again.  If this message appears several times in a row please report the error '.
            'code to your superior.',
            __METHOD__ );
        }

        throw $e;
      }
    }

    // create an assignment for this user
    $db_assignment = lib::create( 'database\assignment' );
    $db_assignment->user_id = $session->get_user()->id;
    $db_assignment->site_id = $session->get_site()->id;
    $db_assignment->interview_id = $db_interview->id;
    $db_assignment->save();

    // if the participant has an unassigned callback then set the callback's assignment
    $callback_mod = lib::create( 'database\modifier' );
    $callback_mod->where( 'assignment_id', '=', NULL );
    $callback_mod->order( 'datetime' );
    $callback_mod->limit( 1 );
    $db_callback = current( $this->db_participant->get_callback_list( $callback_mod ) );
    if( $db_callback )
    {
      $db_callback->assignment_id = $db_assignment->id;
      $db_callback->save();
    }

    // update this participant's queue status
    $this->db_participant->update_queue_status();
  }

  /**
   * The participant to assign
   * @var database\participant $db_participant
   * @access protected
   */
  protected $db_participant = NULL;
}
