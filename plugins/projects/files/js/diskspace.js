/**
 * @package     hubzero-cms
 * @file        plugins/projects/files/js/diskspace.js
 * @copyright   Copyright 2005-2011 Purdue University. All rights reserved.
 * @license     http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

//-----------------------------------------------------------
//  Ensure we have our namespace
//-----------------------------------------------------------
if (!HUB) {
	var HUB = {};
}

//----------------------------------------------------------
//  Members scripts
//----------------------------------------------------------
if (!jq) {
	var jq = $;
}

HUB.ProjectFilesDiskSpace = {
	jQuery: jq,

	initialize: function() {
		var $ = this.jQuery;
		
		// Disk usage indicator
		if ($('#indicator-area').length) {
			var percentage = $('#indicator-area').attr('class');
			percentage = percentage.replace('used:', '');
			if (isNaN(percentage)) {
				percentage = 0;
			}
			percentage = Math.round(percentage);
			var measurein = 'px';
			if ($('#disk-usage')) {
				var measurein = '%';
			}
			$('#indicator-area').css('width', percentage + measurein);				
		}
		
		// Disk usage for files in working dir
		if ($('#actual-area').length) {
			var percentage = $('#actual-area').attr('class');
			percentage = percentage.replace('actual:', '');
			if (isNaN(percentage)) {
				percentage = 0;
			}
			percentage = Math.round(percentage);
			var measurein = 'px';
			if ($('#disk-usage')) {
				var measurein = '%';
			}
			$('#actual-area').css('width', percentage + measurein);				
		}

		if ($('.disk-usage-optimize').length > 0) 
		{
			$('.disk-usage-optimize').each(function(i, item) 
			{
				$(item).on('click', function(e){
					HUB.ProjectFiles.submitViaAjax('Optimizing disk space. Please wait...');
				});	
			});	
		}
		
	}
};

jQuery(document).ready(function($){
	HUB.ProjectFilesDiskSpace.initialize();
});