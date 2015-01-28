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
class participant_view extends \cenozo\ui\widget\participant_view
{
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

    $this->add_item( 'qnaire_quota', 'constant', 'Questionnaire Quota State' );
    $this->add_item( 'qnaire_name', 'constant', 'Current Questionnaire' );
    $this->add_item( 'qnaire_date', 'constant', 'Delay Questionnaire Until' );
    $this->add_item( 'data_collection_draw_blood', 'boolean', 'Consent To Draw Blood' );
    $this->add_item( 'data_collection_draw_blood_continue', 'boolean', 'Draw Blood (Continue)' );
    $this->add_item( 'data_collection_physical_tests_continue', 'boolean', 'Physical Tests (Continue)' );
    $this->add_item( 'next_of_kin_first_name', 'string', 'Next of Kin First Name' );
    $this->add_item( 'next_of_kin_last_name', 'string', 'Next of Kin Last Name' );
    $this->add_item( 'next_of_kin_gender', 'string', 'Next of Kin Gender' );
    $this->add_item( 'next_of_kin_phone', 'string', 'Next of Kin Phone' );
    $this->add_item( 'next_of_kin_street', 'string', 'Next of Kin Street' );
    $this->add_item( 'next_of_kin_city', 'string', 'Next of Kin City' );
    $this->add_item( 'next_of_kin_province', 'string', 'Next of Kin Province' );
    $this->add_item( 'next_of_kin_postal_code', 'string', 'Next of Kin Postal Code' );

    // create the appointment sub-list widget
    $this->appointment_list = lib::create( 'ui\widget\appointment_list', $this->arguments );
    $this->appointment_list->set_parent( $this );
    $this->appointment_list->set_heading( 'Appointments' );

    // create the callback sub-list widget
    $this->callback_list = lib::create( 'ui\widget\callback_list', $this->arguments );
    $this->callback_list->set_parent( $this );
    $this->callback_list->set_heading( 'Scheduled Callbacks' );

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

    $session =lib::create( 'business\session' );
    $withdraw_manager = lib::create( 'business\withdraw_manager' );
    $operation_class_name = lib::get_class_name( 'database\operation' );
    $db_participant = $this->get_record();
    $db_next_of_kin = $db_participant->get_next_of_kin();
    $db_data_collection = $db_participant->get_data_collection();

    $db_effective_qnaire = $db_participant->get_effective_qnaire();
    if( is_null( $db_effective_qnaire ) )
    {
      $qnaire_name = '(none)';
      $qnaire_date = '(not applicable)';
    }
    else
    {
      $qnaire_name = $db_effective_qnaire->name;
      $start_qnaire_date = $db_participant->get_start_qnaire_date();
      $qnaire_date = is_null( $start_qnaire_date )
                   ? 'immediately'
                   : util::get_formatted_date( $start_qnaire_date );
    }

    // set the view's items
    $enabled = $db_participant->get_quota_enabled();
    $this->set_item( 'qnaire_quota',
      is_null( $enabled ) ? '(not applicable)' : ( $enabled ? 'Enabled' : 'Disabled' ) );
    $this->set_item( 'qnaire_name', $qnaire_name );
    $this->set_item( 'qnaire_date', $qnaire_date );
    $this->set_item(
      'data_collection_draw_blood',
      is_null( $db_data_collection ) ? NULL : $db_data_collection->draw_blood );
    $this->set_item(
      'data_collection_draw_blood_continue',
      is_null( $db_data_collection ) ? NULL : $db_data_collection->draw_blood_continue );
    $this->set_item(
      'data_collection_physical_tests_continue',
      is_null( $db_data_collection ) ? NULL : $db_data_collection->physical_tests_continue );
    $this->set_item(
      'next_of_kin_first_name', is_null( $db_next_of_kin ) ? '' : $db_next_of_kin->first_name );
    $this->set_item(
      'next_of_kin_last_name', is_null( $db_next_of_kin ) ? '' : $db_next_of_kin->last_name );
    $this->set_item(
      'next_of_kin_gender', is_null( $db_next_of_kin ) ? '' : $db_next_of_kin->gender );
    $this->set_item(
      'next_of_kin_phone', is_null( $db_next_of_kin ) ? '' : $db_next_of_kin->phone );
    $this->set_item(
      'next_of_kin_street', is_null( $db_next_of_kin ) ? '' : $db_next_of_kin->street );
    $this->set_item(
      'next_of_kin_city', is_null( $db_next_of_kin ) ? '' : $db_next_of_kin->city );
    $this->set_item(
      'next_of_kin_province', is_null( $db_next_of_kin ) ? '' : $db_next_of_kin->province );
    $this->set_item(
      'next_of_kin_postal_code', is_null( $db_next_of_kin ) ? '' : $db_next_of_kin->postal_code );

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
      $this->callback_list->process();
      $this->set_variable( 'callback_list', $this->callback_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}

