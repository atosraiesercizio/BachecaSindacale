jQuery(document).ready(function($) {
	//generici
	$('.my_elenco .navigation > .add-btn').click(function() {
		$(this).text($(this).text() == '+' ? '–' : '+');
		$(this).toggleClass('minus');
		$(this).next().slideToggle('fast');
	});

	/* MY APPS */
	$('#elenco-app').on("click", '.active-items .icn-delete', function() {
		var $t = $(this);
		titolo = $(this).parent().prev().text();
		confirm = '<div class="text-center"><div>Vuoi davvero rimuovere dall\'elenco personalizzato la app:<br><span class="titolo">"'+titolo+'"</span>?</div><div class="small">(La app in sé non verrà toccata in alcun modo: sarà solo esclusa dal tuo personale elenco)</div><div class="actions"><button class="btn cancel">Annulla</button> <button class="btn ok" id="myapp_del_confirm">Sì, rimuovila</button></div></div>';
		$.featherlight(confirm, {
			loading:        '<div class="raispinner"></div>',
			afterContent: function(){
				fl = this;
				$('.featherlight button.btn').click(function(el) {
					fl.close();
				});
				$('#myapp_del_confirm').click(function() {
					$t.closest('.item').appendTo($('#elenco-app .more-apps'));
					mySaveApps();
				});
			}
		});
	});

	$('#elenco-app').on("click", '.more-apps .icn-add', function() {
		$(this).closest('.item').prependTo($('#elenco-app .active-items'));
		$('#elenco-app .active-items .no-results').remove();
		mySaveApps();
	});
	function mySaveApps() {
		var aIds = [];
		//msgt.localSpinner($('#elenco-app'));
		$('#elenco-app > .active-items > .item').each(function(i) {
			aIds.push(parseInt($(this).data('appid')));
		});
		var data = {
			'action' : 'myapps_save',
			'ids' : aIds
		};
		$.post(msg_my_vals.ajaxurl, data, function(response) {
			console.log(response);
			//msgt.localSpinner($('#elenco-app'), false);
			myAppSortable();
			if ($('#elenco-app .active-items .item').length == 0) {
				$('#elenco-app .active-items').html('<div class="no-results">' + msg_my_vals.noapps + '</div>');
			} else {
				$('#elenco-app .active-items .no-results').remove();
			}
		});
	}

	function myAppSortable() {
		/*$('#elenco-app .active-items').sortable({
			handle : '.handle'
		}).bind('sortupdate', function(e, ui) {
			mySaveApps();
		});*/
	}

	myAppSortable();

	/* MY LINKS */
	my_active_link = false;
	$('#elenco-links .navigation > .add-btn').click(function() {
		$('#edit-links input').val('');
		$('#elenco-links .active-items .item.on').removeClass("on");
		my_active_link = false;
	});
	$('#elenco-links').on("click", '.active-items .icn-edit', function() {
		$itm = $(this).closest('.item');
		$('#elenco-links .active-items .item.on').removeClass("on");
		if (!$itm.hasClass('on')) {
			$itm.addClass("on");
			my_active_link = $itm;
			$('#edit-links #mylink_titolo').val($itm.find('.titolo').text());
			$('#edit-links #mylink_url').val($itm.find('.url').text());
			$('#edit-links').slideDown('fast');
			$('#elenco-links .navigation > .add-btn').text('–').addClass('minus');
		} else {
			$('#edit-links input').val('');
			my_active_link = false;
			$('#edit-links').slideUp('fast');
			$('#elenco-links .navigation > .add-btn').text('+').removeClass('minus');
		}
	});
	$('#elenco-links').on("click", '.active-items .icn-delete', function() {
		var $t = $(this);
		titolo = $(this).parent().prev().children('.titolo').text();
		confirm = '<div class="text-center"><div>Vuoi davvero eliminare il link personalizzato:<br><span class="titolo">"'+titolo+'"</span>?</div><div class="actions"><button class="btn cancel">Annulla</button> <button class="btn ok" id="mylink_del_confirm">Sì, eliminalo</button></div></div>';
		$.featherlight(confirm, {
			loading:        '<div class="raispinner"></div>',
			afterContent: function(){
				fl = this;
				$('.featherlight button.btn').click(function(el) {
					fl.close();
				});
				$('#mylink_del_confirm').click(function() {
					$t.closest('.item').remove();
					mySaveLinks();
				});
			}
		});
		/*
		*/
	});
	$('#mylink_save').click(function(){
		var validurlstr =  /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
		validurlstr = /^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/i;
		var validurlregex = new RegExp(validurlstr);
		if($('#mylink_url').val().match(validurlstr)!=null){
			var myttl = $('#mylink_titolo').val() ? $('#mylink_titolo').val() : $('#mylink_url').val();
			var myurl = $('#mylink_url').val();
			if(my_active_link){ //edit existent
				my_active_link.find('.titolo').text(myttl);
				my_active_link.find('.url').text(myurl);
			} else { //insert new
				newLink = '<div class="item">';
				newLink += '<span class="handle">#</span>';
				newLink += '<a href="'+myurl+'"><span class="titolo">'+myttl+'</span> <span class="url">'+myurl+'</span></a>';
				newLink += '<div class="vis-over"><span class="icn-delete">-</span><span class="icn-edit">●</span></div>';
				newLink += '</div>';
				$('#elenco-links .active-items').append(newLink);
			}
			mySaveLinks();
		} else {
			alert("Per salvare il link è necessario un indirizzo valido nel campo URL");
			$('#mylink_url').focus();
		}
	});
	function mySaveLinks() {
		$('#edit-links input').val('');
		$('#elenco-links .active-items .item.on').removeClass("on");
		my_active_link = false;
		var aLinks = [];
		//msgt.localSpinner($('#elenco-links'));
		$('#elenco-links > .active-items > .item').each(function(i) {
			aLinks.push([$(this).find('.titolo').text(), $(this).find('.url').text()]);
		});
		var data = {
			'action' : 'mylinks_save',
			'links' : aLinks
		};
		$.post(msg_my_vals.ajaxurl, data, function(response) {
			console.log(response);
			//msgt.localSpinner($('#elenco-links'), false);
			myLinksSortable();
			$('#edit-links').slideUp('fast');
			if ($('#elenco-links .active-items .item').length == 0) {
				$('#elenco-links .active-items').html('<div class="no-results">' + msg_my_vals.nolinks + '</div>');
			} else {
				$('#elenco-links .active-items .no-results').remove();
			}
			
		});
	}
	
	function myLinksSortable() {
		/*$('#elenco-links .active-items').sortable({
			handle : '.handle'
		}).bind('sortupdate', function(e, ui) {
			mySaveLinks();
		});*/
	}

	myLinksSortable();
	$(window).load(function(){
		$('#edit-links input').val('');
	});

}); 