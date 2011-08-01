<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2011 The Cacti Group                                 |
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

require_once(CACTI_BASE_PATH . "/include/log/constants.php");
require_once(CACTI_BASE_PATH . "/include/log/arrays.php");

/*
 * Log viewing actions
 */

/**
 * Get total number of log records
 *
 * Given filter array, return the number of records
 *
 * @param array $filter_array filter array, field => value elements
 * @return int total number of records
 */
function log_get_total ($filter_array = "") {

	$sql_where = "";
	/* validation and setup for the WHERE clause */
	if ((is_array($filter_array)) && (sizeof($filter_array) > 0)) {
		/* validate each field against the known master field list */
		$field_errors = log_validate(sql_filter_array_to_field_array($filter_array), "|field|");

		/* if a field input error has occured, register the error in the session and return */
		if (sizeof($field_errors) > 0) {
			field_register_error($field_errors);
			return false;
		/* otherwise, form an SQL WHERE string using the filter fields */
		}else{

			$sql_where = "";
			$sql_start = true;
			/* check for start_date and end_date fields */
			if (isset($filter_array["start_date"])) {
				$sql_where .= "logdate >= '" . $filter_array["start_date"] . "'";
				unset($filter_array["start_date"]);
				$sql_start = false;
			}
			if (isset($filter_array["end_date"])) {
				if ($sql_where != "") {
					$sql_where .= " AND ";
				}
				$sql_where .= "logdate <= '" . $filter_array["end_date"] . "'";
				unset($filter_array["end_date"]);
				$sql_start = false;
			}
			if ($sql_start == false) {
				$sql_where = " WHERE " . $sql_where;
			}

			$sql_where .= sql_filter_array_to_where_string($filter_array, log_list_form(), $sql_start);

		}
	}

	return db_fetch_cell("select count(*) from log $sql_where");

}

/**
 * List log records
 *
 * Given filter array, return list of log records
 *
 * @param array $filter_array filter array, field => value elements
 * @return array log records
 */
