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
 * Assigns a participant to the user.
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

    $participant_id = $this->get_argument( 'participant_id' );
    $this->db_participant = lib::create( 'database\participant', $participant_id );
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
  }

  /**
   * The participant to assign (may be specified in the constructor from input arguments)
   * @var database\participant $db_participant
   * @access protected
   */
  protected $db_participant = NULL;
}
?>
