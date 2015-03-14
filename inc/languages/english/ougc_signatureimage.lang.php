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

$l['setting_group_ougc_signatureimage'] = "OUGC Signature Image";
$l['setting_group_ougc_signatureimage_desc'] = "Allows users to upload a picture to display in their signature.";

$l['signature_image'] = "Signature Image";
$l['users_ougc_signatureimage'] = "{1}'s Signature Image";
$l['changing_ougc_signatureimage'] = "<a href=\"usercp.php?action=ougc_signatureimage\">Changing Signature Image</a>";
$l['remove_signature_image'] = "Remove user's signature image?";
$l['can_use_ougc_signatureimage'] = "Can use signature image?";
$l['can_upload_ougc_signatureimage'] = "Can upload signature image?";
$l['profile_pic_size'] = "Maximum File Size:";
$l['profile_pic_size_desc'] = "Maximum file size of an uploaded signature image in kilobytes. If set to 0, there is no limit.";
$l['profile_pic_dims'] = "Maximum Dimensions:";
$l['profile_pic_dims_desc'] = "Maximum dimensions a signature image can be, in the format of width<strong>x</strong>height. If this is left blank then there will be no dimension restriction.";

$l['signature_image_upload_dir'] = "Signature Image Uploads Directory";

$l['nav_usercp'] = "User Control Panel";
$l['ucp_nav_change_ougc_signatureimage'] = "Change Signature Image";
$l['change_ougc_signatureimageture'] = "Change Signature Image";
$l['change_image'] = "Change Image";
$l['remove_image'] = "Remove Image";
$l['ougc_signatureimage_url'] = "Signature Image URL:";
$l['ougc_signatureimage_url_note'] = "Enter the URL of a signature image on the internet.";
$l['ougc_signatureimage_url_gravatar'] = "To use a <a href=\"http://gravatar.com\" target=\"_blank\">Gravatar</a> enter your Gravatar email.";
$l['ougc_signatureimage_description'] = "Signature Image Description:";
$l['ougc_signatureimage_description_note'] = "(Optional) Add a brief description of your signature image.";
$l['ougc_signatureimage_upload'] = "Upload Signature Image:";
$l['ougc_signatureimage_upload_note'] = "Choose a signature image on your local computer to upload.";
$l['ougc_signatureimage_note'] = "A signature image is a small identifying image shown in a user's profile.";
$l['ougc_signatureimage_note_dimensions'] = "The maximum dimensions for signature images are: {1}x{2} pixels.";
$l['ougc_signatureimage_note_size'] = "The maximum file size for signature images is {1}.";
$l['custom_profile_pic'] = "Custom Signature Image";
$l['already_uploaded_ougc_signatureimage'] = "You are currently using an uploaded signature image. If you upload another one, your old one will be deleted.";
$l['ougc_signatureimage_auto_resize_note'] = "If your signature image is too large, it will automatically be resized.";
$l['ougc_signatureimage_auto_resize_option'] = "Try to resize my signature image if it is too large.";
$l['redirect_ougc_signatureimageupdated'] = "Your signature image has been changed successfully.<br />You will now be returned to your User CP.";
$l['using_remote_ougc_signatureimage'] = "You are currently using an remote signature image.";
$l['signature_image_mine'] = "This is your Signature Image";

$l['error_uploadfailed'] = "The file upload failed. Please choose a valid file and try again. ";
$l['error_ougc_signatureimagetype'] = "Invalid file type. An uploaded signature image must be in GIF, JPEG, or PNG format.";
$l['error_invalidougc_signatureimageurl'] = "The URL you entered for your signature image does not appear to be valid. Please ensure you enter a valid URL.";
$l['error_ougc_signatureimagetoobig'] = "Sorry but we cannot change your signature image as the new image you specified is too big. The maximum dimensions are {1}x{2} (width x height)";
$l['error_ougc_signatureimageresizefailed'] = "Your signature image was unable to be resized so that it is within the required dimensions.";
$l['error_ougc_signatureimageuserresize'] = "You can also try checking the 'attempt to resize my signature image' check box and uploading the same image again.";
$l['error_uploadsize'] = "The size of the uploaded file is too large.";
$l['error_descriptiontoobig'] = "Your signature image description is too long. The maximum length for descriptions is 255 characters.";