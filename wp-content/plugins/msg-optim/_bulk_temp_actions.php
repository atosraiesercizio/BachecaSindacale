<?php
add_action('rest_api_init', function () {
    register_rest_route('tmp_rai_rest', '/update_old_posts', array(
        'methods' => 'GET',
        'callback' => 'rest_action_update_old_posts',
    ));
	/*
	// sistema temporaneo di bulk creazione posts (!disabilitato), v. sotto
	register_rest_route('tmp_rest', '/create', array(
		'methods' => 'GET',
		'callback' => 'rest_action_create_posts',
	));
	*/
});

function rest_action_update_old_posts(){
    header('Content-Type: text/html; charset=utf-8');
    global $wpdb;
    $out = '';
    $cust_tables = getTypeTable();
    $out .= '<div style="text-align:center;"><big>Attendi gli aggiornamenti di pagina fino a quando <b>tutti</b> i tipi di post non sono stati importati</big></div>';
    $out .= '<div style="text-align:center;"><small>La procedura modifica al massimo 1000 (250x4) post ad ogni passo/reload</small></div><hr>';
    foreach($cust_tables as $tipo => $cust_table){
        $sqlSelectCount = "SELECT COUNT(wp_posts.ID) AS tot from wp_posts LEFT JOIN $cust_table rpj ON wp_posts.ID = rpj.post_id WHERE rpj.post_id IS NULL AND wp_posts.post_type = '$tipo'";
        $sqlSelect = "SELECT wp_posts.ID from wp_posts LEFT JOIN $cust_table rpj ON wp_posts.ID = rpj.post_id WHERE rpj.post_id IS NULL AND wp_posts.post_type = '$tipo' LIMIT 250";
        $out .= "<h3>Importazione post di tipo '$tipo' (nella tabella '$cust_table')</h3>";
        $righe = $wpdb->get_results($sqlSelect);
        $nrighe = 0;
        $totGen = 0;
        foreach($righe as $row){
            $post_id = (int)$row->ID;
            //$cpost = get_post($post_id);
            $cfields = $custom_fields = get_post_custom($post_id);
            //var_dump($cfields);
            //*
            $visibile_home = (int)$cfields['wpcf-visibile-in-home-page'][0];
            $priorita = (int)$cfields['wpcf-priorita'][0];
            $emittente = (string)$cfields['wpcf-emittente'][0];
            $data_pubb = (int)$cfields['wpcf-data-pubblicazione'][0];
            if($tipo == 'evento'){
                $kinizio = 'data_evento';
                $kfine = 'data_fine_evento';
                $inizio =(int) $cfields['wpcf-data-evento'][0];
                $fine = (int)$cfields['wpcf-data-fine-evento'][0];
            } else {
                $kinizio = 'visibile_dal';
                $kfine = 'al';
                $inizio = (int)$cfields['wpcf-visibile-dal'][0];
                $fine = (int)$cfields['wpcf-al'][0];
            }
            $colvalues = [
                'post_id' => $post_id,
                'visibile_in_home_page' => $visibile_home,
                'priorita' => $priorita,
                'emittente' => $emittente,
                'data_pubblicazione' => $data_pubb,
                $kinizio => $inizio,
                $kfine => $fine
            ];
            $coltypes = [
                '%d',
                '%d',
                '%d',
                '%s',
                '%d',
                '%d',
                '%d',
            ];

            $wpdb->insert(
                $cust_table,
                $colvalues,
                $coltypes
            );
            $nrighe++;
            //print '. ';
            //*/
            //print $row->ID." - ".$wpdb->last_query."<br>";
        }
        //print "<pre>$sqlSelect</pre>";
        $tot = (int)$wpdb->get_var($sqlSelectCount);
        $totGen += $tot;
        if($tot){
            $out .= "<div>Importati $nrighe</b> post: <b>ne restano ancora <big style='color: red;'>".$tot."</big></b> da importare.</div>";
        } else {
            $out .= "<div>OK: Importati tutti i post.</div>";
        }


    }
    $out = '<body style="font-family: Arial, Helvetica, sans-serif; padding:40px;">'.$out;
    if($totGen){
        $out .= '<hr style="margin: 20px 0"><div style="text-align:center;"><b>ATTENDI IL RELOAD DELLA PAGINA</b></div>';
        $out .= '<script type="text/javascript">window.location.reload();</script>';
    } else {
        $out .= '<hr style="margin: 20px 0"><div style="text-align:center;">Hai importato <b>TUTTI</b> i post.<br>Puoi <a href="/wp-admin">uscire</a> da questa pagina.</div>';
    }
    $out .= '</body>';
    print $out;
    exit();
}

