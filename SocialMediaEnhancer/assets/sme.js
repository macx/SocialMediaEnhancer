;(function($, window, document, undefined){
	$(document).ready(function(){
		$('.smeSocial a').bind('click', function(e){
			e.preventDefault();

			var link        = $(this),
				href        = link.attr('href'),
				title       = link.attr('title'),
				service     = link.data('service'),
				smeServices = {
					googlePlus: {
						site:   'Google',
						action: '+1'
					},
					twitter: {
						site:   'Twitter',
						action: 'tweet'
					},
					facebook: {
						site:   'Facebook',
						action: 'share'
					}
				};

			if(!title) {
				title = 'SocialMediaEnhancer';
			}

			if(typeof _gaq != 'undefined') {
				_gaq.push(['_trackSocial', smeServices[service].site, smeServices[service].action]);
			}
		});
	});
})(jQuery, window, document);