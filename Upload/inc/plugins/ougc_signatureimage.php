<?php

/***************************************************************************
 *
 *	OUGC Signature Image plugin (/inc/plugins/ougc_signatureimage.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2015 - 2020 Omar Gonzalez
 *
 *	Based on: Profile Picture plugin
 *	By: Starpaul20 (PaulBender)
 *
 *	Website: https://ougc.network
 *
 *	Allows your users to upload an image to display in their signature.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Tell MyBB when to run the hooks
if(defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_style_templates_set', create_function('&$args', 'global $lang;	isset($lang->setting_group_ougc_signatureimage) or $lang->load("ougc_signatureimage", true);'));

	$plugins->add_hook("datahandler_user_delete_content", "ougc_signatureimage_user_delete");
	$plugins->add_hook("admin_user_users_edit_graph_tabs", "ougc_signatureimage_user_options");
	$plugins->add_hook("admin_user_users_edit_graph", "ougc_signatureimage_user_graph");
	$plugins->add_hook("admin_user_users_edit_commit_start", "ougc_signatureimage_user_commit");
	$plugins->add_hook("admin_formcontainer_end", "ougc_signatureimage_usergroup_permission");
	$plugins->add_hook("admin_user_groups_edit_commit", "ougc_signatureimage_usergroup_permission_commit");
	$plugins->add_hook("admin_tools_system_health_output_chmod_list", "ougc_signatureimage_chmod");
}
else
{
	$plugins->add_hook("global_start", "ougc_signatureimage_header_cache");
	$plugins->add_hook("global_intermediate", "ougc_signatureimage_header");
	$plugins->add_hook("usercp_start", "ougc_signatureimage_run");
	$plugins->add_hook("usercp_menu_built", "ougc_signatureimage_nav");
	$plugins->add_hook("member_profile_end", "ougc_signatureimage_profile");
	$plugins->add_hook("postbit", "ougc_signatureimage_postbit");
	$plugins->add_hook("postbit_prev", "ougc_signatureimage_postbit");
	$plugins->add_hook("postbit_pm", "ougc_signatureimage_postbit");
	$plugins->add_hook("postbit_announcement", "ougc_signatureimage_postbit");
	$plugins->add_hook("usercp_editsig_end", "ougc_signatureimage_editsig");
	$plugins->add_hook("fetch_wol_activity_end", "ougc_signatureimage_online_activity");
	$plugins->add_hook("build_friendly_wol_location_end", "ougc_signatureimage_online_location");
	$plugins->add_hook("modcp_do_editprofile_start", "ougc_signatureimage_removal");
	$plugins->add_hook("modcp_editprofile_start", "ougc_signatureimage_removal_lang");

	// Neat trick for caching our custom template(s)
	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	if(THIS_SCRIPT == 'usercp.php')
	{
		$templatelist .= 'ougcsignatureimage_usercp,ougcsignatureimage_usercp_auto_resize_auto,ougcsignatureimage_usercp_auto_resize_user,ougcsignatureimage_usercp_current,ougcsignatureimage_usercp_nav,ougcsignatureimage_usercp_remove,ougcsignatureimage_usercp_upload,ougcsignatureimage_usercp_remote';
	}

	if(THIS_SCRIPT == 'private.php')
	{
		$templatelist .= 'ougcsignatureimage_usercp_nav';
	}
	
	if(THIS_SCRIPT == 'usercp2.php')
	{
		global $templatelist;
		if(isset($templatelist))
		{
			$templatelist .= ',';
		}
		$templatelist .= 'ougcsignatureimage_usercp_nav';
	}

	if(THIS_SCRIPT == 'member.php')
	{
		$templatelist .= 'ougcsignatureimage_profile';
	}

	if(THIS_SCRIPT == 'modcp.php')
	{
		$templatelist .= 'ougcsignatureimage_modcp';
	}
}

// The information that shows up on the plugin manager
function ougc_signatureimage_info()
{
	global $lang;

	isset($lang->setting_group_ougc_signatureimage) or $lang->load("ougc_signatureimage", true);

	return array(
		"name"			=> 'OUGC Signature Image',
		"description"	=> $lang->setting_group_ougc_signatureimage_desc.$lang->setting_group_ougc_signatureimage_desc_more,
		'website'		=> 'https://ougc.network',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'https://ougc.network',
		'version'		=> '1.8.20',
		'versioncode'	=> 1820,
		'compatibility'	=> '18*',
		'codename'		=> 'ougc_signatureimage',
		'pl'			=> array(
			'version'	=> 13,
			'url'		=> 'https://community.mybb.com/mods.php?action=view&pid=573'
		)
	);
}

// This function runs when the plugin is installed.
function ougc_signatureimage_install()
{
	global $db, $cache, $ougc_signatureimage;
	ougc_signatureimage_uninstall();

	$ougc_signatureimage->_db_verify_columns();

	$cache->update_usergroups();
}

// Checks to make sure plugin is installed
function ougc_signatureimage_is_installed()
{
	global $db;

	foreach(OUGC_SignatureImage::_db_columns() as $table => $columns)
	{
		foreach($columns as $name => $definition)
		{
			$installed = $db->field_exists($name, $table);
			break;
		}
	}

	return $installed;
}

// This function runs when the plugin is uninstalled.
function ougc_signatureimage_uninstall()
{
	global $db, $cache, $ougc_signatureimage;
	$PL or require_once PLUGINLIBRARY;

	foreach($ougc_signatureimage->_db_columns() as $table => $columns)
	{
		foreach($columns as $name => $definition)
		{
			!$db->field_exists($name, $table) || $db->drop_column($table, $name);
		}
	}

	$cache->update_usergroups();

	$PL->settings_delete('ougc_signatureimage');
	$PL->templates_delete('ougcsignatureimage');

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['signatureimage']))
	{
		unset($plugins['signatureimage']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$cache->delete('ougc_plugins');
	}
}

// This function runs when the plugin is activated.
function ougc_signatureimage_activate()
{
	global $db, $PL, $cache, $lang, $ougc_signatureimage;

	isset($lang->setting_group_ougc_signatureimage) or $lang->load("ougc_signatureimage", true);

	$PL or require_once PLUGINLIBRARY;

	// Add settings group
	$PL->settings('ougc_signatureimage', $lang->setting_group_ougc_signatureimage, $lang->setting_group_ougc_signatureimage_desc, array(
		'uploadpath'		=> array(
		   'title'			=> $lang->setting_ougc_signatureimage_uploadpath,
		   'description'	=> $lang->setting_ougc_signatureimage_uploadpath_desc,
		   'optionscode'	=> 'text',
		   'value'			=> './uploads/ougc_signatureimages'
		),
		'resizing'		=> array(
		   'title'			=> $lang->setting_ougc_signatureimage_resizing,
		   'description'	=> $lang->setting_ougc_signatureimage_resizing_desc,
		   'optionscode'	=> "select
auto={$lang->setting_ougc_signatureimage_resizing_auto}
user={$lang->setting_ougc_signatureimage_resizing_user}
disabled={$lang->setting_ougc_signatureimage_resizing_disabled}",
		   'value'			=> 'auto'
		),
		'allowremote'		=> array(
		   'title'			=> $lang->setting_ougc_signatureimage_allowremote,
		   'description'	=> $lang->setting_ougc_signatureimage_allowremote_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
	));

	// Add template group
	$PL->templates('ougcsignatureimage', $lang->setting_group_ougc_signatureimage, array(
		'' => '<img src="{$image[\'image\']}" title="{$description}" alt="{$description}" width="{$image[\'width\']}" height="{$image[\'height\']}" />',
		'usercp' => '<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->change_ougc_signatureimage}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
	{$usercpnav}
	<td valign="top">
		{$ougc_signatureimage_error}
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
			<tr>
				<td class="thead" colspan="2"><strong>{$lang->change_ougc_signatureimage}</strong></td>
			</tr>
			<tr>
				<td class="trow1" colspan="2">
					<table cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td>{$lang->ougc_signatureimage_note}{$ougc_signatureimagemsg}
							{$currentougc_signatureimage}
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="tcat" colspan="2"><strong>{$lang->ougc_signatureimage_custom}</strong></td>
			</tr>
			<form enctype="multipart/form-data" action="usercp.php" method="post">
			<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
			{$ougc_signatureimageupload}
			{$ougc_signatureimage_remote}
		</table>
		<br />
		<div align="center">
			<input type="hidden" name="action" value="do_ougc_signatureimage" />
			<input type="submit" class="button" name="submit" value="{$lang->change_image}" />
			{$removeougc_signatureimage}
		</div>
	</td>
</tr>
</table>
</form>
{$footer}
</body>
</html>',
		'usercp_auto_resize_auto' => '<br /><span class="smalltext">{$lang->ougc_signatureimage_auto_resize_note}</span>',
		'usercp_auto_resize_user' => '<br /><span class="smalltext"><input type="checkbox" name="auto_resize" value="1" checked="checked" id="auto_resize" /> <label for="auto_resize">{$lang->ougc_signatureimage_auto_resize_option}</label></span>',
		'usercp_current' => '<td width="150" align="right"><img src="{$userougc_signatureimage[\'image\']}" alt="{$lang->signature_image_mine}" title="{$lang->signature_image_mine}" {$userougc_signatureimage[\'width_height\']} /></td>',
		'usercp_remove' => '<input type="submit" class="button" name="remove" value="{$lang->remove_image}" />',
		'usercp_upload' => '<tr>
	<td class="trow1" width="40%">
		<strong>{$lang->ougc_signatureimage_upload}</strong>
		<br /><span class="smalltext">{$lang->ougc_signatureimage_upload_note}</span>
	</td>
	<td class="trow1" width="60%">
		<input type="file" name="ougc_signatureimageupload" size="25" class="fileupload" />
		{$auto_resize}
	</td>
</tr>',
		'usercp_nav' => '<div><a href="usercp.php?action=ougc_signatureimage" class="usercp_nav_item" style="padding-left:40px; background:url(\'images/ougc_signatureimage.png\') no-repeat left center;">{$lang->ucp_nav_change_ougc_signatureimage}</a></div>',
		'modcp' => '<tr><td colspan="3"><span class="smalltext"><label><input type="checkbox" class="checkbox" name="remove_ougc_signatureimage" value="1" /> {$lang->remove_signature_image}</label></span></td></tr>',
		'usercp_remote'	=>	'<tr>
		<td class="trow2" width="40%">
			<strong>{$lang->ougc_signatureimage_url}</strong>
			<br /><span class="smalltext">{$lang->ougc_signatureimage_url_note}</span>
		</td>
		<td class="trow2" width="60%">
			<input type="text" class="textbox" name="ougc_signatureimage_url" size="45" value="{$ougc_signatureimage_url}" />
			<br /><span class="smalltext">{$lang->ougc_signatureimage_url_gravatar}</span>
		</td>
	</tr>',
		'global_remote_notice'	=>	'<div class="red_alert"><a href="{$mybb->settings[\'bburl\']}/usercp.php?action=ougc_signatureimage">{$lang->remote_ougc_signatureimage_disabled}</a></div>',
	));

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_signatureimage_info();

	if(!isset($plugins['signatureimage']))
	{
		$plugins['signatureimage'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/
	$ougc_signatureimage->_db_verify_columns();

	!$db->field_exists('ougc_signatureimage_description', 'users') || $db->drop_column('users', 'ougc_signatureimage_description');

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['signatureimage'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("header", "#".preg_quote('{$bannedwarning}')."#i", '{$ougc_signatureimage_remote_notice}{$bannedwarning}');
	find_replace_templatesets("usercp_nav_profile", "#".preg_quote('{$changesigop}')."#i", '{$changesigop}<!-- ougc_signatureimage -->');
	find_replace_templatesets("modcp_editprofile", "#".preg_quote('{$lang->remove_avatar}</label></span></td>
										</tr>')."#i", '{$lang->remove_avatar}</label></span></td>
										</tr>{$ougc_signatureimage}');
}

// This function runs when the plugin is deactivated.
function ougc_signatureimage_deactivate()
{
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("header", "#".preg_quote('{$ougc_signatureimage_remote_notice}')."#i", '', 0);
	find_replace_templatesets("usercp_nav_profile", "#".preg_quote('<!-- ougc_signatureimage -->')."#i", '', 0);
	find_replace_templatesets("modcp_editprofile", "#".preg_quote('{$ougc_signatureimage}')."#i", '', 0);
}

// User CP Nav link
function ougc_signatureimage_nav()
{
	global $db, $mybb, $lang, $templates, $usercpnav;
	isset($lang->setting_group_ougc_signatureimage) or $lang->load("ougc_signatureimage");

	if($mybb->usergroup['ougc_signatureimage_canuse'] == 1)
	{
		eval("\$ougc_signatureimage_nav = \"".$templates->get("ougcsignatureimage_usercp_nav")."\";");
		$usercpnav = str_replace("<!-- ougc_signatureimage -->", $ougc_signatureimage_nav, $usercpnav);
	}
}

// Cache the signature image remote warning template
function ougc_signatureimage_header_cache()
{
	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'global_remote_ougc_signatureimage_notice';
}

// Signature image remote warning
function ougc_signatureimage_header()
{
	global $mybb, $templates, $lang, $remote_ougc_signatureimage_notice;
	$lang->load("ougc_signatureimage");

	$remote_ougc_signatureimage_notice = '';
	if(($mybb->user['ougc_signatureimage_type'] === 'remote' || $mybb->user['ougc_signatureimage_type'] === 'gravatar') && !$mybb->settings['ougc_signatureimage_allowremote'])
	{
		eval('$remote_ougc_signatureimage_notice = "'.$templates->get('global_remote_ougc_signatureimage_notice').'";');
	}
}

// The UserCP signature image page
function ougc_signatureimage_run()
{
	global $db, $mybb, $lang, $templates, $theme, $headerinclude, $usercpnav, $header, $footer;
	isset($lang->setting_group_ougc_signatureimage) or $lang->load("ougc_signatureimage");
	require_once MYBB_ROOT."inc/functions_ougc_signatureimage.php";

	if($mybb->input['action'] == "do_ougc_signatureimage" && $mybb->request_method == "post")
	{
		// Verify incoming POST request
		verify_post_check($mybb->get_input('my_post_key'));

		if($mybb->usergroup['ougc_signatureimage_canuse'] == 0)
		{
			error_no_permission();
		}

		$ougc_signatureimage_error = "";

		if(!empty($mybb->input['remove'])) // remove signature image
		{
			$updated_ougc_signatureimage = array(
				"ougc_signatureimage" => "",
				"ougc_signatureimage_dimensions" => "",
				"ougc_signatureimage_type" => ""
			);
			$db->update_query("users", $updated_ougc_signatureimage, "uid='{$mybb->user['uid']}'");
			remove_ougc_signatureimage($mybb->user['uid']);
		}
		elseif($_FILES['ougc_signatureimageupload']['name']) // upload signature image
		{
			if($mybb->usergroup['ougc_signatureimage_canupload'] == 0)
			{
				error_no_permission();
			}

			$ougc_signatureimage = upload_ougc_signatureimage();
			if($ougc_signatureimage['error'])
			{
				$ougc_signatureimage_error = $ougc_signatureimage['error'];
			}
			else
			{
				if($ougc_signatureimage['width'] > 0 && $ougc_signatureimage['height'] > 0)
				{
					$ougc_signatureimage_dimensions = $ougc_signatureimage['width']."|".$ougc_signatureimage['height'];
				}
				$updated_ougc_signatureimage = array(
					"ougc_signatureimage" => $ougc_signatureimage['ougc_signatureimage'].'?dateline='.TIME_NOW,
					"ougc_signatureimage_dimensions" => $ougc_signatureimage_dimensions,
					"ougc_signatureimage_type" => "upload"
				);
				$db->update_query("users", $updated_ougc_signatureimage, "uid='{$mybb->user['uid']}'");
			}
		}
		elseif($mybb->input['ougc_signatureimageurl'] && $mybb->settings['ougc_signatureimage_allowremote']) // remote signature image
		{
			$mybb->input['ougc_signatureimageurl'] = trim($mybb->get_input('ougc_signatureimageurl'));
			if(validate_email_format($mybb->input['ougc_signatureimageurl']) != false)
			{
				// Gravatar
				$mybb->input['ougc_signatureimageurl'] = my_strtolower($mybb->input['ougc_signatureimageurl']);

				// If user image does not exist, or is a higher rating, use the mystery man
				$email = md5($mybb->input['ougc_signatureimageurl']);

				$s = '';
				if(!$mybb->usergroup['ougc_signatureimage_maxdimensions'])
				{
					$mybb->usergroup['ougc_signatureimage_maxdimensions'] = '200x200'; // Hard limit of 200 if there are no limits
				}

				// Because Gravatars are square, hijack the width
				list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->usergroup['ougc_signatureimage_maxdimensions']));
				$maxheight = (int)$maxwidth;

				// Rating?
				$types = array('g', 'pg', 'r', 'x');
				$rating = $mybb->settings['ougc_signatureimage_rating'];

				if(!in_array($rating, $types))
				{
					$rating = 'g';
				}

				$s = "?s={$maxheight}&r={$rating}&d=mm";

				$updated_avatar = array(
					"ougc_signatureimage" => "https://www.gravatar.com/avatar/{$email}{$s}",
					"ougc_signatureimage_dimensions" => "{$maxheight}|{$maxheight}",
					"ougc_signatureimage_type" => "gravatar"
				);

				$db->update_query("users", $updated_avatar, "uid = '{$mybb->user['uid']}'");
			}
			else
			{
				$mybb->input['ougc_signatureimageurl'] = preg_replace("#script:#i", "", $mybb->input['ougc_signatureimageurl']);
				$ext = get_extension($mybb->input['ougc_signatureimageurl']);

				// Copy the signature image to the local server (work around remote URL access disabled for getimagesize)
				$file = fetch_remote_file($mybb->input['ougc_signatureimageurl']);
				if(!$file)
				{
					$ougc_signatureimage_error = $lang->error_invalidougc_signatureimageurl;
				}
				else
				{
					$tmp_name = $mybb->settings['ougc_signatureimage_uploadpath']."/remote_".md5(random_str());
					$fp = @fopen($tmp_name, "wb");
					if(!$fp)
					{
						$ougc_signatureimage_error = $lang->error_invalidougc_signatureimageurl;
					}
					else
					{
						fwrite($fp, $file);
						fclose($fp);
						list($width, $height, $type) = @getimagesize($tmp_name);
						@unlink($tmp_name);
						if(!$type)
						{
							$ougc_signatureimage_error = $lang->error_invalidougc_signatureimageurl;
						}
					}
				}

				if(empty($ougc_signatureimage_error))
				{
					if($width && $height && $mybb->usergroup['ougc_signatureimage_maxdimensions'] != "")
					{
						list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->usergroup['ougc_signatureimage_maxdimensions']));
						if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight))
						{
							$lang->error_ougc_signatureimagetoobig = $lang->sprintf($lang->error_ougc_signatureimagetoobig, $maxwidth, $maxheight);
							$ougc_signatureimage_error = $lang->error_ougc_signatureimagetoobig;
						}
					}
				}

				if(empty($ougc_signatureimage_error))
				{
					if($width > 0 && $height > 0)
					{
						$ougc_signatureimage_dimensions = (int)$width."|".(int)$height;
					}
					$updated_ougc_signatureimage = array(
						"ougc_signatureimage" => $db->escape_string($mybb->input['ougc_signatureimageurl'].'?dateline='.TIME_NOW),
						"ougc_signatureimage_dimensions" => $ougc_signatureimage_dimensions,
						"ougc_signatureimage_type" => "remote"
					);
					$db->update_query("users", $updated_ougc_signatureimage, "uid='{$mybb->user['uid']}'");
					remove_ougc_signatureimage($mybb->user['uid']);
				}
			}
		}
		else
		{
			$ougc_signatureimage_error = $lang->ougc_signatureimage_error_remote_not_allowed;
		}

		if(empty($ougc_signatureimage_error))
		{
			redirect("usercp.php?action=ougc_signatureimage", $lang->redirect_ougc_signatureimageupdated);
		}
		else
		{
			$mybb->input['action'] = "ougc_signatureimage";
			$ougc_signatureimage_error = inline_error($ougc_signatureimage_error);
		}
	}

	if($mybb->input['action'] == "ougc_signatureimage")
	{
		add_breadcrumb($lang->nav_usercp, "usercp.php");
		add_breadcrumb($lang->change_ougc_signatureimage, "usercp.php?action=ougc_signatureimage");

		// Show main signature image page
		if($mybb->usergroup['ougc_signatureimage_canuse'] == 0)
		{
			error_no_permission();
		}

		$ougc_signatureimagemsg = $ougc_signatureimageurl = '';

		if($mybb->user['ougc_signatureimage_type'] == "upload" || stristr($mybb->user['ougc_signatureimage'], $mybb->settings['ougc_signatureimage_uploadpath']))
		{
			$ougc_signatureimagemsg = "<br /><strong>".$lang->already_uploaded_ougc_signatureimage."</strong>";
		}
		elseif($mybb->user['ougc_signatureimage_type'] == "remote" || my_validate_url($mybb->user['ougc_signatureimage']))
		{
			$ougc_signatureimagemsg = "<br /><strong>".$lang->using_remote_ougc_signatureimage."</strong>";
			$ougc_signatureimageurl = htmlspecialchars_uni($mybb->user['ougc_signatureimage']);
		}

		if(!empty($mybb->user['ougc_signatureimage']) && ((($mybb->user['ougc_signatureimage_type'] == 'remote' || $mybb->user['ougc_signatureimage_type'] == 'gravatar') && $mybb->settings['ougc_signatureimage_allowremote'] == 1) || $mybb->user['ougc_signatureimage_type'] == "upload"))
		{
			$userougc_signatureimage = format_signature_image(htmlspecialchars_uni($mybb->user['ougc_signatureimage']), $mybb->user['ougc_signatureimage_dimensions'], '200x200');
			eval("\$currentougc_signatureimage = \"".$templates->get("ougcsignatureimage_usercp_current")."\";");
		}

		if($mybb->usergroup['ougc_signatureimage_maxdimensions'] != "")
		{
			list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->usergroup['ougc_signatureimage_maxdimensions']));
			$lang->ougc_signatureimage_note .= "<br />".$lang->sprintf($lang->ougc_signatureimage_note_dimensions, $maxwidth, $maxheight);
		}
		if($mybb->usergroup['ougc_signatureimage_maxsize'])
		{
			$maxsize = get_friendly_size($mybb->usergroup['ougc_signatureimage_maxsize']*1024);
			$lang->ougc_signatureimage_note .= "<br />".$lang->sprintf($lang->ougc_signatureimage_note_size, $maxsize);
		}

		$auto_resize = '';
		if($mybb->settings['ougc_signatureimage_resizing'] == "auto")
		{
			eval("\$auto_resize = \"".$templates->get("ougcsignatureimage_usercp_auto_resize_auto")."\";");
		}
		else if($mybb->settings['ougc_signatureimage_resizing'] == "user")
		{
			eval("\$auto_resize = \"".$templates->get("ougcsignatureimage_usercp_auto_resize_user")."\";");
		}

		$ougc_signatureimageupload = '';
		if($mybb->usergroup['ougc_signatureimage_canupload'] == 1)
		{
			eval("\$ougc_signatureimageupload = \"".$templates->get("ougcsignatureimage_usercp_upload")."\";");
		}

		$ougc_signatureimage_remote = '';
		if($mybb->settings['ougc_signatureimage_allowremote'] == 1)
		{
			eval("\$ougc_signatureimage_remote = \"".$templates->get("ougcsignatureimage_usercp_remote")."\";");
		}

		$removeougc_signatureimage = '';
		if(!empty($mybb->user['ougc_signatureimage']))
		{
			eval("\$removeougc_signatureimage = \"".$templates->get("ougcsignatureimage_usercp_remove")."\";");
		}

		if(!isset($ougc_signatureimage_error))
		{
			$ougc_signatureimage_error = '';
		}

		eval("\$ougc_signatureimage = \"".$templates->get("ougcsignatureimage_usercp")."\";");
		output_page($ougc_signatureimage);
	}
}

// Signature Image display in profile
function ougc_signatureimage_profile()
{
	global $signature, $memprofile;

	ougc_signatureimage_profile_parse($signature, $memprofile);
}

// Signature Image display in postbit
function ougc_signatureimage_postbit(&$post)
{
	ougc_signatureimage_profile_parse($post['signature'], $post);
}

// Parse signature in UCP edit page
function ougc_signatureimage_editsig()
{
	global $signature, $mybb;

	ougc_signatureimage_profile_parse($signature, $mybb->user);
}

// Parse the signature
function ougc_signatureimage_profile_parse(&$signature, &$user)
{
	global $templates, $settings, $lang;

	if(strpos($signature, '{SIGNATURE_IMAGE}') == false)
	{
		return;
	}

	if(!$user['ougc_signatureimage'])
	{
		$signature = str_replace('{SIGNATURE_IMAGE}', '', $signature);
		return;
	}

	$user['ougc_signatureimage'] = htmlspecialchars_uni($user['ougc_signatureimage']);

	isset($lang->setting_group_ougc_signatureimage) or $lang->load('ougc_signatureimage');

	$description = htmlspecialchars_uni($user['username']);

	require_once MYBB_ROOT.'inc/functions_ougc_signatureimage.php';
	$image = format_signature_image($user['ougc_signatureimage'], $user['ougc_signatureimage_dimensions']);

	eval('$image = "'.$templates->get('ougcsignatureimage', 1, 0).'";');

	$signature = str_replace('{SIGNATURE_IMAGE}', $image, $signature);
}

// Online location support
function ougc_signatureimage_online_activity($user_activity)
{
	global $user;
	if(my_strpos($user['location'], "usercp.php?action=ougc_signatureimage") !== false)
	{
		$user_activity['activity'] = "usercp_ougc_signatureimage";
	}

	return $user_activity;
}

function ougc_signatureimage_online_location($plugin_array)
{
	global $lang;
	isset($lang->setting_group_ougc_signatureimage) or $lang->load("ougc_signatureimage");

	if($plugin_array['user_activity']['activity'] == "usercp_ougc_signatureimage")
	{
		$plugin_array['location_name'] = $lang->changing_ougc_signatureimage;
	}

	return $plugin_array;
}

// Mod CP removal function
function ougc_signatureimage_removal()
{
	global $mybb, $db, $user;
	require_once MYBB_ROOT."inc/functions_ougc_signatureimage.php";

	if(!empty($mybb->input['remove_ougc_signatureimage']))
	{
		$updated_ougc_signatureimage = array(
			"ougc_signatureimage" => "",
			"ougc_signatureimage_dimensions" => "",
			"ougc_signatureimage_type" => ""
		);
		remove_ougc_signatureimage($user['uid']);

		$db->update_query("users", $updated_ougc_signatureimage, "uid='{$user['uid']}'");
	}
}

// Mod CP language
function ougc_signatureimage_removal_lang()
{
	global $mybb, $lang, $user, $templates, $ougc_signatureimage;

	isset($lang->setting_group_ougc_signatureimage) or $lang->load("ougc_signatureimage");

	eval("\$ougc_signatureimage = \"".$templates->get("ougcsignatureimage_modcp")."\";");
}

// Delete signature image if user is deleted
function ougc_signatureimage_user_delete($delete)
{
	global $db;

	// Remove any of the user(s) uploaded signature images
	$query = $db->simple_select('users', 'ougc_signatureimage', "uid IN({$delete->delete_uids}) AND ougc_signatureimage_type='upload'");
	while($ougc_signatureimage = $db->fetch_field($query, 'ougc_signatureimage'))
	{
		$ougc_signatureimage = substr($ougc_signatureimage, 2, -20);
		@unlink(MYBB_ROOT.$ougc_signatureimage);
	}

	return $delete;
}

// Edit user options in Admin CP
function ougc_signatureimage_user_options(&$tabs)
{
	global $lang;

	$lang->load("ougc_signatureimage", true);

	$tabs['ougc_signatureimage'] = $lang->signature_image;
}

function ougc_signatureimage_user_graph()
{
	global $lang, $form, $mybb, $user;
	$lang->load("ougc_signatureimage", true);

	$ougc_signatureimage_dimensions = explode("|", $user['ougc_signatureimagedimensions']);
	if($user['ougc_signatureimage'] && (my_strpos($user['ougc_signatureimage'], '://') === false || $mybb->settings['ougc_signature_allowremote']))
	{
		if($user['ougc_signatureimagedimensions'])
		{
			require_once MYBB_ROOT."inc/functions_image.php";
			list($width, $height) = explode("|", $user['ougc_signatureimagedimensions']);
			$scaled_dimensions = scale_image($width, $height, 200, 200);
		}
		else
		{
			$scaled_dimensions = array(
				"width" => 200,
				"height" => 200
			);
		}
		if(!my_validate_url($user['ougc_signatureimage']))
		{
			$user['ougc_signatureimage'] = "../{$user['ougc_signatureimage']}\n";
		}
	}
	else
	{
		$user['ougc_signatureimage'] = "../".$mybb->settings['useravatar'];
		$scaled_dimensions = array(
			"width" => 200,
			"height" => 200
		);
	}
	$ougc_signatureimage_top = ceil((206-$scaled_dimensions['height'])/2);

	echo "<div id=\"tab_ougc_signatureimage\">\n";
	$table = new Table;
	$table->construct_header($lang->current_ougc_signatureimage, array('colspan' => 2));

	$table->construct_cell("<div style=\"width: 206px; height: 206px;\" class=\"user_avatar\"><img src=\"".htmlspecialchars_uni($user['ougc_signatureimage'])."\" width=\"{$scaled_dimensions['width']}\" style=\"margin-top: {$ougc_signatureimage_top}px\" height=\"{$scaled_dimensions['height']}\" alt=\"\" /></div>", array('width' => 1));

	$ougc_signatureimage_url = '';
	if($user['ougc_signatureimagetype'] == "upload" || stristr($user['ougc_signatureimage'], $mybb->settings['ougc_signatureimageuploadpath']))
	{
		$current_ougc_signatureimage_msg = "<br /><strong>{$lang->user_current_using_uploaded_ougc_signatureimage}</strong>";
	}
	elseif($user['ougc_signatureimagetype'] == "remote" || my_validate_url($user['ougc_signatureimage']))
	{
		$current_ougc_signatureimage_msg = "<br /><strong>{$lang->user_current_using_remote_ougc_signatureimage}</strong>";
		$ougc_signatureimage_url = $user['ougc_signatureimage'];
	}

	if($errors)
	{
		$ougc_signatureimage_url = htmlspecialchars_uni($mybb->input['ougc_signatureimage_url']);
	}

	$user_permissions = user_permissions($user['uid']);

	if($user_permissions['ougc_signatureimagemaxdimensions'] != "")
	{
		list($max_width, $max_height) = explode("x", my_strtolower($user_permissions['ougc_signatureimagemaxdimensions']));
		$max_size = "<br />{$lang->ougc_signatureimage_max_dimensions_are} {$max_width}x{$max_height}";
	}

	if($user_permissions['ougc_signatureimagemaxsize'])
	{
		$maximum_size = get_friendly_size($user_permissions['ougc_signatureimagemaxsize']*1024);
		$max_size .= "<br />{$lang->ougc_signatureimage_max_size} {$maximum_size}";
	}

	if($user['ougc_signatureimage'])
	{
		$remove_ougc_signatureimage = "<br /><br />".$form->generate_check_box("remove_ougc_signatureimage", 1, "<strong>{$lang->remove_ougc_signatureimage_admin}</strong>");
	}

	$table->construct_cell($lang->ougc_signatureimage_desc."{$remove_ougc_signatureimage}<br /><small>{$max_size}</small>");
	$table->construct_row();

	$table->output($lang->ougc_signatureimage.": ".htmlspecialchars_uni($user['username']));

	// Custom signature image
	if($mybb->settings['ougc_signatureimageresizing'] == "auto")
	{
		$auto_resize = $lang->ougc_signatureimage_auto_resize;
	}
	else if($mybb->settings['ougc_signatureimageresizing'] == "user")
	{
		$auto_resize = "<input type=\"checkbox\" name=\"auto_resize\" value=\"1\" checked=\"checked\" id=\"auto_resize\" /> <label for=\"auto_resize\">{$lang->attempt_to_auto_resize_ougc_signatureimage}</label></span>";
	}
	$form_container = new FormContainer($lang->specify_custom_ougc_signatureimage);
	$form_container->output_row($lang->upload_ougc_signatureimage, $auto_resize, $form->generate_file_upload_box('ougc_signatureimage_upload', array('id' => 'ougc_signatureimage_upload')), 'ougc_signatureimage_upload');
	if($mybb->settings['ougc_signatureimage_allowremote'])
	{
		$form_container->output_row($lang->or_specify_ougc_signatureimage_url, "", $form->generate_text_box('ougc_signatureimage_url', $ougc_signatureimage_url, array('id' => 'ougc_signatureimage_url')), 'ougc_signatureimage_url');
	}
	$form_container->end();
	echo "</div>\n";
}

function ougc_signatureimage_user_commit()
{
	global $db, $extra_user_updates, $mybb, $errors, $user;

	require_once MYBB_ROOT."inc/functions_ougc_signatureimage.php";
	$user_permissions = user_permissions($user['uid']);

	// Are we removing a signature image from this user?
	if($mybb->input['remove_ougc_signatureimage'])
	{
		$extra_user_updates = array(
			"ougc_signatureimage" => "",
			"ougc_signatureimagedimensions" => "",
			"ougc_signatureimagetype" => ""
		);
		remove_ougc_signatureimage($user['uid']);
	}

	// Are we uploading a new signature image?
	if($_FILES['ougc_signatureimage_upload']['name'])
	{
		$ougc_signatureimage = upload_ougc_signatureimage($_FILES['ougc_signatureimage_upload'], $user['uid']);
		if($ougc_signatureimage['error'])
		{
			$errors = array($ougc_signatureimage['error']);
		}
		else
		{
			if($ougc_signatureimage['width'] > 0 && $ougc_signatureimage['height'] > 0)
			{
				$ougc_signatureimage_dimensions = $ougc_signatureimage['width']."|".$ougc_signatureimage['height'];
			}
			$extra_user_updates = array(
				"ougc_signatureimage" => $ougc_signatureimage['ougc_signatureimage'].'?dateline='.TIME_NOW,
				"ougc_signatureimagedimensions" => $ougc_signatureimage_dimensions,
				"ougc_signatureimagetype" => "upload"
			);
		}
	}
	// Are we setting a new signature image from a URL?
	else if($mybb->input['ougc_signatureimage_url'] && $mybb->input['ougc_signatureimage_url'] != $user['ougc_signatureimage'])
	{
		if(!$mybb->settings['ougc_signatureimage_allowremote'])
		{
			$errors = array($lang->error_remote_ougc_signatureimage_not_allowed);
		}
		else
		{
			if(filter_var($mybb->input['ougc_signatureimage_url'], FILTER_VALIDATE_EMAIL) !== false)
			{
				// Gravatar
				$email = md5(strtolower(trim($mybb->input['ougc_signatureimage_url'])));

				$s = '';
				if(!$user_permissions['ougc_signatureimage_maxdimensions'])
				{
					$user_permissions['ougc_signatureimage_maxdimensions'] = '200x200'; // Hard limit of 200 if there are no limits
				}

				// Because Gravatars are square, hijack the width
				list($maxwidth, $maxheight) = explode("x", my_strtolower($user_permissions['ougc_signatureimage_maxdimensions']));

				$s = "?s={$maxwidth}";
				$maxheight = (int)$maxwidth;

				$extra_user_updates = array(
					"ougc_signatureimage" => "https://www.gravatar.com/avatar/{$email}{$s}",
					"ougc_signatureimage_dimensions" => "{$maxheight}|{$maxheight}",
					"ougc_signatureimage_type" => "gravatar"
				);
			}
			else
			{
				$mybb->input['ougc_signatureimage_url'] = preg_replace("#script:#i", "", $mybb->input['ougc_signatureimage_url']);
				$ext = get_extension($mybb->input['ougc_signatureimage_url']);

				// Copy the signature image to the local server (work around remote URL access disabled for getimagesize)
				$file = fetch_remote_file($mybb->input['ougc_signatureimage_url']);
				if(!$file)
				{
					$ougc_signatureimage_error = $lang->ougc_signatureimage_error_invalidurl;
				}
				else
				{
					$tmp_name = "../".$mybb->settings['ougc_signatureimage_uploadpath']."/remote_".md5(random_str());
					$fp = @fopen($tmp_name, "wb");
					if(!$fp)
					{
						$ougc_signatureimage_error = $lang->ougc_signatureimage_error_invalidurl;
					}
					else
					{
						fwrite($fp, $file);
						fclose($fp);
						list($width, $height, $type) = @getimagesize($tmp_name);
						@unlink($tmp_name);
						echo $type;
						if(!$type)
						{
							$ougc_signatureimage_error = $lang->ougc_signatureimage_error_invalidurl;
						}
					}
				}

				if(empty($ougc_signatureimage_error))
				{
					if($width && $height && $user_permissions['ougc_signatureimage_maxdimensions'] != "")
					{
						list($maxwidth, $maxheight) = explode("x", my_strtolower($user_permissions['ougc_signatureimage_maxdimensions']));
						if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight))
						{
							$lang->ougc_signatureimage_error_toobig = $lang->sprintf($lang->ougc_signatureimage_error_toobig, $maxwidth, $maxheight);
							$ougc_signatureimage_error = $lang->ougc_signatureimage_error_toobig;
						}
					}
				}

				if(empty($ougc_signatureimage_error))
				{
					if($width > 0 && $height > 0)
					{
						$ougc_signatureimage_dimensions = (int)$width."|".(int)$height;
					}
					$extra_user_updates = array(
						"ougc_signatureimage" => $db->escape_string($mybb->input['ougc_signatureimage_url'].'?dateline='.TIME_NOW),
						"ougc_signatureimage_dimensions" => $ougc_signatureimage_dimensions,
						"ougc_signatureimage_type" => "remote"
					);
					ougc_signatureimage_remove($user['uid']);
				}
				else
				{
					$errors = array($ougc_signatureimage_error);
				}
			}
		}
	}
}

// Admin CP permission control
function ougc_signatureimage_usergroup_permission()
{
	global $mybb, $lang, $form, $form_container, $run_module;
	isset($lang->setting_group_ougc_signatureimage) or $lang->load("ougc_signatureimage", true);

	if($run_module == 'user' && !empty($form_container->_title) & !empty($lang->misc) & $form_container->_title == $lang->misc)
	{
		$ougc_signatureimage_options = array(
	 		$form->generate_check_box('ougc_signatureimage_canuse', 1, $lang->can_use_ougc_signatureimage, array("checked" => $mybb->input['ougc_signatureimage_canuse'])),
			$form->generate_check_box('ougc_signatureimage_canupload', 1, $lang->can_upload_ougc_signatureimage, array("checked" => $mybb->input['ougc_signatureimage_canupload'])),
			"{$lang->ougc_signatureimage_size}<br /><small>{$lang->ougc_signatureimage_size_desc}</small><br />".$form->generate_numeric_field('ougc_signatureimage_maxsize', $mybb->input['ougc_signatureimage_maxsize'], array('id' => 'ougc_signatureimage_maxsize', 'class' => 'field50', 'min' => 0)). "KB",
			"{$lang->ougc_signatureimage_dims}<br /><small>{$lang->ougc_signatureimage_dims_desc}</small><br />".$form->generate_text_box('ougc_signatureimage_maxdimensions', $mybb->input['ougc_signatureimage_maxdimensions'], array('id' => 'ougc_signatureimage_maxdimensions', 'class' => 'field'))
		);
		$form_container->output_row($lang->signature_image, "", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $ougc_signatureimage_options)."</div>");
	}
}

function ougc_signatureimage_usergroup_permission_commit()
{
	global $db, $mybb, $updated_group;

	$_db_columns = OUGC_SignatureImage::_db_columns();

	foreach($_db_columns['usergroups'] as $name => $definition)
	{
		$updated_group[$name] = $db->escape_string($mybb->get_input($name));
	}
}

// Check to see if CHMOD for signature images is writable
function ougc_signatureimage_chmod()
{
	global $mybb, $lang, $table, $message_signature_image;
	isset($lang->setting_group_ougc_signatureimage) or $lang->load("ougc_signatureimage", true);

	if(is_writable('../'.$mybb->settings['ougc_signatureimage_uploadpath']))
	{
		$message_signature_image = "<span style=\"color: green;\">{$lang->writable}</span>";
	}
	else
	{
		$message_signature_image = "<strong><span style=\"color: #C00\">{$lang->not_writable}</span></strong><br />{$lang->please_chmod_777}";
		++$errors;
	}

	$table->construct_cell("<strong>{$lang->signature_image_upload_dir}</strong>");
	$table->construct_cell($mybb->settings['ougc_signatureimage_uploadpath']);
	$table->construct_cell($message_signature_image);
	$table->construct_row();
}

class OUGC_SignatureImage
{

	// List of columns
	function _db_columns()
	{
		$tables = array(
			'users' => array(
				'ougc_signatureimage' => "varchar(200) NOT NULL default ''",
				'ougc_signatureimage_dimensions' => "varchar(10) NOT NULL default ''",
				'ougc_signatureimage_type' => "varchar(10) NOT NULL default ''",
				//'ougc_signatureimage_description' => "varchar(255) NOT NULL default ''",
			),
			'usergroups' => array(
				'ougc_signatureimage_canuse' => "tinyint(1) NOT NULL default '1'",
				'ougc_signatureimage_canupload' => "tinyint(1) NOT NULL default '1'",
				'ougc_signatureimage_maxsize' => "int unsigned NOT NULL default '40'",
				'ougc_signatureimage_maxdimensions' => "varchar(10) NOT NULL default '200x200'",
			),
		);
	
		switch($db->type)
		{
			case "pgsql":
				$tables['usergroups']['ougc_signatureimage_canuse'] = "smallint NOT NULL default '1'";
				$tables['usergroups']['ougc_signatureimage_canupload'] = "smallint NOT NULL default '1'";
				$tables['usergroups']['ougc_signatureimage_maxsize'] = "int NOT NULL default '40'";
				break;
			case "sqlite":
				$tables['usergroups']['ougc_signatureimage_maxsize'] = "int NOT NULL default '40'";
				break;
		}

		return $tables;
	}

	// Verify DB columns
	function _db_verify_columns()
	{
		global $db;

		foreach($this->_db_columns() as $table => $columns)
		{
			foreach($columns as $field => $definition)
			{
				if($db->field_exists($field, $table))
				{
					$db->modify_column($table, "`{$field}`", $definition);
				}
				else
				{
					$db->add_column($table, $field, $definition);
				}
			}
		}
	}
}

$ougc_signatureimage = new OUGC_SignatureImage();