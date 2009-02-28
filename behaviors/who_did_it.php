<?php
/**
 * WhoDidIt Model Behavior
 *
 * Handles created_by, modified_by fields for a given Model, if they exist in the Model DB table.
 * It's similar to the created, modified automagic, but it stores the logged User id
 * in the models that actAs = array('WhoDidIt')
 * 
 * This is useful to track who created records, and the last user that has changed them
 *
 * @package behaviors
 * @author Daniel Vecchiato
 * @version 1.1
 * @date 28/02/2009
 * @copyright http://www.4webby.com
 * @licence MIT
 **/
class WhoDidItBehavior extends ModelBehavior {
/**
   * Default settings for a model that has this behavior attached.
   *
   * @var array
   * @access protected
   */
  protected $_defaults = array(
    'auth_session' => 'Auth',  //name of Auth session
    'user_model' => 'User',    //name of User model
	'created_by_field' => 'created_by',
	'modified_by_field' => 'modified_by'
  );
/**
 * Initiate WhoMadeIt Behavior
 *
 * @param object $model
 * @param array $config  behavior settings you would like to override
 * @return void
 * @access public
 */
	function setup(&$model, $config = array()) {
		//assigne default settings
		$this->settings[$model->alias] = $this->_defaults;
		
		//merge custom config with default settings
		$this->settings[$model->alias] = array_merge($this->settings[$model->alias], (array)$config);
		
		$hasFieldCreatedBy = $model->hasField($this->settings[$model->alias]['created_by_field']);
		$hasFieldModifiedBy = $model->hasField($this->settings[$model->alias]['modified_by_field']);
		
		$this->settings[$model->alias]['has_created_by'] = $hasFieldCreatedBy;
		$this->settings[$model->alias]['has_modified_by'] = $hasFieldModifiedBy;
		
		if ($hasFieldCreatedBy) {
			$commonBelongsTo = array(
				'CreatedBy' => array('className' => $this->settings[$model->alias]['user_model'],
									'foreignKey' => $this->settings[$model->alias]['created_by_field'])
									);
			$model->bindModel(array('belongsTo' => $commonBelongsTo), false);
		}
		
		if ($hasFieldModifiedBy) {
			$commonBelongsTo = array(
				'ModifiedBy' => array('className' => $this->settings[$model->alias]['user_model'],
									'foreignKey' => $this->settings[$model->alias]['modified_by_field']));
			$model->bindModel(array('belongsTo' => $commonBelongsTo), false);
		}
		
	}
/**
 * Before save callback
 *
 * @param object $model Model using this behavior
 * @return boolean True if the operation should continue, false if it should abort
 * @access public
 */
	function beforeSave(&$model) {
		if ($this->settings[$model->alias]['has_created_by'] || $this->settings[$model->alias]['has_modified_by']) {
			$AuthSession = $this->settings[$model->alias]['auth_session'];
			$UserSession = $this->settings[$model->alias]['user_model'];
			$userId = Set::extract($_SESSION, $AuthSession.'.'.$UserSession.'.'.'id');
			if ($userId) {
				$data = array($this->settings[$model->alias]['modified_by_field'] => $userId);
				if (!$model->exists()) {
					$data[$this->settings[$model->alias]['created_by_field']] = $userId;
				}
				$model->set($data);
			}
		}
		return true;
	}
}
?>