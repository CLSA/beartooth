<?php
/**
 * overview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\business\overview;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * overview: completed_interview
 */
class completed_interview extends \cenozo\business\overview\base_overview
{
  /**
   * Implements abstract method
   */
  protected function build( $modifier = NULL )
  {
    $interview_class_name = lib::get_class_name( 'database\interview' );

    // get a list of all sites
    $site_mod = lib::create( 'database\modifier' );
    $site_mod->order( 'name' );
    $site_sel = lib::create( 'database\select' );
    $site_sel->add_table_column( 'site', 'name' );
    $site_list = lib::create( 'business\session' )->get_application()->get_site_list( $site_sel, $site_mod );

    $now_column = 'CONVERT_TZ( UTC_TIMESTAMP() - INTERVAL 7 DAY, "UTC", site.timezone )';
    $datetime_column = 'CONVERT_TZ( end_datetime, "UTC", site.timezone )';
    $date_column = sprintf( 'DATE( %s )', $datetime_column );
    $date = util::get_datetime_object();
    for( $day = 0; $day < 7; $day++ )
    {
      // count the number of completes for this type/day/site
      $select = lib::create( 'database\select' );
      $select->add_table_column( 'qnaire', 'type' );
      $select->add_table_column( 'site', 'name' );
      $select->add_column( 'COUNT(*)', 'total', false );

      $modifier = lib::create( 'database\modifier' );
      $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
      $modifier->join( 'site', 'interview.site_id', 'site.id' );
      $modifier->where( $date_column, '=', $date->format( 'Y-m-d' ) );
      $modifier->group( 'qnaire.type' );
      $modifier->group( 'site.name' );

      $data = ['home' => [], 'site' => []];
      foreach( $interview_class_name::select( $select, $modifier ) as $row )
        $data[$row['type']][$row['name']] = $row['total'];

      // add a root node for this date along with home and site child nodes
      $node = $this->add_root_item( $date->format( 'l, F jS' ) );
      $home_node = $this->add_item( $node, 'Home Interview' );
      $site_node = $this->add_item( $node, 'Site Interview' );

      foreach( $site_list as $site )
      {
        $this->add_item(
          $home_node,
          $site['name'],
          array_key_exists( $site['name'], $data['home'] ) ? $data['home'][$site['name']] : 0
        );
        $this->add_item(
          $site_node,
          $site['name'],
          array_key_exists( $site['name'], $data['site'] ) ? $data['site'][$site['name']] : 0
        );
      }

      $date->sub( new \DateInterval( 'P1D' ) );
    }
  }
}