function getTypeTable($tipo = false){
    $custtables = [
        'evento' => 'raiposts_eventi',
        'news' => 'raiposts_news',
        'comunicazione-int' => 'raiposts_comunicazioni',
        'job-postings' => 'raiposts_jobposting',
    ];
    if($tipo && !empty($custtables[$tipo])){
        return $custtables[$tipo];
    }
    return $custtables;
}
/***
 * funzioni/classi di appoggio per sistema (temporaneo) di creazione bulk di posts (!disabilitato)
 * è servito per popolare i db di sviluppo con migliaia di post "dummy"
 */
/*
function rest_action_create_posts(){
    global $wpdb;

    $tipiok = ['evento', 'news', 'comunicazione-int', 'job-postings'];
    $tipo = 'news';
    if(isset($_GET['tipo']) && in_array($_GET['tipo'], $tipiok)){
        $tipo = $_GET['tipo'];
    }
    $cust_table = getTypeTable($tipo);
    $conteggio_post = (int)$wpdb->get_var( "SELECT COUNT(*) FROM $cust_table" );

    $max = 100;
    $created = 0;
    for($i=0; $i<$max; $i++){
        $created += creaPost($tipo) ? 1 : 0;
    }
    header('Content-Type: text/html; charset=utf-8');
    $lipsum = new LoremIpsum();
    $countdownstart = 5;
    print '<html><head>
        <script type="text/javascript">
        remaining = '.$countdownstart.';
        function countdown(remaining) {
            document.getElementById("countdown").innerHTML = remaining;
            if(remaining <= 0){
                var d = new Date();
                var tm = d.getFullYear()+"-"+(d.getMonth()+1)+"-"+d.getDate()+"-"+d.getHours()+"-"+d.getMinutes()+"-"+d.getSeconds();
                //window.location.reload();
                href = window.location.href.split("?").shift();
                document.getElementById("countdown").innerHTML = "go!";
                window.location.href = href + "?tipo='.$tipo.'&tempo="+tm;
            } else {
                setTimeout(function(){ countdown(remaining - 1); }, 1000);
            }
        }
        setTimeout(function(){ countdown(remaining - 1); }, 1000)
        </script></head>
        <body style="text-align: center; padding: 50px; font-size: 20px; font-family: Sans-Serif">
            <p>Creati '.$created.' posts di tipo '.$tipo.'</p>
            <p><small>Nodi prima dell\'inserimento: '.$conteggio_post.'</small><br> Nodi attuali: <b>'.($conteggio_post+$created).'</b></p>
            <div id="countdown">'.$countdownstart.'</div>
        </body></html>';
    exit();
}
function creaPost($tipo){
    $lipsum = new LoremIpsum();
    global $wpdb;
    $emittenti = ['Direzione Generale', 'Affari legali', 'Mobility Manager', 'Acquisti'];
    $now = time();
    $post_id = wp_insert_post(array (
        'post_type' => $tipo,
        'post_title' => $lipsum->words(rand(4, 8)),
        'post_content' => $lipsum->words(rand(20, 50)),
        'post_status' => 'publish',
        'comment_status' => 'closed',
        'ping_status' => 'closed'
    ));
    if ($post_id) {
        $visibile_home = intval(rand(1, 10) > 3);
        $emittente = $emittenti[array_rand($emittenti)];
        $priorita = rand(1,5);
        add_post_meta($post_id, 'wpcf-visibile-in-home-page', $visibile_home);
        add_post_meta($post_id, 'wpcf-emittente', $emittente);
        add_post_meta($post_id, 'wpcf-data-pubblicazione', $now);
        $inizio = 0;
        $fine = 0;
        if($visibile_home){
            add_post_meta($post_id, 'wpcf-priorita', $priorita);
            if(rand(1, 10)>4){ //non sempre settati
                if($tipo == 'evento'){
                    $inizio = dateAddRandom($now, 2, 20);
                    $fine = dateAddRandom($inizio, 0, 5);
                    add_post_meta($post_id, 'wpcf-data-evento', $inizio);
                    add_post_meta($post_id, 'wpcf-data-fine-evento', $fine);
                    wp_set_object_terms($post_id, rndTerm('tipo-di-evento'), 'tipo-di-evento');
                } else {
                    $inizio = dateAddRandom($now, -10, 2);
                    $fine = dateAddRandom($inizio, 10, 30);
                    add_post_meta($post_id, 'wpcf-visibile-dal', $inizio);
                    add_post_meta($post_id, 'wpcf-al', $fine);
                    switch ($tipo){
                        case 'news':
                            setRndTerm($post_id, 'tipo-di-news');
                            break;
                        case 'job-postings':
                            setRndTerm($post_id, 'luogo');
                            setRndTerm($post_id, 'mansione');
                            break;
                        case 'comunicazione-int':
                            setRndTerm($post_id, 'tipo-di-comunicazioni-int');
                            break;
                        case 'attivita-iniziative':
                            setRndTerm($post_id, 'tipo-di-attivita-iniziative');
                            break;
                        default:

                    }
                }
            }
        } else {
            $priorita = 100;
        }
        $cust_table = getTypeTable($tipo);
        if($tipo == 'evento'){
            $kinizio = 'data_evento';
            $kfine = 'data_fine_evento';
        } else {
            $kinizio = 'visibile_dal';
            $kfine = 'al';
        }
        $colvalues = [
            'post_id' => $post_id,
            'visibile_in_home_page' => $visibile_home,
            'priorita' => $priorita,
            'emittente' => $emittente,
            'data_pubblicazione' => $now,
            $kinizio => $inizio,
            $kfine => $fine
        ];
        $coltypes = [
            '%d',
            '%d',
            '%d',
            '%s',
            '%d',
            '%d',
            '%d',
        ];

        $wpdb->insert(
            $cust_table,
            $colvalues,
            $coltypes
        );
        return true;
    }
    return false;
}
function setRndTerm($post_id, $tax){
    $termid = rndTerm($tax);
    wp_set_object_terms($post_id, $termid, $tax);
}
function rndTerm($tax){
    $terms = get_terms(array(
        'taxonomy' => $tax,
        'hide_empty' => false,
    ));
    $term_rand = $terms[array_rand($terms)];
    return $term_rand->term_id;

}
function dateAddRandom($starttime, $min, $max){
    $daysadd = rand($min, $max)*60*60*24;
    return $starttime+$daysadd;
}

// TOOLS
class LoremIpsum{
    private $first = false;
    public $words = array(
        // Lorem ipsum...
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',

        // and the rest of the vocabulary
        'a', 'ac', 'accumsan', 'ad', 'aenean', 'aliquam', 'aliquet', 'ante',
        'aptent', 'arcu', 'at', 'auctor', 'augue', 'bibendum', 'blandit',
        'class', 'commodo', 'condimentum', 'congue', 'consequat', 'conubia',
        'convallis', 'cras', 'cubilia', 'curabitur', 'curae', 'cursus',
        'dapibus', 'diam', 'dictum', 'dictumst', 'dignissim', 'dis', 'donec',
        'dui', 'duis', 'efficitur', 'egestas', 'eget', 'eleifend', 'elementum',
        'enim', 'erat', 'eros', 'est', 'et', 'etiam', 'eu', 'euismod', 'ex',
        'facilisi', 'facilisis', 'fames', 'faucibus', 'felis', 'fermentum',
        'feugiat', 'finibus', 'fringilla', 'fusce', 'gravida', 'habitant',
        'habitasse', 'hac', 'hendrerit', 'himenaeos', 'iaculis', 'id',
        'imperdiet', 'in', 'inceptos', 'integer', 'interdum', 'justo',
        'lacinia', 'lacus', 'laoreet', 'lectus', 'leo', 'libero', 'ligula',
        'litora', 'lobortis', 'luctus', 'maecenas', 'magna', 'magnis',
        'malesuada', 'massa', 'mattis', 'mauris', 'maximus', 'metus', 'mi',
        'molestie', 'mollis', 'montes', 'morbi', 'mus', 'nam', 'nascetur',
        'natoque', 'nec', 'neque', 'netus', 'nibh', 'nisi', 'nisl', 'non',
        'nostra', 'nulla', 'nullam', 'nunc', 'odio', 'orci', 'ornare',
        'parturient', 'pellentesque', 'penatibus', 'per', 'pharetra',
        'phasellus', 'placerat', 'platea', 'porta', 'porttitor', 'posuere',
        'potenti', 'praesent', 'pretium', 'primis', 'proin', 'pulvinar',
        'purus', 'quam', 'quis', 'quisque', 'rhoncus', 'ridiculus', 'risus',
        'rutrum', 'sagittis', 'sapien', 'scelerisque', 'sed', 'sem', 'semper',
        'senectus', 'sociosqu', 'sodales', 'sollicitudin', 'suscipit',
        'suspendisse', 'taciti', 'tellus', 'tempor', 'tempus', 'tincidunt',
        'torquent', 'tortor', 'tristique', 'turpis', 'ullamcorper', 'ultrices',
        'ultricies', 'urna', 'ut', 'varius', 'vehicula', 'vel', 'velit',
        'venenatis', 'vestibulum', 'vitae', 'vivamus', 'viverra', 'volutpat',
        'vulputate',
    );
    public function word($tags = false)
    {
        return $this->words(1, $tags);
    }
    public function wordsArray($count = 1, $tags = false)
    {
        return $this->words($count, $tags, true);
    }

    public function words($count = 1, $tags = false, $array = false)
    {
        $words      = array();
        $word_count = 0;

        // Shuffles and appends the word list to compensate for count
        // arguments that exceed the size of our vocabulary list
        while ($word_count < $count) {
            $shuffle = true;

            while ($shuffle) {
                $this->shuffle();

                // Checks that the last word of the list and the first word of
                // the list that's about to be appended are not the same
                if (!$word_count || $words[$word_count - 1] != $this->words[0]) {
                    $words      = array_merge($words, $this->words);
                    $word_count = count($words);
                    $shuffle    = false;
                }
            }
        }

        $words = array_slice($words, 0, $count);

        return $this->output($words, $tags, $array);
    }
    public function sentence($tags = false)
    {
        return $this->sentences(1, $tags);
    }

    public function sentencesArray($count = 1, $tags = false)
    {
        return $this->sentences($count, $tags, true);
    }

    public function sentences($count = 1, $tags = false, $array = false)
    {
        $sentences = array();

        for ($i = 0; $i < $count; $i++) {
            $sentences[] = $this->wordsArray($this->gauss(24.46, 5.08));
        }

        $this->punctuate($sentences);

        return $this->output($sentences, $tags, $array);
    }

    public function paragraph($tags = false)
    {
        return $this->paragraphs(1, $tags);
    }

    public function paragraphsArray($count = 1, $tags = false)
    {
        return $this->paragraphs($count, $tags, true);
    }

    public function paragraphs($count = 1, $tags = false, $array = false)
    {
        $paragraphs = array();

        for ($i = 0; $i < $count; $i++) {
            $paragraphs[] = $this->sentences($this->gauss(5.8, 1.93));
        }

        return $this->output($paragraphs, $tags, $array, "\n\n");
    }

    private function gauss($mean, $std_dev)
    {
        $x = mt_rand() / mt_getrandmax();
        $y = mt_rand() / mt_getrandmax();
        $z = sqrt(-2 * log($x)) * cos(2 * pi() * $y);

        return $z * $std_dev + $mean;
    }

    private function shuffle()
    {
        if ($this->first) {
            $this->first = array_slice($this->words, 0, 8);
            $this->words = array_slice($this->words, 8);

            shuffle($this->words);

            $this->words = $this->first + $this->words;

            $this->first = false;
        } else {
            shuffle($this->words);
        }
    }

    private function punctuate(&$sentences)
    {
        foreach ($sentences as $key => $sentence) {
            $words = count($sentence);

            // Only worry about commas on sentences longer than 4 words
            if ($words > 4) {
                $mean    = log($words, 6);
                $std_dev = $mean / 6;
                $commas  = round($this->gauss($mean, $std_dev));

                for ($i = 1; $i <= $commas; $i++) {
                    $word = round($i * $words / ($commas + 1));

                    if ($word < ($words - 1) && $word > 0) {
                        $sentence[$word] .= ',';
                    }
                }
            }

            $sentences[$key] = ucfirst(implode(' ', $sentence) . '.');
        }
    }

    private function output($strings, $tags, $array, $delimiter = ' ')
    {
        if ($tags) {
            if (!is_array($tags)) {
                $tags = array($tags);
            } else {
                // Flips the array so we can work from the inside out
                $tags = array_reverse($tags);
            }

            foreach ($strings as $key => $string) {
                foreach ($tags as $tag) {
                    // Detects / applies back reference
                    if ($tag[0] == '<') {
                        $string = str_replace('$1', $string, $tag);
                    } else {
                        $string = sprintf('<%1$s>%2$s</%1$s>', $tag, $string);
                    }

                    $strings[$key] = $string;
                }
            }
        }

        if (!$array) {
            $strings = implode($delimiter, $strings);
        }

        return $strings;
    }
}
*/