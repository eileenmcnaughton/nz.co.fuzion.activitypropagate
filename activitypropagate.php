<?php

require_once 'activitypropagate.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function activitypropagate_civicrm_config(&$config) {
  _activitypropagate_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function activitypropagate_civicrm_xmlMenu(&$files) {
  _activitypropagate_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function activitypropagate_civicrm_install() {
  return _activitypropagate_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function activitypropagate_civicrm_uninstall() {
  return _activitypropagate_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function activitypropagate_civicrm_enable() {
  return _activitypropagate_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function activitypropagate_civicrm_disable() {
  return _activitypropagate_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function activitypropagate_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _activitypropagate_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function activitypropagate_civicrm_managed(&$entities) {
  return _activitypropagate_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_post
 *
 * Note that the way webform works it creates the activity & then the custom field
 * so we have to use the post hook for webform
 */
function activitypropagate_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  if($objectName != 'Activity' || $op != 'create'
      || $objectRef->activity_type_id !=  _activitypropagate_get_settings('trigger_activity_type_id')
  ) {
    return;
  }

  try{
    $activity = civicrm_api3('activity', 'getsingle', array('id' => $objectRef->id, 'return.target_contact_id' => 1, 'return.custom' => 1,));
    $noActivities = CRM_Utils_Array::value('custom_' . _activitypropagate_get_settings('no_children_custom_field'), $activity, 0);
    $i = 0;
    while($i < $noActivities) {
      _activitypropagate_create_child_activities($activity);
      $i++;
    }
  }
  catch(CiviCRM_API3_Exception $e) {
    CRM_Core_Session::
    
    ssage('Child Activities not created ' . $e->getMessage());
  }
}

/**
 * hook into webform to create activities - because webform doesn't use the api in the normal way
 * we have to handle the possiblity the custom data is created before or after the post hook
 * create custom data
 * @param unknown $op
 * @param unknown $groupID
 * @param unknown $entityID
 * @param unknown $params
 */
function activitypropagate_civicrm_custom( $op, $groupID, $entityID, &$params ) {
  if($op != 'create'
      || $groupID !=  _activitypropagate_get_settings('no_children_custom_group')
  ) {
    return;
  }

  try{
    $activity = civicrm_api3('activity', 'getsingle', array('id' => $entityID,
      'return.target_contact_id' => 1, 'return.custom' => 1,));
    _activitypropagate_create_child_activities($activity);
  }
  catch(CiviCRM_API3_Exception $e) {
    CRM_Core_Session::setStatus('Child Activities not created ' . $e->getMessage());
  }
}

/**
 * Create child activities
 * @param array $activity
 */
function _activitypropagate_create_child_activities($activity) {
  if($activity['activity_type_id'] !=  _activitypropagate_get_settings('trigger_activity_type_id')
    || civicrm_api3('activity', 'getcount', array('parent_id' => $activity['id']))) {
    return;
  }
    $noActivities = CRM_Utils_Array::value('custom_' . _activitypropagate_get_settings('no_children_custom_field'), $activity, 0);
  $i = 0;
  while($i < $noActivities) {
    civicrm_api3('activity', 'create', array(
    'parent_id' => $activity['id'],
    'activity_type_id' => _activitypropagate_get_settings('child_activity_type_id'),
    'campaign_id' => $activity['campaign_id'],
    'assignee_contact_id' => $activity['target_contact_id'][0],
    'status_id' => 1,
    'source_contact_id' => $activity['source_contact_id'],
    'subject' => $activity['subject'],
    ));
    $i++;
  }
}
/**
 * this function is a half-way house between hard-coding & configurability - so we can change here
 * if we later make it configurable
 */
function _activitypropagate_get_settings($setting) {
  $settings = array(
    'trigger_activity_type_id' => 73,
    'child_activity_type_id' => 35,
    'no_children_custom_field' => 147,
    'no_children_custom_group' => 43,
  );
  return $settings[$setting];
}

/**
 * this function is a half-way house between hard-coding & configurability - so we can change here
 * if we later make it configurable
 */
function _activitypropagate_get_activity_assignee($id) {
  if(!_activitypropagate__is_activity_contact()) {

    $sql = "SELECT assignee_id FROM civicrm_assignee_contact ";
  }
}

/**
 * handling for 4.4 - @ the moment not needed
 */
function _activitypropagate__is_activity_contact() {
  return FALSE;
  $sql = "SHOW TABLES LIKE 'civicrm_activity_contact'";
}
