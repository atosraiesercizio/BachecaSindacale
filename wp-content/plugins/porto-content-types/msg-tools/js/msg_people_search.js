jQuery(document).ready(function($){
    $('#cognome').keyup(function(e){
        $('#people_search button').prop('disabled', $(this).val().length < 3);
    });
    $('#people_search button').click(function(e){
        e.preventDefault();
        data = {
            'action' : 'msg_people_search',
            'cognome' : $('#cognome').val()
        };
        gto.localSpinner('#content', true);
        $.post(msg_people.ajax_url, data, function(resp){
            serp = '';
            gto.localSpinner('#content', false);
            if(resp.esito == 'ok'){
                serp += '<h3>Risultati per "<em>'+data.cognome+'</em>"</h3>';
                $.each(resp.utenti, function(i, ut){
                    serp += '<div class="utente vista-elem item">';
                    serp += '<div class="img">'+ut.img+'</div><div class="meta">';
                    serp += '<div class="nome-cognome titolo">'+ut.nome+' '+ut.cognome+'</div>';
                    serp += '<div class="email"><a href="mailto:'+ut.email+'">'+ut.email+'</a></div>';
                    serp += '<div class="struttura"><span>Struttura:</span> '+ut.struttura+'</div>';
                    serp += '<div class="matricola"><span>Matricola:</span> '+ut.matricola+'</div>';
                    serp += '</div></div>';
                });
                serp += '</div>';
            } else {
                serp = '<h3 class="no-result">Nessun risultato per "<em>'+data.cognome+'</em>"</h3>';
            }
            $('#people_serp').html(serp);
        });
    });
	$('#cognome').keypress(function(e){
		if(e.which == 13){//tasto enter
            if($(this).val().length >= 3){
	            $('#people_search button').click(); //trigger
            }

		}
	});

    $(window).load(function(){ // i campi vengono popolati al reload solo dopo window.load (DOPO document.ready)
        $('#people_search button').prop('disabled', $('#cognome').val().length < 3);
    });
});