<?php
/**
 * This file declare the AuditableBehavior class.
 *
 * @package Loopkey
 * @subpackage lib-propel-behavior
 * @author Julien Muetton <julien_muetton@carpe-hora.com>
 * @copyright (c) Carpe Hora SARL 2011
 * @since 2011-11-30
 */

require_once __DIR__ . '/AuditableBehaviorPeerModifier.php';
require_once __DIR__ . '/AuditableBehaviorObjectModifier.php';
/**
 * Monitor records activity
 */
class AuditableBehavior extends Behavior
{
    /** parameters default values */
    protected $parameters = array(
        'create_label'     => 'CREATE',
        'update_label'     => 'UPDATE',
        'delete_label'     => 'DELETE',
        'audit_create'    => 'true',
        'audit_update'    => 'true',
        'audit_delete'    => 'true',
        'activity_table'    => 'audit_activity',
        'activity_label_column'   => 'label',
        'object_column'     => 'object_class',
        'object_pk_column'  => 'object_pk',
        'created_at_column' => 'created_at',
      );

    protected $activityTable, $peerBuilderModifier, $objectBuilderModifier;

    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getTables() as $table) {
            if ($table->hasBehavior($this->getName())) {
              // don't add the same behavior twice
              continue;
            }
            if (property_exists($table, 'isActivityTable') ||
                $this->getParameter('activity_table') === $table->getName()) {
              // don't add the behavior to activity talbe
              continue;
            }
            $b = clone $this;
            $table->addBehavior($b);
      }
    }

    public function modifyTable()
    {
        $this->addActivityTable();
    }

    public function addActivityTable()
    {
        $table = $this->getTable();
        $database = $table->getDatabase();
        $activityTableName = $this->getParameter('activity_table');

        if (!$database->hasTable($activityTableName)) {
            $activityTable = $database->addTable(array(
                  'name'      => $activityTableName,
                  'package'   => $table->getPackage(),
                  'schema'    => $table->getSchema(),
                  'namespace' => $table->getNamespace() ? '\\' . $table->getNamespace() : null,
            ));

            $activityTable->isActivityTable = true;

            // add PK column
            $pk = $activityTable->addColumn(array(
                'name'					=> 'id',
                'autoIncrement' => 'true',
                'type'					=> 'INTEGER',
                'primaryKey'    => 'true'
            ));
            $pk->setNotNull(true);
            $pk->setPrimaryKey(true);

            $activityTable->addColumn(array(
                'name'          => $this->getParameter('activity_label_column'),
                'type'					=> 'VARCHAR',
                'size'          => 255,
            ));

            $activityTable->addColumn(array(
                'name'          => $this->getParameter('object_column'),
                'type'					=> 'VARCHAR',
                'size'          => 255,
            ));

            $activityTable->addColumn(array(
                'name'          => $this->getParameter('object_pk_column'),
                'type'					=> 'VARCHAR',
                'size'          => 50,
            ));

            $activityTable->addColumn(array(
                'name'          => $this->getParameter('created_at_column'),
                'type'					=> 'TIMESTAMP',
            ));

            // every behavior adding a table should re-execute database behaviors
            foreach ($database->getBehaviors() as $behavior) {
                $behavior->modifyDatabase();
            }

            $this->activityTable = $activityTable;
        }
        else {
            $this->activityTable = $database->getTable($activityTableName);
            $this->activityTable->isActivityTable = true;
        }
    }

    /* builder shortcuts */

    public function getActivityTable()
    {
        return $this->activityTable;
    }

    public function getActivityTableName()
    {
        return $this->activityTable->getPhpName();
    }

    public function getActivityColumnForParameter($parameter)
    {
        return $this->activityTable->getColumn($this->getParameter($parameter));
    }

    public function getOrderByActivityColumnForParameter($parameter)
    {
        return sprintf('orderBy%s', ucfirst($this->getActivityColumnForParameter($parameter)->getPhpName()));
    }

    public function getFilterByActivityColumnForParameter($parameter)
    {
        return sprintf('filterBy%s', ucfirst($this->getActivityColumnForParameter($parameter)->getPhpName()));
    }

    public function getGetterForActivityColumnForParameter($parameter)
    {
        return sprintf('get%s', ucfirst($this->getActivityColumnForParameter($parameter)->getPhpName()));
    }

    public function getSetterForActivityColumnForParameter($parameter)
    {
        return sprintf('set%s', ucfirst($this->getActivityColumnForParameter($parameter)->getPhpName()));
    }

    public function getObjectBuilderModifier()
    {
      if (is_null($this->objectBuilderModifier))
      {
        $this->objectBuilderModifier = new AuditableBehaviorObjectBuilderModifier($this);
      }
      return $this->objectBuilderModifier;
    }

    public function getPeerBuilderModifier()
    {
      if (is_null($this->peerBuilderModifier))
      {
        $this->peerBuilderModifier = new AuditableBehaviorPeerBuilderModifier($this);
      }
      return $this->peerBuilderModifier;
    }
}
