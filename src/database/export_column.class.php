<?php
/**
 * export_column.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * export_column: record
 */
class export_column extends \cenozo\database\export_column
{
  /**
   * Extend the parent method
   */
  public function apply_select( $select )
  {
    if( $this->include && 'interviewing_instance_id' == $this->column_name )
    {
      $select->add_column(
        sprintf( '%s_interviewing_instance_user.name', $this->get_table_alias() ),
        $this->get_column_alias(),
        false
      );
    }
    else
    {
      parent::apply_select( $select );
    }
  }

  /**
   * Extend the parent method
   */
  public function apply_modifier( $modifier )
  {
    parent::apply_modifier( $modifier );

    // the interviewing_instance_id column means we're looking for the II's username
    if( $this->include && 'interviewing_instance_id' == $this->column_name )
    {
      // first determine the interviewing_instance table alias
      $table_name = $this->get_table_alias();

      // now join to the user table from the interviewing instance table
      $joining_table_name = sprintf( '%s_interviewing_instance_user', $table_name );
      if( !$modifier->has_join( $joining_table_name ) )
      {
        $modifier->join(
          'user',
          sprintf( '%s_interviewing_instance.user_id', $table_name ),
          $joining_table_name.'.id',
          'left',
          $joining_table_name
        );
      }
    }
  }

  /**
   * Extend the parent method
   */
  public function get_column_alias()
  {
    $column_alias = NULL;

    if( 'interview' == $this->table_name )
    {
      $alias_parts = array(
        // get the qnaire name
        lib::create( 'database\qnaire', $this->subtype )->name,
        $this->table_name,
        preg_replace( '/_id$/', '', $this->column_name )
      );

      $column_alias = ucWords( str_replace( '_', ' ', implode( ' ', $alias_parts ) ) );
    }
    else
    {
      $column_alias = parent::get_column_alias();
    }

    return $column_alias;
  }
}
