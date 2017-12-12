<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2013-2018 The facileManager Team                               |
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
 | facileManager: Easy System Administration                               |
 | fmDHCP: Easily manage one or more ISC DHCP servers                      |
 +-------------------------------------------------------------------------+
 | http://www.facilemanager.com/modules/fmdhcp/                            |
 +-------------------------------------------------------------------------+
*/

class fm_dhcp_leases {
	
	/**
	 * Displays the item list
	 *
	 * @since 0.1
	 * @package facileManager
	 * @subpackage fmDHCP
	 *
	 * @param array $result Record rows of all items
	 * @return null
	 */
	function rows($result) {
		global $fmdb;
		
		$result = isSerialized($result['output'][0]) ? unserialize($result['output'][0]) : $result['output'][0];
		
		$bulk_actions_list = null;
		
//		if (currentUserCan('manage_leases', $_SESSION['module'])) {
//			$bulk_actions_list = array(_('Delete'));
//		}

		if (is_array($result)) {
			/** Get datetime formatting */
			$date_format = getOption('date_format', $_SESSION['user']['account_id']);
			$time_format = getOption('time_format', $_SESSION['user']['account_id']);

			setTimezone();

			foreach($result as $ip => $lease_info) {
				$lease_info['ends'] = date($date_format . ' ' . $time_format . ' e', strtotime($lease_info['ends'] . ' GMT'));
				if (strtotime($lease_info['ends']) < strtotime('now')) {
					unset($result[$ip]);
				}
			}
			$fmdb->num_rows = count($result);
		}
		
		echo displayPagination(1, 1, @buildBulkActionMenu($bulk_actions_list));
		
		$table_info = array(
						'class' => 'display_results',
						'id' => 'table_edits',
						'name' => 'lease'
					);

//		if (is_array($bulk_actions_list)) {
//			$title_array[] = array(
//								'title' => '<input type="checkbox" class="tickall" onClick="toggle(this, \'bulk_list[]\')" />',
//								'class' => 'header-tiny header-nosort'
//							);
//		}
		$title_array = @array_merge((array) $title_array, $this->getTableHeader());
//		if (is_array($bulk_actions_list)) $title_array[] = array('title' => _('Actions'), 'class' => 'header-actions');

		echo displayTableHeader($table_info, $title_array);

		if (is_array($result)) {
			foreach($result as $ip => $lease_info) {
				$lease_info['ends'] = date($date_format . ' ' . $time_format . ' e', strtotime($lease_info['ends'] . ' GMT'));
				$this->displayRow($ip, $lease_info);
			}
		}

		echo "</tbody>\n</table>\n";
		if (!is_array($result) || !count($result)) {
			printf('<p id="table_edits" class="noresult">%s</p>', __('There are no leases found on this server.'));
		}
	}

	
	/**
	 * Gets the item table header
	 *
	 * @since 0.1
	 * @package facileManager
	 * @subpackage fmDHCP
	 *
	 * @return array
	 */
	function getTableHeader() {
		return array(__('Hardware Address'), __('IP Address'), __('Hostname'), __('State'), __('Expires'));
	}
	
	
	/**
	 * Displays the entry table row
	 *
	 * @since 0.1
	 * @package facileManager
	 * @subpackage fmDHCP
	 *
	 * @param string $ip Lease IP address
	 * @param array $lease Lease info
	 * @return null
	 */
	function displayRow($ip, $lease) {
		global $fmdb, $__FM_CONFIG;
		
		$edit_status = $checkbox = null;
		extract($lease);
		
		if (currentUserCan('manage_leases', $_SESSION['module'])) {
			$edit_status = '<a href="#" class="delete">' . $__FM_CONFIG['icons']['delete'] . '</a>';
			$edit_status = '<td id="edit_delete_img">' . $edit_status . '</td>';
			$checkbox = '<td><input type="checkbox" name="bulk_list[]" value="' . $ip .'" /></td>';
		}
		
		/** Temporary until deletes work */
		$edit_status = $checkbox = null;
		
		echo <<<HTML
		<tr id="$ip" name="leases">
			$checkbox
			<td>$hardware</td>
			<td>$ip</td>
			<td>$hostname</td>
			<td>$state</td>
			<td>$ends</td>
			$edit_status
		</tr>

HTML;
	}
	
	
	/**
	 * Deletes the selected lease
	 *
	 * @since 0.1
	 * @package facileManager
	 * @subpackage fmDHCP
	 *
	 * @param integer $id ID to delete
	 * @return boolean or string
	 */
	function delete($id, $server_serial_no = 0) {
		global $fmdb, $__FM_CONFIG;
		
		basicGet('fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'servers', $server_serial_no, 'server_', 'server_serial_no');
		if (!$fmdb->num_rows) {
			return sprintf('<p>%s</p>', __('You have specified an invalid server.'));
		}
		extract(get_object_vars($fmdb->last_result[0]));
		
		return 'Yay, deleted!';
		
		/** Delete item */
		if (updateStatus('fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'config', $id, 'config_', 'deleted', 'config_id') === false) {
			return formatError(__('This network could not be deleted because a database error occurred.'), 'sql');
		} else {
			setBuildUpdateConfigFlag($server_serial_no, 'yes', 'build');
			addLogEntry(sprintf(__("Network '%s' was deleted."), $tmp_name));
			return true;
		}
	}


	/**
	 * Gets the leases from the server
	 *
	 * @since 0.1
	 * @package facileManager
	 * @subpackage fmDHCP
	 *
	 * @param integer $server_serial_no Server serial number to query
	 * @return null
	 */
	function getServerLeases($server_serial_no) {
		global $__FM_CONFIG, $fmdb;
		
		basicGet('fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'servers', $server_serial_no, 'server_', 'server_serial_no');
		if (!$fmdb->num_rows) {
			return sprintf('<p>%s</p>', __('You have specified an invalid server.'));
		}
		extract(get_object_vars($fmdb->last_result[0]));
		
		/** Get data via ssh */
		if ($server_update_method == 'ssh') {
			$server_remote = runRemoteCommand($server_name, 'sudo php /usr/local/facileManager/' . $_SESSION['module'] . '/client.php -l dump -o web', 'return', $server_update_port, 'include', 'plaintext');
		} elseif (in_array($server_update_method, array('http', 'https'))) {
			/** Get data via http(s) */
			/** Test the port first */
			if (socketTest($server_name, $server_update_port, 10)) {
				/** Remote URL to use */
				$url = $server_update_method . '://' . $server_name . ':' . $server_update_port . '/fM/reload.php';

				/** Data to post to $url */
				$post_data = array('action' => 'get_leases',
					'serial_no' => $server_serial_no,
					'module' => $_SESSION['module'],
					'command_args' => '-l dump -o web'
				);

				$server_remote = getPostData($url, $post_data);
				if (isSerialized($server_remote)) {
					$server_remote = unserialize($server_remote);
				}
			}
		}

		if (isset($server_remote)) {
			if (is_array($server_remote)) {
				if (array_key_exists('output', $server_remote) && !count($server_remote['output'])) {
					unset($server_remote);
				}

				if (isset($server_remote['failures']) && $server_remote['failures']) {
					return join("\n", $server_remote['output']);
				}
			} else {
				return buildPopup('header', _('Error')) . '<p>' . $server_remote . '</p>' . buildPopup('footer', _('OK'), array('cancel_button' => 'cancel'));
			}
		}
		
		/** Return if the zone did not get dumped from the server */
		if (!isset($server_remote['output'])) {
			$return = sprintf('<p>%s</p>', __('The leases could not be retrieved from the DHCP server. Possible causes include:'));
			$return .= sprintf('<ul><li>%s</li><li>%s</li></ul>',
					__('The update ports on the server are not accessible'),
					__('This server is updated via cron (only SSH and http/https are supported)'));
			return $return;
		}
		
		if (!is_array($server_remote)) {
			return $server_remote;
		}
		
		return $this->rows($server_remote);
	}
	
	
}

if (!isset($fm_dhcp_leases))
	$fm_dhcp_leases = new fm_dhcp_leases();

?>
