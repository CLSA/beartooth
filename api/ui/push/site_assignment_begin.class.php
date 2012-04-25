<?php
/**
 * site_assignment_begin.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: site_assignment begin
 *
 * Assigns a participant ready for a site interview.
 * @package beartooth\ui
 */
class site_assignment_begin extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'site_assignment', 'begin', $args );
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
    // This search has to be done one qnaire at a time in queues which have
    // a site interview based qnaire.
    $db_participant = NULL;
    $qnaire_mod = lib::create( 'database\modifier' );
    $qnaire_mod->where( 'type', '=', 'site' );
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
            $db_participant = current( $participant_list );
          }
        }

        // stop looping queues if we found a participant
        if( !is_null( $db_participant ) ) break;
      }
      // stop looping qnaires if we found a participant
      if( !is_null( $db_participant ) ) break;
    }

    // if we didn't find a participant then let the user know none are available
    if( is_null( $db_participant ) )
      throw lib::create( 'exception\notice',
        'There are no participants currently available.', __METHOD__ );

    // start the assignment with the participant
    $operation = lib::create(
      'ui\push\assignment_begin',
      array( 'participant_id' => $db_participant->id ) );
    $operation->finish();

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
}
?>
