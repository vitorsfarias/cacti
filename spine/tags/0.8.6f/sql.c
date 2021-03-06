/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2002-2005 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU Lesser General Public              |
 | License as published by the Free Software Foundation; either            |
 | version 2.1 of the License, or (at your option) any later version. 	   |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU Lesser General Public License for more details.                     |
 |                                                                         | 
 | You should have received a copy of the GNU Lesser General Public        |
 | License along with this library; if not, write to the Free Software     |
 | Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA           |
 | 02110-1301, USA                                                         |
 |                                                                         |
 +-------------------------------------------------------------------------+
 | cactid: a backend data gatherer for cacti                               |
 +-------------------------------------------------------------------------+
 | This poller would not have been possible without:                       |
 |   - Larry Adams (current development and enhancements)                  |
 |   - Rivo Nurges (rrd support, mysql poller cache, misc functions)       |
 |   - RTG (core poller code, pthreads, snmp, autoconf examples)           |
 |   - Brady Alleman/Doug Warner (threading ideas, implimentation details) |
 +-------------------------------------------------------------------------+
 | - Cacti - http://www.cacti.net/                                         |
 +-------------------------------------------------------------------------+
*/

#include "common.h"
#include "cactid.h"
#include "locks.h"
#include "util.h"
#include "sql.h"

int db_insert(MYSQL *mysql, char *query) {
	char logmessage[LOGSIZE];

	if (set.verbose == POLLER_VERBOSITY_DEBUG) {
		snprintf(logmessage, LOGSIZE-1, "DEBUG: SQLCMD: %s\n", query);
		cacti_log(logmessage);
	}

	thread_mutex_lock(LOCK_MYSQL);
	if (mysql_query(mysql, query)) {
		snprintf(logmessage, LOGSIZE-1, "ERROR: Problem with MySQL: %s\n", mysql_error(mysql));
		cacti_log(logmessage);
		thread_mutex_unlock(LOCK_MYSQL);
		return FALSE;
	}else{
		thread_mutex_unlock(LOCK_MYSQL);
		return TRUE;
	}
}

MYSQL_RES *db_query(MYSQL *mysql, char *query) {
	MYSQL_RES *mysql_res;
	int return_code;
	int retries;
	int error;
	
	thread_mutex_lock(LOCK_MYSQL);
	retries = 0;
	error = FALSE;
	while (retries < 3) {
	 	return_code = mysql_query(mysql, query);
		if (return_code) {
			cacti_log("MYSQL: ERROR encountered while attempting to retrieve records from query\n");
			error = TRUE;
		}else{
			mysql_res = mysql_store_result(mysql);
			error = FALSE;
			break;
		}
		usleep(1000);
		retries++;
	}
	thread_mutex_unlock(LOCK_MYSQL);

	if (error) {
		cacti_log("MYSQL: ERROR could not obtain results from database, exiting\n");
		exit_cactid();
	}

	return mysql_res;
}

int db_connect(char *database, MYSQL *mysql) {
	char logmessage[LOGSIZE];
	MYSQL *db;
	int tries;
	int result;
	char *hostname;
	char *socket;

	if ((hostname = strdup(set.dbhost)) == NULL) {
		snprintf(logmessage, LOGSIZE-1, "ERROR: malloc(): strdup() failed\n");
		cacti_log(logmessage);
		exit_cactid();
	}

	if ((socket = strstr(hostname,":"))) {
		*socket++ = 0x0;
	}

	/* initialalize my variables */
	tries = 20;
	result = 0;

	if (set.verbose == POLLER_VERBOSITY_DEBUG) {
		snprintf(logmessage, LOGSIZE-1, "MYSQL: Connecting to MySQL database '%s' on '%s'...\n", database, set.dbhost);
		cacti_log(logmessage);
	}

	thread_mutex_lock(LOCK_MYSQL);
	db = mysql_init(mysql);
	if (db == NULL) {
		cacti_log("ERROR: MySQL unable to allocate memory and therefore can not connect\n");
		exit_cactid();
	}

	while (tries > 0){
		tries--;
		if (!mysql_real_connect(mysql, hostname, set.dbuser, set.dbpass, database, set.dbport, socket, 0)) {
			if (set.verbose == POLLER_VERBOSITY_DEBUG) {
				snprintf(logmessage, LOGSIZE-1, "MYSQL: Connection Failed: %s\n", mysql_error(mysql));
				cacti_log(logmessage);
			}
			result = 1;
		}else{
			tries = 0;
			result = 0;
			if (set.verbose == POLLER_VERBOSITY_DEBUG) {
				snprintf(logmessage, LOGSIZE-1, "MYSQL: Connected to MySQL database '%s' on '%s'...\n", database, set.dbhost);
				cacti_log(logmessage);
			}
		}
		usleep(2000);
	}

	free(hostname);

	if (result == 1){
		snprintf(logmessage, LOGSIZE-1, "MYSQL: Connection Failed: %s\n", mysql_error(mysql));
		cacti_log(logmessage);
		thread_mutex_unlock(LOCK_MYSQL);
		exit_cactid();
	}else{
		thread_mutex_unlock(LOCK_MYSQL);
		return 0;
	}
}

void db_disconnect(MYSQL *mysql) {
	mysql_close(mysql);
}


