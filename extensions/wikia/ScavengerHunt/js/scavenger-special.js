var SpecialScavengerHunt = {
	entry: null,

	init: function() {
		SpecialScavengerHunt.entry = $('.scavenger-article').eq(0).clone();
		SpecialScavengerHunt.entry.find('input').val('');
		$('#gameName').focus();
		$('.scavenger-form').delegate('.scavenger-page-title', 'blur', SpecialScavengerHunt.onPageTitleBlur);
		$('input[name=delete]').bind('click.sumbit', SpecialScavengerHunt.onDeleteClick);
		$('.scavenger-form').delegate('.scavenger-dialog-check', 'click', SpecialScavengerHunt.onDialogCheckClick);
		$('.scavenger-form').delegate('input[type=text], textarea', 'change', SpecialScavengerHunt.onDataChange);
	},

	log: function(msg) {
		$().log(msg, 'SpecialScavengerHunt');
	},

	onPageTitleBlur: function(e) {
		var titles = $('.scavenger-page-title');
		var count = titles.length;
		titles.each(function(i,e) {
			if ($(this).val()) {
				count--;
			}
		});

		if (!count) {
			$('.scavenger-article').last().after(SpecialScavengerHunt.entry.clone())
		}
	},

	onDeleteClick: function(e) {
		e.preventDefault();
		var button = $(this);
		$.confirm({
			//TODO: add i18n if this extension will be used outside staff group
			content: 'Are you sure to delete this game?',
			onOk: function() {
				button.unbind('.sumbit').click();
			}
		});
	},
	
	onDataChange: function(e) {
		$('.scavenger-form .buttons input[type=submit]').not('[name=save]').attr('disabled','disabled');
	},

	onDialogCheckClick: function(e) {
		e.preventDefault();

		var fieldset = $(this).closest('fieldset');
		var type = fieldset.attr('class');
		var formData = {};
		fieldset.find('input, textarea').each(function(i, el){
			formData[$(el).attr('name')] = $(el).val();
		});

		var data = {
			action: 'ajax',
			method: 'getPreviewForm',
			rs: 'ScavengerHuntAjax',
			formData: $.toJSON(formData),
			type: type
		};

		$.getJSON(wgScript, data, function(json) {
			$.showModal(
				json.title,
				json.content,
				{
					id: 'scavengerClueModal',
					showCloseButton: false,
					width: 588
				}
			);
		});
	}
};

$(SpecialScavengerHunt.init);