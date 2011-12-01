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
class AuditableBehaviorPeerBuilderModifier
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

  public function staticAttributes($builder)
  {
    $this->setBuilder($builder);
    return <<<EOF
/* is audit active */
protected static \$auditEnabled = true;

/* create label */
const AUDIT_LABEL_CREATE = '{$this->getParameter('create_label')}';

/* update label */
const AUDIT_LABEL_UPDATE = '{$this->getParameter('update_label')}';

/* delete label */
const AUDIT_LABEL_DELETE = '{$this->getParameter('delete_label')}';
EOF;
  }

  public function staticMethods($builder)
  {
    $this->setBuilder($builder);
    return <<<EOF
/**
 * disable audit activity globaly.
 */
static public function disableAudit()
{
  self::\$auditEnabled = false;
}

/**
 * enable audit activity globaly.
 */
static public function enableAudit()
{
  self::\$auditEnabled = true;
}

/**
 * check audit activity is globaly enabled.
 *
 * @return Boolean
 */
static public function isAudited()
{
  return self::\$auditEnabled;
}
EOF;
  }
} // END OF sfContextBehavior
