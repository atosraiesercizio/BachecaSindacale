jQuery(document).ready(function($){
	dominio = window.location.hostname;
    dominio = dominio.split(".");
    dominio1L = dominio.pop();
    dominio2L = dominio.pop();
    if(dominio1L == 'it' && dominio2L == 'rai'){ // server RAI...
        //check iniziale
        gto.localSpinner('#vasco_wait');
        data = {
            'action': 'mvasco_save',
            'sub_action': 'check'
        };
        vascoajaxurl = typeof(mvasco_debug_url) != "undefined" ? mvasco_debug_url : mvasco.ajaxurl;
        $.post(vascoajaxurl, data, function(resp){
            vasco_hide_class = '.show_vasco_ok';
            if(resp.esito == "ok"){
                if(resp.iscritto){
                    vasco_hide_class = '.show_vasco_ko';
                } else {
                    vasco_hide_class = '.show_vasco_ok';
                }
            }
            if(resp.esito == "ok" && resp.iscritto && resp.tipolicenza != "ESP" &&  resp.tipolicenza != "VDP"){
                $('#vasco_check').fadeOut('fast', function(){
                    $('#vasco_no_virtual').removeClass("hidden").fadeIn('fast');
                });
            } else if(resp.esito == "ko"){
	            $('#vasco_check').fadeOut('fast', function(){
		            $('#vasco_error').removeClass("hidden").fadeIn('fast');
	            });
            } else {
                $('#vasco_check').fadeOut('fast', function(){
                    $('#vasco_after_check '+vasco_hide_class).hide(0);
                    $('#vasco_after_check input.user_cell').val(resp.telefono);
                    $('#vasco_after_check span.user_cell').text(resp.telefono);
                    $('#vasco_after_check').removeClass("hidden").fadeIn('fast');
	                $('#mvasco_cell').keydown();
                });

            }

        });
    } else { // internal dev.
       /* setTimeout(function(){
            $('#vasco_check').fadeOut('fast', function(){
                $('#vasco_no_virtual').removeClass("hidden").fadeIn('fast');
            });
        }, 2000);
        resp = {
            telefono: '012345678'
        };*/
       vasco_hide_class = '.show_vasco_ko';
        $('#vasco_check').fadeOut('fast', function(){
            $('#vasco_after_check '+vasco_hide_class).hide(0);
            $('#vasco_after_check input.user_cell').val(resp.telefono);
            $('#vasco_after_check span.user_cell').text(resp.telefono);
            $('#vasco_after_check').removeClass("hidden").fadeIn('fast');

        });
    }


    //$('#my-field').bind('keypress', testInput);
    // controllo inserimento caratteri (cifre)
    $('#mvasco_cell').keypress(function(e){
        car = String.fromCharCode(e.which);
        //car = $(this).val();
        var reg = /^[\d\s]$/; // cifre+spazi
        var reg = /^[\d]$/; // solo cifre
        k = e.keyCode ? e.keyCode : e.which;
        console.log(car);
        //e.wich -> 8=backspace 46=delete 37=left 39=right
        //console.log(this.selectionStart);
        if(!reg.test(car)
            && !(k == 8 || k == 46 || k == 37 || k == 39)
            && !(this.selectionStart == 0 && car == '+' && $(this).val().charAt(0) != '+')){
            e.preventDefault();
        } else {
            //console.log('cifra');
        }
        /*var value = String.fromCharCode(e.which);
        var pattern = new RegExp(/^\+?\d+$/i);
        return pattern.test(value);*/
    });
    $('#mvasco_cell').keyup(function(e){
        $(this).val($(this).val().replace('  ', ' '));
        $('#mvasco_attiva, #mvasco_modifica').prop('disabled', $(this).val().length < 10); //3+7 (minimo)
    });
    $('#mvasco_attiva').click(function(e){
        e.preventDefault();
        gto.globalSpinner();
        data = {
            'action': 'mvasco_save',
            'sub_action': 'attiva',
            'telefono': $('#mvasco_cell').val()
        };
        $.post(vascoajaxurl, data, function(resp){
           if(resp.esito == "ok"){
			   $('#vasco_after_check .show_vasco_ko').hide(0);
			   $('#vasco_after_check .show_vasco_ok').fadeIn('fast');
			   $('#vasco_after_check span.user_cell').text(resp.telefono);
			   gto.globalSpinner(false);
               //window.location.href=window.location.href;
           };
            //gto.globalSpinner(false);
        });
    });
    $('#mvasco_disattiva').click(function(e){
        e.preventDefault();
        gto.globalSpinner();
        data = {
            'action': 'mvasco_save',
            'sub_action': 'disattiva',
			'telefono': $('#mvasco_cell').val()
        };
        $.post(vascoajaxurl, data, function(resp){
            if(resp.esito == "ok"){
				$('#vasco_after_check .show_vasco_ok').hide(0);
			   $('#vasco_after_check .show_vasco_ko').fadeIn('fast');
			   gto.globalSpinner(false);
                //window.location.href=window.location.href;
            };
            //gto.globalSpinner(false);
        });
    });
    $('#mvasco_modifica').click(function(e){
        e.preventDefault();
        gto.globalSpinner();
        data = {
            'action': 'mvasco_save',
            'sub_action': 'modifica',
            'telefono': $('#mvasco_cell').val()
        };
        $.post(vascoajaxurl, data, function(resp){
            if(resp.esito == "ok"){
				$.featherlight('<p>Numero modificato con successo</p>');
				$('#vasco_after_check .show_vasco_ko').hide(0);
			   $('#vasco_after_check .show_vasco_ok').fadeIn('fast');
			   $('#vasco_after_check span.user_cell').text(resp.telefono);
			   gto.globalSpinner(false);
                //window.location.href=window.location.href;
            };
            //gto.globalSpinner(false);
        });
    });
});