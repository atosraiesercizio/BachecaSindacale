<?php
add_action( 'wp_ajax_msg_zipbasket', 'msg_zipbasket' );
add_action( 'wp_ajax_nopriv_msg_zipbasket', 'msg_zipbasket' );
function msg_zipbasket(){
    $response = [
        'esito' => 'ko'
    ];

    if (get_magic_quotes_gpc()) {
        $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
        while (list($key, $val) = each($process)) {
            foreach ($val as $k => $v) {
                unset($process[$key][$k]);
                if (is_array($v)) {
                    $process[$key][stripslashes($k)] = $v;
                    $process[] = &$process[$key][stripslashes($k)];
                } else {
                    $process[$key][stripslashes($k)] = stripslashes($v);
                }
            }
        }
        unset($process);
    }
    //require(MSG_TOOLS_PLUG_PATH."/inc/zipStream.php");

    $files = str_replace('\"', '"', $_POST['files']);

    /* pulizia dei files più vecchi di x minuti */
    $quantiMinuti = 10;
    $wp_upload = wp_upload_dir();
    $zipdir = $wp_upload['basedir']."/tmp_zips";
    $zip_baseurl = $wp_upload['baseurl']."/tmp_zips";
    if(!file_exists($zipdir)){
        wp_mkdir_p($zipdir);
    }
    $oldfiles = glob($zipdir."/*");
    $ora   = time();
    foreach ($oldfiles as $file) {
        if (is_file($file)) {
            if ($ora - filemtime($file) >= 60 * $quantiMinuti) {
                unlink($file);
            }
        }
    }
    /* fine pulizia */

    if (is_array($files)) {
        $almenounfile = false;
        $verifiles = array();
        foreach ($files as $file) {
            if(parse_url($file, PHP_URL_SCHEME) != '' && $_SERVER['HTTP_HOST'] != parse_url($file, PHP_URL_HOST)){
                // url assoluta con dominio diverso, salto
                continue;
            }
            $file = wp_make_link_relative($file);
            $est = strtolower(array_pop(explode('.', $file)));
            // generic security controls...
            if (strstr($file, '../') !== FALSE || strstr($file, '.') === 0 || strstr($file, '~' || !_msgtools_zipb_estok($est))) {
                //exit("<script>alert('Error: filename/filetype not allowed!');</script>");
            } else {
                $file = ABSPATH . substr($file, 1);
                if (is_file($file)) {
                    $almenounfile = true;
                    $verifiles[] = $file;
                }
            }
        }
        if ($almenounfile) {

            //while (ob_get_level()) ob_end_clean();
            $tempo = date("Y-m-d_H-i-s");
            $current_user = wp_get_current_user();
            $uname = $current_user->user_login;
            $zipfilename = "/raiplace_files_{$uname}_{$tempo}.zip";
            $zipfullpath = $zipdir.$zipfilename;
            //$zip = new ZipStream("raiplace_files_{$uname}_{$tempo}.zip");
            $zip = new ZipArchive();
            $overwrite = file_exists($zipfullpath) ? true : false;
            if($zip->open($zipfullpath, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
                $response = [
                    'esito' => 'ko',
                    'ragione' => 'non posso scrivere il file',
                    'filename' => $zipfullpath
                ];
                wp_send_json($response);
            }
            foreach ($verifiles as $file) {
                $filename = (array_pop(explode('/', $file)));
                $zip->addFile($file, $filename);
                //$zip->addLargeFile($file, $filename);
            }
            $zip->close();
            $response = [
                'esito' => 'ok',
                'zipurl' => $zip_baseurl.$zipfilename
            ];
        } else {
            $response['reason'] = 'nemmeno un file valido';
        }
    } else {
        $response['reason'] = 'nemmeno un file';
    }

    wp_send_json($response);
}
function _msgtools_zipb_estok($ext){
    $extok = array(
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'csv',
        'jpg',
        'png',
        'gif',
        'mov',
        'avi',
        'mp3',
        'mp4'
    );
    return (in_array($ext, $extok));
}


function _msg_rel2abs($rel){
    $base = get_site_url();

    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;

    if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;

    $purl = parse_url($base);
    $path = $purl['path'];
    $host = $purl['host'];
    $scheme = $purl['scheme'];

    $path = preg_replace('#/[^/]*$#', '', $path);

    if ($rel[0] == '/') $path = '';

    $abs = "$host$path/$rel";

    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

    return $scheme.'://'.$abs;
}

