global.jQuery = require('../../bower_components/jquery-legacy/jquery');
var page = require('./components/page');
var post = require('./components/post');
var async = require('./components/async');
var accordion = require('./components/accordion');
var tabs = require('./components/tabs');
var validator = require('./components/validator');
require('../../bower_components/chosen/chosen.jquery.js');

jQuery(document).ready(() => {

	page.init();
	post.init();
	async.init(appConfig);

	if (jQuery.fn.chosen) {
		jQuery(".chosen-select").chosen();
	}

	validator({keypress: false});
	tabs(jQuery('#browseTabs'));
	accordion(jQuery('#accordion'));
});
