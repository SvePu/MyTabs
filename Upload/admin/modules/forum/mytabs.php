<?php

	/*
	 *	MyBBPlugins
	 *	http://www.mybbplug.in/s/
	 *
	 *	MyTabs
	 *	Created by Ethan at MyBBPlugins
	 *	[Administrator & Developer]
	 *
	 *	- File: "{$mybb->settings['bburl']}/admin/modules/forum/mytabs.php"
	 *
	 *  This plugin and its contents are free for use.
	 *
	 */

	/* Prevent users from accessing this file directly. */
	if(!defined("IN_MYBB"))
	{
		die('You aren\'t allowed to view this file directly.<br /><br />Please make sure IN_MYBB is defined.');
	}

	global $admin_options, $mybb, $db, $page, $lang;
	
	/* Load selected language pack. */
	$lang->load('forum_mytabs');
	
	$page->add_breadcrumb_item("MyTabs", "index.php?module=forum-mytabs");
	
	require_once './inc/functions_themes.php';
	
	$sub_tabs['mytabs'] = array(
		'title' => $lang->current_tabs,
		'link' => "index.php?module=forum-mytabs",
		'description' => $lang->current_tabs_desc
	);
	
	$sub_tabs['mytabsadd'] = array(
		'title' => $lang->add_new_tab,
		'link' => "index.php?module=forum-mytabs&amp;do=add",
		'description' => $lang->add_new_tab_desc
	);
	
	$sub_tabs['mytabssettings'] = array(
		'title' => $lang->settings_title,
		'link' => "index.php?module=forum-mytabs&amp;do=settings",
		'description' => $lang->settings_desc
	);
	
	/* Get settings array. */
	
	$query = $db->simple_select("mytabs_settings", "*");
	while($result = $db->fetch_array($query))
	{
		$setting[$result['name']] = $result['value'];
	}
	
	if($mybb->input['do'] == 'add')
	{
		if($admin_options['codepress'] != 0)
		{
			$page->extra_header .= '
<link href="./jscripts/codemirror/lib/codemirror.css" rel="stylesheet">
<link href="./jscripts/codemirror/theme/mybb.css?ver=1804" rel="stylesheet">
<script src="./jscripts/codemirror/lib/codemirror.js"></script>
<script src="./jscripts/codemirror/mode/xml/xml.js"></script>
<script src="./jscripts/codemirror/mode/javascript/javascript.js"></script>
<script src="./jscripts/codemirror/mode/css/css.js"></script>
<script src="./jscripts/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<link href="./jscripts/codemirror/addon/dialog/dialog-mybb.css" rel="stylesheet">
<script src="./jscripts/codemirror/addon/dialog/dialog.js"></script>
<script src="./jscripts/codemirror/addon/search/searchcursor.js"></script>
<script src="./jscripts/codemirror/addon/search/search.js"></script>
<script src="./jscripts/codemirror/addon/fold/foldcode.js"></script>
<script src="./jscripts/codemirror/addon/fold/xml-fold.js"></script>
<script src="./jscripts/codemirror/addon/fold/foldgutter.js"></script>
<link href="./jscripts/codemirror/addon/fold/foldgutter.css" rel="stylesheet">
';
		}
		
		$page->add_breadcrumb_item($lang->add_new_tab, "index.php?module=forum-mytabs&amp;do=add");
		
		$page->output_header($lang->add_new_tab);
		
		$page->output_nav_tabs($sub_tabs, 'mytabsadd');

		/* Generate the adding form for tabs. */

		$form = new Form("index.php?module=forum-mytabs&amp;do=add", "post", "save");
		echo $form->generate_hidden_field("id", $mybb->input['id']);
		if($mybb->request_method == "post")
		{
			/* Add new tab. */
			if($mybb->input['name'] != "")
			{
				if(isset($mybb->input['id']))
				{
					$tab = array(
						'name' => $db->escape_string($mybb->input['name']),
						'forums' => implode(',', $mybb->input['forums']),
						'tab_html' => $db->escape_string($mybb->input['tab_html']),
						'selected_tab_html' => $db->escape_string($mybb->input['selected_tab_html']),
						'visible' => intval($mybb->input['visible']),
						'order' => intval($mybb->input['disporder'])
					);
					if($db->insert_query('mytabs_tabs', $tab))
					{
						flash_message($lang->success_add, 'success');
						admin_redirect("index.php?module=forum-mytabs&amp;id={$mybb->input['id']}");
					}
				}
				else
				{
					flash_message($lang->error_invalid_id, 'error');
					admin_redirect("index.php?module=forum-mytabs");
				}
			}
			else
			{
				flash_message($lang->error_no_name, 'error');
				admin_redirect("index.php?module=forum-mytabs&amp;do=add&amp;id={$mybb->input['id']}");
			}
		}
		if($errors)
		{
			$page->output_inline_error($errors);
		}
		
		$form_container = new FormContainer($lang->add_new_tab);
		
		if(empty($mybb->input['tab_html']))
			$mybb->input['tab_html'] = $setting['default_tab_html'];
		if(empty($mybb->input['selected_tab_html']))
			$mybb->input['selected_tab_html'] = $setting['default_selected_tab_html'];
		
		$form_container->output_row($lang->tab_options_name." <em>*</em>", $lang->tab_options_name_desc, $form->generate_text_box('name', $mybb->input['name'], array('id' => 'name')));
		$form_container->output_row($lang->tab_options_forums, $lang->tab_options_forums_desc, $form->generate_forum_select("forums[]", $mybb->input['forums'], array('multiple' => 1)));
		$form_container->output_row($lang->tab_options_style, $lang->tab_options_style_desc, $form->generate_text_area('tab_html', $mybb->input['tab_html'], array('id' => 'tab_html', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'tab_html');
		$form_container->output_row($lang->tab_options_selected_style, $lang->tab_options_selected_style_desc, $form->generate_text_area('selected_tab_html', $mybb->input['selected_tab_html'], array('id' => 'selected_tab_html', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'selected_tab_html');
		$form_container->output_row($lang->tab_options_visible, $lang->tab_options_visible_desc, $form->generate_yes_no_radio('visible', $mybb->input['visible']));
		$form_container->output_row($lang->tab_options_order, $lang->tab_options_order_desc, $form->generate_text_box('disporder', $mybb->input['disporder'], array('id' => 'disporder')));
		
		$form_container->end();
		
		$buttons[] = $form->generate_submit_button($lang->submit_add);
		
		$form->output_submit_wrapper($buttons);
		
		$form->end();
		
		if($admin_options['codepress'] != 0)
		{
			echo '<script type="text/javascript">
				function editor(id)
				{
					CodeMirror.fromTextArea(document.getElementById(id), {
						lineNumbers: true,
						lineWrapping: true,
						foldGutter: true,
						gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
						viewportMargin: Infinity,
						indentWithTabs: true,
						indentUnit: 4,
						mode: "text/html",
						theme: "mybb"
					});
				}
				editor("tab_html");
				editor("selected_tab_html");
			</script>';
		}
		
		$page->output_footer();
	}
	else if($mybb->input['do'] == 'settings')
	{
		if($admin_options['codepress'] != 0)
		{
			$page->extra_header .= '
<link href="./jscripts/codemirror/lib/codemirror.css" rel="stylesheet">
<link href="./jscripts/codemirror/theme/mybb.css?ver=1804" rel="stylesheet">
<script src="./jscripts/codemirror/lib/codemirror.js"></script>
<script src="./jscripts/codemirror/mode/xml/xml.js"></script>
<script src="./jscripts/codemirror/mode/javascript/javascript.js"></script>
<script src="./jscripts/codemirror/mode/css/css.js"></script>
<script src="./jscripts/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<link href="./jscripts/codemirror/addon/dialog/dialog-mybb.css" rel="stylesheet">
<script src="./jscripts/codemirror/addon/dialog/dialog.js"></script>
<script src="./jscripts/codemirror/addon/search/searchcursor.js"></script>
<script src="./jscripts/codemirror/addon/search/search.js"></script>
<script src="./jscripts/codemirror/addon/fold/foldcode.js"></script>
<script src="./jscripts/codemirror/addon/fold/xml-fold.js"></script>
<script src="./jscripts/codemirror/addon/fold/foldgutter.js"></script>
<link href="./jscripts/codemirror/addon/fold/foldgutter.css" rel="stylesheet">
';
		}
		
		$page->add_breadcrumb_item($lang->settings_title, "index.php?module=forum-mytabs&amp;do=settings");
	
		$page->output_header($lang->edit_settings);
		
		$page->output_nav_tabs($sub_tabs, 'mytabssettings');
		
		$form = new Form("index.php?module=forum-mytabs&amp;do=settings", "post", "save");
		
		if($mybb->request_method == "post")
		{
			/* Update settings. */
			if(isset($mybb->input['enable_tabs']))
			{
				$updated = 0;
				
				/* Check for updates. */
				
				/* AJAX Setting Update */
				$setting_ajax = $db->simple_select('mytabs_settings', '*', "`name` ='ajax'");
				if($db->num_rows($setting_ajax) < 1)
				{
					/* Create ajax setting. */
					$db->insert_query('mytabs_settings', array('name' => 'ajax', 'value' => '1'));
					$updated = 1;
				}
				
				/* User default tab setting update */
				if(!$db->field_exists('default_tab', 'users'))
				{
					$db->add_column('users', 'default_tab', 'TEXT NOT NULL');
					$updated = 1;
				}
				
				$db->update_query('mytabs_settings', array('value' => $mybb->input['enable_tabs']), "`name` ='enabled'");
				$db->update_query('mytabs_settings', array('value' => $mybb->input['default_tab']), "`name` ='default_tab'");
				$db->update_query('mytabs_settings', array('value' => $db->escape_string($mybb->input['default_tab_html'])), "`name` ='default_tab_html'");
				$db->update_query('mytabs_settings', array('value' => $db->escape_string($mybb->input['default_selected_tab_html'])), "`name` ='default_selected_tab_html'");
				$db->update_query('mytabs_settings', array('value' => $db->escape_string($mybb->input['tab_list_html'])), "`name` ='tab_list_html'");
				$db->update_query('mytabs_settings', array('value' => $mybb->input['use_ajax']), "`name` ='ajax'");
			
				if($updated)
				{
					flash_message($lang->success_updated, 'success');
				}
				else
				{
					flash_message($lang->success_settings, 'success');
				}
				
				if($mybb->input['continue']) {
					admin_redirect("index.php?module=forum-mytabs&amp;do=settings");
				} else {
					admin_redirect("index.php?module=forum-mytabs");
				}
			}
		}
		
		$form_container = new FormContainer($lang->mytabs_settings);
		
		$form_container->output_row($lang->tab_setting_enabled, $lang->tab_setting_enabled_desc, $form->generate_yes_no_radio('enable_tabs', $setting['enabled']));
		
		// Disabling the AJAX feature since it takes too long to load and is unstable. There are quite a few adjustments required for this update to function reasonably.
		// $form_container->output_row($lang->tab_setting_ajax, $lang->tab_setting_ajax_desc, $form->generate_yes_no_radio('use_ajax', $setting['ajax']));
		
		// Create a hidden field so that we don't have to remove all references to this guy.
		echo $form->generate_hidden_field("use_ajax", '0');
		
		$query = $db->simple_select("mytabs_tabs");
		if($db->num_rows($query) > 0)
		{
			while($tab = $db->fetch_array($query))
			{
				$select_options[$tab['id']] = $tab['name'];
			}
		}
		else
		{
			$select_options = array(0 => "None");
		}
		
		$form_container->output_row($lang->tab_setting_default_tab, $lang->tab_setting_default_tab_desc, $form->generate_select_box('default_tab', $select_options, $setting['default_tab']));
		
		$form_container->output_row($lang->tab_setting_default_style, $lang->tab_setting_default_style_desc, $form->generate_text_area('default_tab_html', $setting['default_tab_html'], array('id' => 'default_tab_html', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'default_tab_html');
		$form_container->output_row($lang->tab_setting_default_selected_style, $lang->tab_setting_default_selected_style_desc, $form->generate_text_area('default_selected_tab_html', $setting['default_selected_tab_html'], array('id' => 'default_selected_tab_html', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'default_selected_tab_html');
		$form_container->output_row($lang->tab_setting_tab_list_style, $lang->tab_setting_tab_list_style_desc, $form->generate_text_area('tab_list_html', $setting['tab_list_html'], array('id' => 'tab_list_html', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'tab_list_html');
		
		$form_container->end();
		
		$buttons[] = $form->generate_submit_button($lang->submit_save_continue, array('name' => 'continue'));
		$buttons[] = $form->generate_submit_button($lang->submit_save_exit, array('name' => 'exit'));
		
		$form->output_submit_wrapper($buttons);
		$form->end();
		
		if($admin_options['codepress'] != 0)
		{
			echo '<script type="text/javascript">
				function editor(id)
				{
					CodeMirror.fromTextArea(document.getElementById(id), {
						lineNumbers: true,
						lineWrapping: true,
						foldGutter: true,
						gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
						viewportMargin: Infinity,
						indentWithTabs: true,
						indentUnit: 4,
						mode: "text/html",
						theme: "mybb"
					});
				}
				editor("default_tab_html");
				editor("default_selected_tab_html");
				editor("tab_list_html");
			</script>';
		}
		
		$page->output_footer();
	}
	else if($mybb->input['do'] == 'edit')
	{
		if($admin_options['codepress'] != 0)
		{
			$page->extra_header .= '
<link href="./jscripts/codemirror/lib/codemirror.css" rel="stylesheet">
<link href="./jscripts/codemirror/theme/mybb.css?ver=1804" rel="stylesheet">
<script src="./jscripts/codemirror/lib/codemirror.js"></script>
<script src="./jscripts/codemirror/mode/xml/xml.js"></script>
<script src="./jscripts/codemirror/mode/javascript/javascript.js"></script>
<script src="./jscripts/codemirror/mode/css/css.js"></script>
<script src="./jscripts/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<link href="./jscripts/codemirror/addon/dialog/dialog-mybb.css" rel="stylesheet">
<script src="./jscripts/codemirror/addon/dialog/dialog.js"></script>
<script src="./jscripts/codemirror/addon/search/searchcursor.js"></script>
<script src="./jscripts/codemirror/addon/search/search.js"></script>
<script src="./jscripts/codemirror/addon/fold/foldcode.js"></script>
<script src="./jscripts/codemirror/addon/fold/xml-fold.js"></script>
<script src="./jscripts/codemirror/addon/fold/foldgutter.js"></script>
<link href="./jscripts/codemirror/addon/fold/foldgutter.css" rel="stylesheet">
';
		}
		$page->add_breadcrumb_item("mytabs", "index.php?module=forum-mytabs");
		
		$page->output_header($lang->edit_tab);
		
		$page->output_nav_tabs($sub_tabs, 'mytabs');
		
		/* Show tab editing form. */
		if($mybb->request_method == "post")
		{
			/* Edit selected tab. */
			if($mybb->input['name'] != "")
			{
				if(isset($mybb->input['id']))
				{
					$tab = array(
						'name' => $db->escape_string($mybb->input['name']),
						'forums' => implode(',', $mybb->input['forums']),
						'tab_html' => $db->escape_string($mybb->input['tab_html']),
						'selected_tab_html' => $db->escape_string($mybb->input['selected_tab_html']),
						'visible' => intval($mybb->input['visible']),
						'order' => intval($mybb->input['disporder'])
					);
					if($db->update_query('mytabs_tabs', $tab, "id='".$mybb->input['id']."'"))
					{
						flash_message($lang->success_edit, 'success');
						if($mybb->input['continue']) {
							admin_redirect("index.php?module=forum-mytabs&amp;do=edit&amp;id={$mybb->input['id']}");
						} else {
							admin_redirect("index.php?module=forum-mytabs");
						}
					}
				}
				else
				{
					flash_message($lang->error_invalid_id, 'error');
					admin_redirect("index.php?module=forum-mytabs");
				}
			}
			else
			{
				flash_message($lang->error_no_name, 'error');
				admin_redirect("index.php?module=forum-mytabs&amp;do=edit&amp;id={$mybb->input['id']}");
			}
		}
		if(!empty($mybb->input['id']))
			$query = $db->simple_select('mytabs_tabs', '*', "id='{$mybb->input['id']}'");
		else
			$query = $db->simple_select('mytabs_tabs', '*', "id='-1'");
		if($db->num_rows($query) > 0)
		{
			$tab = $db->fetch_array($query);
			$form = new Form("index.php?module=forum-mytabs&amp;do=edit", "post", "save");
			echo $form->generate_hidden_field("id", $mybb->input['id']);
			if($errors)
			{
				$page->output_inline_error($errors);
			}
				
			$form_container = new FormContainer($lang->edit_tab);
			
			$form_container->output_row($lang->tab_options_name.". <em>*</em>", $lang->tab_options_name_desc, $form->generate_text_box('name', $tab['name'], array('id' => 'name')));
			$form_container->output_row($lang->tab_options_forums, $lang->tab_options_forums_desc, $form->generate_forum_select("forums[]", explode(',', $tab['forums']), array('multiple' => 1)));
			$form_container->output_row($lang->tab_options_style, $lang->tab_options_style_desc, $form->generate_text_area('tab_html', $tab['tab_html'], array('id' => 'tab_html', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'tab_html');
			$form_container->output_row($lang->tab_options_selected_style, $lang->tab_options_selected_style_desc, $form->generate_text_area('selected_tab_html', $tab['selected_tab_html'], array('id' => 'selected_tab_html', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 256px;')), 'selected_tab_html');
			$form_container->output_row($lang->tab_options_visible, $lang->tab_options_visible_desc, $form->generate_yes_no_radio('visible', $tab['visible']));
			$form_container->output_row($lang->tab_options_order, $lang->tab_options_order_desc, $form->generate_text_box('disporder', $tab['order'], array('id' => 'disporder')));
			
			$form_container->end();
			
			$buttons[] = $form->generate_submit_button($lang->submit_save_continue, array('name' => 'continue'));
			$buttons[] = $form->generate_submit_button($lang->submit_save_exit, array('name' => 'exit'));
			
			$form->output_submit_wrapper($buttons);
			
			$form->end();
		}
		else
		{
			flash_message($lang->error_invalid_id, 'error');
			admin_redirect("index.php?module=forum-mytabs");
		}
		
		if($admin_options['codepress'] != 0)
		{
			echo '<script type="text/javascript">
				function editor(id)
				{
					CodeMirror.fromTextArea(document.getElementById(id), {
						lineNumbers: true,
						lineWrapping: true,
						foldGutter: true,
						gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
						viewportMargin: Infinity,
						indentWithTabs: true,
						indentUnit: 4,
						mode: "text/html",
						theme: "mybb"
					});
				}
				editor("tab_html");
				editor("selected_tab_html");
			</script>';
		}
		
		$page->output_footer();
	}
	else
	{
		/* Show the current tabs. */
		
		$page->output_header($lang->current_tabs);
		
		if($mybb->input['do'] == 'delete')
		{
			/* Delete selected tab. */
			if(isset($mybb->input['id']) && $mybb->input['my_post_key'] == $mybb->post_code)
			{
				if($db->delete_query('mytabs_tabs', "`id`={$mybb->input['id']}"))
				{
					flash_message($lang->success_delete, 'success');
					admin_redirect("index.php?module=forum-mytabs");
				}
			}
		}
		else if($mybb->input['do'] == 'updateorders')
		{
			/* Update orders. */
			if(!empty($mybb->input['disporder']) && is_array($mybb->input['disporder']))
			{
				foreach($mybb->input['disporder'] as $id => $val)
				{
					$db->update_query('mytabs_tabs', array('order' => intval($val)), "id='".intval($id)."'");
				}
			}
			flash_message($lang->success_order, 'success');
			admin_redirect("index.php?module=forum-mytabs");
		}
		
		$page->output_nav_tabs($sub_tabs, 'mytabs');
		
		$page->add_breadcrumb_item("mytabs", "index.php?module=forum-mytabs");
		
		if($errors)
		{
			$page->output_inline_error($errors);
		}
		
		$form = new Form("index.php?module=forum-mytabs&amp;do=updateorders", "post");
		
		$form_container = new FormContainer($lang->current_tabs);
		
		$form_container->output_row_header($lang->tab);
		$form_container->output_row_header($lang->order, array("class" => "align_center", 'width' => '5%'));
		$form_container->output_row_header($lang->controls, array("class" => "align_center", 'style' => 'width: 200px'));

		/* Generate the list of tabs. */
		$query = $db->simple_select('mytabs_tabs', "*", "", array('order_by' => '`order`', 'order_dir' => 'asc'));
		if($db->num_rows($query) > 0)
		{
			while($tab = $db->fetch_array($query))
			{
				$form_container->output_cell("<strong>{$tab['name']}</strong>");

				$form_container->output_cell("<input type=\"text\" name=\"disporder[".$tab['id']."]\" value=\"".$tab['order']."\" class=\"text_input align_center\" style=\"width: 80%; font-weight: bold;\" />", array("class" => "align_center"));
				
				$popup = new PopupMenu("tab_{$tab['id']}", $lang->options);
				$popup->add_item($lang->edit_tab, "index.php?module=forum-mytabs&amp;do=edit&amp;id={$tab['id']}");
				$popup->add_item($lang->delete_tab, "index.php?module=forum-mytabs&amp;do=delete&amp;id={$tab['id']}&amp;my_post_key={$mybb->post_code}", "return AdminCP.deleteConfirmation(this, '{$lang->confirm_delete}')");
				
				$form_container->output_cell($popup->fetch(), array("class" => "align_center"));
				
				$form_container->construct_row();
			}
		}
		
		$submit_options = array();
		
		if($form_container->num_rows() == 0)
		{
			$form_container->output_cell($lang->no_tabs, array('colspan' => 3));
			$form_container->construct_row();
			$submit_options = array('disabled' => true);
		}
		
		$form_container->end();
		
		$buttons[] = $form->generate_submit_button($lang->submit_update, $submit_options);
		
		$form->output_submit_wrapper($buttons);
		
		$form->end();
		
		$page->output_footer();
	}