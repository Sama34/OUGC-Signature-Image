<?php

/***************************************************************************
 *
 *	OUGC Signature Image plugin (/inc/plugins/ougc_signatureimage.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2015 - 2020 Omar Gonzalez
 *
 *	Based on: Profile Picture plugin [https://github.com/PaulBender/Profile-Pictures/]
 *	By: Starpaul20 (PaulBender) [https://github.com/PaulBender/]
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

require_once MYBB_ROOT.'inc/functions_upload.php';

/**
 * Remove any matching signature image for a specific user ID
 *
 *  @param int $uid The user ID
 * @param string $exclude A file name to be excluded from the removal
 */
function ougc_signatureimage_remove($uid, $exclude="")
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
				require_once MYBB_ROOT."inc/functions_upload.php";
				delete_uploaded_file($ougc_signatureimagepath."/".$file);
			}
		}

		@closedir($dir);
	}
}

/**
 * Upload a new signature image in to the file system
 *
 * @param array $ougc_signatureimage incoming FILE array, if we have one - otherwise takes $_FILES['ougc_signatureimage_upload']
 * @param int $uid User ID this signature image is being uploaded for, if not the current user
 * @return array Array of errors if any, otherwise filename if successful.
 */
function ougc_signatureimage_upload($ougc_signatureimage=array(), $uid=0)
{
	global $db, $mybb, $lang;

	if(!$uid)
	{
		$uid = $mybb->user['uid'];
	}

	if(!$ougc_signatureimage['name'] || !$ougc_signatureimage['tmp_name'])
	{
		$ougc_signatureimage = $_FILES['ougc_signatureimage_upload'];
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
		$ret['error'] = $lang->error_ougc_signatureimage_type;
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
	$file = upload_file($ougc_signatureimage, $ougc_signatureimagepath, $filename);
	if($file['error'])
	{
		delete_uploaded_file($ougc_signatureimagepath."/".$filename);		
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}	

	// Lets just double check that it exists
	if(!file_exists($ougc_signatureimagepath."/".$filename))
	{
		$ret['error'] = $lang->error_uploadfailed;
		delete_uploaded_file($ougc_signatureimagepath."/".$filename);
		return $ret;
	}

	// Check if this is a valid image or not
	$img_dimensions = @getimagesize($ougc_signatureimagepath."/".$filename);
	if(!is_array($img_dimensions))
	{
		delete_uploaded_file($ougc_signatureimagepath."/".$filename);
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
					delete_uploaded_file($ougc_signatureimagepath."/".$filename);
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
				delete_uploaded_file($ougc_signatureimagepath."/".$filename);
				return $ret;
			}			
		}
	}

	// Next check the file size
	if($ougc_signatureimage['size'] > ($mybb->usergroup['ougc_signatureimage_maxsize']*1024) && $mybb->usergroup['ougc_signatureimage_maxsize'] > 0)
	{
		delete_uploaded_file($ougc_signatureimagepath."/".$filename);
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
		case "image/bmp":
			case "image/x-bmp":
			case "image/x-windows-bmp":
				$img_type = 6;
				break;
		default:
			$img_type = 0;
	}

	// Check if the uploaded file type matches the correct image type (returned by getimagesize)
	if($img_dimensions[2] != $img_type || $img_type == 0)
	{
		$ret['error'] = $lang->error_uploadfailed;
		delete_uploaded_file($ougc_signatureimagepath."/".$filename);
		return $ret;		
	}
	// Everything is okay so lets delete old signature image for this user
	ougc_signatureimage_remove($uid, $filename);

	$ret = array(
		"ougc_signatureimage" => $mybb->settings['ougc_signatureimage_uploadpath']."/".$filename,
		"width" => (int)$img_dimensions[0],
		"height" => (int)$img_dimensions[1]
	);
	return $ret;
}

/**
 * Formats a signature image to a certain dimension
 *
 * @param string $ougc_signatureimage The signature image file name
 * @param string $dimensions Dimensions of the signature image, width x height (e.g. 44|44)
 * @param string $max_dimensions The maximum dimensions of the formatted signature image
 * @return array Information for the formatted signature image
 */
function format_signature_image($ougc_signatureimage, $dimensions = '', $max_dimensions = '')
{
	global $mybb;
	static $images;

	if(!isset($images))
	{
		$images = array();
	}

	if(my_strpos($ougc_signatureimage, '://') !== false && !$mybb->settings['ougc_signatureimage_allowremote'])
	{
		// Remote signature images, but remote signature images are disallowed.
		$ougc_signatureimage = null;
	}

	if(!$ougc_signatureimage)
	{
		// Default signature image
		$ougc_signatureimage = '';
		$dimensions = '';
	}

	// An empty key wouldn't work so we need to add a fall back
	$key = $dimensions;
	if(empty($key))
	{
		$key = 'default';
	}
	$key2 = $max_dimensions;
	if(empty($key2))
	{
		$key2 = 'default';
	}

	if(isset($images[$ougc_signatureimage][$key][$key2]))
	{
		return $images[$ougc_signatureimage][$key][$key2];
	}

	$ougc_signatureimage_width_height = '';

	if($dimensions)
	{
		$dimensions = explode("|", $dimensions);

		if($dimensions[0] && $dimensions[1])
		{
			list($max_width, $max_height) = explode('x', $max_dimensions);

			if(!empty($max_dimensions) && ($dimensions[0] > $max_width || $dimensions[1] > $max_height))
			{
				require_once MYBB_ROOT."inc/functions_image.php";
				$scaled_dimensions = scale_image($dimensions[0], $dimensions[1], $max_width, $max_height);
				$width = (int)$scaled_dimensions['width'];
				$height = (int)$scaled_dimensions['height'];
			}
			else
			{
				$width = (int)$dimensions[0];
				$height = (int)$dimensions[1];
			}
		}
	}

	$images[$ougc_signatureimage][$key][$key2] = array(
		'image' => htmlspecialchars_uni($mybb->get_asset_url($ougc_signatureimage)),
		'width' => $width,
		'height' => $height
	);

	return $images[$ougc_signatureimage][$key][$key2];
}