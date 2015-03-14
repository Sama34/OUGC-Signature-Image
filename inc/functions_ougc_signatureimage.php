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

/**
 * Remove any matching profile pic for a specific user ID
 *
 * @param int The user ID
 * @param string A file name to be excluded from the removal
 */
function remove_ougc_signatureimage($uid, $exclude="")
{
	global $mybb;

	if(defined('IN_ADMINCP'))
	{
		$ougc_signatureimagepath = '../'.$mybb->settings['ougc_signatureimage_uploadpath'];
	}
	else
	{
		$ougc_signatureimagepath = $mybb->settings['ougc_signatureimage_uploadpath'];
	}

	$dir = opendir($ougc_signatureimagepath);
	if($dir)
	{
		while($file = @readdir($dir))
		{
			if(preg_match("#ougc_signatureimage_".$uid."\.#", $file) && is_file($ougc_signatureimagepath."/".$file) && $file != $exclude)
			{
				@unlink($ougc_signatureimagepath."/".$file);
			}
		}

		@closedir($dir);
	}
}

/**
 * Upload a new profile pic in to the file system
 *
 * @param srray incoming FILE array, if we have one - otherwise takes $_FILES['ougc_signatureimageupload']
 * @param string User ID this profile pic is being uploaded for, if not the current user
 * @return array Array of errors if any, otherwise filename of successful.
 */
function upload_ougc_signatureimage($ougc_signatureimage=array(), $uid=0)
{
	global $db, $mybb, $lang;

	if(!$uid)
	{
		$uid = $mybb->user['uid'];
	}

	if(!$ougc_signatureimage['name'] || !$ougc_signatureimage['tmp_name'])
	{
		$ougc_signatureimage = $_FILES['ougc_signatureimageupload'];
	}

	if(!is_uploaded_file($ougc_signatureimage['tmp_name']))
	{
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}

	// Check we have a valid extension
	$ext = get_extension(my_strtolower($ougc_signatureimage['name']));
	if(!preg_match("#^(gif|jpg|jpeg|jpe|bmp|png)$#i", $ext)) 
	{
		$ret['error'] = $lang->error_ougc_signatureimagetype;
		return $ret;
	}

	if(defined('IN_ADMINCP'))
	{
		$ougc_signatureimagepath = '../'.$mybb->settings['ougc_signatureimage_uploadpath'];
		$lang->load("messages", true);
	}
	else
	{
		$ougc_signatureimagepath = $mybb->settings['ougc_signatureimage_uploadpath'];
	}

	$filename = "ougc_signatureimage_".$uid.".".$ext;
	$file = upload_ougc_signatureimagefile($ougc_signatureimage, $ougc_signatureimagepath, $filename);
	if($file['error'])
	{
		@unlink($ougc_signatureimagepath."/".$filename);		
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}	

	// Lets just double check that it exists
	if(!file_exists($ougc_signatureimagepath."/".$filename))
	{
		$ret['error'] = $lang->error_uploadfailed;
		@unlink($ougc_signatureimagepath."/".$filename);
		return $ret;
	}

	// Check if this is a valid image or not
	$img_dimensions = @getimagesize($ougc_signatureimagepath."/".$filename);
	if(!is_array($img_dimensions))
	{
		@unlink($ougc_signatureimagepath."/".$filename);
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}

	// Check signature image dimensions
	if($mybb->usergroup['ougc_signatureimage_maxdimensions'] != '')
	{
		list($maxwidth, $maxheight) = @explode("x", $mybb->usergroup['ougc_signatureimage_maxdimensions']);
		if(($maxwidth && $img_dimensions[0] > $maxwidth) || ($maxheight && $img_dimensions[1] > $maxheight))
		{
			// Automatic resizing enabled?
			if($mybb->settings['ougc_signatureimage_resizing'] == "auto" || ($mybb->settings['ougc_signatureimage_resizing'] == "user" && $mybb->input['auto_resize'] == 1))
			{
				require_once MYBB_ROOT."inc/functions_image.php";
				$thumbnail = generate_thumbnail($ougc_signatureimagepath."/".$filename, $ougc_signatureimagepath, $filename, $maxheight, $maxwidth);
				if(!$thumbnail['filename'])
				{
					$ret['error'] = $lang->sprintf($lang->error_ougc_signatureimagetoobig, $maxwidth, $maxheight);
					$ret['error'] .= "<br /><br />".$lang->error_ougc_signatureimageresizefailed;
					@unlink($ougc_signatureimagepath."/".$filename);
					return $ret;				
				}
				else
				{
					// Reset filesize
					$ougc_signatureimage['size'] = filesize($ougc_signatureimagepath."/".$filename);
					// Reset dimensions
					$img_dimensions = @getimagesize($ougc_signatureimagepath."/".$filename);
				}
			}
			else
			{
				$ret['error'] = $lang->sprintf($lang->error_ougc_signatureimagetoobig, $maxwidth, $maxheight);
				if($mybb->settings['ougc_signatureimage_resizing'] == "user")
				{
					$ret['error'] .= "<br /><br />".$lang->error_ougc_signatureimageuserresize;
				}
				@unlink($ougc_signatureimagepath."/".$filename);
				return $ret;
			}			
		}
	}

	// Next check the file size
	if($ougc_signatureimage['size'] > ($mybb->usergroup['ougc_signatureimage_maxsize']*1024) && $mybb->usergroup['ougc_signatureimage_maxsize'] > 0)
	{
		@unlink($ougc_signatureimagepath."/".$filename);
		$ret['error'] = $lang->error_uploadsize;
		return $ret;
	}	

	// Check a list of known MIME types to establish what kind of signature image we're uploading
	switch(my_strtolower($ougc_signatureimage['type']))
	{
		case "image/gif":
			$img_type =  1;
			break;
		case "image/jpeg":
		case "image/x-jpg":
		case "image/x-jpeg":
		case "image/pjpeg":
		case "image/jpg":
			$img_type = 2;
			break;
		case "image/png":
		case "image/x-png":
			$img_type = 3;
			break;
		default:
			$img_type = 0;
	}

	// Check if the uploaded file type matches the correct image type (returned by getimagesize)
	if($img_dimensions[2] != $img_type || $img_type == 0)
	{
		$ret['error'] = $lang->error_uploadfailed;
		@unlink($ougc_signatureimagepath."/".$filename);
		return $ret;		
	}
	// Everything is okay so lets delete old signature image for this user
	remove_ougc_signatureimage($uid, $filename);

	$ret = array(
		"ougc_signatureimage" => $mybb->settings['ougc_signatureimage_uploadpath']."/".$filename,
		"width" => intval($img_dimensions[0]),
		"height" => intval($img_dimensions[1])
	);
	return $ret;
}

