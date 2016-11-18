<?php
/**
 * assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * assignment: record
 */
class assignment extends \cenozo\database\assignment
{
  /**
   * Overrides the parent save method.
   */
  public function save()
  {
    parent::save();
    $this->get_interview()->get_participant()->repopulate_queue( true );
  }

  /**
   * Override the parent method
   */
  public function delete()
  {
    $db_participant = $this->get_interview()->get_participant();
    parent::delete();
    $db_participant->repopulate_queue( true );
  }
}
