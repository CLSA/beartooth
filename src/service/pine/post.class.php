<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\pine;
use cenozo\lib, cenozo\log, beartooth\util;

class post extends \cenozo\service\service
{
  /**
   * Constructor
   * 
   * @param string $path The URL of the service (not including the base)
   * @param array $args An associative array of arguments to be processed by the post operation.
   * @param string $file The raw file posted by the request
   * @access public
   */
  public function __construct( $path, $args, $file )
  {
    parent::__construct( 'POST', $path, $args, $file );
  }

  /**
   * Override parent method
   */
  protected function validate()
  {
    parent::validate();

    if( 300 > $this->status->get_code() )
    {
      $participant_class_name = lib::get_class_name( 'database\participant' );

      // determine the property based on the export type
      $session = lib::create( 'business\session' );
      $db_application = $session->get_application();
      $db_site = $session->get_site();
      $data = $this->get_file_as_object();

      // make sure all participants exist
      $input_list = array();
      foreach( $data as $respondent ) $input_list[$respondent->uid] = $respondent->object;
      ksort( $input_list );

      // match all UIDs for participants belonging to the correct site and application
      $participant_mod = lib::create( 'database\modifier' );

      // restrict by application
      $sub_mod = lib::create( 'database\modifier' );
      $sub_mod->where( 'participant.id', '=', 'application_has_participant.participant_id', false );
      $sub_mod->where( 'application_has_participant.application_id', '=', $db_application->id );
      $sub_mod->where( 'application_has_participant.datetime', '!=', NULL );
      $participant_mod->join_modifier(
        'application_has_participant', $sub_mod, $db_application->release_based ? '' : 'left' );

      // restrict by site
      $sub_mod = lib::create( 'database\modifier' );
      $sub_mod->where( 'participant.id', '=', 'participant_site.participant_id', false );
      $sub_mod->where( 'participant_site.application_id', '=', $db_application->id );
      $sub_mod->where( 'participant_site.site_id', '=', $db_site->id );
      $participant_mod->join_modifier( 'participant_site', $sub_mod );
      $participant_mod->where( 'uid', 'IN', array_keys( $input_list ) );
      $participant_mod->order( 'uid' );
      foreach( $participant_class_name::select_objects( $participant_mod ) as $db_participant )
      {
        $this->object_list[$db_participant->uid] = array(
          'participant' => $db_participant,
          'object' => $input_list[$db_participant->uid] );
      }

      // search for missing uids in the object list
      if( count( $input_list ) != count( $this->object_list ) )
      {
        $missing_list = array_diff( array_keys( $input_list ), array_keys( $this->object_list ) );
        $this->set_data( sprintf( 'The following UIDs are invalid: "%s"', implode( ', ', $missing_list ) ) );
        $this->status->set_code( 306 );
      }
    }
  }


  /**
   * Override parent method since pine is a meta-resource
   */
  protected function execute()
  {
    foreach( $this->object_list as $data )
      $this->process_participant( $data['participant'], $data['object'] );

    if( is_null( $this->status->get_code() ) ) $this->status->set_code( 201 );
  }

  /**
   * Processes the pine/participants service
   * 
   * @param database\participant $db_participant The participant being exported to
   * @param stdClass $object The data sent by Pine
   * @access private
   */
  private function process_participant( $db_participant, $object )
  {
    $interviewing_instance_class_name = lib::get_class_name( 'database\interviewing_instance' );
    $consent_type_class_name = lib::get_class_name( 'database\consent_type' );

    // get the pine instance to tell whether this is a home or site instance
    $db_user = lib::create( 'business\session' )->get_user();
    $db_interviewing_instance = $interviewing_instance_class_name::get_unique_record( 'user_id', $db_user->id );
    if( is_null( $db_interviewing_instance ) )
    {
      throw lib::create( 'exception\runtime',
        sprintf( 'Pine user "%s" is not linked to any pine instance.', $db_user->name ),
        __METHOD__
      );
    }

    // get the interview corresponding with this export
    $interview_type = is_null( $db_interviewing_instance->interviewer_user_id ) ? 'site' : 'home';
    $interview_sel = lib::create( 'database\select' );
    $interview_sel->from( 'interview' );
    $interview_sel->add_column( 'id' );
    $interview_mod = lib::create( 'database\modifier' );
    $interview_mod->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $interview_mod->where( 'qnaire.type', '=', $interview_type );
    $interview_mod->where( 'interview.end_datetime', '=', NULL );
    $interview_mod->order_desc( 'interview.start_datetime' );
    $interview_list = $db_participant->get_interview_object_list( $interview_mod );
    if( 0 == count( $interview_list ) )
    {
      throw lib::create( 'exception\runtime',
        sprintf( 'Trying to export Pine interview but no matching %s interview can be found.', $interview_type ),
        __METHOD__
      );
    }
    $db_interview = current( $interview_list );

    $member = 'end_datetime';
    $datetime_obj = util::get_datetime_object( property_exists( $object, $member ) ? $object->$member : NULL );

    // interview and appointment status
    $db_interview->complete( NULL, $datetime_obj );
    $db_participant->repopulate_queue( false );
  }

  /**
   * A list of participant/object pairs used for processing.  Each element contains and associative
   * array with two elements, a participant record and the object used for processing.
   * @var array( array( 'uid', 'object' ) )
   * @access private
   */
  private $object_list = array();
}
