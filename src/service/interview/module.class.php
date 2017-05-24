<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\service\interview;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\interview\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    if( $select->has_column( 'last_participation_consent' ) )
    {
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'interview.participant_id', '=', 'participant_last_consent.participant_id' );
      $modifier->join(
        'participant_last_consent', 'interview.participant_id', 'participant_last_consent.participant_id' );
      $modifier->join( 'consent_type', 'participant_last_consent.consent_type_id', 'consent_type.id' );
      $modifier->where( 'consent_type.name', '=', 'participation' );
      $modifier->left_join( 'consent', 'participant_last_consent.consent_id', 'consent.id' );
      $select->add_column( 'consent.accept', 'last_participation_consent', false, 'boolean' );
    }

    if( $select->has_column( 'future_appointment' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'appointment' );
      $join_sel->add_column( 'interview_id' );
      $join_sel->add_column( 'COUNT( * ) > 0', 'future_appointment', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'datetime', '>', 'UTC_TIMESTAMP()', false );
      $join_mod->group( 'interview_id' );

      $modifier->left_join(
        sprintf( '( %s %s ) AS interview_join_appointment', $join_sel->get_sql(), $join_mod->get_sql() ),
        'interview.id',
        'interview_join_appointment.interview_id' );
      $select->add_column( 'IFNULL( future_appointment, false )', 'future_appointment', false, 'boolean' );
    }

    if( $select->has_column( 'missed_appointment' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'appointment' );
      $join_sel->add_column( 'interview_id' );
      $join_sel->add_column( 'COUNT( * ) > 0', 'missed_appointment', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'datetime', '<', 'UTC_TIMESTAMP()', false );
      $join_mod->where( 'outcome', '=', NULL );
      $join_mod->group( 'interview_id' );

      $modifier->left_join(
        sprintf( '( %s %s ) AS interview_join_appointment', $join_sel->get_sql(), $join_mod->get_sql() ),
        'interview.id',
        'interview_join_appointment.interview_id' );
      $select->add_column( 'IFNULL( missed_appointment, false )', 'missed_appointment', false, 'boolean' );
    }

    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $select->add_table_column( 'qnaire', 'type' );
  }
}
