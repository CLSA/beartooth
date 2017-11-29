<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\onyx_instance\activity;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Extends parent class
 */
class query extends \cenozo\service\query
{
  /**
   * Extends parent method
   */
  public function get_leaf_parent_relationship()
  {
    $parent_record = $this->get_parent_record();
    return $parent_record->get_user()->get_relationship( $this->get_leaf_subject() );
  }

  /**
   * Extends parent method
   */
  protected function get_record_count()
  {
    $modifier = clone $this->modifier;
    $this->select->apply_aliases_to_modifier( $modifier );
    return $this->get_parent_record()->get_user()->get_activity_count( $modifier );
  }

  /**
   * Extends parent method
   */
  protected function get_record_list()
  {
    $modifier = clone $this->modifier;
    $this->select->apply_aliases_to_modifier( $modifier );
    return $this->get_parent_record()->get_user()->get_activity_list( $this->select, $modifier );
  }
}
