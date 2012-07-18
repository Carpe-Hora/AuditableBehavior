<?php

/**
 * This file declare the AuditableBehaviorObjectBuilderModifier class.
 *
 * @copyright (c) Carpe Hora SARL 2011
 * @since 2011-11-25
 * @license     MIT License
 */

/**
 * @author Julien Muetton <julien_muetton@carpe-hora.com>
 * @package propel.generator.behavior.auditable
 */
class AuditableBehaviorObjectBuilderModifier
{
  protected $behavior, $table, $builder, $objectClassname, $peerClassname;

  public function __construct($behavior)
  {
    $this->behavior = $behavior;
    $this->table = $behavior->getTable();
  }

	protected function setBuilder($builder)
	{
		$this->builder = $builder;
		$this->objectClassname = $builder->getStubObjectBuilder()->getClassname();
		$this->queryClassname = $builder->getStubQueryBuilder()->getClassname();
		$this->peerClassname = $builder->getStubPeerBuilder()->getClassname();
	}

  protected function getParameter($key)
  {
    return $this->behavior->getParameter($key);
  }

  protected function getActivityTable()
  {
    return $this->behavior->getActivityTable();
  }

  protected function getActivityTableName()
  {
    return $this->behavior->getActivityTableName();
  }

  public function getActivityActiveRecordClassname()
  {
    $activityTable = $this->getActivityTable();
    return $this->builder->getNewStubObjectBuilder($activityTable)->getClassname();
  }

  public function getActivityActiveQueryClassname()
  {
    $activityTable = $this->getActivityTable();
    return $this->builder->getNewStubQueryBuilder($activityTable)->getClassname();
  }


  protected function getActivityColumnForParameter($parameter)
  {
    return $this->behavior->getActivityColumnForParameter($parameter);
  }

  protected function getOrderByActivityColumnForParameter($parameter)
  {
    return $this->behavior->getOrderByActivityColumnForParameter($parameter);
  }

  protected function getFilterByActivityColumnForParameter($parameter)
  {
    return $this->behavior->getFilterByActivityColumnForParameter($parameter);
  }

  protected function getGetterForActivityColumnForParameter($parameter)
  {
    return $this->behavior->getGetterForActivityColumnForParameter($parameter);
  }

  protected function getSetterForActivityColumnForParameter($parameter)
  {
    return $this->behavior->getSetterForActivityColumnForParameter($parameter);
  }

  protected function getPkValueForActivityColumn()
  {
    $pk = $this->table->getPrimaryKey();
    $this->builder->getStubObjectBuilder();
    $getters = array();
    foreach($pk as $key => $column)
    {
      $getters[] = sprintf('$this->get%s()', ucfirst($column->getPhpName()));
    }
    return join(".'-'.", $getters);
  }

  /* here starts the modifier methods */

  public function objectAttributes($builder)
  {
    $this->setBuilder($builder);

    return <<<EOF
/** local audit */
protected \$auditEnabled = null;
EOF;
  }

  public function objectMethods($builder)
  {
    $this->setBuilder($builder);

    return <<<EOF
/**
 * create a new related activity record
 *
 * @param String    \$activity_label the activity to report.
 * @param PropelPDO \$con            the connection to use.
 * @return {$this->objectClassname}
 */
public function logActivity(\$activity_label, \$con = null)
{
  if (!\$this->isAudited()) {
    return \$this;
  }
  if (\$this->isNew()) {
    throw new PropelException('Unable to log activity on new object {$this->table->getPhpName()}.');
  }
  \$activity = new {$this->getActivityActiveRecordClassname()}();
  \$activity->{$this->getSetterForActivityColumnForParameter('activity_label_column')}(\$activity_label);
  \$activity->{$this->getSetterForActivityColumnForParameter('object_column')}('{$this->table->getPhpName()}');
  \$activity->{$this->getSetterForActivityColumnForParameter('object_pk_column')}({$this->getPkValueForActivityColumn()});
  \$activity->{$this->getSetterForActivityColumnForParameter('created_at_column')}(time());

  \$activity->save(\$con);
  return \$this;
}

/**
 * create an activity criteria to filter on this {$this->objectClassname} activity.
 */
public function getActivityCriteria()
{
  return {$this->getActivityActiveQueryClassname()}::create()
    ->{$this->getFilterByActivityColumnForParameter('object_column')}('{$this->table->getPhpName()}')
    ->{$this->getFilterByActivityColumnForParameter('object_pk_column')}({$this->getPkValueForActivityColumn()});
}

/**
 * Retrieve last activity for this {$this->objectClassname}.
 *
 * @param Integer   \$number number of {$this->getActivityActiveRecordClassname()} to return.
 * @param String    \$label  label of activity to return.
 * @param PropelPDO \$con    the connection to use.
 * @return PropelCollection
 */
public function getLastActivity(\$number = 10, \$label = null, \$con = null)
{
  \$query = \$this->getActivityCriteria()
    ->{$this->getOrderByActivityColumnForParameter('created_at_column')}('desc');
  if (!is_null(\$label)) {
    \$query->{$this->getFilterByActivityColumnForParameter('activity_label_column')}(\$label);
  }
  if (0 !== \$number) {
    \$query->limit(\$number);
  }
  return \$query->find(\$con);
}

/**
 * Counts activity actions for this {$this->objectClassname}.
 *
 * @param String    \$label  label of activity to return.
 * @param PropelPDO \$con    the connection to use.
 * @return Integer
 */
public function countActivity(\$label = null, \$con = null)
{
  \$query = \$this->getActivityCriteria();
  if (!is_null(\$label)) {
    \$query->{$this->getFilterByActivityColumnForParameter('activity_label_column')}(\$label);
  }
  return \$query->count(\$con);
}

/**
 * is current object audit it's activity ?
 *
 * @return Boolean
 */
public function isAudited()
{
  return is_null(\$this->auditEnabled) ? {$this->peerClassname}::isAudited() : \$this->auditEnabled;
}

/**
 * disable audit activity localy.
 *
 * @return {$this->objectClassname}
 */
public function disableLocalAudit()
{
  \$this->auditEnabled = false;
  return \$this;
}

/**
 * enable audit activity localy.
 *
 * @return {$this->objectClassname}
 */
public function enableLocalAudit()
{
  \$this->auditEnabled = true;
  return \$this;
}
EOF;
  }

  public function postInsert($builder)
  {
    $this->setBuilder($builder);

    if (!$this->getParameter('audit_create'))
    {
      return '';
    }

    return <<<EOF
// AuditableBehavior
\$this->logActivity({$this->peerClassname}::AUDIT_LABEL_CREATE, \$con);
EOF;
  }

  public function postUpdate($builder)
  {
    $this->setBuilder($builder);

    if (!$this->getParameter('audit_update'))
    {
      return '';
    }

    return <<<EOF
// AuditableBehavior
if (\$affectedRows) {
  \$this->logActivity({$this->peerClassname}::AUDIT_LABEL_UPDATE, \$con);
}
EOF;
  }

  public function postDelete($builder)
  {
    $this->setBuilder($builder);

    if (!$this->getParameter('audit_delete'))
    {
      return '';
    }

    return <<<EOF
// AuditableBehavior
if (!\$this->isNew()) {
  \$this->logActivity({$this->peerClassname}::AUDIT_LABEL_DELETE, \$con);
}
EOF;
  }
}
