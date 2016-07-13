<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\service\assignment;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\site_restricted_module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    if( 300 > $this->get_status()->get_code() )
    {
      $service_class_name = lib::get_class_name( 'service\service' );
      $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

      $session = lib::create( 'business\session' );
      $db_user = $session->get_user();
      $db_role = $session->get_role();
      $method = $this->get_method();
      $operation = $this->get_argument( 'operation', false );

      // restrict by site
      $db_restrict_site = $this->get_restricted_site();
      if( !is_null( $db_restrict_site ) )
      {
        $record = $this->get_resource();
        if( $record && $record->site_id && $record->site_id != $db_restrict_site->id )
        {
          $this->get_status()->set_code( 403 );
          return;
        }
      }

      if( ( 'DELETE' == $method || 'PATCH' == $method ) &&
          3 > $db_role->tier &&
          $this->get_resource()->user_id != $db_user->id )
      {
        // only admins can delete or modify assignments other than their own
          $this->get_status()->set_code( 403 );
      }
      else if( 'PATCH' == $method && ( 'close' == $operation || 'force_close' == $operation ) )
      {
        $record = $this->get_resource();

        if( 0 < count( $this->get_file_as_array() ) )
        {
          $this->set_data( 'Patch data must be empty when advancing or closing an assignment.' );
          $this->get_status()->set_code( 400 );
        }
        else if( !is_null( $record->end_datetime ) )
        {
          $this->set_data( 'Cannot close the assignment since it is already closed.' );
          $this->get_status()->set_code( 409 );
        }
        else
        {
          // check if there is an appointment for the current interview
          $db_interview = $record->get_interview();

          if( 'close' == $operation )
          {
            if( 0 < $record->has_open_phone_call() )
            {
              $this->set_data( 'An assignment cannot be closed during an open call.' );
              $this->get_status()->set_code( 409 );
            }
          }
          else if( 'force_close' == $operation )
          {
            if( 2 > $db_role->tier ) $this->get_status()->set_code( 403 );
          }
        }
      }
      else if( 'POST' == $method )
      {
        // do not allow more than one open assignment
        if( $db_user->has_open_assignment() )
        {
          $this->set_data( 'Cannot create a new assignment since you already have one open.' );
          $this->get_status()->set_code( 409 );
        }
        else
        {
          // repopulate the participant immediately to make sure they are still available for an assignment
          $post_object = $this->get_file_as_object();
          $db_participant = lib::create( 'database\participant', $post_object->participant_id );
          $db_participant->repopulate_queue( false );
          if( !is_null( $db_participant->get_current_assignment() ) )
          {
            $this->set_data(
              'Cannot create a new assignment since the participant is already assigned to a different user.' );
            $this->get_status()->set_code( 409 );
          }
          else if( 'open' == $operation )
          {
            $queue_mod = lib::create( 'database\modifier' );
            $queue_mod->where( 'queue.rank', '!=', NULL );
            if( 0 == $db_participant->get_queue_count( $queue_mod ) )
            {
              $this->set_data( 'The participant is no longer available for an interview.' );
              $this->get_status()->set_code( 409 );
            }
          }
        }
      }
    }
  }

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    // restrict by site
    $db_restrict_site = $this->get_restricted_site();
    if( !is_null( $db_restrict_site ) ) $modifier->where( 'assignment.site_id', '=', $db_restrict_site->id );

    if( $select->has_table_columns( 'queue' ) )
      $modifier->left_join( 'queue', 'assignment.queue_id', 'queue.id' );

    if( $select->has_table_columns( 'user' ) )
      $modifier->left_join( 'user', 'assignment.user_id', 'user.id' );

    if( $select->has_table_columns( 'site' ) )
      $modifier->left_join( 'site', 'assignment.site_id', 'site.id' );

    if( $select->has_table_columns( 'participant' ) ||
        $select->has_table_columns( 'qnaire' ) )
    {
      $modifier->join( 'interview', 'assignment.interview_id', 'interview.id' );
      if( $select->has_table_columns( 'participant' ) )
        $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
      if( $select->has_table_columns( 'qnaire' ) )
        $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    }

    if( $select->has_column( 'phone_call_count' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'phone_call' );
      $join_sel->add_column( 'assignment_id' );
      $join_sel->add_column( 'COUNT( * )', 'phone_call_count', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->group( 'assignment_id' );

      $modifier->left_join(
        sprintf( '( %s %s ) AS assignment_join_phone_call', $join_sel->get_sql(), $join_mod->get_sql() ),
        'assignment.id',
        'assignment_join_phone_call.assignment_id' );
      $select->add_column( 'IFNULL( phone_call_count, 0 )', 'phone_call_count', false );
    }

    // add the assignment's last call's status column
    $modifier->left_join( 'assignment_last_phone_call',
      'assignment.id', 'assignment_last_phone_call.assignment_id' );
    $modifier->left_join( 'phone_call AS last_phone_call',
      'assignment_last_phone_call.phone_call_id', 'last_phone_call.id' );
    $select->add_table_column( 'last_phone_call', 'status' );

    if( $select->has_column( 'call_active' ) )
      $select->add_table_column( 'last_phone_call',
        'last_phone_call.id IS NOT NULL AND last_phone_call.end_datetime IS NULL',
        'call_active', false, 'boolean' );
  }

  /**
   * Extend parent method
   */
  public function pre_write( $record )
  {
    parent::pre_write( $record );

    $now = util::get_datetime_object();
    $operation = $this->get_argument( 'operation', false );
    if( 'POST' == $this->get_method() && 'open' == $operation )
    {
      $session = lib::create( 'business\session' );

      // use the post object to fill in the record columns
      $post_object = $this->get_file_as_object();
      $db_participant = lib::create( 'database\participant', $post_object->participant_id );
      $db_interview = $db_participant->get_effective_interview();
      $db_interview->start_datetime = $now;
      $db_interview->save();

      $record->user_id = $session->get_user()->id;
      $record->role_id = $session->get_role()->id;
      $record->site_id = $session->get_site()->id;
      $record->interview_id = $db_interview->id;
      $record->queue_id = $db_participant->current_queue_id;
      $record->start_datetime = $now;
    }
    else if( 'PATCH' == $this->get_method() && ( 'close' == $operation || 'force_close' == $operation ) )
    {
      $record->end_datetime = $now;
    }
  }

  /**
   * Extend parent method
   */
  public function post_write( $record )
  {
    parent::post_write( $record );

    if( 'PATCH' == $this->get_method() )
    {
      $operation = $this->get_argument( 'operation', false );
      if( 'close' == $operation || 'force_close' == $operation )
      {
        if( 'force_close' == $operation )
        {
          // end any active phone calls
          $phone_call_mod = lib::create( 'database\modifier' );
          $phone_call_mod->where( 'phone_call.end_datetime', '=', NULL );
          foreach( $record->get_phone_call_object_list( $phone_call_mod ) as $db_phone_call )
          {
            $db_phone_call->end_datetime = util::get_datetime_object();
            $db_phone_call->status = 'contacted';
            $db_phone_call->save();
            $db_phone_call->process_events();
          }
        }

        // delete the assignment if there are no phone calls, or process callbacks if there are
        if( 0 == $record->get_phone_call_count() ) $record->delete();
        else
        {
          // update any callbacks associated with this assignment
          $record->process_callbacks( true );
        }
      }
    }
  }
}
