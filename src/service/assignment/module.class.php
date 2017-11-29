<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\assignment;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\assignment\module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    if( 300 > $this->get_status()->get_code() )
    {
      if( 'POST' == $this->get_method() )
      {
        // repopulate the participant immediately to make sure they are still available for an assignment
        $this->db_participant->repopulate_queue( false );
        if( !is_null( $this->db_participant->get_current_assignment() ) )
        {
          $this->set_data(
            'Cannot create a new assignment since the participant is already assigned to a different user.' );
          $this->get_status()->set_code( 409 );
        }
        else if( 'open' == $this->get_argument( 'operation', false ) )
        {
          $queue_mod = lib::create( 'database\modifier' );
          $queue_mod->where( 'queue.rank', '!=', NULL );
          if( 0 == $this->db_participant->get_queue_count( $queue_mod ) )
          {
            $this->set_data( 'The participant is no longer available for an interview.' );
            $this->get_status()->set_code( 409 );
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

    if( $select->has_table_columns( 'queue' ) )
      $modifier->left_join( 'queue', 'assignment.queue_id', 'queue.id' );

    if( $select->has_table_columns( 'qnaire' ) || $select->has_column( 'qnaire_name' ) )
    {
      $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );

      // define the qnaire_name alias from the qnaire's name
      if( $select->has_column( 'qnaire_name' ) ) $select->add_column( 'qnaire.name', 'qnaire_name', false );
    }
  }

  /**
   * Extend parent method
   */
  public function pre_write( $record )
  {
    parent::pre_write( $record );

    if( 'POST' == $this->get_method() && 'open' == $this->get_argument( 'operation', false ) )
      $record->queue_id = $this->db_participant->current_queue_id;
  }
}