/**
 * Actually move a file to the uploads directory
 *
 * @param array The PHP $_FILE array for the file
 * @param string The path to save the file in
 * @param string The filename for the file (if blank, current is used)
 */
function upload_ougc_signatureimagefile($file, $path, $filename="")
{
	if(empty($file['name']) || $file['name'] == "none" || $file['size'] < 1)
	{
		$upload['error'] = 1;
		return $upload;
	}

	if(!$filename)
	{
		$filename = $file['name'];
	}

	$upload['original_filename'] = preg_replace("#/$#", "", $file['name']); // Make the filename safe
	$filename = preg_replace("#/$#", "", $filename); // Make the filename safe
	$moved = @move_uploaded_file($file['tmp_name'], $path."/".$filename);

	if(!$moved)
	{
		$upload['error'] = 2;
		return $upload;
	}
	@my_chmod($path."/".$filename, '0644');
	$upload['filename'] = $filename;
	$upload['path'] = $path;
	$upload['type'] = $file['type'];
	$upload['size'] = $file['size'];
	return $upload;
}

/**
 * Formats a signature image to a certain dimension
 *
 * @param string The signature image file name
 * @param string Dimensions of the signature image, width x height (e.g. 44|44)
 * @param string The maximum dimensions of the formatted signature image
 * @return array Information for the formatted signature image
 */
function format_signature_image($ougc_signatureimageture, $dimensions = '', $max_dimensions = '')
{
	global $mybb;
	static $ougc_signatureimagetures;

	if(!isset($ougc_signatureimagetures))
	{
		$ougc_signatureimagetures = array();
	}

	if(!$ougc_signatureimageture)
	{
		// Default signature image
		$ougc_signatureimageture = '';
		$dimensions = '';
	}

	if(isset($ougc_signatureimagetures[$ougc_signatureimageture]))
	{
		return $ougc_signatureimagetures[$ougc_signatureimageture];
	}

	if(!$max_dimensions)
	{
		$max_dimensions = $mybb->usergroup['ougc_signatureimage_maxdimensions'];
	}

	if($dimensions)
	{
		$dimensions = explode("|", $dimensions);

		if($dimensions[0] && $dimensions[1])
		{
			list($max_width, $max_height) = explode('x', $max_dimensions);

			if($dimensions[0] > $max_width || $dimensions[1] > $max_height)
			{
				require_once MYBB_ROOT."inc/functions_image.php";
				$scaled_dimensions = scale_image($dimensions[0], $dimensions[1], $max_width, $max_height);
				$ougc_signatureimageture_width_height = "width=\"{$scaled_dimensions['width']}\" height=\"{$scaled_dimensions['height']}\"";
			}
			else
			{
				$ougc_signatureimageture_width_height = "width=\"{$dimensions[0]}\" height=\"{$dimensions[1]}\"";
			}
		}
	}

	$ougc_signatureimagetures[$ougc_signatureimageture] = array(
		'image' => $mybb->get_asset_url($ougc_signatureimageture),
		'width_height' => $ougc_signatureimageture_width_height
	);

	return $ougc_signatureimagetures[$ougc_signatureimageture];
}