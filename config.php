<?php

class Sendinbox_config
{
    // Configuration for the server
    public function server() {
        $config['server']['multy'][] = '#linkserver';
        return $config;
    }

    // Configuration for the sender
    public function sender() {
        $config['config']['threads'] = 1;                // Number of emails to send in a single request.
        $config['config']['delay'] = 3;                  // Delay in seconds between requests.
        $config['config']['color'] = true;               // Enable or disable colored output.
        $config['config']['time_zone'] = 'Asia/Kuala_Lumpur'; // Timezone for email sending.
        $config['config']['charset'] = 'utf-8';          // Character set (default is utf-8).
        $config['config']['domain_fromemail'] = false;   // Replace "from" email domain with the server domain.

        return $config;
    }

    // Configuration for the message content
    public function message() {
        $config['message']['multy']['from'][] = array(
            'name' => 'Apple Support',
            'email' => 'apple-supportno-reply{textrandom,20,2}@idmsa.apple.com'
        );

        $config['message']['multy']['subject'][] = 'Unusual Activity in your Apple ID [CASE ID #{numrandom,20,3}]';

        $config['message']['multy']['letter'][] = 'letter.html';

        return $config;
    }

    // Configuration for the email headers
    public function header() {
        $config_sender = $this->sender(); // Do not remove this line
        /* Yahoo-specific headers */
        $config['header']['MIME-Version'] = '1.0';
        $config['header']['Content-type'] = 'text/html; charset=' . $config_sender['config']['charset'];
        $config['header']['Content-Transfer-Encoding'] = '7bit';
        $config['header']['Mailer'] = 'Sendinbox Mailer';

        /* Additional headers for other email services (e.g., Hotmail) can be added here */

        return $config;
    }
}
