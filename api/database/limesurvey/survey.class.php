<?php
/**
 * survey.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\database
 * @filesource
 */

namespace beartooth\database\limesurvey;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * Access to limesurvey's survey_SID tables.
 * 
 * @package beartooth\database
 */
class survey extends sid_record
{
  public function get_response( $question_code )
  {
    // the questions table has more than one column in its primary key so custom sql is needed
    $modifier = new db\modifier();
    $modifier->where( 'sid', '=', static::$table_sid );
    $modifier->where( 'title', '=', $question_code );
    $modifier->group( 'sid' );
    $modifier->group( 'gid' );
    $modifier->group( 'qid' );
    $sql = sprintf( 'SELECT gid, qid FROM %s %s',
                    static::db()->get_prefix().'questions',
                    $modifier->get_sql() );
    
    $row = static::db()->get_row( $sql );
    $column_name = sprintf( '%sX%sX%s', static::$table_sid, $row['gid'], $row['qid'] );
    return $this->$column_name;
  }

  /**
   * The name of the table's primary key column.
   * @var string
   * @access protected
   */
  protected static $primary_key_name = 'id';
}
?>