function log_list ($filter_array,$limit = -1,$offset = -1) {

	$sql_where = "";
	/* validation and setup for the WHERE clause */
	if ((is_array($filter_array)) && (sizeof($filter_array) > 0)) {
		/* validate each field against the known master field list */
		$field_errors = log_validate(sql_filter_array_to_field_array($filter_array), "|field|");

		/* if a field input error has occured, register the error in the session and return */
		if (sizeof($field_errors) > 0) {
			field_register_error($field_errors);
			return false;

		/* otherwise, form an SQL WHERE string using the filter fields */
		}else{
			$sql_where = "";
			$sql_start = true;
			/* check for start_date and end_date fields */
			if (isset($filter_array["start_date"])) {
				$sql_where .= "logdate >= '" . $filter_array["start_date"] . "'";
				unset($filter_array["start_date"]);
				$sql_start = false;
			}
			if (isset($filter_array["end_date"])) {
				if ($sql_where != "") {
					$sql_where .= " AND ";
				}
				$sql_where .= "logdate <= '" . $filter_array["end_date"] . "'";
				unset($filter_array["end_date"]);
				$sql_start = false;
			}
			if ($sql_start == false) {
				$sql_where = " WHERE " . $sql_where;
			}

			$sql_where .= sql_filter_array_to_where_string($filter_array, log_list_form(), $sql_start);

		}

	}

	$sql_limit = "";

        return db_fetch_assoc("SELECT
                log.id,
                log.logdate,
                log.facility,
                log.severity,
                poller.name as poller_name,
                poller.id as poller_id,
                device.description as device,
                log.username,
		log.plugin,
		log.source,
                log.message
                FROM (log LEFT JOIN device ON log.device_id = device.id)
                LEFT JOIN poller ON log.poller_id = poller.id
                $sql_where
                order by log.logdate desc",$limit,$offset);

}



/**
 * Validates log field values
 *
 * Validates log field values against the log form definitions
 *
 * @param $_fields_log field array
 * @param $log_field_name_format replacement variable
 * @return array error array if any
 */
function log_validate (&$_fields_log, $log_field_name_format = "|field|") {

	if (sizeof($_fields_log) == 0) {

		return array();
	}

	/* array containing errored fields */
	$error_fields = array();

	/* get a complete field list */
	$fields_device = log_list_form();

	/* base fields */
	while (list($_field_name, $_field_array) = each($fields_device)) {
		if ((isset($_fields_log[$_field_name])) && (isset($_field_array["validate_regexp"])) && (isset($_field_array["validate_empty"]))) {
			$form_field_name = str_replace("|field|", $_field_name, $log_field_name_format);

			if (!form_input_validate($_fields_log[$_field_name], $form_field_name, $_field_array["validate_regexp"], $_field_array["validate_empty"])) {
				$error_fields[] = $form_field_name;
			}
		}
	}

	return $error_fields;
}

/**
 * List of usernames
 *
 * Returns list of id, usernames on the system for use by log viewer
 *
 * @return array record array
 */
function log_list_username () {

	$user = array();
	$users = db_fetch_assoc("select username from user_auth order by username");

	$user["SYSTEM"] = "SYSTEM";
	while (list($id,$user_record) = each($users)) {
		$user[$user_record["username"]] = $user_record["username"];
	}

	return $user;

}


/**
 * List of plugins
 *
 * Returns list of plugins on the system for use by log viewer
 *
 * @return array record array
 */
function log_list_plugin () {

	$plugin = array();

	$plugins = db_fetch_assoc("select distinct plugin,plugin from log where plugin != 'N/A' order by plugin");

	if (sizeof($plugins) > 0) {
		while (list($id,$plugin_record) = each($plugins)) {
			$plugin[$plugin_record["plugin"]] = $plugin_record["plugin"];
		}
	}

	return $plugin;

}


/**
 * List of pollers
 *
 * Returns list of pollers on the system for use by log viewer
 *
 * @return array record array
 */
function logi_list_poller () {

	$poller = array();

	$pollers = db_fetch_assoc("select id, hostname from poller order by hostname");

	$poller["0"] = "SYSTEM";
	while (list($poller_id,$poller_record) = each($pollers)) {
		$poller[$poller_record["id"]] = $poller_record["hostname"];
	}

	return $poller;

}


/**
 * List of devices
 *
 * Returns list of devices on the system for use by log viewer
 *
 * @return array record array
 */
function log_list_device () {

	$device = array();

	$devices = db_fetch_assoc("select id, hostname from device order by hostname");

	$device["0"] = "SYSTEM";
	while (list($id,$hostname) = each($devices)) {
		$device[$hostname["id"]] = $hostname["hostname"];
	}

	return $device;

}


/**
 * List of facilities
 *
 * Returns list of facility on the system for use by log viewer
 *
 * @return array record array
 */
function log_list_facility () {

	$facility = array();
	$facility[CACTI_LOG_FAC_CMDPHP] = "CMDPHP";
	$facility[CACTI_LOG_FAC_CACTID] = "CACTID";
	$facility[CACTI_LOG_FAC_POLLER] = "POLLER";
	$facility[CACTI_LOG_FAC_SCPTSVR] = "SCPTSVR";
	$facility[CACTI_LOG_FAC_WEBUI] = "WEBUI";
	$facility[CACTI_LOG_FAC_EXPORT] = "EXPORT";
	$facility[CACTI_LOG_FAC_AUTH] = "AUTH";
	$facility[CACTI_LOG_FAC_EVENT] = "EVENT";

	return $facility;
}


/**
 * List of severity
 *
 * Returns list of severity on the system for use by log viewer
 *
 * @return array record array
 */
function log_list_severity () {

	$severity = array();
	$severity[CACTI_LOG_SEV_EMERGENCY] = "EMERGENCY";
	$severity[CACTI_LOG_SEV_ALERT] = "ALERT";
	$severity[CACTI_LOG_SEV_CRITICAL] = "CRITICAL";
	$severity[CACTI_LOG_SEV_ERROR] = "ERROR";
	$severity[CACTI_LOG_SEV_WARNING] = "WARNING";
	$severity[CACTI_LOG_SEV_NOTICE] = "NOTICE";
	$severity[CACTI_LOG_SEV_INFO] = "INFO";
	$severity[CACTI_LOG_SEV_DEBUG] = "DEBUG";
	$severity[CACTI_LOG_SEV_DEV] = "DEV";

	return $severity;
}


/**
 * Returns HTML CSS class for log viewer row highlighting
 *
 * Returns HTML CSS class for log viewer row highlighting
 *
 * @param int Cacti system severity
 * @return string HTML CSS class
 */
function log_get_html_css_class($severity) {

	switch ($severity) {
		case "EMERGENCY":
			return "log_row_emergency";
			break;
		case "ALERT":
			return "log_row_alert";
			break;
		case "CRITICAL":
			return "log_row_crit";
			break;
		case "ERROR":
			return "log_row_error";
			break;
		case "WARNING":
			return "log_row_warning";
			break;
		case "NOTICE":
			return "log_row_notice";
			break;
		case "DEBUG":
			return "log_row_debug";
			break;
		case "DEV":
			return "log_row_dev";
			break;
		default: /* Also INFO */
			return "log_row_info";
			break;
	}
}

/*
 * Logging Actions
 */


/**
 * Logs a message to the configured logging system
 *
 * This function is designed to handle logging for the cacti system.
 *
 * @param string $message the message your would like to log
 * @param int $severity the severity you would like to log at, check logging constants for values, Default = CACTI_LOG_SEV_INFO
 * @param int $facility the facility you would like to log in, check logging constants for values. Default = CACTI_LOG_FAC_SYSTEM
 * @return bool true
 */
function log_insert ($message, $severity = CACTI_LOG_SEV_INFO, $facility = CACTI_LOG_FAC_SYSTEM, $parameters = array() ) {
	global $cnn_id;

	/* setup parameters array */
	$parameters = array (
		"" => "",


	);

	/* fill in the current date for printing in the log */
	$logdate = date("Y-m-d H:i:s");

	/* Get variables */
	$log_severity = log_read_config_option("log_severity");

	/* get username */
	if ($severity == CACTI_LOG_SEV_DEV) {
		$username = "DEV";
	}else{
		if (isset($_SESSION["sess_user_id"])) {
			# --> FIX ME FOR NEW AUTH SYSTEM <--
			#$user_info = user_info(array("id" => $_SESSION["sess_user_id"]));
			#$username = $user_info["username"];
			$username = "admin";
		}else{
			$username = "SYSTEM";
		}
	}

	/* set the IP Address */
	if (isset($_SERVER["REMOTE_ADDR"])) {
		$source = $_SERVER["REMOTE_ADDR"];
	}else {
		$source = "0.0.0.0";
	}

	/* Format message for developer if CACTI_LOG_SEV_DEV is allowed */
	if (($severity >= $log_severity) && ($severity == CACTI_LOG_SEV_DEV)) {
		/* get a backtrace so we can derive the current filename/line#/function */
		$backtrace = debug_backtrace();
		if (sizeof($backtrace) == 1) {
			$function_name = $backtrace[0]["function"];
			$filename = $backtrace[0]["file"];
			$line_number = $backtrace[0]["line"];
		} else {
			$function_name = $backtrace[1]["function"];
			$filename = $backtrace[0]["file"];
			$line_number = $backtrace[0]["line"];
		}
		$message = str_replace(CACTI_BASE_PATH, "", $filename) . ":$line_number in " . ($function_name == "" ? "main" : $function_name) . "(): $message";
	}

	/* Log to Cacti System Log */
	if ((log_read_config_option("log_dest_cacti") == CHECKED) && (log_read_config_option("log_status") != "suspended") && ($severity >= $log_severity)) {
		$sql = "insert into log
			(logdate,facility,severity,poller_id,device_id,username,source,plugin,message) values
			(SYSDATE(), " . $facility . "," . $severity . "," . $poller_id . "," .$device_id . ",'" . $username . "','" . $source . "','" . $plugin . "','". sql_sanitize($message) . "');";
		/* DO NOT USE db_execute, function looping can occur when in CACTI_LOG_SEV_DEV mode */
		$cnn_id->Execute($sql);
	}

	/* Log to System Syslog/Eventlog */
	/* Syslog is currently Unstable in Win32 */
	if ((log_read_config_option("log_dest_system") == CHECKED) && ($severity >= $log_severity)) {
		openlog("cacti", LOG_NDELAY | LOG_PID, log_read_config_option("log_system_facility"));
		syslog(log_get_system_severity($severity), log_get_severity($severity) . ": " . log_get_facility($facility) . ": " . $message);
		closelog();
	}

	/* Log to Syslog Server */
	if ((log_read_config_option("log_dest_syslog") == CHECKED) && ($severity >= $log_severity)) {
		log_save_syslog(log_read_config_option("log_syslog_server"), log_read_config_option("log_syslog_port"), log_read_config_option("log_syslog_facility"), log_get_severity_syslog($severity), log_get_severity($severity) . ": " . log_get_facility($facility) . ": " . $message);
	}


	/* print output to standard out if required, only for use in command line scripts */
	if (($output == true) && ($severity >= $log_severity)) {
		print $logdate . " - " . log_get_severity($severity) . ": " . log_get_facility($facility) . ": " . $message . "\n";
	}

	return true;

}


/**
 * Manages the cacti system log
 *
 * Maintains the cacti system log based on system settings
 *
 * @param bool $print_data_to_stdout display log message to stdout
 * @return bool true
 */
function log_maintain ($print_data_to_stdout) {
	/* read current configuration options */
	$syslog_size = read_config_option("log_size");
	$syslog_control = read_config_option("log_control");
	$syslog_maxdays = read_config_option("log_maxdays");
	$total_records = db_fetch_cell("SELECT count(*) FROM log");

	/* Input validation */
	if (! is_numeric($syslog_maxdays)) {
		$syslog_maxdays = 7;
	}
	if (! is_numeric($syslog_size)) {
		$syslog_size = 1000000;
	}

	if ($total_records >= $syslog_size) {
		switch ($syslog_control) {
		case SYSLOG_MNG_ASNEEDED:
			$records_to_delete = $total_records - $syslog_size;
			db_execute("DELETE FROM log ORDER BY logdate LIMIT " . $records_to_delete);
			log_save("Log control removed " . $records_to_delete . " log entires.", CACTI_LOG_SEV_NOTICE, CACTI_LOG_FAC_POLLER, "", 0, 0, $print_data_to_stdout);
			break;
		case SYSLOG_MNG_DAYSOLD:
			db_execute("delete from log where logdate <= '" . date("Y-m-d H:i:s", strtotime("-" . $syslog_maxdays * 24 * 3600 . " Seconds"))."'");
			log_save("Log control removed log entries older than " . $syslog_maxdays . " days.", CACTI_LOG_SEV_NOTICE, CACTI_LOG_FAC_POLLER, "", 0, 0, $print_data_to_stdout);

			break;
		case SYSLOG_MNG_STOPLOG:
			if (read_config_option("log_status") != "suspended") {
				log_save("Log control suspended logging due to the log being full.  Please purge your logs manually.", CACTI_LOG_SEV_CRITICAL, CACTI_LOG_FAC_POLLER, "", 0, 0, 0, $print_data_to_stdout);
				db_execute("REPLACE INTO settings (name,value) VALUES('log_status','suspended')");
			}

			break;
		case SYSLOG_MNG_NONE:
			log_save("The cacti log control mechanism is set to None.  This is not recommended, please purge your logs on a manual basis.", CACTI_LOG_SEV_WARNING, CACTI_LOG_FAC_POLLER, "", 0, 0, $print_data_to_stdout);
			break;
		}
	}

	return true;

}


/**
 * Truncates the cacti system log
 *
 * Truncates the cacti system log and logs that it occured
 *
 * @return bool true
 */
function log_clear () {
	db_execute("TRUNCATE TABLE log");
	db_execute("REPLACE INTO settings (name,value) VALUES('log_status','active')");
	log_save("Log truncated", CACTI_LOG_SEV_NOTICE, CACTI_LOG_FAC_INTERFACE);

	return true;

}



/*
 * Log Translation Functions
 */

/**
 * Reads cacti configuration settings, without this developer debug can cause database looping
 *
 * Finds the current value of a cacti configuration setting
 *
 * @param string $config_name configuration variable to retrieve value
 * @return bool true
 */
function log_read_config_option ($config_name) {
	global $cnn_id, $log_config_options;

	if (isset($log_config_options[$config_name])) {
		/* Prefer global var for speed */
		$value = $log_config_options[$config_name];
	}else{
		if (isset($_SESSION["sess_config_array"][$config_name])) {
			/* Use session if exists */
			$value = $_SESSION["sess_config_array"][$config_name];
		}else{
			/* Go to the database */
			$cnn_id->SetFetchMode(ADODB_FETCH_ASSOC);
			$query = $cnn_id->Execute("select value from settings where name='" . $config_name . "'");

			if ($query) {
				if (! $query->EOF) {
					$db_setting = $query->fields;
				}
			}

			if (isset($db_setting["value"])) {
				$value = $db_setting["value"];
			}else{
				/* Read default if nothing else set */
				$value = read_default_config_option($config_name);
			}
		}
	}

	/* Set session config if sessions active */
	if (isset($_SESSION["sess_config_array"])) {
		$_SESSION["sess_config_array"][$config_name] = $value;
	}
	/* Set value in global array */
	$log_config_options[$config_name] = $value;

	return $value;

}


/**
 * Returns the system (syslog/eventlog) severity level
 *
 * Given a Severity Level constant, return the php syslog constant
 *
 * @param int $severity cacti severity level
 * @return int php syslog severity level
 */
function log_get_system_severity ($severity) {
	if (CACTI_SERVER_OS == "win32") {
		return LOG_WARNING;
	} else {
		switch ($severity) {
			case CACTI_LOG_SEV_EMERGENCY:
				return LOG_EMERG;
			case CACTI_LOG_SEV_ALERT:
				return LOG_ALERT;
			case CACTI_LOG_SEV_CRITICAL:
				return LOG_CRIT;
			case CACTI_LOG_SEV_ERROR:
				return LOG_ERR;
			case CACTI_LOG_SEV_WARNING:
				return LOG_WARNING;
			case CACTI_LOG_SEV_NOTICE:
				return LOG_NOTICE;
			case CACTI_LOG_SEV_INFO:
				return LOG_INFO;
			case CACTI_LOG_SEV_DEBUG:
				return LOG_DEBUG;
			case SEV_DEV:
				return LOG_DEBUG;
		}
	}
	return LOG_INFO;
}


/**
 * Returns human readable facility text
 *
 * Given a facility constant, return human readable text
 *
 * @param int $facility cacti facility constant
 * @return string cacti facility in human readable text
 */
function log_get_facility ($facility) {

	//FIXME: Update to use list function to get array

	switch ($facility) {
		case CACTI_LOG_FAC_CMDPHP:
			return "CMDPHP";
			break;
		case CACTI_LOG_FAC_SPINE:
			return "SPINE";
			break;
		case CACTI_LOG_FAC_POLLER:
			return "POLLER";
			break;
		case CACTI_LOG_FAC_SCPTSVR:
			return "SCPTSVR";
			break;
		case CACTI_LOG_FAC_INTERFACE:
			return "INTERFACE";
			break;
		case CACTI_LOG_FAC_EXPORT:
			return "EXPORT";
			break;
		case CACTI_LOG_FAC_AUTH:
			return "AUTH";
			break;
		case CACTI_LOG_FAC_EVENT:
			return "EVENT";
			break;
		default:
			return "SYSTEM";
			break;
	}
}


/**
 * Returns human readable severity text
 *
 * Given a severity constant, return human readable text
 *
 * @param int $severity cacti severity constant
 * @return string cacti severity in human readable text
 */
function log_get_severity ($severity) {

	//FIXME: Update to use list function to get array

	switch ($severity) {
		case CACTI_LOG_SEV_EMERGENCY:
			return "EMERGENCY";
			break;
		case CACTI_LOG_SEV_ALERT:
			return "ALERT";
			break;
		case CACTI_LOG_SEV_CRITICAL:
			return "CRITICAL";
			break;
		case CACTI_LOG_SEV_ERROR:
			return "ERROR";
			break;
		case CACTI_LOG_SEV_WARNING:
			return "WARNING";
			break;
		case CACTI_LOG_SEV_NOTICE:
			return "NOTICE";
			break;
		case CACTI_LOG_SEV_INFO:
			return "INFO";
			break;
		case CACTI_LOG_SEV_DEBUG:
			return "DEBUG";
			break;
		case CACTI_LOG_SEV_DEV:
			return "DEV";
			break;
		default:
			return "UNKNOWN";
			break;
	}
}


/**
 * Returns syslog severity value
 *
 * Given a severity constant, return syslog severity value
 *
 * @param int $severity cacti severity constant
 * @return int syslog severity value
 */
function log_get_severity_syslog ($severity) {

	//FIXME: Update to use list function to get array

	switch ($severity) {
		case CACTI_LOG_SEV_EMERGENCY:
			return SYSLOG_LEVEL_EMERG;
		case CACTI_LOG_SEV_ALERT:
			return SYSLOG_LEVEL_ALERT;
		case CACTI_LOG_SEV_CRITICAL:
			return SYSLOG_LEVEL_CRIT;
		case CACTI_LOG_SEV_ERROR:
			return SYSLOG_LEVEL_ERR;
		case CACTI_LOG_SEV_WARNING:
			return SYSLOG_LEVEL_WARNING;
		case CACTI_LOG_SEV_NOTICE:
			return SYSLOG_LEVEL_NOTICE;
		case CACTI_LOG_SEV_INFO:
			return SYSLOG_LEVEL_INFO;
		case CACTI_LOG_SEV_DEBUG:
			return SYSLOG_LEVEL_DEBUG;
		case CACTI_LOG_SEV_DEV:
			return SYSLOG_LEVEL_DEBUG;
	}
	return SYSLOG_LEVEL_INFO;
}


/**
 * Send syslog message to a syslog server
 *
 * Generates and sends a syslog packet to a syslog server
 *
 * @param string $syslog_server Server to send syslog messages to
 * @param int $syslog_server_port Port to send to on syslog server
 * @param int $syslog_facility Syslog facility value, refer to syslog log constants
 * @param int $syslog_severity Syslog severity value, refer to syslog log constants
 * @param string $syslog_message message to send to syslog server
 * @return bool true on sent, false on error
 */
function log_save_syslog ($syslog_server, $syslog_server_port, $syslog_facility, $syslog_severity, $syslog_message) {
	global $cnn_id;

	/* Set syslog tag */
	$syslog_tag = "cacti";

	/* Get the pid */
	$pid = getmypid();

	/* Set syslog server */
	if (strtolower(substr($syslog_server, 0, 5)) == "udp://") {
		$syslog_server = strtolower($syslog_server);
	} elseif (strtolower(substr($syslog_server, 0, 5)) == "udp://") {
		$syslog_server = strtolower($syslog_server);
	}else{
		$syslog_server = "udp://" . $syslog_server;
	}

	/* Check facility */
	if (empty($syslog_facility)) {
		$syslog_facility = SYSLOG_LOCAL0;
	}
	if (($syslog_facility > 23) || ($syslog_facility < 0)) {
		$syslog_facility = SYSLOG_LOCAL0;
	}

	/* Check severity */
	if (empty($syslog_severity)) {
		$syslog_severity = SYSLOG_INFO;
	}
	if (($syslog_severity > 7) || ($syslog_severity < 0)) {
		$syslog_severity = SYSLOG_INFO;
	}

	/* Make syslog packet */
	$device = $_SERVER["SERVER_NAME"];
	$time = time();
	if (strlen(date("j", $time)) < 2) {
		$time = date("M  j H H:i:s", $time);
	}else{
		$time = date("M j H H:i:s", $time);
	}
	$priority = ($syslog_facility * 8) + $syslog_severity;
	$packet = "<" . $priority . ">" . $syslog_tag . "[" . $pid  . "]: " . $syslog_message;
	if (strlen($packet) > 1024) {
		$packet = substr($packet, 0, 1024);
	}

	/* Send the syslog message */
	$socket = @fsockopen($syslog_server, $syslog_server_port, $error_number, $error_string);
	if ($socket) {
		@fwrite($socket, $packet);
		@fclose($socket);
		return true;
	}else{
		/* socket error - log to database */
		$sql = "insert into log
			(logdate,facility,severity,poller_id,device_id,username,source,plugin,message) values
			(SYSDATE(), " . CACTI_LOG_FAC_SYSTEM . "," . CACTI_LOG_SEV_ERROR . ",0,0,'SYSTEM','SYSLOG','N/A','". sql_sanitize("Syslog error[" . $error_number ."]: " . $error_string) . "');";
		/* DO NOT USE db_execute, function looping can occur when in CACTI_LOG_SEV_DEV mode */
		$cnn_id->Execute($sql); //FIXME: Mysql direct call
		return false;
	}

	return true;

}


/**
 * Returns the name of the function *before* the calling function
 *
 * Returns the name of the function *before* the calling function. This is useful in
 * situations where you have a generic library and want to log the name of the function
 * that called it.
 *
 * @return string the function name from the call stack
 */
function log_get_last_function () {
	$backtrace = debug_backtrace();
	if (sizeof($backtrace) < 3) {
		return $backtrace[1]["function"];
	}else{
		return $backtrace[2]["function"];
	}
}
