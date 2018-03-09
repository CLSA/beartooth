<?php
/**
 * get.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\interview;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Extends parent class
 */
class get extends \cenozo\service\get
{
  /**
   * Extends parent method
   */
  protected function execute()
  {
    if( $this->get_argument( 'last_interview_note', false ) )
    {
      $note = NULL;

      // get the interview notes from the previous application
      $db_application = lib::create( 'business\session' )->get_application();
      $db_prev_application = $db_application->get_previous_application();
      if( $db_prev_application->version == $db_application->version )
      {
        $db_interview = $this->get_leaf_record();
        $cenozo_manager = lib::create( 'business\cenozo_manager', $db_application->get_previous_application() );
        $data = $cenozo_manager->send( sprintf(
          'participant/uid=%s/interview/qnaire_rank=%d?select={"column":["note"]}',
          $db_interview->get_participant()->uid,
          $db_interview->get_qnaire()->rank
        ) );
        $note = $data->note;
      }

      $this->set_data( $note );
    }
    else parent::execute();
  }
}
