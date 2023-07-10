jQuery(document).ready(function($) {
	$('.bookmark-toggle').click(function(e){
		$toggler = $(this);
		myToggleClick(e, $toggler);
	});
	function myToggleClick(e, $toggler){
		e.preventDefault();
		e.stopImmediatePropagation();
		var data = {
			'action' : 'my_save',
			'sub_action' : $toggler.attr('data-azione'),
			'id' : $toggler.data('id'),
			'tipo' : $toggler.data('tipo'),
		};

		gto.globalSpinner();
		$.post(msg_my_vals.ajaxurl, data, function(response) {
			if(response.esito == "ok"){
				azVerbo = $toggler.attr('data-azione') == 'add' ? 'remove' : 'add';
				azTit = $toggler.attr('title') == msg_my_vals.txt_add ? msg_my_vals.txt_rmv : msg_my_vals.txt_add;
				if(!$toggler.hasClass('no-text')){
					$toggler.children('span').children('em').text(azTit);
				}
				$toggler.children('span').toggleClass("ico-bookmark_full")
				$toggler.attr('data-azione', azVerbo);
				$toggler.attr('title', azTit);
				if($('body').hasClass("mmy_links_page")){ // è la pagina della lista: elimino l'elemento
					$toggler.closest('.item').fadeOut('fast');
				}
				data2 = { 'action': 'my_sidebar_list'};
				$.post(msg_my_vals.ajaxurl, data2, function(response2) {
					if(response2.esito == "ok"){
						$('#mmy_hidden_sidebar').html(response2.html);
						if($('.mmy-hp-list').length){
							$('.mmy-hp-list.app').parent().html(response2.hp_app);
							$('.mmy-hp-list.link').parent().html(response2.hp_link);
						}

					}
					gto.globalSpinner(false);
					$('.mmy-hp-list .bookmark-toggle').click(function(e){
						myToggleClick(e, $(this));
					});
				});
			}

		});
	}
	mmy_sidebar_w = $('#mmy_hidden_sidebar').outerWidth();
	$('#mmy_sidebar_link > a').click(function(e){
		e.preventDefault();
		$(this).children('span').toggleClass('ico-bookmark_full');
		sright = $(this).children('span').hasClass('ico-bookmark_full') ? 0 : -mmy_sidebar_w;
		$('#mmy_hidden_sidebar').css('right', sright);
		if($(this).children('span').hasClass('ico-bookmark_full')){
			$(document).on('click', function(e){
				if (!$('#mmy_sidebar_link').is(e.target) && $('#mmy_sidebar_link').has(e.target).length === 0){
					$('#mmy_hidden_sidebar').css('right', -mmy_sidebar_w);
					$('#mmy_sidebar_link > a > span').removeClass("ico-bookmark_full");
					$(document).off('click');
				}
			});
		} else {
			$(document).off('click');
		}
	});

}); 