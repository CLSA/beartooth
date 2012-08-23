<?php
/**
 * interview_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget interview list
 */
class interview_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the interview list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'interview', $args );
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
    
    $this->add_column( 'participant.uid', 'string', 'UID', true );
    $this->add_column( 'qnaire.name', 'string', 'Questionnaire', true );
    $this->add_column( 'completed', 'boolean', 'Completed', true );

    $this->extended_site_selection = true;
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
    
    foreach( $this->get_record_list() as $record )
    {
      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'participant.uid' => $record->get_participant()->uid,
               'qnaire.name' => $record->get_qnaire()->name,
               'completed' => $record->completed ) );
    }
  }
}
?>
