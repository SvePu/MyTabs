<?php

	/*
	 *	MyBBPlugins
	 *	http://www.mybbplug.in/s/
	 *
	 *	MyTabs
	 *	Created by Ethan at MyBBPlugins
	 *	[Administrator & Developer]
	 *
	 *	- File: "{$mybb->settings['bburl']}/inc/plugins/mytabs.php"
	 *
	 *  This plugin and its contents are free for use.
	 *
	 */

	$plugins->add_hook("index_start", "mytabs_start");
	$plugins->add_hook("index_end", "mytabs_forums");
	$plugins->add_hook("usercp_options_end", "mytabs_useroptions");
	$plugins->add_hook("usercp_do_options_end", "mytabs_save_useroptions");
	$plugins->add_hook("admin_forum_menu", "mytabs_menu");
	$plugins->add_hook("admin_forum_action_handler", "mytabs_action_handler");
	
	function mytabs_info()
	{
		return array(
			'name'			=> 'MyTabs',
			'description'	=> 'Lets you implement tabbed browsing in your forum.',
			'website'		=> 'http://www.pokemonforum.org',
			'author'		=> 'Ethan',
			'authorsite'	=> 'http://www.pokemonforum.org',
			'version'		=> '2.00',
			'guid'			=> '',
			'compatibility' => '18*'
		);
	}
	
	function mytabs_activate()
	{
		global $mybb, $db;
		
		/* Create table for storing tabs. */
		if(!$db->table_exists('mytabs_tabs'))
		{
			$mytabs_table = 'CREATE TABLE `'.TABLE_PREFIX.'mytabs_tabs`(
											`id` INT(10) NOT NULL AUTO_INCREMENT ,
											`name` TEXT NOT NULL ,
											`forums` TEXT NOT NULL ,
											`visible` TEXT NOT NULL ,
											`order` TEXT NOT NULL ,
											`tab_html` TEXT NOT NULL ,
											`selected_tab_html` TEXT NOT NULL ,
											PRIMARY KEY(`id`)
											) ENGINE = MYISAM ;
								';
			$db->query($mytabs_table);
		}
		
		/* Create settings table. */
		if(!$db->table_exists('mytabs_settings'))
		{
			$mytabs_table = 'CREATE TABLE `'.TABLE_PREFIX.'mytabs_settings`(
											`id` INT(10) NOT NULL AUTO_INCREMENT ,
											`name` TEXT NOT NULL ,
											`value` TEXT NOT NULL ,
											PRIMARY KEY(`id`)
											) ENGINE = MYISAM ;
								';
			$db->query($mytabs_table);
		}
		
		/* Create default settings. */
		
		$default_settings[] = array(
			'id' => 1,
			'name' => 'enabled',
			'value' => '1'
		);
		
		$default_settings[] = array(
			'id' => 2,
			'name' => 'default_tab_html',
			'value' => '<a href="{$link}" style="margin-right: 6px;">	<div style="display: inline-block; padding: 10px; border: 1px solid #858787; color: black; background-color: #BABCBC;">		{$name}	</div></a>'
		);
		
		$default_settings[] = array(
			'id' => 3,
			'name' => 'default_selected_tab_html',
			'value' => '<a href="{$link}" style="margin-right: 6px;">	<div style="display: inline-block; padding: 10px; border: 1px solid #49B0D8; color: black; background-color: #8CDEFF;">		{$name}	</div></a>'
		);
		
		$default_settings[] = array(
			'id' => 4,
			'name' => 'tab_list_html',
			'value' => '<div class="trow2" style="border: 1px solid #ccc; margin: 0px 2px 20px 2px; padding: 10px;">	{$tablist}</div>'
		);
		
		$default_settings[] = array(
			'id' => 5,
			'name' => 'default_tab',
			'value' => '1'
		);
		
		$default_settings[] = array(
			'id' => 6,
			'name' => 'ajax',
			'value' => '0'
		);
		
		$db->insert_query_multiple('mytabs_settings', $default_settings);
		
		/* Create user default tab option column. */
		
		if(!$db->field_exists('default_tab', 'users'))
		{
			$db->add_column('users', 'default_tab', 'TEXT NOT NULL');
		}
	}
	
	function mytabs_deactivate()
	{
		global $mybb, $db;
		
		/* Drop the tabs table. */
		if($db->table_exists('mytabs_tabs'))
		{
			$db->drop_table('mytabs_tabs');
		}
		
		/* Drop the tabs settings table. */
		if($db->table_exists('mytabs_settings'))
		{
			$db->drop_table('mytabs_settings');
		}
		
		/* Drop the user default tab option column. */
		if($db->field_exists('default_tab', 'users'))
		{
			$db->drop_column('users', 'default_tab');
		}
	}
	
	/**
	 * This hook includes the mytabs.js file in the website headers.
	 *
	 * Hook: "index_start"
	 */
	function mytabs_start()
	{
		global $db, $header, $headerinclude, $mybb, $setting;
		
		if(empty($setting))
		{
			$query = $db->simple_select('mytabs_settings');
			while($result = $db->fetch_array($query))
			{
				$setting[$result['name']] = $result['value'];
			}
		}
		
		if($setting['enabled'])
		{
			$headerinclude .= "<script type=\"text/javascript\" src=\"{$mybb->settings['bburl']}/jscripts/mytabs.js\"></script>";
		}
	}
	
	/**
	 * This function will create the forums.
	 *
	 * Hook: "index_end"
	 */
	function mytabs_forums()
	{
		global $db, $forumpermissions, $forums, $mybb, $setting;
		
		if(empty($setting))
		{
			$query = $db->simple_select('mytabs_settings');
			while($result = $db->fetch_array($query))
			{
				$setting[$result['name']] = $result['value'];
			}
		}
		
		if($setting['enabled'])
		{
			// Get the tab that is currently input.
			$selected_tab = intval($mybb->input['tab']);
			
			// If we found a tab with an index of > 0, then we need to validate that the tab is visible.
			if(intval($selected_tab) > 0)
			{
				$query = $db->simple_select('mytabs_tabs', '*', "`id`='{$selected_tab}'");
				if($temp_tab = $db->fetch_array($query))
				{
					if($temp_tab['visible'])
					{
						$selected_tab = $temp_tab['id'];
					}
					else
					{
						$selected_tab = 0;
					}
				}
				else
				{
					$selected_tab = 0;
				}
			}
			
			// If there is no tab selected, look up this user's default tab (if any selected).
			if(!$selected_tab)
			{
				$query = $db->simple_select('users', 'default_tab', "`uid`='{$mybb->user['uid']}'");
				if($db->num_rows($query) > 0)
				{
					if($user_tab_info = $db->fetch_array($query))
					{
						$temp = rtrim($user_tab_info['default_tab']);
						if(!empty($temp))
						{
							$selected_tab = intval($user_tab_info['default_tab']);
							$query = $db->simple_select('mytabs_tabs', '*', "id='{$selected_tab}'");
							if($temp_tab = $db->fetch_array($query))
							{
								if($temp_tab['visible'])
								{
									$selected_tab = $temp_tab['id'];
								}
								else $selected_tab = 0;
							}
							else $selected_tab = 0;
						}
					}
				}
			}
			
			// If that step failed, get the board's default tab.
			if(intval($selected_tab) < 1)
			{
				$selected_tab = $setting['default_tab'];
				$query = $db->simple_select('mytabs_tabs', '*', "id='{$selected_tab}'");
				
				if($temp_tab = $db->fetch_array($query))
				{
					if($temp_tab['visible'])
					{
						$selected_tab = $temp_tab['id'];
					}
					else $selected_tab = 0;
				}
				else $selected_tab = 0;
			}
			
			if(intval($selected_tab < 1))
			{
				$query = $db->simple_select('mytabs_tabs', '*', "visible=1", array('order_by' => '`order`', 'order_dir' => 'asc', 'limit' => 1));
				if($temp_tab = $db->fetch_array($query))
				{
					$selected_tab = $temp_tab['id'];
				}
				else
				{
					$setting['enabled'] = false;
					return;
				}
			}
			
			$forums .= "\n<!-- Begin MyTabs -->";
			$tab_query = $db->simple_select('mytabs_tabs', "*", '', array('order_by' => '`order`', 'order_dir' => 'asc'));
			if($db->num_rows($tab_query) > 0)
			{
				$forums = "";
		
				/* Start tab menu code. */
				
				$forums .= "\n<div id=\"mytabs_full\">";
				
				$forums .= "\n<div id=\"tab_nav\">";
				
				$tabs = array();
				while($tab = $db->fetch_array($tab_query)) $tabs[] = $tab;
				
				foreach($tabs as $tab)
				{
					if(!$tab['visible']) continue;
					
					$current_iteration = $tab['id'];
					
					$tablist = "";
					foreach($tabs as $tab)
					{
						if(!$tab['visible']) continue;
						
						$name = $tab['name'];
						$link = "?tab={$tab['id']}\" onclick=\"return switchTab('{$tab['id']}', false);";
						
						if($current_iteration == $tab['id'])
						{
							$temp = rtrim($tab['selected_tab_html']);
							if(!empty($temp))
							{
								eval("\$tablist .= \"".$db->escape_string(empty($tab["selected_tab_html"]) ? $setting["default_selected_tab_html"] : $tab["selected_tab_html"])."\";");
							}
						}
						else
						{
							$temp = rtrim($tab['tab_html']);
							if(!empty($temp))
							{
								eval("\$tablist .= \"".$db->escape_string(empty($tab["tab_html"]) ? $setting["default_tab_html"] : $tab["tab_html"])."\";");
							}
						}
					}
					$display = ($selected_tab == $current_iteration) ? '' : 'display: none;';
					eval("\$forums .= \"".$db->escape_string("<div id=\"tab_nav_{$current_iteration}\" style=\"{$display}\">".$setting['tab_list_html'])."</div>\";");
				}
				
				$forums .= "\n</div>\n<div id=\"tab_content\" style=\"\">";
				
				/* End tab menu code. */
				
				$noshow = array();
				foreach($forumpermissions as $fid => $perms)
				{
					/* Check if forum is already unviewable. */
					if(!$forumpermissions[$fid]['canview'])
					{
						$noshow[] .= $fid;
					}
				}
				
				// Start looping through our tabs again and build our forums based on what each tab contains.
				foreach($tabs as $tab)
				{
					if(!$tab['visible']) continue;
					
					$forums .= "\r\n<!-- Begin processing tab[{$tab['id']}] -->\r\n";
					
					// When the current tab that we are processing is the selected tab, we leave everything alone.
					// Otherwise, we use style="display: none" to hide the contents of the forum.
					if($tab['id'] == $selected_tab)
					{
						$forums .= "\r\n<div id=\"tab_{$tab['id']}\" style=\"\">";
					}
					else
					{
						$forums .= "\r\n<div id=\"tab_{$tab['id']}\" style=\"display: none;\">";
					}
				
					// Set all the forums to invisible.
					foreach($forumpermissions as $fid => $perms)
					{
						$forumpermissions[$fid]['canview'] = 0;
					}
					
					$temp = rtrim($tab['forums']);
					// Make sure the tab has forums to exclude, otherwise show all.
					if(!empty($temp))
					{
						foreach(explode(',', $tab['forums']) as $fid)
						{
							// Check to see if forum is already unviewable.
							if($fid != null && is_array($noshow))
							{
								if(!in_array($fid, $noshow))
								{
									// Set this forum viewable.
									$forumpermissions[$fid]['canview'] = 1;
									
									// Check parent forums and set to viewable.
									$parents = get_parent_list($fid);
									if(!empty($parents))
									{
										foreach(explode(',', $parents) as $pid)
										{
											if($pid != null && !in_array($pid, $noshow))
											{
												$forumpermissions[$pid]['canview'] = 1;
											}
										}
									}
								}
							}
						}
						
						$forum_list = build_forumbits();
						$forums .= $forum_list['forum_list'];
					}
					$forums .= "</div>";
					$forums .= "\n<!-- Finished with tab[{$tab['id']}] -->\n";
				}
				$forums .= "\r\n\r\n</div>\r\n</div>";
			}
			$forums .= "\r\n<!-- End MyTabs -->";
		}
		else
		{
			/* Disabled */
			$forums .= "\n<!-- MyTabs is currently disabled. -->";
		}
	}
	
	function mytabs_useroptions()
	{
		global $db, $lang, $mybb, $templates, $tppselect, $setting;
		
		if(empty($setting))
		{
			$query = $db->simple_select('mytabs_settings');
			while($result = $db->fetch_array($query))
			{
				$setting[$result['name']] = $result['value'];
			}
		}
		
		if($setting['enabled'])
		{
			$lang->load('mytabs');
			
			$query = $db->simple_select('users', 'default_tab', "uid='{$mybb->user['uid']}'");
			if($db->num_rows($query) > 0)
			{
				if($user_tab_info = $db->fetch_array($query))
				{
					$temp = rtrim($user_tab_info['default_tab']);
					if(!empty($temp))
					{
						$selected_tab = $db->escape_string($user_tab_info['default_tab']);
					}
				}
			}
			
			$query = $db->simple_select('mytabs_tabs');
			if($db->num_rows($query) > 0)
			{
				while($tab = $db->fetch_array($query))
				{
					if($tab['visible'])
					{
						$selected = "";
						if($selected_tab == $tab['id'])
						{
							$selected = "selected=\"selected\"";
						}
						$tppoptions .= "<option value=\"{$tab['id']}\" $selected>{$tab['name']}</option>\n";
					}
				}
				eval("\$tabselect .= \"".$templates->get("usercp_options_tppselect")."\";");
				$tppselect .= str_replace('name="tpp"', 'name="defaulttab"', $tabselect);
			}
		}
	}
	
	function mytabs_save_useroptions()
	{
		global $db, $mybb, $setting;
		
		if(empty($setting))
		{
			$query = $db->simple_select('mytabs_settings');
			while($result = $db->fetch_array($query))
			{
				$setting[$result['name']] = $result['value'];
			}
		}
		
		if($setting['enabled'])
		{
			$db->update_query("users", array('default_tab' => intval($mybb->input['defaulttab'])), "uid='".$mybb->user['uid']."'");
		}
	}
	
	function mytabs_menu(&$sub_menu)
	{
		global $mybb, $lang;

		end($sub_menu);
		$key =(key($sub_menu))+10;

		if(!$key)
		{
			$key = '50';
		}
		$sub_menu[$key] = array('id' => 'mytabs', 'title' => 'MyTabs', 'link' => "index.php?module=forum-mytabs");
	}
	
	function mytabs_action_handler(&$action)
	{
		$action['mytabs'] = array('active' => 'mytabs', 'file' => 'mytabs.php');
	}

?>