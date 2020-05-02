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

$l['setting_group_ougc_signatureimage'] = "OUGC Signature Image";
$l['setting_group_ougc_signatureimage_desc'] = "Allows your users to upload an image to display in their signature.";
$l['setting_group_ougc_signatureimage_desc_more'] = "<div style=\"color: red;\">Based on <a href=\"http://galaxiesrealm.com/index.php\">Starpaul20</a>'s <a href=\"https://github.com/PaulBender/Profile-Pictures/\">Profile Picture</a> plugin for MyBB 1.8.</div>";

// Settings
$l['setting_ougc_signatureimage_uploadpath'] = 'Signature Images Upload Path';
$l['setting_ougc_signatureimage_uploadpath_desc'] = 'This is the path where signature images will be uploaded to. This directory <strong>must be chmod 777</strong> (writable) for uploads to work.';
$l['setting_ougc_signatureimage_resizing'] = 'Signature Images Resizing Mode';
$l['setting_ougc_signatureimage_resizing_desc'] = 'If you wish to automatically resize all large signature images, provide users the option of resizing their signature image, or not resize signature images at all you can change this setting.';
$l['setting_ougc_signatureimage_resizing_auto'] = 'Automatically resize large signature images';
$l['setting_ougc_signatureimage_resizing_user'] = 'Give users the choice of resizing large signature images';
$l['setting_ougc_signatureimage_resizing_disabled'] = 'Disable this feature';
$l['setting_ougc_signatureimage_allowremote'] = 'Allow Remote Signature Images';
$l['setting_ougc_signatureimage_allowremote_desc'] = 'Whether to allow the usage of signature images from remote servers. Having this enabled can expose your server\'s IP address.';

// Admin CP
$l['signature_image'] = "Signature Image";
$l['can_use_ougc_signatureimage'] = "Can use signature image?";
$l['can_upload_ougc_signatureimage'] = "Can upload signature image?";
$l['ougc_signatureimage_size'] = "Maximum File Size:";
$l['ougc_signatureimage_size_desc'] = "Maximum file size of an uploaded signature image in kilobytes. If set to 0, there is no limit.";
$l['ougc_signatureimage_dims'] = "Maximum Dimensions:";
$l['ougc_signatureimage_dims_desc'] = "Maximum dimensions a signature image can be, in the format of width<strong>x</strong>height. If this is left blank then there will be no dimension restriction.";

$l['signature_image_upload_dir'] = "Signature Image Uploads Directory";

// Front end
$l['users_ougc_signatureimage'] = "{1}'s Signature Image";
$l['changing_ougc_signatureimage'] = "Changing Signature Image";
$l['remove_signature_image'] = "Remove user's signature image?";

$l['nav_usercp'] = "User Control Panel";
$l['ucp_nav_change_ougc_signatureimage'] = "Change Signature Image";
$l['change_ougc_signatureimage'] = "Change Signature Image";
$l['change_image'] = "Change Image";
$l['remove_image'] = "Remove Image";
$l['ougc_signatureimage_url'] = "Signature Image URL:";
$l['ougc_signatureimage_url_note'] = "Enter the URL of a signature image on the internet.";
$l['ougc_signatureimage_url_gravatar'] = "To use a <a href=\"http://gravatar.com\" target=\"_blank\">Gravatar</a> enter your Gravatar email.";
$l['ougc_signatureimage_upload'] = "Upload Signature Image:";
$l['ougc_signatureimage_upload_note'] = "Choose a signature image on your local computer to upload.";
$l['ougc_signatureimage_note'] = "A signature image is a small identifying image shown in a user's profile.";
$l['ougc_signatureimage_note_dimensions'] = "The maximum dimensions for signature images are: {1}x{2} pixels.";
$l['ougc_signatureimage_note_size'] = "The maximum file size for signature images is {1}.";
$l['ougc_signatureimage_custom'] = "Custom Signature Image";
$l['already_uploaded_ougc_signatureimage'] = "You are currently using an uploaded signature image. If you choose to use another one, your old signature image will be deleted from the server.";
$l['ougc_signatureimage_auto_resize_note'] = "If your signature image is too large, it will automatically be resized.";
$l['ougc_signatureimage_auto_resize_option'] = "Try to resize my signature image if it is too large.";
$l['redirect_ougc_signatureimageupdated'] = "Your signature image has been changed successfully.<br />You will now be returned to your User CP.";
$l['using_remote_ougc_signatureimage'] = "You are currently using a signature image from a remote site. If you choose to use another one, your old signature image URL will be emptied.";
$l['signature_image_mine'] = "This is your Signature Image";

$l['error_uploadfailed'] = "The file upload failed. Please choose a valid file and try again. ";
$l['error_ougc_signatureimagetype'] = "Invalid file type. An uploaded signature image must be in GIF, JPEG, BMP or PNG format.";
$l['error_invalidougc_signatureimageurl'] = "The URL you entered for your signature image does not appear to be valid. Please ensure you enter a valid URL.";
$l['error_ougc_signatureimagetoobig'] = "Sorry, but we cannot change your signature image as the new image you specified is too big. The maximum dimensions are {1}x{2} (width x height)";
$l['error_ougc_signatureimageresizefailed'] = "Your signature image was unable to be resized so that it is within the required dimensions.";
$l['error_ougc_signatureimageuserresize'] = "You can also try checking the 'attempt to resize my signature image' check box and uploading the same image again.";
$l['error_uploadsize'] = "The size of the uploaded file is too large.";
$l['ougc_signatureimage_error_remote_not_allowed'] = "Remote signature image URLs have been disabled by the forum administrator.";
$l['ougc_signatureimage_remote_disabled'] = "You are currently using a remote signature image, which has been disabled. Your signature image has been hidden.";

$l['ougc_signatureimage_current'] = "Current Signature Image";
$l['ougc_signatureimage_remove_admin'] = "Remove current signature image?";
$l['ougc_signatureimage_user_current_using_uploaded'] = "This user is currently using an uploaded signature image.";
$l['ougc_signatureimage_user_current_using_remote'] = "This user is currently using a remotely linked signature image.";
$l['ougc_signatureimage_specify_custom'] = "Specify Custom Signature Image";
$l['ougc_signatureimage_upload'] = "Upload Signature Image";
$l['ougc_signatureimage_auto_resize'] = "If the signature image is too large, it will automatically be resized";
$l['ougc_signatureimage_or_specify_url'] = "or Specify Signature Image/Gravatar URL";
$l['ougc_signatureimage_max_size'] = "Signature Image can be a maximum of";
$l['ougc_signatureimage_desc'] = "Below you can manage the signature image for this user. Signature Image are small identifying images shown in a user's profile.";
$l['ougc_signatureimage_max_dimensions_are'] = "The maximum dimensions for signature image are";
$l['ougc_signatureimage_attempt_to_auto_resize'] = "Attempt to resize this signature image if it is too large?";