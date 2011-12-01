AuditableBehavior
================

Configuration
-------------

Following paramters are available :

* **create_label** : activity log for create (default CREATE)
* **update_label** : activity log for update (default UPDATE)
* **delete_label** : activity log for delete (default DELETE)
* **audit_create** : log object creation (default true)
* **audit_update** : log object update (default true)
* **audit_delete** : log object deletion (default true)
* **activity_table** : activity table name (default audit_activity)
* **activity_label_column** : column for activity log in **activity_table**
* **object_column** : column for object table in **activity_table**
* **object_pk_column** : column for object primarikey in **activity_table**

How it works
------------

This behavior create an activity table and log activity for auditableed object.

To achieve this goal, method ```logActivity($label, $con=null)``` is called on postHooks.

This method create a new ```MonitorActivity``` with following parameters:

* ```auditable_label``` corresponding value for CREATE, UPDATE or DELETE
* ```auditable_object_class``` corresponding object class
* ```auditable_object_pk``` corresponding object primary key

Built in methods
----------------

### Active record extension

* ```getLastActivity($number=10, $label=null, $con=null)``` return the recent object related activity.
* ```logActivity($label, $con=null)``` create an activity entry for $label
* ```countActivity($con=null)``` count related activity
* ```isAudited()``` is the current objcet auditableing its activity
* ```disableLocalAudit()``` temporary remove auditableing activity for this object
* ```enableLocalAudit()``` temporary force auditableing activity for this object
* ```getActivityCriteria``` get a criteria to filter activity against this object

and both static methods

* ```disableAudit()``` disable auditableing activity globaly.
* ```enableAudit()``` enable auditableing activity globaly.

Activity table
--------------

### Active Query extension

* ```filterByObject($auditableedObject)``` filter query for $auditableObject related activity.

### Active record extension

* ```getRelatedObject()``` return the activity related object.

TODO
----

* Handle composite PK
* alter activty table
