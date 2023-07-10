jQuery(document).ready(function($){
   if($('body').hasClass('post-new-php') || $('body').hasClass('post-php')){
       //categorie con radio
       specialTags = [
           'tipo-di-news',
           'tipo-di-app',
           'tipo-di-attivita-e-iniziative',
           'tipo-di-comunicazioni-int',
           'tipo-di-evento',
           'tipo-di-previdenza-e-salute',
           'tipo-di-servizi-convenzioni',
           'luogo',
           'mansione'
       ];


       tagsdivs = new Array();
       $.each(specialTags, function(i, tag){
           if($('.categorydiv#taxonomy-'+tag).length){
               $tagsdiv = $('.categorydiv#taxonomy-'+tag);
               $checkboxes = $tagsdiv.find('.categorychecklist').find('input:checkbox');
               $checkboxes.each(function(j, el){
                   $checkb = $(el);
                   $checkb.attr('type', 'radio');
               });

           }
       });
       // categorie obblig
       reqTags = [
           'tipo-di-news',
           'tipo-di-app',
           'tipo-di-attivita-e-iniziative',
           'tipo-di-comunicazioni-int',
           'tipo-di-evento',
           'tipo-di-previdenza-e-salute',
           'tipo-di-servizi-convenzioni',
           //'luogo',
           'mansione'
       ];

       if(!$('body').hasClass('post-type-comunicazione-int')){ // non obblig. per le comunicazioni interne
           reqTags.push('luogo');
       }
       $('form#post').on('submit', function(e){
           $.each(reqTags, function(i, tag){
               $tagsdiv = $('.categorydiv#taxonomy-'+tag);
               if($tagsdiv.length && !$tagsdiv.find('.categorychecklist').find('input:checked').length){
                   $('#publish').removeClass('button-primary-disabled');
                   alert("Devi inserire almeno una categoria '"+$tagsdiv.closest('.postbox').children('h2').text()+"'");
                   e.preventDefault();
               }
           });

       });

   }
});