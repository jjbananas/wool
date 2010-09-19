<?php
WoolTable::$schema = array (
  'access_locations' => 
  array (
    'accessLocationId' => 
    array (
      'name' => 'accessLocationId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => true,
      'auto_increment' => true,
      'additional' => false,
      'unsigned' => true,
    ),
    'resource' => 
    array (
      'name' => 'resource',
      'default' => NULL,
      'nullable' => false,
      'type' => 'varchar',
      'length' => '150',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'accessRoleId' => 
    array (
      'name' => 'accessRoleId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'createdOn' => 
    array (
      'name' => 'createdOn',
      'default' => NULL,
      'nullable' => false,
      'type' => 'datetime',
      'length' => NULL,
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'modifiedOn' => 
    array (
      'name' => 'modifiedOn',
      'default' => NULL,
      'nullable' => false,
      'type' => 'datetime',
      'length' => NULL,
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
  ),
  'access_roles' => 
  array (
    'accessRoleId' => 
    array (
      'name' => 'accessRoleId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => true,
      'auto_increment' => true,
      'additional' => false,
      'unsigned' => true,
    ),
    'roleName' => 
    array (
      'name' => 'roleName',
      'default' => NULL,
      'nullable' => false,
      'type' => 'varchar',
      'length' => '50',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'loginUrl' => 
    array (
      'name' => 'loginUrl',
      'default' => NULL,
      'nullable' => false,
      'type' => 'varchar',
      'length' => '150',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'deniedUrl' => 
    array (
      'name' => 'deniedUrl',
      'default' => NULL,
      'nullable' => false,
      'type' => 'varchar',
      'length' => '150',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
  ),
  'access_roles_users' => 
  array (
    'accessRoleId' => 
    array (
      'name' => 'accessRoleId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => true,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'userId' => 
    array (
      'name' => 'userId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => true,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
  ),
  'article_revisions' => 
  array (
    'articleRevisionId' => 
    array (
      'name' => 'articleRevisionId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => true,
      'auto_increment' => true,
      'additional' => false,
      'unsigned' => true,
    ),
    'articleId' => 
    array (
      'name' => 'articleId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'authorId' => 
    array (
      'name' => 'authorId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'title' => 
    array (
      'name' => 'title',
      'default' => NULL,
      'nullable' => false,
      'type' => 'varchar',
      'length' => '80',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'content' => 
    array (
      'name' => 'content',
      'default' => NULL,
      'nullable' => false,
      'type' => 'mediumtext',
      'length' => NULL,
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'excerpt' => 
    array (
      'name' => 'excerpt',
      'default' => NULL,
      'nullable' => true,
      'type' => 'text',
      'length' => '65535',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'createdOn' => 
    array (
      'name' => 'createdOn',
      'default' => NULL,
      'nullable' => true,
      'type' => 'datetime',
      'length' => NULL,
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'publishedOn' => 
    array (
      'name' => 'publishedOn',
      'default' => NULL,
      'nullable' => true,
      'type' => 'datetime',
      'length' => NULL,
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
  ),
  'article_types' => 
  array (
    'articleTypeId' => 
    array (
      'name' => 'articleTypeId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => true,
      'auto_increment' => true,
      'additional' => false,
      'unsigned' => true,
    ),
    'name' => 
    array (
      'name' => 'name',
      'default' => NULL,
      'nullable' => false,
      'type' => 'varchar',
      'length' => '50',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
  ),
  'articles' => 
  array (
    'articleId' => 
    array (
      'name' => 'articleId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => true,
      'auto_increment' => true,
      'additional' => false,
      'unsigned' => true,
    ),
    'type' => 
    array (
      'name' => 'type',
      'default' => 'wiki',
      'nullable' => false,
      'type' => 'enum',
      'length' => NULL,
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'options' => 
      array (
        0 => 'wiki',
        1 => 'blog',
      ),
    ),
    'location' => 
    array (
      'name' => 'location',
      'default' => NULL,
      'nullable' => false,
      'type' => 'varchar',
      'length' => '100',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'createdOn' => 
    array (
      'name' => 'createdOn',
      'default' => NULL,
      'nullable' => false,
      'type' => 'datetime',
      'length' => NULL,
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'deleted' => 
    array (
      'name' => 'deleted',
      'default' => 'N',
      'nullable' => false,
      'type' => 'enum',
      'length' => NULL,
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'options' => 
      array (
        0 => 'Y',
        1 => 'N',
      ),
    ),
  ),
  'forum_messages' => 
  array (
    'id' => 
    array (
      'name' => 'id',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => true,
      'auto_increment' => true,
      'additional' => false,
      'unsigned' => true,
    ),
    'attachedTo' => 
    array (
      'name' => 'attachedTo',
      'default' => NULL,
      'nullable' => false,
      'type' => 'varchar',
      'length' => '30',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'attachId' => 
    array (
      'name' => 'attachId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'userId' => 
    array (
      'name' => 'userId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'parentId' => 
    array (
      'name' => 'parentId',
      'default' => '0',
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'threadId' => 
    array (
      'name' => 'threadId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'lft' => 
    array (
      'name' => 'lft',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'rgt' => 
    array (
      'name' => 'rgt',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'message' => 
    array (
      'name' => 'message',
      'default' => NULL,
      'nullable' => false,
      'type' => 'text',
      'length' => '65535',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'title' => 
    array (
      'name' => 'title',
      'default' => NULL,
      'nullable' => false,
      'type' => 'varchar',
      'length' => '100',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'createdOn' => 
    array (
      'name' => 'createdOn',
      'default' => NULL,
      'nullable' => false,
      'type' => 'datetime',
      'length' => NULL,
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'modifiedOn' => 
    array (
      'name' => 'modifiedOn',
      'default' => NULL,
      'nullable' => false,
      'type' => 'datetime',
      'length' => NULL,
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'replyCount' => 
    array (
      'name' => 'replyCount',
      'default' => '0',
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => false,
    ),
  ),
  'sessions' => 
  array (
    'sessionId' => 
    array (
      'name' => 'sessionId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => true,
      'auto_increment' => true,
      'additional' => false,
      'unsigned' => true,
    ),
    'phpSession' => 
    array (
      'name' => 'phpSession',
      'default' => NULL,
      'nullable' => false,
      'type' => 'varchar',
      'length' => '32',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'userId' => 
    array (
      'name' => 'userId',
      'default' => NULL,
      'nullable' => true,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'ipAddress' => 
    array (
      'name' => 'ipAddress',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'token' => 
    array (
      'name' => 'token',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
      'unsigned' => true,
    ),
    'createdOn' => 
    array (
      'name' => 'createdOn',
      'default' => NULL,
      'nullable' => false,
      'type' => 'datetime',
      'length' => NULL,
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
  ),
  'users' => 
  array (
    'userId' => 
    array (
      'name' => 'userId',
      'default' => NULL,
      'nullable' => false,
      'type' => 'int',
      'length' => '10',
      'scale' => '0',
      'primary' => true,
      'auto_increment' => true,
      'additional' => false,
      'unsigned' => true,
    ),
    'name' => 
    array (
      'name' => 'name',
      'default' => NULL,
      'nullable' => false,
      'type' => 'varchar',
      'length' => '50',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'email' => 
    array (
      'name' => 'email',
      'default' => NULL,
      'nullable' => false,
      'type' => 'varchar',
      'length' => '50',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'password' => 
    array (
      'name' => 'password',
      'default' => NULL,
      'nullable' => true,
      'type' => 'varchar',
      'length' => '50',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
    'avatar' => 
    array (
      'name' => 'avatar',
      'default' => NULL,
      'nullable' => true,
      'type' => 'varchar',
      'length' => '50',
      'scale' => NULL,
      'primary' => false,
      'auto_increment' => false,
      'additional' => false,
    ),
  ),
);
