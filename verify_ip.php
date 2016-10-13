<?php

if(!defined("IN_MYBB")) {
    die("You cannot access this file directly. Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('datahandler_login_validate_end', 'verify_ip');

function verify_ip_info() {
	return array(
		"name"  		=> "Verify IP",
		"description"	=> "Checks the users ip address on login and only allows logging into an admin account from an ip from the admin ips list.",
		"website"       => "http://forums.woodnet.net",
		"author"        => "kloddant",
		"authorsite"    => "http://forums.woodnet.net",
		"version"       => "1.0",
		"guid"          => "",
		"compatibility" => "18*"
	);
}

function verify_ip_activate() {
	global $db;

	$verify_ip_group = array(
        'gid'         => 'NULL',
        'name'  	  => 'verify_ip',
        'title'       => 'Verify IP',
        'description' => "Checks the users ip address on login and only allows logging into an admin account from an ip from the admin ips whitelist.",
        'disporder'   => "1",
        'isdefault'   => "0",
    );

    $db->insert_query('settinggroups', $verify_ip_group);
 	$gid = intval($db->insert_id());

 	$enable_verify_ip_setting = array(
        'sid'         => 'NULL',
        'name'        => 'verify_ip_enable',
        'title'       => 'Do you want to enable Verify IP?',
        'description' => 'If you set this option to yes, this plugin will only allow admin login from the ip addresses in the admin ips whitelist.',
        'optionscode' => 'yesno',
        'value'       => '1',
        'disporder'   => 1,
        'gid'         => $gid,
    );

    $db->insert_query('settings', $enable_verify_ip_setting);

    $verify_ip_list_setting = array(
        'sid'         => 'NULL',
        'name'        => 'verify_ip_list',
        'title'       => 'Admin IPs',
        'description' => 'A comma-delimited list of ip addresses required to log into admin accounts.',
        'optionscode' => 'text',
        'value'       => $_SERVER['REMOTE_ADDR'],
        'disporder'   => 1,
        'gid'         => $gid,
    );

    $db->insert_query('settings', $verify_ip_list_setting);

    $verify_ip_gid_setting = array(
        'sid'         => 'NULL',
        'name'        => 'verify_ip_gid',
        'title'       => 'Admin Group',
        'description' => 'The admin group.',
        'optionscode' => 'groupselectsingle',
        'value'       => 4,
        'disporder'   => 1,
        'gid'         => $gid,
    );

    $db->insert_query('settings', $verify_ip_gid_setting);

	rebuild_settings();

}

function verify_ip_deactivate() {
	global $db;

	$db->query("
 		DELETE FROM ".TABLE_PREFIX."settings 
 		WHERE name = 'verify_ip_list'
 	");
	$db->query("
    	DELETE FROM ".TABLE_PREFIX."settings 
    	WHERE name = 'verify_ip_gid'
    ");
    $db->query("
    	DELETE FROM ".TABLE_PREFIX."settings 
    	WHERE name = 'verify_ip_enable'
    ");
    $db->query("
    	DELETE FROM ".TABLE_PREFIX."settinggroups 
    	WHERE name = 'verify_ip'
    ");

	rebuild_settings();
}

function verify_ip($this) {
	if (!$mybb->settings['verify_ip_enable']) {
		return true;
	}
	$groups = array_map('trim', explode(",", $this->login_data['additionalgroups']));
	$groups[] = $this->login_data['usergroup'];
	$adminips = array_map('trim', explode(",", $mybb->settings['verify_ip_list']));
	if (in_array($mybb->settings['verify_ip_gid'], $groups) and !in_array($_SERVER['REMOTE_ADDR'], $adminips)) {
		$this->invalid_combination(true);
		return false;
	}
	return true;
}

?>