    try
    {
      $this->interview_list->process();
      $this->set_variable( 'interview_list', $this->interview_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}

    // add an action for secondary contact if this participant has no active phone numbers or
    // too many failed call attempts
    $allow_secondary = false;
    $interview_mod = lib::create( 'database\modifier' );
    $interview_mod->where( 'completed', '=', false );
    $interview_list = $db_participant->get_interview_list( $interview_mod );

    $phone_mod = lib::create( 'database\modifier' );
    $phone_mod->where( 'active', '=', true );
    if( 0 == $db_participant->get_phone_count( $phone_mod ) )
    {
      $allow_secondary = true;
    }
    else if( 0 < count( $interview_list ) )
    {
      $max_failed_calls = lib::create( 'business\setting_manager' )->get_setting(
        'calling', 'max failed calls', $db_participant->get_effective_site() );

      // should only be one incomplete interview
      $db_interview = current( $interview_list );
      if( $max_failed_calls <= $db_interview->get_failed_call_count() ) $allow_secondary = true;
    }

    if( $allow_secondary )
    {
      $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'secondary' );
      if( $session->is_operation_allowed( $db_operation ) )
      {
        $this->add_action( 'secondary', 'Secondary Contacts', NULL,
          'A list of alternate contacts which can be called to update a '.
          'participant\'s contact information' );
      }
      else $allow_secondary = false;
    }

    $this->set_variable( 'allow_secondary', $allow_secondary );

    // add a withdraw button if there is a withdraw script set up
    if( !is_null( $withdraw_manager->get_withdraw_sid( $db_participant ) ) )
    {
      $db_last_consent = $db_participant->get_last_consent();
      if( is_null( $db_last_consent ) || true == $db_last_consent->accept )
      { // add an action to withdraw the participant
        $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'withdraw' );
        if( $session->is_operation_allowed( $db_operation ) )
        {
          $this->add_action( 'withdraw', 'Withdraw', NULL,
            'Marks the participant as denying consent and brings up the withdraw script '.
            'in order to process the participant\'s withdraw preferences.' );
        }
      }
      else
      { // add an action to reverse-withdraw the participant (but only for administrators)
        $db_operation = $operation_class_name::get_operation( 'push', 'participant', 'withdraw' );
        if( 'administrator' == $session->get_role()->name &&
            $session->is_operation_allowed( $db_operation ) )
        {
          $this->add_action( 'reverse_withdraw', 'Reverse Withdraw', NULL,
            'Reverses an the participant\'s choice to withdraw and deletes the participant\'s '.
            'withdraw preferences.' );
        }
      }
    }
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
   * @var appointment_list
   * @access protected
   */
  protected $appointment_list = NULL;

  /**
   * The participant list widget.
   * @var callback_list
   * @access protected
   */
  protected $callback_list = NULL;

  /**
   * The participant list widget.
   * @var interview_list
   * @access protected
   */
  protected $interview_list = NULL;
}
