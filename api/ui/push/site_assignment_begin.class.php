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

    // we can't use a transaction, otherwise the semaphore in the execute() method won't work
    lib::create( 'business\session' )->set_use_transaction( false );
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

    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $queue_class_name = lib::get_class_name( 'database\queue' );

    $session = lib::create( 'business\session' );
    $setting_manager = lib::create( 'business\setting_manager' );
    $db_user = $session->get_user();

    // we need to use a semaphore to avoid race conditions
    $semaphore = sem_get( getmyinode() );
    if( !sem_acquire( $semaphore ) )
    {
      log::err( sprintf( 'Unable to aquire semaphore for user "%s"', $db_user()->name ) );
      throw lib::create( 'exception\notice',
        'The server is busy, please wait a few seconds then click the refresh button.',
        __METHOD__ );
    }

    // Search through every queue for a new assignment until one is found.
    // This search has to be done one qnaire at a time in queues which have
    // a site interview based qnaire.
    $db_origin_queue = NULL;
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
          // make sure to restrict by language, if necessary
          $participant_mod = lib::create( 'database\modifier' );
          $participant_mod->limit( 1 );
          if( 'any' != $db_user->language )
          {
            // english is default, so if the language is english allow null values
            if( 'en' == $db_user->language )
            {
              $participant_mod->where_bracket( true );
              $participant_mod->where( 'participant.language', '=', $db_user->language );
              $participant_mod->or_where( 'participant.language', '=', NULL );
              $participant_mod->where_bracket( false );
            }
            else $participant_mod->where( 'participant.language', '=', $db_user->language );
          }

          $db_queue->set_site( $session->get_site() );
          $db_queue->set_qnaire( $db_qnaire );
          $participant_list = $db_queue->get_participant_list( $participant_mod );
          if( 1 == count( $participant_list ) )
          {
            $db_origin_qnaire = $db_qnaire;
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

    // make sure the qnaire has phases
    if( 0 == $db_origin_qnaire->get_phase_count() )
      throw lib::create( 'exception\notice',
        'This participant\'s next questionnaire is not yet ready. '.
        'Please immediately report this problem to a superior.',
        __METHOD__ );

    // start the assignment with the participant
    $operation = lib::create(
      'ui\push\assignment_begin',
      array( 'participant_id' => $db_participant->id,
             'queue_id' => $db_origin_queue->id ) );
    $operation->process();

    // release the semaphore, if there is one
    if( !sem_release( $semaphore ) )
      log::err( sprintf( 'Unable to release semaphore for user %s', $db_user->name ) );
  }
}
?>
