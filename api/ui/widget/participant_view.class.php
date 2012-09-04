<?php
/**
 * participant_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget participant view
 */
class participant_view extends \cenozo\ui\widget\base_view
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'view', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();
    
    // create an associative array with everything we want to display about the participant
    $this->add_item( 'active', 'boolean', 'Active' );
    $this->add_item( 'uid', 'constant', 'Unique ID' );
    $this->add_item( 'source', 'constant', 'Source' );
    $this->add_item( 'first_name', 'string', 'First Name' );
    $this->add_item( 'last_name', 'string', 'Last Name' );
    $this->add_item( 'language', 'enum', 'Preferred Language' );
    $this->add_item( 'status', 'enum', 'Condition' );
    $this->add_item( 'default_site', 'constant', 'Default Site' );
    $this->add_item( 'site_id', 'enum', 'Prefered Site' );
    $this->add_item( 'email', 'string', 'Email' );
    $this->add_item( 'gender', 'enum', 'Gender' );
    $this->add_item( 'date_of_birth', 'date', 'Date of Birth' );
    $this->add_item( 'prior_contact_date', 'constant', 'Prior Contact Date' );
    $this->add_item( 'current_qnaire_name', 'constant', 'Current Questionnaire' );
    $this->add_item( 'start_qnaire_date', 'constant', 'Delay Questionnaire Until' );
    $this->add_item( 'consent_to_draw_blood', 'boolean', 'Consent to Draw Blood' );
    $this->add_item( 'defer_until', 'date', 'Defer Contact Until' );
    
    // create the address sub-list widget
    $this->address_list = lib::create( 'ui\widget\address_list', $this->arguments );
    $this->address_list->set_parent( $this );
    $this->address_list->set_heading( 'Addresses' );

    // create the phone sub-list widget
    $this->phone_list = lib::create( 'ui\widget\phone_list', $this->arguments );
    $this->phone_list->set_parent( $this );
    $this->phone_list->set_heading( 'Phone numbers' );

    // create the appointment sub-list widget
    $this->appointment_list = lib::create( 'ui\widget\appointment_list', $this->arguments );
    $this->appointment_list->set_parent( $this );
    $this->appointment_list->set_heading( 'Appointments' );

    // create the availability sub-list widget
    $this->availability_list = lib::create( 'ui\widget\availability_list', $this->arguments );
    $this->availability_list->set_parent( $this );
    $this->availability_list->set_heading( 'Availability' );

    // create the consent sub-list widget
    $this->consent_list = lib::create( 'ui\widget\consent_list', $this->arguments );
    $this->consent_list->set_parent( $this );
    $this->consent_list->set_heading( 'Consent information' );

    // create the interview sub-list widget
    $this->interview_list = lib::create( 'ui\widget\interview_list', $this->arguments );
    $this->interview_list->set_parent( $this );
    $this->interview_list->set_heading( 'Interview history' );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $site_class_name = lib::get_class_name( 'database\site' );

    $db_participant = $this->get_record();

    // create enum arrays
    $languages = $participant_class_name::get_enum_values( 'language' );
    $languages = array_combine( $languages, $languages );
    $statuses = $participant_class_name::get_enum_values( 'status' );
    $statuses = array_combine( $statuses, $statuses );
    $sites = array();
    $site_mod = lib::create( 'database\modifier' );
    $site_mod->order( 'name' );
    foreach( $site_class_name::select( $site_mod ) as $db_site )
      $sites[$db_site->id] = $db_site->name;
    $db_site = $db_participant->get_site();
    $site_id = is_null( $db_site ) ? '' : $db_site->id;
    $genders = $participant_class_name::get_enum_values( 'gender' );
    $genders = array_combine( $genders, $genders );
    
    $start_qnaire_date = $db_participant->start_qnaire_date;
    if( is_null( $db_participant->current_qnaire_id ) )
    {
      $current_qnaire_name = '(none)';

      $start_qnaire_date = '(not applicable)';
    }
    else
    {
      $db_current_qnaire = lib::create( 'database\qnaire', $db_participant->current_qnaire_id );
      $current_qnaire_name = $db_current_qnaire->name;
      $start_qnaire_date = util::get_formatted_date( $start_qnaire_date, 'immediately' );
    }

    $db_default_site = $db_participant->get_default_site();
    $default_site = is_null( $db_default_site ) ? 'None' : $db_default_site->name;

    // set the view's items
    $this->set_item( 'active', $db_participant->active, true );
    $this->set_item( 'uid', $db_participant->uid );
    $this->set_item( 'source', $db_participant->get_source()->name );
    $this->set_item( 'first_name', $db_participant->first_name );
    $this->set_item( 'last_name', $db_participant->last_name );
    $this->set_item( 'language', $db_participant->language, false, $languages );
    $this->set_item( 'status', $db_participant->status, false, $statuses );
    $this->set_item( 'default_site', $default_site );
    $this->set_item( 'site_id', $site_id, false, $sites );
    $this->set_item( 'email', $db_participant->email );
    $this->set_item( 'gender', $db_participant->gender, true, $genders );
    $this->set_item( 'date_of_birth', $db_participant->date_of_birth );
    $this->set_item( 'prior_contact_date', $db_participant->prior_contact_date );
    $this->set_item( 'current_qnaire_name', $current_qnaire_name );
    $this->set_item( 'start_qnaire_date', $start_qnaire_date );
    $this->set_item( 'consent_to_draw_blood', $db_participant->consent_to_draw_blood );
    $this->set_item( 'defer_until', $db_participant->defer_until, false );

    try
    {
      $this->address_list->process();
      $this->set_variable( 'address_list', $this->address_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}

    try
    {
      $this->phone_list->process();
      $this->set_variable( 'phone_list', $this->phone_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}

    try
    {
      $this->appointment_list->process();
      $this->appointment_list->remove_column( 'uid' );
      $this->appointment_list->execute();
      $this->set_variable( 'appointment_list', $this->appointment_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}

    try
    {
      $this->availability_list->process();
      $this->set_variable( 'availability_list', $this->availability_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}

    try
    {
      $this->consent_list->process();
      $this->set_variable( 'consent_list', $this->consent_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}

    try
    {
      $this->interview_list->process();
      $this->set_variable( 'interview_list', $this->interview_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}
  }
  
  /**
   * Overrides the interview list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @interview protected
   */
  public function determine_interview_count( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_id', '=', $this->get_record()->id );
    $interview_class_name = lib::get_class_name( 'database\interview' );
    return $interview_class_name::count( $modifier );
  }

  /**
   * Overrides the interview list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @interview protected
   */
  public function determine_interview_list( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_id', '=', $this->get_record()->id );
    $interview_class_name = lib::get_class_name( 'database\interview' );
    return $interview_class_name::select( $modifier );
  }

  /**
   * The participant list widget.
   * @var address_list
   * @access protected
   */
  protected $address_list = NULL;
  
  /**
   * The participant list widget.
   * @var phone_list
   * @access protected
   */
  protected $phone_list = NULL;
  
  /**
   * The participant list widget.
   * @var appointment_list
   * @access protected
   */
  protected $appointment_list = NULL;
  
  /**
   * The participant list widget.
   * @var availability_list
   * @access protected
   */
  protected $availability_list = NULL;
  
  /**
   * The participant list widget.
   * @var consent_list
   * @access protected
   */
  protected $consent_list = NULL;
  
  /**
   * The participant list widget.
   * @var interview_list
   * @access protected
   */
  protected $interview_list = NULL;
}
?>
