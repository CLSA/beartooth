<?php
/**
 * assignment_begin.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * push: assignment begin
 *
 * Assigns a participant to an assignment.
 * @package beartooth\ui
 */
class assignment_begin extends \beartooth\ui\push
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
    $session = bus\session::self();

    $db_participant = new db\participant( $this->get_argument( 'participant_id' ) );
    
    // make sure the qnaire has phases
    $db_qnaire = new db\qnaire( $db_participant->current_qnaire_id );
    if( 0 == $db_qnaire->get_phase_count() )
      throw new exc\notice(
        'This participant\'s next questionnaire is not yet ready.  '.
        'Please immediately report this problem to a coordinator.',
        __METHOD__ );
    
    // get this participant's interview or create a new one if none exists yet
    $interview_mod = new db\modifier();
    $interview_mod->where( 'participant_id', '=', $db_participant->id );
    $interview_mod->where( 'qnaire_id', '=', $db_participant->current_qnaire_id );

    $db_interview_list = db\interview::select( $interview_mod );
    
    if( 0 == count( $db_interview_list ) )
    {
      $db_interview = new db\interview();
      $db_interview->participant_id = $db_participant->id;
      $db_interview->qnaire_id = $db_participant->current_qnaire_id;
      $db_interview->save();
    }
    else
    {
      $db_interview = $db_interview_list[0];
    }

    // create an assignment for this user
    $db_assignment = new db\assignment();
    $db_assignment->user_id = $session->get_user()->id;
    $db_assignment->site_id = $session->get_site()->id;
    $db_assignment->interview_id = $db_interview->id;
    $db_assignment->queue_id = $this->get_argument( 'queue_id', NULL );
    $db_assignment->save();
  }
}
?>
