;(function($, window, document, undefined){
	$(document).ready(function(){
		$('a[rel~=sme]').bind('click', function(e){
			e.preventDefault();

			var link        = $(this),
				href        = link.attr('href'),
				title       = link.attr('title'),
				service     = link.data('service'),
				smeServices = {
					google: {
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

			window.open(href, title, 'toolbar=no, width=650, height=450');
		});
	});
})(jQuery, window, document);