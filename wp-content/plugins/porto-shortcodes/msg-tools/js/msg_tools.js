jQuery(document).ready(function($){
    /*** zipbasket ***/
    okExt = [ 'doc', 'docx', 'pdf', 'xls', 'xlsx', 'ppt', 'pptx', 'zip']
    zipLinks = [];
    $('#content > article > .page-content a[href]').each(function(){
        if( location.hostname === this.hostname || !this.hostname.length ) { // indirizzo locale
            ext = this.pathname.split('.').pop();
            if($.inArray(ext, okExt) > -1){ // ha un'estensione accettabile
                zipLinks.push(this.pathname);
            }

        }
    });
    if(zipLinks.length > 1){ // abbiamo più di un "allegato"
        $('#content > article > .page-content').append(
            '<div class="vc_row vc_row-fluid zipbasket-wrapper"><a id="zipbasket" class="btn btn-default ti-download">Scarica tutti gli allegati</a></div>'
        );
        $('#zipbasket').on("click", function(e){
            e.preventDefault();
            gto.globalSpinner();
            data = {
                'action': 'msg_zipbasket',
                'files': zipLinks
            }
            $.post(msg_tools.ajaxurl, data, function(resp){
                if(resp.esito == "ok"){
                    location.href = resp.zipurl;
                }
                gto.globalSpinner(false);
            });
        });
    }
    /* MENU 1° LIVELLO ATTIVO (speciale: guidato dal Custom Type) */
    activeId = $('#main .left-sidebar .special-active').length ? $('#main .left-sidebar .special-active').attr('id') : false;
    if(activeId){
        $('#menu-principale li#nav-'+activeId).parentsUntil('#main-menu', 'li.menu-item').addClass('active');
    }
});