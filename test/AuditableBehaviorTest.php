<?php
/*
 *	$Id: VersionableBehaviorTest.php 1460 2010-01-17 22:36:48Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

$_SERVER['PROPEL_DIR'] = dirname(__FILE__) . '/../../../../plugins/sfPropelORMPlugin/lib/vendor/propel/';
$propel_dir = isset($_SERVER['PROPEL_DIR']) ? $_SERVER['PROPEL_DIR'] : dirname(__FILE__) . '/../../../../../plugins/sfPropelORMPlugin/lib/vendor/propel/';
$behavior_dir = file_exists(__DIR__ . '/../src/')
                    ? __DIR__ . '/../src'
                    : $propel_dir . '/generator/lib/behavior/auditable';

require_once $propel_dir . '/runtime/lib/Propel.php';
require_once $propel_dir . '/generator/lib/util/PropelQuickBuilder.php';
require_once $propel_dir . '/generator/lib/util/PropelPHPParser.php';
require_once $behavior_dir . '/AuditableBehavior.php';

/**
 * Test for AuditableBehavior
 *
 * @author     Julien Muetton
 * @version    $Revision$
 * @package    generator.behavior.sf_context
 */
class AuditableBehaviorTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
  	if (!class_exists('AuditableBehaviorTest1')) {
      $schema = <<<EOF
<database name="auditable_behavior_test_applied_on_table">
  <table name="auditable_behavior_test_1">
    <column name="id" type="INTEGER" primaryKey="true" autoincrement="true" />
    <column name="name" type="VARCHAR" size="255" />
    <behavior name="auditable" />
  </table>
</database>
EOF;
			PropelQuickBuilder::buildSchema($schema);
    }
  	if (!class_exists('AuditableBehaviorTest2')) {
      $schema = <<<EOF
<database name="auditable_behavior_test_applied_on_database">
  <behavior name="auditable">
    <parameter name="activity_table" value="auditable_behavior_test_applied_on_database_activity" />
    <parameter name="activity_label_column" value="activity_label" />
    <parameter name="object_column" value="object_classname" />
    <parameter name="object_pk_column" value="related_pk" />
    <parameter name="created_at_column" value="created_at" />
  </behavior>
  <table name="auditable_behavior_test_applied_on_database_activity" phpName="DatabaseActivity">
    <column name="id" type="INTEGER" primaryKey="true" autoincrement="true" />
    <column name="activity_label" type="VARCHAR" size="255" />
    <column name="object_classname" type="VARCHAR" size="255" />
    <column name="related_pk" type="VARCHAR" size="20" />
    <column name="created_at" type="TIMESTAMP" />
  </table>
  <table name="auditable_behavior_test_2">
    <column name="id" type="INTEGER" primaryKey="true" autoincrement="true" />
    <column name="name" type="VARCHAR" size="255" />
  </table>
  <table name="auditable_behavior_test_3">
    <column name="id" type="INTEGER" primaryKey="true" autoincrement="true" />
    <column name="name" type="VARCHAR" size="255" />
  </table>
  <table name="auditable_behavior_test_4" isCrossRef="true">
    <column name="auditable_behavior_test_2_id" type="integer" primaryKey="true" />
    <foreign-key foreignTable="auditable_behavior_test_2" onDelete="cascade">
      <reference local="auditable_behavior_test_2_id" foreign="id" />
    </foreign-key>
    <column name="auditable_behavior_test_3_id" type="integer" primaryKey="true" />
    <foreign-key foreignTable="auditable_behavior_test_3" onDelete="cascade">
      <reference local="auditable_behavior_test_3_id" foreign="id" />
    </foreign-key>
  </table>
  <table name="auditable_behavior_test_5">
    <column name="id" type="INTEGER" primaryKey="true" autoincrement="true" />
    <column name="name" type="VARCHAR" size="255" />
    <column name="auditable_behavior_test_2_id" type="integer" />
    <foreign-key foreignTable="auditable_behavior_test_2" onDelete="cascade">
      <reference local="auditable_behavior_test_2_id" foreign="id" />
    </foreign-key>
  </table>
</database>
EOF;
			PropelQuickBuilder::buildSchema($schema);
    }
  }

  function testActiveRecordMethods()
  {
    $this->assertTrue(method_exists('AuditableBehaviorTest1', 'disableLocalAudit'));
    $this->assertTrue(method_exists('AuditableBehaviorTest1', 'enableLocalAudit'));
    $this->assertTrue(method_exists('AuditableBehaviorTest1', 'isAudited'));
    $this->assertTrue(method_exists('AuditableBehaviorTest1', 'getLastActivity'));
    $this->assertTrue(method_exists('AuditableBehaviorTest1', 'countActivity'));
    $this->assertTrue(method_exists('AuditableBehaviorTest1', 'getActivityCriteria'));
    $this->assertTrue(method_exists('AuditableBehaviorTest1', 'logActivity'));
  }

  function testPeerMethods()
  {
    $this->assertTrue(method_exists('AuditableBehaviorTest1Peer', 'disableAudit'));
    $this->assertTrue(method_exists('AuditableBehaviorTest1Peer', 'enableAudit'));
    $this->assertTrue(method_exists('AuditableBehaviorTest1Peer', 'isAudited'));
  }

  function testApplyOnEveryTableButActivityWhenAppliedToDatabase()
  {
    $this->assertTrue(method_exists('AuditableBehaviorTest2', 'disableLocalAudit'));
    $this->assertTrue(method_exists('AuditableBehaviorTest3', 'disableLocalAudit'));
    $this->assertTrue(method_exists('AuditableBehaviorTest2Peer', 'disableAudit'));
    $this->assertTrue(method_exists('AuditableBehaviorTest3Peer', 'disableAudit'));
    $this->assertFalse(method_exists('AuditableBehaviorTestAppliedOnDatabaseActivity', 'disableLocalAudit'));
  }

  function testAuditActivity()
  {
    $o = new AuditableBehaviorTest1();
    $o->setName('foo');
    $o->save();
    $this->assertEquals(1, $o->countActivity());
    $this->assertEquals(1, $o->countActivity(AuditableBehaviorTest1Peer::AUDIT_LABEL_CREATE));
    $o->setName('bar');
    $o->save();
    $this->assertEquals(2, $o->countActivity());
    $this->assertEquals(1, $o->countActivity(AuditableBehaviorTest1Peer::AUDIT_LABEL_UPDATE));
    $o->logActivity('TEST');
    $this->assertEquals(3, $o->countActivity());
    $this->assertEquals(1, $o->countActivity('TEST'));
    $o->delete();
    $this->assertEquals(4, $o->countActivity());
    $this->assertEquals(1, $o->countActivity(AuditableBehaviorTest1Peer::AUDIT_LABEL_DELETE));
  }

  function testGetLastActivity()
  {
    $o1 = new AuditableBehaviorTest1();
    $o1->setName('bar');
    $o1->save();
    $o = new AuditableBehaviorTest1();
    $o->setName('foo');
    $o->save();
    for ($i = 0; $i < 20; $i++)
    {
      $o->logActivity('CONNECTION');
    }
    $this->assertEquals(21, $o->countActivity());
    $this->assertEquals(20, $o->countActivity('CONNECTION'));
    $this->assertEquals(10, count($o->getLastActivity()));
    $this->assertEquals(10, count($o->getLastActivity(10, 'CONNECTION')));
    $this->assertEquals(1, count($o->getLastActivity(10, 'CREATE')));
  }

  /**
	 * @expectedException PropelException
   */
  function testNotAbleToLogOnNewObject()
  {
    $o = new AuditableBehaviorTest2();
    $o->setName('bar');
    $o->logActivity('CONNECTION');
  }

  function testCompositePrimaryKey()
  {
    $a = new AuditableBehaviorTest2();
    $a->setName('bar');
    $a->save();
    $b = new AuditableBehaviorTest3();
    $b->setName('bar');
    $b->save();
    $o = new AuditableBehaviorTest4();
    $o->setAuditableBehaviorTest2($a);
    $o->setAuditableBehaviorTest3($b);
    $o->save();
    $this->assertEquals(1, $a->countActivity());
    $this->assertEquals(1, $b->countActivity());
    $this->assertEquals(1, $o->countActivity());
  }

  function testOneToManyRelationship()
  {
    $a = new AuditableBehaviorTest5();
    $a->setName('foo');
    $a->save();
    $this->assertEquals(1, $a->countActivity());
    $b = new AuditableBehaviorTest2();
    $b->setName('bar');
    $b->save();
    $a->setAuditableBehaviorTest2($b);
    $a->save();
    $this->assertEquals(2, $a->countActivity());
    $this->assertEquals(1, $b->countActivity());

    $a = new AuditableBehaviorTest5();
    $a->setName('foo');
    $b = new AuditableBehaviorTest2();
    $b->setName('bar');
    $a->setAuditableBehaviorTest2($b);
    $a->save();
    $this->assertEquals(1, $a->countActivity());
    $this->assertEquals(1, $b->countActivity());
  }

  function testManyToManyRelationship()
  {
    $b1 = new AuditableBehaviorTest2();
    $b1->setName('bar');
    $b2 = new AuditableBehaviorTest2();
    $b2->setName('bar');
    $o = new AuditableBehaviorTest3();
    $o->setName('bar');
    $o->addAuditableBehaviorTest2($b1);
    $o->addAuditableBehaviorTest2($b2);
    $o->save();

    $this->assertEquals(1, $o->countActivity());
    $this->assertEquals(1, $b1->countActivity());
    $this->assertEquals(1, $b1->countActivity());

    $links = $o->getAuditableBehaviorTest4s();
    $this->assertEquals(2, count($links));
    foreach ($links as $link) {
      $this->assertEquals(1, $link->countActivity());
    }
  }
}
