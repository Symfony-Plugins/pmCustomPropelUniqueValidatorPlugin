<?php

/**
 * pmCustomPropelUniqueValidator validates that the uniqueness of a 2 or more columns.
 *
 * <b>Required parameters:</b>
 *
 * # <b>class</b>         - [none]               - Propel class name.
 * # <b>columns</b>       - [none]               - Array of Propel columns names.
 *
 * <b>Optional parameters:</b>
 *
 * # <b>unique_error</b>  - [Uniqueness error]   - An error message to use when
 *                                                 the value for those columns already
 *                                                 exists in the database.
 *
 * @author Patricio Mac Adden <pmacadden@desarrollo.cespi.unlp.edu.ar>
 * @version SVN
 *
 */

class pmCustomPropelUniqueValidator extends sfValidator
{

  private function parseClassName($class_str)
  {
    $str = strtolower($class_str[0]);

    for ($i = 1; $i < strlen($class_str); $i++) {
      if ($class_str[$i] != strtoupper($class_str[$i]))
        $str .= $class_str[$i];
      else
        $str .= '_'.strtolower($class_str[$i]);
    }

    return $str;
  }

  public function execute (&$value, &$error)
  {
    $className = $this->getParameter('class').'Peer';
    $columns = $this->getParameter('columns');

    $instance = $this->parseClassName($this->getParameter('class'));

    $c = new Criteria();

    foreach ($columns as $column) {
      // if column value is not stored in class[column] parameter.
      if (!($columnValue = $this->getContext()->getRequest()->getParameter($instance.'['.$column.']')))
        // is stored in column parameter
        $columnValue = $this->getContext()->getRequest()->getParameter($column);

      $columnName = call_user_func(array($className, 'translateFieldName'), $column, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_COLNAME);

      $c->add($columnName, $columnValue);
    }

    $object = call_user_func(array($className, 'doSelectOne'), $c);

    if ($object) {
      $tableMap = call_user_func(array($className, 'getTableMap'));
      foreach ($tableMap->getColumns() as $column) {
        if (!$column->isPrimaryKey()) {
          continue;
        }

        $method = 'get'.$column->getPhpName();
        $primaryKey = call_user_func(array($className, 'translateFieldName'), $column->getPhpName(), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_FIELDNAME);
        if ($object->$method() != $this->getContext()->getRequest()->getParameter($primaryKey)) {

          foreach ($columns as $column)
            // if column value is stored in class[column] parameter
            if ($this->getContext()->getRequest()->getParameter($instance.'['.$column.']'))
              $this->getContext()->getRequest()->setError($instance.'{'.$column.'}', $this->getParameter('unique_error'));
            else
              $this->getContext()->getRequest()->setError($column, $this->getParameter('unique_error'));

          $error = $this->getParameter('unique_error');

          return false;
        }
      }
    }
    return true;
  }

  /**
   * Initialize this validator.
   *
   * @param sfContext The current application context.
   * @param array     An associative array of initialization parameters.
   *
   * @return bool     true, if initialization completes successfully, otherwise false.
   */
  public function initialize ($context, $parameters = null)
  {
    // initialize parent
    parent::initialize($context);

    // set default parameters value
    $this->setParameter('unique_error', 'Uniqueness error');

    // set parameters
    $this->getParameterHolder()->add($parameters);

    // check parameters
    if (!$this->getParameter('class'))
      throw new sfValidatorException('The "class" parameter is mandatory for the sfPropelUniqueValidator validator.');

    if (!$this->getParameter('columns'))
      throw new sfValidatorException('The "columns" parameter is mandatory for the sfPropelUniqueValidator validator.');

    return true;
  }

}

?>
