<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2014 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/* auth control table object types */
define('AUTH_CONTROL_OBJECT_TYPE_USER', 1);
define('AUTH_CONTROL_OBJECT_TYPE_GROUP', 2);

define('AUTH_CONTROL_DATA_POLICY_ALLOW', 1);
define('AUTH_CONTROL_DATA_POLICY_DENY', 2);

define('PERM_GRAPHS', 1);
define('PERM_TREES', 2);
define('PERM_DEVICES', 3);
define('PERM_GRAPH_TEMPLATES', 4);

define('AUTH_METHOD_NONE', 0);
define('AUTH_METHOD_BUILTIN', 1);
define('AUTH_METHOD_WEB', 2);
define('AUTH_METHOD_LDAP', 3);

define('AUTH_LOGIN_OPT_REFER', '1');
define('AUTH_LOGIN_OPT_CONSOLE', '2');
define('AUTH_LOGIN_OPT_GRAPH', '3');

define('AUTH_LOGIN_RESULT_USER_INVALID', 0);
define('AUTH_LOGIN_RESULT_SUCCESS', 1);
define('AUTH_LOGIN_RESULT_GUEST_LOGIN_DENIED', 2);
define('AUTH_LOGIN_RESULT_PASSWORD_CHANGE', 3);
define('AUTH_LOGIN_RESULT_BAD_PASSWORD', 4);

define('AUTH_REALM_BUILTIN', 0);
define('AUTH_REALM_WEB', 1);
define('AUTH_REALM_LDAP', 2);

define('LDAP_ENCRYPT_NONE', 0);
define('LDAP_ENCRYPT_SSL', 1);
define('LDAP_ENCRYPT_TLS', 2);

define('LDAP_SEARCHMODE_NONE', 0);
define('LDAP_SEARCHMODE_ANON', 1);
define('LDAP_SEARCHMODE_SPECIFIC', 2);

?>
