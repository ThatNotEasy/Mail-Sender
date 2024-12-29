<?php

error_reporting(0);
define('SENDINBOX_PATH', realpath(dirname(__FILE__)));

// Include required modules
require_once('Modules/SendinboxMailer/sendinboxMailer.php');
require_once('Modules/sdata-master/sdata-modules.php');
require_once('sendinbox-config.php');

// Main Sendinbox class
class Sendinbox extends Sendinbox_config
{
    public $sdata;
    public $SIBmodules;
    public $Emailist;
    public $arrayData = array();

    function __construct(){
        $this->sdata = new Sdata;
        $this->SIBmodules = new SendinboxMailer($this->sender());
        $this->SIBmodules->cover();
        $this->Emailist = $this->SIBmodules->required();

        // Display header
        echo "\r\n[+] " . $this->SIBmodules->color("string", "====================================================================") . " [+]\r\n";
        echo "[+] " . $this->SIBmodules->color("string", "[ S E N D I N B O X - M A I L E R ]  ") . "\r\n";
        echo "[+] " . $this->SIBmodules->color("string", "====================================================================") . " [+]\r\n\n";

        echo $this->SIBmodules->color("string", "[sendinbox][N] --------- [EMAIL] -----------|----[SUBJECT]----|-STATUS-|------------[Server]--------------") . "\r\n";

        $this->run();
    }

    function run(){
        $config_server = $this->server();
        $config_sender = $this->sender();
        $config_message = $this->message();
        $config_header = $this->header();

        $list_total = count($this->Emailist);
        $threads = $config_sender['config']['threads']; // Do not modify

        $emailist_split = array_chunk($this->Emailist, $threads);
        foreach ($emailist_split as $key => $getEmailist) {
            foreach ($getEmailist as $key => $putEmail) {
                // Randomize settings
                $server = $config_server['server']['multy'][array_rand($config_server['server']['multy'])];
                $subject = $config_message['message']['multy']['subject'][array_rand($config_message['message']['multy']['subject'])];
                $letter = $config_message['message']['multy']['letter'][array_rand($config_message['message']['multy']['letter'])];
                $from = $config_message['message']['multy']['from'][array_rand($config_message['message']['multy']['from'])];

                // Encode subject and alias replacements
                $subject = $this->SIBmodules->alias($subject, $putEmail);
                $from['name'] = $this->SIBmodules->alias($from['name'], $putEmail);
                $from['email'] = $this->SIBmodules->alias($from['email'], $putEmail);

                $subject = "=?".$config_sender['config']['charset']."?B?".base64_encode($subject)."?=";
                $from['name'] = "=?".$config_sender['config']['charset']."?B?".base64_encode($from['name'])."?=";

                $letter = file_get_contents(SENDINBOX_PATH . '/Letter/' . $letter);
                $letter = $this->SIBmodules->alias($letter, $putEmail);
                $letter = base64_encode($letter);

                $config_header['header']['Message-ID'] = '<'.time()."-".md5("sendinbox".rand(0,99999))."-".md5(phpversion()).'-{textrandom,4,1}@'.parse_url($server)['host'].'>';

                if ($config_sender['config']['domain_fromemail'] == true) {
                    $from['email'] = str_replace(explode("@", $from['email'])[1], parse_url($server)['host'], $from['email']);
                }

                $config_header['header']['From'] = $from['name'] . ' <' . $from['email'] . '>';

                foreach ($config_header as $key => $value) {
                    foreach ($value as $subKey => $subValue) {
                        $config_header['header'][$subKey] = $this->SIBmodules->alias($subValue, $putEmail);
                    }
                }

                $this->arrayData[] = array(
                    'url' => array(
                        'url' => $server,
                        'note' => array(
                            'line' => array_search($putEmail, $this->Emailist),
                            'email' => $putEmail,
                            'server' => substr(parse_url($server)['host'], 0, 20) . " ... ",
                        ),
                    ),
                    'head' => array(
                        'post' => http_build_query(
                            array(
                                'letter' => $letter,
                                'to' => $putEmail,
                                'subject' => $subject,
                                'header' => $config_header,
                                'config' => $config_sender,
                                'note' => array(
                                    'line' => array_search($putEmail, $this->Emailist),
                                    'email' => $putEmail,
                                    'server' => substr(parse_url($server)['host'], 0, 20) . " ... ",
                                ),
                            )
                        ),
                    ),
                );

                $list_total = ($list_total - 1);
            }

            $response = $this->send();
            $this->SIBmodules->extract_message($response);
            unset($this->arrayData);
            if ($list_total == 0) {
                die($this->SIBmodules->color("bggreen", "\r\n[Sendinbox] Email sending is complete.\r\n"));
            }
            echo "[+] " . $this->SIBmodules->color("string", "=======================[ DELAY " . $config_sender['config']['delay'] . " ]===========================") . " [+]\r\n";
            sleep($config_sender['config']['delay']);
        }
    }

    function send(){
        $url = array();
        $head = array();

        foreach ($this->arrayData as $key => $arrayData) {
            $url[] = $arrayData['url'];
            $head[] = $arrayData['head'];
        }

        $request = $this->sdata->sdata($url, $head);

        $this->sdata->session_remove($request);

        unset($url);
        unset($head);

        $result = array();
        $arrayNumber = array();

        foreach ($request as $key => $value) {
            $arrayNumber[$key] = $value['data']['note']['line'];
            $result[] = array(
                'email' => $value['data']['note']['email'],
                'line' => $value['data']['note']['line'],
                'code' => $value['info']['http_code'],
                'json' => json_decode($value['respons'], true),
            );
        }

        natsort($arrayNumber);
        sort($arrayNumber);

        return array($result);
    }
}

// Initialize Sendinbox
$Sendinbox = new Sendinbox;
