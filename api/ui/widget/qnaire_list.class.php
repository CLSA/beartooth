<?php
/**
 * qnaire_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * widget qnaire list
 * 
 * @package beartooth\ui
 */
class qnaire_list extends base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the qnaire list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'qnaire', $args );
    
    $this->add_column( 'name', 'string', 'Name', true );
    $this->add_column( 'rank', 'number', 'Rank', true );
    $this->add_column( 'type', 'string', 'Type', true );
    $this->add_column( 'prev_qnaire', 'string', 'Previous', false );
    $this->add_column( 'delay', 'number', 'Delay (weeks)', false );
    $this->add_column( 'phases', 'number', 'Stages', false );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    foreach( $this->get_record_list() as $record )
    {
      $prev_qnaire = 'none';
      if( !is_null( $record->prev_qnaire_id ) )
      {
        $db_prev_qnaire = util::create( 'database\qnaire', $record->prev_qnaire_id );
        $prev_qnaire = $db_prev_qnaire->name;
      }

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'rank' => $record->rank,
               'type' => $record->type,
               'prev_qnaire' => $prev_qnaire,
               'delay' => $record->delay,
               'phases' => $record->get_phase_count() ) );
    }

    $this->finish_setting_rows();
  }
}
?>
