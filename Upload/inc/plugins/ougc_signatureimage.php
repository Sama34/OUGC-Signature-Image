<?php

/***************************************************************************
 *
 *   OUGC Signature Image plugin (/inc/plugins/ougc_signatureimage.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2015 Omar Gonzalez
 *   
 *   Based on: Profile Picture plugin
 *	 By: Starpaul20 (PaulBender)
 *   
 *   Website: http://omarg.me
 *
 *   Allows your users to upload a picture to display in their signature.
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

	$plugins->add_hook("admin_user_users_delete_commit", "ougc_signatureimage_user_delete");
	$plugins->add_hook("admin_formcontainer_end", "ougc_signatureimage_usergroup_permission");
	$plugins->add_hook("admin_user_groups_edit_commit", "ougc_signatureimage_usergroup_permission_commit");
	$plugins->add_hook("admin_tools_system_health_output_chmod_list", "ougc_signatureimage_chmod");
}
else
{
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
		$templatelist .= 'ougcsignatureimage_usercp,ougcsignatureimage_usercp_auto_resize_auto,ougcsignatureimage_usercp_auto_resize_user,ougcsignatureimage_usercp_current,ougcsignatureimage_usercp_description,ougcsignatureimage_usercp_nav,ougcsignatureimage_usercp_remove,ougcsignatureimage_usercp_upload';
	}

	if(THIS_SCRIPT == 'private.php')
	{
		$templatelist .= 'ougcsignatureimage_usercp_nav';
	}

	if(THIS_SCRIPT == 'member.php')
	{
		$templatelist .= 'ougcsignatureimage_profile';
	}

	if(THIS_SCRIPT == 'modcp.php')
	{
		$templatelist .= 'ougcsignatureimage_modcp,ougcsignatureimage_modcp_description';
	}
}

// The information that shows up on the plugin manager
function ougc_signatureimage_info()
{
	global $lang;
	isset($lang->setting_group_ougc_signatureimage) or $lang->load("ougc_signatureimage", true);

	return array(
		"name"				=> 'OUGC Signature Image',
		"description"		=> $lang->setting_group_ougc_signatureimage_desc,
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "1.0",
		"codename"			=> "ougc_signatureimage",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is installed.
function ougc_signatureimage_install()
{
	global $db, $cache;
	ougc_signatureimage_uninstall();

	$db->add_column("users", "ougc_signatureimage", "varchar(200) NOT NULL default ''");
	$db->add_column("users", "ougc_signatureimage_dimensions", "varchar(10) NOT NULL default ''");
	$db->add_column("users", "ougc_signatureimage_type", "varchar(10) NOT NULL default ''");
	$db->add_column("users", "ougc_signatureimage_description", "varchar(255) NOT NULL default ''");

	$db->add_column("usergroups", "ougc_signatureimage_canuse", "tinyint(1) NOT NULL default '1'");
	$db->add_column("usergroups", "ougc_signatureimage_canupload", "tinyint(1) NOT NULL default '1'");
	$db->add_column("usergroups", "ougc_signatureimage_maxsize", "int unsigned NOT NULL default '40'");
	$db->add_column("usergroups", "ougc_signatureimage_maxdimensions", "varchar(10) NOT NULL default '200x200'");

	$cache->update_usergroups();
}

// Checks to make sure plugin is installed
function ougc_signatureimage_is_installed()
{
	global $db;
	if($db->field_exists("ougc_signatureimage", "users"))
	{
		return true;
	}
	return false;
}

// This function runs when the plugin is uninstalled.
function ougc_signatureimage_uninstall()
{
	global $db, $cache;
	$PL or require_once PLUGINLIBRARY;

	if($db->field_exists("ougc_signatureimage", "users"))
	{
		$db->drop_column("users", "ougc_signatureimage");
	}

	if($db->field_exists("ougc_signatureimage_dimensions", "users"))
	{
		$db->drop_column("users", "ougc_signatureimage_dimensions");
	}

	if($db->field_exists("ougc_signatureimage_type", "users"))
	{
		$db->drop_column("users", "ougc_signatureimage_type");
	}

	if($db->field_exists("ougc_signatureimage_description", "users"))
	{
		$db->drop_column("users", "ougc_signatureimage_description");
	}

	if($db->field_exists("ougc_signatureimage_canuse", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_signatureimage_canuse");
	}

	if($db->field_exists("ougc_signatureimage_canupload", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_signatureimage_canupload");
	}

	if($db->field_exists("ougc_signatureimage_maxsize", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_signatureimage_maxsize");
	}

	if($db->field_exists("ougc_signatureimage_maxdimensions", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_signatureimage_maxdimensions");
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
	global $db, $PL, $cache, $lang;
	isset($lang->setting_group_ougc_signatureimage) or $lang->load("ougc_signatureimage", true);
	$PL or require_once PLUGINLIBRARY;

	// Add settings group
	$PL->settings('ougc_signatureimage', $lang->setting_group_ougc_signatureimage, $lang->setting_group_ougc_signatureimage_desc, array(
		'uploadpath'		=> array(
		   'title'			=> 'Signature Images Upload Path',
		   'description'	=> 'This is the path where signature images will be uploaded to. This directory <strong>must be chmod 777</strong> (writable) for uploads to work.',
		   'optionscode'	=> 'text',
		   'value'			=> './uploads/ougc_signatureimages'
		),
		'resizing'		=> array(
		   'title'			=> 'Signature Images Resizing Mode',
		   'description'	=> 'If you wish to automatically resize all large signature images, provide users the option of resizing their signature image, or not resize signature images at all you can change this setting.',
		   'optionscode'	=> 'select
auto=Automatically resize large signature images
user=Give users the choice of resizing large signature images
disabled=Disable this feature',
		   'value'			=> 'auto'
		),
		'description'		=> array(
		   'title'			=> 'Signature Images Description',
		   'description'	=> 'If you wish allow your users to enter an optional description for their signature image, set this option to yes.',
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
		'rating'		=> array(
		   'title'			=> 'Gravatar Rating',
		   'description'	=> 'Allows you to set the maximum rating for Gravatars if a user chooses to use one. If a user signature image is higher than this rating no signature image will be used. The ratings are:
<ul>
<li><strong>G</strong>: suitable for display on all websites with any audience type</li>
<li><strong>PG</strong>: may contain rude gestures, provocatively dressed individuals, the lesser swear words or mild violence</li>
<li><strong>R</strong>: may contain such things as harsh profanity, intense violence, nudity or hard drug use</li>
<li><strong>X</strong>: may contain hardcore sexual imagery or extremely disturbing violence</li>
</ul>',
		   'optionscode'	=> 'select
g=G
pg=PG
r=R
x=X',
		   'value'			=> 'g'
		),
	));

	// Add template group
	$PL->templates('ougcsignatureimage', '<lang:setting_group_ougc_signatureimage>', array(
		'' => '<img src="{$image[\'image\']}" title="{$description}" alt="{$description}" width="{$image[\'width\']}" height="{$image[\'height\']}" />',
		'usercp' => '<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->change_ougc_signatureimageture}</title>
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
				<td class="thead" colspan="2"><strong>{$lang->change_ougc_signatureimageture}</strong></td>
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
				<td class="tcat" colspan="2"><strong>{$lang->custom_profile_pic}</strong></td>
			</tr>
			<form enctype="multipart/form-data" action="usercp.php" method="post">
			<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
			{$ougc_signatureimageupload}
			<tr>
				<td class="trow2" width="40%">
					<strong>{$lang->ougc_signatureimage_url}</strong>
					<br /><span class="smalltext">{$lang->ougc_signatureimage_url_note}</span>
				</td>
				<td class="trow2" width="60%">
					<input type="text" class="textbox" name="ougc_signatureimageurl" size="45" value="{$ougc_signatureimageurl}" />
					<br /><span class="smalltext">{$lang->ougc_signatureimage_url_gravatar}</span>
				</td>
			</tr>
			{$ougc_signatureimage_description}
		</table>
		<br />
		<div align="center">
			<input type="hidden" name="action" value="do_ougc_signatureimage" />
			<input type="submit" class="button" name="submit" value="{$lang->change_image}" />
			{$removeougc_signatureimageture}
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
		'usercp_current' => '<td width="150" align="right"><img src="{$userougc_signatureimageture[\'image\']}" alt="{$lang->signature_image_mine}" title="{$lang->signature_image_mine}" {$userougc_signatureimageture[\'width_height\']} /></td>',
		'usercp_remove' => '<input type="submit" class="button" name="remove" value="{$lang->remove_image}" />',
		'usercp_description' => '<tr>
	<td class="trow1" width="40%">
		<strong>{$lang->ougc_signatureimage_description}</strong>
		<br /><span class="smalltext">{$lang->ougc_signatureimage_description_note}</span>
	</td>
	<td class="trow1" width="60%">
		<input type="text" class="textbox" name="ougc_signatureimage_description" size="100" value="{$description}" />
	</td>
</tr>',
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
		'modcp' => '<tr><td colspan="3"><span class="smalltext"><label><input type="checkbox" class="checkbox" name="remove_ougc_signatureimage" value="1" /> {$lang->remove_signature_image}</label></span></td></tr>{$ougc_signatureimage_description}',
		'modcp_description' => '<tr>
<td colspan="3"><span class="smalltext">{$lang->ougc_signatureimage_description}</span></td>
</tr>
<tr>
<td colspan="3"><textarea name="ougc_signatureimage_description" id="ougc_signatureimage_description" rows="4" cols="30">{$user[\'ougc_signatureimage_description\']}</textarea></td>
</tr>',
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

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['signatureimage'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("usercp_nav_profile", "#".preg_quote('{$changesigop}')."#i", '{$changesigop}<!-- ougc_signatureimage -->');
	find_replace_templatesets("modcp_editprofile", "#".preg_quote('{$lang->remove_avatar}</label></span></td>
										</tr>')."#i", '{$lang->remove_avatar}</label></span></td>
										</tr>{$ougc_signatureimage}');
}

// This function runs when the plugin is deactivated.
function ougc_signatureimage_deactivate()
{
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
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

// The UserCP signature image page
function ougc_signatureimage_run()
{
	global $db, $mybb, $lang, $templates, $theme, $headerinclude, $usercpnav, $header, $ougc_signatureimage, $footer;
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
				"ougc_signatureimage_type" => "",
				"ougc_signatureimage_description" => ""
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

			// See if signature image description is too long
			if(my_strlen($mybb->input['ougc_signatureimage_description']) > 255)
			{
				$ougc_signatureimage_error = $lang->error_descriptiontoobig;
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
					"ougc_signatureimage_type" => "upload",
					"ougc_signatureimage_description" => $db->escape_string($mybb->input['ougc_signatureimage_description'])
				);
				$db->update_query("users", $updated_ougc_signatureimage, "uid='{$mybb->user['uid']}'");
			}
		}
		elseif($mybb->input['ougc_signatureimageurl']) // remote signature image
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

				// See if signature image description is too long
				if(my_strlen($mybb->input['ougc_signatureimage_description']) > 255)
				{
					$ougc_signatureimage_error = $lang->error_descriptiontoobig;
				}

				$updated_avatar = array(
					"ougc_signatureimage" => "http://www.gravatar.com/avatar/{$email}{$s}.jpg",
					"ougc_signatureimage_dimensions" => "{$maxheight}|{$maxheight}",
					"ougc_signatureimage_type" => "gravatar",
					"ougc_signatureimage_description" => $db->escape_string($mybb->input['ougc_signatureimage_description'])
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
					$tmp_name = $mybb->settings['ougc_signatureimage_uploadpath']."/remote_".md5(uniqid(rand(), true));
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

				// See if signature image description is too long
				if(my_strlen($mybb->input['ougc_signatureimage_description']) > 255)
				{
					$ougc_signatureimage_error = $lang->error_descriptiontoobig;
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
						"ougc_signatureimage_type" => "remote",
						"ougc_signatureimage_description" => $db->escape_string($mybb->input['ougc_signatureimage_description'])
					);
					$db->update_query("users", $updated_ougc_signatureimage, "uid='{$mybb->user['uid']}'");
					remove_ougc_signatureimage($mybb->user['uid']);
				}
			}
		}
		else // just updating signature image description
		{
			// See if signature image description is too long
			if(my_strlen($mybb->input['ougc_signatureimage_description']) > 255)
			{
				$ougc_signatureimage_error = $lang->error_descriptiontoobig;
			}

			if(empty($ougc_signatureimage_error))
			{
				$updated_ougc_signatureimage = array(
					"ougc_signatureimage_description" => $db->escape_string($mybb->input['ougc_signatureimage_description'])
				);
				$db->update_query("users", $updated_ougc_signatureimage, "uid='{$mybb->user['uid']}'");
			}
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
		add_breadcrumb($lang->change_ougc_signatureimageture, "usercp.php?action=ougc_signatureimage");

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
		elseif($mybb->user['ougc_signatureimage_type'] == "remote" || my_strpos(my_strtolower($mybb->user['ougc_signatureimage']), "http://") !== false)
		{
			$ougc_signatureimagemsg = "<br /><strong>".$lang->using_remote_ougc_signatureimage."</strong>";
			$ougc_signatureimageurl = htmlspecialchars_uni($mybb->user['ougc_signatureimage']);
		}

		if(!empty($mybb->user['ougc_signatureimage']))
		{
			$userougc_signatureimageture = format_signature_image(htmlspecialchars_uni($mybb->user['ougc_signatureimage']), $mybb->user['ougc_signatureimage_dimensions'], '200x200');
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

		$description = htmlspecialchars_uni($mybb->user['ougc_signatureimage_description']);

		$ougc_signatureimage_description = '';
		if($mybb->settings['ougc_signatureimage_description'] == 1)
		{
			eval("\$ougc_signatureimage_description = \"".$templates->get("ougcsignatureimage_usercp_description")."\";");
		}

		$removeougc_signatureimageture = '';
		if(!empty($mybb->user['ougc_signatureimage']))
		{
			eval("\$removeougc_signatureimageture = \"".$templates->get("ougcsignatureimage_usercp_remove")."\";");
		}

		if(!isset($ougc_signatureimage_error))
		{
			$ougc_signatureimage_error = '';
		}

		eval("\$ougc_signatureimageture = \"".$templates->get("ougcsignatureimage_usercp")."\";");
		output_page($ougc_signatureimageture);
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

	$description = $lang->sprintf($lang->users_ougc_signatureimage, $user['username']);
	if($user['ougc_signatureimage_description'] && $settings['ougc_signatureimage_description'])
	{
		$description = htmlspecialchars_uni($user['ougc_signatureimage_description']);
	}

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
	global $db, $mybb, $lang, $parameters;
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

	if($mybb->input['remove_ougc_signatureimage'])
	{
		$updated_ougc_signatureimage = array(
			"ougc_signatureimage" => "",
			"ougc_signatureimage_dimensions" => "",
			"ougc_signatureimage_type" => ""
		);
		remove_ougc_signatureimage($user['uid']);

		$db->update_query("users", $updated_ougc_signatureimage, "uid='{$user['uid']}'");
	}

	// Update description if active
	if($mybb->settings['ougc_signatureimage_description'] == 1)
	{
		$updated_ougc_signatureimage = array(
			"ougc_signatureimage_description" => $db->escape_string($mybb->input['ougc_signatureimage_description'])
		);
		$db->update_query("users", $updated_ougc_signatureimage, "uid='{$user['uid']}'");
	}
}

// Mod CP language
function ougc_signatureimage_removal_lang()
{
	global $mybb, $lang, $user, $templates, $ougc_signatureimage_description, $ougc_signatureimage;
	isset($lang->setting_group_ougc_signatureimage) or $lang->load("ougc_signatureimage");

	$user['ougc_signatureimage_description'] = htmlspecialchars_uni($user['ougc_signatureimage_description']);

	if($mybb->settings['ougc_signatureimage_description'] == 1)
	{
		eval("\$ougc_signatureimage_description = \"".$templates->get("ougcsignatureimage_modcp_description")."\";");
	}

	eval("\$ougc_signatureimage = \"".$templates->get("ougcsignatureimage_modcp")."\";");
}

// Delete signature image if user is deleted
function ougc_signatureimage_user_delete()
{
	global $db, $mybb, $user;

	if($user['ougc_signatureimage_type'] == "upload")
	{
		// Removes the ./ at the beginning the timestamp on the end...
		@unlink("../".substr($user['ougc_signatureimage'], 2, -20));
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
			"{$lang->profile_pic_size}<br /><small>{$lang->profile_pic_size_desc}</small><br />".$form->generate_text_box('ougc_signatureimage_maxsize', $mybb->input['ougc_signatureimage_maxsize'], array('id' => 'ougc_signatureimage_maxsize', 'class' => 'field50')). "KB",
			"{$lang->profile_pic_dims}<br /><small>{$lang->profile_pic_dims_desc}</small><br />".$form->generate_text_box('ougc_signatureimage_maxdimensions', $mybb->input['ougc_signatureimage_maxdimensions'], array('id' => 'ougc_signatureimage_maxdimensions', 'class' => 'field'))
		);
		$form_container->output_row($lang->signature_image, "", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $ougc_signatureimage_options)."</div>");
	}
}

function ougc_signatureimage_usergroup_permission_commit()
{
	global $db, $mybb, $updated_group;
	$updated_group['ougc_signatureimage_canuse'] = (int)$mybb->input['ougc_signatureimage_canuse'];
	$updated_group['ougc_signatureimage_canupload'] = (int)$mybb->input['ougc_signatureimage_canupload'];
	$updated_group['ougc_signatureimage_maxsize'] = (int)$mybb->input['ougc_signatureimage_maxsize'];
	$updated_group['ougc_signatureimage_maxdimensions'] = $db->escape_string($mybb->input['ougc_signatureimage_maxdimensions']);
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