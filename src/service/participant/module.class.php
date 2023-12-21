<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\participant;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\participant\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $session = lib::create( 'business\session' );

    if( $select->has_column( 'qnaire_type' ) )
    {
      $modifier->join( 'participant_last_interview', 'participant.id', 'participant_last_interview.participant_id' );
      $modifier->join( 'interview', 'participant_last_interview.interview_id', 'interview.id' );
      $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
      $select->add_column( 'qnaire.type', 'qnaire_type', false );
    }

    $qnaire_type = $this->get_argument( 'assignment', false );
    if( $qnaire_type )
    {
      // the assignment argument will either be "home" or "site"; we need to restrict to that qnaire type
      $modifier->where( 'qnaire.type', '=', $qnaire_type );

      // remove hold/proxy/trace/exclusion joins for efficiency
      $modifier->remove_join( 'exclusion' );
      $modifier->remove_join( 'participant_last_hold' );
      $modifier->remove_join( 'hold' );
      $modifier->remove_join( 'hold_type' );
      $modifier->remove_join( 'participant_last_proxy' );
      $modifier->remove_join( 'proxy' );
      $modifier->remove_join( 'proxy_type' );
      $modifier->remove_join( 'participant_last_trace' );
      $modifier->remove_join( 'trace' );
      $modifier->remove_join( 'trace_type' );
      $select->remove_column_by_alias( 'status' );

      $modifier->join( 'queue_has_participant', 'participant.id', 'queue_has_participant.participant_id' );
      $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
      $modifier->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
      $modifier->where( 'queue.rank', '!=', NULL );

      if( $select->has_column( 'coi_list' ) )
      {
        // NOTE: we can't use the parent::add_list_column method because there is a special relationship
        $coi_sel = lib::create( 'database\select' );
        $coi_sel->FROM( 'queue' );
        $coi_sel->add_table_column( 'queue_has_participant', 'participant_id' );
        $coi_sel->add_column( 'GROUP_CONCAT( consent_type.name ORDER BY consent_type.name )', 'coi_list', false );

        $coi_mod = lib::create( 'database\modifier' );
        $coi_mod->join( 'queue_has_participant', 'queue.id', 'queue_has_participant.queue_id' );
        $coi_mod->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
        $coi_mod->left_join( 'qnaire_has_consent_type', 'qnaire.id', 'qnaire_has_consent_type.qnaire_id' );
        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where(
          'qnaire_has_consent_type.consent_type_id',
          '=',
          'participant_last_consent.consent_type_id',
          false
        );
        $join_mod->where(
          'queue_has_participant.participant_id',
          '=',
          'participant_last_consent.participant_id',
          false
        );
        $coi_mod->join_modifier( 'participant_last_consent', $join_mod, 'left' );

        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'participant_last_consent.consent_id', '=', 'consent.id', false );
        $join_mod->where( 'consent.accept', '=', true );
        $coi_mod->join_modifier( 'consent', $join_mod, 'left' );

        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'consent.consent_type_id', '=', 'consent.consent_type_id', false );
        $join_mod->where( 'qnaire_has_consent_type.consent_type_id', '=', 'consent_type.id', false );
        $coi_mod->join_modifier( 'consent_type', $join_mod, 'left' );

        $coi_mod->where( 'queue.rank', '!=', NULL );
        $coi_mod->where( 'qnaire.type', '=', $qnaire_type );
        $coi_mod->group( 'queue_has_participant.participant_id' );

        $modifier->join(
          sprintf( '( %s %s ) AS participant_coi', $coi_sel->get_sql(), $coi_mod->get_sql() ),
          'participant.id',
          'participant_coi.participant_id'
        );

        $select->add_table_column( 'participant_coi', 'coi_list' );
      }

      if( $select->has_column( 'eoi_list' ) )
      {
        // NOTE: we can't use the parent::add_list_column method because there is a special relationship
        $eoi_sel = lib::create( 'database\select' );
        $eoi_sel->FROM( 'queue' );
        $eoi_sel->add_table_column( 'queue_has_participant', 'participant_id' );
        $eoi_sel->add_column( 'GROUP_CONCAT( event_type.name ORDER BY event_type.name )', 'eoi_list', false );

        $eoi_mod = lib::create( 'database\modifier' );
        $eoi_mod->join( 'queue_has_participant', 'queue.id', 'queue_has_participant.queue_id' );
        $eoi_mod->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
        $eoi_mod->left_join( 'qnaire_has_event_type', 'qnaire.id', 'qnaire_has_event_type.qnaire_id' );
        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where(
          'qnaire_has_event_type.event_type_id',
          '=',
          'participant_last_event.event_type_id',
          false
        );
        $join_mod->where(
          'queue_has_participant.participant_id',
          '=',
          'participant_last_event.participant_id',
          false
        );
        $eoi_mod->join_modifier( 'participant_last_event', $join_mod, 'left' );

        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'participant_last_event.event_id', '=', 'event.id', false );
        $eoi_mod->join_modifier( 'event', $join_mod, 'left' );

        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'event.event_type_id', '=', 'event.event_type_id', false );
        $join_mod->where( 'qnaire_has_event_type.event_type_id', '=', 'event_type.id', false );
        $eoi_mod->join_modifier( 'event_type', $join_mod, 'left' );

        $eoi_mod->where( 'queue.rank', '!=', NULL );
        $eoi_mod->where( 'qnaire.type', '=', $qnaire_type );
        $eoi_mod->group( 'queue_has_participant.participant_id' );

        $modifier->join(
          sprintf( '( %s %s ) AS participant_eoi', $eoi_sel->get_sql(), $eoi_mod->get_sql() ),
          'participant.id',
          'participant_eoi.participant_id'
        );

        $select->add_table_column( 'participant_eoi', 'eoi_list' );
      }

      if( $select->has_column( 'soi_list' ) )
      {
        // NOTE: we can't use the parent::add_list_column method because there is a special relationship
        $soi_sel = lib::create( 'database\select' );
        $soi_sel->FROM( 'queue' );
        $soi_sel->add_table_column( 'queue_has_participant', 'participant_id' );
        $soi_sel->add_column( 'GROUP_CONCAT( study.name ORDER BY study.name )', 'soi_list', false );

        $soi_mod = lib::create( 'database\modifier' );
        $soi_mod->join( 'queue_has_participant', 'queue.id', 'queue_has_participant.queue_id' );
        $soi_mod->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
        $soi_mod->left_join( 'qnaire_has_study', 'qnaire.id', 'qnaire_has_study.qnaire_id' );
        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where(
          'qnaire_has_study.study_id',
          '=',
          'study_has_participant.study_id',
          false
        );
        $join_mod->where(
          'queue_has_participant.participant_id',
          '=',
          'study_has_participant.participant_id',
          false
        );
        $soi_mod->join_modifier( 'study_has_participant', $join_mod, 'left' );

        $soi_mod->left_join( 'study', 'study_has_participant.study_id', 'study.id' );

        $soi_mod->where( 'queue.rank', '!=', NULL );
        $soi_mod->where( 'qnaire.type', '=', $qnaire_type );
        $soi_mod->group( 'queue_has_participant.participant_id' );

        $modifier->join(
          sprintf( '( %s %s ) AS participant_soi', $soi_sel->get_sql(), $soi_mod->get_sql() ),
          'participant.id',
          'participant_soi.participant_id'
        );

        $select->add_table_column( 'participant_soi', 'soi_list' );
      }

      if( $select->has_column( 'address_summary' ) )
      {
        $modifier->left_join( 'address', 'queue_has_participant.address_id', 'address.id' );
        $select->add_table_column(
          'address',
          'CONCAT_WS( ", ", address.address1, address.address2, address.city, address.postcode )',
          'address_summary',
          false );
      }

      if( $select->has_column( 'last_completed_datetime' ) )
      {
        // join to the previous completed event
        $modifier->left_join( 'qnaire', 'qnaire.rank', 'prev_qnaire.rank + 1', 'prev_qnaire' );
        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'prev_qnaire.completed_event_type_id', '=', 'completed_event.event_type_id', false );
        $join_mod->where( 'participant.id', '=', 'completed_event.participant_id', false );
        $modifier->join_modifier( 'event', $join_mod, 'left', 'completed_event' );

        $select->add_table_column( 'completed_event', 'datetime', 'last_completed_datetime' );
      }

      if( $select->has_column( 'prev_interview_type' ) )
      {
        // join to the previous interview, and interviewing instance
        if( !$modifier->has_join( 'prev_qnaire' ) )
          $modifier->left_join( 'qnaire', 'qnaire.rank', 'prev_qnaire.rank + 1', 'prev_qnaire' );
        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'prev_qnaire.id', '=', 'prev_interview.qnaire_id', false );
        $join_mod->where( 'participant.id', '=', 'prev_interview.participant_id', false );
        $modifier->join_modifier( 'interview', $join_mod, 'left', 'prev_interview' );
        $modifier->left_join(
          'interviewing_instance',
          'prev_interview.interviewing_instance_id',
          'prev_interviewing_instance.id',
          'prev_interviewing_instance'
        );

        $select->add_column(
          'IFNULL( prev_interviewing_instance.type, "onyx" )',
          'prev_interview_type',
          false
        );
      }

      if( $select->has_column( 'prev_event_user' ) || $select->has_column( 'prev_event_site' ) )
      {
        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'qnaire.prev_event_type_id', '=', 'participant_last_event.event_type_id', false );
        $join_mod->where( 'participant.id', '=', 'participant_last_event.participant_id', false );
        $modifier->join_modifier( 'participant_last_event', $join_mod, 'left' );
        $modifier->left_join( 'event', 'participant_last_event.event_id', 'prev_event.id', 'prev_event' );

        if( $select->has_column( 'prev_event_user' ) )
        {
          $modifier->left_join( 'user', 'prev_event.user_id', 'prev_user.id', 'prev_user' );
          $select->add_table_column(
            'prev_user', 'CONCAT( prev_user.first_name, " ", prev_user.last_name )', 'prev_event_user', false );
        }

        if( $select->has_column( 'prev_event_site' ) )
        {
          $modifier->left_join( 'site', 'prev_event.site_id', 'prev_site.id', 'prev_site' );
          $select->add_table_column( 'prev_site', 'name', 'prev_event_site' );
        }
      }

      // repopulate queue if it is out of date
      $queue_class_name = lib::get_class_name( 'database\queue' );
      $interval = $queue_class_name::get_interval_since_last_repopulate();
      if( is_null( $interval ) || 0 < $interval->days || 22 < $interval->h )
      { // it's been at least 23 hours since the non time-based queues have been repopulated
        $queue_class_name::repopulate();
        $queue_class_name::repopulate_time();
      }
      else
      {
        $interval = $queue_class_name::get_interval_since_last_repopulate_time();
        if( is_null( $interval ) || 0 < $interval->days || 0 < $interval->h || 0 < $interval->i )
        { // it's been at least one minute since the time-based queues have been repopulated
          $queue_class_name::repopulate_time();
        }
      }

      // add all consent records of interest
      $modifier->group( 'participant.id' );
    }
    else
    {
      if( $select->has_table_columns( 'queue' ) || $select->has_table_columns( 'qnaire' ) )
      {
        // Special note: the following is needed when viewing a participant's details but not needed when
        // viewing a list of participants belonging to a queue (and the participant_max_queue join below
        // would drastically slow down the query if we were to use it)
        // We can work around this issue by not joining to this temporary table when the parent is "queue"
        if( 'queue' != $this->get_parent_subject() )
        {
          $join_sel = lib::create( 'database\select' );
          $join_sel->from( 'queue_has_participant' );
          $join_sel->add_column( 'participant_id' );
          $join_sel->add_column( 'MAX( queue_id )', 'queue_id', false );

          $join_mod = lib::create( 'database\modifier' );
          $join_mod->group( 'participant_id' );

          $modifier->left_join(
            sprintf( '( %s %s ) AS participant_max_queue',
                     $join_sel->get_sql(),
                     $join_mod->get_sql() ),
            'participant.id',
            'participant_max_queue.participant_id' );

          $join_mod = lib::create( 'database\modifier' );
          $join_mod->where(
            'participant_max_queue.queue_id', '=', 'queue_has_participant.queue_id', false );
          $join_mod->where(
            'participant_max_queue.participant_id', '=', 'queue_has_participant.participant_id', false );

          $modifier->join_modifier( 'queue_has_participant', $join_mod, 'left' );
        }

        if( $select->has_table_columns( 'queue' ) )
          $modifier->left_join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
        if( $select->has_table_columns( 'qnaire' ) )
        {
          $modifier->left_join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );

          // title is "qnaire.rank: qnaire.name"
          if( $select->has_table_column( 'qnaire', 'title' ) )
            $select->add_table_column( 'qnaire', 'CONCAT( qnaire.rank, ": ", qnaire.name )', 'title', false );

          // fake the qnaire start-date
          if( $select->has_table_column( 'qnaire', 'start_date' ) )
            $select->add_table_column( 'qnaire', 'queue_has_participant.start_qnaire_date', 'start_date', false );
        }
      }

      // interviewer's participant list only includes participants they have an incomplete appointment with
      if( 'GET' == $this->get_method() && is_null( $this->get_resource() ) )
      {
        $db_role = $session->get_role();
        if( 'interviewer' == $db_role->name )
        {
          $modifier->join( 'interview', 'participant.id', 'interview.participant_id' );
          $modifier->join( 'appointment', 'interview.id', 'appointment.interview_id' );
          $modifier->where( 'appointment.user_id', '=', $session->get_user()->id );
          $modifier->where( 'appointment.outcome', '=', NULL );
        }
      }
    }
  }
}
