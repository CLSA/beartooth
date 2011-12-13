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
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $session = lib::create( 'business\session' );

    $db_participant = lib::create( 'database\participant', $this->get_argument( 'participant_id' ) );
    
    // make sure the qnaire has phases
    $db_qnaire = lib::create( 'database\qnaire', $db_participant->current_qnaire_id );
    if( 0 == $db_qnaire->get_phase_count() )
      throw lib::create( 'exception\notice',
        'This participant\'s next questionnaire is not yet ready.  '.
        'Please immediately report this problem to a superior.',
        __METHOD__ );
    
    // get this participant's interview or create a new one if none exists yet
    $interview_mod = lib::create( 'database\modifier' );
    $interview_mod->where( 'participant_id', '=', $db_participant->id );
    $interview_mod->where( 'qnaire_id', '=', $db_participant->current_qnaire_id );

    $class_name = lib::get_class_name( 'database\interview' );
    $db_interview_list = $class_name::select( $interview_mod );
    
    if( 0 == count( $db_interview_list ) )
    {
      $db_interview = lib::create( 'database\interview' );
      $db_interview->participant_id = $db_participant->id;
      $db_interview->qnaire_id = $db_participant->current_qnaire_id;
      $db_interview->save();
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
    $db_assignment->queue_id = $this->get_argument( 'queue_id', NULL );
    $db_assignment->save();
  }
}
?>
